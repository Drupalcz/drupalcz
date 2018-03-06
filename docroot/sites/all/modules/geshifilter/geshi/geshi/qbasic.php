<?php
/*************************************************************************************
 * qbasic.php
 * ----------
 * Author: Oracle (oracle.shinoda@gmail.com)
 * Copyright: (c) 2004 0racle
 * Version: 1.0.0
 * Date Started: 2004/06/20
 * Last Modified: 2004/07/14
 *
 * QBasic/QuickBASIC language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2004/07/14 (1.0.0) - First Release
 *
 * TODO (updated 2004/07/14)
 * -------------------------
 * * Make sure all possible combinations of keywords with
 *   a space in them (EXIT FOR, END SELECT) are added
 *   to the first keyword group
 * * Update colours, especially for the first keyword group
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
	'LANG_NAME' => 'QBasic/QuickBASIC',
	'COMMENT_SINGLE' => array(1 => "'", 2 => "REM"),
	'COMMENT_MULTI' => array(),
	'CASE_KEYWORDS' => 1,
	'QUOTEMARKS' => array('"'),
	'ESCAPE_CHAR' => '',
	'KEYWORDS' => array(
		1 => array(
			'DO', 'LOOP', 'WHILE', 'WEND', 'THEN', 'ELSE', 'ELSEIF', 'END IF', 'IF',
			'FOR', 'TO', 'NEXT', 'STEP', 'GOTO', 'GOSUB', 'RETURN', 'RESUME', 'SELECT',
			'CASE', 'UNTIL'
			),
		3 => array(
			'ABS', 'ABSOLUTE', 'ACCESS', 'ALIAS', 'AND', 'ANY', 'APPEND', 'AS', 'ASC', 'ATN',
			'BASE', 'BEEP', 'BINARY', 'BLOAD', 'BSAVE', 'BYVAL', 'CALL', 'CALLS', 'CASE',
			'CDBL', 'CDECL', 'CHAIN', 'CHDIR', 'CHDIR', 'CHR$', 'CINT', 'CIRCLE', 'CLEAR',
			'CLNG', 'CLOSE', 'CLS', 'COM', 'COMMAND\$', 'COMMON', 'CONST', 'COS', 'CSNG',
			'CSRLIN', 'CVD', 'CVDMBF', 'CVI', 'CVL', 'CVS', 'CVSMDF', 'DATA', 'DATE\$',
			'DECLARE', 'DEF', 'FN', 'SEG', 'DEFDBL', 'DEFINT', 'DEFLNG', 'DEFSNG', 'DEFSTR',
			'DIM', 'DOUBLE', 'DRAW', 'END', 'ENVIRON', 'ENVIRON\$', 'EOF', 'EQV', 'ERASE',
			'ERDEV', 'ERDEV\$', 'ERL', 'ERR', 'ERROR', 'EXIT', 'EXP', 'FIELD', 'FILEATTR',
			'FILES', 'FIX', 'FRE', 'FREEFILE', 'FUNCTION', 'GET', 'HEX\$', 'IMP', 'INKEY\$',
			'INP', 'INPUT', 'INPUT\$', 'INSTR', 'INT', 'INTEGER', 'IOCTL', 'IOCTL\$', 'IS',
			'KEY', 'KILL', 'LBOUND', 'LCASE\$', 'LEFT\$', 'LEN', 'LET', 'LINE', 'LIST', 'LOC',
			'LOCAL', 'LOCATE', 'LOCK', 'LOF', 'LOG', 'UNLOCK', 'LONG', 'LPOS', 'LPRINT',
			'LSET', 'LTRIM\$', 'MID\$', 'MKD\$', 'MKDIR', 'MKDMBF\$', 'MKI\$', 'MKL\$',
			'MKS\$', 'MKSMBF\$', 'MOD', 'NAME', 'NOT', 'OCT\$', 'OFF', 'ON', 'PEN', 'PLAY',
			'STRIG', 'TIMER', 'UEVENT', 'OPEN', 'OPTION', 'BASE', 'OR', 'OUT', 'OUTPUT',
			'PAINT', 'PALETTE', 'PCOPY', 'PEEK', 'PMAP', 'POINT', 'POKE', 'POS', 'PRESET',
			'PRINT', 'USING', 'PSET', 'PUT', 'RANDOM', 'RANDOMIZE', 'READ', 'REDIM', 'RESET',
			'RESTORE', 'RIGHT\$', 'RMDIR', 'RND', 'RSET', 'RTRIM\$', 'RUN', 'SADD', 'SCREEN',
			'SEEK', 'SETMEM', 'SGN', 'SHARED', 'SHELL', 'SIGNAL', 'SIN', 'SINGLE', 'SLEEP',
			'SOUND', 'SPACE\$', 'SPC', 'SQR', 'STATIC', 'STICK', 'STOP', 'STR\$', 'STRIG',
			'STRING', 'STRING\$', 'SUB', 'SWAP', 'SYSTEM', 'TAB', 'TAN', 'TIME\$', 'TIMER',
			'TROFF', 'TRON', 'TYPE', 'UBOUND', 'UCASE\$', 'UEVENT', 'UNLOCK', 'USING', 'VAL',
			'VARPTR', 'VARPTR\$', 'VARSEG', 'VIEW', 'WAIT', 'WIDTH', 'WINDOW', 'WRITE', 'XOR'
			)
		),
	'CASE_SENSITIVE' => array(
		GESHI_COMMENTS => false,
		1 => false,
		3 => false
		),
	'STYLES' => array(
		'KEYWORDS' => array(
			1 => 'color: #a1a100;',
			3 => 'color: #000066;'
			),
		'COMMENTS' => array(
			1 => 'color: #808080;',
			2 => 'color: #808080;'
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
			0 => ''
			),
		'ESCAPE_CHAR' => array(
			0 => 'color: #000099;'
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