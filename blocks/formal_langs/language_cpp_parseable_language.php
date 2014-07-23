<?
// This file is part of Formal Languages block - https://code.google.com/p/oasychev-moodle-plugins/
//
// Formal Languages block is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Formal Languages block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Formal Languages block.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Defines a C++ language with parsing capabilities
 *
 * @package    blocks
 * @subpackage formal_langs
 * @copyright &copy; 2011 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev, Mamontov Dmitriy, Maria Birukova
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
global $CFG;
require_once($CFG->dirroot .'/blocks/formal_langs/language_cpp_language.php');
require_once($CFG->dirroot .'/blocks/formal_langs/parser_cpp_language.php');
require_once($CFG->dirroot .'/blocks/formal_langs/lexer_to_parser_mapper.php');

/**
 * Class block_formal_langs_lexer_cpp_mapper
 * A mapper for mapping C++ lexer to parser constants
 */
class block_formal_langs_lexer_cpp_mapper extends block_formal_langs_lexer_to_parser_mapper {
    /**
     * Construcs mapper
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Adds new type to a test
     * @param string $typename a name for type
     */
    public function introduce_type($typename)
    {
        $this->stack[count($this->stack) - 1][]= (string)$typename;
    }


    /**
     * Returns true, whether token value is string
     * @param string $name token value
     * @return boolean whether token value is type
     */
    public function is_type($name)
    {
        $result = false;
        $name = (string)$name;
        if (count($this->stack))
        {
            foreach($this->stack as $frame)
            {
                if (count($frame))
                {
                    $result = $result || in_array($name, $frame);
                }
            }
        }
        return $result;
    }

    /**
     * Returns name of parser class
     * @return name of parser class
     */
    public function parsername() {
        return 'block_formal_langs_parser_cpp_language';
    }
    /**
     * Makes parser parse specific token
     * @param block_formal_langs_token_base $token parsed token
     * @param mixed $parser parser class
     * @param mixed $token a token
     */
    protected function parse_token($token, $parser) {
        if ($token->type() != 'singleline_comment' && $token->type() != 'multiline_comment') {
            parent::parse_token($token, $parser);
        }
    }
    /**
     * Returns mappings of lexer tokens to parser tokens
     * @param string $any name for any value matching
     * @return array mapping
     */
    public function maptable($any) {
        $table = array(
            'identifier' => array( $any => 'IDENTIFIER' ),
            'typename'   => array( $any => 'TYPENAME', 'signed' => 'SIGNED', 'unsigned' => 'UNSIGNED', 'long' => 'LONG'),
            'numeric'    => array( $any => 'NUMERIC'),
            'ellipsis'   => array( $any => 'ELLIPSIS'),
            'operators'  => array(
                '-' => 'MINUS',
                '+' => 'PLUS',
                '.' => 'DOT',
                '->' => 'RIGHTARROW',
                '*'  => 'MULTIPLY',
                '&'  => 'AMPERSAND',
                '::' => 'NAMESPACE_RESOLVE',
                '++' => 'INCREMENT',
                '--' => 'DECREMENT',
                '<'  => 'LESSER',
                '>'  => 'GREATER',
                '<=' => 'LESSER_OR_EQUAL',
                '>=' => 'GREATER_OR_EQUAL',
                '!'  => 'LOGICALNOT',
                '~'  => 'BINARYNOT',
                '/'  => 'DIVISION',
                '%'  => 'MODULOSIGN',
                '<<' => 'LEFTSHIFT',
                '>>' => 'RIGHTSHIFT',
                '==' => 'EQUAL',
                '!=' => 'NOT_EQUAL',
                '|'  => 'BINARYOR',
                '^'  => 'BINARYXOR',
                '&&' => 'LOGICALAND',
                '||' => 'LOGICALOR',
                '='  => 'ASSIGN',
                '+=' => 'PLUS_ASSIGN',
                '-=' => 'MINUS_ASSIGN',
                '*=' => 'MULTIPLY_ASSIGN',
                '/=' => 'DIVISION_ASSIGN',
                '%=' => 'MODULO_ASSIGN',
                '<<=' => 'LEFTSHIFT_ASSIGN',
                '>>=' => 'RIGHTSHIFT_ASSIGN',
                '&='  => 'BINARYAND_ASSIGN',
                '|='  => 'BINARYOR_ASSIGN',
                '^='  => 'BINARYXOR_ASSIGN',
                ':'   => 'COLON'
            ),
            'question_mark' => array($any => 'QUESTION'),
            'colon' => array($any => 'COLON'),
            'semicolon' => array($any => 'SEMICOLON'),
            'keyword' => array(
                'sizeof' => 'SIZEOF',
                'new' => 'NEWKWD',
                'delete' => 'DELETE',
                'if' => 'IFKWD',
                'else' => 'ELSEKWD',
                'const_cast'       => 'CONST_CAST',
                'dynamic_cast'     => 'DYNAMIC_CAST',
                'reinterpret_cast' => 'REINTERPRET_CAST',
                'static_cast'      => 'STATIC_CAST',
                'break'            => 'BREAKKWD',
                'typedef'          => 'TYPEDEF',
                'static'           => 'STATICKWD',
                'extern'           => 'EXTERNKWD',
                'register'         => 'REGISTERKWD',
                'switch'           => 'SWITCHKWD',
                'case'             => 'CASEKWD',
                'default'          => 'DEFAULTKWD',
                'try'              => 'TRYKWD',
                'catch'            => 'CATCHKWD',
                'volatile'         => 'VOLATILEKWD',
                'goto'             => 'GOTOKWD',
                'continue'         => 'CONTINUEKWD',
                'const'            => 'CONSTKWD',
                'for'              => 'FORKWD',
                'while'            => 'WHILEKWD',
                'do'               => 'DOKWD',
                'return'           => 'RETURNKWD',
                'friend'           => 'FRIENDKWD',
                'template'         => 'TEMPLATEKWD',
                'typename'         => 'TYPENAMEKWD',
                'class'            => 'CLASSKWD',
                'struct'           => 'STRUCTKWD',
                'enum'             => 'ENUMKWD',
                'union'            => 'UNIONKWD',
                'public'           => 'PUBLICKWD',
                'private'          => 'PRIVATEKWD',
                'protected'        => 'PROTECTEDKWD',
                'signals'          => 'SIGNALSKWD',
                'slots'            => 'SLOTSKWD',
                'namespace'        => 'NAMESPACEKWD'
            ),
            'bracket' =>    array(
                '(' => 'LEFTROUNDBRACKET',
                ')' => 'RIGHTROUNDBRACKET',
                '[' => 'LEFTSQUAREBRACKET',
                ']' => 'RIGHTSQUAREBRACKET',
                '{'   => 'LEFTFIGUREBRACKET',
                '}'   => 'RIGHTFIGUREBRACKET',
            ),
            'character' =>  array( $any => 'CHARACTER'),
            'string'    =>  array( $any => 'STRING'),
            'comma'     =>  array( $any => 'COMMA' ),
            'preprocessor' => array(
                '#' => 'PREPROCESSOR_CONCAT',
                '#define'  => 'PREPROCESSOR_DEFINE',
                '##' => 'PREPROCESSOR_STRINGIFY',
                '#if' => 'PREPROCESSOR_IF',
                '#ifdef' => 'PREPROCESSOR_IFDEF',
                '#elif'  => 'PREPROCESSOR_ELIF',
                '#else'  => 'PREPROCESSOR_ELIF',
                '#endif' => 'PREPROCESSOR_ENDIF',
                $any => 'PREPROCESSOR_INCLUDE'
            ),
        );
        return $table;
    }

    /**
     * Tests, whether token is overloaded operator declaration
     * @param string $token token value
     * @return bool
     */
    public function is_operator_overload_declaration($token) {
        $ops = array(
            'operator+', 'operator-', 'operator*', 'operator/', 'operator\\', 'operator~=', 'operator&', 'operator|',
            'operator~','operator->','operator+=','operator-=','operator*=','operator/=','operator++','operator--',
            'operator%','operator%=','operator<<=','operator>>=','operator&=','operator|=','operator!=','operator!',
            'operator&&=','operator||=','operator=','operator++','operator--','operator<','operator>','operator<=',
            'operator>=','operator==','operator!=','operator&&','operator||','operator>>','operator<<','operator^',
            'operator^=','operator==',
            'operator.'
        );
        return in_array($token, $ops);
    }

    /**
     * Maps token from lexer to parser, returning name of constant to parser
     * @param block_formal_langs_token_base  $token a token name
     * @return string mapped constants name
     */
    public function map($token) {
        if ($token->type() == 'keyword') {
            if ($this->is_operator_overload_declaration($token->value())) {
                return 'OPERATOROVERLOADDECLARATION';
            }
        }
        if ($token->type() == 'identifier') {
            if ($this->is_type($token->value())) {
                return 'CUSTOMTYPENAME';
            }
        }
        return parent::map($token);
    }

}


class block_formal_langs_language_cpp_parseable_language extends block_formal_langs_predefined_language
{
    /**
     * Constructs a language
     */
    public function __construct() {
        parent::__construct(null,null);
    }

    /**
     * Returns name for language
     * @return string
     */
    public function name() {
        return 'cpp_parseable';
    }

    /**
     * Returns name for language
     * @return string
     */
    public function lexem_name() {
        return get_string('lexeme', 'block_formal_langs');
    }
    /**
     * Returns name for lexer class
     * @return string
     */
    protected function lexername() {
        return 'block_formal_langs_predefined_cpp_language_lexer_raw';
    }

    /**
     * Returns name for parser class
     * @return string
     */
    protected function parsername() {
        return 'block_formal_langs_lexer_cpp_mapper';
    }

    /**
     * Returns true if this language has parser enabled
     * @return boolean
     */
    public function could_parse() {
        return true;
    }
}
