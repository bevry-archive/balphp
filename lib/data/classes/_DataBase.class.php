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

require_once(dirname(__FILE__).'/../../core/classes/_Log.class.php');
require_once(dirname(__FILE__).'/../../core/functions/_numeric.funcs.php');
require_once(dirname(__FILE__).'/../../core/functions/_strings.funcs.php');
require_once(dirname(__FILE__).'/../../core/functions/_classes.funcs.php');

class DataBase
{	/*
	 ***
	 * ABOUT
	 **
	 * Class: DataBase (DB) Class
	 * Author: Benjamin "balupton" Lupton
	 * Version: 4.0.0.0-dev
	 * Release Date: Unreleased
	 *
	 ***
	 * SUMMARY
	 **
	 * The DB class imports a database structure,
	 * and allow developers to interact with the database structure.
	 *
	 ***
	 * CHANGLOG
	 ***
	 * 3.2.0.0 (21/12/2006)
	 * - Added creation of table's default and required values
	 * - Changed INSERT and UPDATE to use ROWS instead of INFO ARRAYS
	 *
	 * 3.0.1.0 (20/12/2006)
	 * - Added support for limits in the format of '5, 5' and '5,5'
	 *
	 * 3.0.0.0 (18/12/2006)
	 * - Another re-write
	 *
	 * 2.0.0.0 (?/12/2006)
	 * - Another re-write
	 *
	 * 1.0.0.0 (01/12/2006)
	 * - Did a total re-write
	 *
	 ***
	 * THINGS TO DO (Developer Stuff)
	 **
	 * Add proper primary key support
	 * Adding checking of ID columns (eg. if a value is a id of a row somewhere else, we should check if that row exists)
	 * Add support for DataObjects directly
	 * Add 'JOIN' functionality into the select function
	 * Backup functionality needs to be added
	 * Change Upgrade Function to scan the database and create the old database structure from that
	 *
	 ***
	 * VARIABLES (PUBLIC)
	 **
	 * status (boolean)
	 * - Contains the status of the database class
	 * - ! Use this to check if an error has occured instead of the return value of called function
	 * - VALUES:
	 *   - TRUE:  Everything is good
	 *   - FALSE: An error occured
	 *
	 **
	 * Log (Log Class)
	 * - Handles all Logging
	 *
	 ***
	 * FUNCTIONS (PUBLIC)
	 **
	 * open()
	 * - Opens the connection to the database
	 * - Params
	 *   - Halt On Error = true
	 * - Returns
	 *   - Success: TRUE
	 *   - Failure: FALSE or NULL
	 *
	 **
	 * close()
	 * - Closes the connection to the database
	 * - Returns
	 *   - Success: TRUE
	 *   - Failure: FALSE
	 *
	 **
	 * install()
	 * - Installs the database structure
	 * - Returns
	 *   - Success: TRUE
	 *   - Failure: NULL
	 *
	 **
	 * uninstall()
	 * - Uninstalls the database structure
	 * - Returns
	 *   - Success: TRUE
	 *   - Failure: NULL
	 *
	 **
	 * upgrade()
	 * - Upgrades an older database structure into a new databsae structure
	 * - Params
	 *   - Old Datbase Structure
	 *   - After Insert = NULL		(call_user_func param)
	 *   - After Update = NULL		(call_user_func param)
	 *   - After Remove = NULL		(call_user_func param)
	 * - Returns
	 *   - Success: TRUE
	 *   - Failure: NULL
	 *
	 **
	 * total()
	 * - Gets the total number of rows from a search
	 * - Params
	 *   - Table
	 *   - Where Info = NULL		(See USAGE)
	 *   - Limit = NULL				(Integer, Restricts the search to the limit)
	 * - Returns
	 *   - Success: Number of Rows
	 *   - Failure: NULL
	 *
	 **
	 * search()
	 * - Upgrades an older database structure into a new databsae structure
	 * - Things to Know
	 *   - Specifing only one column will return an array of that column instead of an array of rows with only that columns
	 *     - Eg. One column: array($column_contents,);  Multiple Columns: array(array($column_name => $column_contents,),);
	 *   - If the result only contains one row AND the limit is set to 1, then only that row is returned, instead of a array containing that row.
	 *     - Eg. One Row and Limit = 1: $row;  Multiple Rows: array($row,);
	 * - Params
	 *   - Table
	 *   - Columns = '*'			(See USAGE)
	 *   - Where Info = NULL		(See USAGE)
	 *   - Limit = NULL				(Integer, Restricts the search to the limit)
	 *   - Order Info = NULL		(See USAGE)
	 * - Returns
	 *   - Success: Resulted Row(s)
	 *   - Failure: NULL
	 *
	 **
	 * select()
	 * - Mother of total() and search(). Use search() instead.
	 *
	 **
	 * insert()
	 * - Inserts content into the database
	 * - Params
	 *   - Table
	 *   - Insert Info				(See USAGE)
	 * - Returns
	 *   - Success: The Inserted Row's ID
	 *   - Failure: NULL
	 *
	 **
	 * delete()
	 * - Removes rows from the database that match the where info
	 * - Params
	 *   - Table
	 *   - Where Info				(See USAGE)
	 *   - Limit = NULL				(Integer, Restricts the search to the limit)
	 * - Returns
	 *   - Success: TRUE
	 *   - Failure: NULL
	 *
	 ***
	 * USAGE
	 **
	 * Database Structure
	 * - The structure used by the database class
	 * - For syntax refer to the $structure, $table and $column vars of this class
	 *
	 * Columns
	 * - The Columns that we would like to retrieve data from
	 * - Syntax:
	 *   - array($column_nickname,);
	 *
	 **
	 * Where Info
	 * - Information used to create the where text of the query
	 * - Syntax:
	 *     array(
	 *       array( COLUMN, VALUE, BOOLEAN OPERATOR ),
	 *       BRACKET (optional)
	 *       LOGIC OPERATOR (optional)
	 *     )
	 *
	 **
	 * Order Info
	 * - Information used to create the order text of the query
	 * - Syntax:
	 *     array(
	 *       array( COLUMN, DIRECTION ),
	 *     )
	 *
	 **
	 */
	
	var $include_db_in_queries = true;	// Multiple db support
	var $structure; // db structure
	
	var $status = true;

	var $default_structure = array(
		// REQUIRED
			'host'				=> '',
			'user'				=> '',
			'pass'				=> '',
			'name'				=> '',
			'tables'			=> array(),
		// OPTIONAL
			'null_title'		=> 'NULL',
		// FORCED
			'tables_nicknames'	=> array(),
			'tables_size'		=> 0
	);
	var $default_table = array(
		// REQUIRED
			'name'				=> '',
			'columns'			=> array(),
		// OPTIONAL
			'null_title'		=> NULL,
			'title'				=> NULL,
			'type'				=> '',
			'order_column'		=> NULL,
			'order_direction'	=> '',
			'owner'				=> NULL,
		// FORCED
			'nickname'			=> '',
			'columns_nicknames'	=> array(),
			'columns_size'		=> 0,
			'referred_by_columns'	=> array(), // contains a list of column nicknames that contain referred_bys
			// 'database'			=> NULL, // REFERENCE TO PARENT DATABASE
	);
	var $default_column = array(
		// REQUIRED
			'name'				=> '',
			'type'				=> '',
		// OPTIONAL
			'null_title'		=> NULL,
			'title'				=> NULL,		/**
												 * The title of the field
												 */
												
			'unique'			=> false,		/**
												 * Whether or not we want to be unique
												 */
												 
			'min_length'		=> NULL,		/**
												 * A integer that contains the minimum length for a varchar
												 */
												 
			'regex'				=> NULL,		/**
												 * A regular expression that the text or varchar needs to match
												 */
												 
			'range'				=> NULL,		/**
												 * For numbers, which range are we valid in
												 * imports
												 * - array('min' => $min, 'max' => $max, 'type' => $type)
												 */
			
			'values_corrected'	=> false,
			'values'			=> NULL, 		/**
												 * What values are valid
												 * imports
												 * - array($value,)
												 * - array('table' => $table, 'values_column' => $values_column, 'titles_column' => $titles_column, 'where' => $blah)
												 * becomes
												 * - array($value => $title,)
												 */
												 
			'refers_to'			=> NULL,		/**
												 * $table_nickname.'.'.$column_nickname, if this is set, then we check if the value exists in the given column
												 */
			
			'multiple'			=> false,		/**
												 * so we can have multiple checkboxes say
												 */
			
			// DataObject Params
			'input'				=> NULL,		/**
												 * How to display the item in either a add or edit form (Used by DataObject)
												 * values;
												 * - 'text'	 		user specified
												 * - 'password'	 	user specified
												 * - 'textbox'		for integers and varchars
												 * - 'textarea'		for text
												 * - 'dropdown'		for lists (values is specified) if range is specified then this is not valid
												 * - 'checkbox'		for booleans (type is bool)
												 */
												
			/*'enabled'			=> true,*/		/**
												 * Whether or not the field is enabled (for editing) (Used by DataObject)
												 */
												 
			'params'			=> array(),		/**
												 * Optional params
												 */
												 
			'confirm'			=> false,		/**
												 * Whether or not the value needs to be confirmed or not (Used by DataObject)
												 * Becomes true by default is display is set to 'password'
												 */
		// FORCED
			'referred_by'		=> array(),		// contains a array of columns that refers to this one
			'max_length'		=> NULL,		// the max length, retrieved from the type eg. VARCHAR(3) has a maxlength of 3
			'nickname'			=> '',
			'table_nickname'	=> '',
			// 'table'				=> NULL, // REFERENCE TO PARENT TABLE
			// 'database'			=> NULL, // REFERENCE TO PARENT DATABASE
	);
	
	var $Log;
	
	var $values_queue = array();
	
	// ==============================================
	
	function Database ( $structure, $correct_references = true )
	{
		if ( !isset($this->Log) )
			$this->Log = new Log();
	
		$this->correct_structure($structure);
		$this->structure = $structure;
		
		$this->open();
		
		if ( $correct_references )
			$this->correct_references_for_structure($this->structure);
			
		return true;
	}
		
	// ==============================================
	
	/* public */
	function open ( $halt_on_error = true )
	{
		$db_handle = mysql_connect(
			$this->structure['host'],
			$this->structure['user'],
			$this->structure['pass']
		);
		
		# Add Log if error
		if ( $mysql_error = mysql_error() )
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Connecting to the Database Server in the Database Class failed',
				// DESCRIPTION
					'',
				// DETAILS
					'Error: ['.			$mysql_error			.']',
				// WHERE
					'Class: '.			get_class_heirachy($this, true)		."\r\n".
					'Filename: '.		basename(__FILE__)						."\r\n".
					'File: '.			__FILE__								."\r\n".
					'Function: '.		__FUNCTION__							."\r\n".
					'Line: '.			__LINE__
			);
			$this->status = false;
			if ( $halt_on_error )
			{
				$this->Log->display();
				die;
			}
			return NULL;
		}
		
		mysql_select_db($this->structure['name'], $db_handle);
		
		# Add Log if error
		if ( $mysql_error = mysql_error() )
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Selecting the Database in the Database Class failed',
				// DESCRIPTION
					'',
				// DETAILS
					'Database: ['.		$this->structure['name']	.']'."\r\n".
					'Error: ['.			$mysql_error				.']',
				// WHERE
					'Class: '.			get_class_heirachy($this, true)		."\r\n".
					'Filename: '.		basename(__FILE__)						."\r\n".
					'File: '.			__FILE__								."\r\n".
					'Function: '.		__FUNCTION__							."\r\n".
					'Line: '.			__LINE__
			);
			$this->status = false;
			if ( $halt_on_error )
			{
				$this->Log->display();
				die;
			}
			return NULL;
		}
		
		return $db_handle ? true : false;
	}
	
	/* public */
	function close ( )
	{
		return @mysql_close();	
	}
	
	// ==============================================
	
	
	// ==============================================
	
	/* public */
	function perform_query ( $mysql_query, $function_name = 'Perform Query Function', $function_description = '' )
	{
		$this->status = true;
		
		# --------------------
		# Perform the query
		$mysql_result = mysql_query($mysql_query);
		
		# Add Log if error
		if ( $mysql_error = mysql_error() )
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Performing a Query, in the Database Class\'s, '.$function_name.', failed',
				// DESCRIPTION
					$function_description,
				// DETAILS
					'Query: ['.			$mysql_query			.']'."\r\n".
					'Error: ['.			$mysql_error			.']',
				// WHERE
					'Class: '.			get_class_heirachy($this, true)		."\r\n".
					'Filename: '.		basename(__FILE__)						."\r\n".
					'File: '.			__FILE__								."\r\n".
					'Function: '.		__FUNCTION__							."\r\n".
					'Line: '.			__LINE__
			);
			$this->status = false;
			return NULL;
		}
		
		# Log Query
		$this->Log->add(
			// TYPE
				'Query',
			// TITLE
				'Log of a Performed Query, in the Database Class\'s, '.$function_name,
			// DESCRIPTION
				$function_description,
			// DETAILS
				'Query: ['.			$mysql_query			.']'."\r\n".
				'Error: ['.			$mysql_error			.']',
			// WHERE
				'Class: '.			get_class_heirachy($this, true)		."\r\n".
				'Filename: '.		basename(__FILE__)						."\r\n".
				'File: '.			__FILE__								."\r\n".
				'Function: '.		__FUNCTION__							."\r\n".
				'Line: '.			__LINE__
		);
		
		return $mysql_result;
	}
	
	// ==============================================

	/* private */
	function prepare_total_query (
		$table,
		$id_column,
		$where_info				= NULL,		// array ( [0] => Column Nickname, [1] => Column Value, [2] => Column Operator )
		$limit					= NULL,
		$function_name			= 'Perpare Total Query Function',
		$function_description	= ''
	)
	{	$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Prepare text
		
		$select_text = 'SELECT COUNT(`'.$id_column['name'].'`) ';
		
		# --------------------
		# Prepare other texts
		
		$from_text = 'FROM '.($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').' ';
		$limit_text = $this->prepare_limit_text($limit, $function_name, $function_description);
		$where_text = $this->prepare_where_text($table, $where_info, $function_name, $function_description);
		
		# --------------------
		# Build the query
		
		$mysql_query =
			$select_text.
			$from_text.
			$where_text.
			$limit_text.';';
		
		# --------------------
		# Return the query
		
		return $mysql_query;
	}
	
	/* public */
	function total ( $table, $where_info = NULL, $limit = NULL, $function_name = 'Total Function', $function_description = '' )
	{	// Get the total
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		$table = $this->get_table(
			$table,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		$id_column = $this->get_column(
			$table,
			'id',
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Prepare the query
		
		$mysql_query = $this->prepare_total_query(
			$table,
			$id_column,
			$where_info,
			$limit,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Perform the query
		
		$mysql_result = $this->perform_query(
			$mysql_query,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
			
		
		$total = mysql_fetch_array($mysql_result);
		$total = intval($total[0]);
		
		return $total;
	}
	
	/* private */
	function prepare_select_query (
		$table,
		$columns,							// An array of columns, or an array of column's nicknames
		$where_info				= NULL,		// array ( [0] => Column Nickname, [1] => Column Value, [2] => Column Operator )
		$limit					= NULL,
		$order_info				= NULL,		// array ( [0] => Column Nickname, [1] => Direction )
		$function_name			= 'Perpare Select Query Function',
		$function_description	= ''
	)
	{	$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Prepare text
		
		$select_text = '';
		
		for ( $i = 0, $n = sizeof($columns); $i < $n; $i++ )
		{
			$column = $columns[$i];
			$select_text .= '`'.$column['name'].'` as `'.$column['nickname'].'`, ';
		}
		
		if ( !empty($select_text) )
		{	// There is some order info
			$select_text = trim($select_text,', ').' ';	// get rid of the extra ', '
			$select_text = 'SELECT '.$select_text;	// prepend select to text
		
		} else
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Creating the Select Query, in the Database Class\'s, '.$function_name.', did not succeed ($select_text was empty)',
				// DESCRIPTION
					$function_description,
				// DETAILS
					'Table Nickname: ['.	var_export($table['nickname'], true)	.']'."\r\n".
					'Columns: ['.			var_export($columns, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__
			);
			$this->status = false;
			return NULL;
		}
		
		# --------------------
		# Prepare other texts
		
		$from_text = 'FROM '.($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').' ';
		$limit_text = $this->prepare_limit_text($limit, $function_name, $function_description);
		$where_text = $this->prepare_where_text($table, $where_info, $function_name, $function_description);
		$order_text = $this->prepare_order_text($table, $order_info, $function_name, $function_description);
		
		# --------------------
		# Build the query
		
		$mysql_query =
			$select_text.
			$from_text.
			$where_text.
			$order_text.
			$limit_text.';';
		
		# --------------------
		# Return the query
		
		return $mysql_query;
	}
	
	/* public */
	function search ( $table, $columns = '*', $where_info = NULL, $limit = NULL, $order_info = NULL, $get_total = false, $function_name = 'Search Function', $function_description = '' )
	{	// Alias for select
		return $this->select( $table, $columns, $where_info, $limit, $order_info, $get_total, $function_name, $function_description );
	}
	
	/* private-public */
	function select (
		$table,
		$columns				= '*',		// An array of columns, or an array of column's nicknames
		$where_info				= NULL,		// array ( [0] => Column Nickname, [1] => Column Value, [2] => Column Operator )
		$limit					= NULL,
		$order_info				= NULL,		// array ( [0] => Column Nickname, [1] => Direction )
		$get_total				= false,
		$function_name			= 'Select Function',
		$function_description	= ''
	)
	{	
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		$table = $this->get_table(
			$table,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		if ( $columns == '*' )
		{	// Get all columns
			$columns = $table['columns_nicknames'];
		}
		$columns = $this->get_columns(
			$table,
			$columns,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		if ( $where_info === array() ) $where_info = NULL;
		
		# --------------------
		# Prepare the query
		
		$mysql_query = $this->prepare_select_query(
			$table,
			$columns,
			$where_info,
			$limit,
			$order_info,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Perform the query
		
		$mysql_result = $this->perform_query(
			$mysql_query,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Return all the resulted rows
		
		$rows = array();
		if ( $get_total )
		{	// Prepend the total if wanted
			$rows[] = mysql_num_rows($mysql_result);
		}
		
		if ( ($columns_size = sizeof($columns)) == 1 )
		{	// If we only want one column, then let make life simplier
			while ( $row = mysql_fetch_assoc($mysql_result) )
			{	// Append the row's column
				$column = $columns[0];
				$rows[] = $this->real_value($row[$column['nickname']], $column);
			}
			
		} else
		{	// We want multiple columns
			while ( $row = mysql_fetch_assoc($mysql_result) )
			{	// Append the row
				for ( $i = 0; $i < $columns_size; $i++ )
				{	// Convert the returned values back into what they should be
					$column = $columns[$i];
					$row[$column['nickname']] = $this->real_value($row[$column['nickname']], $column);
				}
				$rows[] = $row;
			}
		}
		
		# Free the result, we don't need it anymore
		mysql_free_result($mysql_result);
		
		
		# --------------------
		# Finish up
		
		# If we have a empty result, return false
		if ( $rows === array() )
			return array();	// not false, as sizeof(false) == 1
		
		# If we only want one result, and a result is present, then return only one result
		if ( $limit == 1 )
		{	// Because the limit == 1, we know we either only have one row, so there is no need to do a sizeof check
			$rows = $rows[0];
		}
		
		# Return the result
		return $rows;
		
	}
	
	/* private */
	function prepare_insert_query ( $table, $row, $function_name = 'Prepare Insert Query', $function_description = '')
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Prepare text
		
		$insert_text =
		$insert_columns_text =
		$insert_values_text = '';
		
		$row_columns = $this->get_row_columns($table, $row, $function_name, $function_description);
		
		for ( $i = 0, $n = sizeof($row); $i < $n; $i++ )
		{
			# Get Column
			$column = $row_columns[$i];
			
			# Get Value
			$value = $row[$column['nickname']];
			
			# Append Text
			$value = $this->prepare_value($value, $column);
			$insert_columns_text .= '`'.$column['name'].'`, ';
			$insert_values_text .= $value.', ';
			
		}
		
		if ( !empty($insert_columns_text) && !empty($insert_values_text) )
		{
			$insert_columns_text = trim($insert_columns_text, ', ');
			$insert_values_text = trim($insert_values_text, ', ');
			
			$insert_text =
				'INSERT INTO '.($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').
				' ( '.
					$insert_columns_text.
				' ) VALUES ( '.
					$insert_values_text.
				' )'
			;
		} else
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Creating the Insert Query, in the Database Class\'s, '.$function_name.', did not succeed ($insert_text was empty)',
				// DESCRIPTION
					$function_description,
				// DETAILS
					'Table Nickname: ['.	var_export($table['nickname'], true)	.']'."\r\n".
					'Row: ['.				var_export($row, true)					.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__
			);
			$this->status = false;
			return NULL;
		}
		
		# --------------------
		# Build the query
		
		$mysql_query =
			$insert_text.'; ';
			
		# --------------------
		# Return the query
		
		return $mysql_query;
	}
	
	/* public */
	function insert ( $table, $row, $function_name = 'Insert Function', $function_description = '' )
	{	/***
		 * RETURNS
		 **
		 * TRUE/ROW_ID: If everything went good, then it will return the ID of the new row
		 * FALSE: If the insert failed because the value does not match the requirements
		 * NULL: If the instert failed due to a error
		 *
		 */
		 
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		$in_table = $table;
		
		$table = $this->get_table(
			$table,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		$do_checks = !( $in_table == $table );
		unset($in_table);
		
		if ( array_key_exists('id', $row) )
		{	// Remove id from the row, we don't use it
			unset($row['id']);
		}
		
		$row = $this->make_valid_row(
			$table,
			$row,
			$do_checks,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Setup the query
		
		$mysql_query = $this->prepare_insert_query(
			$table,
			$row,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Perform the query
		
		$mysql_result = $this->perform_query(
			$mysql_query,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Finish up
		
		# Get the new id
		$id = mysql_insert_id();
		
		# Return the new result
		return $id;
	}
	
	/* private */
	function prepare_delete_query ( $table, $where_info, $limit = NULL, $function_name = 'Prepare Delete Query Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Prepare the text
		
		$where_text = $this->prepare_where_text($table, $where_info, $function_name, $function_description);
		
		if ( empty($where_text) )
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Creating the Delete Query, in the Database Class\'s, '.$function_name.', did not succeed ($where_text was empty)',
				// DESCRIPTION
					'',
				// DETAILS
					'Table Nickname: ['.	var_export($table['nickname'], true)	.']'."\r\n".
					'Where Info: ['.		var_export($where_info, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__
			);
			$this->status = false;
			return NULL;
		}
		
		# --------------------
		# Prepare other texts
		
		$delete_text =
			'DELETE FROM '.($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').' ';
		$limit_text = $this->prepare_limit_text($limit, $function_name, $function_description);
		
		# --------------------
		# Build the query
		
		$mysql_query =
			$delete_text.
			$where_text.
			$limit_text.';';
		
		# --------------------
		# Return the query
		
		return $mysql_query;
	}
	
	/* public */
	function delete ( $table, $where_info, $limit = NULL, $function_name = 'Delete Function', $function_description = '' )
	{	/***
		 * RETURNS
		 **
		 * TRUE/ROWS: If everything went good
		 * FALSE: If there was no rows that where returned
		 * NULL: If an error occured
		 *
		 */
		
		$this->status = true;
		
		# --------------------
		# Prepare params
		
		$table = $this->get_table(
			$table,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		if ( $where_info === array() ) $where_info = NULL;
		
		# --------------------
		# Delete references
		
		if ( !empty($table['referred_by_columns']) )
		{	
			for ( $i = 0, $n = sizeof($table['referred_by_columns']); $i < $n; $i++ )
			{
				$column_nickname = $table['referred_by_columns'][$i];
				$column = $table['columns'][$column_nickname];
				
				$column_value = $this->search(
					// TABLE
					$table,
					// COLUMNS
					$column,
					// WHERE
					$where_info,
					// LIMIT
					1
				);	if ( !$this->status )
				return NULL;
				
				if ( $column_value === array() )
					continue;
				
				$referred_by = $column['referred_by'];
				$referred_by_tables = array_keys($referred_by);
				$referred_by_columns = array_values($referred_by);
				for ( $h = 0, $o = sizeof($column['referred_by']); $h < $o; $h++ )
				{
					$r_table = $referred_by_tables[$h];
					$r_column = $referred_by_columns[$h];
					
					$r_table = $this->get_table($r_table);
					$r_column = $this->get_column($r_table, $r_column);
					if ( !$this->status )
						return NULL;
					
					$r_where = array(
						array($r_column['nickname'], $column_value)
					);
					
					if ( $r_column['null'] )
					{	// The column supports NULL so lets set that column to NULL
						$this->update(
							// TABLE
							$r_table,
							// ROW
							array(
								$r_column['nickname'] => NULL
							),
							// WHERE
							$r_where
						);	if ( !$this->status )
						return NULL;

					}
					else
					{	// The column does not support NULL so lets remove that row
						$this->delete(
							// TABLE
							$r_table,
							// WHERE
							$r_where
						);	if ( !$this->status )
						return NULL;
					}
				}
			}
		}
		
		# --------------------
		# Prepare the query
		
		$mysql_query = $this->prepare_delete_query(
			$table,
			$where_info,
			$limit,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Perform the query
		
		$mysql_result = $this->perform_query(
			$mysql_query,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# If we reached here, life is good
		
		return true;
	}
	
	/* private */
	function prepare_update_query ( $table, $row, $where_info, $limit = NULL, $function_name = 'Prepare Update Query Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Prepare the text
		
		$update_text = '';
		
		$row_columns = $this->get_row_columns($table, $row, $function_name, $function_description);
		for ( $i = 0, $n = sizeof($row); $i < $n; $i++ )
		{
			# Get Column
			$column = $row_columns[$i];
			
			# Get Value
			$value = $row[$column['nickname']];
			
			# Append Text
			$value = $this->prepare_value($value, $column);
			$update_text .= '`'.$column['name'].'` = '.$value.', ';
		}
		
		if ( !empty($update_text) )
		{
			$update_text = trim($update_text, ', ');
			$update_text = 'UPDATE '.($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').' SET '.
				$update_text.' '
			;
		
		} else
		{
			$this->Log->add(
				// TYPE
					'Error',
				// TITLE
					'Creating the Update Query, in the Database Class\'s, '.$function_name.', did not succeed ($update_text was empty)',
				// DESCRIPTION
					'',
				// DETAILS
					'Table Nickname: ['.	var_export($table['nickname'], true)	.']'."\r\n".
					'Row: ['.				var_export($row, true)					.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__
			);
			$this->status = false;
			return NULL;
		}
		
		# --------------------
		# Prepare other texts
		
		$where_text = $this->prepare_where_text($table, $where_info, $function_name, $function_description);
		$limit_text = $this->prepare_limit_text($limit, $function_name, $function_description);
		
		# --------------------
		# Build the query
		
		$mysql_query =
			$update_text.
			$where_text.
			$limit_text.';';
		
		# --------------------
		# Return the query
		
		return $mysql_query;
	}
	
	/* public */
	function update ( $table, $row, $where_info, $limit = NULL, $function_name = 'Update Function', $function_description = '' )
	{	/***
		 * RETURNS
		 **
		 * TRUE/ROWS: If everything went good
		 * FALSE: If there was no rows that where returned
		 * NULL: If an error occured
		 *
		 */
		
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		$in_table = $table;
		
		$table = $this->get_table(
			$table,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		$do_checks = !( $in_table == $table );
		unset($in_table);
		
		if ( array_key_exists('id', $row) )
		{	// Remove id from the row, we don't use it
			unset($row['id']);
		}
		
		$row = $this->make_valid_row(
			$table,
			$row,
			$do_checks,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		if ( $where_info === array() ) $where_info = NULL;
		
		# --------------------
		# Setup the query
		
		$mysql_query = $this->prepare_update_query(
			$table,
			$row,
			$where_info,
			$limit,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Perform the query
		
		$mysql_result = $this->perform_query(
			$mysql_query,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Finish up
		
		return true;
	}
	
	// ==============================================
	
	/* private */
	function prepare_install_query ( $table, $function_name = 'Prepare Install Query Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Build the query
		
		$mysql_query = '';
		
		# Begin this table's create query
		$mysql_query .= 'CREATE TABLE '.($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').' ( ';
		
		# Cycle through the table's columns
		for ( $i = 0, $n = $table['columns_size']; $i < $n; $i++ )
		{
			# Get the column
			$column_nickname = $table['columns_nicknames'][$i];
			$column = $this->get_column(
				$table,
				$column_nickname,
				$function_name,
				$function_description
			);	if ( !$this->status )
				return NULL;
			
			# Add the column stuff into the query
			$mysql_query .= '`'.$column['name'].'` ';
			$mysql_query .= 	$column['type'].', ';
		}
			
		# Fix up the query
		$mysql_query = trim($mysql_query,', ').' ) ';
		
		# Finish the query by appending table params
		if ( !is_null($table['type']) )
			$mysql_query .= $table['type'].' ';
		
		$mysql_query .= ';';
		
		# --------------------
		# Return the query
		
		return $mysql_query;
		
	}
	
	/* public */
	function install ( $tables = NULL, $function_name = 'Install Function', $function_description = '' )
	{	
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		if ( is_null($tables) )
			$tables = $this->structure['tables_nicknames'];
		elseif ( empty($tables) )
			return true;
		
		$tables = $this->get_tables(
			$tables,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# ------------------------------
		# Do the queries
		
		for ( $i = 0, $n = sizeof($tables); $i < $n; $i++ )
		{
			# --------------------
			# Get the table
			
			$table = $tables[$i];
			
			# --------------------
			# Setup the query
			
			$mysql_query = $this->prepare_install_query(
				$table,
				$function_name,
				$function_description
			);	if ( !$this->status )
				return NULL;
			
			# --------------------
			# Perform the query
			
			$this->perform_query(
				$mysql_query,
				$function_name,
				$function_description
			);	if ( !$this->status )
				return NULL;
		
		}
		
		# --------------------
		# Finish up
		
		$this->correct_references_for_structure($this->structure);
		
		return true;
	}
	
	/* private */
	function prepare_uninstall_query ( $tables, $function_name = 'Prepare Unnstall Query Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# --------------------
		# Build the query
		
		$mysql_query = '';
		
		for ( $i = 0, $n = sizeof($tables); $i < $n; $i++ )
		{
			$table = $tables[$i];
			$mysql_query .= ($this->include_db_in_queries ? '`'.$this->structure['name'].'`.' : '').('`'.$table['name'].'`').', ';
		}
		
		$mysql_query = trim($mysql_query,', ');
		$mysql_query = 'DROP TABLE '.$mysql_query.' ;';
		
		# --------------------
		# Return the query
		
		return $mysql_query;
		
	}
	
	/* public */
	function uninstall ( $tables = NULL, $function_name = 'Unnstall Function', $function_description = '' )
	{	
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		if ( is_null($tables) )
			$tables = $this->structure['tables_nicknames'];
		elseif ( empty($tables) )
			return true;
		
		$tables = $this->get_tables(
			$tables,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Setup the query
		
		$mysql_query = $this->prepare_uninstall_query(
			$tables,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Perform the query
			
		$this->perform_query(
			$mysql_query,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# --------------------
		# Finish up
		
		return true;
	}
	
	// ==============================================
	
	function backup ( $action )
	{
		switch ( $action )
		{
			case 'create':
				break;
			
			case 'restore':
				break;
				
			case 'remove':
				break;
		}
		
		return true;
	}
		
	// ==============================================
	
	/* public */
	function upgrade_to ( $new_structure, $after_insert, $after_update, $after_remove, $function_name = 'Upgrade To Function', $function_description = '' )
	{	// Alias for upgrade, kindof
		return $this->upgrade_from ( $this->structure, $after_insert, $after_update, $after_remove, $new_structure );
	}
	
	/* public */
	function upgrade_from ( $old_structure, $after_insert, $after_update, $after_remove, $function_name = 'Upgrade To Function', $function_description = '' )
	{	// Alias for upgrade
		return $this->upgrade_from ( $old_structure, $after_insert, $after_update, $after_remove, $this->structure );
	}
	
	/* public */
	function upgrade
	(	// Upgrades an older database structure into a new databsae structure
		$old_structure,			// the older database structure to upgrade
		$after_insert = NULL,	// call_user_func param
		$after_update = NULL,	// call_user_func param
		$after_remove = NULL,	// call_user_func param
		$new_structure = NULL,	// the new structure
		$function_name = 'Upgrade Function',
		$function_description = ''
	) {	

		$this->status = true;

		# =======================================================
		# Prepare the structures
		
		if ( is_null($new_structure) )
			$new_structure = $this->structure;
		else
			$this->correct_structure($new_structure);
		
		$this->correct_structure($old_structure);
		
		
		# =======================================================
		# Prepare the tables
		
		$new_tables_nicknames = $new_structure['tables_nicknames'];
		$old_tables_nicknames = $old_structure['tables_nicknames'];
		
		$insert_tables = array_values(array_diff($new_tables_nicknames, $old_tables_nicknames));
		
		$update_tables = array_values(array_intersect($new_tables_nicknames, $old_tables_nicknames));
		$update_tables = $this->get_tables(
			$update_tables,
			$function_name,
			'Performed at the preperation of the Update Tables'
		);	if ( !$this->status )
			return NULL;
		$update_tables_size = sizeof($update_tables);
		
		$remove_tables = array_values(array_diff($old_tables_nicknames, $new_tables_nicknames));
		
		
		# =======================================================
		# Prepare the Update Tables' Columns
		
		$insert_columns =
		$update_columns =
		$remove_columns = array();
		
		for ( $i = 0, $n = $update_tables_size; $i < $n; $i++ )
		{
			$new_table = $update_tables[$i];
			$table_nickname = $new_table['nickname'];
			$old_table = $old_structure['tables'][$table_nickname];
			
			$new_columns_nicknames = $new_table['columns_nicknames'];
			$old_columns_nicknames = $old_table['columns_nicknames'];
			
			$insert_columns[$table_nickname] = array_values(array_diff($new_columns_nicknames, $old_columns_nicknames));
			$insert_columns[$table_nickname] = $this->get_columns(
				$new_table,
				$insert_columns[$table_nickname],
				$function_name,
				'Performed at the preperation of the Insert Columns'
			);	if ( !$this->status )
				return NULL;
			
			$update_columns[$table_nickname] = array_values(array_intersect($new_columns_nicknames, $old_columns_nicknames));
			$update_columns[$table_nickname] = $this->get_columns(
				$new_table,
				$update_columns[$table_nickname],
				$function_name,
				'Performed at the preperation of the Update Columns'
			);	if ( !$this->status )
				return NULL;
			
			$remove_columns[$table_nickname] = array_values(array_diff($old_columns_nicknames, $new_columns_nicknames));
			// NOTICE HOW WE USE $old_table HERE! that is because if we are removing a column, then it exists in the old table AND not the new one
			$remove_columns[$table_nickname] = $this->get_columns(
				$old_table,
				$remove_columns[$table_nickname],
				$function_name,
				'Performed at the preperation of the Remove Columns'
			);	if ( !$this->status )
				return NULL;
			
		}
		
		# =======================================================
		# INSERT - Insert new tables, update tables (names), insert new columns
		
		# INSERT new TABLES
		$this->install(
			$insert_tables,
			$function_name,
			'Performed at the Installation of new Tables'
		);	if ( !$this->status )
			return NULL;
		
		# INSERT Update Tables' new COLUMNS
		for ( $i = 0, $n = $update_tables_size; $i < $n; $i++ )
		{	# Get the current update table
			$new_table = $update_tables[$i];
			$table_nickname = $new_table['nickname'];
			$old_table = $old_structure['tables'][$table_nickname];
			$table_insert_columns = $insert_columns[$table_nickname];
			
			# Cycle through the insert columns for that update table
			for ( $h = 0, $o = sizeof($table_insert_columns); $h < $o; $h++ )
			{	# Get the column
				$column = $table_insert_columns[$h];
				
				# Create the query
				$mysql_query =
					// Notice how we are using the old table's name here,
					//  this is very important, as we have not yet updated old tables names
					//  to their new names (if need be), so if a table has been renamed then
					//  this would fail if we were using the new table's name here.
					'ALTER TABLE '.($this->include_db_in_queries ? '`'.$old_structure['name'].'`.' : '').('`'.$old_table['name'].'`').' '.
					'ADD `'.$column['name'].'` '.
					$column['type'].' ;';
				
				# --------------------
				# Perform the query
				
				$this->perform_query(
					$mysql_query,
					$function_name,
					'Performed during the Insert Columns part'
				);	if ( !$this->status )
					return NULL;
				
				// We have finished inserting columns
			}
			
			// We have finished grabbing columns to insert
		}
		
		# Call the after_insert function
		if ( !is_null($after_insert) )
			call_user_func($after_insert);
		
		# =======================================================
		# Update - Update old tables and columns to new values
		
		# UPDATE existing TABLES (Change their names to their new names)
		for ( $i = 0, $n = $update_tables_size; $i < $n; $i++ )
		{	# Get the current update table
			$new_table = $update_tables[$i];
			$table_nickname = $new_table['nickname'];
			$old_table = $old_structure['tables'][$table_nickname];
			
			# -----------------------------------------------
			# Check if the TABLE name has changed
			
			if ( $new_table['name'] != $old_table['name'] )
			{	// We need to do an update
			
				# Create the query
				$mysql_query = 'RENAME TABLE '.($this->include_db_in_queries ? '`'.$old_structure['name'].'`.' : '').('`'.$old_table['name'].'`').' TO '.($this->include_db_in_queries ? '`'.$new_structure['name'].'`.' : '').('`'.$new_table['name'].'`').' ;';
				
				# --------------------
				# Perform the query
				
				$this->perform_query(
					$mysql_query,
					$function_name,
					'Performed during the Update Tables\' Name part'
				);	if ( !$this->status )
					return NULL;
			}
			
			# -----------------------------------------------
			# Update the TABLE's params if need be
			
			if ( $new_table['type'] != $old_table['type'] )
			{
				# Create the query
				$mysql_query = 'ALTER TABLE '.($this->include_db_in_queries ? '`'.$new_structure['name'].'`.' : '').('`'.$new_table['name'].'`').' '.$new_table['type'].' ;';
				
				# --------------------
				# Perform the query
				
				$this->perform_query(
					$mysql_query,
					$function_name,
					'Performed during the Update Tables\' Name part'
				);	if ( !$this->status )
					return NULL;
			}
		
			# -----------------------------------------------
			# Let's update any COLUMNS that need updating
			
			$table_update_columns = $update_columns[$table_nickname];
			
			# Cycle through the insert columns for that update table
			for ( $h = 0, $o = sizeof($table_update_columns); $h < $o; $h++ )
			{	
				# Get the column
				$new_column = $table_update_columns[$h];
				$column_nickname = $new_column['nickname'];
				$old_column = $old_table['columns'][$column_nickname];
				
				# Prepare the query
				$mysql_query = '';
				
				# Check if we need to do the query
				if ( $new_column['name'] != $old_column['name'] )
				{	// The COLUMN needs to be renamed
					$mysql_query .= 'CHANGE COLUMN `'.$old_column['name'].'` `'.$new_column['name'].'` ';
					
				} elseif ( $new_column['type'] != $old_column['type'] )
				{	// The COLUMN's type needs to be changed
					$mysql_query .= 'CHANGE COLUMN `'.$new_column['name'].'` `'.$new_column['name'].'` ';
					
				}	// ELSE NOTHING NEEDS TO BE CHANGED, SO WE DON'T NEED TO DO THE QUERY
				
				# Perform the query if need be
				if ( !empty($mysql_query) )
				{
					# Finish up the query
					$mysql_query = 
						'ALTER TABLE '.($this->include_db_in_queries ? '`'.$new_structure['name'].'`.' : '').('`'.$new_table['name'].'`').' '.
							$mysql_query.
							str_replace(' PRIMARY KEY','',$new_column['type']).' '.	// THIS IS GOOD PRIMARY KEY SUPPORT
						';'
					;
					
					# --------------------
					# Perform the query
					
					$this->perform_query(
						$mysql_query,
						$function_name,
						'Performed during the Update Columns part'
					);	if ( !$this->status )
						return NULL;
				}
				
				// Updating the columns is done
			}
			
			// Updating the tables is done
		}
		
		// Call the after_update function
		if ( !is_null($after_update) )
			call_user_func($after_update);
		
		
		# =======================================================
		# Remove - Drop old tables and columns
		
		# Remove TABLES
		$this->uninstall(
			$remove_tables,
			$function_name,
			'Performed at the Uninstallation of the old Tables'
		);	if ( !$this->status )
			return NULL;
		
		# Remove COLUMNS from UPDATE TABLES
		for ( $i = 0, $n = $update_tables_size; $i < $n; $i++ )
		{	# Get the current update table
			$new_table = $update_tables[$i];
			$table_nickname = $new_table['nickname'];
			
			$table_remove_columns = $remove_columns[$table_nickname];
		
			# Cycle through the insert columns for that update table
			for ( $h = 0, $o = sizeof($table_remove_columns); $h < $o; $h++ )
			{
				# Get the column
				$old_column = $table_remove_columns[$h];
				
				# Create the query
				$mysql_query =
					'ALTER TABLE '.($this->include_db_in_queries ? '`'.$new_structure['name'].'`.' : '').('`'.$new_table['name'].'`').' '.
					'DROP `'.$old_column['name'].'` ;'
				;
				
				# --------------------
				# Perform the query
				
				$this->perform_query(
					$mysql_query,
					$function_name,
					'Performed during the Removing Columns part'
				);	if ( !$this->status )
					return NULL;
				
			}
			
			// We are done cycling through the tables to get columns to remove
		}
		
		// We are done with the remove
		
		# Call the after_remove function
		if ( !is_null($after_remove) )
			call_user_func($after_remove);
			
		
		# --------------------
		# Finish up
		
		$this->correct_references_for_structure($this->structure);
		
		return true;
		
	}
	
	/* public */
	function set_column_param ( $param, $table, $column, $value )
	{
		return $this->set_param($param, $table, $column, $value);
	}
	
	/* public */
	function set_table_param ( $param, $table, $value )
	{
		return $this->set_param($param, $table, $value);
	}
	
	/* public */
	function get_param ( $param, $table, $column = NULL )
	{
		$table = & $this->get_table($table);
		// check status
		
		if ( is_null($column) )
		{	// No column
			return $table[$param];
		}
		else
		{	// Have column
			$column = & $this->get_column($table, $column);
			return $column[$param];
		}
		
		return NULL;
	}
	
	/* private */
	function set_param ( $param, $table, $column, $value = NULL )
	{
		$table = & $this->get_table($table);
		// check status
		
		if ( is_null($value) )
		{	// No column
			$table[$param] = $column;
		}
		else
		{	// Have column
			$column = & $this->get_column($table, $column);
			$column[$param] = $value;
		}
		
		return true;
	}

	// ==============================================
	
	/* private */
	function check_all ( $table, $column, $value, $id = NULL, $log = false, $function_name = 'Check All Function', $function_description = '' )
	{	/**
		 * RETURNS
		 **
		 * TRUE: The value matched it's requirements, or if there were no requirements
		 * FALSE: Some values do not match their requirements
		 * NULL: An error occured
		 *
		 */
		$this->status = true;
		
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Check the value
		
		if ( $value === NULL && $column['null'] )
			return true;
		
		if ( $column['multiple'] )
		{	// Multiples are different as if it passes a value here then we don't care about the rest
			$check_refers_to = $this->check_refers_to(
				$column,
				$value,
				$log,
				$function_name,
				$function_description
			);	if ( !$this->status )
				return NULL;
			elseif ( $check_refers_to )
				return $check_refers_to; // pass and quit
		
			$check_values = $this->check_values(
				$column,
				$value,
				$log,
				$function_name,
				$function_description
			);	if ( !$this->status )
				return NULL;
			elseif ( $check_values )
				return $check_values; // pass and quit
				
			// Didn't pass a value, let's see if it passes the other checks
		}
		
		$check_value = $this->check_value(
			$column,
			$value,
			$log,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		
		$check_range = $this->check_range(
			$column,
			$value,
			$log,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		$check_min_length = $this->check_min_length(
			$column,
			$value,
			$log,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		$check_regex = $this->check_regex(
			$column,
			$value,
			$log,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		$check_unique = $this->check_unique(
			$table,
			$column,
			$value,
			$id,
			$log,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		return $check_value && $check_regex && $check_min_length && $check_unique;
		
	}
	
	/* private */
	function check_values ( $column, $value, $log = false, $function_name = 'Check Refers To Function', $function_description = '' )
	{
		if ( $column['null'] && is_null($value) )
		{	// We have a null value and this column allows nulls
			return true;
		}
		
		# Do we need to run
		if ( $column['multiple'] && !empty($column['values']) )
		{	// we should run
		} else {
			return true; // no check here
		}
		
		# Check values
		$possible_values = array_keys($column['values']);
		$values = explode('|', $value); // turn 'hello' into array('hello') or turn 'hello|bye' to array('hello', 'bye');
		$diff = array_diff($values, $possible_values);
		if ( empty($diff) )
			return true;
		$additional_data = $diff;
	
		# Fail
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				'This occured because the value was not one of the field\'s possible values of ['.var_export($column['values'], true).'].',
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Column: ['.			var_export($column, true)				.']'."\r\n".
				'Additonal Data: ['.	var_export($additional_data, true)		.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// ALSO FRIENDLY
				true
		);
		return false;
	}
	
	/* private */
	function check_range ( $column, $value, $log = false, $function_name = 'Check Refers To Function', $function_description = '' )
	{
		if ( $column['null'] && is_null($value) )
		{	// We have a null value and this column allows nulls
			return true;
		}
		
		# Do we need to run
		if ( is_null($column['range']) )
			return true; // don't need to run
		
		# Check range
		$range = $column['range'];
		
		if ( in_range($range['min'], $value, $range['max'], $range['type']) )
			return true; // pass
		
		$additional_data = $range;
		
		# Fail
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'The value you tried to set needs to be '.
						( isset($range['min']) && isset($range['max'])
						?	'between '.var_export($range['min'], true).' and '.var_export($range['max'], true)
						:	( isset($range['min'])
							?	'above '.var_export($range['min'], true)
							:	'below '.var_export($range['max'], true)
							)
						).
						' ('.$range['type'].').',
					'This occured because the value was not inside the field\'s given range of ['.var_export($column['range'], true).'].'
				),
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Column: ['.			var_export($column, true)				.']'."\r\n".
				'Additonal Data: ['.	var_export($additional_data, true)		.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// ALSO FRIENDLY
				true
		);
		return false;
	}
	
	/* private */
	function check_refers_to ( $column, $value, $log = false, $function_name = 'Check Refers To Function', $function_description = '' )
	{
		if ( $column['null'] && is_null($value) )
		{	// We have a null value and this column allows nulls
			return true;
		}
		
		$refers_to = $column['refers_to'];
		if ( is_null($refers_to) )
			return true;
		
		if ( is_string($value) )
			$value = trim($value);
		
		$r_table = $this->get_table($refers_to['table']);
		$r_column = $this->get_column($r_table, $refers_to['values_column']);
		
		$where = array(
			array($r_column, explode('|', $value), 'IN')
		);
		
		$total = $this->total(
			// TABLE
			$r_table,
			// WHERE
			$where,
			// LIMIT
			1
		);
		
		if ( !$this->status )
		{	// Doing the total failed
			return NULL; 
		}
		elseif ( $total )
		{	// The value exists
			return true;
		}
		
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'This occured because the value you tried to set didn\'t match an existing '.$r_column['title'].'.',
					'This occured because the value did not correspond to an existing entry in the ['.$r_table['nickname'].'.'.$r_column['nickname'].'] column.',
				),
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Refers To: ['.			var_export($refers_to, true)			.']'."\r\n".
				'Column: ['.			var_export($column, true)				.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)			."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// ALSO FRIENDLY
				true
		);
		return false;
	}
	
	/* private */
	function check_value ( $column, $value, $log = false, $function_name = 'Check Value Function', $function_description = '' )
	{	/**
		 * RETURNS
		 **
		 * TRUE: The value matched it's requirements, or if there were no requirements
		 * FALSE: Some values do not match their requirements
		 * NULL: An error occured
		 *
		 */
		
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		$failed = false;
		
		# ------------------------------
		# Check the value
		
		if ( $column['null'] && is_null($value) )
		{	// We have a null value and this column alows nulls
			return true;
		}
		
		switch ( $column['datatype'] )
		{
			case 'integer':
			case 'double':
				if ( is_numeric($value) )
					return true; // pass
				break;
			
			case 'bool':
				$real_value = real_value($value, /* bool */ true, /* null */ false, /* numeric */ true);
				if ( is_bool($real_value) || $real_value === 1 || $real_value === 0 )
					return true; // pass
				break;
			
			default:
				return true; // pass
				break;
		}
		
		if ( !isset($additional_data) )
			$additional_data = '';
		
		# Fail
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'The value you tried to set wasn\'t the type of value it should be.',
					'This occured because the value was not of the same type as that required of the field.'
				),
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Column: ['.			var_export($column, true)				.']'."\r\n".
				'Additonal Data: ['.	var_export($additional_data, true)		.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// ALSO FRIENDLY
				true
		);
		return false;
	}
	
	/* private */
	function check_regex( $column, $value, $log = false, $function_name = 'Check Regex Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Check the regex
		
		if ( is_string($value) )
			$value = trim($value);
		
		$regex = $column['regex'];
		
		if ( !$regex )
			return true;
		
		if ( preg_match($regex, $value) )
			return true;
		
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'The value you used for '.$column['title'].' didn\'t look like how it should.',
					'This occured because the value did not pass the field\'s given regex param of ['.var_export($regex, true).'].'
				),
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Regex: ['.				var_export($regex, true)				.']'."\r\n".
				'Column: ['.			var_export($column, true)				.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)			."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// ALSO FRIENDLY
				true
		);
		return false;
	}
	
	/* private */
	function check_min_length( $column, $value, $log = false, $function_name = 'Check Min Length Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Check the min length
		
		// var_export($column['nickname']);
		// var_export($value);
		// echo "<Br />";
		
		$value = trim($value);
		
		$min_length = $column['min_length'];
		
		if ( !$min_length )
			return true;
		
		if ( strlen($value) >= $min_length )
			return true;
		
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'The value you tried to set was too short, it needs to be at least '.var_export($min_length, true).' characters.',
					'This occured because the value was smaller than the field\'s required minimum length of '.var_export($min_length, true).'.'
				),
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Min Length: ['.		var_export($min_length, true)			.']'."\r\n".
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
		return false;
	}
	
	/* private */
	function check_max_length( $column, $value, $log = false, $function_name = 'Check Min Length Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Check the min length
		
		$value = trim($value);
		
		$max_length = $column['max_length'];
		
		if ( !$max_length )
			return true;
		
		if ( strlen($value) <= $max_length )
			return true;
		
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'The value you tried to set was too long, it needs to be smaller than '.var_export($max_length, true).' characters.',
					'This occured because the value was larger than the field\'s maximum length of '.var_export($max_length, true).'.'
				),
			// DETAILS
				'Value: ['.				var_export($value, true)				.']'."\r\n".
				'Min Length: ['.		var_export($min_length, true)			.']'."\r\n".
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
		return false;
	}
	
	/* private */
	function check_unique ( $table, $column, $value, $id = NULL, $log = false, $function_name = 'Check Unique Function', $function_description = '' )
	{	/**
		 * RETURNS
		 **
		 * TRUE: The value is unique (no duplicates)
		 * FALSE: The value is not unique (has duplicates)
		 * NULL: An error occured
		 *
		 */
		
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Check for duplicates
		
		# Check if we need to check for duplicates
		if ( !$column['unique'] /* unique is not required */ || $id === false /* row is new */ || $value === NULL /* value is blank */ )
		{	// We don't care about duplicates
			return true;
		}
		
		if ( is_string($value) )
			$value = trim($value);
		
		$where = array();
		$where[] = array($column,	$value);
		if ( !is_null($id) )
		$where[] = array('id', $id, '!=');
		
		$total = $this->total(
			// TABLE
			$table,
			// WHERE
			$where,
			// LIMIT
			1
		);
		
		if ( !$this->status )
		{	// Doing the total failed
			return NULL; 
		}
		elseif ( !$total )
		{	// Value is unique
			return true;
		
		}
		
		// One or more duplicates where found
		if ( $log )
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Setting the value for the field ['.$column['title'].'] failed.',
			// DESCRIPTION
				array(
					'The value you used already exists in our records.',
					'This occured because the field requires that all values are unique (that they do not already exist).'
				),
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
		return false;
		
	}
	
	// ==============================================
	
	/* private */
	function prepare_limit_text ( $limit, $function_name = 'Prepare Limit Text Function', $function_description = '')
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# BUT WE WILL FOR THIS
		$limit = $this->make_valid_limit($limit);
			
		# ------------------------------
		# Create the text
		
		$limit_text =
			empty($limit)
			?	''
			:	'LIMIT '.$limit.' ';
		
		return $limit_text;
	}
	
	/* private */
	function prepare_order_text ( $table, $order_info, $function_name = 'Prepare Order Text Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Prepare the order info
		
		$order_text = '';
		
		// Get the default stuff
		$default_order_column		= is_null($table['order_column']) ? NULL : $table['columns'][$table['order_column']];
		$default_order_direction	= $table['order_direction'];
		
		// Do we want to append the default order info
		$add_default_order_info		= !is_null($default_order_column);
		
		// Let's do some stuff
		if ( !is_null($order_info) )
		{	// Ok we have order info
		
			# Get the size
			$order_info_size = sizeof($order_info);
			
			# Prepare the where info
			for ( $i = 0, $n = $order_info_size; $i < $n; $i++ )
			{
				// Prepare variables
				$part = $order_info[$i];
				$column = $part[0];
				if ( !isset($part[1]) )	$part[1] = NULL;
				$direction = $part[1];
				
				if ( ends_with($column, '()') )
				{	// We have a function instead
					$order_text .= ', '.$column.' '.$direction;
					continue;
				}
				
				// Order column
				$column = $this->get_column($table, $column, $function_name, $function_description);
				if ( !$this->status )
					return NULL;
				
				if ( $add_default_order_info && $default_order_column['name'] == $column['name'] )
				{	// The default column is here, so we want to use the specified info
					$add_default_order_info = false;
				}
				
				// Order Direction
				$direction = $this->make_valid_direction($direction);
				
				// Append the info
				$order_text .= ', `'.$column['name'].'` '.$direction;
			}
			
		}
			
		if ( $add_default_order_info )
		{	// Do we want to add the default order info
			$order_text .= ', `'.$default_order_column['name'].'` '.$default_order_direction;
		}
		
		if ( !empty($order_text) )
		{	// There is some order info
			$order_text = trim($order_text,', ').' ';	// get rid of the extra ', '
			$order_text = 'ORDER BY '.$order_text;	// prepend order to text
		}
		
		return $order_text;
		
	}
	
	/* private */
	function prepare_where_text ( $table, $where_info, $function_name = 'Prepare Where Text Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# We are a private function, no need to check params
		
		# ------------------------------
		# Prepare the where info
		
		$where_text = '';
		
		if ( !is_null($where_info) )
		{	// Ok we have where info
		
			# Get the size
			$where_info_size = sizeof($where_info);
			
			// A variable so we know wether we need to add a logic operator or not
			$need_logic_operator = false;
			
			# Prepare the where info
			for ( $i = 0, $n = $where_info_size; $i < $n; $i++ )
			{
				$part = & $where_info[$i];
				if ( gettype($part) == 'array' )
				{	// We have a column and info
					$column = & $part[0];
					$value = & $part[1];
					if ( !isset($part[2]) )	$part[2] = NULL;
					$boolean_operator =		& $part[2];
					
					// Where column
					$column = $this->get_column(
						$table,
						$column,
						$function_name,
						$function_description
					);	if ( !$this->status )
						return NULL;
					
					// Where boolean operator (LIKE, =, ETC)
					$boolean_operator = $this->make_valid_boolean_operator($boolean_operator, $value);
					
					// Add logic operator if needed
					if ( $need_logic_operator )
						$where_text .= 'AND ';
					
					// Append the info
					$value = $this->prepare_value($value, $column, $boolean_operator); // prepare for insertion to query
					$where_text .= '`'.$column['name'].'` '.$boolean_operator.' '.$value.' ';
					
					// Make it so we need a logic operator now
					$need_logic_operator = true;
				
				} else
				{	// We have either a Logic Operator (AND, OR) or a Bracket.
					$part = $this->make_valid_where_part($part);
					if ( !empty($part) )
					{
						if ( $part == '(' )
						{	// Add logic operator if needed
							if ( $need_logic_operator )
								$where_text .= 'AND ';
							$need_logic_operator = false;
						
						} elseif ( $part == ')' )
						{	// We now need a logic operator
							$need_logic_operator = true;
						
						} else
						{	// We have a logic operator
							$need_logic_operator = false;
						}
						
						$where_text .= $part.' ';
					}
				}
			}
			
			if ( !empty($where_text) )
			{	// We have some $where_text
				$where_text = 'WHERE '.$where_text;		// prepend where to text
			}
			
		}
		
		return $where_text;
		
	}

	// ==============================================
	
	/* public */
	function get_table_title ( $table )
	{
		if ( $table = $this->get_table($table) )
			return $table['title'];
		return 'Failed to get Table Title';
	}
	
	/* public */
	function get_column_title ( $table, $column )
	{
		if ( $table = $this->get_table($table) )
			if ( $column = $this->get_column($table, $column) )
				return $column['title'];
		return 'Failed to get Column Title';
	}
	
	// ==============================================
	
	/* public */
	function correct_structure( & $structure )
	{
		# Set all the defaults
		$structure = array_merge($this->default_structure, $structure);
		
		# Set the FORCED variables
		$structure['tables_nicknames']	= array_keys($structure['tables']);
		$structure['tables_size']		= sizeof($structure['tables']);
		$structure['null_title']		= $this->get_null_title($structure, NULL, NULL);
		
		# Work with the tables now
		for ( $i = 0, $n = $structure['tables_size']; $i < $n; $i++ )
		{
			# Get the table
			$table_nickname = $structure['tables_nicknames'][$i];
			$table = & $structure['tables'][$table_nickname];
			$table['nickname'] = $table_nickname;

			# Make the table vlaid
			$this->correct_table($structure, $table);
			
			# Check if the table was valid
			if ( is_null($table) )
			{	// Remove the table from the table
				unset($structure['tables'][$table_nickname]);
				unset($structure['tables_nicknames'][$i]);
				$structure['tables_size']--;
			}
			
		}
		
		# Re-Make the tables_nicknames incase any values were removed
		$structure['tables_nicknames']	= array_values($structure['tables_nicknames']);
		
		# Return the new structure
		ksort($structure);
		return $structure;
	}
	
	// ==============================================
	
	function correct_table ( & $structure, & $table )
	{
		# Set the table defaults
		$table = array_merge($this->default_table, $table);
		
		# Set the FORCED variables
		$table['title'] = $this->get_title($table);
		$table['columns_nicknames']	= array_keys($table['columns']);
		$table['columns_size']		= sizeof($table['columns_nicknames']);
		$table['null_title']		= $this->get_null_title($structure, $table, NULL);
		
		# Now work with the columns
		for ( $i = 0, $n = $table['columns_size']; $i < $n; $i++ )
		{
			# Get the column
			$column_nickname = $table['columns_nicknames'][$i];
			$column = & $table['columns'][$column_nickname];
			$column['nickname'] = $column_nickname;
			$column['table_nickname'] = $table['nickname'];
			
			# Make the column valid
			$this->correct_column($structure, $table, $column);
			
			# Check if the column was valid
			if ( is_null($column) )
			{	// Remove the column from the table
				unset($table['columns'][$column_nickname]);
				unset($table['columns_nicknames'][$i]);
				$table['columns_size']--;
			}
		}
		
		# Re-Make the columns_nicknames incase any values were removed
		$table['columns_nicknames']	= array_values($table['columns_nicknames']);
		
		# Set the OPTIONAL variables
		if ( $table['order_column'] )
		{	// We have a order column
			if ( !isset($table['columns'][$table['order_column']]) )
				$table['order_column'] = NULL;
		} else
			$table['order_column'] = NULL;
		
		$table['order_direction']	= $this->make_valid_direction($table['order_direction']);
		$table['owner']				= $this->make_valid_owner($table['owner']);
		$table['type']				= $this->make_valid_table_type($table['type']);
		
		ksort($table);
		return $table;
	}
	
	function correct_column ( & $structure, & $table, & $column )
	{
		# Set the column defaults
		$column = array_merge($this->default_column, $column);
		
		# Set the FORCED variables
		$column['title']			= $this->get_title($column);
		$column['mysql_datatype']	= $this->get_mysql_datatype($column);
		$column['datatype']			= $this->get_datatype($column);
		$column['php_datatype']		= $this->get_php_datatype($column);
		$column['null']				= $this->get_null($column);
		$column['null_title']		= $this->get_null_title($structure, $table, $column);
		$column['default']			= $this->get_default($column);
		$column['required']			= $this->get_required($column);
		$column['max_length']		= $this->get_max_length($column);
		$column['refers_to']		= $this->make_valid_refers_to($column['refers_to']);
		$this->correct_values($column);
		$column['input']			= $this->get_input($column);
		
		if ( !empty($column['values']) || !empty($column['refers_to']) )
			$column['multiple'] = true;
		
		// var_export($column);
		if ( $column['input'] == 'password' )
		{
			$column['min_length'] = 6;
			$column['confirm'] = true;
		}
		elseif ( $column['datatype'] == 'text' || $column['datatype'] == 'varchar' )
		{	// We have string
			if ( is_null($column['min_length']) && $column['required'] )
			{	// If the column doesn't have a min length and it is required
				// Then lets give it a min length of 1
				$column['min_length'] = 1;
			}
		}
		
		# Set the OPTIONAL variables
		$things_to_make_valid = array('unique', 'regex', 'min_length', 'confirm', 'range');
		for ( $i = 0, $n = sizeof($things_to_make_valid); $i < $n; $i++ )
		{
			$thing = $things_to_make_valid[$i];
			$call = 'make_valid_'.$thing;
			$column[$thing] = $this->$call($column[$thing]);
		}
		
		ksort($column);
		return $column;
	}
	
	/* public */
	function correct_references_for_structure ( & $structure )
	{
		# Prepare the values
		for ( $i = 0, $n = $structure['tables_size']; $i < $n; $i++ )
		{
			# Get the table
			$table_nickname = $structure['tables_nicknames'][$i];
			$table = & $structure['tables'][$table_nickname];
		
			# Correct the values
			$this->correct_references_for_table($structure, $table);
		}
		
		return true;
	}
	
	/* public */
	function correct_references_for_table ( & $structure, & $table )
	{	
		# Now work with the columns
		for ( $i = 0, $n = $table['columns_size']; $i < $n; $i++ )
		{
			# Get the column
			$column_nickname = $table['columns_nicknames'][$i];
			$column = & $table['columns'][$column_nickname];
			
			# Correct the columns
			$this->correct_references_for_column($structure, $table, $column);
		}
		
		return true;
	}
	
	/* public */
	function correct_references_for_column ( & $structure, & $table, & $column )
	{
		# Correct the values
		$this->correct_references($structure, $table, $column);
		
		return true;
	}
	
	/* public */
	function correct_references ( & $structure, & $table, & $column )
	{	// We assume column is a valid column
		
		$refers_to = & $column['refers_to'];
		
		if ( empty($refers_to) )
			return true;

		# Lets add the referred_by value
		$r_table = $refers_to['table'];
		$r_column = $refers_to['values_column'];
		
		$r_table = & $structure['tables'][$r_table];
		$r_column = & $r_table['columns'][$r_column];
		
		
		if ( !in_array($r_column['nickname'], $r_table['referred_by_columns']) )
		{	// Add the refer_to column to it's tables referred_by_columns array
			$r_table['referred_by_columns'][] = $r_column['nickname'];
		}
		$r_column['referred_by'][$column['table_nickname']] = $column['nickname'];
		
		return true;
	}
	
	/* public */
	function correct_values ( & $column )
	{	// We assume column is a valid column
		
		$values = $column['values'];
		
		if ( empty($values) )
		{	// We don't have any values
			$column['values'] = NULL;
			$column['values_corrected'] = true;
			return true;
		}
		
		if ( $column['values_corrected'] )
		{	// We have already corrected the values
			return true;
		}
		
		# Let's correct the values
		$datatype = $column['datatype'];
		$new_values = array();
		
		# Correct the values
		$array_keys = array_keys($values);
		for ( $i = 0, $n = sizeof($array_keys); $i < $n; $i++ )
		{	// Get vars
			$value = $array_keys[$i];
			$title = $values[$value];
			
			// Let's correct those vars
			if ( is_integer($value) )
			{	// We have a normal array
				if ( $datatype == 'text' || $datatype == 'varchar' )
				{	// Set the value as the text value
					$value = $title;
					$title = ucfirst($title);
				}
				elseif ( $datatype == 'integer' || $datatype == 'double' )
				{	// We have a number
					if ( is_numeric($title) )
						$value = real_value($title);
				}
				else
				{	// 
					$title = $value;
				}
			}
			
			$new_values[$value] = $title;
		}
		
		/* REMOVED; null handling is not done be the db class
		if ( $null )
		{	// Add null to the values
			array_unshift($new_values, array('NULL', 'NULL'));
		}*/
		
		if ( empty($new_values) )
		{	// We don't have any values
			// $new_values[] = array(NULL, 'No values present');
			return NULL;
		}
		
		$column['values'] = $new_values;
		$column['values_corrected'] = true;
		
		return $new_values;
	}
	
	// ==============================================
		
	/* public */
	function get_value_title ( $table, $column, $value = NULL )
	{
		if ( is_null($value) )
		{
			$value = $column;
			$column = $table;
		}
		else
		{
			$column = $this->get_column($table, $column);
		}
		
		if ( !empty($column['values']) )
		{	// There are values
			if ( isset($column['values'][$value]) )
				return $column['values'][$value];
		}
		
		// We are still going
		if ( !empty($column['refers_to']) )
		{	// There is a reference
			$title = $this->search(
				// TABLE
				$column['refers_to']['table'],
				// COLUMNS
				$column['refers_to']['titles_column'],
				// WHERE
				array(
					array($column['refers_to']['values_column'], $value)
				),
				// LIMIT
				1
			);
			if ( !empty($title) )
				return $title;
		}
		
		return $value;
	}
	
	/* public */
	function add_refers_to_values ( & $column )
	{	// This function will add the contents of a referred column into the current columns values
		
		if ( empty($column['refers_to']) )
			return true;
		
		$refers_to = & $column['refers_to'];
		
		if ( $refers_to['values_added'] )
			return true;
		
		$values = & $column['values'];
		
		$table = $refers_to['table'];
		$values_column = $refers_to['values_column'];
		$titles_column = $refers_to['titles_column'];
		$where = $refers_to['where'];
		
		// do query to get values here
		$rows = $this->search(
			// TABLE
			$table,
			// COLUMNS
			array($values_column, $titles_column),
			// WHERE
			$where
		);
		
		for ( $i = 0, $n = sizeof($rows); $i < $n; $i++ )
		{
			$row = $rows[$i];
			
			$value = $row[$values_column];
			$title = $row[$titles_column];
			
			$values[$value] = $title;
		}
		
		$refers_to['values_added'] = true;
		
		return true;
	}
	
	function make_valid_refers_to ( $refers_to )
	{
		if ( is_array($refers_to) && !empty($refers_to['table']) && !empty($refers_to['values_column']) )
		{
			if ( empty($refers_to['titles_column']) )
				$refers_to['titles_column'] = $refers_to['values_column'];
			
			if ( empty($refers_to['where']) )
				$refers_to['where'] = NULL;
			
			$refers_to['values_added'] = false;
			
		} else
			$refers_to = NULL;
		
		return $refers_to;
	}
	
	/* private */
	function get_title ( $item )
	{
		$nickname_title = str_replace('_',' ',$item['nickname']);
		$nickname_title = ucwords($nickname_title);
		return $this->make_valid_string($item['title'], $nickname_title);
	}
	
	/* private */
	function get_null_title ( $structure, $table = NULL, $column = NULL )
	{
		$null_title = 'NULL';
		
		if ( !is_null($column) )
		{	// Column
			if ( empty($column['null_title']) )
				$null_title = $table['null_title'];
			else
				$null_title = $column['null_title'];
		}
		elseif ( !is_null($table) )
		{	// Table
			if ( empty($table['null_title']) )
				$null_title = $structure['null_title'];
			else
				$null_title = $table['null_title'];
		}
		else
		{	// Structure
			$null_title = $structure['null_title'];
		}
		
		return $null_title;
	}
	
	/* private */
	function get_null ( $column )
	{
		$type = $column['type'];
		
		$null = true;
		
		$matches = array();
		$count = preg_match('/^[^\']*( NOT NULL)/', $type, $matches);
		if ( $count != 0 )
		{	// There is a NOT NULL
			// $matches = array();
			// $count = preg_match('/^[^\']*(TIMESTAMP )/', $type, $matches);
			// if ( $count == 0 )
			// {	// There ins't a time stamp
				$null = false;
			// }
		}
		
		return $null;
	}
	
	/* private */
	function get_max_length ( $column )
	{
		if ( $column['mysql_datatype'] == 'year' )
			return 4;
		
		$type = $column['type'];
		
		$matches = array();
		$count = preg_match('/^[^\'\(]*\((.+)\)/', $type, $matches);
		$max_length = isset($matches[1]) ? real_value($matches[1]) : NULL;
		return $max_length;
	}
	
	/* private */
	function get_default ( $column )
	{
		$row_type = $column['type'];
		
		$matches = array();
		$count = preg_match('/^[^\']* DEFAULT [\']?([^\']*)/',$row_type,$matches);
		if ( isset($matches[1]) )
			$default = $matches[1];
		else
		{
			$default = NULL;
		}
		
		return $default;
	}
	
	/* private */
	function get_required ( $column )
	{
		// we assume column is valid
		$row_type = $column['type'];
		
		$required = true;
		if ( $column['null'] || !is_null($column['default']) )
		{	// The column can be null or has a default value
			$required = false;
		}
		else
		{
			// Check if there is a AUTO_INCREMENT
			$matches = array();
			$count = preg_match('/^[^\']*( AUTO_INCREMENT)/', $row_type, $matches);
			if ( $count != 0 )
			{	// There isn't a AUTO_INCREMENT, check if theres a time stamp
				$required = false;
			}
		}
		
		return $required;
	}
	
	/* private */
	function get_input ( $column )
	{
		if ( !is_null($column['input']) )
		{	// We already have one
			return $column['input'];
		}
		
		if ( $column['nickname'] == 'id'  )
		{	// The id field is not editable
			return 'text';
		}
		
		// Let's get it
		if ( !is_null($column['refers_to']) )
		{
			$input = 'dropdown';
		}
		elseif ( !is_null($column['values']) )
		{
			if ( is_null($column['range']) )
			{	// We have values and no range
				$input = 'dropdown';
			
			} else
			{	// We have a range and values
				$input = 'textbox';
			}
		}
		else
		{
			switch ( $column['datatype'] )
			{
				case 'bool':
					$input = 'checkbox';
					break;
				
				case 'text':
					$input = 'textarea';
					break;
				
				case 'datetime':
					if ( $column['mysql_datatype'] != 'year' )
					{
						$input = $column['mysql_datatype'];
						break;
					}
					
				case 'varchar':
				case 'integer':
				case 'double':
				default:
					$input = 'textbox';
					break;
			}
		}
		
		return $input;
	}
	
	// ==============================================
	
	/* private */
	function get_row_columns ( $table, $row, $function_name = 'Get Row Columns Function', $function_description = '' )
	{
		$columns = array();
		
		$row_columns = array_keys($row);
		
		for ( $i = 0, $n = sizeof($row); $i < $n; $i++ )
		{
			# Get Column
			$column_nickname = $row_columns[$i];
			$columns[] = & $this->get_column(
				$table,
				$column_nickname,
				$function_name,
				$function_description
			);	if ( !$this->status )
				return NULL;
		}
		
		return $columns;
	}
	
	/* public */
	function make_valid_value ( $table, $column, & $value )
	{
		if ( $column['multiple'] )
		{	// Handle file type, convert to string
			if ( is_array($value) )
			{
				$array = $value;
				for ( $i = 0, $n = sizeof($array); $i < $n; ++$i )
				{
					$value = & $array[$i];
					$value = str_replace('|', '', $value);
				}
				$value = implode('|', $array);
				unset($array);
			}
			if ( !empty($value) )
				$value = rtrim($value, '|');
		}
		$value = $this->real_value($value, $column);
		return true;
	}
	
	/* public */
	function make_valid_row ( $table, $row = NULL, $do_checks = true, $function_name = 'Correct Row Function', $function_description = '' )
	{
		$this->status = true;
		
		# ------------------------------
		# Prepare the variables
		
		$table = $this->get_table(
			$table,
			$function_name,
			$function_description
		);	if ( !$this->status )
			return NULL;
		
		# ------------------------------
		# Generate the row
		
		$generate = is_null($row);
		
		$new_row = array();
		
		for ( $i = 0, $n = $table['columns_size']; $i < $n; $i++ )
		{
			$column_nickname = $table['columns_nicknames'][$i];
			$column = $table['columns'][$column_nickname];
			
			if ( $generate )
			{
				$new_row[$column_nickname] = $column['default'];
			
			} elseif ( array_key_exists($column_nickname, $row) )
			{	// We have a specified value
				$value = $row[$column_nickname];
				$id = isset($row['id']) ? $row['id'] : NULL;
				$this->make_valid_value($table, $column, $value);
				
				if ( $do_checks )
				{	# Do the value checks
					$check = $this->check_all(
						$table,
						$column,
						$value,
						$id,
						false,
						$function_name,
						$function_description
					);	if ( !$this->status )
						return NULL;
						
					if ( !$check )
					{	// The values did not pass the check
						$this->Log->add(
							// TYPE
								'Error',
							// TITLE
								'Doing the Value Checks for a Query, in the Database Class\'s '.$function_name.', did not pass (the value failed the checks)',
							// DESCRIPTION
								$function_description,
							// DETAILS
								'Table Nickname: ['.	var_export($table['nickname'], true)	.']'."\r\n".
								'Column: ['.			var_export($column, true)		.']'."\r\n".
								'Value: ['.				var_export($value, true)				.']',
							// WHERE
								'Class: '.				get_class_heirachy($this, true)		."\r\n".
								'Filename: '.			basename(__FILE__)						."\r\n".
								'File: '.				__FILE__								."\r\n".
								'Function: '.			__FUNCTION__							."\r\n".
								'Line: '.				__LINE__
						);
						$this->status = false;
						return NULL;
					}
				}
				$new_row[$column_nickname] = $value;
				
			} elseif ( $do_checks && $column['required'] )
			{	// The column value is required so fail
				// We want to report an error
				$this->Log->add(
					// TYPE
						'Error',
					// TITLE
						'A row in the Database Class\'s '.$function_name.' did not have a value for the required field; '.$column['nickname'].'.',
					// DESCRIPTION
						'',
					// DETAILS
						'Row: ['.				var_export($row, true)					.']'."\r\n".
						'Column: ['.			var_export($column, true)				.']',
					// WHERE
						'Class: '.				get_class_heirachy($this, true)		."\r\n".
						'Filename: '.			basename(__FILE__)						."\r\n".
						'File: '.				__FILE__								."\r\n".
						'Function: '.			__FUNCTION__							."\r\n".
						'Line: '.				__LINE__
				);
				$this->status = false;
				return NULL;
				break;
			}
		}
		
		return $new_row;
	}
	
	/* public */
	function get_mysql_datatype ( $column )
	{
		$type = $column['type'];
		
		$matches = array();
		
		$end1 = strpos($type,'(');
		$end2 = strpos($type, ' ');
		if ( !$end1 && !$end2 )
			$type = $type;
		elseif ( !$end1 && $end2 )
			$type = substr($type,0,$end2);
		elseif ( $end1 && !$end2 )
			$type = substr($type,0,$end1);
		elseif ( $end1 < $end2 )
			$type = substr($type,0,$end1);
		else
			$type = substr($type,0,$end2);
		$type = strtolower($type);
		
		return $type;
	}
	
	function get_php_datatype ( $column )
	{
		$datatype = $column['datatype'];
		
		switch ($datatype)
		{
			case 'varchar':
			case 'text':
			case 'datetime':
			case 'blob':
				$php_datatype = 'string';
				break;
			
			case 'integer':
				$php_datatype = 'int';
				break;
				
			case 'double':
				$php_datatype = 'float';
				break;
			
			case 'bool':
				$php_datatype = 'bool';
				break;
			
			default:
				$php_datatype = 'unknown';
				break;
		}
		
		return $php_datatype;
	}
			
	function get_datatype ( $column )
	{
		$mysql_datatype = $column['mysql_datatype'];
		$mysql_datatype = strtoupper($mysql_datatype);
		
		switch ( $mysql_datatype )
		{
			case 'VARCHAR':
			case 'CHAR':
				$datatype = 'varchar';
				break;
				
			case 'TEXT':
			case 'TINYTEXT':
			case 'MEDIUMTEXT':
			case 'LONGTEXT':
			case 'ENUM':
			case 'SET':
				$datatype = 'text';
				break;
				
			case 'INT':
			case 'TINYINT':
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INTEGER':
			case 'BIGINT':
				$datatype = 'integer';
				break;
			
			case 'BOOL':
				$datatype = 'bool';
				break;
			
			case 'FLOAT':
			case 'DOUBLE':
			case 'DECIMAL':
			case 'NUMERIC':
				$datatype = 'double';
				break;
			
			case 'TIMESTAMP':
			case 'YEAR':
			case 'DATE':
			case 'TIME':
			case 'DATETIME':
				$datatype = 'datetime';
				break;
			
			case 'BLOB':
			case 'TINYBLOB':
			case 'MEDIUMBLOB':
			case 'LONGBLOB':
				$datatype = 'blob';
				break;
			
			default:
				$datatype = 'unknown';
				break;
		}
		
		return $datatype;
	}
	
	/* public */
	function make_valid_confirm ( $confirm )
	{
		if ( !is_bool($confirm) )
			$confirm = $this->default_column['confirm'];
		return $confirm;
	}
	
	/* public */
	function make_valid_unique ( $unique )
	{
		if ( !is_bool($unique) )
			$unique = $this->default_column['unique'];
		return $unique;
	}
	
	/* public */
	function make_valid_limit ( $limit )
	{
		if ( is_numeric($limit) )
		{	// All is good
			
		} elseif ( gettype($limit) == 'array' && is_numeric($limit[0]) && is_numeric($limit[1]) )
		{	// We got a offset
			$limit = $limit[0].', '.$limit[1];
			
		} elseif ( gettype($limit) == 'string' )
		{
			$limit = explode(',',$limit);
			if ( empty($limit) )
				$limit = '';
			else
				$limit = $limit[0].', '.trim($limit[1]);
		} else
		{
			$limit = '';
		}
		
		return $limit;
	}
	
	/* public */
	function make_valid_range ( $range )
	{
		if ( !isset($range['max']) && !isset($range['min']) )
			$range = $this->default_column['range'];
		return $range;
	}
	
	/* public */
	function make_valid_min_length ( $min_length )
	{
		$min_length = real_value($min_length);
		if ( !is_integer($min_length) )
			$min_length = $this->default_column['min_length'];
		return $min_length;
	}
	
	/* public */
	function make_valid_string ( $str, $default = '' )
	{
		if ( empty($str) || gettype($str) != 'string' )
		{
			$str = $default;
		}	
		return $str;
	}
	
	/* public */
	function make_valid_regex ( $regex )
	{
		return $this->make_valid_string($regex, $this->default_column['regex']);
	}
	
	/* public */
	function make_valid_owner ( $owner )
	{
		return $this->make_valid_string($owner, $this->default_table['owner']);
	}
	
	/* public */
	function make_valid_table_type ( $type )
	{
		return $this->make_valid_string($type, $this->default_table['type']);
	}
	
	/* public */
	function make_valid_direction ( $direction )
	{	
		switch ( $direction )
		{
			case 'ASC':
			case 'DESC':
				break;
			
			case 'asc':
			case 'ascending':
			case 'ASCENDING':
				$direction = 'ASC';
				break;
			
			case 'desc':
			case 'descending':
				$direction = 'DESC';
				break;
				
			default:
				$direction = $this->default_table['order_direction'];
				break;
		}
		
		return $direction;
	}
	
	// ==============================================
	
	/* public */
	function make_valid_where_part ( $part )
	{
		$bracket = $this->make_valid_bracket($part);
		if ( $part === $bracket )
			return $bracket;
		else
			return $this->make_valid_logic_operator($part);
	}
	
	/* public */		
	function make_valid_bracket ( $bracket, $default = '' )
	{
		switch ($bracket)
		{
			case '(':
			case ')':
				break;
			
			default:
				$bracket = $default;
				break;
		}
		return $bracket;
	}
	
	/* public */
	function make_valid_logic_operator ( $operator, $default = 'AND' )
	{
		switch ( $operator )
		{
			case 'OR':
			case 'AND':
			case 'XOR':
				break;
			
			case 'or':
			case '||':
				$operator = 'OR';
				break;
				
			case 'and':
			case '&&':
				$operator = 'AND';
				break;
			
			case 'xor':
				$operator = 'XOR';
				break;
			
			default:
				$operator = $default;
				break;
		}
		
		return $operator;
	}
	
	/* public */
	function make_valid_boolean_operator ( $operator, $value = 'blah' )
	{	// Updated 7:22, 10/07/2007
		switch ( $operator )
		{
			case '=':
			case 'LIKE':
			case 'IS':
				if ( is_null($value) )
					$operator = 'IS';
				break;
			
			case '!=':
			case 'NOT':
			case 'NOT LIKE':
			case 'IS NOT':
				if ( is_null($value) )
					$operator = 'IS NOT';
				break;
			
			case '>':
			case '>=':
			case '<':
			case '<=':
			case '<>':
			case 'IN':
			case 'REGEXP':
				break;
			
			default:
				if ( is_null($value) )
					$operator = 'IS';
				else
					$operator = '=';
				break;
		}
		
		return $operator;
	}
	
	
	function real_value ( $value, $column = NULL )
	{
		if ( $column === NULL )
			return real_value($value);
		
		return real_value($value,
			/* bool */
			$column['php_datatype'] === 'bool',
			/* null */
			$column['null'],
			/* numeric */
			$column['php_datatype'] === 'int' || $column['php_datatype'] === 'float'
		);
	}
	
	function prepare_value ( $value, $column = NULL, $boolean_operator = NULL )
	{	// Prepares the value for insert into a query
		if ( !is_null($boolean_operator) )
			$boolean_operator = $this->make_valid_boolean_operator($boolean_operator);
		
		$value = $this->real_value($value, $column);
		
		switch( $boolean_operator )
		{
			case 'LIKE':
			case 'NOT LIKE':
				$value = str_replace('%', '\\%', $value);
				$value = str_replace('_', '\\_', $value);
				$value = '%'.$value.'%';
				break;
			
			case 'IN':
				$values = $value;
				if ( !is_array($values) )
					$values = array($values);
				for ( $i = 0, $n = sizeof($values); $i < $n; ++$i )
				{
					$values[$i] = $this->prepare_value($values[$i], $column);
				}
				return '('.implode(', ', $values).')';
				break;
				
			default:
				break;
		}
		
		switch ( true )
		{
			case is_null($value):
				// We have a value equivilant to null
				$value = 'NULL';
				break;
			
			case is_bool($value):
				$value = $value ? '1' : '0';
				break;
			
			case is_string($value):
				if ( ends_with($value, '()') ) /* we have a function */
				{	// It is safe, because it doesn't have a ' ' at the end.
					break;
				}
				
				// We have a normal string
				$value = trim($value);
				$value = mysql_real_escape_string($value);
				$value = '\''.$value.'\'';
				break;
			
			default:
				break;
		}
		
		return $value;
	}
		
	/* public */
	function escape ( $value, $column = NULL )
	{	// Used to prepare values before they are used with the database class - REQUIRED
		
		if ( !empty($value) && is_string($value) /* !is_numeric($value) && */ )
		{	// We have a string, so we need to be cautious
			$value = trim($value);
			if ( ends_with($value, '()') )
			$value .= ' '; // turn 'NOW()' to 'NOW() ' so that way it is not recognized as a function in prepare_value, everything is trimmed later so it isn't a problem
			
		}/* else
		{	// must be a number, so turn it into a number
			$value = real_value($value);
		}*/
		
		$value = $this->real_value($value, $column);
		
		return $value;
	}
	
	// ==============================================
	
	/* public */
	function is_table ( $table )
	{
		return
			gettype($table) == 'array'
			&& isset($table['name'])
			&& isset($table['columns'])
			;
	}
	
	/* public */
	function & get_table ( $table_nickname, $function_name = 'Get Table Function', $function_description = '' )
	{
		$this->status = true;
		
		if ( $this->is_table($table_nickname) )
		{	// We already have a table
			return $table_nickname;
		}
		
		if ( array_key_exists($table_nickname, $this->structure['tables']) )
		{	// Success
			return $this->structure['tables'][$table_nickname];
			
		}
		
		// Failure
		$this->Log->add(
			// TYPE
				'Error',
			// TITLE
				'Getting the Table for use in the '.$function_name.' in the Database class failed',
			// DESCRIPTION
				$function_description,
			// DETAILS
				'Table Nickname?: ['.	var_export($table_nickname, true)		.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__
		);
		$this->status = false;
		$null = NULL;
		return $null;
	}
	
	/* public */	
	function get_tables ( $tables_nicknames, $function_name = 'Get Tables Function', $function_description = '' )
	{	// Get an array of tables
	
		$this->status = true;
		
		if ( gettype($tables_nicknames) == 'array' )
		{	// We are an array
			if ( $this->is_table($tables_nicknames) )
			{	// We are already a table, so lets make us a table array
				return array($tables_nicknames);
				
			} elseif ( isset($tables_nicknames[0]) && $this->is_table($tables_nicknames[0]) )
			{	// We are already an array of tables
				return $tables_nicknames;
			}
		}
		
		if ( gettype($tables_nicknames) != 'array' )
		{	// If we wern't passed an array, let's make an array
			$tables_nicknames = array($tables_nicknames);
		}
		
		$tables = array();
		for ( $i = 0, $n = sizeof($tables_nicknames); $i < $n; $i++ )
		{
			$table_nickname = $tables_nicknames[$i];
			if ( $table = & $this->get_table($table_nickname, $function_name, $function_description) )
			{
				$tables[] = & $table;
				
			} else
			{	
				$this->status = false;
				return NULL;
			}
		}
		return $tables;
	}
	
	/* public */
	function is_column ( $column )
	{
		return
			gettype($column) == 'array'
			&& isset($column['name'])
			&& isset($column['type'])
			&& !isset($column['columns'])
			;
	}
	
	/* public */
	function & get_column ( $table, $column_nickname, $function_name = 'Get Column Function', $function_description = '' )
	{
		$this->status = true;
	
		if ( $this->is_column($column_nickname) )
		{	// We already have a column
			return $column_nickname;
		}
		
		# Make sure we are working with a table
		$table = & $this->get_table($table, false);
		if ( !$this->status )
		{
			$null = NULL;
			return $null;
		}
		
		// var_export($this->structure['tables']['products']['columns']['id']);
		// var_export($table['columns']['id']);
		// echo "\r\n";
		
		# Try and get the column
		if ( array_key_exists($column_nickname, $table['columns']) )
		{	// Success
			return $this->structure['tables'][$table['nickname']]['columns'][$column_nickname];
			
		}
		
		// Failure
		$this->Log->add(
			// TYPE
				'Warning',
			// TITLE
				'Getting the Column for use in the '.$function_name.' in the Database class failed',
			// DESCRIPTION
				$function_description,
			// DETAILS
				'Column Nickname?: ['.	var_export($column_nickname, true)				.']'."\r\n".
				'Table Nickname: ['.	var_export($table['nickname'], true)			.']'."\r\n".
				'Columns Nicknames: ['.	var_export($table['columns_nicknames'], true)	.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)				."\r\n".
				'Filename: '.			basename(__FILE__)								."\r\n".
				'File: '.				__FILE__										."\r\n".
				'Function: '.			__FUNCTION__									."\r\n".
				'Line: '.				__LINE__
		);
		$this->status = false;
		$null = NULL;
		return $null;
	}
	
	/* public */
	function get_columns ( $table, $columns_nicknames, $function_name = 'Get Columns Function', $function_description = '' )
	{	// Get an array of columns
	
		$this->status = true;
		
		if ( gettype($columns_nicknames) == 'array' )
		{	// We are an array
		
			if ( $this->is_column($columns_nicknames) )
			{	// We are a column, so stick it in a array
				return array($columns_nicknames);
				
			} elseif ( isset($columns_nicknames[0]) && $this->is_column($columns_nicknames[0]) )
			{	// We are already an array of columns
				return $columns_nicknames;
			}
		}
		
		if ( gettype($columns_nicknames) != 'array' )
		{	// If we wern't passed an array, let's make an array
			$columns_nicknames = array($columns_nicknames);
		}
	
		$columns = array();
		for ( $i = 0, $n = sizeof($columns_nicknames); $i < $n; $i++ )
		{
			$column_nickname = $columns_nicknames[$i];
			if ( $column = & $this->get_column($table, $column_nickname, $function_name, $function_description) )
			{
				$columns[] = & $column;
				
			} else
			{
				$this->status = false;
				return NULL;
			}
		}
		return $columns;
	}
}
