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
     */
    public function testInvoiceCreation ( ) {
		# Prepare
		$invoice = array(
			'id' => 1
		);
		$Invoice = new Bal_Payment_Model_Invoice($invoice);
		
		# Check Construct
		$this->assertEquals(1, $Invoice->id);
		
		# Check Set/Get
		$Invoice->id = 2;
		$this->assertEquals(2, $Invoice->id);
		$Invoice->id = 1;
		
		# Check ToArray
		$Invoice_array = array_clean_copy($Invoice->toArray());
		$this->assertEquals(1, $Invoice_array['id']);
		
		# Compare Arrays
		$this->assertEquals($invoice, $Invoice_array);
    }
	
    /**
     * @depends testInvoiceCreation
     */
    public function testInvoiceToArray ( ) {
		# Prepare
		$invoice = $this->generateInvoiceArray();
		$Invoice = new Bal_Payment_Model_Invoice($invoice);
		
		# Get toArray
		$Invoice_array = array_clean_copy($Invoice->toArray());
		
		# Compare Arrays
		$this->assertEquals($invoice, $Invoice_array);
    }
	
    /**
     * @depends testInvoiceCreation
     * @expectedException Exception
     * @expectedException Bal_Exception
     */
    public function testInvoiceValidateException ( ) {
		# Prepare
		$Invoice = new Bal_Payment_Model_Invoice();
		
		# Validate
		$Invoice->validate();
    }
	
    /**
     * @depends testInvoiceCreation
     */
    public function testInvoiceTotals ( ) {
		# Prepare
		$Invoice = $this->generateInvoice();
		
		# Totals
		$Invoice->applyTotals();
		$totals = 0.00;
		
		# Check first InvoiceITem
		$total = 1.00; $totals += $total;
		$this->assertEquals($total, $Invoice->InvoiceItems[0]->total);
		
		# Check Second Invoice Item
		$total = 30000.00; $totals += $total;
		$this->assertEquals($total, $Invoice->InvoiceItems[1]->total);
		
		# Check Third Invoice Item
		$total = 32100.33-30.00; $totals += $total;
		$this->assertEquals((int)($total), (int)$Invoice->InvoiceItems[2]->total);
		
		# Check Fourth Invoice Item
		$subtotal = 32100.33+(30000*0.10);
		$discount = 30.00 + $subtotal * 0.01;
		$total = $subtotal - $discount; $totals += $total;
		$this->assertEquals((int)$total, (int)$Invoice->InvoiceItems[3]->total);
		
		# Now Ensure Invoice Totals
		$totals += 1.00 + 2.00;
		$totals = $totals*0.50 - 1.00;
		$this->assertEquals((int)$totals, (int)$Invoice->total);
    }
	
    /**
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
			
			'handling_invoice' 			=> 1.00,
			'shipping_invoice' 			=> 2.00,
			'discount_invoice' 			=> 1.00,
			'discount_invoice_rate'		=> 0.50,
			
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
