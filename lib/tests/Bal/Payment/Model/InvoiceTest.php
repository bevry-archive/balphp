<?php
require_once 'core/functions/_arrays.funcs.php';
require_once 'core/functions/_validate.funcs.php';
 
class Bal_Payment_Model_InvoiceTest extends PHPUnit_Framework_TestCase {
	
	# ========================
	# Tests
	
    /**
     */
    public function testCreation ( ) {
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
     * @depends testCreation
     */
    public function testToArray ( ) {
		# Prepare
		$invoice = self::generateInvoiceArray();
		$Invoice = new Bal_Payment_Model_Invoice($invoice);
		
		# Get toArray
		$Invoice_array = array_clean_copy($Invoice->toArray());
		
		# Compare Arrays
		$this->assertEquals($invoice, $Invoice_array);
    }
	
    /**
     * @depends testCreation
     * @expectedException Exception
     * @expectedException Bal_Exception
     */
    public function testValidateException ( ) {
		# Prepare
		$Invoice = new Bal_Payment_Model_Invoice();
		
		# Validate
		$Invoice->validate();
    }
	
    /**
     * @depends testCreation
     * @expectedException Bal_Exception
     */
    public function testSetInvoiceItemsException ( ) {
		# Prepare
		$Invoice = new Bal_Payment_Model_Invoice();
		
		# Try to Break
		$Invoice->setInvoiceItems(false);
		$Invoice->setInvoiceItems(true);
		$Invoice->setInvoiceItems(0);
		$Invoice->setInvoiceItems(null);
    }
	
    /**
     * @depends testCreation
     */
    public function testTotals ( ) {
		# Prepare
		$Invoice = self::generateInvoice();
		
		# Totals
		$Invoice->applyTotals();
		$totals = 0.00;
		
		# Check first InvoiceITem
		$total = 1.00;
		$total = round($total,2);
		$totals += $total;
		$this->assertEquals($total, $Invoice->InvoiceItems[0]->price_total);
		
		# Check Second Invoice Item
		$total = 3.00;
		$total = round($total,2);
		$totals += $total;
		$this->assertEquals($total, $Invoice->InvoiceItems[1]->price_total);
		
		# Check Third Invoice Item
		$total =  3.00;					// price
		$total -= 0.30;					// discount
		$total += 3.00;					// tax fixed
		$total += 3.00;					// handling
		$total += 2.00;					// shipping
		$total = round($total,2);
		$totals += $total;
		$this->assertEquals($total, $Invoice->InvoiceItems[2]->price_total);
		
		# Check Fourth Invoice Item
		$total =  3.00;					// price
		$total -= 0.30 + $total*0.10;	// discount
		$total += 3.00 + $total*0.01;	// tax
		$total += 3.00;					// handling
		$total += 2.00;					// shipping
		$total = round($total,2);
		$totals += $total;
		$this->assertEquals($total, $Invoice->InvoiceItems[3]->price_total);
		
		# Check Invoice
		$this->assertEquals(strval($totals), strval($Invoice->price_total));
    }
	
    /**
     * @depends testTotals
     */
    public function testValidate ( ) {
		# Prepare
		$Invoice = self::generateInvoice();
		
		# Totals
		$Invoice->applyTotals();
		
		# Validate
		$Invoice->validate();
    }
	
	# ========================
	# Providers
	
	/**
	 * Generate a populated Invoice
	 * @return Bal_Payment_Model_Invoice
	 */
	public static function generateInvoice ( ) {
		# Generate Array
		$invoice = self::generateInvoiceArray();
		
		# Generate Invoice
		$Invoice = new Bal_Payment_Model_Invoice($invoice);
		
		# Return Invoice
		return $Invoice;
	}
	
	/**
	 * Generate a populated invoice array
	 * @return array
	 */
	public static function generateInvoiceArray ( ) {
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
					'price_each'			=> 1.00,
					'quantity'				=> 3,
					'weight_unit' 			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
				),
				array(
					'id' 					=> 3,
					'title' 				=> 'My Third Item',
					'price_each'			=> 1.00,
					'quantity'				=> 3,
					
					'handling_each' 		=> 1.00,
					
					'tax_each'				=> 1.00,
					
					'weight_each'			=> 1.00,
					'weight_unit'			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
					
					'discount_each'			=> 0.10,
					
					'shipping_first' 		=> 1.00,
					'shipping_additional'	=> 0.50
				),
				array(
					'id' 					=> 4,
					'title' 				=> 'My Fourth Item',
					'price_each'			=> 1.00,
					'quantity'				=> 3,
					
					'handling_each' 		=> 1.00,
					
					'tax_each'				=> 1.00,
					'tax_each_rate'			=> 0.01,
					
					'weight_each'			=> 1.00,
					'weight_unit'			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
					
					'discount_each'			=> 0.10,
					'discount_each_rate'	=> 0.10,
					
					'shipping_first' 		=> 1.00,
					'shipping_additional'	=> 0.50
				)
			)
		);
		
		# Return invoice
		return $invoice;
	}
	
}
