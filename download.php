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

if ( empty($_GET['passkey']) )
	die('not yet able');

# ---------------------------------
# Config

error_reporting(E_ALL);

# ---------------------------------
# Import requirements

$dir = dirname(__FILE__).'/';
require_once($dir.'functions/_files.funcs.php');
require_once($dir.'functions/_zip.funcs.php');

# ---------------------------------
# Perform the script

# Prepare variables
$dir_path = dirname(__FILE__);
$dir_path = realpath($dir_path).'/';

$parent_dir_path = $dir_path.'/../';
$parent_dir_path = realpath($parent_dir_path).'/';

$zip_name = substr($dir_path, strrpos($dir_path, '\\')+1);
$zip_name = trim($zip_name, '/');
$zip_path = $dir_path.$zip_name.'.zip';

# Remove old file
if ( is_file($zip_path) )
	unlink($zip_path);

# How to make the .zip
if ( class_exists('ZipArchive') )
{	// Use Zip Library
	if ( !Zip__create_from_dir($dir_path, $zip_path, $zip_name.'/') )
		exit("failed to make zip file");
}
else
{	// Use perl
	chdir($dir_path);
	`zip -R $zip_name`;
}

# Redirect to file
//become_file_download($zip_path, 'archive/zip');
//die;

header('Location: '.$zip_name.'.zip');

?>