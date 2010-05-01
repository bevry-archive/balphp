<?php
require_once 'Bal/Payment/Model/Abstract.php';
require_once 'Bal/Payment/Model/InvoiceItem.php';
require_once 'Bal/Payment/Model/Payer.php';

class Bal_Payment_Model_Invoice extends Bal_Payment_Model_Abstract {
	
	/**
	 * Possible values for weight units
	 * @var const WEIGHT_UNIT_LBS
	 * @var const WEIGHT_UNIT_KGS
	 */
	const WEIGHT_UNIT_LBS = 'lbs';
	const WEIGHT_UNIT_KGS = 'kgs';
	
	/**
	 * Store of Model's data
	 * @var array $_data
	 */
	protected $_data = array(
		// Required
			// Standard
			'id' 					=> null,
			'InvoiceItems' 			=> null,
			'Payer' 				=> null,
			'currency_code' 		=> null,
			'payment_status' 		=> null,
			
			'each_total'			=> null,
			'price_each_total'		=> null,
			'total'					=> null,
		
			'weight_unit'			=> null,	// overall
			
	 	// Optional
			'paid_at'				=> null,
			
			'handling'				=> null,	// overall
			'handling_each_total' 	=> null,
			'handling_total'		=> null,
			
			'tax_each_total'		=> null,
			'tax_total'				=> null,
			
			'weight_each_total'		=> null,
			'weight_total'			=> null,	// overall
			
			'discount'				=> null,	// overall
			'discount_rate'			=> null,	// overall
			'discount_each_total'	=> null,
			'discount_total'		=> null,
		
			'shipping' 				=> null,	// overall
			'shipping_each_total'	=> null,
			'shipping_total'		=> null,
	);
	
	/**
	 * Validate our Model
	 * @throws Bal_Exception
	 * @return true
	 */
	public function validate ( ) {
		# Prepare
		$Invoice = $this;
		
		# Prepare Checks
		$checks = array(
			'id'					=> !empty($Invoice->id),
			'total'					=> in_range(0, $Invoice->total, 				null, '<', true),
			'price_each_total' 		=> in_range(0, $Invoice->price_each_total, 		null, '<', true),
			'each_total' 			=> in_range(0, $Invoice->each_total, 			null, '<', true),
			'currency_code' 		=> !empty($Invoice->currency_code),
			'payment_status' 		=> in_array($Invoice->payment_status, 			array('created','pending','refunded','processed','completed','canceled_reversal','denied','expired','failed','voided','reversed')),
			'weight_unit' 			=> in_array($Invoice->weight_unit, 				array(self::WEIGHT_UNIT_LBS,self::WEIGHT_UNIT_KGS)),
			'InvoiceItems' 			=> !reallyempty($Invoice->InvoiceItems),
			'Payer' 				=> !reallyempty($Invoice->Payer),
			
			'paid_at'				=> $Invoice->paid_at === null || is_timestamp($Invoice->paid_at),
			'handling'				=> in_range(0, $Invoice->handling, 				null, '<=', true),
			'handling_each_total'	=> in_range(0, $Invoice->handling_each_total, 	null, '<=', true),
			'handling_total'		=> in_range(0, $Invoice->handling_total, 		null, '<=', true),
			'tax_each_total'		=> in_range(0, $Invoice->tax_each_total, 		null, '<=', true),
			'tax_total'				=> in_range(0, $Invoice->tax_total, 			null, '<=', true),
			'weight_each_total'		=> in_range(0, $Invoice->weight_each_total, 	null, '<=', true),
			'weight_total'			=> in_range(0, $Invoice->weight_total, 			null, '<=', true),
			'discount'				=> in_range(0, $Invoice->discount, 				null, '<=', true),
			'discount_rate'			=> in_range(0, $Invoice->discount_rate, 		1,    '<=', true),
			'discount_each_total'	=> in_range(0, $Invoice->discount_each_total, 	null, '<=', true),
			'discount_total'		=> in_range(0, $Invoice->discount_total, 		null, '<=', true),
			'shipping'				=> in_range(0, $Invoice->shipping, 				null, '<=', true),
			'shipping_each_total'	=> in_range(0, $Invoice->shipping_each_total, 	null, '<=', true),
			'shipping_total'		=> in_range(0, $Invoice->shipping_total, 		null, '<=', true)
		);
		
		# Validate Checks
		validate_checks($checks);
		
		# Check Payment Status
		$payment_status = $Invoice->payment_status;
		switch ( $payment_status ) {
			case 'awaiting':
				// Awaiting: Awaiting an action
				break;
				
			case 'canceled_reversal':
				// Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.
				throw new Bal_Exception(array(
					'Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.',
					'Invoice' => $Invoice
				));
				break;
			case 'denied':
				// Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.
				throw new Bal_Exception(array(
					'Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.',
					'Invoice' => $Invoice
				));
				break;
			case 'expired':
				// Expired: This authorization has expired and cannot be captured.
				throw new Bal_Exception(array(
					'Expired: This authorization has expired and cannot be captured.',
					'Invoice' => $Invoice
				));
				break;
			case 'failed':
				// Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.
				throw new Bal_Exception(array(
					'Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.',
					'Invoice' => $Invoice
				));
				break;
			case 'voided':
				// Voided: This authorization has been voided.
				throw new Bal_Exception(array(
					'Voided: This authorization has been voided.',
					'Invoice' => $Invoice
				));
				break;
			case 'reversed':
				// Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.
				throw new Bal_Exception(array(
					'Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.',
					'Invoice' => $Invoice
				));
				break;
			
			case 'created':
				// Created: A German ELV payment is made using Express Checkout.
			case 'pending':
				// Pending: The payment is pending. See pending_reason for more information.
			case 'refunded':
				// Refunded: You refunded the payment.
			case 'processed':
				// Processed: A payment has been accepted.
			case 'completed':
				// Completed: The payment has been completed, and the funds have been added successfully to your account balance.
				break;
			
			default:
				// Unkown: Unkown payment status.
				throw new Bal_Exception(array(
					'Unknown: Unknown payment status',
					'payment_status' => $payment_status,
					'Invoice' => $Invoice
				));
				break;
		}
		
		# Validate InvoiceItems
		$InvoiceItems = $Invoice->InvoiceItems;
		foreach ( $InvoiceItems as $InvoiceItem ) {
			$InvoiceItem->validate();
		}
		
		# Validate Payer
		$Payer = $Invoice->Payer;
		$Payer->validate();
		
		# Return true
		return true;
	}
	
	/**
	 * Apply the totals of the Invoice Item to this
	 * @return $this
	 */
	public function applyTotals ( ) {
		# Prepare
		$InvoiceItems = $this->InvoiceItems;
		
		# Overall Values
		$handling					= until_numeric($InvoiceItem->handling, 0.00);
		$tax						= until_numeric($InvoiceItem->tax, 0.00);
		$tax_rate					= until_numeric($InvoiceItem->tax_rate, 1.00);
		$discount					= until_numeric($InvoiceItem->discount, 0.00);
		$discount_rate				= until_numeric($InvoiceItem->discount_rate, 1.00);
		$shipping					= until_numeric($InvoiceItem->shipping, 0.00);
		
		# Calculate Each Totals
		foreach ( $InvoiceItems as $InvoiceItem ) {
			# Calculate
			$InvoiceItem->applyTotals();
			
			# Fetch
			$price_each_total 		+= until_numeric($InvoiceItem->price_total, 0.00);
			$handling_each_total	+= until_numeric($InvoiceItem->handling_total, 0.00);
			$tax_each_total			+= until_numeric($InvoiceItem->tax_total, 0.00);
			$weight_each_total		+= until_numeric($InvoiceItem->weight_total, 0.00);
			$discount_each_total	+= until_numeric($InvoiceItem->discount_total, 0.00);
			$shipping_each_total	+= until_numeric($InvoiceItem->shipping_total, 0.00);
			$each_total				+= until_numeric($InvoiceItem->total, 0.00);
		}
		
		# Add it all Together
		$handling_total 	= $handling + $handling_each_total;
		$tax_total 			= $tax_each_total;
		$weight_total 		= $weight_each_total;
		$shipping_total 	= $shipping + $shipping_each_total;
		$total 				= $each_total + $handling_total + $tax_total + $weight_total + $shipping_total;
		$discount_total 	= $discount + $total*$discount_rate;
		$total				-= $discount_total;
		
		# Apply Each Totals
		$InvoiceItem->price_each_total 		= $price_each_total;
		$InvoiceItem->handling_each_total 	= $handling_each_total;
		$InvoiceItem->tax_each_total 		= $tax_each_total;
		$InvoiceItem->weight_each_total 	= $weight_each_total;
		$InvoiceItem->discount_each_total 	= $discount_each_total;
		$InvoiceItem->shipping_each_total 	= $shipping_each_total;
		$InvoiceItem->each_total 			= $each_total;
		
		# Apply Totals
		$InvoiceItem->handling_total 	= $handling_total;
		$InvoiceItem->tax_total 		= $tax_total;
		$InvoiceItem->weight_total 		= $weight_total;
		$InvoiceItem->discount_total 	= $discount_total;
		$InvoiceItem->shipping_total 	= $shipping_total;
		$InvoiceItem->total 			= $total;
		
		# Return this
		return $this;
	}
	
	/**
	 * Setter for Payer
	 * @param array|object $payer
	 * @return $this
	 */
	public function setPayer ( $payer ) {
		# Prepare
		$Payer = new Bal_Payment_Model_Payer($payer);
		
		# Apply
		$this->_set('Payer',$Payer);
		
		# CHain
		return $this;
	}
	
	/**
	 * Setter for InvoiceItems
	 * @param array|object $invoiceitems
	 * @return $this
	 */
	public function setInvoiceItems ( $invoiceitems ) {
		# Prepare
		$InvoiceItems = array();
		
		# Check
		if ( $invoiceitems && !is_traversable($invoiceitems) ) {
			throw new Bal_Exception(array(
				'Passed InvoiceItems to set are not of a valid traversable type',
				'InvoiceItems' => $invoiceitems
			));
		}
		
		# Cycle
		foreach ( $invoiceitems as $invoiceitem ) {
			$InvoiceItem = new Bal_Payment_Model_InvoiceItem($invoiceitem);
			$InvoiceItems[] = $InvoiceItem;
		}
		
		# Apply
		$this->_set('InvoiceItems',$InvoiceItems);
		
		# CHain
		return $this;
	}
	
}
