<?php
abstract class Bal_Controller_Action_Helper_Abstract extends Zend_Controller_Action_Helper_Abstract {
	
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
	
	/**
	 * Get the Action Controller View
	 * @return
	 */
	public function getActionControllerView ( ) {
		return $this->getActionController()->view;
	}
	
	/**
	 * Get the Action Controller View
	 * @return
	 */
	public function getActionControllerRequest ( ) {
		return $this->getActionController()->getRequest();
	}
	
	# ========================
	# OPTIONS
	
	/**
	 * Get the helper option
	 * @param string $name
	 * @param mixed $default
	 */
	public function getOption ( $name, $default = null ) {
		# Get
		return empty($this->_options[$name]) ? $default : $this->_options[$name];
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
