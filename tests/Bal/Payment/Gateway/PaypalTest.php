<?php
require_once 'core/functions/_arrays.funcs.php';
require_once 'core/functions/_strings.funcs.php';
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
		# Check
		if ( defined('PaypalTest_testResponseIpn') && !PaypalTest_testResponseIpn ) {
			return $this->markTestSkipped('Configuration has specified to skip this test.');
		}
		
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
	
	
    /**
     * @depends testRequest
     */
    public function testResponsePdt ( ) {
		# Check
		if ( defined('PaypalTest_testResponsePdt') && !PaypalTest_testResponsePdt ) {
			return $this->markTestSkipped('Configuration has specified to skip this test.');
		}
		
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Request
		$request = self::generateRequest();
		
		# Response
		$response = self::generateResponsePdt();
		
		# Response
		$Paypal->handleResponsePdt($response);
    }


	# ========================
	# Providers
	
	public static function generateRequest ( ) {
		# Prepare
		$Invoice = Bal_Payment_Model_InvoiceTest::generateInvoice();
		$Paypal = self::generatePaypal();
		
		# Request
		$Invoice->id = 'invoice-1273420490';
		$request = $Paypal->generateRequest($Invoice);
		
		# Return request
		return $request;
	}
	
	public static function generateResponsePdt ( ) {
		# Create
		$response = unserialize('a:6:{s:2:"tx";s:17:"6TH130142R8402737";s:2:"st";s:9:"Completed";s:3:"amt";s:5:"23.54";s:2:"cc";s:3:"AUD";s:2:"cm";s:0:"";s:11:"item_number";s:0:"";}');
		
		# Hydrate
		//hydrate_value($response);
		
		# Return response
		return $response;
	}
	
	public static function generateResponseIpn ( ) {
		# Create
		$response = unserialize('a:53:{s:8:"mc_gross";s:5:"23.54";s:7:"invoice";s:18:"invoice-1273420490";s:22:"protection_eligibility";s:10:"Ineligible";s:14:"address_status";s:11:"unconfirmed";s:12:"item_number1";s:1:"5";s:3:"tax";s:4:"3.59";s:12:"item_number2";s:1:"6";s:8:"payer_id";s:13:"SCZ8ZGE8F9NPG";s:14:"address_street";s:22:"1 Cheeseman Ave - East";s:12:"payment_date";s:25:"08:56:22 May 09, 2010 PDT";s:14:"payment_status";s:9:"Completed";s:7:"charset";s:12:"windows-1252";s:7:"mc_tax1";s:4:"1.80";s:11:"address_zip";s:4:"3001";s:11:"mc_shipping";s:4:"0.00";s:7:"mc_tax2";s:4:"1.79";s:11:"mc_handling";s:4:"0.00";s:10:"first_name";s:4:"Test";s:6:"mc_fee";s:4:"0.86";s:20:"address_country_code";s:2:"AU";s:12:"address_name";s:9:"Test User";s:14:"notify_version";s:3:"2.9";s:6:"custom";s:0:"";s:12:"payer_status";s:8:"verified";s:8:"business";s:34:"seller_1249741848_biz@balupton.com";s:15:"address_country";s:9:"Australia";s:14:"num_cart_items";s:1:"2";s:12:"mc_handling1";s:4:"0.00";s:12:"mc_handling2";s:4:"0.00";s:12:"address_city";s:9:"Melbourne";s:11:"verify_sign";s:56:"AXHsxBiCsbDXrqtLaH4rmL6Yapz1ABnCX8bYEQAfscPMAa7Ei-WZYYB1";s:11:"payer_email";s:33:"buyer_1272803352_per@balupton.com";s:12:"mc_shipping1";s:4:"0.00";s:12:"mc_shipping2";s:4:"0.00";s:6:"txn_id";s:17:"6TH130142R8402737";s:12:"payment_type";s:7:"instant";s:9:"last_name";s:4:"User";s:13:"address_state";s:8:"Victoria";s:10:"item_name1";s:30:"invoice-buyer_system-fee-title";s:14:"receiver_email";s:34:"seller_1249741848_biz@balupton.com";s:10:"item_name2";s:37:"invoice-buyer_system-commission-title";s:11:"payment_fee";s:0:"";s:9:"quantity1";s:1:"1";s:9:"quantity2";s:1:"1";s:11:"receiver_id";s:13:"7NR6AHFVJDSEQ";s:8:"txn_type";s:4:"cart";s:10:"mc_gross_1";s:5:"10.00";s:11:"mc_currency";s:3:"AUD";s:10:"mc_gross_2";s:4:"9.95";s:17:"residence_country";s:2:"AU";s:8:"test_ipn";s:1:"1";s:19:"transaction_subject";s:13:"Shopping Cart";s:13:"payment_gross";s:0:"";}');
		
		# Hydrate
		//hydrate_value($response);
		
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
