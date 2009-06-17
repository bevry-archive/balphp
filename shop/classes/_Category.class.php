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
 * @subpackage shop
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once(dirname(__FILE__).'/_ShopObject.class.php');
require_once(dirname(__FILE__).'/../../core/functions/_params.funcs.php');

class Category extends ShopObject
{
	// ===========================
	
	function Category ( $row = NULL, $perform_action = true )
	{	
		// Finish Construction
		return $this->ShopObject('categories', $row, $perform_action);
	}
	
	// ===========================
	
	function display_select ( $Category_type, $Category_id = NULL, $i = 1, $n = 2 )
	{
		if ( is_null($Category_type) )
			$Category_type = get_param('Category_type', NULL, false);
		$Original_Category_type = $Category_type;
		if ( $Category_type == 'purchases' || $Category_type == 'downloads' )
		{
			$Category_type = 'products';
		}
		if ( is_null($Category_id) )
			$category_id = $Category_id = get_param('Category_id', NULL, false);
		require(dirname(__FILE__).'/.display/'.$this->name.'/_display_select.php');
	}

}

?>