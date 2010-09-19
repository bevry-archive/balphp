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

// Require the resources
require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_general.funcs.php');

if ( function_compare('scan_dir', 7, true, __FILE__, __LINE__) ) {

	/**
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
	function scan_dir ( $dir, array $options = array() ) {
		/* If we want to include the [ files || dirs ] in the output we make
		 *		[ $pattern_files = true or $pattern_files = string || $pattern_dirs = true or $pattern_dirs = string ]
		 * If we do not want to include a [ dir || file ] we do
		 *		[ $pattern_files  = false || $pattern_dirs = false ]
		 * If we do not want to recurse in the action set
		 *		$continue = true
		 */
		
		# Prepare
		$result = array();
		
		# Create Valid Directory
		$dir = realpath($dir);
		if ( !$dir ) {
			return array();
		}
		$dir = str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $dir);
		
		# Extract Options
		$pattern = $pattern_files = $pattern_dirs =
			$action = $action_files = $action_dirs =
			false;
		$return_files = $return_dirs = true;
		$skip_files = $skip_dirs = false;
		$skip_hidden = true;
		$return_format = 'flat';
		$recurse = true;
		extract($options);
		
		# Return Format
		if ( in_array($return_format, array('separate','seperate')) ) {
			$result['dirs'] = $result['files'] = array();
		}
		
		# Pattern
		if ( !empty($pattern) ) {
			# Predefined Pattern?
			switch ( $pattern ) {
				case 'php':
					$pattern_files = '/\\'.DIRECTORY_SEPARATOR.'.+\\.php$/';
					$return_dirs = false;
					break;
				case 'inc_php':
					$pattern_files = '/\\'.DIRECTORY_SEPARATOR.'_.+\\.php$/';
					$return_dirs = false;
					break;
				case 'image':
					$pattern_files = '/\\'.DIRECTORY_SEPARATOR.'.+?\\.(jpe?g|gif|png|tiff|x?bmp)$/i';
					$return_dirs = false;
					break;
				case 'file':
				case 'files':
					$return_dirs = false;
					break;
				case 'directory':
				case 'directories':
					$skip_files = true;
					$return_files = false;
					break;
				default:
					$pattern_files = $pattern_dirs = $pattern;
					break;
			}
		}
		
		# Action
		if ( !empty($action) ) {
			# Predefined Action?
			switch ( $action ) {
				case 'inc_php':
					$action_files = 'scan_dir__action_files__inc_php';
					$return_dirs = false;
					break;
				default:
					$action_files = $action_dirs = $action;
					break;
			}
		}
		
		# Cycle
		$dh = opendir($dir);
		if ( $dh ) {
			while ( ($filename = readdir($dh)) !== false ) {
				
				# Prepare
				$skip = false;
				$path = $dir . DIRECTORY_SEPARATOR . $filename;
		
				# Check
				if ( empty($filename) || ($skip_hidden && substr($filename, 0, 1) === '.') ) {
					continue; // skip
				}
		
				# Handle
				if ( is_file($path) ) {
		
					# Check Pattern
					if ( $skip_files || ($pattern_files && !preg_match($pattern_files, $path)) ) {
						continue; // skip
					}
			
					# Trigger Action
					if ( $action_files ) {
						$result = $action_files($path, $filename, $dir);
						extract($result);
						if ( $skip ) continue; // action set skip
					}
					
					# Check
					if ( !$return_files ) {
						continue; // skip
					}
			
					# Return
					switch ( $return_format ) {
						case 'separate':
						case 'seperate':
							$result['files'][$path] = $filename;
							break;
						
						case 'tree':
						case 'flat':
							$result[$path] = $filename;
							break;
						
						default:
							throw new Exception('scan_dir: unkown $return_format:['.$return_format.']');
							break;
					}
				
				}//is_file
				elseif ( is_dir($path) ) {
		
					# Check Pattern
					if ( $skip_dirs || ($pattern_dirs && !preg_match($pattern_dirs, $path)) ) {
						continue; // skip
					}
			
					# Trigger Action
					if ( $action_dirs ) {
						$result = $action_dirs($path, $filename, $dir);
						extract($result);
						if ( $skip ) continue; // action set skip
					}
			
					# Return
					switch ( $return_format ) {
				
						case 'separate':
						case 'seperate':
							if ( $return_dirs ) 
								$result['dirs'][] = $path;
							if ( $recurse ) {
								$scan_dir = scan_dir($path, $options);
								$result['files'] = array_merge($result['files'], $scan_dir['files']);
								$result['dirs'] = array_merge($result['dirs'], $scan_dir['dirs']);
							}
							break;
						
						case 'tree':
							if ( $return_dirs ) {
								$result[$path] = $filename;
							}
							if ( $recurse ) {
								$scan_dir = scan_dir($path, $options);
								$result[$path] = $scan_dir;
							}
							break;
						
						case 'flat':
							if ( $return_dirs ) {
								$result[$path] = $filename;
							}
							if ( $recurse ) {
								$scan_dir = scan_dir($path, $options);
								$result = array_merge($result,$scan_dir);
							}
							break;
							
						default:
							throw new Exception('scan_dir: unkown $return_format:['.$return_format.']');
							break;
						
					}
			
				
				}//is_dir
			
			}//while
		
			# Close
			closedir($dh);
		}//opendir
		
		# Return result
		return $result;
	
	}//scan_dir
	
	function scan_dir__action_files__inc_php ( $path, $file, $dir ) {
		require_once $path;
		return array();
	}

}//function_exists
