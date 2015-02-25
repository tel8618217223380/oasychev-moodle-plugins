<?php
// This file is part of Preg question type - https://code.google.com/p/oasychev-moodle-plugins/
//
// Preg question type is free software: you can redistribute it and/or modify
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
 * Defines FA node classes.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Valeriy Streltsov <vostreltsov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/preg_regex_handler.php');
require_once($CFG->dirroot . '/question/type/preg/preg_nodes.php');
require_once($CFG->dirroot . '/question/type/preg/preg_fa.php');
require_once($CFG->dirroot . '/question/type/poasquestion/poasquestion_string.php');

/**
 * Abstract class for both nodes (operators) and leafs (operands).
 */
abstract class qtype_preg_fa_node {

    public $pregnode;    // Reference to the corresponding qtype_preg_node.

    /**
     * Returns true if this node is supported by the engine, rejection string otherwise.
     */
    public function accept() {
        return true; // Accepting anything by default.
    }

    /**
     * Creates an automaton corresponding to this node.
     * @param automaton - a reference to the automaton being built.
     * @param stack - a stack of arrays in the form of array('start' => $ref1, 'end' => $ref2),
     *                start and end states of parts of the resulting automaton.
     * @param transform - if true, perform transformations such as assertion merging
     */
    abstract protected function create_automaton_inner(&$automaton, &$stack, $transform);

    public function __construct($node, $matcher) {
        $this->pregnode = $node;
    }

    /**
     * Adds the opening tag for this node. Tricky when $transform == true.
     */
    protected function add_open_tag($transition, $transform) {
        //echo "\nthis node: {$this->pregnode->subpattern}\n";
        //echo "main transition: {$transition->pregleaf->subpattern}\n";
        $thetransition = $transition;

        if ($transform) {
            $thedelta = null;

            $search = $transition->consumeschars
                    ? array_merge($transition->mergedbefore, array($transition))
                    : $transition->mergedafter;

            // Look through all merged transitions and find one with minimal subpattern number
            foreach ($search as $merged) {
                if (/*$merged->pregleaf->subpattern < $this->pregnode->subpattern/*/$merged->pregleaf->subpattern == -1) {
                   // continue;
                }
                $newdelta = $merged->pregleaf->subpattern - $this->pregnode->subpattern;
                if ($thedelta === null || $newdelta < $thedelta) {
                    $thetransition = $merged;
                    $thedelta = $newdelta;
                }
            }
        }

        $thetransition->opentags[] = $this->pregnode;

        if ($this->pregnode->subpattern !== -2 &&
            ($thetransition->minopentag === null || $this->pregnode->subpattern < $thetransition->minopentag->subpattern)) {
            $thetransition->minopentag = $this->pregnode;
        }
    }

    /**
     * Adds the closing tag for this node. Tricky when $transform == true.
     */
    protected function add_close_tag($transition, $transform) {
        $thetransition = $transition;

        if ($transform) {
            $thedelta = null;

            $search = $transition->consumeschars
                    ? array_merge(array($transition), $transition->mergedafter)
                    : $transition->mergedbefore;

            // Look through all merged transitions and fine one with maximal subpattern number
            foreach ($search as $merged) {
                if (/*$merged->pregleaf->subpattern > */$merged->pregleaf->subpattern == -1) {
                  //  continue;
                }
                $newdelta = $merged->pregleaf->subpattern - $this->pregnode->subpattern;
                if ($thedelta === null || $newdelta > $thedelta) {
                    $thetransition = $merged;
                    $thedelta = $newdelta;
                }
            }
        }

        $thetransition->closetags[] = $this->pregnode;
    }

    public function create_automaton(&$automaton, &$stack, $transform) {
        $this->create_automaton_inner($automaton, $stack, $transform);

        // Don't augment transition if the node is not a subpattern.
        if ($this->pregnode->subpattern == -1) {
            return;
        }

        $body = array_pop($stack);

        // Copy this node to the starting transitions.
        foreach ($automaton->get_adjacent_transitions($body['start'], true) as $transition) {
            $this->add_open_tag($transition, $transform);
        }

        // Copy this node to the ending transitions.
        foreach ($automaton->get_adjacent_transitions($body['end'], false) as $transition) {
            $this->add_close_tag($transition, $transform);
        }

        $stack[] = $body;
    }

    /**
     * Merging transitions without merging states.
     *
     * @param del - uncapturing transition for deleting.
     */
    static public function go_round_transitions($automaton, $del, $endstates) {
        $clonetransitions = array();
        $tagsets = array();
        $oppositetransitions = array();
        $intersection = null;
        $flag = new qtype_preg_charset_flag();
        $flag->set_data(qtype_preg_charset_flag::TYPE_SET, new qtype_poasquestion_string("\n"));
        $charset = new qtype_preg_leaf_charset();
        $charset->flags = array(array($flag));
        $charset->userinscription = array(new qtype_preg_userinscription("\n"));
        $righttran = new qtype_preg_fa_transition(0, $charset, 1);
        $outtransitions = $automaton->get_adjacent_transitions($del->to, true);
        // Cycled last states.

        if (in_array($del->to, $endstates) || !$del->consumeschars)
        {
            return false;
        }
        // Get transitions for merging back.
        if (($del->is_unmerged_assert() && $del->is_start_anchor()) || ($del->is_eps() && in_array($del->to, $endstates))) {
            $transitions = $automaton->get_adjacent_transitions($del->from, false);
            $back = true;
        } else {
            // Get transitions for merging forward.
            $transitions = $automaton->get_adjacent_transitions($del->to, true);
            $back = false;
            // Case of changeing assrts' places while merging.//TO THINK AND TODO!!!
            /*if ($del->is_unmerged_assert() && $del->is_conditional_assert()) {
                $oppositetransitions = $automaton->get_adjacent_transitions($del->from, false);
                if (empty($oppositetransitions)) {
                    return false;
                }
                // Copy after-tags.
                // Copy open tags.
                foreach ($del->opentags as $open) {

                }
                $sets = array();
                foreach ($del->tagsets as &$set) {
                    $copytags = array();
                    foreach ($set->tags as $tag) {
                        if ($tag->pos == qtype_preg_fa_tag_set::POS_AFTER_TRANSITION) {
                            $copytags[] = $tag;
                            unset($set->tags[array_search($tag, $set->tags)]);
                        }
                    }
                    $newset = new qtype_preg_fa_tag_set(qtype_preg_fa_tag_set::POS_AT_TRANSITION);  // TODO!!!
                    $newset->tags = $copytags;
                    $sets[] = $newset;
                    if (empty($set->tags)) {
                        unset($del->tagsets[array_search($set, $del->tagsets)]);
                    }
                }
                foreach ($oppositetransitions as $opposite) {
                    // Copy after assertions.
                    $leaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
                    $leaf->assertionsafter = $del->pregleaf->assertionsafter;
                    $newleaf = $opposite->pregleaf->intersect_asserts($leaf);
                    $opposite->pregleaf = $newleaf;
                    $del->pregleaf->assertionsafter = array();

                    $tagsets = array_merge($opposite->tagsets, $sets);
                    $opposite->tagsets = $tagsets;
                }
            }*/
        }

        // Changing leafs in case of merging.
        foreach ($transitions as $transition) {
            if (!($transition->from == $transition->to && ($transition->is_unmerged_assert() || $transition->is_eps()))) {

                $tran = clone $transition;
                $delclone = clone $del;
                $tran->loopsback = $transition->loopsback || $del->loopsback;
                $tran->greediness = qtype_preg_fa_transition::min_greediness($tran->greediness, $del->greediness);
                $merged = array_merge($delclone->mergedbefore, array($delclone), $delclone->mergedafter);
                // Work with tags.
                if (!$tran->consumeschars && $del->is_eps() && $del->from !== $del->to) {
                    if ($back) {
                        $tran->mergedbefore = array_merge($tran->mergedbefore, $merged);
                    } else {
                        $tran->mergedafter = array_merge($merged, $tran->mergedafter);
                    }
                } else if ($del->is_unmerged_assert() && $del->is_start_anchor() || ($del->is_eps() && in_array($del->to, $endstates))) {
                    if ($del->pregleaf->subtype == qtype_preg_leaf_assert::SUBTYPE_CIRCUMFLEX) {

                    }
                    $tran->mergedafter = array_merge($tran->mergedafter, $merged);
                } else {
                    $tran->mergedbefore = array_merge($merged, $tran->mergedbefore);
                }

                $clonetransitions[] = $tran;

            }

        }
        // Has deleting or changing transitions.
        if (count($transitions) != 0 ) {
            if (!($del->is_unmerged_assert() && $del->pregleaf->is_start_anchor()) && !($del->is_eps() && in_array($del->to, $endstates))) {
                foreach ($clonetransitions as &$tran) {
                    if ($del->pregleaf->subtype == qtype_preg_leaf_assert::SUBTYPE_DOLLAR && !$tran->is_unmerged_assert()) {
                        $intersection = $tran->intersect($righttran);
                        if ($intersection !== null) {
                            $tran->pregleaf = $intersection->pregleaf;
                        }
                    }
                    if ($intersection !== null || $del->pregleaf->subtype != qtype_preg_leaf_assert::SUBTYPE_DOLLAR || $tran->is_unmerged_assert()) {
                        $tran->from = $del->from;
                        $tran->redirect_merged_transitions();
                        $automaton->add_transition($tran);
                    }

                }
            } else {
                foreach ($clonetransitions as &$tran) {
                    if ($del->pregleaf->subtype == qtype_preg_leaf_assert::SUBTYPE_CIRCUMFLEX && !$tran->is_unmerged_assert()) {
                        $intersection = $tran->intersect($righttran);
                        if ($intersection !== null) {
                            $tran->pregleaf = $intersection->pregleaf;
                        }
                    }
                    if ($intersection !== null || $del->pregleaf->subtype != qtype_preg_leaf_assert::SUBTYPE_CIRCUMFLEX || $tran->is_unmerged_assert()) {
                        $tran->to = $del->to;
                        $tran->redirect_merged_transitions();
                        $automaton->add_transition($tran);
                    }
                }
            }
            if (!($del->is_end_anchor() && in_array($del->to, $endstates)) && !($transition->from == $transition->to && ($transition->is_unmerged_assert() || $transition->is_eps()))) {

                $automaton->remove_transition($del);

            }
            return true;
        }
        return false;
    }


    public static function get_wordbreaks_transitions($negative, $isinto) {
        $result = array();
        // Create transitions which can replace \b and \B.
        // Create \w.
        $flagw = new qtype_preg_charset_flag();
        $flagw->set_data(qtype_preg_charset_flag::TYPE_FLAG, qtype_preg_charset_flag::SLASH_W);
        $charsetw = new qtype_preg_leaf_charset();
        $charsetw->flags = array(array($flagw));
        $charsetw->userinscription = array(new qtype_preg_userinscription("\w", qtype_preg_charset_flag::SLASH_W));
        $tranw = new qtype_preg_fa_transition(0, $charsetw, 1, qtype_preg_fa_transition::ORIGIN_TRANSITION_FIRST, false);
        // Create \W.
        $flagbigw = clone $flagw;
        $flagbigw->negative = true;
        $charsetbigw = new qtype_preg_leaf_charset();
        $charsetbigw->flags = array(array($flagbigw));
        $charsetbigw->userinscription = array(new qtype_preg_userinscription("\W", qtype_preg_charset_flag::SLASH_W));
        $tranbigw = new qtype_preg_fa_transition(0, $charsetbigw, 1, qtype_preg_fa_transition::ORIGIN_TRANSITION_FIRST, false);
        // Create ^.
        $assertcircumflex = new qtype_preg_leaf_assert_circumflex();
        $transitioncircumflex = new qtype_preg_fa_transition(0, $assertcircumflex, 1, qtype_preg_fa_transition::ORIGIN_TRANSITION_FIRST, false);
        // Create $.
        $assertdollar = new qtype_preg_leaf_assert_dollar();
        $transitiondollar = new qtype_preg_fa_transition(0, $assertdollar, 1, qtype_preg_fa_transition::ORIGIN_TRANSITION_FIRST, false);

        // Incoming transitions.
        if ($isinto) {
            $result[] = $tranw;
            $result[] = $tranbigw;
            $result[] = $transitioncircumflex;
            // Case \b.
            if (!$negative) {
                $result[] = $tranw;
            } else {
                // Case \B.
                $result[] = $tranbigw;
            }
        } else {
            // Outcoming transitions.
            // Case \b.
            if (!$negative) {
                $result[] = $tranbigw;
                $result[] = $tranw;
                $result[] = clone $tranw;
            } else {
                // Case \B.
                $result[] = $tranw;
                $result[] = $tranbigw;
                $result[] = clone $tranbigw;
            }
            $result[] = $transitiondollar;
        }
        return $result;
    }

    public static function merge_wordbreaks($tran, $automaton, &$stack_item, $changeend = true) {
        //printf($automaton->fa_to_dot());
        $fromdel = true;
        $todel = true;
        $outtransitions = $automaton->get_adjacent_transitions($tran->to, true);
        $intotransitions = $automaton->get_adjacent_transitions($tran->from, false);
        //$startstates = $this->start_states();

        // Add empty transitions if ot's nessesaary.
        if (count($outtransitions) == 0) {
            $state = $automaton->add_state();
            $pregleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $transition = new qtype_preg_fa_transition($tran->to, $pregleaf, $state, $tran->origin, $tran->consumeschars);
            $outtransitions[] = $transition;
            $todel = false;
            if ($changeend) {
                $stack_item['end'] = $state;
            }
        }
        if (count($intotransitions) == 0) {
            $state = $automaton->add_state();
            $pregleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $transition = new qtype_preg_fa_transition($state, $pregleaf, $tran->from, $tran->origin, $tran->consumeschars);
            $intotransitions[] = $transition;
            $fromdel = false;
            $stack_item['start'] = $state;
        }

        $wordbreakinto = self::get_wordbreaks_transitions($tran->pregleaf->negative, true);
        $wordbreakout = self::get_wordbreaks_transitions($tran->pregleaf->negative, false);
        foreach ($wordbreakinto as $wordbreak) {
            $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $epstran = new qtype_preg_fa_transition($wordbreak->from, $epsleaf, $wordbreak->to);
            $epstran->opentags = $tran->opentags;
            $wordbreak->mergedafter[] = $epstran;
        }
        foreach ($wordbreakout as $wordbreak) {
            $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $epstran = new qtype_preg_fa_transition($wordbreak->from, $epsleaf, $wordbreak->to);
            $epstran->closetags = $tran->closetags;
            $wordbreak->mergedbefore[] = $epstran;
        }

        // Intersect transitions.
        for ($i = 0; $i < count($wordbreakinto); $i++) {
            foreach ($intotransitions as $intotran) {
                $resultinto = $intotran->intersect($wordbreakinto[$i]);
                /*printf($wordbreakinto[$i]->get_label_for_dot($wordbreakinto[$i]->from, $wordbreakinto[$i]->to));
                var_dump("\n");
                printf($intotran->get_label_for_dot($intotran->from, $intotran->to));
                 var_dump("\n");
                printf($resultinto->get_label_for_dot($resultinto->from, $resultinto->to));
                 var_dump("\n");*/
                if ($resultinto !== null) {

                    foreach ($outtransitions as $outtran) {

                        $clone = clone $resultinto;
                        $resultout = $wordbreakout[$i]->intersect($outtran);
                        /*printf($wordbreakout[$i]->get_label_for_dot($wordbreakout[$i]->from, $wordbreakout[$i]->to));
                var_dump("\n");
                printf($outtran->get_label_for_dot($outtran->from, $outtran->to));
                 var_dump("\n");*/

                        if ($resultout !== null) {

                            // Add state and transition
                 //printf($resultinto->get_label_for_dot($resultinto->from, $resultinto->to));
                 //var_dump("\n");
                            $state = $automaton->add_state();
                            $clone->from = $intotran->from;
                            $clone->to = $state;
                            $resultout->from = $state;
                            $resultout->to = $outtran->to;
                            $clone->redirect_merged_transitions();
                            $resultout->redirect_merged_transitions();
                            $automaton->add_transition(clone $clone);
                            $automaton->add_transition(clone $resultout);
                            /*if ($fromdel) {
                                // Copy transitions from deleting states.
                                $copiedout = $automaton->get_adjacent_transitions($tran->from, true);
                                foreach ($copiedout as $copytran) {
                                    if ($copytran !== $tran) {
                                        $copytran->from = $state;
                                        $automaron->add_transition($copytran);
                                    }
                                }
                            }
                            if ($todel) {
                                // Copy transitions from deleting states.
                                $copiedinto = $automaton->get_adjacent_transitions($tran->to, false);
                                foreach ($copiedinto as $copytran) {
                                    if ($copytran !== $tran) {
                                        $copytran->to = $state;
                                        $automaton->add_transition($copytran);
                                    }
                                }
                            }*/
                            // If result should be one cycled state.
                            /*if ($intotran->from == $tran->to) {
                                $resulttran = new qtype_preg_fa_transition($state, $resultinto->pregleaf, $state, $tran->origin, $tran->consumeschars);
                                $automaton->add_transition($resulttran);
                            } else {
                                $resulttran = new qtype_preg_fa_transition($intotran->from, $resultinto->pregleaf, $state, $tran->origin, $tran->consumeschars);
                                $automaton->add_transition($resulttran);
                                $resulttran = new qtype_preg_fa_transition($state, $resultout->pregleaf, $outtran->to, $tran->origin, $tran->consumeschars);
                                $automaton->add_transition($resulttran);
                            }*/
                        }
                    }
                }
            }
        }
        //Remove repeated uncapturing transitions.
        $automaton->remove_transition($tran);
        //printf($automaton->fa_to_dot());
    }
}

/**
 * Class for leafs. They contruct trivial FAs with two states and one transition between them.
 */
class qtype_preg_fa_leaf extends qtype_preg_fa_node {

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        // Create start and end states of the resulting automaton.
        $start = $automaton->add_state();
        $end = $automaton->add_state();

        // Add a corresponding transition between them.
        $automaton->add_transition(new qtype_preg_fa_transition($start, $this->pregnode, $end));

        $stack[] = array('start' => $start, 'end' => $end);
    }
}

/**
 * Abstract class for nodes, they construct FAs by combining existing FAs.
 */
abstract class qtype_preg_fa_operator extends qtype_preg_fa_node {

    public $operands = array();    // Array of operands.

    public function __construct($node, $matcher) {
        parent::__construct($node, $matcher);
        foreach ($this->pregnode->operands as $operand) {
            $this->operands[] = $matcher->from_preg_node($operand);
        }
    }

    protected static function add_ending_eps_transition_if_needed(&$automaton, &$stack_item, $transform) {
        $outgoing = $automaton->get_adjacent_transitions($stack_item['end'], true);
        if (!empty($outgoing)) {
            $end = $automaton->add_state();
            $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $automaton->add_transition(new qtype_preg_fa_transition($stack_item['end'], $epsleaf, $end));
            $stack_item['end'] = $end;
        }
        if ($transform) {
            $incoming = $automaton->get_adjacent_transitions($stack_item['end'], false);
            foreach ($incoming as $transition) {
                $transition->set_transition_type();
                if ($transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS) {
                    qtype_preg_fa_node::go_round_transitions($automaton, $transition, array($stack_item['end']));
                }
            }
        }
    }

    protected static function merge_after_concat(&$automaton, &$stack_item, $borderstate, $changeend) {
        $incoming = $automaton->get_adjacent_transitions($borderstate, false);
        $outgoing = $automaton->get_adjacent_transitions($borderstate, true);

        foreach ($incoming as $transition) {
            $transition->set_transition_type();

            if ($transition->is_wordbreak()) {
                qtype_preg_fa_node::merge_wordbreaks($transition, $automaton, $stack_item, $changeend);
            }
        }
        $outgoing = $automaton->get_adjacent_transitions($borderstate, true);
        foreach ($outgoing as $transition) {
            $transition->set_transition_type();

            if ($transition->is_wordbreak()) {
                qtype_preg_fa_node::merge_wordbreaks($transition, $automaton, $stack_item, $changeend);
            }
        }
        $incoming = $automaton->get_adjacent_transitions($borderstate, false);
        $outgoing = $automaton->get_adjacent_transitions($borderstate, true);

        foreach ($incoming as $transition) {
            $transition->set_transition_type();
            if ($transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT) {

                qtype_preg_fa_node::go_round_transitions($automaton, $transition, array($stack_item['end']));
            }

        }
        $outgoing = $automaton->get_adjacent_transitions($borderstate, true);
        foreach ($outgoing as $transition) {
            $transition->set_transition_type();
            if ($transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT) {
                qtype_preg_fa_node::go_round_transitions($automaton, $transition, array($stack_item['end']));
            }

        }

        self::intersect($borderstate, $automaton);  // TODO: а что тут с конечным состоянием, по аналогии с бесконечными квантификаторами?
    }

    protected static function intersect($borderstate, $automaton, $del = true) {
        $charset = null;
        $charsetuncapturering = null;
        $uncapturing = array();
        $hasintersect = false;
        $changed = array();
        // Uncapturing transitions are outgoing.
        // If one transition doesn't consume chars intersect it with other.
        $incoming = $automaton->get_adjacent_transitions($borderstate, false);

        $outgoing = $automaton->get_adjacent_transitions($borderstate, true);
        foreach ($outgoing as $tran) {
            //printf($tran->get_label_for_dot($tran->from, $tran->to));
            if (!$tran->consumeschars) {
                $uncapturing[] = clone $tran;
                if ($tran->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET ) {
                    $charsetuncapturering = $tran;
                }
            }
        }
        //printf($automaton->fa_to_dot());
        //var_dump(count($uncapturing));
        if (count($uncapturing) != 0) {

            foreach ($incoming as $intran) {
                if ($intran->consumeschars) {
                    if ($intran->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET) {
                        $charset = $intran;
                    }
                    foreach ($uncapturing as $tran) {

                        //printf($intran->get_label_for_dot($intran->from, $intran->to));
                        //printf($tran->get_label_for_dot($tran->from, $tran->to));
                        $resulttran = $intran->intersect($tran);
                        if ($resulttran != NULL) {
                            $hasintersect = true;
                            $resulttran->from = $intran->from;
                            $resulttran->to = $tran->to;
                            //$resulttran->consumeschars = true;
                            if ($del) {
                                $automaton->remove_transition($tran);
                            }
                            $resulttran->redirect_merged_transitions();
                            $automaton->add_transition($resulttran);
                            //printf($automaton->fa_to_dot());
                            $changed[] = $resulttran->to;
                        } else if ($del) {
                            $automaton->remove_transition($tran);
                        }
                    }
                    if (count($outgoing) == count($uncapturing)) {
                        $automaton->remove_transition($intran);
                    }
                }
            }
            if (!$hasintersect && $charset != null && $charsetuncapturering != null) {
                $unreachable = new qtype_preg_fa_transition($charset->from, $charset->pregleaf->intersect($charsetuncapturering->pregleaf), $charsetuncapturering->to);
                $unreachable->loopsback = $charset->loopsback || $charsetuncapturering->loopsback;
                $automaton->add_transition($unreachable);
                /*foreach ($uncapturing as $tran) {
                    $automaton->remove_transition($tran);
                }*/
            }
            return $changed;
        }

        // Uncapturing transitions are incoming.
        // If one transition doesn't consume chars intersect it with other.
        $incoming = $automaton->get_adjacent_transitions($borderstate, false);

        $outgoing = $automaton->get_adjacent_transitions($borderstate, true);
        foreach ($incoming as $tran) {
            //printf($tran->get_label_for_dot($tran->from, $tran->to));
            if (!$tran->consumeschars) {
                $uncapturing[] = clone $tran;
                if ($tran->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET) {
                    $charsetuncapturering = $tran;
                }
            }
        }
        //printf($automaton->fa_to_dot());
        if (count($uncapturing) != 0) {

            foreach ($uncapturing as $tran) {

                foreach ($outgoing as $outtran) {
                    if ($outtran->consumeschars) {
                        if ($outtran->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET) {
                            $charset = $outtran;
                        }
                        $resulttran = $tran->intersect($outtran);
                        if ($resulttran != NULL) {
                            $hasintersect = true;
                            $resulttran->from = $tran->from;
                            $resulttran->to = $outtran->to;
                            //$resulttran->consumeschars = true;
                            if ($del) {
                                $automaton->remove_transition($tran);
                            }
                            $resulttran->redirect_merged_transitions();
                            $automaton->add_transition($resulttran);
                            $changed[] = $resulttran->from;
                            //printf($automaton->fa_to_dot());
                        } else if ($del) {
                            $automaton->remove_transition($tran);
                        }
                    }
                    if (count($incoming) == count($uncapturing)) {
                        $automaton->remove_transition($outtran);
                    }
                }
            }
            if (!$hasintersect && $charset != null && $charsetuncapturering != null) {
                $unreachable = new qtype_preg_fa_transition($charsetuncapturering->from, $charset->pregleaf->intersect($charsetuncapturering->pregleaf), $charset->to);
                $unreachable->loopsback = $charset->loopsback || $charsetuncapturering->loopsback;
                $automaton->add_transition($unreachable);
                /*foreach ($uncapturing as $tran) {
                    $automaton->remove_transition($tran);
                }*/
            }
            return $changed;
        }

    }

    protected static function concatenate(&$automaton, &$stack, $count, $transform) {
        if ($count < 2) {
            return;
        }
        $result = array_pop($stack);
        for ($i = 0; $i < $count - 1; $i++) {
            $cur = array_pop($stack);
            $before = false;


            $automaton->redirect_transitions($cur['end'], $result['start']);
            $borderstate = $result['start'];

            $result = array('start' => $cur['start'], 'end' => $result['end']);
            $start = $cur['start'];
            $end = $result['end'];
            $incoming = $automaton->get_adjacent_transitions($end, false);
            foreach ($incoming as $tran) {
                if ($tran->is_wordbreak()) {
                    $before = true;
                }
            }

            if ($transform) {
                self::merge_after_concat($automaton, $result, $borderstate, $before);
            }
        }

        $stack[] = $result;
       // printf ($automaton->fa_to_dot());
    }
}

/**
 * Class for concatenation.
 */
class qtype_preg_fa_node_concat extends qtype_preg_fa_operator {

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        $count = count($this->operands);
        for ($i = 0; $i < $count; $i++) {
            $this->operands[$i]->create_automaton($automaton, $stack, $transform);
        }
        self::concatenate($automaton, $stack, $count, $transform);
    }
}

/**
 * Class for alternation.
 */
class qtype_preg_fa_node_alt extends qtype_preg_fa_operator {

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        $count = count($this->operands);
        $result = null;

        // Create automata for operands and alternate them.
        for ($i = 0; $i < $count; $i++) {
            $this->operands[$i]->create_automaton($automaton, $stack, $transform);
            $cur = array_pop($stack);
            self::add_ending_eps_transition_if_needed($automaton, $cur, $transform);  // Necessary if there's a quantifier in the end.
            if ($result === null) {
                $result = $cur;
            } else {
                // Merge start and end states.
                $automaton->redirect_transitions($cur['start'], $result['start']);
                $automaton->redirect_transitions($cur['end'], $result['end']);
            }
        }

        $stack[] = $result;
    }

}

/**
 * Class containing common methods for both finite and infinite quantifiers.
 */
abstract class qtype_preg_fa_node_quant extends qtype_preg_fa_operator {

    public function accept() {
        if ($this->pregnode->possessive) {
            return get_string('possessivequant', 'qtype_preg');
        }
        return true;
    }

    protected function mark_transitions($automaton, $startstate, $endstate) {
        $outgoing = $automaton->get_adjacent_transitions($startstate, true);
        $incoming = $automaton->get_adjacent_transitions($endstate, false);
        foreach ($outgoing as $transition) {
            $transition->startsquantifier = true;
        }
        foreach ($incoming as $transition) {
            $transition->endsquantifier = true;
        }
    }
}

/**
 * Class for infinite quantifiers (*, +, {m,}).
 */
class qtype_preg_fa_node_infinite_quant extends qtype_preg_fa_node_quant {

    /**
     * Creates an automaton for * or {0,} quantifier.
     */
    private function create_aster(&$automaton, &$stack, $transform) {
        // Operand creates its automaton.
        $this->operands[0]->create_automaton($automaton, $stack, $transform);
        $body = array_pop($stack);
        $prevtrans = $automaton->get_adjacent_transitions($body['end'], false);
        $redirectedtransitions = array();
        // Now, clone all transitions from the start state to the end state.
        $greediness = $this->pregnode->lazy ? qtype_preg_fa_transition::GREED_LAZY : qtype_preg_fa_transition::GREED_GREEDY;
        $outgoing = $automaton->get_adjacent_transitions($body['start'], true);
        foreach ($outgoing as $transition) {
            $realgreediness = qtype_preg_fa_transition::min_greediness($transition->greediness, $greediness);
            $transition->greediness = $realgreediness;        // Set this field for transitions, including original body.
            $newtransition = clone $transition;
            $newtransition->from = $body['end'];
            $newtransition->redirect_merged_transitions();
            $newtransition->loopsback = true;
            $newtransition->set_transition_type();
            $automaton->add_transition($newtransition);
            $redirectedtransitions[] = $transition;
            if ($transform && ($newtransition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $newtransition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT)) {
                qtype_preg_fa_node::go_round_transitions($automaton, $newtransition, array($body['end']));
            }
        }

        $modified = $transform
                  ? self::intersect($body['end'], $automaton, false)
                  : false;

        foreach ($prevtrans as $transition) {
            $transition->set_transition_type();
            if ($transform && ($transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT)) {
                qtype_preg_fa_node::go_round_transitions($automaton, $transition,  array($body['end']));
            }
        }


        // Change end states if automaton was rebuilt with intersection.
        if (!empty($modified)) {
            foreach ($prevtrans as $prev) {
                // Add transitions for coming from cycle without intersection with wordbreak
                $newend = $automaton->add_state();
                $prev->to = $newend;
                $prev->redirect_merged_transitions();
                $automaton->add_transition($prev);
            }
            $body['end'] = $newend;

        }
        // The body automaton can be skipped by an eps-transition.
        self::add_ending_eps_transition_if_needed($automaton, $body, $transform);
        $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
        $transition = new qtype_preg_fa_transition($body['start'], $epsleaf, $body['end']);
        $automaton->add_transition($transition);

        $stack[] = $body;
    }

    /**
     * Creates an automaton for {m,} quantifier
     */
    private function create_brace(&$automaton, &$stack, $transform) {

        // Operand creates its automaton m times.
        $leftborder = $this->pregnode->leftborder;
        //printf ($automaton->fa_to_dot());
        for ($i = 0; $i < $leftborder; $i++) {
            $this->operands[0]->create_automaton($automaton, $stack, $transform);
            // The last block is repeated.
            if ($i == $leftborder - 1) {
                $cur = array_pop($stack);
                $greediness = $this->pregnode->lazy ? qtype_preg_fa_transition::GREED_LAZY : qtype_preg_fa_transition::GREED_GREEDY;
                $outgoing = $automaton->get_adjacent_transitions($cur['start'], true);
                foreach ($outgoing as $transition) {
                    $newtransition = clone $transition;
                    $realgreediness = qtype_preg_fa_transition::min_greediness($newtransition->greediness, $greediness);
                    $newtransition->greediness = $realgreediness; // Set this field only for the last repetition.
                    $newtransition->from = $cur['end'];
                    $newtransition->loopsback = true;
                    $newtransition->redirect_merged_transitions();
                    $automaton->add_transition($newtransition);
                    $newtransition->set_transition_type();
                    if ($transform && $newtransition->is_wordbreak()) {
                        qtype_preg_fa_node::merge_wordbreaks($newtransition, $automaton, $cur, false);
                    }
                    if ($transform && ($newtransition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $newtransition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT)) {
                        qtype_preg_fa_node::go_round_transitions($automaton, $newtransition, array($cur['end']));
                    }

                }

                $modified = $transform
                          ? self::intersect($cur['end'], $automaton, false)
                          : false;

                $prevtrans = $automaton->get_adjacent_transitions($cur['end'], false);
                foreach ($prevtrans as $transition) {
                    $transition->set_transition_type();
                    if ($transform && $newtransition->is_wordbreak()) {
                        qtype_preg_fa_node::merge_wordbreaks($newtransition, $automaton, $cur, false);
                    }
                    if ($transform && ($transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT)) {
                        qtype_preg_fa_node::go_round_transitions($automaton, $transition, array($cur['end']));
                    }
                }

                // Change end states if automaton was rebuilt with intersection.
                if (!empty($modified)) {
                    /*$newend = $modified[0];
                    for ($i = 1; $i < count($modified) && $modified[$i]!= $newend; $i++) {
                        $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
                        $transition = new qtype_preg_fa_transition($modified[$i], $epsleaf, $newend);
                        $automaton->add_transition($transition);
                        qtype_preg_fa_node::go_round_transitions($automaton, $transition, array($newend));
                    }
                    $cur['end'] = $newend;*/

                }
                $stack[] = $cur;
            }
        }
        // Concatenate operands.
        self::concatenate($automaton, $stack, $leftborder, $transform);
        //printf($automaton->fa_to_dot());
    }

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        if ($this->pregnode->leftborder == 0) {
            $this->create_aster($automaton, $stack, $transform);
        } else {
            $this->create_brace($automaton, $stack, $transform);
        }
        $body = array_pop($stack);
        $this->mark_transitions($automaton, $body['start'], $body['end']);

        $stack[] = $body;
    }
}

/**
 * Class for finite quantifiers {m,n}.
 */
class qtype_preg_fa_node_finite_quant extends qtype_preg_fa_node_quant {

    /**
     * Creates an automaton for ? quantifier.
     */
    private function create_qu(&$automaton, &$stack, $transform) {
        // Operand creates its automaton.
        $this->operands[0]->create_automaton($automaton, $stack, $transform);
        $body = array_pop($stack);

        // Set the greediness.
        $greediness = $this->pregnode->lazy ? qtype_preg_fa_transition::GREED_LAZY : qtype_preg_fa_transition::GREED_GREEDY;
        $outgoing = $automaton->get_adjacent_transitions($body['start'], true);
        foreach ($outgoing as $transition) {
            $realgreediness = qtype_preg_fa_transition::min_greediness($transition->greediness, $greediness);
            $transition->greediness = $realgreediness;
        }
        // The body automaton can be skipped by an eps-transition.
        self::add_ending_eps_transition_if_needed($automaton, $body, $transform);
        $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
        $transition = new qtype_preg_fa_transition($body['start'], $epsleaf, $body['end']);
        $automaton->add_transition($transition);

        $stack[] = $body;
    }

    /**
     * Creates an automaton for {m, n} quantifier.
     */
    private function create_brace(&$automaton, &$stack, $transform) {
        // Operand creates its automaton n times.
        $leftborder = $this->pregnode->leftborder;
        $rightborder = $this->pregnode->rightborder;

        for ($i = 0; $i < $rightborder; $i++) {
            $this->operands[0]->create_automaton($automaton, $stack, $transform);
            if ($i >= $leftborder) {
                $cur = array_pop($stack);
                self::add_ending_eps_transition_if_needed($automaton, $cur, $transform);
                $stack[] = $cur;
            }
        }
        $endstate = end($stack);
        $endstate = $endstate['end'];

        // Add eps-transitions to the end state. Set greediness.
        $quantified = array();
        $greediness = $this->pregnode->lazy ? qtype_preg_fa_transition::GREED_LAZY : qtype_preg_fa_transition::GREED_GREEDY;
        for ($i = 0; $i < $rightborder - $leftborder; $i++) {
            $cur = array_pop($stack);
            $outgoing = $automaton->get_adjacent_transitions($cur['start'], true);
            foreach ($outgoing as $transition) {
                $realgreediness = qtype_preg_fa_transition::min_greediness($transition->greediness, $greediness);
                $transition->greediness = $realgreediness;
            }
            $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $transition = new qtype_preg_fa_transition($cur['start'], $epsleaf, $endstate);
            $automaton->add_transition($transition);
            $transition->set_transition_type();
            if ($transform && ($transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_EPS || $transition->type == qtype_preg_fa_transition::TYPE_TRANSITION_ASSERT)) {
                qtype_preg_fa_node::go_round_transitions($automaton, $transition, array($cur['end']));
            }
            $quantified[] = $cur;
        }
        for ($i = 0; $i < $rightborder - $leftborder; $i++) {
            $cur = array_pop($quantified);
            $stack[] = $cur;
        }
        self::concatenate($automaton, $stack, $rightborder, $transform);
    }

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        if ($this->pregnode->rightborder == 0) {
            // Create start and end states of the resulting automaton.
            $start = $automaton->add_state();
            $end = $automaton->add_state();
            $epsleaf = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
            $automaton->add_transition(new qtype_preg_fa_transition($start, $epsleaf, $end));
            $stack[] = array('start' => $start, 'end' => $end);
        } else if ($this->pregnode->leftborder == 0 && $this->pregnode->rightborder == 1) {
            $this->create_qu($automaton, $stack, $transform);
        } else {
            $this->create_brace($automaton, $stack, $transform);
        }
        $body = array_pop($stack);
        $this->mark_transitions($automaton, $body['start'], $body['end']);

        $stack[] = $body;
    }
}

/**
 * Class for subexpressions.
 */
class qtype_preg_fa_node_subexpr extends qtype_preg_fa_operator {

    public function accept() {
        if ($this->pregnode->subtype == qtype_preg_node_subexpr::SUBTYPE_ONCEONLY) {
            return get_string($this->pregnode->subtype, 'qtype_preg');
        }
        return true;
    }

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        // Operand creates its automaton.
        $this->operands[0]->create_automaton($automaton, $stack, $transform);

        if ($this->pregnode->subpattern != -1) {
            $automaton->on_subexpr_added($this->pregnode, end($stack));
        }
    }
}

/**
 * Class for conditional subexpressions.
 */
class qtype_preg_fa_node_cond_subexpr extends qtype_preg_fa_operator {

    public function __construct($node, $matcher) {
        $this->pregnode = $node;

        $shift = (int)$node->is_condition_assertion();

        // Form the assertion nodes.
        switch ($this->pregnode->subtype) {
        case qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR:
            $assertpos = new qtype_preg_leaf_assert_subexpr(false, $node->number, $node->name);
            $assertneg = new qtype_preg_leaf_assert_subexpr(true, $node->number, $node->name);
            break;
        case qtype_preg_node_cond_subexpr::SUBTYPE_RECURSION:
            $assertpos = new qtype_preg_leaf_assert_recursion(false, $node->number, $node->name);
            $assertneg = new qtype_preg_leaf_assert_recursion(true, $node->number, $node->name);
            break;
        default:
            // WTF?
            $assertpos = null;
            $assertneg = null;
            break;
        }

        $concatpos = new qtype_preg_node_concat();
        $concatpos->operands[] = $assertpos;
        $concatpos->operands[] = $node->operands[0 + $shift];

        $concatneg = new qtype_preg_node_concat();
        $concatneg->operands[] = $assertneg;
        $concatneg->operands[] = $node->operands[1 + $shift];

        $alt = new qtype_preg_node_alt();
        $alt->operands[] = $concatpos;
        $alt->operands[] = $concatneg;

        $grouping = new qtype_preg_node_subexpr(qtype_preg_node_subexpr::SUBTYPE_GROUPING);
        $grouping->subpattern = $node->subpattern;
        $grouping->operands[] = $alt;

        $this->operands = array($matcher->from_preg_node($grouping));
    }

    public function accept() {
        if ($this->pregnode->subtype != qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR &&
            $this->pregnode->subtype != qtype_preg_node_cond_subexpr::SUBTYPE_RECURSION) {
            return get_string($this->pregnode->subtype, 'qtype_preg');
        }
        return true;
    }

    protected function create_automaton_inner(&$automaton, &$stack, $transform) {
        $this->operands[0]->create_automaton($automaton, $stack, $transform);
    }
}
