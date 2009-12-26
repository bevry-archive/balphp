<?php
class Bal_View_Helper_Help extends Zend_View_Helper_Abstract {

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
		# Set View
		$this->view = $view;
		
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
		$helpers = $this->view->getHelpers();
		$variables = $this->view->getVars();
		$help = array(
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
		);
		if ( !empty($this->view->form) ) $help['Applicable Form'] = $this->view->form;
		$result = Bal_Debug::render($help, 'Help');
		return $result;
	}
	
}