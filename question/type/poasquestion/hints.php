<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines hint classes for the POAS abstract question type.
 *
 * @package    qtype_poasquestion
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev <oasychev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Question which could return some specific hints and want to use *withhint behaviours should implement this
 */
interface question_with_qtype_specific_hints {

    /**
     * Returns an array of available specific hint types depending on question settings
     *
     * The keys are hint type indentifiers, unique for the qtype
     * The values are interface strings with the hint description (without "hint" word!)
     */
    public function available_specific_hint_types();

    /**
     * Hint object factory
     *
     * Returns a hint object for given type
     */
    public function hint_object($hintkey);
}

/**
 * Base class for question-type specific hints
 */
abstract class qtype_specific_hint {

    /** @var object Question object, created this hint*/
    protected $question;

    /**
     * Constructs hint object, remember question to use
     */
    public function __construct($question) {
        $this->question = $question;
    }

    /**
     * Is hint based on response or not?
     *
     * @return boolean true if response is used to calculate hint (and, possibly, penalty)
     */
    abstract public function hint_response_based();

    /**
     * Returns whether question and response allows for the hint to be done
     */
    abstract public function hint_available($response = null);

    /**
     * Returns whether response is used to calculate penalty (cost) for the hint.
     */
    public function penalty_response_based() {
        return false; // Most hint have fixed penalty (cost).
    }

    /**
     * Returns penalty (cost) for using specific hint of given hint type (possibly for given response)
     * Even if response is used to calculate penalty, hint object should still return an approximation
     * to show to the student if $response is null.
     */
    abstract public function penalty_for_specific_hint($response = null);

    /**
     * Returns true if there should be only one hint button for the given situation
     *
     * TODO - define what to do with multiple instance hints and how function should really behave there,
     * implement in hinting behaviours.
     * Example of multiple instance hints is teacher-defined text hints or correctwriting question hints,
     * where could be several misplaced (deleted, extraneous) lexems.
     */
    public function is_single_instance_hint() {
        return true;
    }
}
