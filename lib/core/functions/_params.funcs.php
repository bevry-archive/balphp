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
require_once (dirname(__FILE__) . '/_strings.funcs.php');

if ( function_compare('explode_querystring', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function explode_querystring ( $query_string, $amp = '&amp;' ) {
		$query_string = explode($amp, $query_string);
		$params = array();
		for($i = 0, $n = sizeof($query_string); $i < $n; ++$i) {
			$param = explode('=', $query_string[$i]);
			if ( sizeof($param) === 2 ) {
				$key = $param[0];
				$value = $param[1];
				$params[$key] = $value;
			}
		}
		return $params;
	}
}

if ( function_compare('implode_querystring', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function implode_querystring ( $query_string, $amp = '&amp;' ) {
		$params = '';
		foreach ( $query_string as $key => $value ) {
			$params .= $key . '=' . $value . $amp;
		}
		return $params;
	}
}

if ( function_compare('regenerate_params', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function regenerate_params ( $display = 'form', $params = NULL ) {
		if ( $params === NULL )
			$params = array_merge($_GET, $_POST);
		elseif ( gettype($params) === 'string' ) {
			$params = explode_querystring($params);
		}
		
		$result = '';
		
		switch ( $display ) {
			case 'form' :
				foreach ( $params as $key => $value ) {
					$result .= '<input type="hidden" name="' . $key . '" value="' . $value . '"  />';
				}
				break;
			
			default :
				die('Unknown regenerate params display: ' . $display);
				break;
		}
		
		return $result;
	}
}

if ( function_compare('get_param', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Get the param
	 * @version 2
	 * @param string $name
	 * @param mixed $default [optional]
	 * @param boolean $hydrate [optional]
	 * @param mixed $stripslashes [optional]
	 * @param boolean $hydrate [optional]
	 */
	function get_param ( $name, $default = null, $hydrate = true, $stripslashes = null, $delve = true ) {
		if ( $stripslashes === null )
			$stripslashes = get_magic_quotes_gpc() ? true : false;
		
		switch ( true ) {
			case array_key_exists($name, $_POST) :
				$value = $_POST[$name];
				$value = trim($stripslashes ? stripslashes($value) : $value);
				break;
			
			case array_key_exists($name, $_GET) :
				$value = $_GET[$name];
				$value = trim($stripslashes ? stripslashes($value) : $value);
				break;
			
			case isset($_FILES[$name]) :
				$value = $_FILES[$name];
				break;
			
			default :
				$value = $default;
		}
		
		if ( $convert )
			$value = real_value($value);
		
		return $value;
	}
}

if ( function_compare('hydrate_param_init', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Initialise our hydrated params
	 * @version 1, January 06, 2010
	 */
	function hydrate_param_init ( $once = true ) {
		# Init
		if ( defined('REQUEST_HYDRATED') ) {
			if ( $once ) return;
		} else
			define('REQUEST_HYDRATED', 1);
	
		# Prepare
		global $_POST_HYDRATED, $_GET_HYDRATED, $_REQUEST_HYDRATED, $_FILES_HYDRATED, $_PARAMS_HYDRATED;
		$_POST_HYDRATED = $_POST;
		$_GET_HYDRATED = $_GET;
		$_REQUEST_HYDRATED = $_REQUEST;
		$_FILES_HYDRATED = array();
		
		# Apply
		array_hydrate($_POST_HYDRATED);
		array_hydrate($_GET_HYDRATED);
		array_hydrate($_REQUEST_HYDRATED);
		array_hydrate($_FILES_HYDRATED);
		liberate_files($_FILES_HYDRATED);
		
		# Merge
		$_PARAMS_HYDRATED = array_merge_recursive_keys($_FILES_HYDRATED, $_GET_HYDRATED, $_POST_HYDRATED);
		
		# Done
		return true;
	}
}

if ( function_compare('fetch_param', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Fetch a hydrated param
	 * @version 1, January 06, 2010
	 */
	function fetch_param ( $name ) {
		# Prepare
		hydrate_param_init();
		global $_PARAMS_HYDRATED;
		$value = null;
		
		# Handle
		$value = array_delve($_PARAMS_HYDRATED, $name);
		
		# Hydrate the Param
		hydrate_param($value);
		
		# Done
		return $value;
	}
}


if ( function_compare('hydrate_param', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Hydrate a param
	 * @version 1, January 06, 2010
	 */
	function hydrate_param ( &$value, $realvalue = true, $stripslashes = null, $trim = true ) {
		# Prepare
		if ( $stripslashes === null )
			$stripslashes = ini_get('magic_quotes_gpc') ? true : false;
		
		# Handle
		if ( is_array($value) ) {
			# Array
			foreach ( $value as $_key => &$_value )
				hydrate_param($_value, $realvalue, $stripslashes, $trim);
		}
		else {
			# Value
			if ( is_string($value) ) {
				# Stripslashes
				if ( $stripslashes ) $value = stripslashes($value);
	
				# Trim
				if ( $trim ) $value = trim($value);
			}
		
			# Realvalue
			if ( $realvalue ) $value = real_value($value);
		}
		
		# Done
		return true;
	}
}


if ( function_compare('liberate_subfiles', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Liberate subfiles
	 * @version 1, January 06, 2010
	 */
	function liberate_subfiles ( &$where, $prefix, $suffix, $subvalue ) {
		# Prepare
		$prefix = trim($prefix, '.');
		$suffix = trim($suffix, '.');
	
		# Handle
		if ( !is_array($subvalue) ) {
			# We have reached the bottom
			$name = $prefix.'.'.$suffix;
			array_apply($where, $name, $subvalue, true); // when setting to false, PHP memory reference error occurs...
			// baldump($name, array_delve($where, $name), $subvalue);
		}
		else {
			# More sub files
			foreach ( $subvalue as $key => $value ) {
				liberate_subfiles($where, $prefix.'.'.$key, $suffix, $value);
			}
		}
	
		# Done
		return true;
	}
}


if ( function_compare('liberate_files', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Liberate files
	 * The purpose of this is when using $_FILE with param arrays, we want to be able to do this $_FILE['user']['avatar']['tmpname'] instead of $_FILE['user']['tmpname']['avatar']
	 * @version 1, January 06, 2010
	 */
	function liberate_files ( &$where ) {
		# Handle
		foreach ( $_FILES as $fileKey => $fileValue ) {
			foreach ( $fileValue as $filePartKey => $filePartValue ) {
				if ( is_array($filePartValue) ) {
					# We have a multiple file
					liberate_subfiles($where, $fileKey, $filePartKey, $filePartValue);
				}
			}
		}
	}
}

if ( function_compare('fix_magic_quotes', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Fix magic quotes
	 */
	function fix_magic_quotes ( ) {
		if ( ini_get('magic_quotes_gpc') ) {
			$_POST = array_map('stripslashes_deep', $_POST);
			$_GET = array_map('stripslashes_deep', $_GET);
			$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
			ini_set('magic_quotes_gpc', 0);
		}
	}
}

if ( function_compare('hydrate_params', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Fix magic quotes, hydrate params, and liberate files
	 */
	function hydrate_params ( ) {
		fix_magic_quotes();
		array_hydrate($_REQUEST);
		array_hydrate($_POST);
		array_hydrate($_GET);
		array_hydrate($_FILES);
		liberate_files($_FILES);
	}
}

if ( function_compare('has_true', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Checks all arguments for one which is loosely true
	 */
	function has_true ( ) {
		$args = func_get_args();
		foreach ( $args as $arg ) {
			if ( $arg )
				return true;
		}
	}
}

if ( function_compare('cycle', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Cycle through param until find one which is set and return that one
	 * @version 1, January 11, 2010
	 * @param mixed ...
	 * @return mixed
	 */
	function cycle ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Handle
		foreach ( $args as $arg ) {
			$result = $arg;
			if ( !empty($result) ) {
				break;
			}
		}
		
		# Done
		return $result;
	}
}

/*
if( function_comp('set_param', 1) )
{
	function set_param ( $name, $value, $expire = 2147483647 )
	{
		setcookie($name, to_string($value), $expire, '/');
		return true;
	}
}
*/

?>