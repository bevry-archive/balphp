<?php;
/* Resources */
require_once 'Bal/Payment/Model/Item.php';
require_once 'Bal/Payment/Model/User.php';
require_once 'Bal/Payment/Model/Invoice.php';


Usage:
$Invoice = new Invoice();
$Invoice->InvoiceItems = array(new InvoiceItem());

$Paypal = Bal_Payment_Gateway_Paypal();
echo $Paypal->generateForm($Invoice);
$Invoice = $Paypal->handleResponse($response);



/**
 * Paypal Payment Gateway
 * @author Benjamin "balupton" Lupton
 */
class Bal_Payment_Gateway_Paypal extends Bal_Payment_Gateway_Abstract {
	
	
	# ========================
	# Constants
	
    const PDT = 1;
	const IPN = 2;
	
	
	# ========================
	# Variables
	
	/**
	 * The response received from PayPal
	 * @var array $response
	 */
	protected $response = array();
	
	/**
	 * The type of reponse received from Paypal
	 * @var string[post|get] $response_type
	 */
	protected $response_type;
	
	/**
	 * A map of from our Bal_Payment_Models to the PayPal standards
	 * @var array $maps
	 */
	protected const $maps = array(
		'request' => array(
			'invoice' => array(
				'id' => 'invoice',
				'amount' => 'amount',
				'currency' => 'currency_code',
				'handling' => 'handling',
				'shipping' => 'shipping',
				'tax' => 'tax_cart',
				'weight' => 'weight_cart',
				'weight_unit' => 'weight_unit',
				'discount_amount' => 'discount_amount_cart',
				'discount_rate' => 'discount_rate_cart'
			),
			'item' => array( // for multiples the number is appended
				'id' => 'item_number',
				'amount' => 'amount',
				'title' => 'item_name',
			
				'quantity' => 'quantity',
				'shipping' => 'shipping',
				'shipping_additional' => 'shipping2',
				'tax' => 'tax',
				'tax_rate' => 'tax_rate',
				'weight' => 'weight',
				'weight_unit' => 'weight_unit',
				'handling' => 'handling',
			
				'discount_amount' => 'discount_amount',
				'discount_rate' => 'discount_rate',
			),
			'payer' => array(
				'address1' => 'address1',
				'address2' => 'address2',
				'city' => 'city',
				'country' => 'country',
				'state' => 'state',
				'postcode' => 'zip',
				'firstname' => 'first_name',
				'lastname' => 'last_name',
				'language' => 'lc',
				'charset' => 'charset'
			)
		),
		'response' => 
			'invoice' => array(
				'id' => 'invoice',
				'amount' => 'mc_gross',
				'currency' => 'mc_currency',
				'handling' => array('handling_amount','mc_handling'),
				'shipping' => array('shipping','mc_shipping'),
				'tax' => 'tax'
			),
			'item' => array( // for multiples the number is appended
				'id' => 'item_number',
				'amount' => 'mc_gross_', // underscore gets trimmed if we only have a single
				'title' => 'item_name',
				'quantity' => 'quantity',
				'shipping' => 'mc_shipping',
				'tax' => 'tax',
				'handling' => 'mc_handling'
			),
			'payer' = array(
				'address1' => 'address_street',
				'address2' => 'address_street2',
				'city' => 'address_city',
				'country' => 'address_country', // 'address_country_code', - incorrect, actually not code
				'state' => 'address_state',
				'postcode' => 'address_zip',
				'firstname' => 'first_name',
				'lastname' => 'last_name',
				'charset' => 'charset',
				'phone' => 'contact_phone'
			)
		)
	);
	
	/**
	 * A map of values to validate
	 * @var array $validate
	 */
	protected const $validate = array(
		'config' => array(
			'url', 'token', 'business', 'notify_url', 'return'
		)
	);
	
	
	# ========================
	# Config
	
	/**
	 * Set the Config
	 * @param array $Config
	 * @return $this
	 */
	public function setConfig ( array $Config ) {
		# Check
		$validate = $this->validate['config'];
		foreach ( $validate as $validate_field ) {
			$value = delve($Config,$validate_field);
			if ( empty($value) ) {
				throw new Bal_Exception(array(
					'Missing a configuration field for the paypal payment gateway',
					'config' => $Config,
					'field' => $validate_field
					'required_fields' => $validate
				));
			}
		}
		
		# Apply
		$this->Config = $Config;
		
		# Chain
		return $this;
	}
	
	# ========================
	# Form
	
	/**
	 * Generates our Payment Gateway Form
	 * Used to submit the invoice to the payment gateway for their handling
	 * @param string[auto|cart|buynow] $type
	 * @return string HTML Form
	 */
	public function genereateForm ( $type ) {
		# Prepare
		$Invoice = $this->getInvoice();
		$Log = $this->getLog();
		$Config = $this->getConfig();
		$request = $this->generateRequest();
		
		# Is Type Automatic?
		if ( $type === 'auto' ) {
			# Determine Type
			if ( sizeof($Invoice->InvoiceItems) > 1 ) {
				$type = 'cart';
			} else {
				$type = 'buynow';
			}
		}
		
		# Prepare Request based on Type
		switch ( $type ) {
			case 'cart':
				$request['cmd'] = '_cart';
				$request['upload'] = '1';
				break;
				
			case 'buynow':
				$request['cmd'] = '_xclick';
				if ( sizeof($Invoice->InvoiceItems) > 1 ) {
					throw new Bal_Exception(array(
						'Too many items for buynow',
						'Invoice' => $Invoice
					));
				}
				break;
		}
		
		# Add some config variables to request if they are set
		$params = array('business', 'notify_url', 'return');
		foreach ( $params as $param ) {
			$config_value = delve($Config,'param');
			if ( $config_value ) {
				$request[$param] = $config_value;
			}
		}
		
		# Apply our Request?
		if ( !empty($request) ) {
			$request = array_merge($request, $request);
		}
		
		// Save
		$Store = array(
			'custom' => delve($Config,'custom'),
			'Invoice' => $Invoice,
			'request' => $request
		);
		$this->setStore($Store, $Invoice->id);
		
		# Generate Form
		$form = '';
		foreach ( $request as $key => $value ) {
			$form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}
		$form =
		'<form action="'.delve($Config,'url').'" method="post">
			<!--[Values]-->
			'.$form.'
			<!--[Button]-->
			<input type="image" name="submit" border="0" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" alt="PayPal - The safer, easier way to pay online">
			<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
		</form>';
		
		# Return form
		return $form;
	}
	
	/**
	 * Generates a Request based on our Invoice and Config to send to PayPal
	 * @return array $request
	 */
	public function generateRequest ( Bal_Payment_Model_Invoice $Invoice ) {
		# Prepare
		$Payer = $Invoice->Payer;
		$InvoiceItems = $Invoice->InvoiceItems;
		$maps = $this->maps['request'];
		$request = array();
		
		
		# --------------------------
		# Invoice
		
		# Merge mapped Invoice to request
		$request = array_merge($request, array_keys_map($Invoice, $maps['invoice']));
		
		
		# --------------------------
		# Payer
		
		# Merge mapped Payer to request
		$request = array_merge($request, array_keys_map($Payer, $maps['payer']));
		
		# Map the phone
		if ( !empty($Payer->phone) ) {
			$phone = $Payer->phone;
			$z = strlen($phone)-1;
			$request['night_phone_c'] = substr($phone,-4);
			$request['night_phone_b'] = substr($phone,-7, 3);
			$request['night_phone_a'] = substr($phone,0,-7);
		}
		
		
		# --------------------------
		# Items
		
		# Check if we have a single or multiple items
		$InvoiceItems_count = count($InvoiceItems);
		
		# Handle Appropriatly
		if ( $InvoiceItems_count === 1 ) {
			# Single Item
			
			# Prepare
			$maps_item = $maps['item'];
			
			# Merge mapped Item to request
			$request = array_merge($request, array_keys_map($InvoiceItem, $maps_item));
		}
		else {
			# Multiple Items
			
			# Cycle through the remote fields
			for ( $InvoiceItems_i=0; $i<$InvoiceItems_count; +$InvoiceItems_i ) {
				# Prepare
				$maps_item = $maps['item'];
				
				# Adjust
				foreach ( $maps_item as &$value ) {
					$value .= '_'.($InvoiceItems_i+1);
				}
				
				# Merge mapped Item to request
				$request = array_merge($request, array_keys_map($InvoiceItem, $maps_item));
			}
		}
		
		
		# --------------------------
		# Done
		
		# Return request
		return $request;
	}
	
	
	/**
	 * Handle a PayPal Response
	 * Receives an $response array and then passes to the appropriate handler for the response type
	 * @param array $response
	 * @return $this
	 */
	public function handleResponse ( array $response ) {
		# Check
		if ( empty($response['tx']) && empty($response['txn_id']) ) {
			throw new Bal_Exception(array(
				'Response received is not a valid paypal response',
				'response' => $response
			));
		}
		
		# Determine Response Type
		$response_type = !empty($response['tx']) ? self::PDT : self::IPN;
		
		# Handle Appropriatly
		switch ( $response_type ) {
			case self::PDT:
				$this->handlePDT($response);
				break;
				
			case self::IPN:
			default:
				$this->handleIPN($response);
				break;
		}
		
		# Chain
		return $this;
	}
	
	/**
	 * Handle a PDT Request
	 * After payment, if the Payer returns to our site, PayPal will perform the redirect as a PDT Request
	 * @return $this
	 */
	public function handlePDT ( array $response_pdt ) {
		# Check we have a transaction id
		if ( empty($reponse_pdt['tx']) ) {
			# Error
			throw new Bal_Exception(array(
				'PDT received an HTTP GET request without a transaction ID.',
				'response' => $reponse_pdt
			));
		}
		
		# Send a request to paypal to receive the Authentic Reponse for that transaction
		$response_authentic = $this->PPHttpPost(delve($Config,'url'), array(
			'cmd' => '_notify-synch',
			'tx' => $reponse_pdt['tx'],
			'at' => delve($Config,'token'),
		), true);
		
		# Check the status of that Authentic Response
		if( empty($response_authentic['status']) ) {
			// Error
			throw new Exception(array(
				'There was an error processing the authentic response for the PDT request',
				'response_error' => $response_authentic['error_no'].': '.$response_authentic['error_msg'],
				'response' => $response_authentic
			));
			exit;
		}
		
		# Fetch the Authentc Response's Data
		$response_authentic_data = $response["httpParsedResponseAr"];
		
		# Check that the Authentic Responses's Transaction ID does not differ from that of our original PDT Response
		if ( $response_authentic_data['txn_id'] !== $response_pdt['tx'] ) {
			// Error
			throw new Exception('Transaction IDs do not match.');
			exit;
		}
		
		# Merge the PDT and Authentic Response Data to generate a complete valid response
		$response_complete = array_merge($response_pdt, $response_authentic_data);
		
		# Now continue as if we were an IPN request
		$this->handleIPN($response_complete);
		
		# Chain
		return $this;
	}
	
	/**
	 * Handle a IPN Request
	 * As soon as a update to the payment is performed, PayPal will try to send us a notification request (IPN) to inform us
	 * This may however occur before or after the PDT request due to network slowdowns
	 * Therefor we also trigger this in our @handlePDT function
	 * @return $this
	 */
	public function handleIPN ( array $resonse ) {
		# Prepare
		$InvoiceItems = array();
		$Invoice = new Bal_Payment_Invoice();
		$Payer = new Bal_Payment_Payer();
		$maps = $this->maps['response'];
		
		
		# --------------------------
		# Invoice
		
		# Map a Invoice from the response
		$Invoice = array_keys_map($response, array_flip_deep($maps['invoice']));
		
		
		# --------------------------
		# Payer
		
		# Map a Payer from the response
		$Payer = array_keys_map($response, array_flip_deep($maps['payer']));
		
		# Add to Invoice
		$Invoice->Payer = $Payer;
		
		
		# --------------------------
		# Items
		
		# Check if we have a single or multiple items
		$InvoiceItems_count = count($InvoiceItems);
		
		# Handle Appropriatly
		if ( $InvoiceItems_count === 1 ) {
			# Single Item
			
			# Prepare
			$maps_item = $maps['item'];
			
			# Adjust
			foreach ( $maps_item as &$value ) {
				$value = trim($value, '_');
			}
				
			# Merge mapped Item to request
			$InvoiceItems[] = array_keys_map($response, array_flip_deep($maps_item));
		}
		else {
			# Multiple Items
			
			# Cycle through the remote fields
			for ( $InvoiceItems_i=0; $i<$InvoiceItems_count; +$InvoiceItems_i ) {
				# Prepare
				$maps_item = $maps['item'];
				
				# Adjust
				foreach ( $maps_item as &$value ) {
					$value .= ($InvoiceItems_i+1);
				}
				
				# Merge mapped Item to request
				$InvoiceItems[] = array_keys_map($response, array_flip_deep($maps_item));
			}
		}
		
		
		# --------------------------
		# Check against Stored Local Invoice
		
		# Fetch the local Invoice
		$Invoice_local = $this->getStore($Invoice->id);
		
		# Check Payment Date
		$paid_at = delve($response,'payment_date');
		if ( !$paid_at ) {
			throw new Bal_Exception(array(
				'Response payment_date is empty',
				'response' => $response
			));
		}
		
		# Compare Payment Date against our local Invoice
		$paid_at = strtotime($paid_at);
		if ( $Invoice_local->paid_at > $paid_at ) {
			throw new Bal_Exception(array(
				'Response paid_at is older than the local Invoice paid_at',
				'invoice_paid_at' => date('r',$Invoice_local->paid_at),
				'response_paid_at' => date('r',$paid_at),
				'response' => $response
			));
		}
		
		# We have newer payment information
		$Invoice_local->paid_at = $paid_at;
		
		# A series of from our Bal_Payment_Models to the PayPal standards
		$checks = array(
			'invoice' => array(
				'id', 'amount', 'currency', 'handling', 'shipping', 'tax'
			),
			'item' => array(
				'id', 'amount', 'quantity', 'shipping', 'tax', 'handling'
			),
			'response' = array(
				'business' => delve($Config,'business')
			)
		);
		
		# Validate Invoice
		foreach ( $checks['invoice'] as $check_field ) {
			# Fetch
			$new_value = real_value($Invoice->$check_field);
			$local_value = real_value($Invoice_local->$check_field);
			# Check
			$valid = ($new_value === $local_value) || (empty($new_value) && empty($local_value));
			if ( !$valid ) {
				throw new Bal_Exception(array(
					'A Invoice new vs local check failed',
					'check_field' => $check_field,
					'new_value' => $new_value,
					'local_value' => $local_value,
					'response' => $response
				));
			}
		}
		
		# Validate Items Size
		if ( count($Invoice->InvoiceItems) !== count($Invoice_local->InvoiceItems) ) {
			throw new Bal_Exception(array(
				'The Invoice new vs local check on Items count failed',
				'new_count' => count($Invoice->InvoiceItems),
				'local_count' => count($Invoice_local->InvoiceItems)
				'response' => $response
			));
		}
		
		# Validate Items
		$i = -1; foreach ( $Invoice->InvoiceItems as $InvoiceItem_new ) { ++$i;
			$InvoiceItem_local = $Invoice_local->InvoiceItems[$i]; // already guarenteed to exist from able check
			
			# Check Fields
			foreach ( $checks['item'] as $check_field ) {
				# Fetch
				$new_value = real_value($Invoice->$check_field);
				$local_value = real_value($Invoice_local->$check_field);
				# Check
				$valid = ($new_value === $local_value) || (empty($new_value) && empty($local_value));
				if ( !$valid ) {
					throw new Bal_Exception(array(
						'A Item new vs local check failed',
						'check_field' => $check_field,
						'new_value' => $new_value,
						'local_value' => $local_value,
						'response' => $response
					));
				}
			}
		}
		
		# Validate Response
		foreach ( $checks['response'] as $check_field => $value ) {
			$value1 = isset($this->response[$check]) ? $this->response[$check] : null;
			$value2 = $value;
			if ( is_numeric($value1) ) $value1 = intval($value1);
			if ( is_numeric($value2) ) $value2 = intval($value2);
			$valid = ($value1 === $value2) || (empty($value1) && empty($value2));
			if ( !$valid ) {
				throw new Exception('Response check failed on: '.$check.' ['.$value1.'|'.$value2.']');
				exit;
			}
		}
		
		# Check Status
		$status = strtolower(delve($this->response,'payment_status'));
		switch ( $status ) {
			case 'canceled_reversal':
				// Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.
				throw new Exception('Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.');
				break;
			case 'denied':
				// Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.
				throw new Exception('Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.');
				break;
			case 'expired':
				// Expired: This authorization has expired and cannot be captured.
				throw new Exception('Expired: This authorization has expired and cannot be captured.');
				break;
			case 'failed':
				// Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.
				throw new Exception('Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.');
				break;
			case 'voided':
				// Voided: This authorization has been voided.
				throw new Exception('Voided: This authorization has been voided.');
				break;
			case 'reversed':
				// Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.
				throw new Exception('Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.');
				break;
			
			case 'created':
				// Created: A German ELV payment is made using Express Checkout.
			case 'pending':
				// Pending: The payment is pending. See pending_reason for more information.
			case 'refunded':
				// Refunded: You refunded the payment.
			case 'processed':
				// Processed: A payment has been accepted.
			case 'completed':
				// Completed: The payment has been completed, and the funds have been added successfully to your account balance.
				break;
			
			default:
				// Unkown: Unkown payment status.
				throw new Exception('Unkown: Unkown payment status.');
				break;
		}
		
		
		// Apply
		$this->Order->status = $status;
		
		// Save
		$Store['Order'] = $this->Order;
		$Store['response'] = $this->response;
		$this->setStore($Store, $this->Order->id);
		
	}
	
	
	# ========================
	# Resources
	
	/**
	 * Send HTTP POST Request
	 * @param	string	The request URL
	 * @param	string	The POST Message fields in &name=value pair format
	 * @param	bool		determines whether to return a parsed array (true) or a raw array (false)
	 * @return	array		Contains a bool status, error_msg, error_no, and the HTTP Response body(parsed=httpParsedResponseAr  or non-parsed=httpResponse) if successful
	 * @access	public
	 * @static
	 * @author	PayPal Developer Network
	 */
	public static function PPHttpPost ($url, $fields, $parsed) {
		// Prepare
		if ( is_array($fields) ) {
			foreach ( $fields as $key => $value ) {
				$fields[$key] = $key.'='.rawurlencode(htmlspecialchars($value));
			}
			$fields = implode('&', $fields);
		}
		
		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		
		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		
		//getting response from server
		$httpResponse = curl_exec($ch);
		
		if(!$httpResponse) {
			return array("status" => false, "error_msg" => curl_error($ch), "error_no" => curl_errno($ch));
		}
		
		if(!$parsed) {
			return array("status" => true, "httpResponse" => $httpResponse);
		}
		
		$httpResponseAr = explode("\n", $httpResponse);
		
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = urldecode($tmpAr[1]);
			}
		}
		
		if(0 == sizeof($httpParsedResponseAr)) {
			$error = "Invalid HTTP Response for POST request($fields) to $url.";
			return array("status" => false, "error_msg" => $error, "error_no" => 0);
		}
		
		return array("status" => true, "httpParsedResponseAr" => $httpParsedResponseAr);
	}

}
