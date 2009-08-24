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

class Order extends ShopObject
{
	var $order_details = array();
	
	// Authorize
	var $authorize_delete;
	var $authorize_update;
	
	// Blah
	// var $will_need_to_send_email = false;
	
	// ===========================
	
	function Order ( $row = NULL, $perform_action = true )
	{	
		// Construct
		if ( empty($this->Shop) )
			$this->Shop = & $GLOBALS['Shop'];
		if ( empty($this->Log) )
			$this->Log = & $this->Shop->Log;
		
		// Authorize
		$this->authorize_delete = false;
		$this->authorize_update = $this->Shop->admin;
		
		// Finish Construction
		return $this->ShopObject('orders', $row, $perform_action);
	}
	
	// ===========================
	
	/* public
	function load ( $add_log_on_success = false )
	{	// We get a row based on $this->row['id']
		
		$result = parent::load($add_log_on_success);
		
		if ( $result && $this->id && !$this->get('processed') )
		{
			$this->will_need_to_send_email = true;
		}
		
		echo '<pre>load:'."\r\n";
		var_dump($result, $this->will_need_to_send_email, $this->get('processed'), $result && $this->will_need_to_send_email && $this->get('processed') );
		echo '</pre>';
		
		return $result;
	} */
	
	/* public */
	function update ( $add_log_on_success = true )
	{	
	
		$result = parent::update($add_log_on_success);
		
		// echo '<pre>update:'."\r\n";
		// var_dump($result, $this->will_need_to_send_email, $this->get('processed'), $result && $this->will_need_to_send_email && $this->get('processed') );
		// echo '</pre>';
		
		/*
				if (	$result &&
				(	isset($_REQUEST['Order_processed_day']) &&
					(	!empty($_REQUEST['Order_processed_day']) ||
						(	!empty($_REQUEST['Order_processed_now']) &&
							$_REQUEST['Order_processed_now'] === 'TRUE'
						)
					)
				)
		*/
		
		if (	
			$result
			&&
			$this->get('processed')
			&&
			(
				(
					(!empty($_REQUEST['Order_processed_day']) || !empty($_REQUEST['Order_processed__day']))
					&&
					(!empty($_REQUEST['Order_processed_month']) || !empty($_REQUEST['Order_processed__month']))
					&&
					(!empty($_REQUEST['Order_processed_year']) || !empty($_REQUEST['Order_processed__year']))
				)
				||
				(!empty($_REQUEST['Order_processed_now']) && ($_REQUEST['Order_processed_now'] === 'TRUE' || $_REQUEST['Order_processed_now'] === 'true'))
				||
				(!empty($_REQUEST['Order_processed__now']) && ($_REQUEST['Order_processed__now'] === 'TRUE' || $_REQUEST['Order_processed__now'] === 'true'))
			)
			/*&& $this->will_need_to_send_email*/
		)
		{	// Send off the Purchase Emails
			$Customer = new Customer($this->get('customer_id'));
			$User = new User($this->get('user_id'));
			$arguments = $this->Shop->get_email_arguments(array($Customer, $User, $this));
			$this->Shop->send_email('successful_purchase', $arguments);
			$this->will_need_to_send_email = false;
		}
		
		return $result;
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
			case 'order_details':
				$total = $this->DB->total(
					// TABLE
					$table_nickname,
					// WHERE INFO
					array(
						array('order_id', $this->id)
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
			$this->load_relations('order_details');
			
			// Check if everything went well
			if ( !$this->check_status(false) )	return NULL;
			
			return true;
		}
		
		// Continue
		$table_nickname = strtolower($what);
		
		switch ( $what )
		{
			case 'order_details':
				$this->$what = array();
				$rows = $this->DB->search(
					// TABLE
					$table_nickname,
					// COLUMNS
					'id',
					// WHERE INFO
					array(
						array('order_id', $this->id)
					),
					// LIMIT
					NULL
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
			case 'price_postage':
			case 'price_sub_total':
			case 'price_grand_total':
				$value = parent::get($column, $display, $default, $get_title);
				
				// We want to return
				if ( $display != 'htmlbody' )
				{	// We just want the price
					break;
				}
				
				// We want to display
				$value = '$'.$value;
				
				break;
				
			default:
				$value = parent::get($column, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	// ===========================
	
	function display_input__processed ( $column, $field_name, $action, $value = NULL, $get_value = true, $params = array() )
	{	// Display the processed column input
		if ( $get_value )	$value = $this->get($column);
		extract($params);
		
		# Do a different input if we are processd or not
		if ( empty($value) )
		{	# We haven't been processed yet
			return $this->display_input__datetime ( $column, $field_name, $action, false, false, $params);
		}
		else
		{	# We have been processed
			return $this->display_input__text ( $column, $field_name, $action, $value, false, $params);
		}
		
		# Return
		return true;
	}
	
	// ===========================
	
	function purchase ( $name_on_card, $card_number, $expiry_month, $expiry_year, $cvn )
	{	// Purchase the order
		
		if ( $this->get('processed') )
		{	// We have already been purchased
			return true;
		}
		
		// assume all is ok
		// if ( empty($name_on_card) || empty($card_number) || empty($expiry_month) || empty($cvn) )
		//	return false;
		
		// Get vars
		$Customer = new Customer($this->get('customer_id'));
		$User = new User($this->get('user_id'));
		
		// Set e-way vars
		$firstname				= $User->get('firstname');
		$lastname				= $User->get('lastname');
		$email					= $Customer->get('email');
		
		// Set e-way vars
		$address				= $Customer->get('address'). ', '. $Customer->get('suburb'). ', '. $Customer->get('state'). ', '. $Customer->get('country');
		$postcode				= $Customer->get('postal_postcode');
		$invoice_description	= $Customer->get('name');
		$invoice_reference		= $this->id;
		$total_amount			= $this->get('price_grand_total');
		
		// Test
		$testing = (DEBUG || $Customer->get('test_account'));
		
		// Blah
		if ( $card_number === '0000000000000000' && $testing )
		{
			$eway_result = EWAY_TRANSACTION_OK;
			$payment_successfull = true;
	
			// Assign transaction responses from eway
			$ewayAmount = $total_amount * 100 /* convert dollars to cents */;
			$ewayReturnAmount = $total_amount;
			$ewayTrxn = '123';
			$ewayTrxnReference = 'Test Reference';
			$ewayAuthCode = 'Test Code';
		}
		else
		{
			// LIVE GATEWAY
			$eway = new EwayPayment( '11722163', 'https://www.eway.com.au/gateway_cvn/xmlpayment.asp' );
			
			// TESTING GATEWAY
			// $eway = new EwayPayment( '87654321', 'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp' );
			
			/*
			For eWay field specs see http://www.eway.com.au/support/xml_structure.aspx?p=cvn
			*/
			
			// Set the vars
			$eway->setCustomerFirstname( $firstname ); 
			$eway->setCustomerLastname( $lastname );
			$eway->setCustomerEmail( $email );
			
			$eway->setCustomerAddress( $address );
			$eway->setCustomerPostcode( $postcode );
			$eway->setCustomerInvoiceDescription( $invoice_description );
			$eway->setCustomerInvoiceRef( $invoice_reference );
			$ewayAmount = $total_amount * 100 /* convert dollars to cents */;
			$eway->setTotalAmount( $ewayAmount );
			
			$eway->setCardHoldersName( $name_on_card );
			$eway->setCardNumber( $card_number );
			$eway->setCardExpiryMonth( $expiry_month );
			$eway->setCardExpiryYear( $expiry_year );
			$eway->setCVN( $cvn );
			
			// Process
			$eway_result = $eway->doPayment();
			$payment_successfull = $eway_result === EWAY_TRANSACTION_OK;
			
			// Assign transaction responses from eway
			$ewayReturnAmount = $eway->getReturnAmount();
			$ewayTrxn = $eway->getTrxnNumber();
			$ewayTrxnReference = $eway->getTrxnReference();
			$ewayAuthCode = $eway->getAuthCode();
		}
		
		// Status
		$this->Log->add(
			// TYPE
				'status',
			// TITLE
				'Transaction Status',
			// DESCRIPTION
				'',
			// DETAILS
				'$testing: ['.				var_export($testing, true)				.']'."\r\n".
				'$total_amount: ['.			var_export($total_amount, true)			.']'."\r\n".
				
				'$ewayAmount: ['.			var_export($ewayAmount, true)			.']'."\r\n".
				'$ewayReturnAmount: ['.		var_export($ewayReturnAmount, true)		.']'."\r\n".
				'$ewayTrxn: ['.				var_export($ewayTrxn, true)				.']'."\r\n".
				'$ewayTrxnReference: ['.	var_export($ewayTrxnReference, true)	.']'."\r\n".
				'$ewayAuthCode: ['.			var_export($ewayAuthCode, true)			.']'."\r\n".
				
				'$eway_result: ['.			var_export($eway_result, true)			.']'."\r\n".
				'$payment_successfull: ['.	var_export($payment_successfull, true)	.']'."\r\n".
				'EWAY_TRANSACTION_OK: ['.	var_export(EWAY_TRANSACTION_OK, true)	.']',
			// WHERE
				'Class: '.				get_class($this).' - '.__CLASS__		."\r\n".
				'Filename: '.			basename(__FILE__)						."\r\n".
				'File: '.				__FILE__								."\r\n".
				'Function: '.			__FUNCTION__							."\r\n".
				'Line: '.				__LINE__,
			// FRIENDLY
				false
		);

		
		if ( $payment_successfull )
		{	// All is ok with the transaction so 
			
			// Set the order
			$this->set('processed', 'NOW()');
			$this->set('eway_txn', $ewayTrxn);
			$this->update(false);
			
			// Make the customer a member
			$Customer->set('is_member', true);
			$Customer->update(false);
			
			// Assign transaction responses from eway
			$this->Log->add(
				// TYPE
					'success',
				// TITLE
					'Transaction Successful',
				// DESCRIPTION
					'A email has been forwarded to you with the following information that should be kept for reference; '. "\r\n".
					'Eway Authorization Code: '. $ewayAuthCode. "\r\n".
					'Eway Transaction Number: '. $ewayTrxn. "\r\n".
					'Eway Transaction Reference: '. $ewayTrxnReference. "\r\n".
					'Order Number: '. $this->get('id', 'htmlbody'). "\r\n".
					'Total Cost: $'. $total_amount,
				// DETAILS
					'$testing: ['.				var_export($testing, true)				.']'."\r\n".
					'$total_amount: ['.			var_export($total_amount, true)			.']'."\r\n".
					
					'$ewayAmount: ['.			var_export($ewayAmount, true)			.']'."\r\n".
					'$ewayReturnAmount: ['.		var_export($ewayReturnAmount, true)		.']'."\r\n".
					'$ewayTrxn: ['.				var_export($ewayTrxn, true)				.']'."\r\n".
					'$ewayTrxnReference: ['.	var_export($ewayTrxnReference, true)	.']'."\r\n".
					'$ewayAuthCode: ['.			var_export($ewayAuthCode, true)			.']'."\r\n".
					
					'$eway_result: ['.			var_export($eway_result, true)			.']'."\r\n".
					'$payment_successfull: ['.	var_export($payment_successfull, true)	.']'."\r\n".
					'EWAY_TRANSACTION_OK: ['.	var_export(EWAY_TRANSACTION_OK, true)	.']',
				// WHERE
					'Class: '.				get_class($this).' - '.__CLASS__		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// FRIENDLY
					true
			);
			
			return true;
		}
		else
		{	// Transaction failed
		
			// Display error to the customer
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					'Transaction Unsuccessful',
				// DESCRIPTION
					'The following error was returned; '. "\r\n".
					'Error Code: '. $eway->getError(). "\r\n".
					'Error Message: '. $eway->getErrorMessage(),
				// DETAILS
					'$testing: ['.				var_export($testing, true)				.']'."\r\n".
					'$total_amount: ['.			var_export($total_amount, true)			.']'."\r\n".
					
					'$ewayAmount: ['.			var_export($ewayAmount, true)			.']'."\r\n".
					'$ewayReturnAmount: ['.		var_export($ewayReturnAmount, true)		.']'."\r\n".
					'$ewayTrxn: ['.				var_export($ewayTrxn, true)				.']'."\r\n".
					'$ewayTrxnReference: ['.	var_export($ewayTrxnReference, true)	.']'."\r\n".
					'$ewayAuthCode: ['.			var_export($ewayAuthCode, true)			.']'."\r\n".
					
					'$eway_result: ['.			var_export($eway_result, true)			.']'."\r\n".
					'$payment_successfull: ['.	var_export($payment_successfull, true)	.']'."\r\n".
					'EWAY_TRANSACTION_OK: ['.	var_export(EWAY_TRANSACTION_OK, true)	.']',
				// WHERE
					'Class: '.				get_class($this).' - '.__CLASS__		."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// FRIENDLY
					true
			);
			
			// Remove the order
			$this->delete(false);
			
			return NULL;
		}
		
		return true;
	}
	
	// ===========================
}

?>