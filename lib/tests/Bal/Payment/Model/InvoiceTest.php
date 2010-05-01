<?php
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Bal/Payment/Model/Invoice.php';
require_once 'core/functions/_arrays.funcs.php';
require_once 'core/functions/_validate.funcs.php';
 
class Bal_Payment_Model_InvoiceTest extends PHPUnit_Framework_TestCase {
	
	# ========================
	# Tests
	
    /**
     * @depends testInvoiceToArray
     * @expectedException Bal_Exception
     */
    public function testInvoiceValidateException ( ) {
		# Prepare
		$Invoice = $this->generateInvoice();
		
		# Validate
		$Invoice->validate();
    }
	
    /**
     * @depends testInvoiceToArray
     * @depends testInvoiceTotals
     */
    public function testInvoiceValidate ( ) {
		# Prepare
		$Invoice = $this->generateInvoice();
		
		# Totals
		$Invoice->applyTotals();
		
		# Validate
		$Invoice->validate();
    }
	
    /**
     * @depends testInvoiceToArray
     */
    public function testInvoiceTotals ( ) {
		# Prepare
		$Invoice = $this->generateInvoice();
		
		# Totals
		$Invoice->applyTotals();
		
		# Should be a bunch of asserts here to prove that totals are what they should be
    }
	
    /**
     */
    public function testInvoiceToArray ( ) {
		# Prepare
		$invoice = $this->generateInvoiceArray();
		$Invoice = $this->generateInvoice();
		
		# Generate Array
		$Invoice_array = $Invoice->toArray();
		
		# Compare Arrays
		$this->assertEquals($invoice, $Invoice_array);
    }
	
	
	# ========================
	# Providers
	
	public function generateInvoice ( ) {
		# Generate Array
		$invoice = $this->generateInvoiceArray();
		
		# Generate Invoice
		$Invoice = new Bal_Payment_Model_Invoice($invoice);
		
		# Return Invoice
		return $Invoice;
	}
	
	public function generateInvoiceArray ( ) {
		# Generate Array 
		$invoice = array(
			'id' => intval(rand(50,200)),
			'currency_code' => 'AUD',
			'payment_status' => 'awaiting',
			'weight_unit' => Bal_Payment_Model_Invoice::WEIGHT_UNIT_KGS,
			'Payer' =>  array(
				'id' => intval(rand(50,200)),
				'firstname' => 'Benjamin',
				'lastname' => 'Lupton'
			),
			'InvoiceItems' => array(
				array(
					'id' 					=> 1,
					'title' 				=> 'My First Item',
					'price_each'			=> 1.00,
					'quantity'				=> 1,
					'weight_unit' 			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
				),
				array(
					'id' 					=> 2,
					'title' 				=> 'My Second Item',
					'price_each'			=> 10000.00,
					'quantity'				=> 3,
					'weight_unit' 			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
				),
				array(
					'id' 					=> 3,
					'title' 				=> 'My Third Item',
					'price_each'			=> 10000.00,
					'quantity'				=> 3,
			
					'handling_each' 		=> 00000.01,
			
					'tax_each'				=> 00000.10,
			
					'weight_each'			=> 00001.00,
					'weight_unit'			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
			
					'discount_each'			=> 00010.00,
			
					'shipping_first' 		=> 00100.00,
					'shipping_additional'	=> 01000.00
				),
				array(
					'id' 					=> 4,
					'title' 				=> 'My Fourth Item',
					'price_each'			=> 10000.00,
					'quantity'				=> 3,
			
					'handling_each' 		=> 00000.01,
			
					'tax_each'				=> 00000.10,
					'tax_rate'				=> 00000.10,
			
					'weight_each'			=> 00001.00,
					'weight_unit'			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
			
					'discount_each'			=> 00010.00,
					'discount_rate'			=> 00000.01,
			
					'shipping_first' 		=> 00100.00,
					'shipping_additional'	=> 01000.00
				)
			)
		);
		
		# Return invoice
		return $invoice;
	}
	
}
