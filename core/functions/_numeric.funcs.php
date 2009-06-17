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

if ( !function_exists('is_odd') && function_compare('is_odd', 1, true, __FILE__, __LINE__) )
{	/**
	 * Checks if the number is odd
	 * 
	 * @author http://au.php.net/manual/en/function.is-numeric.php#59691
	 * @copyright none
	 * @license none
	 * @version 1
	 */
	function is_odd($num){
		return (is_numeric($num)&($num&1));
	}
}

if ( !function_exists('is_even') && function_compare('is_even', 1, true, __FILE__, __LINE__) )
{	/**
	 * Checks if the number is even
	 * 
	 * @author http://au.php.net/manual/en/function.is-numeric.php#59691
	 * @copyright none
	 * @license none
	 * @version 1
	 */
	function is_even($num){
		return (is_numeric($num)&(!($num&1)));
	}
}

if( function_compare( 'in_range', 3, true, __FILE__, __LINE__ ) )
{	/**
	 * Is the value within range
	 * 
	 * @version 3, April 24, 2008
	 *
	 * @param	string	$min			min value
	 * @param	string	$value			value
	 * @param	string	$max			maximum value
	 * @param	string	$comparison		('<'|'exclusive'|'exc'|false)|('<='|'inclusive'|'inc'|true)
	 * 
	 * @return    bool
	 */
	function in_range ( $min = NULL, $value, $max = NULL, $comparison )
	{
		// if ( gettype($value) === 'array' ) return in_range__v2($min, $value);
		$operator;
		switch ( $comparison )
		{	// Support for retards
			case false:
			case 'exclusive':
			case 'exc':
			case '<':
				$operator = '<';
				break;
			default:
				$operator = '<=';
				break;
		}
		return ($min === NULL || version_compare($min, $value, $operator)) && ($max === NULL || version_compare($value, $max, $operator));
	}
	
	/*
	function in_range__v2 ( $value, $range )
	{	
		$operator =
			( isset($range['type']) && begins_with($range['type'], 'inc') )
			?	'<='
			:	'<'
			;
		
		$pass_min = true;
		if ( !empty($range['min']) )
		{
			$pass_min = version_compare($range['min'],$value,$operator);
		}
		
		$pass_max = true;
		if ( !empty($range['max']) )
		{
			$pass_max = version_compare($value,$range['max'],$operator);
		}
		
		return $pass_min && $pass_max;
	}
	*/
}

?>