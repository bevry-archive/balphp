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

require_once(dirname(__FILE__).'/../../core/functions/_params.funcs.php');

class Cart
{
	var $Log;
	
	var $contents = array(); /* = array(
		$Product_id => $quantity,
	); */
	
	// var $storage = 'session';
	
	var $name = 'Cart';
	
	function Cart ( )
	{
		$this->Log = new Log();
	
		// $this->Customer = & $GLOBALS['current_Customer'];
		$this->load();
		
		if ( get_param('add_Product') )
		{
			if ( $Product_id = get_param('Product_id') )
			{
				$this->add_Product($Product_id);
				url_remove_params($this->get_url_params('add', $Product_id));
			}
		}
		
		if ( get_param('remove_Product') )
		{
			if ( $Product_id = get_param('Product_id') )
			{
				$this->remove_Product($Product_id);
				url_remove_params($this->get_url_params('remove', $Product_id));
			}
		}
		
		if ( get_param('clearCart') )
		{
			$this->clear();
		}
	}
	/* public */
	function get ( $column_nickname, $display = false, $default = '' )
	{	
		switch ( $column_nickname )
		{
			case 'total_price':
				$total_price = 0;
				$contents_keys = array_keys($this->contents);
				for ( $i = 0, $n = sizeof($contents_keys); $i < $n; $i++ )
				{
					$product = $contents_keys[$i];
					$quantity = $this->contents[$product];
					$Product = new Product($product);
					$total_price += $Product->get('price')*$quantity;
				}
				
				// We want to return
				if ( !$display )
				{	// We just want the price
					$value = $total_price;
					break;
				}
				
				// We want to display
				$result = '$'.$total_price;
					
				$value = $result;
				break;
			
			default:
				die('Unknown get thing; '.$column_nickname);
				break;
		}
		
		return $value;
	}
	
	function get_url_params ( $action, $id = NULL )
	{	// add, remove, clear
		$params = '';
		
		switch ( $action )
		{
			case 'clear':
				$params .= 'clear_Cart=true&amp;';
				break;
			
			case 'add':
			case 'remove':
				if ( empty($id) )
					break;
				$params .= $action.'_Product=true&amp;Product_id='.$id;
				break;
			
			default:
				die('unknown action; '.$action);
		}
		
		return $params;
	}
	
	function load ( )
	{	// Saves the cookie
		@session_start();
		if ( empty($_SESSION[$this->name.'_contents']) /* || !session_is_registered($this->name.'_contents') */ )
		{
			// session_register($this->name.'_contents');
			$_SESSION[$this->name.'_contents'] = serialize($this->contents);
		}
		
		$contents = unserialize($_SESSION[$this->name.'_contents']);
		$contents = empty($contents) ? array() : $contents;
		$this->contents = $contents;
		
		return true;
	}
	
	function save ( )
	{	// Saves the cookie
		@session_start();
		// session_register($this->name.'_contents');
		
		$_SESSION[$this->name.'_contents'] = serialize($this->contents);
		
		return true;
	}
	
	function contains ( $Product )
	{
		$Product = new Product($Product);
		
		$contains = isset($this->contents[$Product->id]);
		
		return $contains;
	}
	
	function clear ( $add_log_on_success = true )
	{
		$this->contents = array();
		$this->save();
		
		$this->Log->add(
			// TYPE
				'success',
			// TITLE
				'Successfully cleared the '.$this->name.'.',
			// DESCRIPTION
				'',
			// DETAILS
				'',
			// WHERE
				'Class: '.				get_class($this).' - '.__CLASS__		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// FRIENDLY
				$add_log_on_success
		);
		
		return true;
	}
	
	function add_Product ( $Product )
	{
		$Product = new Product($Product);
		if ( isset($this->contents[$Product->id]) )
		{
			// $this->contents[$Product->id]++;
			$this->Log->add(
				// TYPE
					'warning',
				// TITLE
					'The '.$Product->name.' ['.$Product->get('title').'] has already been added to the '.$this->name.'.',
				// DESCRIPTION
					'View your cart <a href="'.PRODUCTS_URL.'cart.php">here</a>, Proceed to the checkout <a href="'.MEMBERS_URL.'checkout.php">here</a>.',
				// DETAILS
					'Product ID: ['.		var_export($Product->id, true)			.']',
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
		else
		{
			$this->contents[$Product->id] = 1;
			
			$this->Log->add(
				// TYPE
					'success',
				// TITLE
					'Successfully added the '.$Product->name.' ['.$Product->get('title').'] to the '.$this->name.'.',
				// DESCRIPTION
					'View your cart <a href="'.PRODUCTS_URL.'cart.php">here</a>, Proceed to the checkout <a href="'.MEMBERS_URL.'checkout.php">here</a>.',
				// DETAILS
					'Product ID: ['.		var_export($Product->id, true)			.']',
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
		
		$this->save();
		
		return $this->contents[$Product->id];
	}
	
	function remove_Product ( $Product )
	{
		$Product = new Product($Product);
		if ( isset($this->contents[$Product->id]) )
		{
			// if ( $this->contents[$Product->id] == 1 )
				unset($this->contents[$Product->id]);
			//else
			//	$this->contents[$Product->id]--;
			
			$this->Log->add(
				// TYPE
					'success',
				// TITLE
					'Successfully removed the '.$Product->name.' ['.$Product->get('title').'] from the '.$this->name.'.',
				// DESCRIPTION
					'View your cart <a href="'.PRODUCTS_URL.'cart.php">here</a>, Proceed to the checkout <a href="'.MEMBERS_URL.'checkout.php">here</a>.',
				// DETAILS
					'Product ID: ['.		var_export($Product->id, true)			.']',
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
		
		$this->save();
		
		return isset($this->contents[$Product->id]) ? $this->contents[$Product->id] : 0;
	}
	
}

?>