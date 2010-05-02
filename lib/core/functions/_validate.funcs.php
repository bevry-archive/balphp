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


if ( function_compare('reallyempty', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Checks if the string is totally completely empty
	 * @version 1, March 24, 2010
	 * @param	string	$haystack
	 * @return    bool
	 */
	function reallyempty ( $haystack ) {
		$result = false;
		if ( empty($haystack) && !$haystack ) {
			$result = true;
		} elseif ( is_string($haystack) ) {
			$haystack = preg_replace('/\s/', '', $haystack);
			$result = strlen($haystack) === 0 ? true : false;
		}
		elseif ( is_traversable($haystack) ) {
			array_clean($haystack);
			$result = count($haystack) === 0 ? true : false;
		}
		else {
			$result = empty($haystack) && !$haystack; // !$haystack in here due to very wierd problem with magic properties
		}
		return $result;
	}
}


if ( !function_exists('is_odd') && function_compare('is_odd', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Checks if the number is odd
	 *
	 * @author http://au.php.net/manual/en/function.is-numeric.php#59691
	 * @copyright none
	 * @license none
	 * @version 1
	 */
	function is_odd ( $num ) {
		return (is_numeric($num) & ($num & 1));
	}
}

if ( !function_exists('is_even') && function_compare('is_even', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Checks if the number is even
	 *
	 * @author http://au.php.net/manual/en/function.is-numeric.php#59691
	 * @copyright none
	 * @license none
	 * @version 1
	 */
	function is_even ( $num ) {
		return (is_numeric($num) & (!($num & 1)));
	}
}

if ( function_compare('in_range', 3, true, __FILE__, __LINE__) ) {

	/**
	 * Is the value within the range
	 *
	 * @version 3, April 24, 2008
	 *
	 * @param	string	$min			min value
	 * @param	string	$value			value
	 * @param	string	$max			maximum value
	 * @param	string	$comparison		('<'|'exclusive'|'exc'|false)|('<='|'inclusive'|'inc'|true)
	 * @param	bool	$only_numeric	only allow numeric?
	 *
	 * @return    bool
	 */
	function in_range ( $min = null, $value, $max = null, $comparison = null, $only_numeric = false ) {
		# Prepare
		$operator = null;
		
		# Check
		if ( $only_numeric && !is_numeric($value) ) {
			return false;
		}
		
		# Determine Operator
		switch ( $comparison ) {
			case false :
			case 'exclusive' :
			case 'exc' :
			case '<' :
				$operator = '<';
				break;
			default :
				$operator = '<=';
				break;
		}
		
		# Determine
		$result = ($min === null || version_compare($min, $value, $operator)) && ($max === null || version_compare($value, $max, $operator));
		
		# Return result
		return $result;
	}
	
}

if ( function_compare('has_true', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Checks all arguments for one which is loosely true
	 */
	function has_true ( ) {
		$args = func_get_args();
		foreach ( $args as $arg ) {
			if ( $arg )
				return true;
		}
	}
}

if ( function_compare('until_integer', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Cycle through param until find one which is set and return that one
	 * @version 1, May 01, 2010
	 * @param mixed ...
	 * @return mixed
	 */
	function until_integer ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Handle
		foreach ( $args as $arg ) {
			$result = real_value($arg);
			if ( is_integer($result) ) {
				break;
			}
		}
		
		# Done
		return $result;
	}
}

if ( function_compare('until_numeric', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Cycle through param until find one which is set and return that one
	 * @version 1, May 01, 2010
	 * @param mixed ...
	 * @return mixed
	 */
	function until_numeric ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Handle
		foreach ( $args as $arg ) {
			$result = real_value($arg);
			if ( is_numeric($result) ) {
				break;
			}
		}
		
		# Done
		return $result;
	}
}

if ( function_compare('until_notnullorfalse', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Cycle through param until find one which is set and return that one
	 * @version 1, May 01, 2010
	 * @param mixed ...
	 * @return mixed
	 */
	function until_notnullorfalse ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Handle
		foreach ( $args as $arg ) {
			$result = real_value($arg);
			if ( $result !== null && $result !== false ) {
				break;
			}
		}
		
		# Done
		return $result;
	}
}

if ( function_compare('until_notnull', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Cycle through param until find one which is set and return that one
	 * @version 1, May 01, 2010
	 * @param mixed ...
	 * @return mixed
	 */
	function until_notnull ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Handle
		foreach ( $args as $arg ) {
			$result = real_value($arg);
			if ( $result !== null ) {
				break;
			}
		}
		
		# Done
		return $result;
	}
}

if ( function_compare('until_notempty', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Cycle through param until find one which is set and return that one
	 * @version 1, May 01, 2010
	 * @param mixed ...
	 * @return mixed
	 */
	function until_notempty ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Handle
		foreach ( $args as $arg ) {
			$result = real_value($arg);
			if ( !empty($result) && $result ) {
				break;
			}
		}
		
		# Done
		return $result;
	}
}


if ( function_compare('validate_checks', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Perform a series of checks on a bunch of data
	 * @version 1, May 02, 2010
	 * @see array_walker
	 * @param array $checks
	 * @param bool $throw [optional]
	 * @return array
	 */
	function validate_checks ( $checks, $throw = true ) {
		# Prepare
		$result = true;
		
		# Cycle
		foreach ( $checks as $code => $result ) {
			# Check
			if ( !$result ) {
				# Throw?
				if ( $throw ) {
					throw new Exception('The checks on ['.$code.'] failed');
				}
				
				# Failed
				$result = false;
				break;
			}
		}
		
		# Return result
		return $result;
	}
}


if ( function_compare('is_not', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Negates the call to $func with $options
	 * @version 1, May 02, 2010
	 * @param string $func
	 * @param array $checks
	 * @return bool
	 */
	function is_not ( $func, array $options = array() ) {
		# Perform
		$result = call_user_func_array($func, $options);
		
		# Return negation of result
		return !$result;
	}
}
