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
	public function __construct ( array $Config, $Log = null ) {
		# Apply
		$this->setConfig($Config);
		if ( $Log ) {
			$this->setLog($Log);
		}
		
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
	
	/**
	 * Get the full path of a store file
	 * @param mixed $id
	 * @return string
	 */
	protected function getStoreFilePath ( $file ) {
		# Prepare
		$Config = $this->getConfig();
		$store_path = delve($Config,'store_path');
		
		# Handle
		if ( $store_path )
			$file_path = $store_path . DIRECTORY_SEPARATOR . $file . '.txt';
		else
			$file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Bal_Payment_Gateway-' . $file . '.txt';
		
		# Return file_path
		return $file_path;
	}
	
	/**
	 * Set or Get the file in the store
	 * @throws Bal_Exception
	 * @param string $file
	 * @param array $data [optional]
	 * @return mixed
	 */
	public function store ( $file, $data = null ) {
		# Prepare
		$file_path = $this->getStoreFilePath($file);
		$result = null;
		
		# Handle
		if ( $data === null ) {
			# Get Contents
			$data = file_get_contents($file_path);
			if ( $data === false ) {
				throw new Bal_Exception(array(
					'Failed to get the store data',
					'file_path' => $file_path
				));
			}
			
			# Unserialise Data
			$result = unserialize($data);
			if ( $result === false ) {
				throw new Bal_Exception(array(
					'Failed to unserialize the store data',
					'file_path' => $file_path,
					'data' => $data
				));
			}
		}
		else {
			# Serialise Data
			$result = serialize($data);
			if ( $result === false ) {
				throw new Bal_Exception(array(
					'Failed to serialise the store data',
					'file_path' => $file_path,
					'data' => $data
				));
			}
			
			# Put Contents
			$result = file_put_contents($file_path, $result);
			if ( $result === false ) {
				throw new Bal_Exception(array(
					'Failed to put the store data',
					'file_path' => $file_path,
					'data' => $data
				));
			}
		}
		
		# Chain
		return $result;
	}
	
}