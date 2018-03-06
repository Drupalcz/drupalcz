<?php
/*************************************************************************************
 * c.php
 * -----
 * Author: Oracle (oracle.shinoda@gmail.com)
 * Copyright: (c) 2004 0racle
 * Version: 1.0.0
 * Date Started: 2004/06/04
 * Last Modified: 2004/07/14
 *
 * C language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2004/07/14 (1.0.0) - First Release
 *
 * TODO (updated 2004/07/14)
 * -------------------------
 * * Get a list of inbuilt functions to add (and explore C more
 *   to complete this rather bare language file
 *
 *************************************************************************************
 *
 *     This file is part of GeSHi.
 *
 *   GeSHi is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   GeSHi is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with GeSHi; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ************************************************************************************/

$language_data = array (
	'LANG_NAME' => 'C',
	'COMMENT_SINGLE' => array(1 => "//", 2 => '#'),
	'COMMENT_MULTI' => array("/*" => "*/"),
	'CASE_KEYWORDS' => 0,
	'QUOTEMARKS' => array("'", '"'),
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => array(
		1 => array(
			'if', 'return', 'while', 'case', 'continue', 'default',
			'do', 'else', 'for', 'switch', 'goto'
			),
		2 => array(
			'null', 'false', 'break', 'true', 'function', 'enum', 'extern'
			),
		3 => array(
			'printf', 'cout'
			),
		4 => array(
			'auto', 'char', 'const', 'double',  'float', 'int', 'long',
			'register', 'short', 'signed', 'sizeof', 'static', 'struct',
			'typedef', 'union', 'unsigned', 'void', 'volatile'
			),
		),
	'CASE_SENSITIVE' => array(
		GESHI_COMMENTS => true,
		1 => false,
		2 => false,
		3 => false,
		4 => false,
		),
	'STYLES' => array(
		'KEYWORDS' => array(
			1 => 'color: #b1b100;',
			2 => 'color: #000000; font-weight: bold;',
			3 => '',
			4 => 'color: #993333;'
			),
		'COMMENTS' => array(
			1 => 'color: #808080; font-style: italic;',
			2 => 'color: #339933;',
			'MULTI' => 'color: #808080; font-style: italic;'
			),
		'ESCAPE_CHAR' => array(
			0 => 'color: #000099; font-weight: bold;'
			),
		'BRACKETS' => array(
			0 => 'color: #66cc66;'
			),
		'STRINGS' => array(
			0 => 'color: #ff0000;'
			),
		'NUMBERS' => array(
			0 => 'color: #cc66cc;'
			),
		'METHODS' => array(
			0 => 'color: #202020;'
			),
		'REGEXPS' => array(
			),
		'SCRIPT' => array(
			)
		),
	'OOLANG' => false,
	'OBJECT_SPLITTER' => '',
	'REGEXPS' => array(
		),
	'STRICT_MODE_APPLIES' => GESHI_NEVER,
	'SCRIPT_DELIMITERS' => array(
		),
	'HIGHLIGHT_STRICT_BLOCK' => array(
		)
);

?>