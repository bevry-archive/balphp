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

class Customer extends ShopObject
{
	var $users = array();
	var $orders = array();
	
	var $type = NULL;
	var $customer_type = NULL;
	
	// Authorize
	var $authorize_create;
	var $authorize_delete;

	// ===========================
	
	function Customer ( $row = NULL, $perform_action = true )
	{	
		// Construct
		if ( empty($this->Shop) )
			$this->Shop = & $GLOBALS['Shop'];
		if ( empty($this->Log) )
			$this->Log = & $this->Shop->Log;
		
		// Authorize
		$this->authorize_create = !$this->Shop->admin;
		// $this->authorize_delete = false;
		
		// Finish Construction
		return $this->ShopObject('customers', $row, $perform_action);
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
		
		# Create
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
		
		# Update
		return parent::update($add_log_on_success);
	}
	
	function validate ( )
	{
		if ( $this->get('student_teacher') && !$this->get('valid_student_teacher') )
		{	// Student teacher problem
			$expired = $this->get('st_end_year') < (int)date('Y');
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					$expired
					?	'Your Student Teacher status has expired.'
					:	'Not all Student Teacher fields were filled out.',
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
			$this->status = false;
			return NULL;
		}
		
		// Success
		return true;
	}
	
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
				{	$value = $this->customer_type;	break;	}
				
				$type = $this->get('type');
				
				switch ( $type )
				{
					case 'school':
						$customer_type = $this->get('customer_type_id', $display, $default, $get_title);
						break;
					
					case 'student_teacher':
						$customer_type = 'Student Teacher';
						break;
					
					case 'individual':
					default:
						$customer_type = 'Individual';
						break;
				}
				
				// Finish
				$value = $this->customer_type = $customer_type;
				break;
		
			case 'type':
				if ( $this->type !== NULL )
				{	$value = $this->type;	break;	}
				
				// Do stuff
				if ( $this->get('customer_type_id') )
				{	// School
					$type = 'school';
				} else
				if ( $this->get('valid_student_teacher') )
				{	// Student Teacher
					$type = 'student_teacher';
				} else
				{	// Individual
					$type = 'individual';
				}
				
				// Finish
				$value = $this->type = $type;
				break;
				
			case 'valid_student_teacher':
				$value =
					$this->get('student_teacher')
					&&	$this->get('st_university_name')
					&&	$this->get('st_student_number')
					&&	$this->get('st_course_name')
					&&	$this->get('st_start_year')
					&&	$this->get('st_end_year')
					&&	$this->get('st_end_year') >= (int)date('Y')
					;
				break;
			
			default:
				$value = parent::get($column_nickname, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	// ===========================
	
	function authorized ( $action, $error = true )
	{
		return true;
	}
	
	// ===========================
	
	function total_relations ( $what )
	{
		// Continue
		$table_nickname = strtolower($what);
		
		switch ( $what )
		{
			case 'orders':
			case 'users':
				$total = $this->DB->total(
					// TABLE
					$table_nickname,
					// WHERE INFO
					array(
						array('customer_id', $this->id)
					)
				);
				
				// Check if everything went well
				if ( !$this->check_status(true) )	return NULL;
				
				// IF it did we are here
				return $total;
				break;
			
			default:
				die('unknown relation');
				break;
		}
		
		return NULL;
	}
	
	function load_relations ( $what = NULL )
	{
		// What to do
		if ( is_null($what) )
		{
			$this->load_relations('users');
			$this->load_relations('orders');
			
			// Check if everything went well
			if ( !$this->check_status(false) )	return NULL;
			
			return true;
		}
		
		// Continue
		$table_nickname = strtolower($what);
		
		switch ( $what )
		{
			case 'users':
				$order = array(
					array('firstname', 'asc'),
					array('lastname', 'asc')
				); // todo: remove the above and build it directly into the database structure
				
			case 'orders':
				if ( !isset($order) ) $order = NULL;
				
				$this->$what = array();
				$rows = $this->DB->search(
					// TABLE
					$table_nickname,
					// COLUMNS
					'id',
					// WHERE INFO
					array(
						array('customer_id', $this->id)
					),
					// LIMIT
					NULL,
					// ORDER
					$order
				);
				
				// Check if everything went well
				if ( !$this->check_status(true) )	return NULL;
				
				// Everything went well so lets set the $what with the array of ids
				$this->$what = $rows;
				break;
		}
		
		return true;
				
	}
	
	// ===========================
	
	function display_registration ( $page )
	{
		if ( $page != 'individual' && $page != 'company' )
			return NULL;
		
	}
}

?>