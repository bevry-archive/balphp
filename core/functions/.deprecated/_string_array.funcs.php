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
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once(dirname(__FILE__).'/_general.funcs.php');
require_once(dirname(__FILE__).'/_strings.funcs.php');


if( function_compare( 'array_to_string', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a array to a string
	 * 
	 * Deprecated over the alternative of
	 * <code>
	 * $contents = serialize($data);
	 * // or
	 * $contents = json_encode($data);
	 * </code>
	 * 
	 * @version 1
	 * @deprecated
	 * 
	 * @author Kevin Law
	 * @copyright none
	 * @license none
	 */
	function array_to_string($array)
	{
		$line = "";
		foreach($array AS $key => $value){
			if( is_array($value) )
				$value = "(". array_to_string($value) . ")";
			else
				$value = urlencode(to_string($value));
			$line = $line . "," . urlencode($key) . ":" . $value . "";		   
		}
		$line = substr($line, 1);
		return $line;
	}
}

if( function_compare( 'string_to_array', 1, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a array to a string
	 * 
	 * Deprecated over the alternative of
	 * <code>
	 * $data = unserialize($contents);
	 * // or
	 * $data = json_decode($contents);
	 * </code>
	 * 
	 * @version 1
	 * @deprecated
	 * 
	 * @author Kevin Law
	 * @copyright none
	 * @license none
	 */
	function string_to_array($line)
	{
		$q_pos = strpos($line, ":");
		$name = urldecode(from_string(substr($line,0,$q_pos)));
		$line = trim(substr($line,$q_pos+1));
		$open_backet_pos = strpos($line, "(");
		
		if($open_backet_pos===false || $open_backet_pos>0) {
			$comma_pos = strpos($line, ",");
			if($comma_pos===false) {
				$result[$name] = urldecode(from_string($line));
				$line = "";
			}else{
				$result[$name] = urldecode(from_string(substr($line,0,$comma_pos)));
				$result = array_merge($result, string_to_array(substr($line,$comma_pos+1)));
				$line = "";
			}
		} elseif ($open_backet_pos==0) {
			$line = substr($line,1);
			$num_backet = 1;
			$line_char_array = str_split($line);
			for($index = 0; count($line_char_array); $index++){
				if($line_char_array[$index] == '(')
					$num_backet++;
				elseif ($line_char_array[$index] == ')')
					$num_backet--;
				if($num_backet == 0)
					break;
			}
			$sub_line = substr($line,0,$index);
			$result[$name] = string_to_array($sub_line);
			$line = substr($line,$index+2);
		}
		
		if(strlen($line)!=0)
			$result = array_merge($result, string_to_array($line));
		
		return $result;
	}
}

?>