<?php
require_once 'Bal/Payment/Payer.php';
require_once 'Bal/Payment/Cart.php';
class Bal_Payment_Order {
	public $Cart;
	public $Payer;
	
	public $id;
	public $status;
	public $modified_at;
	
	public function __construct ( Bal_Payment_Cart $Cart, Bal_Payment_Payer $Payer, $id ) {
		$this->Cart = $Cart;
		$this->Payer = $Payer;
		$this->id = $id;
		return $this;
	}
}
