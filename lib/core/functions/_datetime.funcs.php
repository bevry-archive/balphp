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


if ( function_compare('ensure_timestamp', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Gets the days between two timestamps
	 * @version 1, January 28, 2010
	 * @param mixed	$start
	 * @return timestamp
	 */
	function ensure_timestamp ( $value ) {
		return is_numeric($value) ? $value : strtotime($value);
	}
}


if ( function_compare('days_between', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Gets the days between two timestamps
	 * @version 1, January 28, 2010
	 * @param timestamp	$start
	 * @param timestamp	$finish
	 * @param boolean	$invlusive
	 * @return integer
	 */
	function days_between ( $start, $finish, $inclusive = null ) {
		# Prepare
		$start = ensure_timestamp($start);
		$finish = ensure_timestamp($finish);
		
		# Check
		if ( $start > $finish ) {
			# Stat is longer, swap
			$tmp = $start;
			$start = $finish;
			$finish = $start;
		}
		
		# Calculate
		$offset = $finish-$start;
		$between = floor($offset/60/60/24);
		
		# Adjust
		if ( $inclusive === true ) {
			++$between;
		} elseif ( $inclusive === false ) {
			if ( $between > 0 ) {
				--$between;
			}
		}
		
		# Done
		return $between;
	}
}

if ( function_compare('doctrine_timestamp', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Create a doctrine timestamp
	 * @version 1, January 28, 2010
	 * @param mixed	$value
	 * @return timestamp
	 */
	function doctrine_timestamp ( $value = null ) {
		$value = empty($value) ? time() : ensure_timestamp($value);
		$result = date('Y-m-d H:i:s', $value);
		return $result;
	}
}
