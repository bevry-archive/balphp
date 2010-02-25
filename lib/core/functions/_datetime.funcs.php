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
	function ensure_timestamp ( $value = null ) {
		$result = null;
		
		if ( $value === null ) $result = time();
		elseif ( is_numeric($value) ) $result = $value;
		elseif ( is_string($value) ) $result = strtotime($value);
		else throw new Exception('Unkown timestamp type.');
		
		return $result;
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


if ( function_compare('weeks_between', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Gets the weeks between two timestamps
	 * @version 1, February 05, 2010
	 * @param timestamp	$start
	 * @param timestamp	$finish
	 * @param boolean	$invlusive
	 * @return integer
	 */
	function weeks_between ( $start, $finish, $inclusive = null ) {
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
		$between = floor($offset/60/60/24/7);
		
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


if ( function_compare('month_start', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get the start of the month
	 * @version 1, January 28, 2010
	 * @param integer	$month
	 * @param integer	$year
	 * @return timestamp
	 */
	function month_start ( $month, $year ) {
		$result = mktime(0,0,0,$month,1,$year);
		return $result;
	}
}


if ( function_compare('month_finish', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get the finish of the month
	 * @version 1, January 28, 2010
	 * @param integer	$month
	 * @param integer	$year
	 * @return timestamp
	 */
	function month_finish ( $month, $year ) {
		$result = mktime(23,59,59,$month+1,0,$year);
		return $result;
	}
}


if ( function_compare('week_start', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get the start of the week
	 * @version 1, February 05, 2010
	 * @param timestamp	$timestamp
	 * @param string	$day [optional]
	 * @return timestamp
	 */
	function week_start ( $timestamp, $day = 'Monday' ) {
		$result = ensure_timestamp($timestamp);
		if ( date('l',$result) !== $day )
			$result = strtotime('last '.$day, $result);
		return $result;
	}
}


if ( function_compare('week_finish', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get the finish of the week
	 * @version 1, February 05, 2010
	 * @param timestamp	$timestamp
	 * @param string	$day [optional]
	 * @return timestamp
	 */
	function week_finish ( $timestamp, $day = 'Sunday' ) {
		$result = ensure_timestamp($timestamp);
		if ( date('l',$result) !== $day )
			$result = strtotime('next '.$day, $result);
		return $result;
	}
}


if ( function_compare('day_start', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get the start of the day
	 * @version 1, February 05, 2010
	 * @param timestamp	$timestamp
	 * @param string	$day [optional]
	 * @return timestamp
	 */
	function day_start ( $timestamp ) {
		$timestamp = ensure_timestamp($timestamp);
		$year = date('Y', $timestamp);
		$month = date('n', $timestamp);
		$day = date('j', $timestamp);
		$result = mktime(0,0,0,$month,$day,$year);
		return $result;
	}
}


if ( function_compare('day_finish', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Get the finish of the day
	 * @version 1, February 05, 2010
	 * @param timestamp	$timestamp
	 * @param string	$day [optional]
	 * @return timestamp
	 */
	function day_finish ( $timestamp ) {
		$timestamp = ensure_timestamp($timestamp);
		$year = date('Y', $timestamp);
		$month = date('n', $timestamp);
		$day = date('j', $timestamp);
		$result = mktime(23,59,59,$month,$day,$year);
		return $result;
	}
}
