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

require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_general.funcs.php');

if ( function_compare('strprefix', 1.0, true, __FILE__, __LINE__) ) {
	function strprefix ( $haystack, $prefix = '' ) {
		return $prefix.$haystack;
	}
}

if ( function_compare('strsuffix', 1.0, true, __FILE__, __LINE__) ) {
	function strsuffix ( $haystack, $suffix = '' ) {
		return $haystack.$suffix;
	}
}

if ( function_compare('strclean', 1.0, true, __FILE__, __LINE__) ) {

	/**
	 * Cleans unwanted characters
	 * @version 1.0, August 22, 2009
	 * @param	string		$str
	 * @return	string
	 */
	function strclean ( $str ) {
		$str_ = '';
		for($i = 0, $n = strlen($str); $i < $n; ++$i) {
			if ( ord($str[$i]) <= 127 )
				$str_ .= $str[$i];
		}
		return $str_;
	}
}


if ( function_compare('stripslashes_deep', 1.0, true, __FILE__, __LINE__) ) {

	/**
	 * Strips slashes
	 * @version 1.0, August 22, 2009
	 * @param	string		$str
	 * @return	string
	 */
	function stripslashes_deep ( $value ) {
		$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
		return $value;
	}
}

if ( function_compare('str_populate', 1.0, true, __FILE__, __LINE__) ) {

	/**
	 * Populates a string
	 * @version 1.0, September 13, 2009
	 * @param	string		$str
	 * @param	array		$params
	 * @return	string
	 */
	function str_populate ( $str, $params ) {
		return preg_replace('/\{\$(\w+)\}/ie', '\$params["${1}"]', $str);
	}
}

if ( function_compare('strinitials', 1.0, true, __FILE__, __LINE__) ) {

	/**
	 * Get the initials of a string
	 * @version 1.0, August 08, 2009
	 * @param	string		$str
	 * @return	string
	 */
	function strinitials ( $str ) {
		$str = ucwords(strtolower($str));
		return strtoupper(preg_replace('/[^A-Z]/', '', $str));
	}
}

if ( function_compare('to_string', 2.1, true, __FILE__, __LINE__) ) {

	/**
	 * Alias for text_value
	 *
	 * @see text_value
	 */
	function to_string ( $value, $format = NULL ) {
		return text_value($value, $format);
	}
}

if ( function_compare('text_value', 2.1, true, __FILE__, __LINE__) ) {

	/**
	 * Convert a value from it's text value to it's real value
	 *
	 * @version 2.1, December 01, 2006
	 *
	 * @param	mixed		$value
	 * @param	boolean		$format		a format to use {@see format_to_output}
	 *
	 * @return	mixed
	 */
	function text_value ( $value, $format = NULL ) { // Turns all special values into a string
		// v2.1 -
		$str = gettype($value) === 'string' ? $value : var_export($value, true);
		if ( $format ) {
			if ( $format === true )
				$format = 'htmlbody';
			$str = format_to_output($str, $format);
		}
		return $str;
	}
}

if ( function_compare('from_string', 2.4, true, __FILE__, __LINE__) ) {

	/**
	 * Alias for real_value
	 *
	 * @see real_value
	 */
	function from_string ( $value ) {
		return real_value($value);
	}
}

if ( function_compare('real_value', 2.6, true, __FILE__, __LINE__) ) {

	/**
	 * Convert a value from it's text value to it's real value
	 *
	 * @version 2.6, March 1, 2010
	 *
	 * @param	mixed		$value
	 * @param	boolean		$bool		convert from boolean?
	 * @param	boolean		$null		convert from null?
	 * @param	boolean		$numeric	convert from numeric?
	 * @param	boolean		$question	convert question to boolean?
	 * @param	boolean		$array		convert an array's values into real values?
	 *
	 * @return	mixed
	 */
	function real_value ( $value, $bool = true, $null = true, $numeric = true, $question = true, $array = true ) { // Turns a value into it's actual value, regardless of strings
		// v2.6, March 1, 2010 - Numbers starting with 0 are now kept as strings to keep the 0
		// v2.5, January 05, 2010 - Added array option
		// v2.4 - 02/10/2007
		// v2.3 - 01/12/2006
		
		if ( $bool && ($value === true || $value === 'TRUE' || $value === 'true' || ($question && ($value === 'on' || $value === 'yes'))) )
			return true;
		elseif ( $bool && ($value === false || $value === 'FALSE' || $value === 'false' || ($question && ($value === 'off' || $value === 'no'))) )
			return false;
		elseif ( $null && ($value === NULL || $value === 'NULL' || $value === 'null' || $value === 'UNDEFINED' || $value === 'undefined') )
			return NULL;
		elseif ( $numeric && is_numeric($value) && substr($value,0,1) !== '0' ) {
			$int = intval($value);
			$float = floatval($value);
			if ( $int == $float ) {
				return $int;
			} else {
				return $float;
			}
		} elseif ( $array && is_array($array) ){
			foreach ( $value as $_key => &$_value ) {
				$_value = real_value($_value, $bool, $null, $numeric, $question, $array);
			}
		} else {
			return $value;
		}
	}
}

if ( function_compare('begins_with', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Does the haystack begin with the needle?
	 *
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 *
	 * @return    bool
	 */
	function begins_with ( $haystack, $needle ) {
		return strpos($haystack, $needle) === 0;
	}
}

if ( function_compare('ends_with', 3, true, __FILE__, __LINE__) ) {

	/**
	 * Does the haystack end with the needle?
	 *
	 * @version 3, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 *
	 * @return    bool
	 */
	function ends_with ( $haystack, $needle ) {
		return strrpos($haystack, $needle) === strlen($haystack) - strlen($needle);
	}
}

if ( function_compare('trim_value', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Trim the haystack left and right from the needle
	 *
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 *
	 * @return    bool
	 */
	function trim_value ( $haystack, $needle ) {
		$haystack = ltrim_value($haystack, $needle);
		$haystack = rtrim_value($haystack, $needle);
		return $haystack;
	}
}

if ( function_compare('ltrim_value', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Trim the haystack left from the needle
	 *
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 *
	 * @return    bool
	 */
	function ltrim_value ( $haystack, $needle ) {
		if ( begins_with($haystack, $needle) )
			$haystack = substr($haystack, strlen($needle));
		return $haystack;
	}
}

if ( function_compare('rtrim_value', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Trim the haystack right from the needle
	 *
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 *
	 * @return    bool
	 */
	function rtrim_value ( $haystack, $needle ) {
		if ( ends_with($haystack, $needle) )
			$haystack = substr($haystack, 0, strlen($haystack) - strlen($needle));
		return $haystack;
	}
}

if ( function_compare('append_or_set', 2.1, true, __FILE__, __LINE__) ) {

	/**
	 * If the var is set then append the value, if not make the var equal the value
	 *
	 * @version 1, April 21, 2008
	 *
	 * @param	mixed	&$var
	 * @param	mixed	$value
	 *
	 * @return    bool
	 */
	function append_or_set ( &$var, $value ) {
		if ( empty($var) )
			$var = $value;
		else
			$var .= $value;
	}
}

if ( !function_exists('format_to_output') && function_compare('format_to_output', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Format a string to specific output
	 *
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$value
	 * @param	string	$display	'raw'|'htmlbody'|'htmlattr','formvalue'|'urlencoded'
	 *
	 * @return    bool
	 */
	function format_to_output ( $value, $display ) {
		$value = to_string($value);
		switch ( $display ) {
			case 'raw' :
				break;
			
			case 'htmlbody' :
				// Convert special chars not including 's
				$value = htmlspecialchars($value);
				break;
			
			case 'htmlattr' :
			case 'formvalue' :
				// Convert special chars including 's
				$value = htmlspecialchars($value, ENT_QUOTES);
				break;
			
			case 'urlencoded' :
				// Encode string to be passed as part of an URL
				$value = rawurlencode($value);
				break;
		}
		return $value;
	}
}

if ( !function_exists('str_split') && function_compare('str_split', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Add the str_split function if it doesn't exist
	 *
	 * @version 1
	 *
	 * @author none
	 * @copyright none
	 * @license none
	 */
	function str_split ( $string, $split_length = 1 ) {
		$count = strlen($string);
		if ( $split_length < 1 ) {
			return false;
		} elseif ( $split_length > $count ) {
			return array($string);
		} else {
			$num = (int)ceil($count / $split_length);
			$ret = array();
			for($i = 0; $i < $num; $i++) {
				$ret[] = substr($string, $i * $split_length, $split_length);
			}
			return $ret;
		}
	}
}

if ( !function_exists('strleft') && function_compare('strleft', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Grab the left side of the string from the needle
	 *
	 * @version 1
	 *
	 * @author none
	 * @copyright none
	 * @license none
	 *
	 * @param string	$haystack
	 * @param string	$needle
	 *
	 * @return string
	 */
	function strleft ( $haystack, $needle ) {
		return substr($haystack, 0, strpos($haystack, $needle));
	}
}

if ( !function_exists('strright') && function_compare('strright', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Grab the right side of the string from the needle
	 *
	 * @version 1
	 *
	 * @author none
	 * @copyright none
	 * @license none
	 *
	 * @param string	$haystack
	 * @param string	$needle
	 *
	 * @return string
	 */
	function strright ( $haystack, $needle ) {
		$pos = strrpos($haystack, $needle);
		return substr($haystack, $pos + strlen($needle));
	}
}


if ( function_compare('preg_unescape', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Unescape a string a pregex replace function
	 * @version 1, February 01, 2010
	 * @param string	$value
	 * @return string
	 */
	function preg_unescape ( $value ) {
		/*
		 * When using the e modifier, this function escapes some characters (namely ', ", \ and NULL) in the strings that replace the backreferences.
		 * This is done to ensure that no syntax errors arise from backreference usage with either single or double quotes (e.g. 'strlen(\'$1\')+strlen("$2")').
		 * Make sure you are aware of PHP's string syntax to know exactly how the interpreted string will look like.
		 */
		$result = str_replace(array("\\'", '\\"', '\\\\', '\\0'), array("'", '"', '\\', '\0'), $value);
		return $result;
	}
}


if ( function_compare('magic_function', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Convert a function name into a magic camel case one
	 * @version 1, February 06, 2010
	 * @param string	$value
	 * @return string
	 */
	function magic_function ( $value ) {
		$value = str_replace(array('-','_',"\t","\n"),' ',$value);
		$value = preg_replace('/[^a-zA-Z0-9_ ]/', '', $value);
		$value = ucwords($value);
		$value = str_replace(' ', '', $value);
		return $value;
	}
}

if ( function_compare('sanitize', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Sanitize passed input
	 * @version 1, February 10, 2010
	 * @param mixed		&$value
	 * @param string	$mode [optional]
	 * @return string
	 */
	function sanitize ( &$value, $mode = 'strip' ) {
		# Handle
		if ( is_array($value) ) {
			# Array
			foreach ( $value as &$_value ) {
				sanitize($_value,$mode);
			}
		}
		elseif ( is_string($value)) {
			# String
			if ( $mode === 'clean' && class_exists('HTMLPurifier') )
				$value = HTMLPurifier::getInstance()->purify($value);
			else
				$value = strip_tags($value);
		}
		elseif ( is_object($value) ) {
			# Error
			throw new Exception('Cannot sanitize passed input');
		}
	
		# Return value
		return $value;
	}

}
