<?php
class Bal_Payment_Model_Invoice extends Bal_Payment_Model_Abstract {
	
	/**
	 * Store of Model's data
	 * @var array $_data
	 */
	protected $_data = array(
		// required
		'id' 				=> null,
		'InvoiceItems' 		=> null,
		'Payer' 			=> null,
		
		// optional - the below fields are ALL totals
		'amount' 			=> null,
		'currency' 			=> null,
		'handling' 			=> null,
		'shipping' 			=> null,
		'tax' 				=> null,
		'weight' 			=> null,
		'weight_unit' 		=> null,
		'discount_amount' 	=> null,
		'discount_rate' 	=> null,
		
		'paid_at'			=> null,
		'payment_status' 	=> null,
	);
	
	/**
	 * Validate our Model
	 * @throws Bal_Exception
	 * @return true
	 */
	public function validate ( ) {
		# Prepare
		$error = false;
		$Invoice = $this;
		
		# Fetch
		$id = $Invoice->id;
		$payment_status = $Invoice->payment_status;
		$InvoiceItems = $Invoice->InvoiceItems;
		$Payer = $Invoice->Payer;
		
		# Check ID
		if ( !$id ) {
			$error = 'Invoice id must not be empty';
		}
		
		# Check Amount
		if ( $amount === null ) {
			$error = 'Invoice amount must have a value';
		}
		
		# Check Payment Status
		if ( !in_array($payment_status, array('created','pending','refunded','processed','completed','canceled_reversal','denied','expired','failed','voided','reversed')) ) {
			$error = 'Invoice status is not a valid value';
		}
		
		# Check Invoice Items
		if ( !$InvoiceItems ) {
			$error = 'Invoice must have at least one invoice item';
		}
		else {
			foreach ( $InvoiceItems as $InvoiceItem ) {
				$InvoiceItem->validate();
			}
		}
		
		# Check Payer
		if ( !$Payer ) {
			$error = 'Invoice must have a Payer';
		}
		else {
			$Payer->validate();
		}
		
		# Check Payment Status
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
		
		# Handle?
		if ( $error ) {
			throw new Bal_Exception(array(
				$error,
				'Invoice' => $Invoice
			));
		}
		
		# Return true
		return true;
	}
	
	/**
	 * Setter for Payer
	 * @param array|object $payer
	 * @return $this;
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
	 * @return $this;
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
		foreach ( $invoiceitems as $invoiceItem ) {
			$InvoiceItem = new Bal_Payment_Model_InvoiceItem($invoiceitem);
			$InvoiceItems[] = $InvoiceItem;
		}
		
		# Apply
		$this->_set('InvoiceItems',$InvoiceItems);
		
		# CHain
		return $this;
	}
	
}
