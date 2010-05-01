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
		// Required
			'id' 					=> null,
			'title' 				=> null,
			'price_total'			=> null,
			'price_each'			=> null,
			'total'					=> null,
			'quantity'				=> null,
		
		// Optional
			'handling_each' 		=> null,
			'handling_total'		=> null,
			
			'tax_each'				=> null,
			'tax_rate'				=> null,
			'tax_total'				=> null,
			
			'weight_each'			=> null,
			'weight_total'			=> null,
			'weight_unit'			=> null,
			
			'discount_each'			=> null,
			'discount_rate'			=> null,
			'discount_total'		=> null,
		
			'shipping_first' 		=> null,
			'shipping_additional'	=> null,
			'shipping_total'		=> null,
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
		$id				= $InvoiceItem->id;
		$title 			= $InvoiceItem->title;
		$price_total 	= $InvoiceItem->price_total;
		$price_each 	= $InvoiceItem->price_each;
		$total 			= $InvoiceItem->total;
		$quantity 		= $InvoiceItem->quantity;
		$weight_unit 	= $InvoiceItem->weight_unit;
		
		# Ensure ID
		if ( !$id ) {
			$error = 'InvoiceItem id must not be empty';
		}
		
		# Ensure Title
		if ( !$title ) {
			$error = 'InvoiceItem title must not be empty';
		}
		
		# Ensure Price Total
		if ( $price_total === null ) {
			$error = 'InvoiceItem price_total must have a value';
		}
		
		# Ensure Price Each
		if ( $price_each === null ) {
			$error = 'InvoiceItem price_each must have a value';
		}
		
		# Ensure Total
		if ( $total === null ) {
			$error = 'InvoiceItem total must have a value';
		}
		
		# Ensure Quantity
		if ( $quantity < 1 ) {
			$error = 'InvoiceItem quantity must be greater than one';
		}
		
		# Ensure Weight Unit
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
	 * Apply the Totals to the Model
	 * @return $this
	 */
	public function applyTotals ( ) {
		# Prepare
		$InvoiceItem = $this;
		
		# Fetch + Force Valid Inputs
		$price_each 			= until_numeric($InvoiceItem->price_each, 0.00);
		$quantity				= until_integer($InvoiceItem->quantity, 1.00);
		$handling_each			= until_numeric($InvoiceItem->handling_each, 0.00);
		$tax_each				= until_numeric($InvoiceItem->tax_each, 0.00);
		$tax_rate				= until_numeric($InvoiceItem->tax_rate, 1.00);
		$weight_each			= until_numeric($InvoiceItem->weight_each, 0.00);
		$discount_each			= until_numeric($InvoiceItem->discount_each, 0.00);
		$discount_rate			= until_numeric($InvoiceItem->discount_rate, 1.00);
		$shipping_first			= until_numeric($InvoiceItem->shipping_first, 0.00);
		$shipping_additional 	= until_numeric($InvoiceItem->shipping_additional, 0.00);
		
		# Calculate
		$price_total 		= $quantiy*$price_each;
		$handling_total 	= $quantity*$handling_each;
		$tax_total 			= $quantity*$tax_each + $price_total*$tax_rate;
		$weight_total 		= $quantity*$weight_each;
		$shipping_total 	= $shipping_first + ($quantity-1)*$shipping_additional
		$total 				= $price_total + $handling_total + $tax_total + $weight_total + $shipping_total;
		$discount_total 	= $quantity*$discount_each + $total*$discount_rate;
		$total				-= $discount_total;
		
		# Apply Totals
		$InvoiceItem->price_total 		= $price_total;
		$InvoiceItem->handling_total 	= $handling_total;
		$InvoiceItem->tax_total 		= $tax_total;
		$InvoiceItem->weight_total 		= $weight_total;
		$InvoiceItem->discount_total 	= $discount_total;
		$InvoiceItem->shipping_total 	= $shipping_total;
		$InvoiceItem->total 			= $total;
		
		# Return this
		return $this;
	}
	
}
