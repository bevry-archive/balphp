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
	 * Do something
	 *
	 * @version 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function get_param ( $name, $default = NULL, $convert = true, $stripslashes = NULL ) {
		if ( $stripslashes === NULL )
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

if ( function_compare('array_hydrate', 1, true, __FILE__, __LINE__) ) {

	function array_hydrate ( &$array ) {
		foreach ( $array as $key => $value ) {
			if ( is_array($value) ) {
				array_hydrate($array[$key]);
			} else {
				$array[$key] = real_value($value);
			}
		}
	}
}

if ( function_compare('hydrate_params', 1, true, __FILE__, __LINE__) ) {

	function hydrate_params ( ) {
		array_hydrate($_REQUEST);
		array_hydrate($_POST);
		array_hydrate($_GET);
	}
}

if ( function_compare('until', 1, true, __FILE__, __LINE__) ) {

	function until ( ) {
		$args = func_get_args();
		foreach ( $args as $arg ) {
			if ( $arg )
				return true;
		}
	}
}

if ( function_compare('array_set', 1, true, __FILE__, __LINE__) ) {

	function array_set ( &$array ) {
		$args = func_get_args();
		unset($args[0]);
		foreach ( $args as $arg ) {
			if ( !isset($array[$arg]) )
				$array[$arg] = null;
		}
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