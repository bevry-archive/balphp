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

class Order_Detail extends ShopObject
{
	// Authorize
	var $authorize_delete;
	var $authorize_update;
	
	// ===========================
	
	function Order_Detail ( $row = NULL, $perform_action = true )
	{	
		$this->name = 'Order_Detail';
		
		// Construct
		if ( empty($this->Shop) )
			$this->Shop = & $GLOBALS['Shop'];
		if ( empty($this->Log) )
			$this->Log = & $this->Shop->Log;
		
		// Authorize
		$this->authorize_delete = false;
		$this->authorize_update = $this->Shop->admin;
		
		// Finish Construction
		return $this->ShopObject('order_details', $row, $perform_action);
	}
	
	// ===========================
	
	function authorized ( $action, $error = true )
	{
		return true;
	}
	
	// ===========================
	
	/* public */
	function get_download_count ( $customer_id = NULL )
	{
		if ( is_null($customer_id) )
		{
			$order = $this->get('order_id');
			$Order = new Order($order);
			$customer_id = $Order->get('customer_id');
		}
		$value = $this->DB->total(
			// TABLE
			'downloads',
			// WHERE
			array(
				array('product_id',		$this->get('product_id')	),
				array('customer_id',	$customer_id				),
				// array('user_id',		$Order->get('user_id')		)
			)
		);
		return $value;
	}
	
	/* public */
	function get ( $column_nickname, $display = false, $default = '', $get_title = true )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		switch ( $column_nickname )
		{
			case 'download_count':
				$value = $this->get_download_count();
				break;
			
			case 'download_cap':
				$value = parent::get($column_nickname, $display, $default, $get_title);
				if ( !$value )
				{
					$mysql_query = 'SELECT `max_downloads` FROM `global_settings` LIMIT 1';
					$mysql_result = mysql_query($mysql_query) or die('MySql Error: '.mysql_error());
					$value = mysql_result($mysql_result, 0, 'max_downloads') or die('MySql Error: '.mysql_error());
				}
				break;
			
			case 'price':
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
