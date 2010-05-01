<?php
require_once 'Bal/Payment/Model/Abstract.php';

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
			
			'weight_unit'			=> null,
		
		// Optional
			'handling_each' 		=> null,
			'handling_total'		=> null,
			
			'tax_each'				=> null,
			'tax_rate'				=> null,
			'tax_total'				=> null,
			
			'weight_each'			=> null,
			'weight_total'			=> null,
			
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
		$InvoiceItem = $this;
		
		# Prepare Checks
		$checks = array(
			'id'					=> !empty($InvoiceItem->id),
			'title' 				=> !empty($InvoiceItem->title),
			'price_total' 			=> in_range(0, $InvoiceItem->price_each_total, 		null, '<=', true),
			'price_each' 			=> in_range(0, $InvoiceItem->each_total, 			null, '<=', true),
			'total' 				=> in_range(0, $InvoiceItem->each_total, 			null, '<=', true),
			'weight_unit' 			=> in_array($InvoiceItem->weight_unit, 				array(self::WEIGHT_UNIT_LBS,self::WEIGHT_UNIT_KGS)),
			
			'handling_each'			=> in_range(0, $InvoiceItem->handling_each, 		null, '<=', true),
			'handling_total'		=> in_range(0, $InvoiceItem->handling_total, 		null, '<=', true),
			'tax_each'				=> in_range(0, $InvoiceItem->tax_each, 				null, '<=', true),
			'tax_rate'				=> in_range(0, $InvoiceItem->tax_rate, 				1,    '<=', true),
			'tax_total'				=> in_range(0, $InvoiceItem->tax_total, 			null, '<=', true),
			'weight_each'			=> in_range(0, $InvoiceItem->weight_each, 			null, '<=', true),
			'weight_total'			=> in_range(0, $InvoiceItem->weight_total, 			null, '<=', true),
			'discount_each'			=> in_range(0, $InvoiceItem->discount_each, 		null, '<=', true),
			'discount_rate'			=> in_range(0, $InvoiceItem->discount_rate, 		1,    '<=', true),
			'discount_total'		=> in_range(0, $InvoiceItem->discount_total, 		null, '<=', true),
			'shipping_first'		=> in_range(0, $InvoiceItem->shipping_first, 		null, '<=', true),
			'shipping_additional'	=> in_range(0, $InvoiceItem->shipping_additional, 	null, '<=', true),
			'shipping_total'		=> in_range(0, $InvoiceItem->shipping_total, 		null, '<=', true)
		);
		
		# Validate Checks
		validate_checks($checks);
		
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
		$shipping_total 	= $shipping_first + ($quantity-1)*$shipping_additional;
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
