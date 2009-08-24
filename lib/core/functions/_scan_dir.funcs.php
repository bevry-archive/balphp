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

// Require the resources
require_once(dirname(__FILE__).'/_general.funcs.php');

if( function_compare( 'scan_dir', 6, true, __FILE__, __LINE__ ) )
{	/**
	 * Scan the directory for files and folders, and return an array with the contents.
	 * 
	 * <pre>
	 * Predefined Patterns:
	 *  'php': only php files
	 *  'inc_php': only php files preceeded by a underscore '_file.php'
	 *  'image': only image files
	 *  'file'|'files': only files, no directories
	 *  'directory'|'directories': only directories, no files
	 *  
	 * Predefined Actions:
	 *  'no_recurse': only get contents in the immediate directory (no recursion)
	 *  'inc_php': require_once, to be used in conjuction with the 'inc_php' pattern.
	 *  
	 * Return Formats {@link $return_format}:
	 *  default: an array containing all the absolute file locations (and dirs if a dir pattern is specified)
	 *  'seperate': same as defualt except split using $array['files'] and $array['dirs']
	 *  'tree': an array tree containing all the file relative paths parented by their parent directory
	 *  'absolute_tree': an array tree containing all the absolute paths parented by their parent directory
	 * </pre>
	 * 
	 * @version 6.2-final, April 21, 2008
	 * 
	 * @param string $dir the directory to scan
	 * @param string|regex|array $pattern optional a predefined regular expression to use (string), or your own (regex),
	 *  or an array containing individual patterns: 'file', 'dir', 'both'.
	 * @param string|array $action optional a string to be eval'd,
	 *  or an array containing individual actions: 'file', 'dir', 'both'.
	 * @param string|'' $prepend optional used for recursion, what is prepended onto the locations
	 * @param string $return_format optional
	 * 
	 * @return array the contents of the directoy returned in the specified format
	 * 
	 */
	function scan_dir ($dir, $pattern = NULL, $action = NULL, $prepend = '', $return_format = NULL)
	{	/* If we want to include the [ files || dirs ] in the output we make
		 *		[ $file_pattern = true or $file_pattern = string || $dir_pattern = true or $dir_pattern = string ]
		 * If we do not want to include a [ dir || file ] we do 
		 *		[ $file_pattern  = false || $dir_pattern = false ]
		 * If we do not want to recurse in the action set
		 *		$continue = true
		 */
		
		// Create Valid Directory
		$dir = realpath($dir);
		if ( !$dir )
			return array();
		$dir = str_replace('\\', '/', $dir);
		if ( is_dir($dir) && substr($dir, strlen($dir)-1) !== '/' )
			$dir .= '/';
		
		// Get on with the script
		$files = array(); // Define our array to return
		if ( $return_format === 'seperate' )
		{	// Add extra
			$files['dirs'] = $files['files'] = array();
		}
		
		// Set defaults
		$file_pattern = $dir_pattern = $both_pattern = true;
		$both_action = $file_action = $dir_action = NULL;
		
		// Handle pattern
		if ( !empty($pattern) )
		// We have a pattern
		switch ( $pattern )
		{	// Replace the pattern if it is predefined
			case 'php':
				$file_pattern = '/^(.+)\.php$/';
				$dir_pattern = NULL;
				break;
			case 'inc_php':
				$file_pattern = '/^(_.+)\.php$/';
				$dir_pattern = NULL;
				break;
			case 'image':
				$file_pattern = '/^(.+)\.(jpg|jpeg|gif|png|tiff|bmp|xbmp)$/i';
				$dir_pattern = NULL;
				break;
			case 'file':
			case 'files':
				$dir_pattern = NULL;
				break;
			case 'directory':
			case 'directories':
				$file_pattern = NULL;
				break;
			default:
				if ( is_array($pattern) )
				{
					$file_pattern_exists = array_key_exists('file', $pattern);
					if ( $file_pattern_exists )	$dir_pattern = NULL;
					
					$dir_pattern_exists = array_key_exists('dir',  $pattern);
					if ( $dir_pattern_exists )	$file_pattern = NULL;
					
					$both_pattern_exists = array_key_exists('both', $pattern);
					
					if ( $file_pattern_exists )	$file_pattern = $pattern['file'];
					if ( $dir_pattern_exists  )	$dir_pattern  = $pattern['dir'];
					if ( $both_pattern_exists )	$both_pattern = $pattern['both'];
				}
				else
				{
					$file_pattern = $pattern;
					$dir_pattern = NULL;
				}
				break;
		}
		$pattern = array('both'=>$both_pattern,'file'=>$file_pattern,'dir'=>$dir_pattern);
		
		// Handle action
		if ( !empty($action) )
		{	// We have a pattern
			switch ( $action )
			{	// Replace the pattern if it is predefined
				case 'no_recurse':
					$dir_action = 'scan_dir__dir_action__no_recurse';
					break;
				case 'inc_php':
					$file_action = 'scan_dir__file_action__inc_php';
					break;
				default:
					if ( is_array($action) )
					{
						if ( isset($action['file']) )	$file_action = $action['file'];
						if ( isset($action['dir']) )	$dir_action  = $action['dir'];
						if ( isset($action['both']) )	$both_action = $action['both'];
					}
					else
					{
						$file_action = $action;
					}
					break;
			}
		}
		//
		$action = array('both'=>$both_action,'file'=>$file_action,'dir'=>$dir_action);
		
		// Get down to business
		$both_matches = $file_matches = $dir_matches = array();
		if ( $dh = opendir($dir) )
		{	// Open the directory
			// Go through the directory and include the files that match the given regular expression
			while (($file = readdir($dh)) !== false)
			{	// Cycle through files
				$skip = false;
				$path = $dir.$file;
				if ( !empty($file) && substr($file,0,1) != '.' )
				{	// We have a file or directory
				
					// Check
					if ( $both_pattern === true || $both_pattern === NULL || ($both_pattern !== false && preg_match($both_pattern, $file, $both_matches)) )
					{	// passed check
					} else
					{	// failed check
						continue; // continue to next file
					}
					
					// Perform custom action
					if ( $both_action ) {
						$result = $both_action($path, $file, $dir, $skip);
						extract($result); // Custom action
					}
					if ( $skip ) continue;
					
					// Continue with specifics
					if ( is_file($path) )
					{	// We have a file
						
						// Check
						if ( $file_pattern === true || $file_pattern === NULL || ($file_pattern !== false && preg_match($file_pattern, $file, $file_matches)) )
						{	// passed check
						} else
						{	// failed check
							continue; // continue to next file
						}
						
						// Perform custom action
						if ( $file_action ) {
							$result = $file_action($path, $file, $dir, $skip);
							extract($result); // Custom action
						}
						if ( $skip ) continue;
						
						// Return
						if ( $file_pattern !== NULL )
						// We want to return, so it is either TRUE or STRING as if it was FALSE we would of continued
						switch ( $return_format )
						{	// Work with the return
						
							case 'seperate':
								$files['files'][] = $prepend.$file; // Append the file location to the array to be returned
								break;
							
							case 'absolute_tree':
								$filename = $file;
								$end = strrpos($filename, '.');
								if ( $end !== -1 )
									$filename = substr($filename, 0, $end);
								$files[$filename] = $prepend.$file; // Append the file location to the array to be returned
								break;
						
							case 'tree':
								$files[] = $file; // Append the file name to the array to be returned
								/*
								$filename = $file;
								$end = strrpos($filename, '.');
								if ( $end !== -1 )
									$filename = substr($filename, 0, $end);
								$files[$filename] = $prepend.$file; // Append the file location to the array to be returned
								*/
								break;
							
							default:
								$files[] = $prepend.$file; // Append the file location to the array to be returned
								break;
						}
					}
					elseif ( is_dir($path) )
					{	// We have a dir
						
						// Check
						if ( $dir_pattern === true || $dir_pattern === NULL || ($dir_pattern !== false && preg_match($dir_pattern, $file, $dir_matches)) )
						{	// passed check
						} else
						{	// failed check
							continue; // continue to next file
						}
						
						// Perform custom action
						if ( $dir_action ) {
							$result = $dir_action($path, $file, $dir, $skip);
							extract($result); // Custom action
						}
						if ( $skip ) continue;
						
						// Return
						switch ( $return_format )
						{	// Work with the return		
										
							case 'seperate':
								if ( $dir_pattern !== NULL )
								{	// We want to return, so it is either TRUE or STRING as if it was FALSE we would of continued
									$files['dirs'][] = $prepend.$file; // Append the file location to the array to be returned
								}
								$scan_dir = scan_dir($path, $pattern, $action, $prepend.$file.'/', $return_format);
								$files['files'] = array_merge($files['files'], $scan_dir['files']);
								$files['dirs'] = array_merge($files['dirs'], $scan_dir['dirs']);
								unset($scan_dir);
								break;
							
							case 'absolute_tree':
							case 'tree':
								$files[$file] =
									scan_dir($path, $pattern, $action, $prepend.$file.'/', $return_format);
								break;
							
							default:
								if ( $dir_pattern !== NULL )
								{	// We want to return, so it is either TRUE or STRING as if it was FALSE we would of continued
									$files[] = $prepend.$file; // Append the file location to the array to be returned
								}
								$files = array_merge(
									$files,
									scan_dir($path, $pattern, $action, $prepend.$file.'/', $return_format)
								);
								break;
						}
					} // end file or dir compare
					
				} // end is file or dor
				
			} // end while
			
			closedir($dh); // Close the directory
			
		} // end open dir
		
		return $files;
		
	} // END: scan_dir

	function scan_dir__dir_action__no_recurse ($path, $file, $dir, $skip) {
		$return = array();
		if ( dirname($path) !== $dir ) $return['skip'] = true;
		return $return;
	}
	
	function scan_dir__file_action__inc_php ($path, $file, $dir, $skip){
		require_once($path);
		return array();
	}
					
} // END: function_exists

?>