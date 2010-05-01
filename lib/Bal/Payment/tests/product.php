<?
// Load
require_once(dirname(__FILE__).'/config.php');

// Display
$Invoice = new Bal_Payment_Model_Invoice(array(
	'id' => intval(rand(50,200)),
	'currency_code' => 'AUD',
	'payment_status' => 'awaiting',
	'Payer' =>  new Bal_Payment_Model_Payer(array(
		'id' => intval(rand(50,200)),
		'firstname' => 'Benjamin',
		'lastname' => 'Lupton'
	)),
	'InvoiceItems' => array(
		new Bal_Payment_Model_InvoiceItem(array(
			'id' 					=> 2,
			'title' 				=> 'My First Item',
			'price_each'			=> 10000.00,
			'quantity'				=> 3,
			
			'handling_each' 		=> 00000.01,
			
			'tax_each'				=> 00000.10,
			
			'weight_each'			=> 00001.00,
			'weight_unit'			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
			
			'discount_each'			=> 00010.00,
			
			'shipping_first' 		=> 00100.00,
			'shipping_additional'	=> 01000.00
		)),
		new Bal_Payment_Model_InvoiceItem(array(
			'id' 					=> 2,
			'title' 				=> 'My Second Item',
			'price_each'			=> 10000.00,
			'quantity'				=> 3,
			
			'handling_each' 		=> 00000.01,
			
			'tax_each'				=> 00000.10,
			'tax_rate'				=> 00000.10,
			
			'weight_each'			=> 00001.00,
			'weight_unit'			=> Bal_Payment_Model_InvoiceItem::WEIGHT_UNIT_KGS,
			
			'discount_each'			=> 00010.00,
			'discount_rate'			=> 00000.10,
			
			'shipping_first' 		=> 00100.00,
			'shipping_additional'	=> 01000.00
		))
	)
));
$Payer = new Bal_Payment_Payer(array(
	'firstname' => 'Benjamin',
	'lastname' => 'Lupton'
));
$Order = new Bal_Payment_Order($Cart, $Payer, $Cart->id);

// Request
$form = $Paypal->applyOrder($Order)->generateForm('auto', array(
	'return' => 'http://www.balupton.com/paypal/pdt.php',
	'notify_url' => 'http://www.balupton.com/paypal/ipn.php'
), true);

?><html><head><title>Test</title></head><body><?
echo $form
?></body></html><?

