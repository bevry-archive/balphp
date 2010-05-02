<?php
require_once 'core/functions/_arrays.funcs.php';
require_once 'core/functions/_validate.funcs.php';
 
class Bal_Payment_Gateway_PaypalTest extends PHPUnit_Framework_TestCase {
	
	# ========================
	# Tests
	
    /**
     */
    public function testCreation ( ) {
		# Prepare
		$paypal = self::generatePaypalArray();
		$Paypal = new Bal_Payment_Gateway_Paypal($paypal);
    }
	
    /**
     * @depends testCreation
     * @expectedException Exception
     * @expectedException Bal_Exception
     */
    public function testCreationException ( ) {
		# Prepare
		$Payal = new Bal_Payment_Gateway_Paypal();
    }
	
    /**
     * @depends testCreation
     */
    public function testRequest ( ) {
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Request
		$request = $Paypal->generateRequest($Invoice);
		$this->assertType('array', $request);
    }
	
	
    /**
     * @depends testRequest
     */
    public function testForm ( ) {
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Form
		$form = $Paypal->generateForm($Invoice);
		$this->assertType('string', $form);
    }
	
	
    /**
     * @depends testRequest
     */
    public function testResponse ( ) {
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Request
		// $request = $Paypal->generateRequest($Invoice);
		
		# Response
		// $response = $Paypal->handleResponse()
    }
	
	
	# ========================
	# Providers
	
	/**
	 * Generate a Paypal payment gateway array
	 * @return Bal_Payment_Gateway_Paypal
	 */
	public static function generatePaypalArray ( ) {
		# Config
		$paypal = array(
			'url' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			'token' => '0ctkJcfypZk5536hrpk3TfV2goHrY1idPM67R4Z21KuFgKGeenh1MldQwUm',
			'business' => 'seller_1249741848_biz@balupton.com',
			'notify_url' => 'http://localhost/',
			'return' => 'pdt'
		);
		
		# Return paypal
		return $paypal;
	}
	
	/**
	 * GEnerate a Paypal payment gateway
	 * @return Bal_Payment_Gateway_Paypal
	 */
	public static function generatePaypal ( ) {
		# Config
		$paypal = self::generatePaypalArray();
		
		# Generate
		$Paypal = new Bal_Payment_Gateway_Paypal($paypal);
		
		# Return Paypal
		return $Paypal;
	}
	 
}
