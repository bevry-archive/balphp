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

if ( function_compare('gen_url', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Does something
	 * @version 1.1, April 27, 2008
	 * @return string
	 * @todo figure out what the hell this does
	 */
	function gen_url ( ) {
		$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
		$protocol = strleft(strtolower($_SERVER['SERVER_PROTOCOL']), '/') . $s;
		$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':' . $_SERVER['SERVER_PORT']);
		$last_part = (!empty($_SERVER['REQUEST_URI']) ? (strpos($_SERVER['REQUEST_URI'], '?') !== false ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'] . '?') : $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']);
		if ( substr($last_part, 0, 1) !== '/' )
			$last_part = '/' . $last_part;
		return $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $last_part;
	}
	/*
	 * Changelog
	 *
	 * version 1.1, April 27, 2008
	 * - Sometimes url was incorrect due to $last_part not starting with a '/'
	 *
	 * version 1
	 * - Initial
	 *
	 */
}

if ( function_compare('selfURL', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Alias for gen_url
	 * @see gen_url
	 */
	function selfURL ( ) {
		return gen_url();
	}
}

if ( function_compare('url_remove_params', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Does something
	 * @version 1
	 * @return string
	 * @todo figure out what the hell this does
	 */
	function url_remove_params ( $params, $url = NULL, $recurse = 0 ) {
		++$recurse;
		if ( !empty($_SERVER['REQUEST_URI']) )
			$_SERVER['REQUEST_URI'] = str_replace($params, '', $_SERVER['REQUEST_URI']);
		if ( !empty($_SERVER['QUERY_STRING']) )
			$_SERVER['QUERY_STRING'] = str_replace($params, '', $_SERVER['QUERY_STRING']);
		if ( $url !== NULL )
			$url = str_replace($params, '', $url);
		
		if ( $recurse === 1 )
			$url = url_remove_params(html_entity_decode($params), $url, $recurse);
		
		return $url;
	}
}

if ( function_compare('regen_url', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Does something
	 * @version 1
	 * @return string
	 * @todo figure out what the hell this does
	 */
	function regen_url ( $params = NULL, $amp = '&' ) {
		$url = gen_url();
		if ( !empty($params) ) {
			$cut = strpos($url, '?') + 1;
			$url_params = substr($url, $cut);
			$url = substr($url, 0, $cut);
			
			$url_params = explode_querystring($url_params, '&');
			if ( gettype($params) === 'string' )
				$params = explode_querystring($params, $amp);
				
			// echo '<pre>';
			// var_dump($params);
			$new_params = array_merge($url_params, $params);
			// var_dump($new_params);
			/*foreach ( $new_params as $key => $value )
			{	// Remove empty vars
				if ( $value === '' )
					unset($new_params[$key]);
			}*/
			$new_params = implode_querystring($new_params, $amp);
			// var_dump($new_params);
			// echo '</pre>';
			

			$url .= $new_params;
		}
		return $url;
	}
}


if ( function_compare('is_connected', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Checks internet connection
	 * @author http://www.weberdev.com/get_example-4025.html
	 * @copyright Unkown
	 * @version 1, February 24, 2010
	 * @return string
	 * @todo figure out what the hell this does
	 */
	function is_connected ( ) {
		$result = false;
	    $connected = @fsockopen('www.google.com', 80); 
	    if ( $connected ){ 
	        $result = true; 
	        fclose($connected); 
	    }
	    return $result; 
	}
}
