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

require_once(dirname(__FILE__).'/_Association.class.php');

class Step extends Association
{
	// ===========================
	
	function Step ( $row = NULL, $perform_action = true )
	{	
		// Blah
		$this->name = 'Step';
		
		// Finish Construction
		return $this->Association($row, $perform_action);
	}
	
	// ===========================
	
	/* private */
	function _load_from_request_action ( )
	{	// Loads the action from a request
		
		$this->status = true;
		
		$action = false;
		
		if ( ($move_up = get_param('move_up_'.$this->name, false)) || ($move_down = get_param('move_down_'.$this->name, false)) )
		{
			$action = $move_up ? 'move_up' : 'move_down';
			
			$this->action = $action;
			
			return true;
		}
		
		return parent::_load_from_request_action();
	}
	
	// ===========================
	
	function _move ( $current_position, $new_position, $add_log_on_success = true )
	{
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Check that we are ready to update
		if ( empty($this->id) )
		{
			$this->status = false;
			return false;
		}
		
		# Set action
		$this->action = 'move';
		
		# Check positions
		if ( $new_position < 0 )
			$new_position = 0;
		
		# See if we need to perform a switch
		if ( false && $new_position !== $current_position )
		{
			# Load the object that was there
			$row = $this->DB->search(
				// TABLE
				$this->table,
				// COLUMNS
				'id',
				// WHERE
				array(
					array('primary_product_id',	$this->get('primary_product_id')),
					array('order_position',		$new_position)
				),
				// LIMIT
				1
			);
			if ( !empty($row) )
			{
				$Step = new Step($row);
				if ( !$Step->status )
					return $Step->status;
				
				$Step->set('order_position', $current_position);
				if ( !($update = $Step->update(false)) )
					return $update;
			}
		}
		
		# Update this object
		$this->set('order_position', $new_position);
		if ( !($update = $this->update($add_log_on_success)) )
			return $update;
		
		# Finish
		return true;
	}
	
	function move_up ( $add_log_on_success = true )
	{
		if ( array_key_exists('order_position', $this->row) || $this->load(false) )
		{
			$current_position = $this->get('order_position');
			$new_position = $current_position-1;
			return $this->_move($current_position, $new_position, $add_log_on_success);
		}
		
		return $this->status;
	}
	
	function move_down ( $add_log_on_success = true )
	{
		if ( array_key_exists('order_position', $this->row) || $this->load(false) )
		{
			$current_position = $this->get('order_position');
			$new_position = $current_position+1;
			return $this->_move($current_position, $new_position, $add_log_on_success);
		}
		
		return $this->status;
	}
	
	// ===========================
	
}

?>