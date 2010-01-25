<?php
require_once 'Zend/View/Helper/Abstract.php';
class Bal_View_Helper_Log extends Zend_View_Helper_Abstract {
	
	# ========================
	# CONSTRUCT
	
	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	public $view;

	/**
	 * Apply View
	 * @param Zend_View_Interface $view
	 */
	public function setView (Zend_View_Interface $view) {
		# Apply
		$this->_Log = Bal_Log::getInstance();
		
		# Set
		$this->view = $view;
		
		# Done
		return true;
	}
	
	/**
	 * Self Reference
	 * @return Zend_View_Helper_Interface
	 */
	public function log ( ) {
		# Chain
		return $this;
	}
	
	# ========================
	# PARENT
	
	/**
	 * The App Plugin
	 * @var Bal_Controller_Plugin_App
	 */
	protected $_Log = null;
	
	/**
	 * Returns @see Bal_Controller_Plugin_App
	 */
	public function getLog(){
		# Done
		return $this->_Log;
	}
	
	/**
	 * Magic
	 * @return mixed
	 */
	function __call ( $method, $args ) {
		$Parent = $this->getLog();
		if ( method_exists($Parent, $method) ) {
			return call_user_func_array(array($Parent, $method), $args);
		} else {
			throw new Zend_Exception('Could not find the method: '.$method);
		}
		return false;
	}
	
}