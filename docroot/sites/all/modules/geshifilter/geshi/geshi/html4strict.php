<?php
/*************************************************************************************
 * html4strict.php
 * ---------------
 * Author: Oracle (oracle.shinoda@gmail.com)
 * Copyright: (c) 2004 0racle
 * Version: 1.0.0
 * Date Started: 2004/07/10
 * Last Modified: 2004/07/14
 *
 * HTML 4.01 strict language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2004/07/14 (1.0.0)
 *  - First Release
 *
 * TODO (updated 2004/07/14)
 * -------------------------
 * * Check that only HTML4 strict attributes are highlighted
 * * Eliminate empty tags that aren't allowed in HTML4 strict
 * * Split to several files - html4trans, xhtml1 etc
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
	'LANG_NAME' => 'HTML',
	'COMMENT_SINGLE' => array(),
	'COMMENT_MULTI' => array("<!--" => "-->"),
	'CASE_KEYWORDS' => 0,
	'QUOTEMARKS' => array("'", '"'),
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => array(
		1 => array(
			),
		2 => array(
			'&lt;a&gt;', '&lt;abbr&gt;', '&lt;acronym&gt;', '&lt;address&gt;', '&lt;applet&gt;',
			'&lt;a', '&lt;abbr', '&lt;acronym', '&lt;address', '&lt;applet',
			'&lt;/a&gt;', '&lt;/abbr&gt;', '&lt;/acronym&gt;', '&lt;/address&gt;', '&lt;/applet&gt;',
			'&lt;/a', '&lt;/abbr', '&lt;/acronym', '&lt;/address', '&lt;/applet',

			'&lt;base&gt;', '&lt;basefont&gt;', '&lt;bdo&gt;', '&lt;big&gt;', '&lt;blockquote&gt;', '&lt;body&gt;', '&lt;br&gt;', '&lt;button&gt;', '&lt;b&gt;',
			'&lt;base', '&lt;basefont', '&lt;bdo', '&lt;big', '&lt;blockquote', '&lt;body', '&lt;br', '&lt;button', '&lt;b',
			'&lt;/base&gt;', '&lt;/basefont&gt;', '&lt;/bdo&gt;', '&lt;/big&gt;', '&lt;/blockquote&gt;', '&lt;/body&gt;', '&lt;/br&gt;', '&lt;/button&gt;', '&lt;/b&gt;',
			'&lt;/base', '&lt;/basefont', '&lt;/bdo', '&lt;/big', '&lt;/blockquote', '&lt;/body', '&lt;/br', '&lt;/button', '&lt;/b',

			'&lt;caption&gt;', '&lt;center&gt;', '&lt;cite&gt;', '&lt;code&gt;', '&lt;colgroup&gt;', '&lt;col&gt;',
			'&lt;caption', '&lt;center', '&lt;cite', '&lt;code', '&lt;colgroup', '&lt;col',
			'&lt;/caption&gt;', '&lt;/center&gt;', '&lt;/cite&gt;', '&lt;/code&gt;', '&lt;/colgroup&gt;', '&lt;/col&gt;',
			'&lt;/caption', '&lt;/center', '&lt;/cite', '&lt;/code', '&lt;/colgroup', '&lt;/col',

			'&lt;dd&gt;', '&lt;dfn&gt;', '&lt;dir&gt;', '&lt;div&gt;', '&lt;dl&gt;', '&lt;dt&gt;',
			'&lt;dd', '&lt;dfn', '&lt;dir', '&lt;div', '&lt;dl', '&lt;dt',
			'&lt;/dd&gt;', '&lt;/dfn&gt;', '&lt;/dir&gt;', '&lt;/div&gt;', '&lt;/dl&gt;', '&lt;/dt&gt;',
			'&lt;/dd', '&lt;/dfn', '&lt;/dir', '&lt;/div', '&lt;/dl', '&lt;/dt',

			'&lt;em&gt;',
			'&lt;em',
			'&lt;/em&gt;',
			'&lt;/em',

			'&lt;fieldset&gt;', '&lt;font&gt;', '&lt;form&gt;', '&lt;frame&gt;', '&lt;frameset&gt;',
			'&lt;fieldset', '&lt;font', '&lt;form', '&lt;frame', '&lt;frameset',
			'&lt;/fieldset&gt;', '&lt;/font&gt;', '&lt;/form&gt;', '&lt;/frame&gt;', '&lt;/frameset&gt;',
			'&lt;/fieldset', '&lt;/font', '&lt;/form', '&lt;/frame', '&lt;/frameset',

			'&lt;h1&gt;', '&lt;h2&gt;', '&lt;h3&gt;', '&lt;h4&gt;', '&lt;h5&gt;', '&lt;h6&gt;', '&lt;head&gt;', '&lt;hr&gt;', '&lt;html&gt;',
			'&lt;h1', '&lt;h2', '&lt;h3', '&lt;h4', '&lt;h5', '&lt;h6', '&lt;head', '&lt;hr', '&lt;html',
			'&lt;/h1&gt;', '&lt;/h2&gt;', '&lt;/h3&gt;', '&lt;/h4&gt;', '&lt;/h5&gt;', '&lt;/h6&gt;', '&lt;/head&gt;', '&lt;/hr&gt;', '&lt;/html&gt;',
			'&lt;/h1', '&lt;/h2', '&lt;/h3', '&lt;/h4', '&lt;/h5', '&lt;/h6', '&lt;/head', '&lt;/hr', '&lt;/html',

			'&lt;iframe&gt;', '&lt;ilayer&gt;', '&lt;img&gt;', '&lt;input&gt;', '&lt;isindex&gt;', '&lt;i&gt;',
			'&lt;iframe', '&lt;ilayer', '&lt;img', '&lt;input', '&lt;isindex', '&lt;i',
			'&lt;/iframe&gt;', '&lt;/ilayer&gt;', '&lt;/img&gt;', '&lt;/input&gt;', '&lt;/isindex&gt;', '&lt;/i&gt;',
			'&lt;/iframe', '&lt;/ilayer', '&lt;/img', '&lt;/input', '&lt;/isindex', '&lt;/i',

			'&lt;kbd&gt;',
			'&lt;kbd',
			'&t;/kbd&gt;',
			'&lt;/kbd',

			'&lt;label&gt;', '&lt;legend&gt;', '&lt;link&gt;', '&lt;li&gt;',
			'&lt;label', '&lt;legend', '&lt;link', '&lt;li',
			'&lt;/label&gt;', '&lt;/legend&gt;', '&lt;/link&gt;', '&lt;/li&gt;',
			'&lt;/label', '&lt;/legend', '&lt;/link', '&lt;/li',

			'&lt;map&gt;', '&lt;meta&gt;',
			'&lt;map', '&lt;meta',
			'&lt;/map&gt;', '&lt;/meta&gt;',
			'&lt;/map', '&lt;/meta',

			'&lt;noframes&gt;', '&lt;noscript&gt;',
			'&lt;noframes', '&lt;noscript',
			'&lt;/noframes&gt;', '&lt;/noscript&gt;',
			'&lt;/noframes', '&lt;/noscript',

			'&lt;object&gt;', '&lt;ol&gt;', '&lt;optgroup&gt;', '&lt;option&gt;',
			'&lt;object', '&lt;ol', '&lt;optgroup', '&lt;option',
			'&lt;/object&gt;', '&lt;/ol&gt;', '&lt;/optgroup&gt;', '&lt;/option&gt;',
			'&lt;/object', '&lt;/ol', '&lt;/optgroup', '&lt;/option',

			'&lt;param&gt;', '&lt;pre&gt;', '&lt;p&gt;',
			'&lt;param', '&lt;pre', '&lt;p',
			'&lt;/param&gt;', '&lt;/pre&gt;', '&lt;/p&gt;',
			'&lt;/param', '&lt;/pre', '&lt;/p',

			'&lt;q&gt;',
			'&lt;q',
			'&lt;/q&gt;',
			'&lt;/q',

			'&lt;samp&gt;', '&lt;script&gt;', '&lt;select&gt;', '&lt;small&gt;', '&lt;span&gt;', '&lt;strike&gt;', '&lt;strong&gt;', '&lt;style&gt;', '&lt;sub&gt;', '&lt;sup&gt;', '&lt;s&gt;',
			'&lt;samp', '&lt;script', '&lt;select', '&lt;small', '&lt;span', '&lt;strike', '&lt;strong', '&lt;style', '&lt;sub', '&lt;sup', '&lt;s',
			'&lt;/samp&gt;', '&lt;/script&gt;', '&lt;/select&gt;', '&lt;/small&gt;', '&lt;/span&gt;', '&lt;/strike&gt;', '&lt;/strong&gt;', '&lt;/style&gt;', '&lt;/sub&gt;', '&lt;/sup&gt;', '&lt;/s&gt;',
			'&lt;/samp', '&lt;/script', '&lt;/select', '&lt;/small', '&lt;/span', '&lt;/strike', '&lt;/strong', '&lt;/style', '&lt;/sub', '&lt;/sup', '&lt;/s',

			'&lt;table&gt;', '&lt;tbody&gt;', '&lt;td&gt;', '&lt;textarea&gt;', '&lt;text&gt;', '&lt;tfoot&gt;', '&lt;thead&gt;', '&lt;th&gt;', '&lt;title&gt;', '&lt;tr&gt;', '&lt;tt&gt;',
			'&lt;table', '&lt;tbody', '&lt;td', '&lt;textarea', '&lt;text', '&lt;tfoot', '&lt;tfoot', '&lt;thead', '&lt;th', '&lt;title', '&lt;tr', '&lt;tt',
			'&lt;/table&gt;', '&lt;/tbody&gt;', '&lt;/td&gt;', '&lt;/textarea&gt;', '&lt;/text&gt;', '&lt;/tfoot&gt;', '&lt;/thead', '&lt;/tfoot', '&lt;/th&gt;', '&lt;/title&gt;', '&lt;/tr&gt;', '&lt;/tt&gt;',
			'&lt;/table', '&lt;/tbody', '&lt;/td', '&lt;/textarea', '&lt;/text', '&lt;/tfoot', '&lt;/tfoot', '&lt;/thead', '&lt;/th', '&lt;/title', '&lt;/tr', '&lt;/tt',

			'&lt;ul&gt;', '&lt;u&gt;',
			'&lt;ul', '&lt;u',
			'&lt;/ul&gt;', '&lt;/ul&gt;',
			'&lt;/ul', '&lt;/u',

			'&lt;var&gt;',
			'&lt;var',
			'&lt;/var&gt;',
			'&lt;/var',

			'&gt;', '&lt;'
			),
		3 => array(
			'abbr', 'accept-charset', 'accept', 'accesskey', 'action', 'align', 'alink', 'alt', 'archive', 'axis',
			'background', 'bgcolor', 'border',
			'cellpadding', 'cellspacing', 'char', 'char', 'charoff', 'charset', 'checked', 'cite', 'class', 'classid', 'clear', 'code', 'codebase', 'codetype', 'color', 'cols', 'colspan', 'compact', 'content', 'coords', 
			'data', 'datetime', 'declare', 'defer', 'dir', 'disabled',
			'enctype', 
			'face', 'for', 'frame', 'frameborder',
			'headers', 'height', 'href', 'hreflang', 'hspace', 'http-equiv',
			'id', 'ismap',
			'label', 'lang', 'language', 'link', 'longdesc',
			'marginheight', 'marginwidth', 'maxlength', 'media', 'method', 'multiple',
			'name', 'nohref', 'noresize', 'noshade', 'nowrap',
			'object', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onfocus', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onreset', 'onselect', 'onsubmit', 'onunload',
			'profile', 'prompt',
			'readonly', 'rel', 'rev', 'rowspan', 'rows', 'rules',
			'scheme', 'scope', 'scrolling', 'selected', 'shape', 'size', 'span', 'src', 'standby', 'start', 'style', 'summary',
			'tabindex', 'target', 'text', 'title', 'type',
			'usemap',
			'valign', 'value', 'valuetype', 'version', 'vlink', 'vspace',
			'width'
			)
		),
	'CASE_SENSITIVE' => array(
		GESHI_COMMENTS => false,
		1 => false,
		2 => false,
		3 => false,
		),
	'STYLES' => array(
		'KEYWORDS' => array(
			1 => 'color: #b1b100;',
			2 => 'color: #000000; font-weight: bold;',
			3 => 'color: #000066;'
			),
		'COMMENTS' => array(
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
			0 => 'color: #006600;'
			),
		'SCRIPT' => array(
			0 => 'color: #00bbdd;',
			1 => 'color: #ddbb00;',
			2 => 'color: #009900;'
			),
		'REGEXPS' => array(
			)
		),
	'OOLANG' => false,
	'OBJECT_SPLITTER' => '',
	'REGEXPS' => array(
		),
	'STRICT_MODE_APPLIES' => GESHI_ALWAYS,
	'SCRIPT_DELIMITERS' => array(
		0 => array(
			'<!DOCTYPE' => '>'
			),
		1 => array(
			'&' => ';'
			),
		2 => array(
			'<' => '>'
			)
	),
	'HIGHLIGHT_STRICT_BLOCK' => array(
		0 => false,
		1 => false,
		2 => true
		)
);

?>