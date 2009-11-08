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

if ( class_exists('ZipArchive') && function_compare('Zip__create_from_dir', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Add a folder to a zip archive.
	 *
	 * @version 1.0-final, April 21, 2008
	 *
	 * @param string $dir_path the directory to be included in the .zip archive
	 * @param object $zip_path the path to the .zip archive
	 * @param string $container_name optional should there be a container directory within the archive
	 *
	 * @return boolean true on success, false on failure
	 *
	 */
	function Zip__create_from_dir ( $dir_path, $zip_path, $container_name = '' ) {
		$Zip = new ZipArchive();
		
		if ( $Zip->open($zip_path, ZIPARCHIVE::CREATE) !== TRUE )
			return false;
		
		Zip__append_dir($dir_path, $Zip, $container_name);
		
		$Zip->close();
		
		return true;
	}
}

if ( class_exists('ZipArchive') && function_compare('Zip__append_dir', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Add a folder to the .zip archive.
	 *
	 * @version 1.0-final, April 21, 2008
	 * @author hostingfanatic dot com - {@link http://au3.php.net/manual/en/ref.zip.php#65998}
	 *
	 * @param string $dir the directory to be included in the .zip archive
	 * @param object &$Zip the current Zip object <code>$zip = new ZipArchive();</code>
	 * @param string $extdir used internally
	 *
	 * @return boolean true on success, false on failure
	 *
	 */
	function Zip__append_dir ( $dir, & $Zip, $extdir = '' ) { //
		if ( is_dir($dir) ) {
			$dh = opendir($dir);
			if ( $dh ) {
				while ( ($file = readdir($dh)) !== false ) {
					if ( $file !== '.' && $file !== '..' ) {
						if ( is_dir($dir . $file) ) {
							$Zip->addFile($dir . $file, $extdir . $file);
							Zip__append_dir($dir . $file . '/', $Zip, $extdir . $file . '/');
						} else {
							$Zip->addFile($dir . $file, $extdir . $file);
						}
					}
				}
				closedir($dh);
			}
		}
		return true;
	}
}

?>