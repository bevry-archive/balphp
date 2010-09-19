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

require_once(dirname(__FILE__).'/../../data/classes/_DataObject.class.php');

class ShopObject extends DataObject
{	// A DataObject of a Shop Object

	var $Log;
	var $Shop;
	
	// Auhtorize
	var $authorize_create = NULL;
	var $authorize_delete = NULL;
	var $authorize_update = NULL;
	var $authorize_load = NULL;
	
	function ShopObject ( $table_nickname, $row = NULL, $perform_action = true )
	{
		if ( empty($this->name) )
		{
			$class = ucfirst(get_class($this));
			$this->name = $class == 'Shopobject' ? 'ShopObject' : $class;
		}
		if ( empty($this->Shop) )
			$this->Shop = & $GLOBALS['Shop'];
		if ( empty($this->Log) )
			$this->Log = & $this->Shop->Log;
		
		// Authorize
		if ( is_null($this->authorize_create) ) $this->authorize_create = $this->Shop->admin;
		if ( is_null($this->authorize_delete) ) $this->authorize_delete = $this->Shop->admin;
		if ( is_null($this->authorize_update) ) $this->authorize_update = $this->Shop->admin;
		if ( is_null($this->authorize_load) ) $this->authorize_load = true;
		
		// Create and return
		return $this->DataObject($this->Shop->DB, $table_nickname, $row, $perform_action );
	}
	
	// ===========================
	
	/* public */
	function get ( $column_nickname, $display = false, $default = '', $get_title = true )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		
		$column = $this->get_column($column_nickname);
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
		{	// Something failed
			$this->status = false;
			return NULL;
		}
		$column_nickname = $column['nickname'];
		
		switch ( $column_nickname )
		{
			case 'id':
				$value = parent::get($column, $display, $default, $get_title);
				if ( $display != 'htmlbody' )
					break;
				
				$str = '000000';
				$str = substr($str, 0, 6-strlen($value));
				$str .= $value;
				$value = $str;
				break;
				
			default:
				$value = parent::get($column, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	// ===========================
	
	function authorized ( $action, $error = true )
	{
		$var = 'authorize_'.$action;
		$authorized = $this->$var;
		unset($var);
		
		if ( !$authorized )
		{
			if ( !$error )
				return false;
			
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					'You are not authorized to perform that action on a '.$this->name.'.',
				// DESCRIPTION
					'Try logging in as a admin.',
				// DETAILS
					'Authorized Status: ['.	var_export($authorized, true)		.']'."\r\n".
					'Admin Status: ['.		var_export($this->Shop->admin, true)	.']',
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
		
		return true;
	}
	
	// ===========================
	
	function display_description ( $column_nickname, $action, $confirm_value = false )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		
		$column = & $this->DB->get_column($this->table, $column_nickname);
		if ( !$this->check_status(true, false, __FUNCTION__) )
			return NULL;
			
		// ----
		
		switch ( $column['nickname'] )
		{
			case 'body':
				return true;
				break;
			
			default:
				return parent::display_description($column, $action, $confirm_value);
				break;
		}
		
		return true;
	}
	
	// ===========================
	
	/* public */
	function update ( $add_log_on_success = true )
	{	
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Set action
		$this->action = 'update';
		
		# Check if we are authorized
		if ( !$this->authorized('update') )
			return NULL;
		
		# Still all good
		return parent::update($add_log_on_success);
	}
	
	/* public */
	function delete ( $add_log_on_success = true )
	{	
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Set action
		$this->action = 'delete';
		
		# Check if we are authorized
		if ( !$this->authorized('delete') )
			return NULL;
		
		# Still all good
		return parent::delete($add_log_on_success);
	}
	
	/* public */
	function create ( $add_log_on_success = true )
	{	
		# Check if we are deconstructed, if we are then fail
		$this->status = true;
		
		# Set action
		$this->action = 'create';
		
		# Check if we are authorized
		if ( !$this->authorized('create') )
			return NULL;
		
		# Still all good
		return parent::create($add_log_on_success);
	}
	
	// ===========================
	
	function display_input__wysiwyg ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a textarea
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		# Display WYSIWYG Editor
		echo '<textarea name="'.$field_name.'" id="'.$field_name.'" class="wysiwyg" style="width:100%">'.$value.'</textarea>';
		require(LIB_TINYMCE_PATH.'_template.inc.php');
		
		# Return
		return true;
	}
	
}
