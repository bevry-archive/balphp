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
require_once(dirname(__FILE__).'/../../core/functions/_files.funcs.php');

class Product extends ShopObject
{	// This class represents a Product in a Shop
	// Creator: Bejamin "balupton" Lupton

	// ===========================
	
	// Object Arrays
	// var $associated_products = array();
	var $associations = array();
	var $licences = array();
	var $discounts = array();
	var $downloads = array();
	var $serials = array();
	
	var $applied_discounts = array();
	var $price = NULL;
	
	var $Licence = NULL;

	// ===========================
	
	function Product ( $row = NULL, $perform_action = true )
	{	
		$this->Shop = & $GLOBALS['Shop'];
		$this->table_nickname = 'products';
		$this->table = & $this->Shop->DB->get_table($this->table_nickname);
		
		$this->table['columns']['summary']['input'] = 'wysiwyg';
		$this->table['columns']['body']['input'] = 'wysiwyg';
		
		$this->table['columns']['version_major']['title'] = 'Version';
		$this->table['columns']['version_minor']['input'] = false;
		$this->table['columns']['version_build']['input'] = false;
		
		$this->table['columns']['file_clickonce']['input'] = 'clickonce_select';
		$this->table['columns']['file_web']['input'] = 
		$this->table['columns']['file_local']['input'] = 
		$this->table['columns']['file_general']['input'] = 
		$this->table['columns']['file_picture']['input'] = 'file_select';
		
		$this->table['columns']['file_clickonce']['params']['import_path'] = CLICKONCE_PATH;
		$this->table['columns']['file_web']['params']['import_path'] = IMPORT_PATH.'web/';
		$this->table['columns']['file_local']['params']['import_path'] = IMPORT_PATH.'local/';
		$this->table['columns']['file_general']['params']['import_path'] = IMPORT_PATH.'general/';
		$this->table['columns']['file_picture']['params']['import_path'] = IMPORT_PATH.'picture/';
		
		$this->table['columns']['file_clickonce']['params']['import_url'] = CLICKONCE_URL;
		$this->table['columns']['file_web']['params']['import_url'] = IMPORT_URL.'web/';
		$this->table['columns']['file_local']['params']['import_url'] = IMPORT_URL.'local/';
		$this->table['columns']['file_general']['params']['import_url'] = IMPORT_URL.'general/';
		$this->table['columns']['file_picture']['params']['import_url'] = IMPORT_URL.'picture/';
		
		$this->table['columns']['file_clickonce']['params']['publish_path'] = CLICKONCE_PATH;
		$this->table['columns']['file_web']['params']['publish_path'] = PUBLISH_PATH.'web/';
		$this->table['columns']['file_local']['params']['publish_path'] = PUBLISH_PATH.'local/';
		$this->table['columns']['file_general']['params']['publish_path'] = PUBLISH_PATH.'general/';
		$this->table['columns']['file_picture']['params']['publish_path'] = PUBLISH_PATH.'picture/';
		
		$this->table['columns']['file_clickonce']['params']['publish_url'] = CLICKONCE_URL;
		$this->table['columns']['file_web']['params']['publish_url'] = PUBLISH_URL.'web/';
		$this->table['columns']['file_local']['params']['publish_url'] = PUBLISH_URL.'local/';
		$this->table['columns']['file_general']['params']['publish_url'] = PUBLISH_URL.'general/';
		$this->table['columns']['file_picture']['params']['publish_url'] = PUBLISH_URL.'picture/';
		
		// Finish Construction
		return $this->ShopObject('products', $row, $perform_action);
		
		/*
		// Check if everything went well
		if ( !$this->check_status(false) )	return NULL;
		
		return true;
		*/
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
		if ( parent::create($add_log_on_success) )
		{	// Create was sucessful
			
			// Create the association for a document set
			if ( $this->get('document_set') )
			{	// Is a document set
				$Association = new Association(array(
					'primary_product_id'	=> $this->id,
					'secondary_product_id'	=> $this->id,
					'order_position'		=> 0
				));
				
				if ( !$Association->status )
				{	// Failed to create association
					// Let's delete
					$this->delete($add_log_on_success);
					return false;
				}
			}
		}
		
		return true;
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
		if ( parent::update($add_log_on_success) )
		{	// Update was sucessful
			
			// Update the association for a document set
			if ( $this->get('document_set') )
			{	// is ds
				
				$total = $this->DB->total(
					// TABLE
					'associations',
					// WHERE INFO
					array(
						array('primary_product_id',		$this->id),
						array('secondary_product_id',	$this->id)
					)
				);
				if ( $total !== 0 )
					return true;
				
				$Association = new Association(array(
					'primary_product_id'	=> $this->id,
					'secondary_product_id'	=> $this->id,
					'order_position'		=> 0
				));
				
				if ( !$Association->status )
				{	// Failed to create association
					// Let's delete
					$this->delete($add_log_on_success);
					return false;
				}
			}
			else
			{	// Is not a document set
				return $this->DB->delete(
					// TABLE
					'associations',
					// WHERE
					array(
						array('primary_product_id',		$this->id),
						array('secondary_product_id',	$this->id)
					)
				);
			}
					
		}
		
		return true;
	}
	
	function validate ( )
	{
		$display_warning = '';
		
		$release_level = $this->get('release_level');
		
		$file_clickonce = $this->get('file_clickonce') || !array_key_exists('file_clickonce', $this->row);
		$file_web = $this->get('file_web') || !array_key_exists('file_web', $this->row);
		$file_local = $this->get('file_local') || !array_key_exists('file_local', $this->row);
		$file_general = $this->get('file_general') || !array_key_exists('file_general', $this->row);
		$file_picture = $this->get('file_picture') || !array_key_exists('file_picture', $this->row);
		
		switch ( $release_level )
		{
			case 'free':
				if ( empty($file_general) )
					$display_warning .= 'You do not have a general file (This is required for free downloads).'."\r\n";
				
				if ( !empty($file_clickonce) && !empty($file_web) || !empty($file_local) )
					$display_warning .= 'You have a clickonce, web or local install file (These are not available to free downloads).'."\r\n";
				
				if ( !empty($file_picture) )
					$display_warning .= 'You have a picture file (This is not available to free downloads).'."\r\n";
				
				break;
			
			
			case 'registered_free':
			case 'member_free':
				if ( !empty($file_general) && (!empty($file_clickonce) || !empty($file_web) || !empty($file_local) || !empty($file_picture)) )
					$display_warning .= 'You have a general file along with install (and picture) files which is conflicting, pick one or the other. '."\r\n";
				
				break;
			
			
			case 'purchase':
			case 'trial':
				if ( empty($file_clickonce) && empty($file_web) && empty($file_local) )
					$display_warning .= 'You do not have a clickonce, web or local install file (One of these are required for purchase/trial downloads).'."\r\n";
				
				if ( !empty($file_general) )
					$display_warning .= 'You have a general file (This is not available for purchase/trial downloads).'."\r\n";
					
				break;
			
			
			default:
				die('eeeek, unknown release level.');
				break;
		}
		
		// Check Files / Release Level
		if ( !empty($display_warning) )
		{	// We have a warning
			$this->set('display', false);
			$this->Log->add(
				// TYPE
					'warning',
				// TITLE
					'The display value for the current '.$this->name.' has been set to false.',
				// DESCRIPTION
					'The '.$this->name.' has a release level of ['.$release_level.'] which caused the following problems:  '."\r\n".$display_warning,
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
		}
		
		// Check Licence
		if ( $release_level == 'purchase' && !$this->get_Licence(NULL) )
		{	// There is no individual licence
			$this->set('display', false);
			$this->Log->add(
				// TYPE
					'warning',
				// TITLE
					'The display value for the current '.$this->name.' has been set to false.',
				// DESCRIPTION
					'This occured because there is no Base Licence (a Licence for individuals) for this purchasable '.$this->name.'.',
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
		}
		
		// Check Serial
		if ( $release_level == 'purchase' && !$this->get_Serial() )
		{	// There is no individual licence
			$this->set('display', false);
			$this->Log->add(
				// TYPE
					'warning',
				// TITLE
					'The display value for the current '.$this->name.' has been set to false.',
				// DESCRIPTION
					'This occured because there is no Serial for this purchasable '.$this->name.'.',
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
		}
		
		// Check Display
		if ( empty($display_warning) && !$this->get('display') )
		{
			$this->Log->add(
				// TYPE
					'warning',
				// TITLE
					'The display value for the current '.$this->name.' is currently set to false.',
				// DESCRIPTION
					'This means that the product will not be displayed on the public area of the site.',
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
		}
		
		return empty($display_warning);
	}
	
	function & get_Licence ( $customer_type_id = -1, $student_teacher = -1 )
	{
		if ( !empty($this->Licence) )
			return $this->Licence;
		
		$this->load_relations('licences');
		
		$Licence = NULL;
		$found_licence = false;
		
		if ( $customer_type_id === -1 )
			$customer_type_id = empty($this->Shop->Customer) ? NULL : $this->Shop->Customer->get('customer_type_id');
		if ( $student_teacher === -1 )
			$student_teacher = empty($this->Shop->Customer) ? false : $this->Shop->Customer->get('valid_student_teacher');
		
		for ( $i = 0, $n = sizeof($this->licences); $i < $n; $i++ )
		{	// Cycle through all the licences
			$licence = $this->licences[$i];
			$Licence = new Licence($licence);
			if ( !$Licence->status )
			{
				$return = NULL;
				return $return;
			}
			$Licence_customer_type_id = $Licence->get('customer_type_id');
			if ( is_null($Licence_customer_type_id) )
			{
				if ( $Licence->get('student_teacher') )
				{
					if ( $student_teacher )
					{	// Student teacher licence
						$found_licence = true;
						break;
					}
				}
				else
				{	// Individual licence is the base
					$found_licence = true;
				}
			}
			if ( !$Licence->get('student_teacher') && $Licence_customer_type_id == $customer_type_id )
			{	// We have a match
				$found_licence = true;
				break;
			}
		}
		
		if ( !$found_licence || !$Licence->status )
		{
			$return = NULL;
			return $return;
		}
		
		$this->Licence = $Licence;
		return $this->Licence;
	}
	
	function get_Serial ( )
	{
		$this->load_relations('serials');
		if ( !isset($this->serials[0]) )
			return NULL;
		
		$Serial = new Serial($this->serials[0]);
		return $Serial;
	}
	
	function total_relations ( $what )
	{
		// Continue
		$table_nickname = strtolower($what);
		
		switch ( $what )
		{
			case 'associations':
				$total = $this->DB->total(
					// TABLE
					$table_nickname,
					// WHERE INFO
					array(
						array('primary_product_id', $this->id)
					)
				);
				
				// Check if everything went well
				if ( !$this->check_status(true) )	return NULL;
				
				// IF it did we are here
				return $total;
				break;
				
			case 'licences':
			case 'discounts':
			case 'downloads':
			case 'serials':
				$total = $this->DB->total(
					// TABLE
					$table_nickname,
					// WHERE INFO
					array(
						array('product_id', $this->id)
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
	
	function load_applied_discounts ( $Customer = NULL )
	{	// Customer required
		if ( $Customer === NULL )
			$Customer = &$this->Shop->Customer;
		
		if ( !$Customer )
			return NULL;
		
		$discounts = $this->discounts;
		$this->discounts = array();
		$where = 
			array(
				'(',
					array('customer_id', $Customer->id),
					'OR',
					array('customer_type_id', $Customer->get('customer_type_id')),
				')'
			);
		$result = $this->load_relations('discounts', NULL, $where);
		$this->applied_discounts = $this->discounts;
		$this->discounts = $discounts;
		
		return $result;
	}
	
	function load_relations ( $what = NULL, $limit = NULL, $where = array() )
	{
		// What to do
		if ( is_null($what) )
		{
			$this->load_relations('licences', $limit, $where);
			$this->load_relations('discounts', $limit, $where);
			$this->load_relations('downloads', $limit, $where);
			$this->load_relations('associations', $limit, $where);
			$this->load_relations('serials', $limit, $where);
			
			// Check if everything went well
			if ( !$this->check_status(false) )	return NULL;
			
			return true;
		}
		
		// Check if we have already loaded this
		if ( !empty($this->$what) )
			return true;
			
		// Continue
		$table_nickname = strtolower($what);
		
		switch ( $what )
		{
			case 'associations':
				// Where
				$where[] = array('primary_product_id', $this->id);
				//
				$this->$what = array();
				$rows = $this->DB->search(
					// TABLE
					$table_nickname,
					// COLUMNS
					'id',
					// WHERE INFO
					$where,
					// LIMIT
					$limit
				);
				
				// Check if everything went well
				if ( !$this->check_status(true) )	return NULL;
				
				// Everything went well so lets set the $what with the array of ids
				$this->$what = $rows;
				break;
			
			case 'licences':
			case 'discounts':
			case 'downloads':
			case 'serials':
				// Where
				$where[] = array('product_id', $this->id);
				// 
				$this->$what = array();
				$rows = $this->DB->search(
					// TABLE
					$table_nickname,
					// COLUMNS
					'id',
					// WHERE INFO
					$where,
					// LIMIT
					$limit
				);
				
				// Check if everything went well
				if ( !$this->check_status(true) )	return NULL;
				
				// Everything went well so lets set the $what with the array of ids
				$this->$what = $rows;
				break;
			
			default:
				die('Unknown Relation: '.$what);
				break;
		}
		
		return true;
				
	}
	
	function display_input__clickonce_select ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display clickonce dropdown
		$params['regex'] = '/^(.+)\.application$/';
		return $this->display_input__file_select($column, $field_name, $action, $value, $get_value, $params);
	}
	
	function display_input__version_major ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display the major version
		$this->table['columns']['version_minor']['input'] =
		$this->table['columns']['version_build']['input'] = 'textbox';
		
		$this->display_input__textbox($column, $field_name, $action, $value, $get_value, $params);
		echo '&nbsp;.&nbsp;';
		$this->display_input('version_minor', $action);
		echo '&nbsp;.&nbsp;';
		$this->display_input('version_build', $action);
		
		# Bad hack
		$this->table['columns']['version_minor']['input'] =
		$this->table['columns']['version_build']['input'] = false;
		
		# Return
		return true;
	}
	
	
	/* public */
	function get_column_title ( $column )
	{
		if ( $column === 'version' )
			return 'Version';
		return $this->DB->get_column_title($this->table, $column);
	}
	
	/* public */
	function get_prices ( $Customer = NULL )
	{
		// Load Customer if needed
		if ( $Customer === NULL )
			$Customer = &$this->Shop->Customer;
		
		// Get on with it
		$this->load_relations('licences');
		$prices = array();
		
		// Do the code
		for ( $i = 0, $n = sizeof($this->licences); $i < $n; ++$i )
		{
			$licence = $this->licences[$i];
			$Licence =& new Licence($licence);
			if ( !$Licence->status )
				return $Licence->status;
			
			$price = $this->get_price($Licence, NULL, true);
			$prices[] = $price;
		}
		
		// Return
		return $prices;
	}
	
	/* public */
	function get_price ( $Licence = NULL, $Customer = NULL, $detailed = false )
	{	// Licence is required
		// Customer is optional, Customer = false if none
		
		if ( empty($Licence) && empty($Customer) && !empty($this->price) )
		{	// Everything is default so use default price
			return $detailed ? $this->price : $this->price['price'];
		}
		
		if ( $Licence === NULL )
		{	// No custom Licence specified
			$Licence = $this->get_Licence();
			if ( $Licence === NULL )
				return NULL;
		}
		$licence = $Licence->id;
		$total_price = $base_price = $Licence->get('base_price');
		$customer_type = $Licence->get('customer_type');
		
		// Additional price mods
		$teachers_applied = $discount_applied = false;
		$total_teacher_amount = $total_discount_amount = 0;
		
		// Teachers
		if ( $customer_type === 'school' )
		{	// Licence applies for schools set some vars
			$teacher_cap = $Licence->get('teacher_cap');
			$per_teacher_new = $Licence->get('per_teacher_new');
		}
		
		// Load Customer if needed
		if ( $Customer === NULL )
			$Customer = &$this->Shop->Customer;
		
		// Do we have a cusotmer
		if ( $Customer )
		{	// We do
			$customer = $Customer->id;
			
			// Teachers
			if ( $customer_type === 'school' && $Customer->get('customer_type_id') )
			{	// We have a school, let's add the per teacher cost
				$Customer_teachers = $Customer->get('teachers');
				$teacher_cap_applied = $Customer_teachers > $teacher_cap;
				if ( $teacher_cap_applied )
					$teachers = $teacher_cap;
				else
					$teachers = $Customer_teachers;
				$total_teacher_amount += $per_teacher_new*$teachers;
				$teachers_applied = true;
			}
			
			// Discounts
			$this->load_applied_discounts($Customer);
			if ( !empty($this->applied_discounts) )
			{
				$discount_applied = true;
				$discounts = sizeof($this->applied_discounts);
				for ( $i = 0, $n = $discounts; $i < $n; $i++ )
				{
					$discount = $this->discounts[$i];
					$Discount =& new Discount($discount);
					if ( !$Discount->status )
						return $Discount->status;
					$total_discount_amount -= $Discount->get('amount');
				}
			}
		} else
			$customer = NULL;
		
		// Do alternatives
		$price = $total_price;
		$price += $total_teacher_amount;
		$price -= $total_discount_amount;
		
		//
		$selected_Licence = $this->get_Licence();
		$selected = $Licence->id === $selected_Licence->id;
		
		// Return detailed report
		if ( $detailed )
		{	
			$result = compact(
				'price', 'total_price', 'base_price',
				'teachers_applied', 'discount_applied', 'discounts', 'teacher_cap_applied',
				'total_teacher_amount', 'Customer_teachers', 'teachers', 'teacher_cap', 'per_teacher_new',
				'total_discount_amount',
				'customer', 'licence',
				// 'Customer', 'Licence'
				'customer_type',
				'selected'
			);
			return $result;
		}
		
		// Done
		return $price;
	}
	
	/* public */
	function get ( $column_nickname, $display = false, $default = '', $get_title = true )
	{	// Get a column value
		// Should be re-written to use display_input style coding
		
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		switch ( $column_nickname )
		{	
			case 'id':
				$value = parent::get($column_nickname, $display, $default, $get_title);
				if ( !$display )
					break;
				
				$str = '000000';
				$str = substr($str, 0, 6-strlen($value));
				$str .= $value;
				$value = $str;
				break;
			
			case 'version':
				$value = $this->get('version_major', $display);
				$value .= '.'.$this->get('version_minor', $display);
				$value .= '.'.$this->get('version_build', $display);
				break;
			
			case 'serial':
				$Serial = $this->get_Serial();
				if ( !$Serial || !$Serial->status )
					return NULL;
				$value = $Serial->get('serial');
				break;
			
			case 'prices':
				# Get the prices
				
				// There is no simple mode
				
				// Advanced
				$result = '<br />'."\r\n".'<strong>Licences</strong>';
				
				// Get Prices
				if ( $this->Shop->Customer )
				{	// User logged in
					/*
						# After person is logged in, show the relevant product price from one of these options:
						1) School Licence Total: $3300
						- Base Licence: $1100
						- 20 Teacher Licence: $2200 ($220 each)
						2) Individual Teacher Licence: $399
						3) Student Teacher Licence: $99 
					*/
					$price = $this->get_price(NULL, NULL, true);
					$prices = array($price);
				}
				else
				{	// User logged in
					/*
						# Before person is logged in, show all product prices as follows:
						School Licence: $1100 plus $220* per teacher
						Individual Teacher Licence: $399
						Student Teacher Licence: $99 
					*/
					$prices = $this->get_prices();
				}
				
				// Append
				for ( $i = 0, $n = sizeof($prices); $i < $n; ++$i )
				{
					$price = $prices[$i];
					
					if ( $price['selected'] )
						$result .= '<em>';
					
					$customer_type = $price['customer_type'];
					switch ( $customer_type )
					{
						case 'school':
							$result .= empty($price['Customer_teachers'])
							?	// Not logged in
								'<br />'."\r\n".
									'School Licence: $'.format_to_output($price['base_price'], 'htmlbody').', plus '.
										format_to_output($price['per_teacher_new'], 'htmlbody').'* per teacher'
							:	// Is logged in
								'<br />'."\r\n".'School Licence Total: $'.format_to_output($price['price'], 'htmlbody').', made up of:'.
								'<br />'."\r\n".'- Site Licence: $'.format_to_output($price['base_price'], 'htmlbody').
								'<br />'."\r\n".'- '.$price['Customer_teachers'].' Teacher Licence: $'.format_to_output($price['total_teacher_amount'], 'htmlbody').
								(	!empty($price['teacher_cap_applied'])
									? ' <em>(Capped on '.$price['teacher_cap'].' teachers)</em>'
									: ''
								).
								(	empty($price['teacher_cap_applied'])
								?	'<br /><em>* volume discounts available for '.$price['teacher_cap'].' or more teachers</em>'
								:	''
								);	
							break;
							
						case 'individual':
							$result .= '<br />'."\r\n".'Individual Teacher Licence: $'.format_to_output($price['price'], 'htmlbody');
							break;
							
						case 'student_teacher':
							$result .= '<br />'."\r\n".'Student Teacher Licence: $'.format_to_output($price['price'], 'htmlbody');
							break;
							
					}
					
					if ( $price['selected'] )
						$result .= '</em>';
				}
					
				// Discount part
				if ( !empty($price['total_discount_amount']) )
				{	// Discount was applied
					$result .= '<br />'."\r\n".
						'<br />'."\r\n".'Discount'.($price['discounts'] > 1 ? 's' : '').': $'.format_to_output($price['total_discount_amount'], 'htmlbody').' <em>(Already Applied)</em>';
				}
				
				// Finish
				$value = $result;
				break;
			
			case 'price':
				# Get the price
				$price = $this->get_price(NULL, NULL, true);
				
				// Simple
				if ( $display !== 'htmlbody' )
				{	// Just get the dam price
					return $price['price'];
				}
				
				// Advanced
				$result = '$'.$price['price'];
				
				switch ( $price['customer_type'] )
				{
					case 'school':
						$result .= ' ('.$price['Customer_teachers'].' Teacher Licence)';
						break;
					case 'student_teacher':
						$result .= ' (Individual Student Teacher Licence)';
						break;
					case 'individual':
						$result .= ' (Individual Licence)';
						break;
				}
				
				if ( $price['discount_applied'] )
					$result .= ' (Discount Applied)';
				
				// Finish
				$value = $result;
				break;
			
			case 'body':
			case 'summary':
				if ( !$display )
					continue;
				
				// We want to append the prices
				$value = parent::get($column_nickname, $display, $default, $get_title);
				$value .= $this->get('prices', $display);
				break;
			
			default:
				$value = parent::get($column_nickname, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	/* public */
	function get_dirsize ( $column, $human = true, $default = NULL )
	{
		if ( $column !== 'file_clickonce' )
			return parent::get_dirsize($column, $human, $default);
		
		// file_clickonce
		$file_clickonce = $this->get_file_path('file_clickonce');
		$contents = file_get_contents($file_clickonce);
		$version = substr($contents, $start = strpos($contents, ' version="', strpos($contents, '<assemblyIdentity'))+10, strpos($contents, '"', $start)-$start);
		$clickonce_dir = substr($file_clickonce, 0, strlen($file_clickonce)-12).'_'.str_replace('.','_',$version).'/';
		if ( !is_dir($clickonce_dir) )
		{
			$size = parent::get_dirsize('file_clickonce');
		} else
		{
			$size = dirsize($clickonce_dir);
			if ( $human )
				$size = filesize_to_human($size);
		}
		return $size;
	}
				
	/* public */
	function get_download_size ( $type = 'total', $human = true, $default = NULL )
	{
		$size = '';
		
		switch ( $type )
		{
			case 'total':
				
				$file_general_size = $this->get_download_size('file_general', $human);
				if ( $file_general_size )
					return $file_general_size;
				
				$size = 0;
				$size += $this->get_download_size('file_clickonce', false, 0);
				$size += $this->get_download_size('file_local', false, 0);
				$size += $this->get_download_size('file_web', false, 0);
				if ( $human )
					$size = filesize_to_human($size);
				break;
			
			case 'file_general':
			case 'file_web':
			case 'file_local':
				if ( $this->get($type) )
					$size = $this->get_filesize($type, $human);
				break;
			
			case 'file_clickonce':
				if ( $this->get('file_clickonce') )
					$size = $this->get_dirsize('file_clickonce', $human);
				break;
			
			case 'associations':
			case 'document_set':
				if ( !$this->get('document_set') )
					break;
				
				$size = 0;
				$this->load_relations('associations');
				for ( $i = 0, $n = sizeof($this->associations); $i < $n; ++$i )
				{
					$association = $this->associations[$i];
					$Association = new Association($association);
					
					$product = $Association->get('secondary_product_id');
					$Product = new Product($product);
					
					$size += $Product->get_download_size('total', false, 0);
				}
				if ( $human )
					$size = filesize_to_human($size);
				break;
		}
		
		if ( empty($size) )
			return $default;
		
		return $size;
	}
	
	/* public */
	function get_download_type ( $default = false )
	{	
		$type = '';
		
		$file_general = $this->get('file_general');
		
		if ( $file_general )
		{
			$type = filetype_human($this->get('file_general'));
		}
		else
		{
			$file_clickonce = $this->get('file_clickonce');
			$file_web = $this->get('file_web');
			$file_local = $this->get('file_local');
			if ( $file_clickonce || $file_web || $file_local )
				$type = 'Install';
		}
		
		if ( empty($type) )
			return $default;
		
		return $type;
	}
	
	/* public */
	function get_download_url_params ( $file_type, $force = false, $additional_params = '' )
	{
		if ( substr($file_type, 0, 5 /* 'file_' */) === 'file_' )
			$file_type = substr($file_type, 5);
		
		if ( !$force )
		{
			if ( $file_type === 'clickonce_download' )
			{
				$file = $this->get('file_clickonce');
			}
			else
			{
				$file = $this->get('file_'.$file_type);
			}
			if ( !$file )
				return NULL;
		}
		
		if ( $file_type === 'clickonce_download' )
		{	// We could also add something unique to the product to check as well
			// - NO POINT! We know wether the user/customer has access to the product through purchase details!
			
			$crypt = '';
			
			$Product_url_params = $this->get_url_params();
			$crypt .= str_replace('&amp;', '&', $Product_url_params);
			$crypt .= $this->name.'_file_clickonce='.$this->get('file_clickonce', 'htmlattr').'&';
			
			$User_url_params = $this->Shop->User->get_url_params();
			$crypt .= str_replace('&amp;', '&', $User_url_params);
			$crypt .= 'User_email='.$this->Shop->User->get('email', 'htmlattr').'&';
			
			$Customer_url_params = $this->Shop->Customer->get_url_params();
			$crypt .= str_replace('&amp;', '&', $Customer_url_params);
			$crypt .= 'Customer_email='.$this->Shop->Customer->get('email', 'htmlattr').'&';
			
			$crypt .= 'clickonce_url_key='.CLICKONCE_URL_KEY.'&';
			
			$crypt .= 'file_type=clickonce&';
			$crypt .= 'download=true&';
			$crypt .= 'site='.SITE.'&';
			$crypt .= $additional_params;
			
			$crypt = encode($crypt);
			
			$url_params = 'crypt='.$crypt;
			
			/*
			$url_params =
				$this->get_url_params().
				'file_type=clickonce&amp;'.
				'download=true&amp;'.
				'crypt='.$crypt.'&amp;'.
				$additional_params
			;
			*/
		}
		else
		{
			$url_params =
				$this->get_url_params().
				'file_type='.$file_type.'&amp;'.
				$additional_params
			;
		}
		
		return $url_params;
	}
	
	// Get download links
	function get_download_url ( $type, $download_params = ''  )
	{
		if ( is_array($type) && isset($type['url']) )
		{
			$url = $type['url'];
		}
		elseif ( $type === 'file_clickonce' || $type === 'clickonce' )
		{
			$url_params = $this->get_download_url_params('clickonce_download', false, $download_params);
			$file_clickonce = $this->get('file_clickonce');
			$url = CLICKONCE_URL.$file_clickonce.'?'.$url_params;
		}
		else
		{
			$url = DOWNLOAD_URL.'download.php?'.$this->get_download_url_params($type, true, $download_params);
		}
		return $url;
	}
	
	function download_link_check ( )
	{
		$release_level = $this->get('release_level');
		switch ( $release_level )
		{
			case 'registered_free':
				// Check if user is logged in
				if ( !$this->Shop->Customer )
				{	// Display login
					return
						'Only available to registered users. '.
						$this->Shop->get_signup_link().
						', or '.
						$this->Shop->get_login_link().'.';
				}
				break;
				
			case 'member_free':
				// Check if user is logged in
				if ( !$this->Shop->Customer )
				{	// Display login
					return
						'Only available to members who have made a purchase. '.
						$this->Shop->get_signup_link().
						', or '.
						$this->Shop->get_login_link().'.';
				}
				elseif ( !$this->Shop->Customer->get('is_member') )
				{	// The customer is not a member
					return 'Only available to members who have made a purchase.';
				}
				break;
			
			case 'purchase':
			case 'trial':
				if ( !$this->Shop->Customer )
				{	// Display login
					return
						'Only available to customers. '.
						$this->Shop->get_signup_link().
						', or '.
						$this->Shop->get_login_link().'.';
				}
				break;
				
			default:
				break;
		}
		
		return false;
	}
	
	function get_download_link ( $type, $download_params = '', $link_type = 'text', $include_download_size = true )
	{
		if ( $download_link_check = $this->download_link_check() )
			return $download_link_check;
		
		$text = $title = '';
		switch ( $type )
		{
			case 'document_set':
				$target = '_self';
				$title	= 'Install the Package Online';
				$text	= 'Install the Package';
				break;
			
			case 'file_general':
				$target = '_self';
				$title	= 'Download the File';
				$text	= 'Download the File';
				break;
			
			case 'file_clickonce':
				$url = $this->get_download_url($type, $download_params);
				$target = strpos($url, '.php') > 1 ? '_blank' : '_self';
				$title	= 'Install this Product via ClickOnce';
				$text	= 'Install via ClickOnce';
				break;
				
			case 'file_web':
				$target = '_blank';
				$title	= 'Install this Product Online';
				$text	= 'Install Online';
				break;
			
			case 'file_local':
				$target = '_self';
				$title	= 'Download the Installer for this Product';
				$text	= 'Download the Installer';
				break;
			
			default:
				$target = '_self';
				$title = $text = 'Unknown';
				if ( is_array($type) )
					extract($type);
				break;
		}
		
		$popup_text = 'popup=true&amp;';
		if ( $target === '_blank' && strpos($download_params, $popup_text) <= 0 )
			$download_params .= $popup_text;
		
		if ( !isset($url) )
			$url = $this->get_download_url($type, $download_params);
		if ( !isset($target) )
			$target = strpos($url, '.php') > 1 ? '_blank' : '_self';
		
		if ( $include_download_size )
			$text .= ' ('.$this->get_download_size($type, true, 'Unknown').')';
		
		if ( !isset($style) )
			$style = '';
		
		switch ( $link_type )
		{
			case 'button':
				$params = substr($url, strpos($url, '?')+1);
				$button = '<input onclick="window.open(\''.$url.'\', \''.$target.'\'); return false;" type="submit" title="'.$title.'" value="'.$text.'" />';
				$text = $button;
				if ( strpos($style, 'text-decoration') <= 0 )
					$style .= 'text-decoration:none; ';
				// $link = '<form method="get" action="'.$url.'" target="'.$target.'">'.regenerate_params('form', $params).$button.'</form>';
				// break;
				
			default:
				$link = '<a target="'.$target.'" style="'.$style.'" href="'.$url.'" title="'.$title.'">'.$text.'</a>';
				break;
		}
		
		return $link;
	}
	
	function get_download_links ( $include_document_set = true, $download_params = '', $include_download_size = true, $link_type = false )
	{
		if ( $download_link_check = $this->download_link_check() )
			return $download_link_check;
		
		$download_links = '';
		
		if ( $include_document_set && $this->get('document_set') )
		{
			$download_links .= $this->get_download_link('document_set', $download_params, $link_type, $include_download_size);
		}
		elseif ( $this->get('file_general') )
		{
			$download_links .= $this->get_download_link('file_general', $download_params, $link_type, $include_download_size);
		}
		else
		{	// This bits used for trials and member free downloads
			if ( $this->get('file_clickonce') )
			{
				$download_links .= $this->get_download_link('file_clickonce', $download_params, $link_type, $include_download_size);
			}
			if ( $this->get('file_local') )
			{
				$download_links .= $this->get_download_link('file_local', $download_params, $link_type, $include_download_size);
			}
			if ( $this->get('file_web') )
			{
				$download_links .= $this->get_download_link('file_web', $download_params, $link_type, $include_download_size);
			}
		}
		
		return $download_links;
	}
	
}
