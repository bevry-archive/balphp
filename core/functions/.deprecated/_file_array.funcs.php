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
require_once(dirname(__FILE__).'/_string_array.funcs.php');

if( function_compare( 'file_to_array', 2, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a file to an array
	 * 
	 * Deprecated over the alternative of
	 * <code>$contents = serialize($data); file_put_contents($file_location, $contents);</code>
	 * 
	 * @version 2
	 * @deprecated
	 * 
	 * @author none
	 * @copyright none
	 * @license none
	 */
	function file_to_array( $file, $array = false, $debug = false )
	{
		// Check if the file exists
		if( ! file_exists($file) )
		{	// Something screwed up
			if ( $debug )
				echo('<!-- ERROR: file_to_array: File does not exist: '.$file.' -->');
			return $array;
		}
		
		// Get the contents of the file
		if(
			($fp = @fopen($file,'r'))
			&&	($contents = @fread($fp,@filesize($file)))
			&&	@fclose($fp)
		)
		{	// Turn the file into an array
			return string_to_array($contents);
		} else
		{	// Something screwed up
			if ( $debug )
				echo('<!-- ERROR: file_to_array: Reading the file failed: '.$file.' -->');
			return $array;
		}	
	}
}

if( function_compare( 'array_to_file', 2, true, __FILE__, __LINE__ ) )
{	/**
	 * Convert a array to a file
	 * 
	 * Deprecated over the alternative of
	 * <code>$contents = file_get_contents($file_location); $data = unserialize($contents);</code>
	 * 
	 * @version 2
	 * @deprecated
	 * 
	 * @author none
	 * @copyright none
	 * @license none
	 */
	function array_to_file( $file, $array, $debug = false )
	{
		$text = array_to_string($array);
		
		if ( ($fp = @fopen($file,'w')) && @fwrite($fp, $text) && @fclose($fp) )
			return true;
		else {
			if ( $debug )
				echo('<!-- ERROR: array_to_file: Writing to the file failed: '.$file.' -->');
			return false;
		}
			
	}
}
	
?>