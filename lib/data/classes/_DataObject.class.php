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
 * @subpackage data
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once(dirname(__FILE__).'/_DataBase.class.php');
require_once(dirname(__FILE__).'/../../core/classes/_Log.class.php');
require_once(dirname(__FILE__).'/../../core/functions/_params.funcs.php');
require_once(dirname(__FILE__).'/../../core/functions/_strings.funcs.php');
require_once(dirname(__FILE__).'/../../core/functions/_files.funcs.php');
require_once(dirname(__FILE__).'/../../core/functions/_url.funcs.php');

class DataObject
{	/*
	 ***
	 * ABOUT
	 **
	 * Class: DataObject Class
	 * Author: Benjamin "balupton" Lupton
	 * Version: 2.0.0.0-dev
	 * Release Date: Unreleased
	 *
	 ***
	 * SUMMARY
	 **
	 * The DataObject Class is a Object that is used to represent a row (Data) within a Database Class's Table.
	 * It will handle the following actions for you;
	 *    create/add, delete, load/get, update
	 *
	 * Please use the set and get functions for getting column values
	 *
	 **
	 */
	
	var $DB;
	var $Log = NULL;
	
	var $title;
	var $name = NULL;
	var $table_nickname = NULL;
	var $table;
	
	var $required_columns = array();
	
	var $default_row = array();
	var $row = array();
	var $id = NULL;
	
	// The action performed on load
	var $action = false;	// load, create, delete, update
	
	// The status of this class
	var $status = true;		// true (everything is ok), false (an error occured), null (we were deconstructed)
	
	function DataObject ( & $DB, $table_nickname, $row = NULL, $perform_action = true )
	{	
		# ------------------------------
		# Make sure everything is ok
		
		if ( gettype($row) === 'object' && get_class($row) === get_class($this) )
		{	// We have ourselves
			$this->action			= $row->action;
			$this->row				= $row->row;
			$this->id				= $row->id;
			$this->status			= $row->status;
			$this->default_row		= $row->default_row;
			$this->required_columns	= $row->required_columns;
			$this->table			= & $row->table;
			$this->table_nickname	= $row->table_nickname;
			$this->title			= $row->title;
			$this->name				= $row->name;
			$this->Log				= & $row->Log;
			$this->DB				= & $row->DB;
			return $this->status;
		}
		
		if ( !$this->check_status(false, true, __FUNCTION__) )
		{
			$this->_deconstruct();
			return NULL;
		}
		
		# ------------------------------
		# Set variables
		
		$this->DB = & $DB;
		if ( empty($this->Log) )
			$this->Log = & $this->DB->Log;
		if ( empty($this->name) )
			$this->name = ucfirst(get_class($this)); // php4 get_class is lowercase :@ // get_class($this);
		if ( empty($this->table_nickname) )
			$this->table_nickname = $table_nickname;
		if ( empty($this->table) )
		{
			//var_export($table_nickname);
			// var_export($DB);
			// var_export($this->DB);
			$this->table = & $this->DB->get_table($this->table_nickname);
		}
		if ( !$this->check_status(true, false, __FUNCTION__) )
		{
			$this->_deconstruct();
			return NULL;
		}
		
		// get the title
		$this->title = $this->table['columns']['id']['title'];
		if ( $this->title == 'id' )
		{	// add warning about missing id column title
			$this->title = $this->name;
		}
		
		// the default row is used for the create display
		$this->default_row = $this->DB->make_valid_row($this->table, NULL, true);
		if ( !$this->check_status(true, false, __FUNCTION__) )
		{
			$this->_deconstruct();
			return NULL;
		}
		
		# get the required columns
		for ( $i = 0, $n = sizeof($this->table['columns_nicknames']); $i < $n; $i++ )
		{
			$column_nickname = $this->table['columns_nicknames'][$i];
			$column = $this->table['columns'][$column_nickname];
			if ( $column['required'] )
				$this->required_columns[] = $column_nickname;
		}
		
		# ------------------------------
		# Construct
		
		if ( empty($row) )
		{	// We do not want to perform
			$perform_action = false;
		}
		elseif ( is_numeric($row) )
		{	// Row is a id
			$row = real_value($row);
			$this->id = $row;
			
			/*	the following is some stupid code that was there instead of doing the correct:
			 *	// changed because if a row is a integer then the action is load, but we are doing updates here as well
			 *	$Obj = new ShopObject($table, 1, false);
			 *	$Obj->_load_from_request();
			 *	$Obj->perform_action();
			
			$param_id = get_param($this->name.'_id', NULL, true);
			
			if ( $param_id === $row )
			{
				if ( !$this->_load_from_request() )
				{	// If the id is in the url, but no action is in the url, we want to load
					$this->action = 'load';
				}
				if ( !$this->status )
				{	
					$this->_deconstruct();
					return NULL;
				}
			}
			else
			{
				*/
				$this->action = 'load';
				/*
			}
			*/
		}
		elseif ( is_string($row) )
		{
			
			// echo 'Blah:['.var_export($this->status, true).']';
			if ( $row === 'request' )
			{	// We want to figure out what row is from a request
				// echo 'as:['.var_export($this->status, true).']';
				$this->id = get_param($this->name.'_id', NULL, true);
				$this->_load_from_request();
				if ( $this->action && $this->action !== 'load' )
					url_remove_params($this->get_url_params($this->action));
				// echo 'dd:['.var_export($this->status, true).']';
			}
			else
			{
				$this->action = $row;
				$this->_load_from_request_row();
			}
			
			if ( $this->action !== 'load' && !empty($this->id) )
			{	// We have a id
				$_action = $this->action;
				$status = $this->status;
				$this->load(false, true);
				if ( !$status && $this->status )
					$this->status = $status;
				$this->action = $_action;
				unset($_action);
			}
			
			if ( !$this->status )
			{	
				$this->_deconstruct();
				return NULL;
			}
		}
		elseif ( is_array($row) )
		{	// we want to figure out what to do from teh row
			$this->_load_from_array($row);
		}
		else
		{	// We have a invalid row
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					'A invalid row was passed to the constructor of a '.$this->title.'.',
				// DESCRIPTION
					'',
				// DETAILS
					'Row Passed: ['.		var_export($row, true)					.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__
			);
			$this->_deconstruct();
			return NULL;
		}
		
		# ------------------------------
		# Perform the action
		
		if ( $perform_action )
		{
			return $this->perform_action();
		}
		else
		{	// The user does not want to perform an action
			$this->status = true;
			return true;
		}/*
		else
		{	
			$this->_deconstruct();
			return false;
		}*/
		
		$this->status = true;
		return true;
		
	}
	
	// ===========================
		
	/* public */
	function clear ( )
	{
		$this->id = NULL;
		$this->row = array();
	}

	/* private */
	function _deconstruct ( )
	{
		// $this->row = array();
		$this->status = NULL;
	}
	
	// ==============================================
	
	/* private */
	function perform_action ( )
	{
		if ( !$this->status )
			return $this->status;
		
		# Perform action
		if ( $this->action )
		{	// Perform the action
			$action = $this->action;
			$result = $this->$action();
			return $result;
		} else
			return false;
		
		return true;
	}
	
	/* private */
	function _load_from_array ( $row )
	{
		$this->status = true;
		
		// THREE CASES
		// 1 - We have a id in the row and nothing else then we want to get
		// 2 - We have a id in the row and other stuff then we want to update
		// 3 - We don't have a id then we want to create
		
		// We know that $row is not empty and is a array
		
		// Figure out what we want to do
		$action = false;
		
		if ( isset($this->row['id']) && is_integer($this->row['id']) )
		{	// We have a id
			$this->id = $this->row['id'];
			
			// Now check what exactly we want to do
			if ( sizeof($this->row) == 1 )
			{	// CASE 1 - We only have a id
				$action = 'load';
			}
			else
			{	// CASE 2 - We have a id with other stuff
				$action = 'update';
			}
			
		}
		else
		{	// CASE 3 - We don't have a id
			$action = 'create';
		}
		
		$this->action = $action;
		
		$pass = true;
		
		if ( $action != 'load' )
		{	// We have update or create, so lets cycle through and set the row
			$row_columns = array_keys($row);
			for ( $i = 0, $n = sizeof($row); $i < $n; $i++ )
			{
				$column_nickname = $row_columns[$i];
				$value = $row[$column_nickname];
				$this->set($column_nickname, $value);
				if ( !$this->check_status(false, false, __FUNCTION__) )
				{	// The last value failed to set
					$pass = false;
					break;
				}
			}
		}
		
		if ( !$pass )
		{	// One or more values failed setting
			$this->status = false;
			return NULL;
		}
		
		return true;
	}
	
	/* private */
	function _load_from_request_action ( )
	{	// Loads the action from a request
		
		$this->status = true;
		
		$action = false;
		
		if ( get_param('create_'.$this->name, false) )
		{
			$action = 'create';
		}
		else// if ( ($id = get_param($this->name.'_id', true)) && is_integer($id) )
		{
			// $this->id = $id;
			if ( get_param('update_'.$this->name, false) )
			{
				$action = 'update';
			}
			elseif ( get_param('delete_'.$this->name, false) )
			{
				$action = 'delete';
			}
			elseif ( get_param('load_'.$this->name, false) )
			{
				$action = 'load';
			}
		}
		
		unset($_REQUEST[$action.'_'.$this->name], $_GET[$action.'_'.$this->name], $_POST[$action.'_'.$this->name]);
		
		$this->action = $action;
		
		if ( !$this->action )
		{	// There was no requested actions
			// Below is commented because we want it to be
			// $this->status = NULL; // we want to deconstruct
			return false;
		}
		
		return true;
	}
	
	function _load_from_request_row ( )
	{	// Loads the row from a request
		
		$this->status = true;
		
		$pass = true; // this is set to false if one of the values fails, we check all values
		
		$remove_files = array();
		$move_files = array();
		$upload_files = array();
		
		$row = array();
		
		for ( $i = 0, $n = sizeof($this->table['columns_nicknames']); $i < $n; $i++ )
		{	
			// ==================================
			$column_nickname = $this->table['columns_nicknames'][$i];
			$column = $this->get_column($column_nickname);
			if ( !$this->check_status(true, false, __FUNCTION__) )
			{	// Check the status of the database query, if it failed then kill this function
				$this->status = false;
				return NULL;
			}
			
			// ==================================
			switch ( $column['input'] )
			{
				case 'text':
				case false:
					continue;	// possible hack attempt
					break;
				
				default:
					break;
			}
			
			// ==================================
			$param = $this->name.'_'.$column_nickname;
			$value = get_param($param, NULL, false /* convert */);
			if ( $value === NULL )
			{	// We were not given a value for this field
				continue;
			}
			if ( $column['confirm'] )
			{	// Check if the value was confirmed
				$confirm = get_param($param.'__confirm', NULL, false /* convert */);
				if ( $confirm !== NULL && $confirm !== $value )
				{	// The value and the confirmed value did not match
					$this->Log->add(
						// TYPE
							'Error',
						// TITLE
							'Setting the value for the field ['.$column['title'].'] failed.',
						// DESCRIPTION
							'This occured because the value did and it\'s confirmation value do not match.',
						// DETAILS
							'Value 1: ['.			var_export($value, true)				.']'."\r\n".
							'Value 2: ['.			var_export($confirm, true)				.']'."\r\n".
							'Column: ['.			var_export($column, true)				.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)		."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__,
						// ALSO FRIENDLY
							true
					);
					$pass = false;
					continue; // break out of this cycle
				}
			}
			$value = $this->DB->escape($value, $column); // also applies real_value
			$this->DB->make_valid_value($this->table, $column, $value);
			
			// ==================================
			if ( $value === '' || $value === NULL )
			{	// We have a blank field, so we want to set it to NULL
				// So that way NULL enabled text fields get a NULL value
				// And NULL DISabled text fields which do not have a DEFAULT will fail
				// To enable the value '' the field must have a default, e.g. ''
				// OR specificly set min_length to 0
				if ( $column['null'] )
				{	// If the column supports null then lets set it as null
					$value = NULL; // we don't care about $column['default'], as that is what the fields originally contain
				}
			}
			
			// ==================================
			switch ( $column['input'] )
			{
			
				// ==================================
				case 'timestamp':
				case 'datetime':
				case 'date':
				
					// Check value
					if ( !empty($value) )
						break;
					
					// Use now
					$now = get_param($param.'__now', NULL, true /* convert */);
					if ( $now )
					{	// We want to use now
						$date = date('Y-m-d H:i:s');
						$value = $date;
						break;
					}
					
					// Do date
					$day 	= get_param($param.'__day',		NULL, false /* convert */);
					$month 	= get_param($param.'__month',	NULL, false /* convert */);
					$year	= get_param($param.'__year',		NULL, false /* convert */);
					$date = NULL;
					
					if ( $day !== NULL && $day !== '' && $month !== NULL && $month !== '' && $year !== NULL && $year !== '' )
					{
						$date = $year.'-'.$month.'-'.$day;
					}
					
					// Date check
					if ( $column['input'] == 'date' )
					{	// We have done the date
						$value = $date;
						break;
					}
					// else continue for time
				
				case 'time':
				
					// Check value
					if ( !empty($value) )
						break;
					
					// Use now
					$now = get_param($param.'__now', NULL, true /* convert */);
					if ( $now )
					{	// We want to use now
						$date = date('Y-m-d H:i:s');
						$value = $date;
						break;
					}
						
					// Do the time
					$hour 		= get_param($param.'__hour',		NULL, false /* convert */);
					$minute 	= get_param($param.'__minute',	NULL, false /* convert */);
					$second		= get_param($param.'__second',	NULL, false /* convert */);
					
					if ( $hour !== NULL && $hour !== '' && $minute !== NULL && $minute !== '' && $second !== NULL && $second !== '' )
					{	// We have a custom set time
						$time = $hour.':'.$minute.':'.$second;
						$value = (!empty($date) ? $date : '').$time;
						break;
					}
					
					$value = NULL;
					
					break;
				
				// ==================================
				case 'checkbox':
					if ( $column['multiple'] )
					{
						$value = NULL;
						if ( !isset($values) )
						{
							$this->DB->add_refers_to_values($column);
							$values = $column['values'];
						}
						if ( !empty($values) )
						{	// Multiples for this, so special handling
							$value = '';
							foreach ( $values as $value_value => $value_title )
							{
								$checked = get_param($param.'__'.$value_value, false, true /* convert */);
								if ( $checked )
									$value .= $value_value.'|';
							}
						}
					}
					break;
				
				// ==================================
				case 'filesize':
					$value = filesize_from_human($value);
					break;
				
				#case 'version':
				#	$version_major 		= get_param($param.'__major',	NULL, false /* convert */);
				#	$version_minor 		= get_param($param.'__minor',	NULL, false /* convert */);
				#	$version_revision	= get_param($param.'__revision',	NULL, false /* convert */);
				#	$version_build		= get_param($param.'__build',	NULL, false /* convert */);
				#
				#	$version = $version_major.'.'.$version_minor.'.'.$version_revision.'.'.$version_build;
				#	$_POST[$param] = $version;
				#	break;
					
				// ==================================
				case 'file_upload': 
				case 'file_select':
					// We assume we have import_path and publish_path available
					$current_file = get_param($param.'__current_option', NULL, true /* convert */);
					//
					if ( !empty($current_file) )
					{	// We have a existing file
						if ( $value === $current_file )
						{	// Keep the current file
							continue 2;
						}	else
						{	// Delete the current file
							$remove_files[] = array($column, $current_file);
							$set = $this->set($column_nickname, NULL);
							if ( !$this->check_status(false, false, __FUNCTION__) )
							{	// Something went wrong in the set
								$this->status = false;
								return NULL;
							}
							if ( !$set )
							{	// The value failed to set
								$pass = false;
								continue 2;
							}
							// If all went well continue, as we may have another file to upload
						}
					}
					if ( !empty($value) )
					{
						if ( $column['input'] === 'file_select' )
						{
							if ( $value !== $current_file )
							{	// If we are not the current file, and we have a new file
								$move_files[] = array($column, $value);			// move the new file
							}
						} elseif ( $column['input'] === 'file_upload' )
						{	// we assume publish_path and upload_url are existing
							if ( $value['error'] )
							{	// The file failed to upload
								if ( $value['error'] == 4 )
								{	// No file was uploaded, we don't care
									continue 2;
								}
								// A actual error occured
								$this->Log->add(
									// TYPE
										'Error',
									// TITLE
										'Uploading the file for the field ['.$column['title'].'] failed.',
									// DESCRIPTION
										'',
									// DETAILS
										'Value: ['.				var_export($value, true)				.']'."\r\n".
										'Column: ['.			var_export($column, true)				.']',
									// WHERE
										'Class: '.				get_class_heirachy($this, true)		."\r\n".
										'Filename: '.			basename(__FILE__)						."\r\n".
										'File: '.				__FILE__								."\r\n".
										'Function: '.			__FUNCTION__							."\r\n".
										'Line: '.				__LINE__,
									// ALSO FRIENDLY
										true
								);
								$pass = false;
								continue 2; // break out of this cycle
							}
							// Everything is good so far
							$upload_files[] = array($column, $value);
							$value = $value['name'];
						}
					}
					break;
				
				// ==================================
				default:
					break;
			}
			
			// We have a value for this column
			$set = $this->set($column_nickname, $value);
			if ( !$this->check_status(false, false, __FUNCTION__) )
			{	// Something went wrong in the set
				$this->status = false;
				return NULL;
			}
			if ( !$set )
			{	// The value failed to set
				$pass = false;
				continue;
			}
		}
		
		if ( !empty($this->row['id']) )
		{	// Set the id
			$this->id = $this->row['id'];
		}
		
		if ( !$pass )
		{	// One or more values failed setting
			$this->status = false;
			return NULL;
		}
		
		for ( $i = 0, $n = sizeof($remove_files); $i < $n; $i++ )
		{
			$remove_file = $remove_files[$i];
			$column = $remove_file[0];
			$current_file = $remove_file[1];
			
			$file_path = $column['params']['publish_path'].$current_file;
			$success = !is_file($file_path) || unlink($file_path);
			$this->Log->add(
				// TYPE
					$success ? 'success' : 'error',
				// TITLE
					'Deleting the file for the field ['.$column['title'].'] '.($success ? 'was successful' : 'failed').'.',
				// DESCRIPTION
					'',
				// DETAILS
					'File Path: ['.			var_export($file_path, true)			.']'."\r\n".
					'Column: ['.			var_export($column, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// ALSO FRIENDLY
					true
			);
			if ( !$success )
			{
				$this->status = false;
				return NULL;
			}
		}
		
		for ( $i = 0, $n = sizeof($move_files); $i < $n; $i++ )
		{
			$move_file = $move_files[$i];
			$column = $move_file[0];
			$current_file = $move_file[1];
			
			$old_path = $column['params']['import_path'].$current_file;
			$new_path = $column['params']['publish_path'].$current_file;
			$success = rename($old_path, $new_path);
			$this->Log->add(
				// TYPE
					$success ? 'success' : 'error',
				// TITLE
					'Moving the file for the field ['.$column['title'].'] '.($success ? 'was successful' : 'failed').'.',
				// DESCRIPTION
					'',
				// DETAILS
					'File: ['.				var_export($current_file, true)			.']'."\r\n".
					'Old Path: ['.			var_export($old_path, true)				.']'."\r\n".
					'New Path: ['.			var_export($new_path, true)				.']'."\r\n".
					'Column: ['.			var_export($column, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// ALSO FRIENDLY
					true
			);
			if ( !$success )
			{
				$this->status = false;
				return NULL;
			}
			
			if ( !empty($column['params']['thumbnail']) )
			{	// We want to make a thumbnail
				$image = $new_path;
				$width_new = 0;
				$height_new = 0;
				$resize_mode = 'area';
				$max_filesize = 0;
				$quality = 96;
				
				$thumbnail = compact(
					'image',
					'width_new', 'height_new',
					'resize_mode', 'max_filesize', 'quality',
					'image_type', 'image_extension'
				);
				
				$thumbnail = array_merge($thumbnail, $column['params']['thumbnail']);
				
				$thumbnail = remake_image($thumbnail);
				
				$thumbnail_path = $column['params']['thumbnails_path'].basename($new_path);
				
				$success = file_put_contents($thumbnail_path, $thumbnail);
				
				$this->Log->add(
					// TYPE
						$success ? 'success' : 'error',
					// TITLE
						'Creating the thumbnail for the field ['.$column['title'].'] '.($success ? 'was successful' : 'failed').'.',
					// DESCRIPTION
						'',
					// DETAILS
						'File: ['.				var_export($current_file, true)			.']'."\r\n".
						'Base Image Path: ['.	var_export($new_path, true)				.']'."\r\n".
						'Thumbnail Path: ['.	var_export($thumbnail_path, true)		.']'."\r\n".
						'Column: ['.			var_export($column, true)				.']',
					// WHERE
						'Class: '.				get_class_heirachy($this, true)		."\r\n".
						'Filename: '.			basename(__FILE__)						."\r\n".
						'File: '.				__FILE__								."\r\n".
						'Function: '.			__FUNCTION__							."\r\n".
						'Line: '.				__LINE__,
					// ALSO FRIENDLY
						true
				);
				if ( !$success )
				{
					$this->status = false;
					return NULL;
				}
			}
		}
		
		for ( $i = 0, $n = sizeof($upload_files); $i < $n; $i++ )
		{
			$upload_file = $upload_files[$i];
			$column = $upload_file[0];
			$current_file = $upload_file[1];
			
			$old_path = $current_file['tmp_name'];
			$new_path = $column['params']['publish_path'].$current_file['name'];
			$success = move_uploaded_file($old_path, $new_path);
			$this->Log->add(
				// TYPE
					$success ? 'success' : 'error',
				// TITLE
					'Uploading the file for the field ['.$column['title'].'] '.($success ? 'was successful' : 'failed').'.',
				// DESCRIPTION
					'',
				// DETAILS
					'File: ['.				var_export($current_file, true)			.']'."\r\n".
					'Old Path: ['.			var_export($old_path, true)				.']'."\r\n".
					'New Path: ['.			var_export($new_path, true)				.']'."\r\n".
					'Column: ['.			var_export($column, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// ALSO FRIENDLY
					true
			);
			if ( !$success )
			{
				$this->status = false;
				return NULL;
			}
			
			if ( !empty($column['params']['thumbnail']) )
			{	// We want to make a thumbnail
				$image = $new_path;
				$width_new = 0;
				$height_new = 0;
				$resize_mode = 'area';
				$max_filesize = 0;
				$quality = 96;
				
				$thumbnail = compact(
					'image',
					'width_new', 'height_new',
					'resize_mode', 'max_filesize', 'quality',
					'image_type', 'image_extension'
				);
				
				$thumbnail = array_merge($thumbnail, $column['params']['thumbnail']);
				
				$thumbnail = remake_image($thumbnail);
				
				$thumbnail_path = $column['params']['thumbnails_path'].basename($new_path);
				
				$success = file_put_contents($thumbnail_path, $thumbnail);
				
				$this->Log->add(
					// TYPE
						$success ? 'success' : 'error',
					// TITLE
						'Creating the thumbnail for the field ['.$column['title'].'] '.($success ? 'was successful' : 'failed').'.',
					// DESCRIPTION
						'',
					// DETAILS
						'File: ['.				var_export($current_file, true)			.']'."\r\n".
						'Base Image Path: ['.	var_export($new_path, true)				.']'."\r\n".
						'Thumbnail Path: ['.	var_export($thumbnail_path, true)		.']'."\r\n".
						'Column: ['.			var_export($column, true)				.']',
					// WHERE
						'Class: '.				get_class_heirachy($this, true)		."\r\n".
						'Filename: '.			basename(__FILE__)						."\r\n".
						'File: '.				__FILE__								."\r\n".
						'Function: '.			__FUNCTION__							."\r\n".
						'Line: '.				__LINE__,
					// ALSO FRIENDLY
						true
				);
				if ( !$success )
				{
					$this->status = false;
					return NULL;
				}
			}
		}
		
		if ( !$pass )
		{	// One or more values failed setting
			$this->status = false;
			return NULL;
		}
				
		return true;
	}
		
	/* private */
	function _load_from_request ( )
	{	// If there is a requested action, then get the requested row
		return ( $this->_load_from_request_action() && $this->_load_from_request_row() );
	}
	
// ==============================================

/* public */
function update ( $add_log_on_success = true )
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
	$this->action = 'update';
	
	# Check requirements
	/* removed because; if the value is already inserted it should have the required values, if they are trying to set something to null or whatever the fail will occur on the set.
	$has_requirements = $this->has_requirements();
	if ( !$this->check_status(false, false, __FUNCTION__) || !$has_requirements )
	{	// Did not pass the requirements
		return false;
	}
	*/
	
	# Perform the query
	$this->DB->update(
		// TABLE
		$this->table,
		// UPDATE INFO
		$this->row,
		// WHERE
		array(
			array('id', $this->id)
		),
		// LIMIT
		1
	);
	
	# Check the DB status
	$status = $this->check_status(true, false, __FUNCTION__);
	$this->Log->add(
		// TYPE
			($status ? 'success' : 'error'),
		// TITLE
			'Updating a '.$this->title.' was '.($status ? 'successful' : 'unsuccessful').'.',
		// DESCRIPTION
			array(
				'',
				'The '.$this->title.' has the '.(isset($this->row['title']) ? 'title of '.$this->row['title'] : 'id of '.$this->id).'.'
			),
		// DETAILS
			'Row Used: ['.			var_export($this->row, true)			.']',
		// WHERE
			'Class: '.				get_class_heirachy($this, true)		."\r\n".
			'Filename: '.			basename(__FILE__)						."\r\n".
			'File: '.				__FILE__								."\r\n".
			'Function: '.			__FUNCTION__							."\r\n".
			'Line: '.				__LINE__,
		// FRIENDLY
			(!$status || $add_log_on_success)
	);
	if ( !$status )
		return NULL;
	
	# Reload
	$this->load();
	
	# Return
	return true;
}
	
/* public */
function create ( $add_log_on_success = true )
{	
	$this->status = true;
	
	# Set action
	$this->action = 'create';
	
	# Check requirements
	$has_requirements = $this->has_requirements();
	if ( !$this->check_status(false, false, __FUNCTION__) || !$has_requirements )
	{	// Did not pass the requirements
		if ( !is_null($this->status) )
			$this->status = false;
		return false;
	}
	
	# Perform the query
	$this->id = $this->row['id'] = $this->DB->insert(
		// TABLE
		$this->table,
		// ROW
		$this->row
	);
			
	# Check the DB status
	$status = $this->check_status(true, false, __FUNCTION__);
	$this->Log->add(
		// TYPE
			($status ? 'success' : 'error'),
		// TITLE
			'Creating a '.$this->title.' was '.($status ? 'successful' : 'unsuccessful').'.',
		// DESCRIPTION
			'',
		// DETAILS
			'Row Used: ['.			var_export($this->row, true)			.']',
		// WHERE
			'Class: '.				get_class_heirachy($this, true)		."\r\n".
			'Filename: '.			basename(__FILE__)						."\r\n".
			'File: '.				__FILE__								."\r\n".
			'Function: '.			__FUNCTION__							."\r\n".
			'Line: '.				__LINE__,
		// FRIENDLY
			(!$status || $add_log_on_success)
	);
	if ( !$status )
		return NULL;
	
	# Reload
	$this->load();
	
	# Return
	return true;
}
	
	// ===========================
	
	/* public */
	function delete ( $add_log_on_success = true )
	{
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Check that we are ready to delete
		if ( empty($this->id) )
		{
			$this->status = false;
			return false;
		}
		
		# Set action
		$this->action = 'delete';
		
		# Remove Files
		// Cycle through the columns to find out if we had any files
		for ( $i = 0, $n = $this->table['columns_size']; $i < $n; $i++ )
		{
			$column_nickname = $this->table['columns_nicknames'][$i];
			$column = $this->table['columns'][$column_nickname];
			
			switch ( $column['input'] )
			{
				case 'file-upload':
				case 'file-select':
					break;
				
				default:
					continue 2;
					break;
			}
			
			// We have  a file
			$file_path = $column['params']['publish_path'].$this->get($column_nickname);
			if ( is_file($file_path) )
			{	// The file exists
				if ( !unlink($file_path) )
				{	// The file failed to delete
					$this->Log->add(
						// TYPE
							'Error',
						// TITLE
							'Deleting the file for the field ['.$column['title'].'] failed.',
						// DESCRIPTION
							'',
						// DETAILS
							'File Path: ['.			var_export($file_path, true)			.']'."\r\n".
							'Column: ['.			var_export($column, true)				.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)		."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__,
						// ALSO FRIENDLY
							true
					);
					$this->status = false;
					return NULL;
				}
			}
		}
		
		# Perform the query
		$this->DB->delete(
			// TABLE
			$this->table,
			// WHERE
			array(
				array('id', $this->id)
			),
			// LIMIT
			1
		);
		
		# Check the DB status
		$status = $this->check_status(true, false, __FUNCTION__);
		$this->Log->add(
			// TYPE
				($status ? 'success' : 'error'),
			// TITLE
				'Deleting a '.$this->title.' was '.($status ? 'successful' : 'unsuccessful').'.',
			// DESCRIPTION
				array(
					'',
					'The '.$this->title.' has the '.(isset($this->row['title']) ? 'title of '.$this->row['title'] : 'id of '.$this->id).'.'
				),
			// DETAILS
				'Row Used: ['.			var_export($this->row, true)			.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// FRIENDLY
				(!$status || $add_log_on_success)

		);
		if ( !$status )
			return NULL;
		
		# Deconstruct
		$this->_deconstruct();
		
		# Return
		return true;
	}
	
	/* public */
	function load_from_unique ( $column_nickname, $value )
	{
		// if ( !is_null($this->status) )
			$this->status = true;
		
		$column = $this->get_column($column_nickname, 'DataObject Class\'s Load From Unique Function', 'Used to load the object from a unique value.');
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
		{	// Getting the column failed
			$this->status = false;
			return NULL;
		}
		
		$row = $this->DB->search(
			// TABLE
			$this->table,
			// COLUMNS
			'id',
			// WHERE
			array(
				array($column, $value)
			),
			// LIMIT
			1
		);
		
		$this->id = $row;
		
		return $this->load();
	}
	
	/* public */
	function load ( $add_log_on_success = false, $only_unset = false )
	{	// We get a row based on $this->row['id']
	
		// if ( !is_null($this->status) )
			$this->status = true;
		
		if ( empty($this->id) )
		{
			if ( empty($this->row['id']) )
			{	// Nothing to load
				$this->_deconstruct();
				return NULL;
			}
			$this->id = $this->row['id'];
		}
		
		// Get columns
		if ( $only_unset === false )
		{	// All columns
			$columns = '*';
		}
		else
		{	// Only unset columns
			$columns_all = $this->table['columns_nicknames'];
			$columns_set = array_keys($this->row);
			$columns_unset = array_diff($columns_all, $columns_set);
			$columns = array_values($columns_unset);
			unset($columns_all, $columns_set, $columns_unset);
			$columns[] = 'id';
		}
		
		// Get the row
		$row = $this->DB->search(
			// TABLE
			$this->table,
			// COLUMNS
			$columns,
			// WHERE
			array(
				array('id',	$this->id)
			),
			// LIMIT
			1
		);
		
		// Set the data
		if ( $only_unset === false )
		{	// All columns
			$this->row = $row;
		}
		else
		{	// Only unset columns
			$this->row = array_merge($this->row, $row);
		}
		
		$status = $this->check_status(true, false, __FUNCTION__);
		if ( $status )
			$status = !empty($row);
		$this->Log->add(
			// TYPE
				($status ? 'success' : 'error'),
			// TITLE
				'Loading a '.$this->title.' was '.($status ? 'successful' : 'unsuccessful').'.',
			// DESCRIPTION
				array(
					'',
					'The '.$this->title.' has the '.(isset($this->row['title']) ? 'title of '.$this->row['title'] : 'id of '.$this->id).'.'
				),
			// DETAILS
				'Only Unset:   ['.			var_export($only_unset, true)			.']'."\r\n".
				'Row Returned: ['.			var_export($row, true)					.']'."\r\n".
				'Final Row: ['.				var_export($this->row, true)			.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// FRIENDLY
				(!$status || $add_log_on_success)
		);
		if ( !$status )
		{
			$this->_deconstruct();
			return NULL;
		}
		
		$this->id = $this->row['id'];
		
		// Return
		return true;
	}
	
	// ==============================================
	
	/* public */
	function has_requirements ( )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		
		$current_columns = array_keys($this->row);
		$current_required_columns = array_intersect($current_columns, $this->required_columns);
		$current_required_columns = array_values($current_required_columns);
		$rc_size = sizeof($this->required_columns);
		$crc_size = sizeof($current_required_columns);
		if ( $rc_size != $crc_size )
		{	// We are missing some required columns
			// Lets find out what columns we are missing
			$missing_required_columns = array_diff($this->required_columns, $current_required_columns);
			$missing_required_columns = array_values($missing_required_columns);
			$mrc_size = sizeof($missing_required_columns);
			for ( $i = 0, $n = $mrc_size; $i < $n; $i++ )
			{	// Lets add a log about the missing required column
				$column_nickname = $missing_required_columns[$i];
				$column = $this->get_column($column_nickname);
				if ( !$this->check_status(true, false, __FUNCTION__) )
				{	// A database error occured
					$this->status = false;
					return NULL;
				}
				$this->Log->add(
					// TYPE
						'Error',
					// TITLE
						'A required value for the field ['.$column['title'].'] for a ['.$this->title.'] could not be found.',
					// DESCRIPTION
						'',
					// DETAILS
						'Column: ['.			var_export($column, true)								.']',
					// WHERE
						'Class: '.				get_class_heirachy($this, true)						."\r\n".
						'Filename: '.			basename(__FILE__)										."\r\n".
						'File: '.				__FILE__												."\r\n".
						'Function: '.			__FUNCTION__											."\r\n".
						'Line: '.				__LINE__,
					// FRIENDLY
						true
				);
			}
			// $this->status = false;
			return false;
		}
		
		return true;
	}
	
	/* public */
	function set_column_param ( $param, $column, $value )
	{
		return $this->DB->set_param($param, $column, $value);
	}
	
	/* public */
	function set_table_param ( $param, $value )
	{
		return $this->DB->set_param($param, $value);
	}
	
	/* public */
	function set_param ( $param, $column, $value = NULL )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		return $this->DB->set_param($param, $this->table, $column, $value);
	}
	
	/* public */
	/*function unset ( $column_nickname )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		$column = $this->get_column($column_nickname, 'DataObject Class\'s Set Function', 'Used to set a value inside a row.');
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
		{	// Getting the column failed
			$this->status = false;
			return NULL;
		}
		
		unset($row[$column['nickname']]);
		
		return true;
	}*/
	
	/* public */
	function set ( $column_nickname, $value, $escape = false )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		$column = $this->get_column($column_nickname, 'DataObject Class\'s Set Function', 'Used to set a value inside a row.');
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
		{	// Getting the column failed
			$this->status = false;
			return NULL;
		}
		
		switch ( $column['input'] )
		{
			case 'checkbox':
				if ( $column['multiple'] )
				{	// Multiple values for this
					if ( is_array($value) )
						$value = implode('|', $value);
				}
				break;
			
			default:
				break;
		}
		
		if ( $escape )
		{	// Escape the value
			$value = $this->DB->escape($value);
		}
		
		if ( $this->action == 'update' || $this->action == 'create' )
			$id = $this->id;
		else
			$id = false;
		
		$this->DB->make_valid_value($this->table, $column, $value);
		$check = $this->DB->check_all($this->table, $column, $value, $id, true, 'DataObject Class\'s Set Function', 'Used to set a value inside a row.');
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column['nickname'],true).']'
		);	if ( !$status )
		{	// Something failed within the database checks
			$this->status = false;
			return NULL;
		}
		
		$this->row[$column['nickname']] = $value;
		
		if ( !$check )
		{	// The value failed the checks
			// $this->status = false;
			return false;
		}
		
		return true;
	}
	
	/* public */
	function get ( $column_nickname, $display_mode = false, $default = NULL, $to_human = true )
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
		
		if ( !empty($this->row) && array_key_exists($column_nickname, $this->row) )
			$value = $this->row[$column_nickname];
		else
			$value = $this->table['columns'][$column_nickname]['default'];
		
		switch ( $column['input'] )
		{
			case 'checkbox':
				if ( $value !== NULL && $column['multiple'] )
				{	// Multiple values for this
					$value = explode('|', $value);
				}
				break;
			
			default:
				break;
		}
		
		// We have a value
		if ( !$display_mode )
		{	// We are just retrieving the value
			return $value;
		}
		
		// Format to output
		$value = $this->format_to_output($column, $value, $display_mode, $default, $to_human);
		
		// Return
		return $value;
	}
	
	/* public */
	function format_to_output ( $column, $value, $display_mode, $default = NULL, $to_human = true )
	{
		// Do advanced shite
		if ( is_array($value) )
		{	// We need to do this differently
			$values = $value;
			unset($value);
			for ( $i = 0, $n = sizeof($values); $i < $n; ++$i )
			{
				$value =& $values[$i];
				$value = $this->format_to_output($column, $value, $display_mode, $default, $to_human);
				unset($value);
			}
			$value = implode(', ', $values);
		}
		
		// We are doing some display
		if ( $to_human )
		{
			if ( $column['input'] === 'filesize' )
			{
				$value = filesize_to_human($value);
			}
			else
			if ( !empty($value) && $column['datatype'] == 'datetime' )
			{
				$time = strtotime($value);
				if ( $time > 0 )
				{	// If the time was valid
					$value = '';
					switch($column['mysql_datatype'])
					{
						case 'datetime':
						case 'timestamp':
						case 'date':
							$value .= date('d/m/Y', $time);
							if ( $column['mysql_datatype'] == 'date' )
								break;
							else
								$value .= ' @ ';
						
						case 'time':
							$value .= date('h:i:s a', $time);
							break;
					}
				}
			} else
				$value = $this->DB->get_value_title($column, $value); // will get the value title if appropriate, or the value if there are no values	
				
			if ( $value === NULL )
				if ( $default === NULL )
					$value = $column['null_title'];
						
		}
		else
		{
			if ( $value === NULL )
				if ( $default === NULL )
					$value = 'NULL';
		}
		
		// Blah
		if ( $value === NULL )
			$value = $default;
		
		// Format to output
		$value = format_to_output($value, $display_mode);
		
		// Return 
		return $value;
	}
	
	/* private */
	function _get_action ( $action )
	{
		switch ( $action )
		{
			case 'edit':
			case 'update':
				$action = 'update';
				break;
			
			case 'add':
			case 'create':
				$action = 'create';
				break;
			
			case 'delete':
				$action = 'delete';
				break;
			
			default:
				$action = NULL;
				break;
		}
		return $action;
	}
	
	// ===========================
	
	function get_dirsize ( $column, $human = true, $default = NULL )
	{
		$dir_path = $this->get_file_path($column);
		if ( !$dir_path )
			return $default;
		$dir_path = dirname($dir_path);
		$dirsize = dirsize($dir_path);
		if ( $human )
			$dirsize = filesize_to_human($dirsize);
		return $dirsize;
	}
	
	function get_filesize ( $column, $human = true, $default = NULL  )
	{
		$file_path = $this->get_file_path($column);
		if ( !$file_path )
			return $default;
		$filesize = filesize($file_path);
		if ( $human )
			$filesize = filesize_to_human($filesize);
		return $filesize;
	}
	
	function get_file_url ( $column, $container = 'publish' )
	{
		$column = $this->get_column($column);
		if ( ($value = $this->get($column['nickname'])) && ($file_url = $column['params'][$container.'_url']) )
			return $file_url.$value;
		return NULL;
	}
	
	function get_file_path ( $column, $container = 'publish' )
	{
		$column = $this->get_column($column);
		if ( ($value = $this->get($column['nickname'])) && ($file_path = $column['params'][$container.'_path']) )
			return $file_path.$value;
		return NULL;
	}
	
	function get_url_params ( $action = 'load', $id = false )
	{
		$url_params = '';
		
		if ( !empty($action) )
			$url_params .= $action.'_'.$this->name.'=true&amp;';
		
		if ( !is_null($id) )
		{
			if ( empty($id) )
				$id = $this->id;
			$url_params .= $this->name.'_id='.$id.'&amp;';
		}
		
		return $url_params;
	}
	
	function get_table_title ( )
	{
		return $this->DB->get_table_title($this->table);
	}
	
	function get_column_title ( $column )
	{
		return $this->DB->get_column_title($this->table, $column);
	}
	
	function & get_column ( $column, $function_name = 'DataObject\'s Get Column Function', $function_description = '' )
	{
		$column = & $this->DB->get_column($this->table, $column, $function_name, $function_description);
		return $column;
	}
	
	// ===========================
	
	/* public */
	function display ( $display = 'view' )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		
		switch ( $display )
		{
			case 'update':
			case 'create':
			case 'delete':
				$action = $this->_get_action($display);
				?><form action="<?php echo selfURL(); ?>" method="post" enctype="multipart/form-data" ><?php
				?><input type="hidden" name="<?php echo $action.'_'.$this->name; ?>" value="true" /><?php
				break;
			
			default:
				$action = $display;
				break;
		}
		
		switch ( $action )
		{
			case 'update':
				# Check the construction status
				if ( empty($this->row) )
					return NULL;
			
			case 'create':
				$columns_nicknames = $this->table['columns_nicknames'];
				for ( $i = 0, $n = sizeof($columns_nicknames); $i < $n; $i++ )
				{
					$column_nickname = $columns_nicknames[$i];
					$this->display_field($column_nickname, $action);
				}
				break;
			
			case 'delete':
				# Check the construction status
				$status = $this->check_status(false, true, __FUNCTION__, __CLASS__,
					'Display: ['.var_export($display,true).']'
				);	if ( !$status )
					return NULL;
				
				?><input type="hidden" name="<?php echo $this->name.'_id'; ?>" value="<?php echo $this->get('id', 'htmlattr'); ?>" /><?php
				$this->display('view');
				
				break;
			
			case 'view':
				# Check the construction status
				$status = $this->check_status(false, true, __FUNCTION__, __CLASS__,
					'Display: ['.var_export($display,true).']'
				);	if ( !$status )
					return NULL;
				
				echo '<pre>'.var_export($this->row, true).'</pre>';
				break;
			
			default:
				# We don't know what we are meant to do
				echo '<p>Unknown display: '.$display.'</p>';
				break;
				
		}
		
		switch ( $action )
		{
			case 'update':
			case 'create':
				$action = 'save'; // fucking ers
			case 'delete':
				?><br /><input type="submit" value="<?php echo ucfirst($action); ?>" name="<?php echo $action; ?>" title="<?php echo ucfirst($action); ?>" /></form><?php
				break;
			
			default:
				break;
		}
		
		return true;	
	}
	
	function display_field ( $column_nickname, $action = 'update', $confirm_value = false )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		
		$column = & $this->get_column($column_nickname);
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
			return NULL;
		
		if ( $action == 'create' && $column['input'] == 'text' )
			return false;

		if ( $column['input'] == 'hidden' )
		{
			$this->display_input($column, $action, $confirm_value);
			return true;
		} elseif ( !$column['input'] )
			return true;
		
		$field_name = $this->name.'_'.$column['nickname'];
		
		?><div style="padding-top:8px;" id="<?php echo $field_name.'__field'; ?>" >
			<div style="float:left; width:17%;" id="<?php echo $field_name.'__title'; ?>" >
				<?php $this->display_title($column, $action, $confirm_value); ?>
				<br />
				<span style="color:#CCC; font-size:smaller;" id="<?php echo $field_name.'__description'; ?>">
					<?php $this->display_description($column, $action, $confirm_value); ?>
				</span>
			</div>
			<div style="float:left; width:83%" id="<?php echo $field_name.'__input'; ?>" >
				<?php $this->display_input($column, $action, $confirm_value); ?>
			</div>
			<div style="clear:both;" id="<?php echo $field_name.'__clear'; ?>" ></div>
		</div><?php
		
		if ( !$confirm_value && $column['confirm'] )
			$this->display_field($column, $action, true);
		
		return true;
	}
	
	function display_title ( $column_nickname, $action, $confirm_value = false )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		
		$column = & $this->get_column($column_nickname);
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
			return NULL;
		
		$input = $column['input'];
		if ( !$input || $input == 'hidden' )
		{	// We don't want to display
			return true;
		}
		
		$field_name = $this->name.'_'.$column['nickname'];
		$field_title = $column['title'];
		
		if ( $confirm_value )
		{
			$field_name .= '__confirm';
			$field_title = 'Confirm '.$field_title;
		}
				
		?><label for="<?php echo $field_name; ?>"><?php echo format_to_output($field_title, 'htmlbody'); ?>: </label><?php
		
		return true;
	}
	
	function display_description ( $column_nickname, $action, $confirm_value = false )
	{
		if ( !is_null($this->status) )
			$this->status = true;
		
		$column = & $this->get_column($column_nickname);
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
			return NULL;
		
		$input = $column['input'];
		if ( !$input || $input == 'hidden' )
		{	// We don't want to display
			return true;
		}
		
		if ( $confirm_value )
		{
			echo 'Same as above.';
			return true;
		}
				
		if ( $column['input'] != 'text' )
		{
			if ( $column['required'] )
				echo '<strong>Required.&nbsp;&nbsp;</strong>';
			switch ( $column['datatype'] )
			{
				case 'integer':
				case 'double':
					if ( $column['input'] != 'dropdown' )
						echo '<strong>Numeric.&nbsp;&nbsp;</strong>';
					break;
				
				case 'datetime':
					echo '<strong>(';
					switch ( $column['mysql_datatype'] )
					{
						case 'timestamp':
						case 'datetime':
						case 'date':
							echo 'DD/MM/YYYY';
							if ( $column['mysql_datatype'] == 'date' )
								break;
							else
								echo '&nbsp;-&nbsp;';
							
						case 'time':
							if ( $column['mysql_datatype'] != 'time' )
								echo ' ';
							echo 'HH:mm:ss';
							break;
					}
					echo ').&nbsp;&nbsp;</strong>';
					break;
					
			}
			if ( $column['refers_to'] )
			{
				$r_column = $this->DB->get_column($column['refers_to']['table'], $column['refers_to']['values_column']);
				echo '<strong>Refers to '.
					( DEBUG
					?	$column['refers_to']['table'].'.'.$column['refers_to']['values_column']
					:	'an existing '.$r_column['title']
					).
					'.&nbsp;&nbsp;</strong>';
			}
		}
		if ( !empty($column['description']) )
			echo str_replace("\n", "\n".'<br />', $column['description']);
		
		return true;
	}
	
	function display_input ( $column_nickname, $action, $confirm_value = false )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		
		$column = & $this->get_column($column_nickname);
		$status = $this->check_status(true, false, __FUNCTION__, __CLASS__,
			'Column Nickname: ['.var_export($column_nickname,true).']'
		);	if ( !$status )
			return NULL;
		unset($column_nickname);
		
		$input = $column['input'];
		if ( !$input )
		{	// We don't want to display
			return true;
		}
		
		if ( array_key_exists($column['nickname'], $this->row) )
			$row = $this->row;
		else
			$row = $this->default_row;
		
		$field_name = $this->name.'_'.$column['nickname'];
		if ( $confirm_value )
			$field_name .= '__confirm';
		
		//
		$function = 'display_input__'.$column['nickname'];
		if ( is_callable(array($this, $function)) )
			return $this->$function($column, $field_name, $action);	
				
		//
		$function = 'display_input__'.$input;
		if ( is_callable(array($this, $function)) )
			return $this->$function($column, $field_name, $action);
		
		//
		echo 'Unknown input type: ['.$input.']';
		return NULL;
	}
	
	// ==============================================
	
	function check_status ( $check_db = false, $check_construction = true, $function_name = 'check_status', $class = __CLASS__, $extra_details = '' )
	{
		// Continue
		if ( /* (!$check_db && !$check_construction &&*/ $this->status === false /* )*/ || ($check_db && !$this->DB->status) || ($check_construction && $this->status === NULL) )
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					is_null($this->status)
					?	'The Status of '.$this->title.' Class is currently NULL. This means at one point this class was deconstructed.'
					:	'The Status of '.$this->title.' Class is currently FALSE, This means that an error has occured at some point.'
					,
				// DESCRIPTION
					is_null($this->status)
					?	(	
							is_null($this->action)
							?	'The Deconstruction occured because the Class was never Constructed. '."\r\n".
								'This usually happens because $perform_action in the constructor was set to false.'
							:	(
									$this->action == 'delete'
									?	'The Deconstruction occured because the Delete action was performed.'
									:	'The Deconstruction occured because the Construction of the Class Failed.'
								)
						)
					:	'An error occured at some point, you can use the details below to figure this out, or look at other log entries.'
					,
				// DETAILS
					'Row: ['.			var_export($this->row, true)			.']'."\r\n".
					'Action: ['.		var_export($this->action, true)			.']'."\r\n".
					'Caller: ['.		get_class_heirachy($class, true).'->'.$function_name	.']'.(!empty($extra_details) ? "\r\n".$extra_details : ''),
				// WHERE
					'Class: '.			get_class_heirachy($this, true)			."\r\n".
					'Filename: '.		basename(__FILE__)						."\r\n".
					'File: '.			__FILE__								."\r\n".
					'Function: '.		__FUNCTION__							."\r\n".
					'Line: '.			__LINE__
			);
			// if ( $check_db && $this->status !== NULL )
			// 	$this->status = false;
			return false;
		}
		
		return true;
	}
	
	// ==============================================
	
	function display_input__hidden ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display hidden field
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		echo '<input type="hidden" name="'.$field_name.'" id="'.$field_name.'__hidden" value="'.$this->format_to_output($column, $value, 'htmlattr', '', false).'" />';
		return true;
	}
	
	function display_input__checkbox ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a textarea
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		# We have multiple values
		$this->display_input__hidden($column, $field_name, $action, false, false, $params);
		
		# Check if we are manual
		if ( $value !== NULL && $get_value === false )
		{	// Manual
			if ( !isset($checked) )
				$checked = real_value($value);
			echo '<input type="checkbox" '.
				'name="'.$field_name.'" id="'.$field_name.'" '.
				'value="true" '.
				( $checked ? 'checked="checked" ' : ' ' ).
				'/>';
			return true;
		}
		
		# Check if we have a single value
		if ( !$column['multiple'] )
		{	//
			$this->display_input__checkbox($column, $field_name, $action, $value, false, $params);
			return true;
		}
		
		//
		if ( !isset($values) )
		{	// No manual override for values has been set
			$this->DB->add_refers_to_values($column);
			$values = $column['values'];
		}
		
		//
		if ( $value === NULL )
		{	// There is no value, so use all values
			$value = array_keys($values); // all by default
		}
		
		//
		if ( empty($value) ) $value = array();
		
		// Display checkboxes for each value
		foreach ( $values as $option_value => $option_title )
		{	// Cycle through values
			$checked = in_array($option_value, $value);
			$style = $checked ? 'font-weight:bold;' : '';
			$option_name = $field_name.'__'.$option_value;
			
			if ( !empty($option_title) )
				echo '<label for="'.$option_name.'" style="'.$style.'" >';
			
			$params['checked'] = $checked;
			$this->display_input__checkbox($column, $option_name, $action, true, false, $params);
			
			if ( !empty($option_title) )
				echo $option_title.'&nbsp;</label> ';
		}
		
		// Return
		return true;
	}
	
	function display_input__text ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display hidden field plus text
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		$this->display_input__hidden($column, $field_name, $action, $value, false, $params);
		echo $this->format_to_output($column, $value, 'htmlbody', NULL, true);
		return true;
	}
	
	function display_input__version ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display version
		// NOT YET FINISHED, Display thingo
		return $this->display_input__textbox($column, $field_name, $action, $value, $get_value, $params);
		
		/*
			$column_major = 
			$column_minor = 
			$column_revision = 
			$column_build =
				$column;
			
			$column_major['input']	=
			$column_minor['input']	=
			$column_revision['input']	=
			$column_build['input']	=
				'textbox';
			
			$column_major['max_length']	=
			$column_minor['max_length']	=
			$column_revision['max_length']	=
			$column_build['max_length']	=
				3;
			
			$column_major['nickname']		= $column['nickname'].'__major';
			$column_minor['nickname']		= $column['nickname'].'__minor';
			$column_revision['nickname']	= $column['nickname'].'__revision';
			$column_build['nickname']		= $column['nickname'].'__build';
			
			echo 'v&nbsp;';
			$this->display_input($column_major, 	$action, false);
			echo '&nbsp;.&nbsp;';
			$this->display_input($column_minor, 	$action, false);
			echo '&nbsp;.&nbsp;';
			$this->display_input($column_revision,	$action, false);
			echo '&nbsp;.&nbsp;';
			$this->display_input($column_build, 	$action, false);
		*/
	}
	
	function display_input__filesize ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Alias for textbox
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		$value = filesize_to_human($value);
		
		return $this->display_input__textbox($column, $field_name, $action, $value, false, $params);
	}
	
	function display_input__year ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Alias for textbox
		return $this->display_input__textbox($column, $field_name, $action, $value, $get_value, $params);
	}
	
	function display_input__textbox ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display hidden field plus text
		if ( $get_value )	$value = $this->get($column);
		
		if ( $column['max_length'] !== NULL )
		{
			$max_length = $column['max_length'];
			if ( $column['max_length'] <= 40 )
				$size = $max_length;
			else
				$size = NULL;
		}
		else
		{
			$max_length = $size = NULL;
		}
		
		$input_type = 'text';
		
		extract($params);
		
		echo '<input type="'.$input_type.'" '.
			'name="'.$field_name.'" id="'.$field_name.'" '.
			'value="'.$this->format_to_output($column, $value, 'htmlattr', '', false).'" '.
			( $max_length !== NULL ? 'maxlength="'.to_string($max_length).'" ' : ' ' ).
			( $size !== NULL ? 'size="'.to_string($size).'" ' : 'style="width:100%;" ' ).
			'/>';
		
		return true;
	}
	
	function display_input__textarea ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a textarea
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		echo '<textarea '.
			'name="'.$field_name.'" id="'.$field_name.'" '.
			'style="width:100%;" rows="7" '.
			'>'.
			$this->format_to_output($column, $value, 'htmlbody', '', false).
		'</textarea>';
		return true;
	}
	
	function display_input__password ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a textarea
		$params['input_type'] = 'password';
		return $this->display_input__textbox($column, $field_name, $action, $value, $get_value, $params);
	}
	
	function display_input__file_select ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Displays a dropdown full of files
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		// We assume, import_path, and publish_path exist
		if ( !isset($import_path) ) $import_path = $column['params']['import_path'];
		
		// Check that the dir exists
		if ( !is_dir($import_path) )
		{	// Error
			echo 'The import path does not exist: ['.$import_path.']';
			return NULL;
		}
		
		// Get the files/values
		if ( empty($regex) )
		{	// No regex specified
			if ( !empty($column['regex']) )
				$regex = $column['regex'];
			else
				$regex = 'files';
		}
		$scan_dir = scan_dir($import_path, $regex);
		if ( empty($scan_dir) )
		{	// No Files
			$values = array();
		}
		else
		{	// Has Files
			$values = array_combine($scan_dir, $scan_dir);
		}
		
		$params['values'] = $values;
			
		// Dropdown
		$result = $this->display_input__dropdown($column, $field_name,  $action, $value, false, $params);
		
		// Display link
		if ( $value )
		echo
			'<br />View the current file: <a href="'.$column['params']['publish_url'].$this->format_to_output($column, $value, 'htmlattr', '', false).'" title="View this file">'.
				$this->format_to_output($column, $value, 'htmlbody', '', true).
			'</a>';
			
		// Return
		return $result;
	}
	
	function display_input__directory_select ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a dropdown full of directories
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		// We assume, import_path, and publish_path exist
		if ( !isset($import_path) ) $import_path = $column['params']['import_path'];
		
		// Check that the dir exists
		if ( !is_dir($import_path) )
		{	// Error
			echo 'The import path does not exist: ['.$import_path.']';
			return NULL;
		}
		
		// Get the files/values
		if ( empty($regex) )
		{	// No regex specified
			if ( !empty($column['regex']) )
				$regex = $column['regex'];
			else
				$regex = 'directories';
		}
		$scan_dir = scan_dir($import_path, $regex);
		$values = array_combine($scan_dir, $scan_dir);
		$params['values'] = $values;
		
		
		// Dropdown
		$result = $this->display_input__dropdown($column, $field_name,  $action, $value, false, $params);
		
		// Display link
		if ( $value )
		echo
			'<br />View the current dir: <a href="'.$column['params']['publish_url'].$this->format_to_output($column, $value, 'htmlattr', '', false).'" title="View this dir">'.
				$this->format_to_output($column, $value, 'htmlbody', '', true).
			'</a>';
			
		// Return
		return $result;
	}
	
	function display_input__dropdown ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a dropdown
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		// Get values
		if ( !isset($values) )
		{	// If we haven't got the values yet, get them
			$this->DB->add_refers_to_values($column);
			$values = $column['values'];
		}
		if ( empty($values) )
			$values = array();
		
		// Current option
		$this->display_input__hidden ( $column, $field_name.'__current_option', $action, $value, false, $params);
		
		// Get options
		$size = sizeof($values);
		
		/*
		// Default
		if ( $action == 'create' && $size !== 0 && empty($field_value) )
		{	// There is no value, so let's use the first real value
			$value = $keys[0];
		}
		*/
		

		// Current
		if ( !empty($value) && !array_key_exists($value, $values) )
		{	// Value doesn't exist
			$values_prepend = array(
				$value	=>	'Current: '.$this->format_to_output($column, $value, 'htmlbody', '', false),
				''		=>	'---',
			);
			$values = $values_prepend + $values;
			unset($values_prepend);
		}
		
		// Nulls
		if ( $column['null'] /* && !$column['required'] */ )
		{	// We have null as one of the possible values
			// So add it to the options
			$values = array_merge(array('NULL'=>$column['null_title']), $values);
		}
		
		// Display options
		if ( !empty($values) )
		{
			echo '<select '.
				'name="'.$field_name.'" id="'.$field_name.'" '.
				'>';
			foreach ( $values as $option_value => $option_title )
			{
				$selected = $option_value === $value;
				echo
				'<option '.
					'value="'.to_string($option_value, 'htmlattr').'" '.
					( $selected ? 'selected="selected" ' : ' ' ).
					'>'.
				format_to_output($option_title, 'htmlbody').
				'</option>';
			}
			echo '</select>';
		}
		else
		{
			echo 'Nothing to select.';
			echo $this->display_input__hidden($column, $field_name, $action, $value, false, $params);
		}
		
		// Return
		return true;
	}
	
	function display_input__file_upload ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display a file upload
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		// Display upload form
		echo '<input '.
			'name="'.$field_name.'" id="'.$field_name.'" '.
			'style="width:100%;" type="file" '.
			'/>'.
			'<br />';
				
		// Display curent
		if ( !empty($field_value) && $action == 'update' )
		{
			$this->display_input__checkbox ( $column, $field_name.'__current_option', $action, true, false, $params);
			echo
			'&nbsp;Keep current file:&nbsp;&nbsp;'.
			'<a href="'.$column['params']['publish_url'].$this->get($column, 'htmlattr', '', false).'" title="View this file">'.
				$this->get($column, 'htmlbody', '', true).
			'</a>';
		}
		
		// Return
		return true;
	}
	
	function display_input__datetime ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Alias for timestamp
		return $this->display_input__timestamp($column, $field_name, $action, $value, $get_value, $params);
	}
	function display_input__timestamp ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display timestamp
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		# Display date
		$params['display_now'] = false;
		$this->display_input__date($column, $field_name, $action, $value, false, $params);
		
		echo '&nbsp;-&nbsp;&nbsp;';
		
		# Display time
		$params['display_now'] = true;
		$this->display_input__time($column, $field_name, $action, $value, false, $params);
		
		// Return
		return true;
	}
	
	function display_input__date ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display date
		if ( $get_value )	$value = $this->get($column);
		$display_now = true;
		extract($params);
		
		# Get day month year
		if ( empty($value) )
		{	// Set default
			$day = $month = $year = '';
		}
		else
		{	// Acquire
			$datetime = strtotime($value);
			$day = date('d', $datetime);
			$month = date('m', $datetime);
			$year = date('Y', $datetime);
		}
		
		# Display inputs
		$params['maxlength'] = $params['size'] = 2;
		$this->display_input__textbox($column, $field_name.'__day',  $action, $day,   false, $params);
		echo '&nbsp;/&nbsp;';
		
		$this->display_input__textbox($column, $field_name.'__month', $action, $month, false, $params);
		echo '&nbsp;/&nbsp;';
		
		$params['maxlength'] = $params['size'] = 4;
		$this->display_input__textbox($column, $field_name.'__year',  $action, $year,  false, $params);
		
		unset($params['maxlength'], $params['size']);
		
		# Display now
		if ( !empty($display_now) )
		{	// We want to
			echo '&nbsp;or&nbsp; Now ';
			$params['checked'] = ($column['required'] && empty($value)) || $value === 'NOW()';
			$this->display_input__checkbox($column, $field_name.'__now', $action, '', false, $params);
			// Display param var - this is so the field is detected in load_from_request
			$this->display_input__hidden($column, $field_name, $action, '', false, $params);
		}
		
		# Return
		return true;
	}	
	
	function display_input__time ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display time
		if ( $get_value )	$value = $this->get($column);
		$display_now = true;
		extract($params);
		
		# Get day month year
		if ( empty($value) )
		{	// Set default
			$hour = $minute = $second = '';
		}
		else
		{	// Acquire
			$datetime = strtotime($value);
			$hour = date('H', $datetime);
			$minute = date('i', $datetime);
			$second = date('s', $datetime);
		}
		
		# Display inputs
		$params['maxlength'] = $params['size'] = 2;
		$this->display_input__textbox($column, $field_name.'__hour',  $action, $hour,   false, $params);
		echo '&nbsp;:&nbsp;';
		
		$this->display_input__textbox($column, $field_name.'__minute', $action, $minute, false, $params);
		echo '&nbsp;:&nbsp;';
		
		$this->display_input__textbox($column, $field_name.'__second',  $action, $second,  false, $params);
		
		unset($params['maxlength'], $params['size']);
		
		# Display now
		if ( !empty($display_now) )
		{	// We want to
			echo '&nbsp;or&nbsp; Now ';
			$params['checked'] = ($column['required'] && empty($value)) || $value === 'NOW()';
			$this->display_input__checkbox($column, $field_name.'__now', '', true, false, $params);
			// Display param var - this is so the field is detected in load_from_request
			$this->display_input__hidden($column, $field_name, $action, '', false, $params);
		}
		
		# Return
		return true;
	}
	
}
