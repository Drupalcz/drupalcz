<?php
/*************************************************************************************
 * sql.php
 * -------
 * Author: Oracle (oracle.shinoda@gmail.com)
 * Copyright: (c) 2004 0racle
 * Version: 1.0.0
 * Date Started: 2004/06/04
 * Last Modified: 2004/07/14
 *
 * SQL language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2004/07/14 (1.0.0) - First Release
 *
 * TODO (updated 2004/07/14)
 * -------------------------
 * * Add all keywords
 * * Split this to several sql files - mysql-sql, ansi-sql etc
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
	'LANG_NAME' => 'SQL',
	'COMMENT_SINGLE' => array(1 => "--"),
	'COMMENT_MULTI' => array(),
	'CASE_KEYWORDS' => 1,
	'QUOTEMARKS' => array("'", '"'),
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => array(
		1 => array(
			'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'ALTER', 'DROP', 'SET', 'VALUES', 'FROM', 'WHERE', 'AND', 'CREATE',
			'OR', 'LEFT', 'RIGHT', 'OUTER', 'INNER', 'JOIN', 'TRUNCATE', 'TABLE', 'INTO', 'ON', 'TEMPORARY', 'TRIGGER',
			'EXPLAIN', 'VIEW', 'TRUSTED', 'PROCEDURAL', 'LANGUAGE', 'GRANT', 'IDENTIFIED', 'BY', 'IN', 'IS', 'NOT', 'NULL', 'ADD',
			'BOOLEAN', 'SETVAL', 'NEXTVAL', 'DEFAULT', 'KEY', 'AUTO_INCREMENT', 'PRIMARY', 'UNSIGNED'
			)
		),
	'CASE_SENSITIVE' => array(
		GESHI_COMMENTS => false,
		1 => false
		),
	'STYLES' => array(
		'KEYWORDS' => array(
			1 => 'color: #993333; font-weight: bold;'
			),
		'COMMENTS' => array(
			1 => 'color: #808080; font-style: italic;'
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
		'SCRIPT' => array(
			),
		'REGEXPS' => array(
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