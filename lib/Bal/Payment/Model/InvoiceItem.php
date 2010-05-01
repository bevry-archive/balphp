<?php

class Bal_Payment_Model_InvoiceItem extends Bal_Payment_Model_Abstract {
	
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
		// required
		'id' 					=> null,
		'title' 				=> null,
		'amount' 				=> null,
		
		// optional
		'quantity' 				=> null,
		'shipping' 				=> null,
		'shipping_additional' 	=> null,
		'tax' 					=> null,
		'tax_rate' 				=> null,
		'weight' 				=> null,
		'weight_unit' 			=> null,
		'handling' 				=> null,
		'discount_amount' 		=> null,
		'discount_rate' 		=> null
	);
	
	/**
	 * Validate our Model
	 * @throws Bal_Exception
	 * @return true
	 */
	public static function validate ( ) {
		# Prepare
		$error = false;
		$InvoiceItem = $this;
		
		# Fetch
		$id = $InvoiceItem->id;
		$title = $InvoiceItem->title;
		$amount = $InvoiceItem->amount;
		$quantity = $InvoiceItem->quantity;
		$weight_unit = $InvoiceItem->weight_unit;
		
		# Ensure ID
		if ( !$id ) {
			$error = 'InvoiceItem id must not be empty';
		}
		
		# Ensure Title
		if ( !$title ) {
			$error = 'InvoiceItem title must not be empty';
		}
		
		# Ensure Amount
		if ( $amount === null ) {
			$error = 'InvoiceItem amount must have a value';
		}
		
		# Ensure Quantity
		if ( $quantity < 1 ) {
			$error = 'InvoiceItem quantity must be greater than one';
		}
		
		# Ensure Amount
		if ( !in_array($weight_unit, array(self::WEIGHT_UNIT_LBS,self::WEIGHT_UNIT_KGS)) ) {
			$error = 'InvoiceItem weight unit is not a valid value';
		}
		
		# Handle?
		if ( $error ) {
			throw new Bal_Exception(array(
				$error,
				'InvoiceItem' => $InvoiceItem
			));
		}
		
		# Return true
		return true;
	}
	
	/**
	 * Generates the total
	 * @throws Bal_Exception
	 * @return true
	 */
	public function getTotal ( ) {
		# Prepare
		$InvoiceItem = $this;
		
		# Fetch
		$amount = 				$InvoiceItem->amount;
		$quantity = 			$InvoiceItem->quanity;
		$shipping = 			$InvoiceItem->shipping;
		$shipping_additional = 	$InvoiceItem->shipping_additional;
		$tax = 					$InvoiceItem->tax;
		$tax_rate = 			$InvoiceItem->tax_rate;
		
		# Add Together
		$total =
			 	$shipping
		 	+	$quantity
				*	$amount
			+	($quantity-1)
				*	$shipping_additional;
	
		# Apply Tax Rate
		$total *= $tax_rate;
		
		# Add Final Tax
		$total += $tax;
		
		# Return total
		return $total;
	}
	
}
