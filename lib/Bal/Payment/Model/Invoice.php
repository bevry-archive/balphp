<?php
class Bal_Payment_Model_Invoice extends Bal_Payment_Model_Abstract {
	
	/**
	 * Store of Model's data
	 * @var array $_data
	 */
	protected $_data = array(
		// required
		'id' 				=> null,
		'payment_status' 	=> null,
		'InvoiceItems' 		=> null,
		'Payer' 			=> null,
		
		// optional
		'amount' 			=> null,
		'currency' 			=> null,
		'handling' 			=> null,
		'shipping' 			=> null,
		'tax' 				=> null,
		'weight' 			=> null,
		'weight_unit' 		=> null,
		'discount_amount' 	=> null,
		'discount_rate' 	=> null
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
		
		# Ensure ID
		if ( !$id ) {
			$error = 'Invoice id must not be empty';
		}
		
		# Ensure Amount
		if ( $amount === null ) {
			$error = 'Invoice amount must have a value';
		}
		
		# Ensure Payment Status
		if ( !in_array($payment_status, array('created','pending','refunded','processed','completed','canceled_reversal','denied','expired','failed','voided','reversed')) ) {
			$error = 'Invoice status is not a valid value';
		}
		
		# Ensure Invoice Items
		if ( !$InvoiceItems ) {
			$error = 'Invoice must have at least one invoice item';
		}
		else {
			foreach ( $InvoiceItems as $InvoiceItem ) {
				$InvoiceItem->validate();
			}
		}
		
		# Ensure Payer
		if ( !$Payer ) {
			$error = 'Invoice must have a Payer';
		}
		else {
			$Payer->validate();
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
