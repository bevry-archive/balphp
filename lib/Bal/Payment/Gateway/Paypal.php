<?php
/**
 * Paypal Payment Gateway
 * @author Benjamin "balupton" Lupton
 */
class Bal_Payment_Gateway_Paypal extends Bal_Payment_Gateway_Abstract {
	
	
	# ========================
	# Constants
	
    const RESPONSE_PDT = 1;
	const RESPONSE_IPN = 2;
	
	
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
	protected $maps = array(
		'request' => array(
			// NOTE: The below uses the PDT guide
			'invoice' => array(
				'id' 						=> 'invoice',
				'subtotal' 					=> 'amount', // after discount, before shipping, handling, tax
				'currency_code' 			=> 'currency_code',
				
				'handling_total' 			=> 'handling',
				'shipping_total' 			=> 'shipping',
				
				'tax_total' 				=> 'tax_cart',
				
				'weight_total' 				=> 'weight_cart',
				'weight_unit' 				=> 'weight_unit'
			),
			'item' => array(
				// for multiples the (_x) is appended
				'id' 						=> 'item_number',
				'title' 					=> 'item_name',
				'quantity' 					=> 'quantity',
				'price_each_total' 			=> 'amount', // after discount, before shipping, handling, tax
				
				'shipping_first' 			=> 'shipping',
				'shipping_additional' 		=> 'shipping2',
				
				'tax_total'					=> 'tax',
				// 'tax_each' 				=> 'tax',
				// 'tax_rate' 				=> 'tax_rate',
				// ^ too complicated using both
				
				'weight_each' 				=> 'weight', // unsure if each or total should be used
				'weight_unit' 				=> 'weight_unit',
				'handling_total' 			=> 'handling',
			),
			'payer' => array(
				'address1' 					=> 'address1',
				'address2' 					=> 'address2',
				'city' 						=> 'city',
				'country_code' 				=> 'country', // is actually country_code
				'state' 					=> 'state',
				'postcode' 					=> 'zip',
				'firstname' 				=> 'first_name',
				'lastname' 					=> 'last_name',
				'language' 					=> 'lc',
				'charset' 					=> 'charset'
			)
		),
		'response' => array(
			// NOTE: The below uses the IPNGuide
			'invoice' => array(
				'id' 						=> 'invoice',
				'total' 					=> 'mc_gross', // after discount, shipping, handling, tax
				'currency_code' 			=> 'mc_currency',
				
				'payment_fee'				=> 'mc_fee',
				'handling_total'			=> 'mc_handling',
				'shipping_total'			=> 'mc_shipping', // shipping
				'tax_total'					=> 'tax',
				
				'paid_at' 					=> 'payment_date',
				'payment_status' 			=> 'payment_status',
				'payment_error'				=> 'reason_code',
			),
			'item' => array(
				// for multiples the (x) is appended
				// underscores gets trimmed if we only have a singular
				'id' 						=> 'item_number',
				'title' 					=> 'item_name',
				'quantity' 					=> 'quantity',
				
				'total' 					=> 'mc_gross_', // after discount, shipping, handling, tax
				'payment_fee'				=> 'mc_fee_',
				'shipping' 					=> 'mc_shipping',
				'tax' 						=> 'tax',
				'handling' 					=> 'mc_handling'
			),
			'payer' => array(
				'email'						=> 'payer_email',
				'address1' 					=> 'address_street',
				'address2' 					=> 'address_street2',
				'city' 						=> 'address_city',
				'country' 					=> 'address_country',
				'country_code' 				=> 'address_country_code',
				'state' 					=> 'address_state',
				'postcode' 					=> 'address_zip',
				'firstname' 				=> 'first_name',
				'lastname' 					=> 'last_name',
				'charset' 					=> 'charset',
				'phone' 					=> 'contact_phone'
			)
		)
	);
	
	/**
	 * A map of values to validate
	 * @var array $validate
	 */
	protected $validates = array(
		'config' => array(
			'url', 'token', 'business', 'notify_url', 'return'
		),
		'invoice' => array(
			'id', 'amount', 'currency', 'handling', 'shipping', 'tax'
		),
		'item' => array(
			'id', 'amount', 'quantity', 'handling', 'shipping', 'tax'
		),
		'request' => array(
			'business', 'notify_url', 'return'
		),
		'response' => array(
			'business'
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
		$validate = $this->validates['config'];
		foreach ( $validate as $validate_field ) {
			$value = delve($Config,$validate_field);
			if ( empty($value) ) {
				throw new Bal_Exception(array(
					'Missing a configuration field for the paypal payment gateway',
					'config' => $Config,
					'field' => $validate_field,
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
	# Requests
	
	/**
	 * Generates our Payment Gateway Form
	 * Used to submit the invoice to the payment gateway for their handling
	 * @param Bal_Payment_Model_Invoice $Invoice
	 * @return string HTML Form
	 */
	public function generateForm ( Bal_Payment_Model_Invoice $Invoice ) {
		# Prepare
		$Log = $this->getLog();
		$Config = $this->getConfig();
		$request = $this->generateRequest($Invoice);
		
		# Determine Type
		if ( sizeof($Invoice->InvoiceItems) > 1 ) {
			$type = 'cart';
		}
		else {
			$type = 'buynow';
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
		$params = $this->validates['request'];
		foreach ( $params as $param ) {
			$config_value = delve($Config,$param);
			if ( $config_value ) {
				$request[$param] = $config_value;
			}
		}
		
		# Store our Request
		$this->store('paypal-request-'.$Invoice->id, array(
			'Invoice' => $Invoice,
			'request' => $request,
			'Config' => $Config
		));
		
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
	 * @param Bal_Payment_Model_Invoice $Invoice
	 * @return array $request
	 */
	public function generateRequest ( Bal_Payment_Model_Invoice $Invoice ) {
		# Prepare
		$maps = $this->maps['request'];
		$request = array();
		
		
		# --------------------------
		# Invoice
		
		# Prepare Invoice
		$Invoice->applyTotals();
		
		# Merge mapped Invoice to request
		$request = array_merge($request, array_keys_map($Invoice, $maps['invoice']));
		
		
		# --------------------------
		# Payer
		
		# Fetch Payer
		$Payer = $Invoice->Payer;
		
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
		
		# Fetch InvoiceItems
		$InvoiceItems = $Invoice->InvoiceItems;
		
		# Check if we have a single or multiple items
		$InvoiceItems_count = count($InvoiceItems);
		
		# Handle Appropriatly
		if ( $InvoiceItems_count === 1 ) {
			# Single Item
			
			# Prepare
			$InvoiceItems_i = 0;
			$maps_item = $maps['item'];
			
			# Merge mapped Item to request
			$InvoiceItem = $InvoiceItems[$InvoiceItems_i];
			$request = array_merge($request, array_keys_map($InvoiceItem, $maps_item));
		}
		else {
			# Multiple Items
			
			# Cycle through the remote fields
			for ( $InvoiceItems_i=0; $InvoiceItems_i<$InvoiceItems_count; ++$InvoiceItems_i ) {
				# Prepare
				$maps_item = $maps['item'];
				
				# Adjust
				foreach ( $maps_item as &$value ) {
					$value .= '_'.($InvoiceItems_i+1);
				}
				
				# Merge mapped Item to request
				$InvoiceItem = $InvoiceItems[$InvoiceItems_i];
				$request = array_merge($request, array_keys_map($InvoiceItem, $maps_item));
			}
		}
		
		
		# --------------------------
		# Done
		
		# Return request
		return $request;
	}
	
	
	# ========================
	# Responses
	
	/**
	 * Handle a PayPal Response
	 * Receives an $response array and then passes to the appropriate handler for the response type
	 * @throws Bal_Exception
	 * @param array $response
	 * @return Bal_Payment_Model_Invoice
	 */
	public function handleResponse ( array $response ) {
		# Prepare
		$Invoice = null;
		
		# Check
		if ( empty($response['tx']) && empty($response['txn_id']) ) {
			throw new Bal_Exception(array(
				'Response received is not a valid paypal response',
				'response' => $response
			));
		}
		
		# Determine Response Type
		$response_type = !empty($response['tx']) ? self::RESPONSE_PDT : self::RESPONSE_IPN;
		
		# Handle Appropriatly
		switch ( $response_type ) {
			case self::RESPONSE_PDT:
				$Invoice = $this->handleResponsePDT($response);
				break;
				
			case self::RESPONSE_IPN:
			default:
				$Invoice = $this->handleResponseIPN($response);
				break;
		}
		
		# Return Invoice
		return $Invoice;
	}
	
	/**
	 * Handle a PDT Response
	 * After payment, if the Payer returns to our site, PayPal will perform the redirect as a PDT Request
	 * @throws Bal_Exception
	 * @param array $response_pdt
	 * @return $this
	 */
	public function handleResponsePDT ( array $response_pdt ) {
		# Prepare
		$Invoice = null;
		
		# Check we have a transaction id
		if ( empty($reponse_pdt['tx']) ) {
			# Error
			throw new Bal_Exception(array(
				'PDT received an HTTP GET request without a transaction ID.',
				'response' => $reponse_pdt
			));
		}
		
		# Send a request to paypal to receive the Authentic Reponse for that transaction
		$response_authentic = $this->fetchResponse($reponse_pdt['tx']);
		
		# Fetch the Authentc Response's Data
		$response_authentic_data = $response["httpParsedResponseAr"];
		
		# Check that the Authentic Responses's Transaction ID does not differ from that of our original PDT Response
		if ( $response_authentic_data['txn_id'] !== $response_pdt['tx'] ) {
			// Error
			throw new Bal_Exception(array(
				'Transaction IDs do not match.',
				'response_authentic_data' => $response_authentic_data,
				'response_pdt' => $response_pdt
			));
			exit;
		}
		
		# Merge the PDT and Authentic Response Data to generate a complete valid response
		$response_complete = array_merge($response_pdt, $response_authentic_data);
		
		# Now continue as if we were an IPN request
		$Invoice = $this->handleIPN($response_complete);
		
		# Return Invoice
		return $Invoice;
	}
	
	/**
	 * Handle a IPN Request
	 * As soon as a update to the payment is performed, PayPal will try to send us a notification request (IPN) to inform us
	 * This may however occur before or after the PDT request due to network slowdowns
	 * Therefor we also trigger this in our @handlePDT function
	 * @param array $response
	 * @return Bal_Payment_Model_Invoice
	 */
	public function handleResponseIPN ( array $response ) {
		# Prepare
		$Config = $this->GetConfig();
		$maps = $this->maps['response'];
		
		# Fetch the local Invoice
		$local_Store = $this->store('paypal-request-'.$Invoice->id);
		$local_Invoice = delve($local_Store, 'Invoice');
		
		
		# --------------------------
		# Check the Response
		
		# Validate
		$this->validateResponse($response);
		
		# Build an Invoice from the Response
		$remote_Invoice = $this->fetchInvoice($response);
		
		# Validate the Invoice
		$remote_Invoice->validate();
		
		# Compare the local invoice against the remote invoice
		$this->validateInvoices($local_Invoice, $remote_Invoice);
		
		
		# --------------------------
		# Return our Invoice
		
		// Theoritically the only fields that should be changed are:
		// - payment_status
		// - paid_at
		
		# Store the remote Invoice
		$this->store('paypal-response-'.$Invoice->id, array(
			'Invoice' => $remote_Invoice,
			'response' => $response,
			'Config' => $Config
		));
		
		# Return remote Invoice
		return $remote_Invoice;
	}
	
	
	# ========================
	# Fetches
	
	
	/**
	 * Send off a request to paypal and return a response
	 * @throws Bal_Exception
	 * @param int $tx - transaction id
	 * @return array
	 */
	public function fetchResponse ( $tx ) {
		# Prepare
		$Config = $this->getConfig();
		
		# Send a request to paypal to receive the Authentic Reponse for that transaction
		$response = $this->PPHttpPost(delve($Config,'url'), array(
			'cmd' => '_notify-synch',
			'tx' => $tx,
			'at' => delve($Config,'token'),
		), true);
		
		# Check the status
		if( empty($response['status']) ) {
			// Error
			throw new Bal_Exception(array(
				'There was an error processing the authentic response for the PDT request',
				'response_error' => $response['error_no'].': '.$response['error_msg'],
				'response' => $response
			));
		}
		
		# Return response_authentic
		return $response;
	}
	
	protected function fetchInvoice ( array $response ) {
		# Prepare
		$map = $this->maps['invoice'];
		
		# Map a Invoice from the response
		$Invoice = new Bal_Payment_Model_Invoice(array_keys_map($response, array_flip_deep($map)));
		
		# Payer + InvoiceItems
		$Invoice->Payer = $this->fetchPayer($response);
		$Invoice->InvoiceItems = $this->fetchInvoiceItems($response);
		
		# Validate
		$Invoice->validate();
		
		# Return Invoice
		return $Invoice;
	}
	
	protected function fetchPayer ( array $response ) {
		# Prepare
		$map = $this->maps['payer'];
		
		# Map a Payer from the response
		$Payer = new Bal_Payment_Model_Payer(array_keys_map($response, array_flip_deep($map)));
		$Payer->validate();
		
		# Return Payer
		return $Payer;
	}
	
	protected function fetchInvoiceItems ( array $response ) {
		# Prepare
		$map = $this->maps['item'];
		$InvoicItems = array();
		
		# Check if we have a single or multiple items
		$InvoiceItems_count = count($InvoiceItems);
		
		# Handle Appropriatly
		if ( $InvoiceItems_count === 1 ) {
			# Single Item
			
			# Prepare
			$maps_item = $map; // reset map
			
			# Adjust
			foreach ( $maps_item as &$value ) {
				$value = trim($value, '_');
			}
				
			# Merge mapped Item to request
			$InvoiceItem = new Bal_Payment_Model_InvoiceItem(array_keys_map($response, array_flip_deep($maps_item)));
			$InvoiceItem->validate();
			$InvoiceItems[] = $InvoiceItem;
		}
		else {
			# Multiple Items
			
			# Cycle through the remote fields
			for ( $InvoiceItems_i=0; $i<$InvoiceItems_count; +$InvoiceItems_i ) {
				# Prepare
				$maps_item = $map; // reset map
				
				# Adjust
				foreach ( $maps_item as &$value ) {
					$value .= ($InvoiceItems_i+1);
				}
				
				# Merge mapped Item to request
				$InvoiceItem = new Bal_Payment_Model_InvoiceItem(array_keys_map($response, array_flip_deep($maps_item)));
				$InvoiceItem->validate();
				$InvoiceItems[] = $InvoiceItem;
			}
		}
		
		# Return InvoiceItems
		return $InvoiceItems;
	}
	
	
	# ========================
	# Validates
	
	/**
	 * Validate the response
	 * @throws Bal_Exception
	 * @param array $response
	 * @return true
	 */
	protected function validateResponse ( array $response ) {
		# Prepare
		$Config = $this->getConfig();
		$local_response = array(
			'business' => delve($Config,'business')
		);
		
		# Check Payment Date
		if ( !delve($response,'payment_date') ) {
			throw new Bal_Exception(array(
				'Response payment_date is empty',
				'response' => $response
			));
		}
		
		# Validate Response
		$this->validateCompare(
			$this->validates['response'],
			$local_response, $response,
			'A response local VS remote check failed'
		);
		
		# Return true
		return true;
	}
	
	/**
	 * Compare two items together and throw an exception is mismatch
	 * @throws Bal_Exception
	 * @param array $validate
	 * @param array|object $a
	 * @param array|object $b
	 * @param string $message
	 * @return true
	 */
	protected function validateCompare ( array $validate, $a, $b, $message ) { 
		# Handle
		foreach ( $validate as $check_field ) {
			# Fetch
			$value_a = real_value(delve($a,$check_field));
			$value_b = real_value(delve($b,$check_field));
			# Check
			$valid = ($value_a === $value_b) || (empty($value_a) && empty($value_b));
			if ( !$valid ) {
				throw new Bal_Exception(array(
					$message,
					'check_field' => $check_field,
					'value_a' => $value_a,
					'value_b' => $value_b
				));
			}
		}
		
		# Return true
		return true;
	}
	
	/**
	 * Validate the local Invoice against the remote Invoice
	 * @throws Bal_Exception
	 * @param Bal_Payment_Model_Invoice $local_Invoice
	 * @param Bal_Payment_Model_Invoice $remote_Invoice
	 * @return true
	 */
	protected function validateInvoices ( Bal_Payment_Model_Invoice $local_Invoice, Bal_Payment_Model_Invoice $remote_Invoice ) {
		# Compare Payment Date
		if ( strtotime($local_Invoice->paid_at) > strtotime($remote_Invoice->paid_at) ) {
			throw new Bal_Exception(array(
				'Local Invoice payment date is newer than the remote Invoice',
				'local_Invoice__paid_at' => date('r',$local_Invoice->paid_at),
				'remote_Invoice__paid_at' => date('r',$remote_Invoice->paid_at),
				'local_Invoice' => $local_Invoice,
				'remote_Invoice' => $remote_Invoice
			));
		}
		
		# Validate Invoice
		$this->validateCompare(
			$this->validates['invoice'],
			$local_Invoice, $remote_Invoice,
			'A Invoice local VS remote check failed'
		);
		
		# Validate Items Size
		if ( count($Invoice->InvoiceItems) !== count($local_Invoice->InvoiceItems) ) {
			throw new Bal_Exception(array(
				'The Invoice new vs local check on Items count failed',
				'remote_count' => count($remote_Invoice->InvoiceItems),
				'local_count' => count($local_Invoice->InvoiceItems)
			));
		}
		
		# Validate Items
		$i = -1; foreach ( $remote_Invoice->InvoiceItems as $remote_InvoiceItem ) { ++$i;
			$local_InvoiceItem = $local_Invoice->InvoiceItems[$i]; // already guarenteed to exist from above check
			
			# Check Fields
			$this->validateCompare(
				$this->validates['item'],
				$local_InvoiceItem, $remote_InvoiceItem,
				'A InvoiceItem local VS remote check failed'
			);
		}
		
		# Return true
		return true;
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
