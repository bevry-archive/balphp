<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage core
 * @version 0.1.1-final, November 11, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once (dirname(__FILE__) . '/_general.funcs.php');
require_once (dirname(__FILE__) . '/_arrays.funcs.php');
require_once (dirname(__FILE__) . '/_strings.funcs.php');

if ( !function_exists('__') && function_compare('__', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Translate some text with some params using a simple vsprintf
	 * @version 1
	 */
	function __ ( $text, $params = NULL ) {
		return vsprintf($text, $params);
	}
}

if ( function_compare('populate', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Populates some text with a set of params
	 * @version 1, January 30, 2010
	 * @param array $array
	 * @return mixed
	 */
	function populate ( $text, $params = null, $sprintf = null ) {
		# Prepare
		$result = '';
		
		# Ensure params is an array
		if ( !$params ) $params = array(); elseif ( !is_array($params) ) $params = array($params);
		
		# Use sprintf?
		if ( $sprintf === null ) {
			# Detect
			$sprintf = is_simple_array($params);
		}
		
		# Apply sprintf?
		if ( $sprintf ) {
			# Populate simple
			$result = vsprintf($text, $params);
		}
		else {
			# Populate advanced
			$result = ' '.$text;
			$result = preg_replace('/([^\\\\])\\$([a-zA-Z0-9_.]+)/ie', 'preg_unescape("${1}") . delve(\\$params, preg_unescape("${2}"))', $result);
			$result = substr($result,1);
			$result = str_replace(array('\\$','\\.'), array('$','.'), $result);
		}
		
		# Return result
		return $result;
	}
}
