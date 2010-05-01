<?php

abstract class Bal_Payment_Gateway_Abstract {
	
	# ========================
	# Variables
	
	/** @var Bal_Payment_Model_Invoice $Invoice */
	protected $Invoice;
	
	/** @var Zend_Config $Config */
	protected $Config;
	
	/** @var Zend_Log $Log */
	protected $Log;
	
	
	# ========================
	# Constructors
	
	/**
	 * Construct our Payment Gateway
	 * @param array $Config
	 * @param Zend_Log $Log[optional]
	 * @return $this
	 */
	public function __construct ( array $Config, Zend_Log $Log = null ) {
		# Apply
		$this->setConfig($Config)->setLog($Log);
		# Return this
		return $this;
	}
	
	
	# ========================
	# Invoice
	
	/**
	 * Set the Invoice
	 * @param Bal_Payment_Model_Invoice $Invoice
	 * @return $this
	 */
	public function setInvoice ( Bal_Payment_Model_Invoice $Invoice ) {
		$this->Invoice = $Invoice;
		return $this;
	}
	
	/**
	 * Fetch the Invoice
	 * @return Bal_Payment_Model_Invoice
	 */
	public function getInvoice ( ) {
		return $this->Invoice;
	}
	
	
	# ========================
	# Config
	
	/**
	 * Set the Config
	 * @throws Bal_Exception
	 * @param array $Config
	 * @return $this
	 */
	public function setConfig ( array $Config ) {
		# Apply
		$this->Config = $Config;
		
		# Chain
		return $this;
	}
	
	/**
	 * Fetch the Config
	 * @return array
	 */
	public function getConfig ( ) {
		return $this->Config;
	}
	
	
	# ========================
	# Log
	
	/**
	 * Set the Log
	 * @param Zend_Log $Log
	 * @return $this
	 */
	public function setLog ( Zend_Log $Log ) {
		$this->Log = $Log;
		return $this;
	}
	
	/**
	 * Fetch the Log
	 * @return Zend_Log
	 */
	public function getLog ( ) {
		return $this->Log;
	}
	
	
	# ========================
	# Invoice
	
	
	public function getDetails ( ) {
		return $this->getStore($this->Order->id);
	}
	
	public function setStore ( $store, $file = 'store' ) {
		// Log
		$file = $this->settings['transactions_path'].'/'.$file.'.txt';
		file_put_contents($file, serialize($store));
		// Done
		return $this;
	}
	public function getStore ( $file = 'store' ) {
		// Log
		$file = $this->settings['transactions_path'].'/'.$file.'.txt';
		return unserialize(file_get_contents($file));
	}
	
	public function log ( $stuff, $file = 'log' ) {
		// Log
		$file = $this->settings['logs_path'].'/'.$file.'.txt';
		file_put_contents($file, "\n\n----\n".var_export($stuff,true), FILE_APPEND);
		// Done
		return $this;
	}
	
}