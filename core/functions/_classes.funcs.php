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

if( function_compare('get_ancestors', 2, true, __FILE__, __LINE__) )
{	/**
	 * Return an array of the class's ancestors
	 * 
	 * @version 2, April 21, 2008
	 * 
	 * @param string|object		$class		The tested object or class name	
	 * @param boolean			$display	Should the result be displayed?	
	 * @param string			$order		Should the order be 'asc' or 'desc'
	 * 
	 * @return array
	 */
	function get_class_heirachy ( $class, $display = false, $order = 'asc')
	{
		# Convert object to class name if need be
		if ( gettype($class) !== 'string' )
			$class = get_class($class);
		# Get array of class names
		$classes = array();
		for ($classes[] = $class; $class = get_parent_class($class); $classes[] = $class);
		# Reverse (if need be), so that they are parent, parent, child
		if ( $order === 'asc' )
			$classes = array_reverse($classes);
		# Do display
		if ( $display )
			return implode('->', $classes);
		# Return
		return $classes;
	}
}

?>