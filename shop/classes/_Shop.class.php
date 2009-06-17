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

class Shop
{
	// Objects
	var $DB;
	var $Log;
	
	var $Customer = NULL;
	var $User = NULL;
	var $Cart = NULL;
	
	var $Settings;
	var $Regions;
	var $Region;
	
	var $Main_Menu;
	var $Sub_Menu = NULL;
	
	var $Emails_send_from = NULL;
	var $Emails_send_to = NULL;
	
	var $admin = false;
	
	function Shop ( $db )
	{		
		if ( empty($this->name) )
			$this->name = ucfirst(get_class($this)); // php4 get_class is lowercase :@ // get_class($this);
		
		// Check if in the admin
		@session_start();
		$this->admin = !empty($_SESSION['administrator']);
		
		// Create Objects
		$this->Log = new Log();
		$this->DB = new ShopDB($this->Log, $db);
		$this->DB->open();
	}
	
	function construct ( )
	{
		# Construct Cart
		$this->Cart = new Cart();
		
		# Login / Cosntruct User & Customer
		if ( get_param('login_Shop') )
		{
			$this->login();
		}
		elseif ( !empty($_SESSION['current_User_id']) )
		{
			$this->User = new User($_SESSION['current_User_id']);
			$this->Customer = new Customer($this->User->get('customer_id'));
		}
		
		# Test Account?
		if ( $this->Customer && $this->Customer->get('test_account') )
			setcookie('test', 'true', 0, '/' );
		else
			setcookie('test', NULL, 0, '/' );
		
		if ( get_param('logout_Shop') )
			$this->logout();
		
		# Global Settings
		$this->Settings = new ShopObject('global_settings', 1);
		
		# Regions
		$this->Regions = array();
		$regions = $this->DB->search(
			// TABLE
			'regions',
			// COLUMNS
			'id'
		);
		if ( empty($regions) || !$this->DB->status )
			die('Error with regions.');
		else
		for ( $i = 0, $n = sizeof($regions); $i < $n; ++$i )
		{
			$region = $regions[$i];
			$Region = new ShopObject('regions', $region);
			$this->Regions[$Region->get('abbreviation')] = $Region;
		}
		
		# Resolve Emails
		$Emails_send_from = '';
		if ( !is_null($this->Customer) )
		{	// https://educationresearch.com.au/admin/manageregion.php
			$mysql_result = mysql_query('SELECT `region_contact_email` FROM `region` WHERE `title` = \''.$this->Customer->get('state').'\' LIMIT 1');
			if ( mysql_num_rows($mysql_result) )
			{
				$row = mysql_fetch_array($mysql_result);
				$Emails_send_from = $row[0];
			}
		}
		if ( empty($Emails_send_from) )
		{	// https://educationresearch.com.au/admin/global_settings.php
			$Emails_send_from = $this->Settings->get('default_email');
		}
		if ( !empty($Emails_send_from) )
		{
			$this->Emails_send_to = $this->Emails_send_from = $Emails_send_from;
		}
		
		# Resolve Menus
		if ( !isset($GLOBALS['resolve_menus']) || $GLOBALS['resolve_menus'] )
			$this->resolve_menus();
		
		# Return
		return true;
	}
	
	function resolve_menus ( $main_menu = NULL, $sub_menu = NULL )
	{
		# Main Menu
		if ( !$main_menu )
		{	# Get the Main Menu
			if ( !empty($_REQUEST['menu_id']) )
				$main_menu = $_REQUEST['menu_id'];
			elseif ( !empty($_REQUEST['main_menu']) )
				$main_menu = $_REQUEST['main_menu'];
			else
				$main_menu = NULL;
		}
		# Get the Main Menu
		if ( !$main_menu )
		{	// Try detect from the url
			$dir = basename(DIR_PATH);
			if ( $dir === 'download' || $dir === 'newsletter' ) $dir = 'members';
			$main_menu = $this->DB->search('main_menus', 'id', array(array('directory', $dir)), 1);
		}
		else
			$main_menu = 1;
		# Create the Main Menu
		$Main_Menu = new ShopObject('main_menus', $main_menu);
		# Set the Main Menu
		$this->Main_Menu = $Main_Menu;
		# Clean
		unset($Main_Menu, $main_menu);
		
		# Sub Menu
		if ( !$sub_menu )
		{	// Get $sub_menu
			if ( !empty($_REQUEST['submenu_id']) )
				$sub_menu = $_REQUEST['submenu_id'];
			elseif ( !empty($_REQUEST['sub_menu']) )
				$sub_menu = $_REQUEST['sub_menu'];
			else
				$sub_menu = NULL;
		}
		# Setup Where
		$where = array(
			array('regions', REGION_CODE.'\|?', 'REGEXP'),
			'AND',
			array('main_menu', $this->Main_Menu->get('id'))
		);
		if ( $sub_menu )
		{
			$where[] = 'AND';
			$where[] = array('id', $sub_menu);
		}
		# Get the Sub Menu
		$sub_menu = $this->DB->search(
			// TABLE
			'sub_menus',
			// COLUMNS
			'id',
			// WHERE
			$where,
			// LIMIT
			1
		);
		# Create the Sub Menu
		$Sub_Menu = new ShopObject('sub_menus', $sub_menu);
		# Set the Sub Menu
		if ( $Sub_Menu->id )
			$this->Sub_Menu = $Sub_Menu;
		else
			$this->Sub_Menu = NULL;
		# Clean
		unset($sub_menu, $Sub_Menu, $where);
	}
	
	function login ( )
	{
		// Start our session
		@session_start();
	
		$email = get_param('User_email');
		$password = get_param('User_password');
		
		if ( empty($email) || empty($password) )
			return false;
		
		$user = $this->DB->search(
			// TABLE
			'users',
			// COLUMNS
			'id',
			// WHERE
			array(
				array('email',		$email),
				array('password',	$password)
			),
			// LIMIT
			1
		);
		
		if ( empty($user) )
		{
			$this->Log->add(
				// TYPE
					'notice',
				// TITLE
					'Logging in the user ['.$email.'] failed.',
				// DESCRIPTION
					'This occured because the login details were not correct.',
				// DETAILS
					'Email: ['.				var_export($email, true)				.']'."\r\n".
					'Password: ['.			var_export($password, true)				.']',
				// WHERE
					'Class: '.				get_class($this).' - '.__CLASS__		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// ALSO FRIENDLY
					true
			);
			return NULL;
		}
	
		// If they have good credentials then authorize them
		$_SESSION['current_User_id'] = $user;
		
		$this->User = new User($_SESSION['current_User_id']);
		$this->Customer = new Customer($this->User->get('customer_id'));
		
		return true;
	}
	
	function logout ( )
	{
		// Start our session
		@session_start();
		// $_SESSION['current_'.$this->name.'_id'] = NULL;
		// session_unregister('current_'.$this->name.'_id');
		unset($_SESSION['current_User_id']);
		session_destroy();
		setcookie('session_id', NULL, 0, '/');
		setcookie('PHPSESSID', NULL, 0, '/');
		
		$this->User = $this->Customer = NULL;
		return true;
	}
	
	function get_url_params ( $action, $id = false )
	{
		$url_params = '';
		
		if ( !empty($action) )
			$url_params .= $action.'_'.$this->name.'=true&amp;';
		
		if ( !empty($id) )
		{
			$url_params .= $this->name.'_id='.$id.'&amp;';
		}
		
		return $url_params;
	}
	
	// =================================================
	
	function get_signup_link ( $text = 'Signup', $redir_url = '' )
	{
		return '<a href="'.MEMBERS_URL.'signup_step1.php?redir_url='.urlencode(regen_url('redir_url='.$redir_url)).'" title="'.$text.'" >'.$text.'</a>';
	}
	
	function get_login_url ( $redir_url = '' )
	{
		return MEMBERS_URL.'login.php?redir_url='.urlencode(regen_url('redir_url='.$redir_url));
	}
	
	function get_login_link ( $text = 'Login', $redir_url = '' )
	{
		return '<a href="'.$this->get_login_url($redir_url).'" title="'.$text.'" >'.$text.'</a>';
	}
	
	function get_logout_link ( $text = 'Logout', $redir_url = '' )
	{
		return '<a href="'.MEMBERS_URL.'logout.php?'.$this->get_url_params('logout').'redir_url='.urlencode(regen_url('redir_url='.$redir_url)).'" title="'.$text.'" >'.$text.'</a>';
	}
	
	// =================================================
	
	function & get_Misc_text ( $id )
	{
		// Check how to load
		if ( gettype($id) === 'integer' )
		{	// Load the misc text for the web installation
			$Misc_text = new ShopObject('misc_texts', $id);
		}
		else
		{	// Load from code
			$Misc_text = new ShopObject('misc_texts');
			$Misc_text->load_from_unique('code', $id);
		}
		
		// Return
		return $Misc_text;
	}
	
	function display_misc_text ( $id, $display = true )
	{	// Implements dean's newb code into a leet function
		
		// Get
		$Misc_text = & $this->get_Misc_text($id);
		
		// Display
		if ( $Misc_text->id )
			$return = $Misc_text->get('body', 'raw');
		else
			$return = 'Could not load Misc Text ['.$id.'].';
		
		// What to do
		if ( $display )
			echo $return;
		return $return;
	}
	
	function display_header ( )
	{
		// echo $this->Log->display_header();
	}
	
	function display_footer ( )
	{
		$this->display_log();
	}
	
	function get_log ( )
	{
		return $this->Log->display(true);
	}
	
	function display_log ( $return = false )
	{	// Only for awesome people
		
		$force_display = DEBUG === 'force' || (DEBUG && $this->Log->display);
		$display = $this->Log->display;
		$result = NULL;
		
		if ( $force_display || $display )
		{
			# Get log
			$log = $this->get_log();
			
			# Figure it out
			if ( $force_display )
			{	# Display visually
				$result = '<pre style="overflow:scroll; height:500px; width:90%; margin-left:5%; margin-right:5%; padding:5px; border:1px solid grey; font-size:12px; font-family:"Courier New", Courier, monospace" >'.$log.'</pre>';
			}
			elseif ( !preg_match('/bot|search/i', $_SERVER['HTTP_USER_AGENT']) )
			{	# Email / Display message
				
				# Message
				$result = '<p style="color:red; font-style:italic; font-weight:bold;">Information about the error that occured has been emailed to the site administrator.</p>';
				
				# Email
				
				// Append extra needed stuff
				$log .=
					"\r\n".
					'SERVER URL: '.selfURL()."\r\n".
					'POST DATA: '.var_export($_POST, true)."\r\n".
					'GET DATA: '.var_export($_GET, true)."\r\n".
					'COOKIE DATA: '.var_export($_COOKIE, true)."\r\n".
					'SESSION DATA: '.var_export($_SESSION, true)."\r\n".
					'SESSION ID: '.var_export(session_id(), true)."\r\n".
					'SERVER DATA: '.var_export($_SERVER, true)."\r\n"
					;
					
				// Append regen url
				$log .= BASE_URL.'resend.php?'.
					'_POST='.urlencode(serialize($_POST)).'&'.
					'_GET='.urlencode(serialize($_GET)).'&'.
					'_FILES='.urlencode(serialize($_FILES)).'&'.
					'_COOKIE='.urlencode(serialize($_COOKIE)).'&'.
					'_SESSION='.urlencode(serialize($_SESSION)).'&'.
					'session_id='.urlencode(session_id()).'&'.
					'file='.urlencode($_SERVER['PHP_SELF'])."\r\n\r\n";
				
				// Mail config
				$headers = 'From: logs@balupton.com' . "\r\n" .
				    'Reply-To: logs@balupton.com' . "\r\n" .
				    'X-Mailer: PHP/' . phpversion();
				
				// Mail
				mail('logs@balupton.com', 'ERS: Error Log: '.date('r').(DEBUG ? ' (debug:'.to_string(DEBUG).')' : ''), $log, $headers);
			}
		}
		
		if ( !$return )
			echo $result;
		else
			return $result;
	}
	
	function display_category_select_admin ( $Category_type = NULL, $keep_params = false )
	{
		if ( $categories_size = $this->display_category_select($Category_type, $keep_params) == 0 )
		{
			$link = ADMIN_SHOP_URL.'category.php?Category_type='.$Category_type.'&amp;'; ?>
			<a href="<?php echo $link; ?>" title="Add a Category">
				<input type="button" value="Add a Category" onclick="document.location = '<?php echo $link; ?>';" />
			</a>
			<?php
		}
		else
		{
			$link = ADMIN_SHOP_URL.'category.php?Category_type='.$Category_type.'&amp;'; ?>
			<input type="button" value="Edit" onclick="var id = document.getElementById('category_select').value; if ( parseInt(id) == id ) document.location = '<?php
				echo $link; ?>load_Category=true&Category_id='+id; else alert('Select a category that you wish to perform this action on.');" />
			<?php $link = ADMIN_SHOP_URL.'category.php?Category_type='.$Category_type.'&amp;'; ?>
			<a href="<?php echo $link; ?>" title="Add">
				<input type="button" value="Add" onclick="document.location = '<?php echo $link; ?>';" />
			</a><?php $link = ADMIN_SHOP_URL.$Category_type.'.php?Category_type='.$Category_type.'&amp;'; ?>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="Delete" onclick="var category_select = document.getElementById('category_select'); var id = category_select.value; if ( parseInt(id) == id ) { var title = new String(category_select.options[category_select.selectedIndex].firstChild.nodeValue); if ( title.lastIndexOf('Certificates (') == 0 ) { alert('You cannot delete this category!'); return false; } if ( confirm('Are you sure you want to delete this Category?') ) document.location = '<?php
				echo $link; ?>delete_Category=true&Category_id='+id; } else alert('Select a category that you wish to perform this action on.');" />
			<?php
		}
	}
	
	function display_customer_type_select_admin ( $keep_params = false )
	{
		$table_nickname = 'customer_types';
		$table = $this->DB->get_table('customer_types');
		$table_title = $this->DB->get_table_title($table);
		
		$class_name = 'Customer_Type';
		$class_title = $this->DB->get_column_title($table, 'id');
		$title_column_nickname = 'name';
		
		$class_link = ADMIN_SHOP_URL.strtolower($class_name).'.php?';
		$parent_table_nickname = 'customers';
		$parent_link = ADMIN_SHOP_URL.$parent_table_nickname.'.php?';
		
		if ( $objs_size = $this->display_customer_type_select($keep_params) == 0 )
		{
			?><a href="<?php echo $class_link; ?>" title="Add a <?php echo $class_title; ?>">
				<input type="button" value="Add a <?php echo $class_title; ?>" onclick="document.location = '<?php echo $link; ?>';" />
			</a><?php
		}
		else
		{
			?><script type="text/javascript">
			function update_<?php echo $class_name; ?> ( )
			{
				var id = document.getElementById('<?php echo $class_name.'_id'; ?>').value;
				if ( parseInt(id) == id )
				{
					document.location = '<?php echo $class_link; ?>load_<?php echo $class_name; ?>=true&<?php echo $class_name; ?>_id='+id;
					return true;
				}
				else
				{
					alert('Select a <?php echo $class_title; ?> that you wish to perform this action on.');
					return false;
				}
				return false;
			}
			function delete_<?php echo $class_name; ?> ( )
			{
				var select_Obj = document.getElementById('<?php echo $class_name.'_id'; ?>');
				var id = select_Obj.value;
				if ( parseInt(id) == id )
				{
					var title = new String(select_Obj.options[select_Obj.selectedIndex].firstChild.nodeValue);
					var confirm_result = confirm('Are you sure you want to delete this <?php echo $class_title; ?>?');
					if ( confirm_result )
					{
						document.location = '<?php echo $parent_link; ?>delete_<?php echo $class_name; ?>=true&<?php echo $class_name; ?>_id='+id;
						return true;
					}
					else
					{
						alert('Select a <?php echo $class_title; ?> that you wish to perform this action on.');
						return false;
					}
				}
				return false;
			}
			</script>
			<input type="button" value="Edit" onclick="return update_<?php echo $class_name; ?>();" />
			<a href="<?php echo $class_link; ?>" title="Add">
				<input type="button" value="Add" onclick="document.location = '<?php echo $class_link; ?>';" />
			</a>&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="Delete" onclick="return delete_<?php echo $class_name; ?>();" /><?php
		}
	}
	
	function display_customer_type_select ( $keep_params = false )
	{
		if ( $keep_params )
			echo regenerate_params('form');
		
		$table_nickname = 'customer_types';
		$table = $this->DB->get_table('customer_types');
		$table_title = $this->DB->get_table_title($table);
		
		$class_name = 'Customer_Type';
		$class_title = $this->DB->get_column_title($table, 'id');
		$title_column_nickname = 'name';
		
		$parent_table_nickname = 'customers';
		$parent_link = ADMIN_SHOP_URL.$parent_table_nickname.'.php?';
		
		$current_id = get_param($class_name.'_id', NULL, true);
		
		?><strong><?php echo $class_title; ?> Selection:&nbsp;&nbsp;</strong><?php
	
		$objs = $this->DB->search(
			// TABLE
			$table,
			// COLUMNS
			'id',
			// WHERE
			NULL
		);
		
		$objs_size = sizeof($objs);
		for ( $i = 0, $n = $objs_size; $i < $n; $i++ )
		{
			$obj = $objs[$i];
			$Obj = new $class_name($obj);
			
			if ( $i == 0 )
			{
				?><select name="<?php echo $class_name.'_id'; ?>" id="<?php echo $class_name.'_id'; ?>" >
					<option value="">All <?php echo $table_title; ?></option>
					<option value="">---</option><?php
			}
			
			?><option value="<?php echo $Obj->id; ?>" <?php if ( $current_id == $obj->id ) { ?>selected="selected"<?php } ?>><?php
			
					$total = $this->DB->total(
						// TABLE
						$parent_table_nickname,
						// WHERE
						array(
							array(strtolower($class_name).'_id', $Obj->id)
						)
					);
					
					echo $Obj->get($title_column_nickname, 'htmlbody').' ('.$total.')';
					
			?></option><?php
			
			if ( $i == $n-1 )
			{
				?></select>
				<input type="submit" name="submit" value="Display" title="Display"  /><?php
			}
		}
		
		return $objs_size;
	}
	
	function display_category_select ( $Category_type = NULL, $keep_params = false )
	{
		if ( $keep_params )
			echo regenerate_params('form');
		
		?><strong>Category Selection:&nbsp;&nbsp;</strong><?php
		
		if ( is_null($Category_type) )
			$Category_type = get_param('Category_type', NULL, false);
		else
		{
			if ( $Category_type == 'purchases' || $Category_type == 'downloads' )
			{
				$table = 'products';
				$table = $this->DB->get_table($table);
				
				$mysql_query = 
					'SELECT DISTINCT '.
						'`'.$table['columns']['category_id']['name'].'` '.
					'FROM '.
						'`'.$table['name'].'` '.
					'WHERE '.
						'`'.$table['columns']['display']['name'].'` = 1 '.
						'AND '.
						'`'.$table['columns']['release_level']['name'].'` '.($Category_type == 'purchases' ? '=' : '!=').' \'purchase\' '
					;
				
				$mysql_result = mysql_query($mysql_query);
				
				echo mysql_error();
				
				$categories = array();
				
				while ( $row = mysql_fetch_array($mysql_result, MYSQL_NUM) )
					if ( !empty($row) && !empty($row[0]) )
					{
						$categories[] = $row[0];
					}
			}
		}
		
		
		if ( !isset($categories) )
		{
			$where = empty($Category_type)
				?	NULL
				:	array(
						array('type', $Category_type)
					)
				;
			
			$categories = $this->DB->search(
				// TABLE
				'categories',
				// COLUMNS
				'id',
				// WHERE
				$where
			);
		}
		
		$categories_size = sizeof($categories);
		for ( $i = 0, $n = $categories_size; $i < $n; $i++ )
		{
			$category = $categories[$i];
			$Category = new Category($category);
			$Category->display_select($Category_type, NULL, $i, $n);
		}
		
		return $categories_size;
	}
	
	
	function display_release_level_select ( $for = 'products', $keep_params = false )
	{
		$current_release_level = get_param('Product_release_level', '', true);
		
		if ( $keep_params && !empty($_SERVER['QUERY_STRING']) )
		{	// Insert the current params
			$params = $_SERVER['QUERY_STRING'];
			$params = explode('&', $params);
			$params = array_reverse($params);
			$params = array_unique($params);
			$params = array_values($params);
			for ( $i = 0, $n = sizeof($params); $i < $n; $i++ )
			{
				$param = $params[$i];
				$param = explode('=', $param);
				if ( empty($param) || empty($param[0]) )
					continue;
				?><input type="hidden" name="<?php echo $param[0]; ?>" value="<?php echo $param[1]; ?>"  /><?php
			}
		}
		
		?><strong>Release Level Selection:&nbsp;&nbsp;</strong><?php
		
		$table = $this->DB->get_table('products');
		$column = 'release_level';
		$column = $this->DB->get_column($table, $column);
		$values = $column['values'];
		if ( $for == 'downloads' )
		{	// Remove the purchase release level
			unset($values['purchase']);
		}
		
		$size = sizeof($values);
		$keys = array_keys($values);
		for ( $i = 0, $n = $size; $i < $n; $i++ )
		{
			$value = $keys[$i];
			$title = $values[$value];
			require(dirname(__FILE__).'/.display/'.$this->name.'/_display_release_level_select.php');
		}
		
		return $size;
	}
	
	function get_email_arguments ( $Objs )
	{
		if ( !is_array($Objs) )
			$Objs = array($Objs);
		
		$arguments = array();
		for ( $i = 0, $n = sizeof($Objs); $i < $n; $i++ )
		{
			$Obj = $Objs[$i];
			
			$obj_arguments = array();
			$columns = $Obj->table['columns_nicknames'];
			
			for ( $h = 0, $o = $Obj->table['columns_size']; $h < $o; $h++ )
			{
				$column_nickname = $columns[$h];
				$obj_arguments[$Obj->name.'_'.$column_nickname] = $Obj->get($column_nickname, 'htmlbody');
			}
			
			$arguments = array_merge($arguments, $obj_arguments);
		}
		
		return $arguments;
	}
	
	function send_email ( $email_code, $email_arguments, $send_to = NULL, $send_from = NULL )
	{
		// Blah
		if ( is_null($send_from) )
			$send_from = $this->Emails_send_from;
		
		// Send the email off
		switch ( $email_code )
		{
			case 'order_quote':
			case 'order_processing':
			case 'successful_purchase':
				// Takes in the following classes; Customer, Order
				
				// Load objects
				$Order = new Order($email_arguments['Order_id']);
				if ( !$Order->status )
				{	// Error
					$this->Log->add(
						// TYPE
							'error',
						// TITLE
							'Could not load the Order',
						// DESCRIPTION
							'',
						// DETAILS
							'Row Passed: ['.		var_export($email_arguments['Order_id'], true)		.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)			."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__
					);
					return $Order->Status;
					break;
				}
				
				$Customer = new Customer($email_arguments['Customer_id']);
				if ( !$Customer->status )
				{	// Error
					$this->Log->add(
						// TYPE
							'error',
						// TITLE
							'Could not load the Customer',
						// DESCRIPTION
							'',
						// DETAILS
							'Row Passed: ['.		var_export($email_arguments['Customer_id'], true)		.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)			."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__
					);
					return $Customer->Status;
					break;
				}
				
				$User = new User($email_arguments['User_id']);
				if ( !$User->status )
				{	// Error
					$this->Log->add(
						// TYPE
							'error',
						// TITLE
							'Could not load the User',
						// DESCRIPTION
							'',
						// DETAILS
							'Row Passed: ['.		var_export($email_arguments['User_id'], true)		.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)			."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__
					);
					return $User->Status;
					break;
				}
				
				// Load the other email
				if ( $email_code === 'order_processing' )
					$full_email_code = $email_code.'_customer__'.$Order->get('payment_method');
				else
					$full_email_code = $email_code.'_customer';
				
				// Load email
				$Email = new Email();
				$Email->load_from_unique('code', $full_email_code);
				
				// Check email
				if ( !$Email->status && $email_code === 'order_processing' )
				{	// Loading custom email failed, use default
					$full_email_code = $email_code.'_customer';
					$Email = new Email();
					$Email->load_from_unique('code', $full_email_code);
				}
				
				// Check email
				if ( !$Email->status )
				{
					$this->Log->add(
						// TYPE
							'error',
						// TITLE
							'Could not load the Email',
						// DESCRIPTION
							'',
						// DETAILS
							'Row Passed: ['.		var_export($full_email_code, true)		.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)			."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__
					);
					return $Email->status;
				}
				
				// Load the other email
				$full_email_code = $email_code.'_staff';
				$staff_Email = new Email();
				$staff_Email->load_from_unique('code', $full_email_code);
				if ( !$staff_Email->status )
				{
					$this->Log->add(
						// TYPE
							'error',
						// TITLE
							'Could not load the Email',
						// DESCRIPTION
							'',
						// DETAILS
							'Row Passed: ['.		var_export($full_email_code, true)		.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)			."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__
					);
					return $staff_Email->status;
				}
					
				// Replace the arguments
				$Email->replace_arguments($email_arguments);
				$staff_Email->replace_arguments($email_arguments);
				
				$Email->content = trim($Email->content);
				$staff_Email->content = trim($staff_Email->content);
				
				$append_text = 
					"\r\n".
					"\r\n".
					'Selected Products:'. "\r\n".
					"\r\n"
					;
				$Email->content .= $append_text;
				$staff_Email->content .= $append_text;
				
				$Order->load_relations('order_details');
				
				// Append the Order Details
				for ( $i = 0, $n = sizeof($Order->order_details); $i < $n; $i++ )
				{	// Cycle through the order
					
					$order_detail = $Order->order_details[$i];
					$Order_Detail = new Order_Detail($order_detail);
					if ( !$Order_Detail->status )
					{
						$this->Log->add(
							// TYPE
								'error',
							// TITLE
								'Could not load the Order Detail',
							// DESCRIPTION
								'',
							// DETAILS
								'Row Passed: ['.		var_export($order_detail, true)		.']',
							// WHERE
								'Class: '.				get_class_heirachy($this, true)			."\r\n".
								'Filename: '.			basename(__FILE__)						."\r\n".
								'File: '.				__FILE__								."\r\n".
								'Function: '.			__FUNCTION__							."\r\n".
								'Line: '.				__LINE__
						);
						return $Order_Detail->status;
					}
					
					$Product = new Product($Order_Detail->get('product_id'));
					if ( !$Product->status )
					{
						$this->Log->add(
							// TYPE
								'error',
							// TITLE
								'Could not load the Product',
							// DESCRIPTION
								'',
							// DETAILS
								'Row Passed: ['.		var_export($Order_Detail->get('product_id'), true)		.']',
							// WHERE
								'Class: '.				get_class_heirachy($this, true)			."\r\n".
								'Filename: '.			basename(__FILE__)						."\r\n".
								'File: '.				__FILE__								."\r\n".
								'Function: '.			__FUNCTION__							."\r\n".
								'Line: '.				__LINE__
						);
						return $Product->status;
				 	}
					
					// Append the Order Detail
					$append_text =
						'Product: '.$Product->get('title', 'htmlbody'). "\r\n".
				 		'Version: '.$Product->get('version', 'htmlbody'). "\r\n".
						( $email_code === 'successful_purchase' ? 'Serial:  '.$Order_Detail->get('serial', 'htmlbody').' (required during download process) '."\r\n" : '' ).
						'Price:   '.$Product->get('price', 'htmlbody'). "\r\n".
						"\r\n"
						;
					$Email->content .= $append_text;
					$staff_Email->content .= $append_text;
				}
				
				// Attach the total
				$append_text = 
					"\r\n".
					'GRAND TOTAL: '.$Order->get('price_grand_total', 'htmlbody').' (including GST)'.
					"\r\n".
					"\r\n"
					;
				$Email->content .= $append_text;
				$staff_Email->content .= $append_text;
				
				// Send the email
				if ( is_null($send_to) )
				{
					$send_to = $email_arguments['Customer_email'];
					if ( $email_arguments['Customer_email'] != $email_arguments['User_email'] )
						$send_to .= ', '.$email_arguments['User_email'];
				}
				$Email__result = $Email->send($send_to, $send_from);
				$staff_Email__result = $staff_Email->send($send_from, $send_from);
				return $Email__result && $staff_Email__result;
				break;
			
			case 'trial_download':
				$this->send_email($email_code.'_staff', $email_arguments, $send_to, $send_from);
				$this->send_email($email_code.'_customer', $email_arguments, $send_to, $send_from);
				return true;
				break;
				
			case 'trial_download_staff':
				if ( is_null($send_to) )
					$send_to = $send_from;
				
			case 'trial_download_customer':
				if ( is_null($send_to) )
					$send_to = $email_arguments['User_email']; // .', '.$email_arguments['Customer_email'];
				
			case 'general_signup_customer':
			case 'forgot_password':
				if ( is_null($send_to) )
					$send_to = $email_arguments['User_email'];
				
				
			case 'certification':
			default:
				if ( is_null($send_to) )
					die('no send to email specified');
				
				// Load the email
				$Email = new Email();
				$Email->load_from_unique('code', $email_code);
				if ( !$Email->status )
				{
					$this->Log->add(
						// TYPE
							'error',
						// TITLE
							'Could not load the Email',
						// DESCRIPTION
							'',
						// DETAILS
							'Row Passed: ['.		var_export($email_code, true)		.']',
						// WHERE
							'Class: '.				get_class_heirachy($this, true)			."\r\n".
							'Filename: '.			basename(__FILE__)						."\r\n".
							'File: '.				__FILE__								."\r\n".
							'Function: '.			__FUNCTION__							."\r\n".
							'Line: '.				__LINE__
					);
					return $Email->status;
				}
				
				// Replace the arguments
				$Email->replace_arguments($email_arguments);
				
				// Send the email
				if ( is_null($send_to) )
					$send_to = $email_arguments['User_email'];
				$Email->send($send_to, $send_from);
				return true;
				break;
		}
		
		// No $Email was loaded
		$this->Log->add(
			// TYPE
				'error',
			// TITLE
				'Could not load the Email.',
			// DESCRIPTION
				'No Email was Loaded.',
			// DETAILS
				'Row Passed: ['.		var_export($email_code, true)		.']',
			// WHERE
				'Class: '.				get_class_heirachy($this, true)			."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__
		);
		
		return false;
	}
	
	function order_Cart ( $payment_method, $name_on_card = NULL, $card_number = NULL, $expiry_month = NULL, $expiry_year = NULL, $cvn = NULL )
	{	// Order the cart
		
		// ------------------------------------------------
		// Do some checking
		
		// Prevent double purchase by limiting orders to a 30 second gap
		if ( empty($_SESSION['session_last_order']) )
		{	// First order
			$_SESSION['session_last_order'] = date('Y-m-d H:i:s');
		}
		else
		{	// Has ordered before
			// Find out when it was
			$last_order = strtotime($_SESSION['session_last_order']);
			$next_order = strtotime('+30 seconds', $last_order);
			$time_remaining = $next_order - time();
			if ( $time_remaining > 0 )
			{	// The user cannot make a purchase so soon
				$this->Log->add(
					// TYPE
					'error',
					// TITLE
					'You cannot make another order so soon after your previous one.',
					// DESCRIPTION
					'You can make your next order in: '.date('i \m\i\n\u\t\e\s \a\n\d s \s\e\c\o\n\d\s', $time_remaining).'. '/*. "\r\n".
					'Your last order was at: '.date('Y-m-d H:i:s', $last_order).'. '. "\r\n".
					'Your next order can be made after: '.date('Y-m-d H:i:s', $next_order).'. '*/
				);
				return false;
			}
			else
			{	// Was more than 30 seconds ago
				// Continue with this order
				$_SESSION['session_last_order'] = date('Y-m-d H:i:s');
			}
		}
		
		// ------------------------------------------------
		// Create the order
		
		// Get Products
		$products = array_keys($this->Cart->contents);
		$products_size = sizeof($products);
		if ( $products_size == 0 )
		{	// No products, so the order fails!!
			$this->Log->add(
				// TYPE
				'error',
				// TITLE
				'There are no products in your cart.',
				// DESCRIPTION
				''
			);
			return false;
		}
		
		// Create the order
		$Order = new Order();
		$Order->set('customer_id',		$this->Customer->id);
		$Order->set('user_id',			$this->User->id);
		$Order->set('price_postage',	0);
		$Order->set('price_sub_total',	$this->Cart->get('total_price'));
		$Order->set('price_grand_total',$this->Cart->get('total_price'));
		$Order->set('created',			'NOW()');
		$Order->set('payment_method',	$payment_method);
		$Order->set('eway_txn',			NULL);
		$Order->set('processed',		NULL);
		
		// Check
		if ( !$Order->status )
		{	// Failure
			$this->Log->add(
				// TYPE
				'error',
				// TITLE
				'Failed to set Order variables',
				// DESCRIPTION
				''
			);
			return false;
		}
		
		// Physically create the Order
		$Order->create(false /*false = silent*/);
		
		// Check
		if ( !$Order->status )
		{	// Failure
			$this->Log->add(
				// TYPE
				'error',
				// TITLE
				'Failed to create Order',
				// DESCRIPTION
				''
			);
			return false;
		}
		
		// Create the order details - orders for each individual product (instead of cart)
		for ( $i = 0, $n = $products_size; $i < $n; $i++ )
		{	// Cycle cart/order Products
			
			// Get Product
			$product = $products[$i];
			$Product = new Product($product);
			
			// We do not support quantities
			// Create Order Detail for Product
			$Order_Detail = new Order_Detail();
			$Order_Detail->set('order_id',		$Order->id);
			$Order_Detail->set('product_id',	$Product->id);
			$Order_Detail->set('product_title',	$Product->get('title'));
			$Order_Detail->set('teachers',		$this->Customer->get('teachers'));
			$Order_Detail->set('serial',		$Product->get('serial'));
			$Order_Detail->set('price',			$Product->get('price'));
			$Order_Detail->set('download_cap',	0);
			$Order_Detail->create(false);
			
			// Check
			if ( !$Order_Detail->status )
			{	// Failure
				// Delete the order (and order details)
				$Order->delete(false);
				
				// Log error
				$this->Log->add(
					// TYPE
					'error',
					// TITLE
					'Failed to create Order Detail',
					// DESCRIPTION
					''
				);
				return false;
			}
		}
		
		// ------------------------------------------------
		// Purchase the order
		
		// Not via credit card, so is a payment that requires administration
		if ( $payment_method != 'credit_card' )
		{	// Everything went well
		
			// Add a success log
			$this->Log->add(
				// TYPE
				'success',
				// TITLE
				'Your order was successfully processed.',
				// DESCRIPTION
				'You have been emailed a copy of the order details for future reference. '."\r\n".
				'You can also view the order details by going to the "Order History" page in the Members Area.'
			);
			
			// Clear the cart
			$this->Cart->clear(false);
			
			// Send off the Processing emails
			$arguments = $this->get_email_arguments(array($this->Customer, $this->User, $Order));
			if ( $payment_method == 'quote' )
				$this->send_email('order_quote', $arguments);
			else
				$this->send_email('order_processing', $arguments);
			
			// Finish
			return true;
		}
		
		// Must be by credit card, check credit card variables passed to us
		if ( empty($name_on_card) || empty($card_number) || empty($expiry_month) || empty($cvn) )
		{	// We need those details
		
			// Delete the order
			$Order->delete(false);
			
			$this->Log->add(
				// TYPE
				'error',
				// TITLE
				'Did not provide required credit card information',
				// DESCRIPTION
				"if ( empty($name_on_card) || empty($card_number) || empty($expiry_month) || empty($cvn) )"
			);
			
			// Finish
			return false;
		}
		
		// Credit card details exist
		// Purchase the order
		$Order->purchase($name_on_card, $card_number, $expiry_month, $expiry_year, $cvn);
		if ( !$Order->status )
		{	// The order failed to purchase
		
			// Delete the order (and order details)
			$Order->delete(false);
			
			// Log error
			$this->Log->add(
				// TYPE
				'error',
				// TITLE
				'Order failed to purcahse',
				// DESCRIPTION
				''
			);
			
			// Finish
			return false;
		}
		
		// ------------------------------------------------
		// Purchased so finish up
		
		// Clear the cart
		$this->Cart->clear(false);
		
		// Send off the Purchase Emails
		$arguments = $this->get_email_arguments(array($this->Customer, $this->User, $Order));
		if ( !$this->send_email('successful_purchase', $arguments) )
		{
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					'Could not send the Email',
				// DESCRIPTION
					'',
				// DETAILS
					'Row Passed: ['.		var_export('successful_purchase', true)		.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)			."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__
			);
		}
		
		// Life is swell
		return true;
	}
}

?>