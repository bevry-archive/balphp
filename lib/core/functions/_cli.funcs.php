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
require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_arrays.funcs.php');
require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_strings.funcs.php');

if ( function_compare('readstdin', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Reads some input from stdin
	 * @version 1, February 02, 2010
	 * @param string $text
	 * @param array $params [optional]
	 * @param integer $max_tries [optional]
	 * @return mixed
	 */
	function readstdin ( $text, array $params = null, $max_tries = 1, $_try = 0 ) {
		# Write
		echo $text;
		if ( !empty($params) ) {
			echo ' ['.implode(',',$params).']';
		}
		echo "\n";
		
		# Read
		$read = trim(fgets(STDIN));
		
		# Check
		if ( !empty($params) && $max_tries && !in_array($read,$params) ) {
			if ( $_try < $max_tries ) {
				echo 'Invalid input. Please try again.'."\n";
				$read = readstdin($text,$params,$max_tries,$_try+1);
			} else {
				throw new Zend_Exception('Invalid input');
			}
		}
		
		# Done
		return $read;
	}
}


if ( function_compare('systems', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Perform a series of system commands
	 * @version 1, February 02, 2010
	 * @param string $text
	 * @param array $params [optional]
	 * @param integer $max_tries [optional]
	 * @return mixed
	 */
	function systems ( array $commands ) {
		$system = implode(';',$commands);
		return system($system);
	}
}
