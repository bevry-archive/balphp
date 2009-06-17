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

class Discount extends ShopObject
{
	
	// ===========================
	
	function Discount ( $row = NULL, $perform_action = true )
	{	
		// Finish Construction
		return $this->ShopObject('discounts', $row, $perform_action);
	}
	
	// ===========================
	
	/* public */
	function create ( $add_log_on_success = true )
	{	
		$this->status = true;
		
		# Set action
		$this->action = 'create';
		
		# Check that we are valid for display
		$validate = $this->validate();
		if ( is_null($validate) )
		{	// If a error (not a warning) occured then lets stop
			return NULL;
		}
		
		return parent::create($add_log_on_success);
	}
	
	/* public */
	function update ( $add_log_on_success = true )
	{	
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Set action
		$this->action = 'update';
		
		# Check that we are valid for display
		$validate = $this->validate();
		if ( is_null($validate) )
		{	// If a error (not a warning) occured then lets stop
			return NULL;
		}
		
		return parent::update( $add_log_on_success );
	}
	
	function validate ( )
	{
		$display_warning = '';
		
		if ( $this->get('customer_id') && $this->get('customer_type_id') )
		{	// The user cannot have a discount ofr both a customer and a customer type
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					'You cannot have the '.$this->name.' applying for both a '.$this->DB->get_table_title('customer_id').' and a '.$this->DB->get_table_title('customer_type_id').'.',
				// DESCRIPTION
					'',
				// DETAILS
					'Row Used: ['.			var_export($this->row, true)			.']',
				// WHERE
					'Class: '.				get_class($this).' - '.__CLASS__		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// FRIENDLY
					true
			);
			return NULL;
		}
		
		return empty($display_warning);
	}
	
	// ===========================
}

?>