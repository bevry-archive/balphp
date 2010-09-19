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
	const RESPONSE_STANDARD = 3;
	
	
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
				'currency_code' 			=> 'currency_code',
				'weight_unit' 				=> 'weight_unit',
				'weight_all_total' 			=> 'weight_cart',
				
				'price_all_total_d'			=> 'amount', 			// + discount, - shipping, handling, tax
				'shipping_all_total' 		=> 'shipping',
				'handling_all_total' 		=> 'handling',
				'tax_all_total' 			=> 'tax_cart'
			),
			'item' => array(
				// for multiples the (_x) is appended
				'id' 						=> 'item_number',
				'title' 					=> 'item_name',
				'quantity' 					=> 'quantity',
				'weight_unit' 				=> 'weight_unit',
				'weight_each' 				=> 'weight', 			// unsure if each or total should be used
				
				'price_each_d' 				=> 'amount', 			// + discount, - shipping, handling, tax
				'shipping_first' 			=> 'shipping',
				'shipping_additional' 		=> 'shipping2',
				'handling_all_total' 		=> 'handling',
				'tax_each_total'			=> 'tax',
			),
			'payer' => array(
				'address1' 					=> 'address1',
				'address2' 					=> 'address2',
				'city' 						=> 'city',
				'country_code' 				=> 'country', 			// is actually country_code
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
				'currency_code' 			=> 'mc_currency',
				'payment_fee'				=> 'mc_fee',
				'payment_status' 			=> 'payment_status',
				'payment_error'				=> 'reason_code',
				'paid_at' 					=> 'payment_date',
				
				'price_total' 				=> 'mc_gross',			// + discount, shipping, handling, tax
				'shipping_all_total'		=> 'mc_shipping', 		// altenative is: shipping
				'handling_all_total'		=> 'mc_handling',
				'tax_all_total'				=> 'tax'
			),
			'item' => array(
				// for multiples the (x) is appended
				// underscores gets trimmed if we only have a singular
				'id' 						=> 'item_number',
				'title' 					=> 'item_name',
				'payment_fee'				=> 'mc_fee_',
				'quantity' 					=> 'quantity',
				
				'price_all_total_dhs' 		=> 'mc_gross_',			// + discount, shipping, handling, - tax
				'handling_all_total' 		=> 'mc_handling',
				'shipping_all_total' 		=> 'mc_shipping',
				'tax_all_total' 			=> 'tax',				// unsure if each or all should be used
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
			'url', 'business', 'notify_url', 'return'
			// token is optional, as we support standard responses
		),
		'invoice' => array(
			'id', 'currency_code', 'price_total', 'shipping_all_total', 'handling_all_total', 'tax_all_total'
		),
		'item' => array(
			'id', 'quantity', 'price_all_total_dhs', 'shipping_all_total', 'handling_all_total',
			// 'tax_all_total' - paypal does not return the tax back!
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
		$Config = $this->getConfig();
		$request = $this->generateRequest($Invoice);
		
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
		$Config = $this->getConfig();
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
		# Request Type
		
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
		
		
		# --------------------------
		# Config
		
		# Add some config variables to request if they are set
		$params = $this->validates['request'];
		foreach ( $params as $param ) {
			$config_value = delve($Config,$param);
			if ( $config_value ) {
				$request[$param] = $config_value;
			}
		}
		
		
		# --------------------------
		# Done
		
		# Store our Request
		$this->store('paypal-'.$Invoice->id.'-request', array(
			'Invoice' => $Invoice,
			'request' => $request,
			'Config' => $Config
		));
		
		# Return request
		return $request;
	}
	
	
	# ========================
	# Responses
	
	/**
	 * Determine the response from the $response_type
	 * @param mixed $response_type
	 * @return mixed
	 */
	public static function fetchResponse ( $response_type ) {
		# Prepare
		$response = false;
		
		# Determine
		switch ( $response_type ) {
			case self::RESPONSE_PDT:
			case self::RESPONSE_STANDARD:
				$response = $_GET;
				break;
			
			case self::RESPONSE_IPN:
				$response = $_POST;
				break;
			
			default:
				throw new Bal_Exception(array(
					'Unsupported response type passed',
					'response_type' => $response_type
				));
				break;
		}
		
		# Return response
		return $response;
	}
	
	/**
	 * Determine the response type from the _POST and _GET
	 * @return mixed
	 */
	public static function fetchResponseType ( ) {
		# Prepare
		$response_type = false;
		
		# Determine
		if ( empty($_POST) && !empty($_GET) ) {
			if ( !empty($_GET['tx']) ) {
				$response_type = self::RESPONSE_PDT;
			}
			elseif ( !empty($_GET['txn_id']) ) {
				$response_type = self::RESPONSE_STANDARD;
			}
		}
		elseif ( !empty($_POST) && empty($_GET) ) {
			$response_type = self::RESPONSE_IPN;
		}
		else {
			throw new Bal_Exception(array(
				'Could not determine the response type',
				'POST' => $_POST,
				'GET' => $_GET
			));
		}
		
		# Return response_type
		return $response_type;
	}
	
	/**
	 * Handle a PayPal Response
	 * Receives an $response array and then passes to the appropriate handler for the response type
	 * @throws Bal_Exception
	 * @param array $response
	 * @param mixed $response_type
	 * @return Bal_Payment_Model_Invoice
	 */
	public function handleResponse ( array $response = array(), $response_type = null ) {
		# Prepare
		$Invoice = null;
		
		# Defaults
		if ( empty($response_type) )	$response_type = self::fetchResponseType();
		if ( empty($response) )			$response = self::fetchResponse($response_type);
		
		# Check
		if ( empty($response['tx']) && empty($response['txn_id']) ) {
			throw new Bal_Exception(array(
				'The received response is not a valid paypal response',
				'response' => $response
			));
		}
		
		# Handle Appropriatly
		switch ( $response_type ) {
			case self::RESPONSE_PDT:
				$Invoice = $this->handleResponsePdt($response);
				break;
				
			case self::RESPONSE_IPN:
				$Invoice = $this->handleResponseIpn($response);
				break;
				
			case self::RESPONSE_STANDARD:
				$Invoice = $this->handleResponseStandard($response);
				break;
			
			default:
				throw new Bal_Exception(array(
					'Unknown response type',
					'response' => $response
				));
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
	public function handleResponsePdt ( array $response_pdt ) {
		# Prepare
		$Invoice = null;
		
		# Check we have a transaction id
		if ( empty($response_pdt['tx']) ) {
			# Error
			throw new Bal_Exception(array(
				'PDT received without a transaction ID.',
				'response' => $response_pdt
			));
		}
		
		# Send a request to paypal to receive the Authentic Reponse for that transaction
		$reply_pdt = $this->fetchReplyPdt($response_pdt);
		
		# Fetch the Authentc Response's Data
		$response_authentic = $reply_pdt['httpParsedResponseAr'];
		
		# Check that the reply succeded
		if ( !begins_with($reply_pdt['httpResponse'], 'SUCCESS') ) {
			throw new Bal_Exception(array(
				'The PDT reply did not succeed.',
				'reply' => $reply_pdt,
				'response_authentic' => $response_authentic,
				'response_pdt' => $response_pdt
			));
		}
		
		# Check that the Authentic Responses's Transaction ID does not differ from that of our original PDT Response
		if ( $response_authentic['txn_id'] !== $response_pdt['tx'] ) {
			throw new Bal_Exception(array(
				'The PDT reply did not match the transaction ID.',
				'reply' => $reply_pdt,
				'response_authentic' => $response_authentic,
				'response_pdt' => $response_pdt
			));
		}
		
		# Merge the PDT and Authentic Response Data to generate a complete valid response
		$response_complete = array_merge($response_pdt, $response_authentic);
		
		# Now continue to handle the response
		$Invoice = $this->handleResponseStandard($response_complete);
		
		# Return Invoice
		return $Invoice;
	}
	
	/**
	 * Handle a IPN Response
	 * As soon as a update to the payment is performed, PayPal will try to send us a notification request (IPN) to inform us
	 * This may however occur before or after the PDT request due to network slowdowns
	 * @throws Bal_Exception
	 * @param array $response_ipn
	 * @return $this
	 */
	public function handleResponseIpn ( array $response_ipn ) {
		# Prepare
		$Invoice = null;
		
		# Check we have a transaction id
		if ( empty($response_ipn['txn_id']) ) {
			# Error
			throw new Bal_Exception(array(
				'The IPN response was received without a transaction ID.',
				'response' => $response_ipn
			));
		}
		
		# Send a request to paypal to determine if the response is authentic
		$reply_ipn = $this->fetchReplyIpn($response_ipn);
		
		# Check that the reply was valid
		if ( !begins_with($reply_ipn['httpResponse'], 'VERIFIED') ) {
			throw new Bal_Exception(array(
				'The IPN reply came back invalid.',
				'reply_ipn' => $reply_ipn,
				'response_ipn' => $response_ipn
			));
		}
		
		# Now continue to handle the response
		$Invoice = $this->handleResponseStandard($response_ipn);
		
		# Return Invoice
		return $Invoice;
	}
	
	/**
	 * Handle a Response (For both IPN and PDT Responses)
	 * @throws Bal_Exception
	 * @param array $response
	 * @return Bal_Payment_Model_Invoice
	 */
	public function handleResponseStandard ( array $response ) {
		# Prepare
		$Config = $this->GetConfig();
		$maps = $this->maps['response'];
		
		# Check we have a transaction id
		if ( empty($response['txn_id']) ) {
			# Error
			throw new Bal_Exception(array(
				'Response received without a transaction ID.',
				'response' => $response
			));
		}
		
		# Check we have a invoice id
		if ( empty($response['invoice']) ) {
			# Error
			throw new Bal_Exception(array(
				'Response received without a invoice ID.',
				'response' => $response
			));
		}
		
		# Fetch the local Invoice
		$local_Store = $this->store('paypal-'.$response['invoice'].'-request');
		$local_Invoice = delve($local_Store, 'Invoice');
		$request = $local_Store['request'];
		
		
		# --------------------------
		# Check the Response
		
		# Validate
		$this->validateResponse($response, $request);
		
		# Build an Invoice from the Response
		$remote_Invoice = $this->fetchInvoice($response);
		
		# Validate the Invoice
		// $remote_Invoice->validate();
		
		# Compare the local invoice against the remote invoice
		$this->validateInvoices($local_Invoice, $remote_Invoice);
		
		
		# --------------------------
		# Return our Invoice
		
		// Theoritically the only fields that should be changed are:
		// - payment_status
		// - paid_at
		
		# Store the remote Invoice
		$this->store('paypal-'.$remote_Invoice->id.'-response-'.time(), array(
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
	public function fetchReplyPdt ( array $response ) {
		# Prepare
		$Config = $this->getConfig();
		
		# Prepare
		$url = delve($Config,'url');
		$token = delve($Config,'token');
		
		# Check the token
		if( !$token ) {
			throw new Bal_Exception(array(
				'We were not supplied a token for PDT. Perhaps you want to try a STANDARD response instead.',
				'Config' => $Config
			));
		}
		
		# Prepare Request
		$request = array(
			'cmd' => '_notify-synch',
			'tx' => delve($response,'tx'),
			'at' => $token,
		);
		
		# Send a request to paypal to receive the Authentic Reponse for that transaction
		$reply = $this->PPHttpPost($url, $request, true);
		
		# Check the reply
		if( empty($reply['status']) ) {
			throw new Bal_Exception(array(
				'There was an error processing the authentic reply for the PDT request',
				'error' => $reply['error_no'].': '.$reply['error_msg'],
				'reply' => $reply
			));
		}
		
		# Return reply
		return $reply;
	}
	
	/**
	 * Send off a request to paypal and return a response
	 * @throws Bal_Exception
	 * @param array $response
	 * @return array
	 */
	public function fetchReplyIpn ( array $response ) {
		# Prepare
		$Config = $this->getConfig();
		
		# Prepare
		$url = delve($Config,'url');
		
		# Prepare Request
		$request = array(
			'cmd' => '_notify-validate'
		);
		$request += $response;
		
		# Send a request to paypal to receive the Authentic Reponse for that transaction
		$reply = $this->PPHttpPost($url, $request, true);
		
		# Check the reply
		if( empty($reply['status']) ) {
			throw new Bal_Exception(array(
				'There was an error processing the authentic reply for the IPN request',
				'error' => $reply['error_no'].': '.$reply['error_msg'],
				'reply' => $reply
			));
		}
		
		# Return reply
		return $reply;
	}
	
	/**
	 * Fetch the Invoice Model representation from our Response
	 * @throws Bal_Exception
	 * @param array $response
	 * @return Bal_Payment_Model_Invoice
	 */
	protected function fetchInvoice ( array $response ) {
		# Prepare
		$map = $this->maps['response']['invoice'];
		
		# Map a Invoice from the response
		$invoice = array_keys_map($response, array_flip_deep($map));
		
		# Adjust
		$invoice['payment_status'] = strtolower($invoice['payment_status']);
		
		# Generate
		$Invoice = new Bal_Payment_Model_Invoice($invoice);
		
		# Payer + InvoiceItems
		$Invoice->Payer = $this->fetchPayer($response);
		$Invoice->InvoiceItems = $this->fetchInvoiceItems($response);
		
		# Check Payment Status
		$payment_status = $Invoice->payment_status;
		switch ( $payment_status ) {
			case 'awaiting':
				// Awaiting: Awaiting an action
				break;
				
			case 'canceled_reversal':
				// Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.
				throw new Bal_Exception(array(
					'Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.',
					'Invoice' => $Invoice
				));
				break;
			case 'denied':
				// Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.
				throw new Bal_Exception(array(
					'Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.',
					'Invoice' => $Invoice
				));
				break;
			case 'expired':
				// Expired: This authorization has expired and cannot be captured.
				throw new Bal_Exception(array(
					'Expired: This authorization has expired and cannot be captured.',
					'Invoice' => $Invoice
				));
				break;
			case 'failed':
				// Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.
				throw new Bal_Exception(array(
					'Failed: The payment has failed. This happens only if the payment was made from your customer’s bank account.',
					'Invoice' => $Invoice
				));
				break;
			case 'voided':
				// Voided: This authorization has been voided.
				throw new Bal_Exception(array(
					'Voided: This authorization has been voided.',
					'Invoice' => $Invoice
				));
				break;
			case 'reversed':
				// Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.
				throw new Bal_Exception(array(
					'Reversed: A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.',
					'Invoice' => $Invoice
				));
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
				throw new Bal_Exception(array(
					'Unknown: Unknown payment status',
					'payment_status' => $payment_status,
					'Invoice' => $Invoice
				));
				break;
		}
		
		# Validate
		//$Invoice->validate();
		
		# Return Invoice
		return $Invoice;
	}
	
	/**
	 * Fetch the Payer Model representation from our Response
	 * @throws Bal_Exception
	 * @param array $response
	 * @return Bal_Payment_Model_Invoice
	 */
	protected function fetchPayer ( array $response ) {
		# Prepare
		$map = $this->maps['response']['payer'];
		
		# Map a Payer from the response
		$Payer = new Bal_Payment_Model_Payer(array_keys_map($response, array_flip_deep($map)));
		//$Payer->validate();
		
		# Return Payer
		return $Payer;
	}
	
	/**
	 * Fetch the InvoiceItems Model representation from our Response
	 * @throws Bal_Exception
	 * @param array $response
	 * @return Bal_Payment_Model_Invoice
	 */
	protected function fetchInvoiceItems ( array $response ) {
		# Prepare
		$map = $this->maps['response']['item'];
		$InvoiceItems = array();
		
		# Check for num_cart_items
		if( empty($response['num_cart_items']) ) {
			if ( empty($response['item_name']) ) {
				// We don't have an item or item count
				throw new Bal_Exception(array(
					'Response received however did not include any item(s) information',
					'response' => $response
				));
			}
			else {
				// We have an item, but no count, which means we only have one item
				$InvoiceItems_count = 1;
			}
		}
		else {
			// We have a item count
			$InvoiceItems_count = $response['num_cart_items'];
		}
		
		# Check if we have a single or multiple items
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
			
			# Adjust Total
			// as we only have one item, paypal doesn't return it's mc_gross/price_all_total_dhs, instead only the cart total
			// so we must calculate it by removing the cart total from the tax total
			// we are using the already assigned values rather than the response values here
			$InvoiceItem->price_all_total_dhs -= $InvoiceItem->tax_all_total;
			
			# Append to Items
			$InvoiceItems[] = $InvoiceItem;
		}
		else {
			# Multiple Items
			
			# Cycle through the remote fields
			for ( $InvoiceItems_i=0; $InvoiceItems_i<$InvoiceItems_count; ++$InvoiceItems_i ) {
				# Prepare
				$maps_item = $map; // reset map
				
				# Adjust
				foreach ( $maps_item as &$value ) {
					$value .= ($InvoiceItems_i+1);
				}
				
				# Merge mapped Item to request
				$InvoiceItem = new Bal_Payment_Model_InvoiceItem(array_keys_map($response, array_flip_deep($maps_item)));
				
				# Append to Items
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
	protected function validateResponse ( array $response, array $request ) {
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
			$request, $response,
			'A request VS response check failed'
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
			$valid = ($value_a == $value_b) || (empty($value_a) && empty($value_b));
			if ( !$valid ) {
				throw new Bal_Exception(array(
					$message . ' ['.$check_field.'] ['.$value_a.'] ['.$value_b.']',
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
		if ( count($local_Invoice->InvoiceItems) !== count($remote_Invoice->InvoiceItems) ) {
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
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		
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
		
		if( 0 == sizeof($httpParsedResponseAr) && !$httpResponse ) {
			$error = "Invalid HTTP Response for POST request($fields) to $url.";
			return array("status" => false, "error_msg" => $error, "error_no" => 0, "httpResponse" => $httpResponse);
		}
		
		return array("status" => true, "httpParsedResponseAr" => $httpParsedResponseAr, "httpResponse" => $httpResponse);
	}

}
