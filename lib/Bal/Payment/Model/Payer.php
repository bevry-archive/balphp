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
		'address1'	=> null,
		'address2'	=> null,
		'city' 		=> null,
		'country' 	=> null,
		'state' 	=> null,
		'postcode' 	=> null,
		'firstname' => null,
		'lastname' 	=> null,
		'language' 	=> null,
		'charset' 	=> null,
	);
	
	/**
	 * Validate our Model
	 * @throws Bal_Exception
	 * @return true
	 */
	public function validate ( ) {
		# Prepare
		$error = false;
		$Payer = $this;
		
		# Fetch
		$id = $Payer->id;
		
		# Ensure ID
		if ( !$id ) {
			$error = 'Payer id must not be empty';
		}
		
		# Handle?
		if ( $error ) {
			throw new Bal_Exception(array(
				$error,
				'Payer' => $Payer
			));
		}
		
		# Return true
		return true;
	}
	
	
}