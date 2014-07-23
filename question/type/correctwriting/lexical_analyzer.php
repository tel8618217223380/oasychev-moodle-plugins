<?php
// This file is part of CorrectWriting question type - https://code.google.com/p/oasychev-moodle-plugins/
//
// CorrectWriting question type is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CorrectWriting is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CorrectWriting.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Defines class of lexical analyzer for correct writing question.
 *
 * Lexical analyzer object is created for each correct answer and
 * is responsible for tokenizing, looking for lexical mistakes (typos,
 * missing and extra separators etc) and other mistakes involving individual tokens,
 * merging resulting array of mistakes from all analyzers and determine
 * answer fitness for the response.
 *
 * Lexical analyzers create and use sequence analyzers to determine structural mistakes.
 * There may be more than one sequence analyzer created if there are several equal groups of
 * lexical mistakes possible.
 *
 * @copyright &copy; 2011  Oleg Sychev
 * @author Oleg Sychev, Dmitriy Mamontov, Birukova Maria, Volgograd State Technical University
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questions
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/correctwriting/question.php');
require_once($CFG->dirroot.'/question/type/correctwriting/sequence_analyzer.php');
require_once($CFG->dirroot.'/question/type/correctwriting/lexical_mistakes.php');
require_once($CFG->dirroot.'/blocks/formal_langs/tokens_base.php');
require_once($CFG->dirroot.'/blocks/formal_langs/block_formal_langs.php');
//Other necessary requires

class qtype_correctwriting_lexical_analyzer {
    protected $question;//A reference to the question object with necessary data (language id, answers, threshold etc)
    protected $language;//Language object - contains scaner, parser etc
    protected $errors;//Array of error objects - teacher errors when entering answer

    protected $mistakes;//Array of mistake objects - student errors (merged from all stages)

    protected $fitness;//Fitness, used to choose appropriate analyzer
    /**
     * A string pair with best matches, which can be passed to sequence analyzer
     * @var qtype_correctwriting_string_pair
     */
    protected $bestmatchstring;
    /**
     * Do all processing and fill all member variables
     *
     * Passed responsestring could be null, than object used just to find errors in the answers, token count etc...
     *
     * @param qtype_correctwriting_question $question
     * @param qtype_correctwriting_string_pair $bestmatchpair a pair
     * @param block_formal_langs_abstract_language $language a language
     */
    public function __construct($question, $bestmatchpair, $language) {
        $this->question = $question;

        //TODO:
        //0. Create language object
        $this->language = $language;
        //1. Scan answer and response - Mamontov
        //  - call language object to do it
        $this->bestmatchstring = $bestmatchpair;
        //2. Check for full match - stop processing if answer and response arrays are equal - Mamontov
        if ($question->are_lexeme_sequences_equal($this->bestmatchstring)) {
            $this->mistakes = array();
            $this->fitness = 0;
            return;
        }
        //3. Set array of mistakes from lexer errors - Mamontov
        $mistakes = array();
        // Mapping from error kind to our own language string
        $mistakecustomhandling = array('clanguagemulticharliteral' => 'clanguagemulticharliteral');
        if (count($bestmatchpair->comparedstring()->stream->errors) != 0) {
            /**
             * @var block_formal_langs_lexical_error $error
             */
            foreach($bestmatchpair->comparedstring()->stream->errors as $index => $error) {
                $mistake = new qtype_correctwriting_scanning_mistake();

                $message =  $error->errormessage;
                $mistake->languagename = $question->get_used_language()->name();
                $mistake->position = $bestmatchpair->comparedstring()->stream->tokens[$error->tokenindex]->position();
                $mistake->answermistaken = null;
                $mistake->responsemistaken = array( $error->tokenindex );
                $mistake->weight = $question->lexicalerrorweight;
                $mistake->stringpair = $this->bestmatchstring;
                if (array_key_exists($error->errorkind, $mistakecustomhandling)) {
                    $a = new stdClass();
                    /**
                     * @var qtype_correctwriting_node_position $pos
                     */
                    $pos = $mistake->position;

                    $a->linestart = $pos->linestart();
                    $a->colstart = $pos->colstart();
                    $a->lineend = $pos->lineend();
                    $a->colend = $pos->colend();
                    $a->value = $bestmatchpair->comparedstring()->stream->tokens[$error->tokenindex]->value();
                    $message = get_string($mistakecustomhandling[$error->errorkind],  'qtype_correctwriting', $a);
                }
                $mistake->mistakemsg = $message;
                $mistakes[] = $mistake;
            }
        }

        //4. Look for matched pairs group using block_formal_langs_token_stream::look_for_token_pairs - Birukova
        //5. Create corrected response using block_formal_langs_token_stream::correct_mistakes - Birukova
        //6. Create qtype_correctwriting_sequence_analyzer for each group of pairs, passing corrected array of tokens - Birukova or Mamontov
        $analyzer = new qtype_correctwriting_sequence_analyzer($question, $this->bestmatchstring, $language);

        //7. Select best fitted sequence analyzer using their fitness method - Birukova or Mamontov
        //8. Set array of mistakes accordingly - Birukova and Mamontov
        //  - matches_to_mistakes function  + merging mistakes from sequence analyzer
        $this->mistakes = array_merge($mistakes, $analyzer->mistakes());
        $this->fitness = $analyzer->fitness();
        //NOTE: if responsestr is null just check for errors - Mamontov
        //NOTE: if some stage create errors in answer, stop processing right there
        //NOTE: throw exception (c.f. moodle_exception and preg_exception) if there are errors when responsestr!==null - e.g. during real analysis
    }

    public function get_corrected_response() {
        return $this->bestmatchstring->correctedstring();
    }
    /**
     * Returns an array of mistakes objects for given matches_group object
     */
    public function matches_to_mistakes($group) {
    }

    /**
    * Returns fitness as aggregate measure of how students response fits this particular answer - i.e. more fitness = less mistakes
    * Used to choose best matched answer
    * Fitness is negative or zero (no errors, full match)
    * Fitness doesn't necessary equivalent to the number of mistakes as each mistake could have different weight
    */
    public function fitness() {
        return $this->fitness;
    }

    public function mistakes() {
        return $this->mistakes;
    }

    public function has_errors() {
        return !empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }

    //Other necessary access methods
}

?>