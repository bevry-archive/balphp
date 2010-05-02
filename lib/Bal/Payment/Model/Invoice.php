<?php

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
			'id' 						=> null,
			'InvoiceItems' 				=> null,
			'Payer' 					=> null,
			'currency_code' 			=> null,
			'payment_status' 			=> null,
			
			'items_total'				=> null,
			'price_items_total'			=> null,
			'subtotal'					=> null,
			'total'						=> null,
		
			'weight_unit'				=> null,
			
	 	// Optional
			'paid_at'					=> null,
			
			'handling_invoice'			=> null,		
			'handling_invoice_total'	=> null,
			'handling_items_total' 		=> null,
			'handling_total'			=> null,
			
			'tax_items_total'			=> null,
			'tax_total'					=> null,
			
			'weight_items_total'		=> null,
			'weight_total'				=> null,
			
			'discount_invoice'			=> null,
			'discount_invoice_rate'		=> null,
			'discount_invoice_total'	=> null,
			'discount_items_total'		=> null,
			'discount_total'			=> null,
		
			'shipping_invoice' 			=> null,
			'shipping_invoice_total' 	=> null,
			'shipping_items_total'		=> null,
			'shipping_total'			=> null,
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
			'id'						=> !reallyempty($Invoice->id),
			'subtotal'					=> in_range(0, $Invoice->subtotal, 					null, '<', true),
			'total'						=> in_range(0, $Invoice->total, 					null, '<', true),
			'price_items_total' 		=> in_range(0, $Invoice->price_items_total, 		null, '<', true),
			'items_total' 				=> in_range(0, $Invoice->items_total, 				null, '<', true),
			'currency_code' 			=> !reallyempty($Invoice->currency_code),
			'payment_status' 			=> in_array($Invoice->payment_status, 				array('awaiting','created','pending','refunded','processed','completed','canceled_reversal','denied','expired','failed','voided','reversed')),
			'weight_unit' 				=> in_array($Invoice->weight_unit, 					array(self::WEIGHT_UNIT_LBS,self::WEIGHT_UNIT_KGS)),
			'InvoiceItems' 				=> !reallyempty($Invoice->InvoiceItems),
			'Payer' 					=> !reallyempty($Invoice->Payer),
			
			'paid_at'					=> $Invoice->paid_at === null || is_timestamp($Invoice->paid_at),
			
			'handling_invoice'			=> in_range(0, $Invoice->handling_invoice, 			null, '<=', true),
			'handling_invoice_total'	=> in_range(0, $Invoice->handling_invoice_total,	null, '<=', true),
			'handling_items_total'		=> in_range(0, $Invoice->handling_items_total, 		null, '<=', true),
			'handling_total'			=> in_range(0, $Invoice->handling_total, 			null, '<=', true),
			
			'tax_items_total'			=> in_range(0, $Invoice->tax_items_total, 			null, '<=', true),
			'tax_total'					=> in_range(0, $Invoice->tax_total, 				null, '<=', true),
			
			'weight_items_total'		=> in_range(0, $Invoice->weight_items_total, 		null, '<=', true),
			'weight_total'				=> in_range(0, $Invoice->weight_total, 				null, '<=', true),
			
			'discount_invoice'			=> in_range(0, $Invoice->discount_invoice, 			null, '<=', true),
			'discount_invoice_rate'		=> in_range(0, $Invoice->discount_invoice_rate, 	1,    '<=', true),
			'discount_invoice_total'	=> in_range(0, $Invoice->discount_invoice_total, 	null, '<=', true),
			'discount_items_total'		=> in_range(0, $Invoice->discount_items_total, 		null, '<=', true),
			'discount_total'			=> in_range(0, $Invoice->discount_total, 			null, '<=', true),
			
			'shipping_invoice'			=> in_range(0, $Invoice->shipping_invoice, 			null, '<=', true),
			'shipping_invoice_total'	=> in_range(0, $Invoice->shipping_invoice_total,	null, '<=', true),
			'shipping_items_total'		=> in_range(0, $Invoice->shipping_items_total, 		null, '<=', true),
			'shipping_total'			=> in_range(0, $Invoice->shipping_total, 			null, '<=', true)
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
		$Invoice = $this;
		
		
		# Overall Values
		$handling_invoice					= until_numeric($Invoice->handling_invoice, 0.00);
		$discount_invoice					= until_numeric($Invoice->discount_invoice, 0.00);
		$discount_invoice_rate				= until_numeric($Invoice->discount_invoice_rate, 0.00);
		$shipping_invoice					= until_numeric($Invoice->shipping_invoice, 0.00);
		
		# Apply Valid Overall Values
		$Invoice->handling_invoice 			= $handling_invoice;
		$Invoice->discount_invoice 			= $discount_invoice;
		$Invoice->discount_invoice_rate 	= $discount_invoice_rate;
		$Invoice->shipping_invoice 			= $shipping_invoice;
		
		
		# Prepare Each Totals
		$price_items_total 			= 
		$handling_items_total		= 
		$tax_items_total			= 
		$weight_items_total			= 
		$discount_items_total		= 
		$shipping_items_total		= 
		$items_total				= 0;
		
		# Calculate Each Totals
		$InvoiceItems = $this->InvoiceItems;
		foreach ( $InvoiceItems as $InvoiceItem ) {
			# Calculate
			$InvoiceItem->applyTotals();
			
			# Fetch
			$price_items_total 				+= until_numeric($InvoiceItem->price_total, 0.00);
			$handling_items_total			+= until_numeric($InvoiceItem->handling_total, 0.00);
			$tax_items_total				+= until_numeric($InvoiceItem->tax_total, 0.00);
			$weight_items_total				+= until_numeric($InvoiceItem->weight_total, 0.00);
			$discount_items_total			+= until_numeric($InvoiceItem->discount_total, 0.00);
			$shipping_items_total			+= until_numeric($InvoiceItem->shipping_total, 0.00);
			$items_total					+= until_numeric($InvoiceItem->total, 0.00);
		}
		
		# Apply Each Totals
		$Invoice->price_items_total 		= $price_items_total;
		$Invoice->handling_items_total 		= $handling_items_total;
		$Invoice->tax_items_total 			= $tax_items_total;
		$Invoice->weight_items_total 		= $weight_items_total;
		$Invoice->discount_items_total 		= $discount_items_total;
		$Invoice->shipping_items_total 		= $shipping_items_total;
		$Invoice->items_total 				= $items_total;
		
		
		# Add it all Together
		$handling_invoice_total				= $handling_invoice;
		$handling_total 					= $handling_invoice_total + $handling_items_total;
		$tax_total 							= $tax_items_total;
		$weight_total 						= $weight_items_total;
		$shipping_invoice_total				= $shipping_invoice;
		$shipping_total 					= $shipping_invoice_total + $shipping_items_total;
		$subtotal 							= $items_total + $handling_invoice_total + $shipping_invoice_total;
		$discount_invoice_total 			= $discount_invoice + $subtotal*$discount_invoice_rate;
		$discount_total						= $discount_invoice_total + $discount_items_total;
		$total								= $subtotal-$discount_invoice_total;
		
		# Apply Totals
		$Invoice->handling_invoice_total 	= $handling_invoice_total;
		$Invoice->handling_total 			= $handling_total;
		$Invoice->tax_total 				= $tax_total;
		$Invoice->weight_total 				= $weight_total;
		$Invoice->discount_invoice_total 	= $discount_invoice_total;
		$Invoice->discount_total 			= $discount_total;
		$Invoice->shipping_invoice_total 	= $shipping_invoice_total;
		$Invoice->shipping_total 			= $shipping_total;
		$Invoice->subtotal 					= $subtotal;
		$Invoice->total 					= $total;
		
		
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
