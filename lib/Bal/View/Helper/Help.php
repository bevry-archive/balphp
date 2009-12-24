<?php
class Bal_View_Helper_Help extends Zend_View_Helper_Abstract {

	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	protected $_View;
	
	/**
	 * Apply View
	 * @param Zend_View_Interface $view
	 */
	public function setView (Zend_View_Interface $View) {
		# Set View
		$this->_View = $View;
		
		# Chain
		return $this;
	}
	
	/**
	 * Self reference
	 */
	public function help ( ) {
		# Chain
		return $this;
	}
	
	/**
	 * Render Help
	 * @return string
	 */
	function render ( ) {
		$helpers = $this->_View->getHelpers();
		$variables = $this->_View->getVars();
		$result = Bal_Debug::render(array(
			'Available Variables' => $variables,
			'Received Params' => array(
				'Request' => $_REQUEST,
				'Post' => $_POST,
				'Get' => $_GET,
				'Files' => $_FILES,
				'Cookies' => $_COOKIE,
				'Server' => $_SERVER
			),
			'Zend View Helpers' => $helpers
		), 'Help');
		return $result;
	}
	
}