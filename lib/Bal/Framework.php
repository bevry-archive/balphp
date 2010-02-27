<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008-2009 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage bal
 * @version 0.2.0-final, December 9, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008-2009, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */
class Bal_Framework {
	
	public static function import ( $libraries = array() ) {
		$balphp__sub_packages = $libraries;
		$file = 'balphp.php';
		if ( file_exists($file) ) {
			require($file);
		} elseif ( defined('BALPHP_PATH') && file_exists(BALPHP_PATH.DIRECTORY_SEPARATOR.$file) ) {
			require(BALPHP_PATH.DIRECTORY_SEPARATOR.$file);
		} else {
			throw new Zend_Exception ('Could not find balPHP: '.get_include_path());
		}
	}
	
}
