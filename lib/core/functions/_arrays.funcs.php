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

if ( function_compare('array_unset', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Unset the keys from the array
	 * @version 1, August 09, 2009
	 * @param array $array
	 * @param array $unsets
	 * @return mixed
	 */
	function array_unset ( &$array, $unsets ) {
		# Prepare
		if ( !is_array($unsets) ) $unsets = array($unsets);
		# Apply
		foreach ( $unsets as $unset )
			unset($array[$unset]);
		# Done
		return $array;
	}
}

if ( function_compare('array_keep', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Unset the keys from the array
	 * @version 1, November 9, 2009
	 * @param array $array
	 * @param array $keeps
	 * @return mixed
	 */
	function array_keep ( &$array, $keeps ) {
		# Prepare
		if ( !is_array($array) ) $array = array($array);
		if ( !is_array($keeps) ) $keeps = array($keeps);
		# Apply
		$keys = array_flip($keeps);
		# Done
		return array_intersect_key($array, $keys);
	}
}


if ( function_compare('array_key_ensure', 1.1, true, __FILE__, __LINE__) ) {
	/**
	 * Ensure the key exists in the array
	 * @version 1.1, November 9, 2009
	 * @param array $array
	 * @param mixed $key
	 * @param mixed $value [optional]
	 * @return mixed
	 */
	function array_key_ensure ( &$array, $key, $value = null ) {
		# Prepare
		if ( is_array($key) ) return array_keys_ensure($array, $key, $value);
		if ( !is_array($array) ) $array = array($array);
		# Apply
		if ( !array_key_exists($key, $array) ) $array[$key] = $value;
		# Done
		return $array;
	}
}

if ( function_compare('array_keys_ensure', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Ensure the keys exists in the array
	 * @version 1, November 9, 2009
	 * @param array $array
	 * @param array $key
	 * @param mixed $value [optional]
	 * @return mixed
	 */
	function array_keys_ensure ( &$array, $keys, $value = null ) {
		# Prepare
		if ( !is_array($array) ) $array = array($array);
		# Apply
		foreach ( $keys as $key ) {
			if ( is_array($key) ) {
				array_keys_ensure($array, $key, $value);
			} else {
				array_key_ensure($array, $key, $value);
			}
		}
		# Done
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
		foreach ( $array as $key => &$value ) {
			$value = trim($value);
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


if ( function_compare('array_tree_flat', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Turns an array into an flat array tree
	 * @version 1, Novemer 9, 2009
	 * @param array $map
	 * @param string $idKey
	 * @param string $parentKey
	 * @param string $levelKey
	 * @param string $positionKey
	 */
	function array_tree_flat ( $array, $idKey = 'id', $parentKey = 'parent', $levelKey = 'level', $positionKey = 'position' ) {
		$map = array();
		foreach ( $array as $i => &$node ) {
			// Ensure
			array_keys_ensure($node, array($idKey, $parentKey, $levelKey, $positionKey));
			// Fetch
			$id = $node[$idKey];
			$parent = $node[$parentKey];
			$position = $node[$positionKey];
			// Handle
			$node[$levelKey] = 0;
			if ( empty($parent) ) {
				// Root
				if ( !isset($map[0]) ) $map[0] = array();
				$map[0][$position] = $node;
			} else {
				// Child
				if ( !isset($map[$parent]) ) $map[$parent] = array();
				$map[$parent][$position] = $node;
			}
		}
		// Build again
		$new = array();
		array_tree_flat_helper($map,$idKey,$parentKey,$levelKey,$positionKey,$new,0,0);
		return $new;
	}
}

if ( function_compare('array_tree_round', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Turns an array into an round array tree
	 * @version 1, Novemer 9, 2009
	 * @param array $map
	 * @param string $idKey
	 * @param string $parentKey
	 * @param string $levelKey
	 * @param string $positionKey
	 * @param string $childrenKey
	 */
	function array_tree_round ( $array, $idKey = 'id', $parentKey = 'parent', $levelKey = 'level', $positionKey = 'position', $childrenKey = 'children', array $keep = array() ) {
		# Generate Map
		$map = array();
		foreach ( $array as $i => $node ) {
			# Ensure
			array_keys_ensure($node, array($idKey, $parentKey, $levelKey, $positionKey, $childrenKey));
			# Fetch
			$id = $node[$idKey];
			# Prepare
			$node[$levelKey] = 0;
			$node[$childrenKey] = array();
			# Apply
			$map[$id] = $node;
		}
		
		# Build Chidren
		$tree = array();
		foreach ( $map as $id => &$node ) {
			# Fetch
			$id = $node[$idKey];
			$parent = $node[$parentKey];
			$position = $node[$positionKey];
			# Trim
			if ( $keep ) $node = array_keep($node, $keep);
			# Apply
			if ( $parent ) {
				$map[$parent][$childrenKey][$position] = &$node;
			} else {
				$tree[$id] = &$node;
			}
		}
		
		# Done
		return $tree;
	}
}

if ( function_compare('array_tree_flat_helper', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Array tree helper
	 * @version 1, Novemer 9, 2009
	 * @param array $map
	 * @param string $idKey
	 * @param string $parentKey
	 * @param string $levelKey
	 * @param string $positionKey
	 * @param array $new
	 * @param integer $parent
	 * @param integer $level
	 */
	function array_tree_flat_helper ( &$map, $idKey, $parentKey, $levelKey, $positionKey, &$new, $parent, $level ) {
		if ( empty($map[$parent]) ) return;
		ksort($map[$parent]);
		foreach ( $map[$parent] as $node ) {
			// Fetch
			$id = $node[$idKey];
			$position = $node[$positionKey];
			// Handle
			$node[$levelKey] = $level;
			$new[] = $node;
			array_tree_flat_helper($map,$idKey,$parentKey,$levelKey,$positionKey,$new,$id,$level+1);
		}
		return true;
	}
}

if ( function_compare('array_cycle', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Cycle through an array
	 * @version 1, July 22, 2008
	 * @param array &$array
	 * @param array $default
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