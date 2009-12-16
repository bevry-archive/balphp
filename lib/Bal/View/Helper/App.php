<?php
class Bal_View_Helper_App extends Zend_View_Helper_Abstract {

	/**
	 * The App Plugin
	 * @var Bal_Controller_Plugin_App
	 */
	protected $_App = null;
	
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
		$this->_App = Zend_Controller_Front::getInstance()->getPlugin('Bal_Controller_Plugin_App');
		
		# Set
		$this->view = $view;
		
		# Chain
		return $this;
	}
	
	/**
	 * Returns @see Bal_Controller_Plugin_App
	 */
	public function getApp(){
		# Done
		return $this->_App;
	}
	
	/**
	 * Self reference
	 */
	public function app ( ) {
		# Chain
		return $this;
	}
	
	/**
	 * Magic
	 * @return mixed
	 */
	function __call ( $method, $args ) {
		$App = $this->getApp();
		if ( method_exists($App, $method) ) {
			return call_user_func_array(array($App, $method), $args);
		} else {
			throw new Zend_Exception('Could not find the method: '.$method);
		}
		return false;
	}
	
	# -----------
	# View stuff
	
	public function getStylesheetUrl ( $file ) {
		$file = 'styles/' . $file;
		$url = $this->getApp()->getFileUrl($file);
		return $url;
	}
	
	public function getScriptUrl ( $file ) {
		$file = 'scripts/' . $file;
		$url = $this->getApp()->getFileUrl($file);
		return $url;
	}
	
	public function getLocaleStylesheetUrl ( ) {
		# Attempt Locale
		$file = 'locale/'.$this->view->locale()->getFullLocale().'.css';
		$url = $this->getStylesheetUrl($file);
		
		# Attempt Language
		if ( !$url ) {
			$file = 'locale/'.$this->view->locale()->getLanguage().'.css';
			$url = $this->getStylesheetUrl($file);
		}
		
		# Done
		return $url;
	}
	
	public function appendStylesheets ( $offset = 100 ) {
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		
		# Locale
		$locale = $this->getLocaleStylesheetUrl();
		if ( $locale )	$this->view->headLink()->offsetSetStylesheet($offset+0, $locale);
		
		# Browser
		$browser = $this->getBrowserStylesheetUrl();
		if ( $browser )	$this->view->headLink()->offsetSetStylesheet($offset+1, $browser);
		
		# Style
		$style = $this->getStylesheetUrl($layout === 'layout' ? 'style.css' : 'style-'.$layout.'.css');
		if ( $style )	$this->view->headLink()->offsetSetStylesheet($offset+2, $style);
		
		# Done
		return $this->view->headLink();
	}
	
	public function appendScripts ( $offset = 100 ) {
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		
		# Style
		$script = $this->getScriptUrl($layout === 'layout' ? 'script.js' : 'script-'.$layout.'.js');
		if ( $script )	$this->view->headScript()->offsetSetFile($offset+0, $script);
		
		# Done
		return $this->view->headScript();
	}
	
	public function getBrowserStylesheetUrl ( ) {
		# Attempt Browser
		$file = 'browser/'.$GLOBALS['BROWSER']['browser'].$GLOBALS['BROWSER']['version'].'.css';
		$url = $this->getStylesheetUrl($file);
		
		# Done
		return $url;
	}
	
}