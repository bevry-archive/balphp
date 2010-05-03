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
			'quantity'				=> null, // the amount of items to have
			'payment_fee'			=> null, // returned payment fee from gateway
			'weight_unit'			=> null, // weight unit to use
			
			'price_each'			=> null, // the price for each item		- Discount, Handling, Shipping, Tax
			'price_each_d'			=> null, // the price for each item 	+ Discount
			'price_all_total'		=> null, // the price for all  items	- Discount, Handling, Shipping, Tax
			'price_all_total_d'		=> null, // the price for all  items	+ Discount
			'price_all_total_dhs'	=> null, // the price for all  items	+ Discount, Handling, Shipping
			'price_total'			=> null, // the price for all  items	+ Discount, Handling, Shipping, Tax
			
		// Optional
			'handling_each' 		=> null, // handling for each item
			'handling_all_total'	=> null, // total handling for all items
			
			'tax_each'				=> null, // tax for each item
			'tax_each_rate'			=> null, // tax rate for each item
			'tax_each_total'		=> null, // total tax for each item
			'tax_all_total'			=> null, // total tax for all items
			
			'weight_each'			=> null, // weight for each item
			'weight_all_total'		=> null, // total weight for all times
		
			'discount_each'			=> null, // discount for each item
			'discount_each_rate'	=> null, // discount rate for each item
			'discount_each_total'	=> null, // total discount for each item
			'discount_all_total'	=> null, // total discount for all items
			
			'shipping_first' 		=> null, // shipping for first item
			'shipping_additional'	=> null, // shipping for each additional item
			'shipping_all_total'	=> null, // total shipping for all items
	);
	
	/**
	 * Validate ourself
	 * @throws Bal_Exception
	 * @return true
	 */
	public function validate ( ) {
		# Prepare
		$InvoiceItem = $this;
		
		# Validate
		$result = self::validateModel($InvoiceItem);
		
		# Return result
		return $result;
	}
	
	/**
	 * Validates a InvoiceItem
	 * @param mixed $InvoiceItem
	 * @throws Bal_Exception
	 * @return true
	 */
	public static function validateModel ( $InvoiceItem ) {
		# Prepare Checks
		$checks = array(
			'id'					=> !reallyempty($InvoiceItem->id),
			'title' 				=> !reallyempty($InvoiceItem->title),
			'quantity' 				=> is_integer($InvoiceItem->quantity) && $InvoiceItem->quantity >= 1,
			'payment_fee'			=> is_numeric($InvoiceItem->payment_fee),
			'weight_unit' 			=> in_array($InvoiceItem->weight_unit, 				array(self::WEIGHT_UNIT_LBS,self::WEIGHT_UNIT_KGS)),
			
			'price_each' 			=> is_numeric($InvoiceItem->price_each),
			'price_each_d' 			=> is_numeric($InvoiceItem->price_each_d),
			'price_all_total'		=> is_numeric($InvoiceItem->price_all_total),
			'price_all_total_d' 	=> is_numeric($InvoiceItem->price_all_total_d),
			'price_all_total_dhs' 	=> is_numeric($InvoiceItem->price_all_total_dhs),
			'price_total' 			=> is_numeric($InvoiceItem->price_total),
			
			'handling_each'			=> in_range(0, $InvoiceItem->handling_each, 		null, '<=', true),
			'handling_all_total'	=> in_range(0, $InvoiceItem->handling_all_total, 	null, '<=', true),
			
			'tax_each'				=> in_range(0, $InvoiceItem->tax_each, 				null, '<=', true),
			'tax_each_rate'			=> in_range(0, $InvoiceItem->tax_each_rate, 		1,    '<=', true),
			'tax_each_total'		=> in_range(0, $InvoiceItem->tax_each_total, 		null, '<=', true),
			'tax_all_total'			=> in_range(0, $InvoiceItem->tax_all_total, 		null, '<=', true),
			
			'weight_each'			=> in_range(0, $InvoiceItem->weight_each, 			null, '<=', true),
			'weight_all_total'		=> in_range(0, $InvoiceItem->weight_all_total, 		null, '<=', true),
			
			'discount_each'			=> in_range(0, $InvoiceItem->discount_each, 		null, '<=', true),
			'discount_each_rate'	=> in_range(0, $InvoiceItem->discount_each_rate, 	1,    '<=', true),
			'discount_each_total'	=> in_range(0, $InvoiceItem->discount_each_total, 	null, '<=', true),
			'discount_all_total'	=> in_range(0, $InvoiceItem->discount_all_total, 	null, '<=', true),
		
			'shipping_first'		=> in_range(0, $InvoiceItem->shipping_first, 		null, '<=', true),
			'shipping_additional'	=> in_range(0, $InvoiceItem->shipping_additional, 	null, '<=', true),
			'shipping_all_total'	=> in_range(0, $InvoiceItem->shipping_all_total, 	null, '<=', true)
		);
		
		# Validate Checks
		validate_checks($checks);
		
		# Return true
		return true;
	}
	
	/**
	 * Apply the totals to ourself
	 * @return $this
	 */
	public function applyTotals ( ) {
		# Prepare
		$InvoiceItem = $this;
		
		# Apply Totals
		self::applyTotalsModel($InvoiceItem);
		
		# Return true
		return true;
	}
	
	/**
	 * Apply the Totals to the Model
	 * @param mixed $InvoiceItem
	 * @return true
	 */
	public static function applyTotalsModel ( $InvoiceItem ) {
		# Force Valid Inputs
		$InvoiceItem->price_each 				= until_numeric($InvoiceItem->price_each, 0.00);
		$InvoiceItem->price_each_d 				= until_numeric($InvoiceItem->price_each_d, 0.00);
		$InvoiceItem->quantity 					= until_integer($InvoiceItem->quantity, 1.00);
		$InvoiceItem->payment_fee 				= until_numeric($InvoiceItem->payment_fee, 0.00);
		$InvoiceItem->handling_each 			= until_numeric($InvoiceItem->handling_each, 0.00);
		$InvoiceItem->tax_each 					= until_numeric($InvoiceItem->tax_each, 0.00);
		$InvoiceItem->tax_each_rate 			= until_numeric($InvoiceItem->tax_each_rate, 0.00);
		$InvoiceItem->weight_each 				= until_numeric($InvoiceItem->weight_each, 0.00);
		$InvoiceItem->discount_each 			= until_numeric($InvoiceItem->discount_each, 0.00);
		$InvoiceItem->discount_each_rate 		= until_numeric($InvoiceItem->discount_each_rate, 0.00);
		$InvoiceItem->shipping_first 			= until_numeric($InvoiceItem->shipping_first, 0.00);
		$InvoiceItem->shipping_additional 		= until_numeric($InvoiceItem->shipping_additional, 0.00);
		
		# Calculate Each Parts
		$InvoiceItem->discount_each_total		= $InvoiceItem->discount_each + ($InvoiceItem->discount_each_rate * $InvoiceItem->price_each);
		$InvoiceItem->price_each_d				= $InvoiceItem->price_each - $InvoiceItem->discount_each_total;
		$InvoiceItem->tax_each_total			= $InvoiceItem->tax_each + ($InvoiceItem->tax_each_rate * $InvoiceItem->price_each_d);
		
		# Calculate Extra Parts
		$InvoiceItem->discount_all_total		= ($InvoiceItem->quantity * $InvoiceItem->discount_each_total);
		$InvoiceItem->weight_all_total			= ($InvoiceItem->quantity * $InvoiceItem->weight_each);
		$InvoiceItem->tax_all_total				= ($InvoiceItem->quantity * $InvoiceItem->tax_each_total);
		$InvoiceItem->handling_all_total		= ($InvoiceItem->quantity * $InvoiceItem->handling_each);
		$InvoiceItem->shipping_all_total		= $InvoiceItem->shipping_first + (($InvoiceItem->quantity-1) * $InvoiceItem->shipping_additional);
		
		# Calculate Price Parts
		$InvoiceItem->price_all_total			= ($InvoiceItem->quantity * $InvoiceItem->price_each);
		$InvoiceItem->price_all_total_d			= ($InvoiceItem->quantity * $InvoiceItem->price_each_d);
		$InvoiceItem->price_all_total_dhs		= $InvoiceItem->price_all_total_d + $InvoiceItem->handling_all_total + $InvoiceItem->shipping_all_total;
		$InvoiceItem->price_total				= $InvoiceItem->price_all_total_dhs + $InvoiceItem->tax_all_total;
		
		# Now round everything - This is because paypal will discard any values that are not rounded
		$fields = array(
			'price_each','price_each_d','payment_fee','handling_each','tax_each','tax_each_rate','weight_each','discount_each','discount_each_rate','shipping_first','shipping_additional',
			'discount_each_total','price_each_d','tax_each_total',
			'discount_all_total','weight_all_total','tax_all_total','handling_all_total','shipping_all_total',
			'price_all_total','price_all_total_d','price_all_total_dhs','price_total'
		);
		foreach ( $fields as $field ) {
			$InvoiceItem->$field = round($InvoiceItem->$field, 2);
		}
		
		# Return true
		return true;
	}
	
}
