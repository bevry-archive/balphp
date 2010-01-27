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

class Licence extends ShopObject
{
	
	var $customer_type = NULL;
	
	// ===========================
	
	function Licence ( $row = NULL, $perform_action = true )
	{	
		// Finish Construction
		return $this->ShopObject('licences', $row, $perform_action);
	}
	
	// ===========================
	
	/* public */
	function get ( $column_nickname, $display = false, $default = '', $get_title = true )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		switch ( $column_nickname )
		{
			
			case 'customer_type':
				if ( $this->customer_type !== NULL )
					return $this->customer_type;
				
				// Do stuff
				if ( $this->get('customer_type_id') )
				{	// School
					$customer_type = 'school';
				} else
				if ( $this->get('student_teacher') )
				{	// Student Teacher
					$customer_type = 'student_teacher';
				} else
				{	// Individual
					$customer_type = 'individual';
				}
				
				// Finish
				$value = $this->customer_type = $customer_type;
				break;
				
			case 'base_price':
				$value = parent::get($column_nickname, $display, $default, $get_title);
				
				// We want to return
				if ( $display != 'htmlbody' )
				{	// We just want the price
					break;
				}
				
				// We want to display
				$value = '$'.$value;
				
				break;
				
			default:
				$value = parent::get($column_nickname, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	// ===========================
}
