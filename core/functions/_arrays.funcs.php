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
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once(dirname(__FILE__).'/_general.funcs.php');

if ( function_compare('array_clean', 1, true, __FILE__, __LINE__) )
{	/**
	 * Clean empty values from an array
	 * 
	 * @version 1, July 22, 2008
	 * 
	 * @param array &$array
	 * @param array $default
	 * 
	 * @return mixed
	 */
	function array_clean ( &$array )
	{
		foreach ( $array as $key => $value )
		{
			if ( $value === '' || $value === NULL )
			{	// Empty value, only key
				unset($array[$key]);		// unset
			}
		}
		$array = array_merge($array);		// reset keys
	}
}

if ( function_compare('array_cycle', 1, true, __FILE__, __LINE__) )
{	/**
	 * Cycle through an array
	 * 
	 * @version 1, July 22, 2008
	 * 
	 * @param array &$array
	 * @param array $default
	 * 
	 * @return mixed
	 */
	function array_cycle ( &$array, $default )
	{
		if ( empty($array) )
			$array = $default;
		else
			array_push($array, array_shift($array));
		$value = $array[0];
		return $value;
	}
}

if ( function_compare('is_first', 1, true, __FILE__, __LINE__) )
{	/**
	 * Checks if the value is the first item of the array
	 * 
	 * @version 1, July 22, 2008
	 * 
	 * @param array $array
	 * @param array $value
	 * 
	 * @return mixed
	 */
	function is_first ( $array, $value )
	{
		return array_shift($array) === $value;
	}
}

if ( function_compare('is_last', 1, true, __FILE__, __LINE__) )
{	/**
	 * Checks if the value is the last item of the array
	 * 
	 * @version 1, July 22, 2008
	 * 
	 * @param array $array
	 * @param array $value
	 * 
	 * @return mixed
	 */
	function is_last ( $array, $value )
	{
		return array_pop($array) === $value;
	}
}

if ( !function_exists('array_combine') && function_compare('array_combine', 1, true, __FILE__, __LINE__) )
{	/**
	 * Create the array_combine function if it does not already exist
	 * 
	 * @author none
	 * @copyright none
	 * @license none
	 * @version 1
	 */
	function array_combine($keys, $values)
	{
		$out = array();
		foreach($keys as $key) $out[$key] = array_shift($values);
		return $out;
	}
}
	
if( function_compare('array_remove_empty', 1, true, __FILE__, __LINE__) )
{	/**
	 * Remove all empty items from an array
	 * 
	 * @version 1
	 * 
	 * @param array &$array
	 * @param boolean $deep should we go deep?
	 * @return array
	 */
	function array_remove_empty ( & $array, $deep = true )
	{
		$s = sizeof($array);
		for ( $i = 0; $i < $s; $i++ )
		{
			$c = & $array[$i];
			if ( empty($c) )
			{
				unset($c);
				unset($array[$i]);
				$ii--;
			} elseif ( $deep && gettype($c) == 'array' )
			{
				array_remove_empty($c);
			}
		}
	}
}

if( function_compare('array_first_needle', 1, true, __FILE__, __LINE__) )
{	/**
	 * Find the first common needle of $array_one within $array_two
	 * 
	 * @version 1, April 21, 2008
	 * 
	 * @param array $array_one	 
	 * @param array $array_two
	 * 
	 * @return mixed|NULL needle, or nothing
	 */
	function array_first_needle ( $array_one, $array_two )
	{
		$result = NULL;
		$array_one_size = sizeof($array_one);
		for ( $i = 0; $i < $array_one_size && is_null($result); $i++ )
		{
			$needle = $array_one[$i];
			if ( in_array($needle,$array_two) )
				$result = $needle;
		}
		return $result;
	}
}

?>