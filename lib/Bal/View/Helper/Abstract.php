<?php
abstract class Bal_View_Helper_Abstract extends Zend_View_Helper_Abstract {
	
	# ========================
	# VARIABLES
	
	protected $_options = array(
	);
	
	
	# ========================
	# CONSTRUCTORS
	
	/**
	 * Construct
	 * @param array $options
	 */
	public function __construct ( array $options = array() ) {
		# Prepare
		$result = true;
		
		# Options
		$this->mergeOptions($options);
		
		# Done
		return $result;
	}
	
	# ========================
	# OPTIONS
	
	/**
	 * Get the helper option
	 * @param string $name
	 * @param mixed $default
	 */
	public function getOption ( $name ) {
		# Get
		return $this->_options[$name];
	}
	
	/**
	 * Set the helper option
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption ( $name, $value ) {
		# Set
		$this->_options[$name] = $value;
		# Chain
		return $this;
	}
	
	/**
	 * Merge the helper options
	 * @param array $options
	 */
	public function mergeOptions ( array $options ) {
		# Merge
		$this->_options = array_merge($this->_options, $options);
		# Chain
		return $this;
	}

}
