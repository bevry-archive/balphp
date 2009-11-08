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

if ( function_compare('unset_multi', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Unset the keys from the array
	 * @version 1, August 09, 2009
	 * @param array $array
	 * @param array $unsets
	 * @return mixed
	 */
	function unset_multi ( &$array, $unsets ) {
		if ( !is_array($unsets) )
			$unsets = array($unsets);
		foreach ( $unsets as $unset )
			unset($array[$unset]);
		return $array;
	}
}

if ( function_compare('in_array_multi', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Checks if multiple values are inside the array
	 * @version 1.1, November 11, 2009
	 * @since 1, August 08, 2009
	 * @param array $needles
	 * @param array $haystack
	 * @param boolean $all [optional]
	 * @return mixed
	 */
	function in_array_multi ( $needles, $haystack, $all = false ) {
		$result = false;
		$count = 0;
		if ( !$all ) {
			foreach ( $needles as $needle ) {
				if ( in_array($needle, $haystack) ) {
					$result = true;
					break;
				}
			}
		} else {
			foreach ( $needles as $needle ) {
				if ( in_array($needle, $haystack) ) {
					++$count;
				}
			}
			$result = sizeof($needles) === $count;
		}
		return $result;
	}
}

if ( function_compare('array_clean', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Clean empty values from an array
	 * @version 1, July 22, 2008
	 * @param array &$array
	 * @param mixed $to [optional]
	 * @return mixed
	 */
	function array_clean ( &$array, $to = 'remove' ) {
		if ( !is_array($array) )
			return $array;
		foreach ( $array as $key => $value ) {
			if ( $value === '' || $value === NULL ) {
				// Empty value, only key
				if ( $to === 'remove' ) {
					unset($array[$key]); // unset
				} else {
					$array[$key] = $to;
				}
			} elseif ( is_array($value) ) {
				array_clean($array[$key]);
			}
		}
		$array = array_merge($array); // reset keys
		return $array;
	}
}

if ( function_compare('array_cycle', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Cycle through an array
	 *
	 * @version 1, July 22, 2008
	 *
	 * @param array &$array
	 * @param array $default
	 *
	 * @return mixed
	 */
	function array_cycle ( &$array, $default ) {
		if ( empty($array) )
			$array = $default;
		else
			array_push($array, array_shift($array));
		$value = $array[0];
		return $value;
	}
}

if ( function_compare('is_first', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Checks if the value is the first item of the array
	 *
	 * @version 1, July 22, 2008
	 *
	 * @param array $array
	 * @param array $value
	 *
	 * @return mixed
	 */
	function is_first ( $array, $value ) {
		return array_shift($array) === $value;
	}
}

if ( function_compare('is_last', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Checks if the value is the last item of the array
	 *
	 * @version 1, July 22, 2008
	 *
	 * @param array $array
	 * @param array $value
	 *
	 * @return mixed
	 */
	function is_last ( $array, $value ) {
		return array_pop($array) === $value;
	}
}

if ( !function_exists('array_combine') && function_compare('array_combine', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Create the array_combine function if it does not already exist
	 *
	 * @author none
	 * @copyright none
	 * @license none
	 * @version 1
	 */
	function array_combine ( $keys, $values ) {
		$out = array();
		foreach ( $keys as $key )
			$out[$key] = array_shift($values);
		return $out;
	}
}

?>