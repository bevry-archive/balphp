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

if ( function_compare('create_password', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Create a random user password
	 *
	 * @version 1.1, April 24, 2008
	 *
	 * @param int				$length		the length of the password
	 * @param boolean|string	$upper		true to use default, empty to use nothing, string to use custom
	 * @param boolean|string	$lower		true to use default, empty to use nothing, string to use custom
	 * @param boolean|string	$number		true to use default, empty to use nothing, string to use custom
	 * @param boolean|string	$symbols	true to use default, empty to use nothing, string to use custom
	 * @param boolean|string	$custom		true to use default, empty to use nothing, string to use custom
	 *
	 * @return string
	 *
	 */
	function create_password ( $length = 8, $upper = true, $lower = true, $number = true, $symbols = false, $custom = false ) { // Prepare
		if ( $upper === true )
			$upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if ( $lower === true )
			$lower = 'abcdefghjkmnpqrstuvwxyz';
		if ( $number === true )
			$number = '123456789';
		if ( $symbols === true )
			$symbols = '~!@#$%^&*()_+-={}[]:;,.?';
			// Append
		$seed = $upper . $lower . $number . $symbols . $custom;
		$seed_length = strlen($seed);
		// Generate
		$password = '';
		for($i = 0, $n = $length, $z = $seed_length - 1; $i < $n; ++$i) { // Append random char to password for length
			$password .= $seed[rand(0, $z)];
		}
		// Return
		return ($password);
	}
}

?>