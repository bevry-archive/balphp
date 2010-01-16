<?php
require_once 'Zend/Controller/Action/Helper/Abstract.php';
class Bal_Controller_Plugin_Abstract extends Zend_Controller_Plugin_Abstract {
	
	# ========================
	# OPTIONS
	
	protected $_options = array(
	);
	
	
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

	
	# ========================
	# FRONT
	
	/**
	 * Get the Front Controller
	 */
	public function getFront ( ) {
		return Zend_Controller_Front::getInstance();
	}
	
	/**
	 * Get the Front Controller's Router
	 */
	public function getRouter ( ) {
		return $this->getFront()->getRouter();
	}
	
	
	# ========================
	# CONFIG
	
	
	/**
	 * Gets the Application Configuration (as array) or specific config variable
	 * @param string $delve [optional]
	 * @param mixed $default [optional]
	 * @return mixed
	 */
	public function getConfig ( $delve = null, $default = null ) {
		# Prepare:
		$applicationConfig = array();
		
		# Load
		if ( Zend_Registry::isRegistered('applicationConfig') ) {
			$applicationConfig = Zend_Registry::get('applicationConfig');
		}
		
		# Check
		if ( !$delve ) {
			return $applicationConfig;
		}
		
		# Delve
		$value = delve($applicationConfig, $delve, $default);
		
		# Done
		return $value;
	}
	
	
}
