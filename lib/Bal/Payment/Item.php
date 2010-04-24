<?php
require_once 'Bal/Basic.php';
class Bal_Payment_Item extends Bal_Basic {
	protected $id;
	protected $amount;
	protected $title;
	
	protected $quantity = 1.0;
	protected $shipping = 0.0;
	protected $shipping_additional = 0.0;
	protected $tax = 0.0;
	protected $tax_rate = 0.0;
	protected $weight = 0.0;
	protected $weight_unit = 'lbs';
	protected $handling = 0.0;
	
	protected $discount_amount = 0.0;
	protected $discount_rate = 0.0;
	
	/** Will be called on $Item->total thanks to Bal_Basic */
	public function getTotal ( ) {
		$total = $this->shipping + $this->quantity*$this->amount + ($this->quantity-1)*$this->shipping_additional;
		if ( $this->tax_rate ) $total *= $this->tax_rate;
		$total += $this->tax;
		return $total;
	}
	
	public function setWeightUnit ( $value ) {
		if ( !in_array($value, array('lbs','kgs')) ) {
			throw new Exception('Invalid weight unit: '.$value);
		}
		$this->weight_unit = $value;
		return $this;
	}
}