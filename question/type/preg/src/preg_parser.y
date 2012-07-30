%name preg_parser_
%include{
    require_once($CFG->dirroot . '/question/type/poasquestion/poasquestion_string.php');
    require_once($CFG->dirroot . '/question/type/preg/preg_nodes.php');
    require_once($CFG->dirroot . '/question/type/preg/preg_regex_handler.php');
}
%include_class {
    // Root of the Abstract Syntax Tree (AST).
    private $root;
    // Objects of qtype_preg_node_error for errors during the parsing.
    private $errornodes;
    // Count of reduces made.
    private $reducecount;
    // Open-parenthesis strings.
    private $parens;
    // Node id counter.
    private $idcounter;
    // Handling options
    public $handlingoptions;

    function __construct() {
        $this->errornodes = array();
        $this->reducecount = 0;
        $this->parens = array('grouping' => '(?:',
                              qtype_preg_node_subpatt::SUBTYPE_SUBPATT => '(',
                              qtype_preg_node_subpatt::SUBTYPE_ONCEONLY => '(?>',
                              qtype_preg_node_assert::SUBTYPE_PLA => '(?=',
                              qtype_preg_node_assert::SUBTYPE_PLB => '(?<=',
                              qtype_preg_node_assert::SUBTYPE_NLA => '(?!',
                              qtype_preg_node_assert::SUBTYPE_NLB => '(?<!',
                              qtype_preg_node_cond_subpatt::SUBTYPE_PLA => '(?(?=',
                              qtype_preg_node_cond_subpatt::SUBTYPE_PLB => '(?(?<=',
                              qtype_preg_node_cond_subpatt::SUBTYPE_NLA => '(?(?!',
                              qtype_preg_node_cond_subpatt::SUBTYPE_NLB => '(?(?<!');
        $this->idcounter = 0;
        $this->handlingoptions = new qtype_preg_handling_options;
    }

    function get_root() {
        return $this->root;
    }

    function get_error() {
        return (count($this->errornodes) > 0);
    }

    public function get_error_nodes() {
        return $this->errornodes;
    }

    /**
     * Creates and returns an error node, also adds it to the array of parser errors
     * @param subtype type of error
     * @param indfirst the starting index of the highlited area
     * @param indlast the ending index of the highlited area
     * @param addinfo additional info, supplied for this error
     * @return qtype_preg_node_error object
     */
    protected function create_error_node($subtype, $indfirst = -1, $indlast = -1, $addinfo = null, $operands = array()) {
        $newnode = new qtype_preg_node_error;
        $newnode->id = $this->idcounter++;
        $newnode->subtype = $subtype;
        $newnode->indfirst = $indfirst;
        $newnode->indlast = $indlast;
        $newnode->operands = $operands;
        $newnode->addinfo = $addinfo;
        $this->errornodes[] = $newnode;
        return $newnode;
    }

    /**
     * Creates error node(s) if there is an error in the given node.
     * @param node the node to be checked.
     */
    protected function create_error_node_from_lexer($node) {
        if (is_array($node->error)) {
            foreach ($node->error as $error) {
                $this->create_error_node($error->subtype, $error->indfirst, $error->indlast, $error->addinfo);
            }
        } else if ($node->error !== null) {
            $this->create_error_node($node->error->subtype, $node->error->indfirst, $node->error->indlast, $node->error->addinfo);
        }
    }

    /**
      * Creates and return correct parenthesis node (subpattern, groping or assertion).
      *
      * Used to avoid code duplication between empty and non-empty parenthesis.
      * @param parens parenthesis token from lexer
      * @param exprnode the node for expression inside parenthesis
      */
    protected function create_parens_node($parens, $exprnode) {
        $result = null;
        if ($parens->subtype == 'grouping' && !$this->handlingoptions->preserveallnodes) {//TODO - replace 'grouping' with new supbattern subtypes here and in the lexer
            $result = $exprnode;
        } else {
            if ($parens->subtype == 'grouping') {
                $result = new qtype_preg_node_subpatt;
            } else if ($parens->subtype === qtype_preg_node_subpatt::SUBTYPE_SUBPATT || $parens->subtype === qtype_preg_node_subpatt::SUBTYPE_ONCEONLY) {
                $result = new qtype_preg_node_subpatt;
                $result->number = $parens->number;
            } else {
                $result = new qtype_preg_node_assert;
            }
            $result->subtype = $parens->subtype;
            $result->operands[0] = $exprnode;
            $result->id = $this->idcounter++;
            $result->userinscription = $parens->userinscription . ' ... )';
        }
        $result->indfirst = $parens->indfirst;
        $result->indlast = $exprnode->indlast + 1;
        return $result;
    }
}
%parse_failure {
    if (count($this->errornodes) === 0) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_ERROR);
    }
}
%nonassoc ERROR_PREC_VERY_SHORT.
%nonassoc ERROR_PREC_SHORT.
%nonassoc ERROR_PREC.
%nonassoc CLOSEBRACK.
%left ALT.
%left CONC PARSLEAF.
%nonassoc QUANT.
%nonassoc OPENBRACK CONDSUBPATT.

start ::= lastexpr(B). {
    $this->root = B;
}

expr(A) ::= expr(B) expr(C). [CONC] {
    A = new qtype_preg_node_concat;
    A->operands[0] = B;
    A->operands[1] = C;
    A->userinscription = '';
    A->id = $this->idcounter++;
    $this->reducecount++;
    A->indfirst = B->indfirst;
    A->indlast = C->indlast;
}

expr(A) ::= expr(B) ALT expr(C). {
    //ECHO 'ALT <br/>';
    A = new qtype_preg_node_alt;
    A->operands[0] = B;
    A->operands[1] = C;
    A->userinscription = '|';
    A->id = $this->idcounter++;
    $this->reducecount++;
    A->indfirst = B->indfirst;
    A->indlast = C->indlast;
}

expr(A) ::= expr(B) ALT. {
    A = new qtype_preg_node_alt;
    A->operands[0] = B;
    A->operands[1] = new qtype_preg_leaf_meta;
    A->operands[1]->subtype = qtype_preg_leaf_meta::SUBTYPE_EMPTY;
    A->operands[1]->id = $this->idcounter++;
    A->userinscription = '|';
    A->id = $this->idcounter++;
    $this->reducecount++;
    A->indfirst = B->indfirst;
    A->indlast = B->indlast + 1;
}

expr(A) ::= expr(B) QUANT(C). {
    A = C;
    A->operands[0] = B;
    A->id = $this->idcounter++;
    $this->reducecount++;
    A->indfirst = B->indfirst;
    A->indlast = C->indlast;
    $this->create_error_node_from_lexer(C);
}

expr(A) ::= OPENBRACK(B) expr(C) CLOSEBRACK. {
    //ECHO 'SUBPATT '.$this->parens[B].'<br/>';
    A = $this->create_parens_node(B, C);
    $this->reducecount++;
}

expr(A) ::= CONDSUBPATT(D) expr(B) CLOSEBRACK expr(C) CLOSEBRACK. {
    A = new qtype_preg_node_cond_subpatt;
    if (C->type != qtype_preg_node::TYPE_NODE_ALT) {
        A->operands[0] = C;
    } else {
        if (C->operands[0]->type == qtype_preg_node::TYPE_NODE_ALT || C->operands[1]->type == qtype_preg_node::TYPE_NODE_ALT) {
            //One or two top-level alternative allowed in conditional subpattern
            A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CONDSUBPATT_TOO_MUCH_ALTER, D->indfirst, C->indlast + 1, null, array( C, B));
            $this->reducecount++;
            return;
        } else {
            A->operands[0] = C->operands[0];
            A->operands[1] = C->operands[1];
        }
    }
    A->operands[2] = new qtype_preg_node_assert;
    A->operands[2]->subtype = D->subtype;
    A->operands[2]->operands[0] = B;
    A->operands[2]->userinscription = qtype_poasquestion_string::substr(D->userinscription, 2) . ' ... )';
    A->operands[2]->id = $this->idcounter++;

    A->userinscription = D->userinscription . ' ... ) ... | .... )';
    A->id = $this->idcounter++;
    $this->reducecount++;
    A->indfirst = D->indfirst;
    A->indlast = C->indlast + 1;
}

expr(A) ::= PARSLEAF(B). {
    //ECHO 'LEAF <br/>';
    A = B;
    A->id = $this->idcounter++;
    $this->reducecount++;
    $this->create_error_node_from_lexer(B);
}

lastexpr(A) ::= expr(B). {
    A = B;
    $this->reducecount++;
}

expr(A) ::= expr(B) CLOSEBRACK. [ERROR_PREC] {
    //ECHO 'UNOPENPARENS <br/>';
    A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_CLOSE_PAREN, B->indlast + 1, B->indlast + 1, null, array(B));
    $this->reducecount++;
}

expr(A) ::= CLOSEBRACK(B). [ERROR_PREC_SHORT] {
    //ECHO 'CLOSEPARENATSTART <br/>';
    A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_CLOSE_PAREN, B->indfirst, B->indlast);
    $this->reducecount++;
}

expr(A) ::= OPENBRACK(B) expr(C). [ERROR_PREC] {
    //ECHO 'UNCLOSEDPARENS <br/>';
    $emptyparens = false;
    foreach($this->errornodes as $key=>$node) {
        if ($node->subtype == qtype_preg_node_error::SUBTYPE_WRONG_CLOSE_PAREN && $node->indfirst == B->indlast + 1) {//empty parens, avoiding two error messages
            unset($this->errornodes[$key]);
            if ($this->handlingoptions->pcrestrict) {//In strict regular expression notation empty parenthesis is OK, they just contains emptyness
                $emptynode = new qtype_preg_leaf_meta;
                $emptynode->subtype = qtype_preg_leaf_meta::SUBTYPE_EMPTY;
                $emptynode->id = $this->idcounter++;
                A = $this->create_parens_node(B, $emptynode);
            } else {//Give error for empty parenthesis - they are sure a very strange thing to do, probably an error
                A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_EMPTY_PARENS, B->indfirst, B->indlast + 1, $this->parens[B->subtype]);
            }
            $emptyparens = true;
        }
    }
    if (!$emptyparens) {//regular unclosed parens
        A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_OPEN_PAREN, B->indfirst, B->indlast, $this->parens[B->subtype], array(C));
    }
    $this->reducecount++;
}

expr(A) ::= OPENBRACK(B). [ERROR_PREC_SHORT] {
    A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_OPEN_PAREN, B->indfirst,  B->indlast, $this->parens[B->subtype]);
    $this->reducecount++;
}

expr(A) ::= CONDSUBPATT(B) expr(E) CLOSEBRACK(D) expr(C). [ERROR_PREC] {
    //ECHO 'UNCLOSEDPARENS <br/>';
    $emptyparens = false;
    foreach($this->errornodes as $key=>$node) {
        if ($node->subtype == qtype_preg_node_error::SUBTYPE_WRONG_CLOSE_PAREN && $node->indfirst == D->indlast + 1) {//empty parens, avoiding two error messages
            unset($this->errornodes[$key]);
            //TODO check if such empty parens are allowed in PCRE and upgrade like expr(A) ::= OPENBRACK(B) expr(C). [ERROR_PREC] { if needed
            A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_EMPTY_PARENS, B->indfirst, D->indlast + 1, $this->parens[B->subtype], array (E));
            $emptyparens = true;
        }
    }
    if (!$emptyparens) {//regular unclosed parens
        A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_OPEN_PAREN, B->indfirst, B->indlast, $this->parens[B->subtype], array( C, E));
    }
    $this->reducecount++;
}

expr(A) ::= CONDSUBPATT(B) expr(C). [ERROR_PREC_SHORT] {
    //ECHO 'UNCLOSEDPARENS <br/>';
    //Two unclosed parens for conditional subpatterns
    //Create only one error node to avoid confusion when reporting errors to the user
    $emptyparens = false;
    foreach($this->errornodes as $key=>$node) {
        if ($node->subtype == qtype_preg_node_error::SUBTYPE_WRONG_CLOSE_PAREN && $node->indfirst == B->indlast + 1) {//unclosed parens + empty parens, avoiding two error messages
            unset($this->errornodes[$key]);
            //TODO check if such empty parens are allowed in PCRE and upgrade like expr(A) ::= OPENBRACK(B) expr(C). [ERROR_PREC] { if needed
            A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_EMPTY_PARENS, B->indfirst, B->indlast + 1, $this->parens[B->subtype]);
            $emptyparens = true;
        }
    }
    if (!$emptyparens) {//two unclosed parens
        A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_OPEN_PAREN, B->indfirst, B->indlast, $this->parens[B->subtype], array(C));
    }
    $this->reducecount++;
}

expr(A) ::= CONDSUBPATT(B). [ERROR_PREC_VERY_SHORT] {
    A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_OPEN_PAREN, B->indfirst,  B->indlast, $this->parens[B->subtype]);
    $this->reducecount++;
}

expr(A) ::= QUANT(B). [ERROR_PREC] {
    A = $this->create_error_node(qtype_preg_node_error::SUBTYPE_QUANTIFIER_WITHOUT_PARAMETER, B->indfirst,  B->indlast);
    $this->create_error_node_from_lexer(B);
    $this->reducecount++;
}
