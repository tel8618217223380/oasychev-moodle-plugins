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
 * Capability definitions for the poasassignment module
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 * @package   mod_poasassignment
 * @copyright 2010 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//defined('MOODLE_INTERNAL') || die();

$capabilities = array(


    'mod/poasassignment:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/poasassignment:submit' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ),
    
    'mod/poasassignment:grade' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:managetasksfields'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:managetasks'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:managecriterions'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:viewownsubmission'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:finalgrades'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:selectowntask'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:seeotherstasks'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:seecriteriondescription'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:seefielddescription'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    'mod/poasassignment:manageanything'=> array(
        'captype'=>'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array (
            'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )
);

