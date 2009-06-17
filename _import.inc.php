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
 * @version 0.2.1-final, April 25, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */
	
// ------------------------
// Load resources

$dir = dirname(__FILE__).'/';
require_once($dir.'core/functions/_scan_dir.funcs.php');

// ------------------------
// Load the sub packages

// If no sub packages specified only load core
global $balphp__sub_packages, $balphp__sub_packages__loaded;
if ( empty($balphp__sub_packages) ) $balphp__sub_packages = array();
if ( empty($balphp__sub_packages__loaded) ) $balphp__sub_packages__loaded = array();

// Add core sub package
if ( !in_array('core', $balphp__sub_packages) )
	$balphp__sub_packages[] = 'core';

// Unique it
$balphp__sub_packages = array_unique($balphp__sub_packages);

// Only load unloaded
$balphp__sub_packages = array_diff($balphp__sub_packages, $balphp__sub_packages__loaded);
$balphp__sub_packages__loaded = array_merge($balphp__sub_packages, $balphp__sub_packages__loaded);
// loaded is set here, to stop overflow

// Load sub packages
foreach ( $balphp__sub_packages as $sub_package )
{	// Load the sub package
	scan_dir( $dir.$sub_package.'/', 'inc_php', 'inc_php' );
}

/*
 * Changelog
 * 
 * version 0.2.1-final, April 25, 2008
 * - stopped overlow
 * 
 * version 0.2.0-final, April 25, 2008
 * - added support for sub packages
 * 
 * version 0.1.0-final, April 21, 2008
 * - initial
 * 
 */

?>