<?php
require_once 'Zend/Exception.php';
class Bal_Exception extends Zend_Exception {
	
    /**
     * @var array
     */
    protected $information = null;

    /**
     * Construct the exception
     *
     * @param  string|array $msg
     * @param  int $code
     * @param  Exception $previous
     * @return void
     */
    public function __construct($msg = '', $code = 0, Exception $previous = null) {
		# Prepare
		$message = '';
		$information = array();
		
		# Fetch Message
		if ( is_array($msg) ) {
			$message = array_shift($msg); // first element is our message
			$information = $msg; // the rest is our information
		} else {
			$message = $msg;
		}
		
		# Set our Information
		$this->setInformation($information);
		
		# Construct our Message
        parent::__construct($message, $code, $previous);
    }
	
	/**
	 * Set our Information
	 * @param array $information
	 * @return $this
	 */
	public function setInformation ( array $information ) {
		$this->information = $information;
		return $this;
	}
	
	/**
	 * Get our Information
	 * @return array
	 */
	public function getInformation ( ) {
		return $this->information;
	}
	
}
