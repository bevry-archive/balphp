<?php

class Bal_Payment_Model_Payer extends Bal_Payment_Model_Abstract {
	
	/**
	 * Store of Model's data
	 * @var array $_data
	 */
	protected $_data = array(
		// required
		'id'		=> null,
		
		// optional
		'email'			=> null,
		'address1'		=> null,
		'address2'		=> null,
		'city' 			=> null,
		'country' 		=> null,
		'country_code' 	=> null,
		'state' 		=> null,
		'postcode' 		=> null,
		'firstname' 	=> null,
		'lastname' 		=> null,
		'language' 		=> null,
		'charset' 		=> null,
	);
	
	/**
	 * Validate our Model
	 * @throws Bal_Exception
	 * @return true
	 */
	public function validate ( ) {
		# Prepare
		$Payer = $this;
		
		// we should check the id, however this is not returned with paypal IPN, so no use
		
		# Return true
		return true;
	}
	
	
}