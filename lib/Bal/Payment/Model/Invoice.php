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
			'id' 					=> null,
			'InvoiceItems' 			=> null,
			'Payer'					=> null,
			'currency_code'			=> null,
			'paid_at'				=> null,
			'payment_status'		=> null, // returned payment status from paypal
			'payment_error'			=> null, // returned payment error from paypal
			'payment_fee'			=> null, // returned payment fee from gateway
			'weight_unit'			=> null, // weight unit to use
		
			'price_all_total'		=> null, // the price for all  items	- Discount, Handling, Shipping, Tax
			'price_all_total_d'		=> null, // the price for all  items	+ Discount
			'price_all_total_dhs'	=> null, // the price for all  items	+ Discount, Handling, Shipping
			'price_total'			=> null, // the price for all  items	+ Discount, Handling, Shipping, Tax
			
		// Optional
			'handling_all_total'	=> null, // total handling for all items
			'tax_all_total'			=> null, // total tax for all items
			'weight_all_total'		=> null, // total weight for all times
			'discount_all_total'	=> null, // total discount for all items
			'shipping_all_total'	=> null, // total shipping for all items
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
			'id'					=> !reallyempty($Invoice->id),
			'InvoiceItems' 			=> !reallyempty($Invoice->InvoiceItems),
			'Payer' 				=> !reallyempty($Invoice->Payer),
			'currency_code' 		=> !reallyempty($Invoice->currency_code),
			'paid_at'				=> $Invoice->paid_at === null || is_timestamp($Invoice->paid_at),
			'payment_status' 		=> in_array($Invoice->payment_status, 				array('awaiting','created','pending','refunded','processed','completed','canceled_reversal','denied','expired','failed','voided','reversed')),
			'payment_fee'			=> in_range(0, $Invoice->payment_fee, 				null, '<=', true),
			'weight_unit' 			=> in_array($Invoice->weight_unit, 					array(self::WEIGHT_UNIT_LBS,self::WEIGHT_UNIT_KGS)),
			
			'price_all_total'		=> is_numeric($Invoice->price_all_total),
			'price_all_total_d'		=> is_numeric($Invoice->price_all_total_d),
			'price_all_total_dhs' 	=> is_numeric($Invoice->price_all_total_dhs),
			'price_total' 			=> is_numeric($Invoice->price_total),
			
			'handling_all_total'	=> in_range(0, $Invoice->handling_all_total, 		null, '<=', true),
			'tax_all_total'			=> in_range(0, $Invoice->tax_all_total, 			null, '<=', true),
			'weight_all_total'		=> in_range(0, $Invoice->weight_all_total, 			null, '<=', true),
			'discount_all_total'	=> in_range(0, $Invoice->discount_all_total, 		null, '<=', true),
			'shipping_all_total'	=> in_range(0, $Invoice->shipping_all_total, 		null, '<=', true)
		);
		
		# Validate Checks
		validate_checks($checks);
		
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
		
		# Force Valid Inputs
		$Invoice->payment_fee 				= until_numeric($Invoice->payment_fee, 0.00);
		
		# Prepare Totals
		$Invoice->price_all_total 			= 
		$Invoice->price_all_total_d 		= 
		$Invoice->price_all_total_dhs 		= 
		$Invoice->price_total 				= 
		$Invoice->handling_all_total		= 
		$Invoice->tax_all_total				= 
		$Invoice->weight_all_total			= 
		$Invoice->discount_all_total		= 
		$Invoice->shipping_all_total		= 0;
		
		# Calculate Each Totals
		$InvoiceItems = $this->InvoiceItems;
		foreach ( $InvoiceItems as $InvoiceItem ) {
			# Calculate
			$InvoiceItem->applyTotals();
			
			# Fetch
			$Invoice->price_all_total 		+= until_numeric($InvoiceItem->price_all_total, 	0.00);
			$Invoice->price_all_total_d 	+= until_numeric($InvoiceItem->price_all_total_d, 	0.00);
			$Invoice->price_all_total_dhs 	+= until_numeric($InvoiceItem->price_all_total_dhs, 	0.00);
			$Invoice->price_total 			+= until_numeric($InvoiceItem->price_total, 		0.00);
			$Invoice->handling_all_total	+= until_numeric($InvoiceItem->handling_all_total, 	0.00);
			$Invoice->tax_all_total			+= until_numeric($InvoiceItem->tax_all_total, 		0.00);
			$Invoice->weight_all_total		+= until_numeric($InvoiceItem->weight_all_total, 	0.00);
			$Invoice->discount_all_total	+= until_numeric($InvoiceItem->discount_all_total, 	0.00);
			$Invoice->shipping_all_total	+= until_numeric($InvoiceItem->shipping_all_total, 	0.00);
		}
		
		# Now round everything - This is because paypal will discard any values that are not rounded
		$fields = array(
			'payment_fee',
			'price_all_total','price_all_total_d','price_all_total_dhs','price_total','handling_all_total','tax_all_total','discount_all_total','shipping_all_total'
		);
		foreach ( $fields as $field ) {
			$Invoice->$field = round($Invoice->$field, 2);
		}
		
		# Chain
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
		if ( !is_traversable($invoiceitems) ) {
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
