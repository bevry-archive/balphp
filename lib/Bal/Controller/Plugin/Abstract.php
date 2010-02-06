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
	public function getFrontController ( ) {
		return Bal_App::getFrontController();
	}
	
	/**
	 * Get the Front Controller's Router
	 */
	public function getRouter ( ) {
		return Bal_App::getRouter();
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
		return Bal_App::getConfig($delve, $default);
	}
	
	
}
