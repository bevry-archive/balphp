<?php
require_once 'core/functions/_arrays.funcs.php';
require_once 'core/functions/_validate.funcs.php';
require_once 'core/functions/_datetime.funcs.php';
 
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
    public function testResponseIpn ( ) {
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Request
		$request = self::generateRequest();
		
		# Response
		$response = self::generateResponseIpn();
		
		# Response
		$Paypal->handleResponseIpn($response);
    }
	

	# ========================
	# Providers
	
	public static function generateRequest ( ) {
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Request
		$Invoice->id = 145;
		$request = $Paypal->generateRequest($Invoice);
		
		# Return request
		return $request;
	}
	
	public static function generateResponsePdt ( ) {
		# Create
		$response = array(
			'tx' => '4A412521A3541820E',
			'st' => 'Completed',
			'amt' => '25.12',
			'cc' => 'AUD',
			'cm' => '',
			'item_number' => '',
		);
		
		# Hydrate
		array_hydrate($response);
		
		# Return response
		return $response;
	}
	
	public static function generateResponseIpn ( ) {
		# Create
		$response = array(
			'mc_gross' => '25.12',
			'invoice' => '145',
			'protection_eligibility' => 'Ineligible',
			'address_status' => 'unconfirmed',
			'item_number1' => '1',
			'tax' => '6.02',
			'item_number2' => '2',
			'payer_id' => 'SCZ8ZGE8F9NPG',
			'item_number3' => '3',
			'address_street' => '1 Cheeseman Ave - East',
			'item_number4' => '4',
			'payment_date' => '08:41:51 May 02, 2010 PDT',
			'payment_status' => 'Completed',
			'charset' => 'windows-1252',
			'address_zip' => '3001',
			'mc_shipping' => '4.00',
			'mc_handling' => '6.00',
			'first_name' => 'Test',
			'mc_fee' => '0.90',
			'address_country_code' => 'AU',
			'address_name' => 'Test User',
			'custom' => '',
			'payer_status' => 'verified',
			'business' => 'seller_1249741848_biz@balupton.com',
			'address_country' => 'Australia',
			'num_cart_items' => '4',
			'mc_handling1' => '0.00',
			'mc_handling2' => '0.00',
			'mc_handling3' => '3.00',
			'address_city' => 'Melbourne',
			'mc_handling4' => '3.00',
			'payer_email' => 'buyer_1272803352_per@balupton.com',
			'mc_shipping1' => '0.00',
			'mc_shipping2' => '0.00',
			'mc_shipping3' => '2.00',
			'mc_shipping4' => '2.00',
			'txn_id' => '4A412521A3541820E',
			'payment_type' => 'instant',
			'last_name' => 'User',
			'address_state' => 'Victoria',
			'item_name1' => 'My First Item',
			'receiver_email' => 'seller_1249741848_biz@balupton.com',
			'item_name2' => 'My Second Item',
			'payment_fee' => '',
			'item_name3' => 'My Third Item',
			'item_name4' => 'My Fourth Item',
			'quantity1' => '1',
			'quantity2' => '3',
			'receiver_id' => '7NR6AHFVJDSEQ',
			'quantity3' => '3',
			'txn_type' => 'cart',
			'quantity4' => '3',
			'mc_gross_1' => '1.00',
			'mc_currency' => 'AUD',
			'mc_gross_2' => '3.00',
			'mc_gross_3' => '7.70',
			'residence_country' => 'AU',
			'mc_gross_4' => '7.40',
			'transaction_subject' => 'Shopping Cart',
			'payment_gross' => '',
		);
		
		# Hydrate
		array_hydrate($response);
		
		# Return response
		return $response;
	}
	
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
