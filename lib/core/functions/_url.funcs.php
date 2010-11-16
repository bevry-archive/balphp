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
require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_strings.funcs.php');

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
	 * Checks internet connection. Used to detect whether or not we can send emails.
	 * @version 1.1, November 11, 2010
	 * @since 1, February 24, 2010
	 * @param string $url [optional]
	 * @return string
	 */
	function is_connected ( $url = 'www.google.com' ) {
		$result = false;
	    $connected = @fsockopen($url, 80, $errno, $errstr, 5); 
	    if ( $connected ){ 
	        $result = true; 
	        fclose($connected); 
	    }
		elseif ( $errstr ) {
			echo "<!--[$errstr ($errno)]-->\n";
		}
	    return $result;
	}
}


if ( function_compare('get_browser_info', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get Browser Information
	 * @version 1, July 08, 2010
	 * @return array [browser, engine, version]
	 */
	function get_browser_info ( ) {
		// IE8: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.2; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)
		// Firefox: Mozilla/5.0 (Windows; U; Windows NT 6.1; it; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)
		// Safari: Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16
		// Chrome: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.2 (KHTML, like Gecko) Chrome/6.0.451.0 Safari/534.2
		// abreviation asd
	
		# Check if we need ot define
		if ( !array_key_exists('BROWSER', $GLOBALS) ) {
			$GLOBALS['BROWSER'] = array(
				'ie' => false,
				'firefox' => false,
				'opera' => false,
				'chrome' => false,
				'safari' => false,
				'other' => false,
				'version' => false,
				'mobile' => false,
				'desktop' => true,
				'environment' => 'desktop'
			);
			global $BROWSER;
	
			$BROWSER['browser'] = $h_u_a = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'MSIE';
			
			if ( strstr($h_u_a,'Firefox') ) {
				$BROWSER['browser'] 		= 'firefox';
				$BROWSER['engine'] 			= 'moz';
				$BROWSER['firefox'] 		= true;
				$BROWSER['version'] 		= (strstr($h_u_a,'Firefox/3') ? 3 : (strstr($h_u_a,'Firefox/2') ? 2 : 1));
			}
			elseif ( strstr($h_u_a,'Opera') ) {
				$BROWSER['browser'] 		= 'opera';
				$BROWSER['engine'] 			= 'o';
				$BROWSER['opera'] 			= true;
				$BROWSER['version']			= (strstr($h_u_a,'Opera/10') ? 10 : (strstr($h_u_a,'Opera/9') ? 9 : 8));
			}
			elseif ( strstr($h_u_a,'Chrome') ) {
				$BROWSER['browser'] 		= 'chrome';
				$BROWSER['engine'] 			= 'webkit';
				$BROWSER['chrome'] 			= true;
				$BROWSER['version']			= (strstr($h_u_a,'Chrome/6') ? 6 : (strstr($h_u_a,'Opera/5') ? 5 : 4));
			}
			elseif( strstr($h_u_a,'Safari') ) {
				$BROWSER['browser'] 		= 'safari';
				$BROWSER['engine'] 			= 'webkit';
				$BROWSER['safari'] 			= true;
				$BROWSER['version']			= 99;
			}
			elseif( strstr($h_u_a,'MSIE') ) {
				$BROWSER['browser'] 		= 'ie';
				$BROWSER['engine'] 			= 'trident';
				$BROWSER['ie'] 				= true;
				$BROWSER['version']			= (strstr($h_u_a,'MSIE 8') ? 8 : (strstr($h_u_a,'MSIE 7') ? 7 : 6));
			}
			else {
				$BROWSER['browser'] 		= 'other';
				$BROWSER['engine'] 			= 'other';
				$BROWSER['other'] 			= true;
				$BROWSER['version'] 		= 99;
			}
			
			$BROWSER['mobile'] = strstr($h_u_a, 'Mobile');
			$BROWSER['desktop'] = !$BROWSER['mobile'];
			$BROWSER['environment'] = $BROWSER['mobile'] ? 'mobile' : 'desktop';
		}
		
		# Return $GLOBALS['BROWSER']
		return $GLOBALS['BROWSER'];
	}
}
