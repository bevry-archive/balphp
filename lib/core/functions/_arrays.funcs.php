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


if ( function_compare('array_merge_recursive_keys', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Will merge the keys of arrays together. For a normal array we replace.
	 * @version 1, January 06, 2010
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 * @author Benjamin "balupton" Lupton <contact@balupton.com> {@link http://www.balupton.com/}
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 */
	function array_merge_recursive_keys ( ) {
		# Prepare
		$args = func_get_args();
		$merged = array_shift($args);
		# Handle
		foreach ( $args as $array ) {
			# Prepare
			if ( !is_array($array) ) $array = array($array);
			# Check if we have keys
			if ( is_numeric(implode('',array_keys($array))) ) {
				# We don't
				$merged = $array;
			}
			else {
				# We do, cycle through keys
				foreach ( $array as $key => $value ) {
					# Merge
					if ( is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]) ) {
						# Array is keyed array
						$merged[$key] = array_merge_recursive_keys($merged[$key], $value);
					}
					else {
						# Normal
						$merged[$key] = $value;
					}
				}
			}
		}
		# Done
		return $merged;
	}
	/*
	$a = array(
		'register' => array(
			'user' => array(
				'id' => 1,
				'title' => 'hello'
			)
		),
		'field' => array(
			'one','two','three'
		)
	);
	$b = array(
		'register' => array(
			'user' => array(
				'id' => 1,
				'avatar' => 'file'
			)
		),
		'field' => array(
			'four','five'
		)
	);
	$c = array_merge_recursive_keys($a, $b);
	$c = array (
		'register' => array (
			'user' => array (
				'id' => 1,
				'title' => 'hello',
				'avatar' => 'file',
			),
		),
		'field' => array (
			0 => 'four',
			1 => 'five',
		),
	)
	*/
}


if ( function_compare('array_hydrate', 1, true, __FILE__, __LINE__) ) {

	function array_hydrate ( &$array ) {
		if ( !is_array($array) ) $array = array();
		foreach ( $array as $key => $value ) {
			if ( is_array($value) ) {
				array_hydrate($array[$key]);
			} else {
				$array[$key] = real_value($value);
			}
		}
	}
}


if ( function_compare('array_prepare', 1, true, __FILE__, __LINE__) ) {

	function array_prepare ( &$array ) {
		array_clean($array);
		array_hydrate($array);
	}
	
}

	
if ( function_compare('array_first', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Return the first element of an array
	 * @version 1, December 24, 2009
	 * @param array $arr
	 * @return mixed
	 */
	function array_first ( $arr ) {
		$value = array_shift($arr);
		return $value;
	}
}

	
if ( function_compare('array_last', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Return the last element of an array
	 * @version 1, December 24, 2009
	 * @param array $arr
	 * @return mixed
	 */
	function array_last ( $arr ) {
		$value = array_pop($arr);
		return $value;
	}
}

	
if ( function_compare('ensure_keys', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Ensure keys to use to delve into an array or object
	 * @version 1, January 08, 2010
	 * @param mixed $keys
	 * @param mixed $holder
	 * @return array
	 */
	function ensure_keys ( &$keys, $holder = null ) {
		# Handle
		if ( !is_array($keys) ) {
			if ( is_string($keys) ) {
				if ( (is_array($holder) && array_key_exists($keys, $holder)) || (is_object($holder) && !empty($holder->$keys)) ) {
					$keys = array($keys);
				} else {
					$keys = explode('.', $keys);
				}
			} else {
				$keys = array($keys);
			}
		}
	
		# Done
		return $keys;
	}
}


if ( function_compare('nvp', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Return the array as a name value pair array
	 * @version 1, January 11, 2010
	 * @param array $arr
	 * @param string $name [optional]
	 * @param string $value [optional]
	 * @return array
	 */
	function nvp ( $arr, $name = 'id', $value = 'title' ) {
		# Prepare
		$result = array();
		if ( !is_array($arr) ) return array();
		
		# Cycle
		foreach ( $arr as $item ) {
			$result[$item[$name]] = $item[$value];
		}
		
		# Done
		return $result;
	}
}


if ( function_compare('array_values_to_keys', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Copy the array values to the keys
	 * @version 1, January 15, 2010
	 * @param array $arr
	 * @return array
	 */
	function array_values_to_keys ( $arr ) {
		# Prepare
		$result = array();
		
		# Cycle
		foreach ( $arr as $value ) {
			$result[$value] = $value;
		}
		
		# Done
		return $result;
	}
}


if ( function_compare('array_apply', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Delve into an array to apply a value from a set of keys
	 * @version 2, January 08, 2010
	 * @param array $arr
	 * @param array $keys
	 * @param mixed $value
	 * @param boolean $copy [optional]
	 * @return array
	 */
	/* Changelog:
	 * 2, January 08, 2010
	 * - Made it so instead of replace of every delve, only the end result will replace.
	 * 1, December 24, 2009
	 * - Init
	 */
	function array_apply ( &$arr, $keys, &$value, $copy = true ) {
		# Prepare
		ensure_keys($keys, $arr);
		
		# Handle
		$key = array_shift($keys);
		if ( empty($key) ) {
			if ( $copy ) {
				$arr = $value;
			} else {
				$arr =& $value;
			}
		} elseif ( is_array($arr) ) {
			if ( !array_key_exists($key, $arr) || !is_array($arr[$key]) )
				$arr[$key] = array();
			return array_apply($arr[$key], $keys, $value, $copy);
		} elseif ( is_object($arr) ) {
			if ( !isset($arr->$key) || !is_array($arr->$key) )
				$arr->$key = array();
			return array_apply($arr->$key, $keys, $value, $copy);
		}
		return true;
	}
}

if ( function_compare('delver', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Delve into an array or object reptively until all arguments are exhausted, return the first true value
	 * @version 1, January 12, 2010
	 * @param mixed $holder
	 * @param mixed ...
	 * @return mixed
	 */
	function delver ( $holder ) {
		# Prepare
		$result = null;
		$args = func_get_args(); array_shift($args);
		if ( sizeof($args) < 2 ) array_push($args, null);
	
		# Cycle through and delve each
		foreach ( $args as $arg ) {
			# Delve the result
			if ( is_string($arg) && !is_numeric($arg) ) {
				$result = delve($holder, $arg, null);
			} else {
				$result = $arg;
			}
			# Check
			if ( $result === null ) {
				# We have received a negative result, set the arg as we may be a static value, and continue
				$result = $arg;
			} else {
				# We have received a positive result
				break;
			}
		}
		
		# Done
		return $result;
	}
}


if ( function_compare('delver_array', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Delve into an array or object reptively until all arguments are exhausted, return the first true value
	 * @version 1, January 12, 2010
	 * @param mixed $holder
	 * @param array $array
	 * @return mixed
	 */
	function delver_array ( $holder, $array ) {
		# Prepare
		$result = null;
		if ( !is_array($array) ) $array = empty($array) ? array() : array($array);
		
		# Place holder as first
		array_unshift($array, $holder);
		
		# Delver
		$result = call_user_func_array('delver', $array);
		
		# Done
		return $result;
	}
}

if ( function_compare('delve', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Delve into an array or object to return the value of a set of keys
	 * @version 1, December 24, 2009
	 * @param mixed $holder
	 * @param mixed $keys
	 * @param mixed $default
	 * @return mixed
	 */
	function delve ( $holder, $keys, $default = null) {
		# Prepare
		$result = null;
		$end = false;
		
		# Prepare Keys
		ensure_keys($keys, $holder);
		
		# Handle
		$key = array_shift($keys);
		if ( $key === null ) {
			# Reched the end of our key array, so holder must be what we want
			$result = $holder;
			$end = true;
		} else {
			switch ( gettype($holder) ) {
				case 'array':
					if ( array_key_exists($key, $holder) ) {
						# We exist, so recurse
						$result = delve($holder[$key], $keys, $default);
					} else {
						$end = true;
					}
					break;
				
				case 'object':
					if (
						/* Already accessible via normal object means */
						isset($holder->$key)
						/* Is Doctrine Record */
						||	(	($holder instanceOf Doctrine_Record)
								&&	($holder->hasAccessor($key)
										||	($holder->hasRelation($key) && ($holder->refreshRelated($key) || isset($holder->$key)))
										||	$holder->getTable()->hasField($key)
									)
							)
						/* Is normal object */
						||	(	!($holder instanceOf Doctrine_Record)
								&&	method_exists($holder, 'get')
								&&	$holder->get($key)
							)
					) {
						# We exist, so recurse
						$result = delve($holder->$key, $keys, $default);
					} else {
						$end = true;
					}
					break;
				
				default:
					$end = true;
					break;
			}
		}
		
		# Check Default
		if ( $end && $result === null ) {
			$result = $default;
		}
		
		# Done
		return $result;
	}
}


if ( function_compare('array_delve', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Delve into an array to return the value of a set of keys
	 * @version 1, December 24, 2009
	 * @param array $arr
	 * @param mixed $keys
	 * @return array
	 */
	function array_delve ( $arr, $keys) {
		# Prepare
		ensure_keys($keys, $arr);
		
		# Handle
		$key = array_shift($keys);
		if ( empty($key) ) {
			return $arr;
		} elseif ( array_key_exists($key, $arr) ) {
			return array_delve($arr[$key], $keys);
		} else {
			return null;
		}
	}
}


if ( function_compare('array_walk_keys', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Walkthrough the keys of the array
	 * @version 1, December 23, 2009
	 * @param array $attr
	 * @param callback $callback
	 * @return array
	 */
	function array_walk_keys ( &$arr, $callback ) {
		# Prpare
		$args = func_get_args();
		unsets($args[0], $args[1]);
		# Handle
		$keys = array_keys($arr);
		$values = array_values($arr);
		$keys = call_user_func_array('array_walk', array($keys, $callback, $args));
		$result = array_combine($keys, $values);
		# Done
		return $result;
	}
}

if ( function_compare('array_join', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Push an array into an array
	 * @version 1, December 23, 2009
	 * @param array $arr
	 * @param array $args
	 * @return array
	 */
	function array_join ( $arr, $args ) {
		call_user_func_array('array_push', $arr, $args);
		return $arr;
	}
}


if ( function_compare('array_walk_nokey', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Walkthrough the keys of the array
	 * @version 1, December 23, 2009
	 * @param array $attr
	 * @param callback $callback
	 * @return array
	 */
	function array_walk_nokey ( &$arr, $callback ) {
		# Prpare
		$args = func_get_args();
		unsets($args[0], $args[1]);
		# Handle
		foreach ( $arr as &$value ) {
			$_args = array_join(array($value), $_args);
			$_value = call_user_func_array($callback, $_args);
			if ( $_value !== $value ) {
				$value = $_value;
			}
		}
		# Done
		return $arr;
	}
}

if ( function_compare('array_from_attributes', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Return a NameValuePair from a bunch of text attributes
	 * @version 1, December 10, 2009
	 * @param string $attrs
	 * @return array
	 */
	function array_from_attributes ( $attrs = '' ) {
		# Prepare
		if ( is_array($attrs) ) return $attrs;
		$array = array();
	
		# Search
		$search = '/(?<name>\w+)\="(?<value>.*?[^\\\\])"/';
		$matches = array();
		preg_match_all($search, $attrs, $matches);
		
		# Handle
		foreach ( $matches['name'] as $match => $name ) {
			$value = $matches['value'][$match];
			$array[$name] = $value;
		}
		
		# Done
		return $array;
	}
}

if ( function_compare('array_unset', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Unset the keys from the array
	 * @version 1, August 09, 2009
	 * @param array $array
	 * @param mixed ... what to remove
	 * @return mixed
	 */
	function array_unset ( &$array ) {
		# Prepare
		if ( !is_array($array) ) $array = $array === null ? array() : array($array);
		$args = func_get_args(); array_shift($args);
		if ( sizeof($args) === 1 && is_array($args[0]) ) $args = $args[0];
		# Apply
		foreach ( $args as $var ) {
			unset($array[$var]);
		}
		# Done
		return $array;
	}
}

if ( function_compare('array_keys_unset', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Unset the keys from the array
	 * @version 1, February 01, 2010
	 * @param array $array
	 * @param array $unset
	 * @return mixed
	 */
	function array_keys_unset ( &$array, array $unset ) {
		return array_unset($array, $unset);
	}
}

if ( function_compare('array_keys_keep', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Keep the keys in the array
	 * @version 1, November 9, 2009
	 * @param array $array
	 * @param mixed ... what to keep
	 * @return array
	 */
	function array_keys_keep ( &$array ) {
		# Prepare
		if ( !is_array($array) ) $array = $array === null ? array() : array($array);
		$args = func_get_args(); array_shift($args);
		if ( sizeof($args) === 1 && is_array($args[0]) ) $args = $args[0];
		# Apply
		$keys = array_flip($args);
		# Done
		$array = array_intersect_key($array, $keys);
		return $array;
	}
}


if ( function_compare('array_keys_keep_ensure', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Keep the keys in the array and ensure their value
	 * @version 1, November 9, 2009
	 * @param array $array
	 * @param array $keys
	 * @param mixed $default [optional]
	 * @return array
	 */
	function array_keys_keep_ensure ( &$array, $keys, $default = null ) {
		array_keys_keep($array, $keys);
		array_keys_ensure($array, $keys);
		return $array;
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
	function array_clean ( &$array, $to = null ) {
		# Prepare
		if ( !is_array($array) )
			return $array;
		if ( is_null($to) )
			$to = 'remove';
		
		# Cycle
		foreach ( $array as $key => &$value ) {
			if ( $value === '' || $value === NULL ) {
				// Empty value, only key
				if ( $to === 'remove' ) {
					unset($array[$key]); // unset
				} else {
					$array[$key] = $to;
				}
			} elseif ( is_array($value) ) {
				array_clean($array[$key]);
			} else {
				$value = trim($value);
			}
		}
		// $array = array_merge($array); // reset keys
		
		# Return
		return $array;
	}
}


if ( function_compare('implode_recursive', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Implode an array recursively
	 * @version 1, July 22, 2008
	 * @param array &$array
	 * @param mixed $to [optional]
	 * @return mixed
	 */
	function implode_recursive ( $glue, $array ) {
		# Prepare
		if ( !is_array($array) ) return $array;
		# Handle
		foreach ( $array as &$value ) {
			if ( is_array($value) ) {
				$value = implode_recursive($glue, $value);
			}
		}
		# Implode
		$result = implode(', ', $array);
		# Done
		return $result;
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
			if ( $keep ) $node = array_keys_keep($node, $keep);
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

if ( function_compare('is_simple_array', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Checks if the array is a simple array
	 * @version 1, January 30, 2010
	 * @param array $array
	 * @return mixed
	 */
	function is_simple_array ( array $array ) {
		return is_numeric(implode('',array_keys($array)));
	}
}


if ( function_compare('check_flow', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Checks the flow of a state conversion
	 * @version 1, February 05, 2010
	 * @param array $array
	 * @return mixed
	 */
	function check_flow ( $current, $who, $to, array $state ) {
		# Handle
		$result = ($current === $to) || ( array_key_exists($current, $state) && array_key_exists($who, $state[$current]) && in_array($to, $state[$current][$who]) );
	
		# Return
		return $result;
	}
}
