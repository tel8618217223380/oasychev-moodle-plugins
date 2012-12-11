<?php
/**
 * Defines mistakes classes for the correct writing question.
 *
 * Mistakes are student errors: e.g. lexical, sequence and syntax errors
 * that displays how response differ from answer. Or we could say that
 * each mistake represent an operation, whole set of which would convert
 * response to correct answer.
 *
 * @copyright &copy; 2011  Oleg Sychev
 * @author Oleg Sychev, Volgograd State Technical University
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questions
 */

defined('MOODLE_INTERNAL') || die();

//Other necessary requires

//Base class for answer error
abstract class  qtype_correctwriting_response_mistake {
    //Error position as qtype_correctwriting_node_position object
    public $position;
    //Language name
    public $languagename;
    //Mistake message, generated by constructor
    public $mistakemsg;
    //Answer as array of tokens
    public $answer;
    //Response as array of tokens
    public $response;
    //Indexes of answer tokens involved (if applicable)
    public $answermistaken;
    //Indexes of response tokens involved (if applicable)
    public $responsemistaken;
    //Weight of mistake used in mark computation
    public $weight;

    /** Returns a message for mistakes. Used for lazy message initiallization.
        @return string mistake message
     */
    public function get_mistake_message() {
        return $this->mistakemsg;
    }
}
?>