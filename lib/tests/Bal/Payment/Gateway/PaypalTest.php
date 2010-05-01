<?php
require_once 'PHPUnit/Framework.php';
 
class Bal_Payment_Gateway_PaypalTest extends PHPUnit_Framework_TestCase {
	
    public $PayPal;

    public function setUp ( ) {
		# Config
		$config = array(
			'url' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			'token' => '0ctkJcfypZk5536hrpk3TfV2goHrY1idPM67R4Z21KuFgKGeenh1MldQwUm',
			'business' => 'seller_1249741848_biz@balupton.com',
			'notify_url' => 'blah',
			'return' => 'pdt'

		);
		
		# Prepare
		$this->PayPal = new Bal_Payment_Gateway_Paypal($config);
    }
	
	public function testGenerateForm ( ) {
		# Prepare
		$Invoice = array(
			
		)
		
		# Handle
		$form = $this->PayPal->generateForm($Invoice);
	}
	
    public function testPushAndPop()
    {
        $stack = array();
        $this->assertEquals(0, count($stack));
 
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));
 
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
}
?>

class Bal_Payment_Gateway_PaypalTest
?>