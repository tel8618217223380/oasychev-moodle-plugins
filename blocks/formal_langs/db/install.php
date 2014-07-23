<?php
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

function xmldb_block_formal_langs_install() {
    global $DB;

    $lang = new stdClass();
    $lang->ui_name = 'Simple english';
    $lang->description = 'Simple english language definition';
    $lang->name = 'simple_english';
    $lang->scanrules = null;
    $lang->parserules = null;
    $lang->version='1.0';
    $lang->visible = 1;
    $lang->lexemname = '';
    
    $DB->insert_record('block_formal_langs',$lang);

    $lang = new stdClass();
    $lang->ui_name = 'C programming language';
    $lang->description = 'C language, with only lexer. One-line comments not supported';
    $lang->name = 'c_language';
    $lang->scanrules = null;
    $lang->parserules = null;
    $lang->version='1.0';
    $lang->visible = 1;
    $lang->lexemname = '';
    
    $DB->insert_record('block_formal_langs',$lang);


    $lang = new stdClass();
    $lang->ui_name = 'C++ programming language';
    $lang->description = 'C++ language, with only lexer. One-line comments not supported';
    $lang->name = 'cpp_language';
    $lang->scanrules = null;
    $lang->parserules = null;
    $lang->version='1.0';
    $lang->visible = 1;

    $DB->insert_record('block_formal_langs',$lang);

    $lang = new stdClass();
    $lang->ui_name = 'C formatting string rules';
    $lang->description = 'C formatting string rules, as used in printf';
    $lang->name = 'printf_language';
    $lang->scanrules = null;
    $lang->parserules = null;
    $lang->version='1.0';
    $lang->visible = 1;
    $lang->lexemname = '';

    $DB->insert_record('block_formal_langs',$lang);

}

