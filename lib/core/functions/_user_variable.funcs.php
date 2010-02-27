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

if ( function_compare('get_user_variable', 4, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 4
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function user_variable ( $name, $default = false, $values = false, $cookie = true, $recieved = true, $update = false, $stripslashes = true ) { //Created by balupton
		

		$value = $default;
		
		// NOTE: when input is '~!@#$%^&*()_+|`-=\{}[]:";'<>?,./�', output becomes '~!@#$%^&*()_ |`-={}[]:";'<>?,./©', strange...
		

		if ( $cookie ) { //
			if ( isset($_COOKIE[$name]) ) {
				$value = from_string(trim($stripslashes ? stripslashes($_COOKIE[$name]) : $_COOKIE[$name]));
			}
		}
		
		if ( $recieved ) { //
			if ( isset($_GET[$name]) ) {
				$value = from_string(trim($stripslashes ? stripslashes($_GET[$name]) : $_GET[$name]));
			} elseif ( isset($_POST[$name]) ) {
				$value = from_string(trim($stripslashes ? stripslashes($_POST[$name]) : $_POST[$name]));
			} elseif ( isset($_FILES[$name]) ) {
				$value = $_FILES[$name];
			}
		}
		
		if ( $values != false ) {
			$exists = false;
			$s = sizeof($values);
			for($i = 0; $i < $s; $i++) {
				if ( $values[$i] === $value ) {
					$exists = true;
					break;
				}
			}
			
			if ( !$exists && $s != 0 )
				$value = $default;
		}
		
		if ( $update ) { // We ant to update the cookie
			@setcookie($name, to_string($value), 2147483647, '/');
		}
		
		return $value;
	}
}
