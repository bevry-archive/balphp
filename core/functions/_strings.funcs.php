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
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once(dirname(__FILE__).'/_general.funcs.php');

if( function_compare( 'to_string', 2.1, true, __FILE__, __LINE__ ) )
{	/**
	 * Alias for text_value
	 *
	 * @see text_value
	 */
	function to_string($value, $format = NULL)
	{
		return text_value($value, $format);
	}
}

if( function_compare( 'text_value', 2.1, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a value from it's text value to it's real value
	 *
	 * @version 2.1, December 01, 2006
	 *
	 * @param	mixed		$value
	 * @param	boolean		$format		a format to use {@see format_to_output}
	 * 
	 * @return	mixed
	 */
	function text_value($value, $format = NULL)
	{	// Turns all special values into a string
		// v2.1 - 
		$str = gettype($value) === 'string' ? $value : var_export($value, true);
		if ( $format )
		{
			if ( $format === true )
				$format = 'htmlbody';
			$str = format_to_output($str, $format);
		}
		return $str;
	}
}

if( function_compare( 'from_string', 2.4, true, __FILE__, __LINE__ ) )
{	/**
	 * Alias for real_value
	 *
	 * @see real_value
	 */
	function from_string($value)
	{
		return real_value($value);
	}
}


if( function_compare( 'real_value', 2.4, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a value from it's text value to it's real value
	 *
	 * @version 2.4, October 02, 2007
	 *
	 * @param	mixed		$value
	 * @param	boolean		$bool		convert from boolean?
	 * @param	boolean		$null		convert from null?
	 * @param	boolean		$numeric	convert from numeric?
	 * 
	 * @return	mixed
	 */
	function real_value($value, $bool = true, $null = true, $numeric = true)
	{	// Turns a value into it's actual value, regardless of strings
		// v2.4 - 02/10/2007
		// v2.3 - 01/12/2006
	
		if ( $bool && ($value === true || $value === 'TRUE' || $value === 'true') )
				return true;
		elseif( $bool && ($value === false || $value === 'FALSE' || $value === 'false') )
				return false;
		elseif( $null && ($value === NULL || $value === 'NULL' || $value === 'null' || $value === 'UNDEFINED' || $value === 'undefined') )
			return NULL;
		elseif( $numeric && is_numeric($value) )
		{
			$int = intval($value);
			$float = floatval($value);
			if ( $int == $float )
				return $int;
			else
				return $float;
		}
		else
			return $value;
	}
}

if( function_compare( 'begins_with', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Does the haystack begin with the needle?
	 * 
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 * 
	 * @return    bool
	 */
	function begins_with( $haystack, $needle )
	{
    	return strpos($haystack, $needle) === 0;
	}
}
	
if( function_compare( 'ends_with', 3, true, __FILE__, __LINE__ ) )
{	/**
	 * Does the haystack end with the needle?
	 * 
	 * @version 3, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 * 
	 * @return    bool
	 */
	function ends_with( $haystack, $needle )
	{
    	return strrpos($haystack, $needle) === strlen($haystack)-strlen($needle);
	}
}
	
if( function_compare( 'trim_value', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Trim the haystack left and right from the needle
	 * 
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 * 
	 * @return    bool
	 */
	function trim_value( $haystack, $needle )
	{
    	$haystack = ltrim_value($haystack, $needle);
    	$haystack = rtrim_value($haystack, $needle);
		return $haystack;
	}
}
	
if( function_compare( 'ltrim_value', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Trim the haystack left from the needle
	 * 
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 * 
	 * @return    bool
	 */
	function ltrim_value( $haystack, $needle )
	{
    	if ( starts_with($haystack, $needle) )	$haystack = substr($haystack, strlen($needle));
		return $haystack;
	}
}
	
if( function_compare( 'rtrim_value', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Trim the haystack right from the needle
	 * 
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$haystack
	 * @param	string	$needle
	 * 
	 * @return    bool
	 */
	function rtrim_value( $haystack, $needle )
	{
    	if ( ends_with($haystack, $needle) )	$haystack = substr($haystack, 0, strlen($haystack)-strlen($needle));
		return $haystack;
	}
}

if( function_compare( 'append_or_set', 2.1, true, __FILE__, __LINE__ ) )
{	/**
	 * If the var is set then append the value, if not make the var equal the value
	 * 
	 * @version 1, April 21, 2008
	 *
	 * @param	mixed	&$var
	 * @param	mixed	$value
	 * 
	 * @return    bool
	 */
	function append_or_set( &$var, $value )
	{
		if( empty($var) )
			$var	=	$value;
		else
			$var	.=	$value;
	}
}

if( !function_exists('format_to_output') &&  function_compare( 'format_to_output', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Format a string to specific output
	 * 
	 * @version 1, April 21, 2008
	 *
	 * @param	string	$value
	 * @param	string	$display	'raw'|'htmlbody'|'htmlattr','formvalue'|'urlencoded'
	 * 
	 * @return    bool
	 */
	function format_to_output( $value, $display )
	{
		$value = to_string($value);
		switch ( $display )
		{
			case 'raw':	
				break;
				
			case 'htmlbody':
				// Convert special chars not including 's
				$value = htmlspecialchars($value);
				break;
			
			case 'htmlattr':
			case 'formvalue':
				// Convert special chars including 's
				$value = htmlspecialchars($value, ENT_QUOTES);
				break;
				
			case 'urlencoded':
				// Encode string to be passed as part of an URL
				$value = rawurlencode( $value );
				break;
		}
		return $value;
	}
}

if( !function_exists('str_split') && function_compare('str_split', 1, true, __FILE__, __LINE__) )
{	/**
	 * Add the str_split function if it doesn't exist
	 * 
	 * @version 1
	 * 
	 * @author none
	 * @copyright none
	 * @license none
	 */
	function str_split($string,$split_length=1)
	{
		$count = strlen($string); 
		if ( $split_length < 1 ) {
			return false; 
		} elseif ( $split_length > $count ) {
			return array($string);
		} else {
			$num = (int)ceil($count/$split_length); 
			$ret = array(); 
			for($i=0;$i<$num;$i++){ 
				$ret[] = substr($string,$i*$split_length,$split_length); 
			} 
			return $ret;
		}	 
	} 
}

if( !function_exists('strleft') && function_compare('strleft', 1, true, __FILE__, __LINE__) )
{	/**
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
	function strleft($haystack, $needle) {
		return substr($haystack, 0, strpos($haystack, $needle));
	}
}

if( !function_exists('strright') && function_compare('strright', 1, true, __FILE__, __LINE__) )
{	/**
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
	function strright($haystack, $needle) {
		$pos = strrpos($haystack, $needle);
		return substr($haystack, $pos+strlen($needle));
	}
}


?>