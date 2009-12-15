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
	protected function __call ( $method, $args ) {
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
	
	public function getFileUrl ( $file ) {
		# Prepare
		$App = $this->getApp();
		$publicPath = $App->getPublicPath();
		$publicUrl = $App->getPublicUrl();
		$themePath = $App->getThemePath();
		$themeUrl = $App->getThemeUrl();
		$result = false;
		
		# Handle
		if ( file_exists($themePath . DIRECTORY_SEPERATOR . $file) ) {
			$result = $themeUrl . '/' . $file;
		} elseif ( file_exists($publicPath . DIRECTORY_SEPERATOR . $file) ) {
			$result = $publicUrl . '/' . $file;
		}
		
		# Done
		return $result;
	}
	
	public function getStylesheetUrl ( $file ) {
		$file = 'styles/' . $file;
		$url = $this->getFileUrl($file);
		return $url;
	}
	
	public function getLocaleStylesheetUrl ( ) {
		# Attempt Locale
		$file = 'locale/'.$this->locale()->getFullLocale().'.css';
		$url = $this->getStylesheetUrl($file);
		
		# Attempt Language
		if ( !$url ) {
			$file = 'locale/'.$this->locale()->getLanguage().'.css';
			$url = $this->getStylesheetUrl($file);
		}
		
		# Done
		return $url;
	}
	
	public function appendStylesheets ( ) {
		# Locale
		$locale = $this->getLocaleStylesheetUrl();
		if ( $locale )	$this->headLink()->appendStylesheet($locale);
		
		# Browser
		$browser = $this->getBrowserStylesheetUrl();
		if ( $browser )	$this->headLink()->appendStylesheet($browser);
		
		# Style
		$style = $this->getStylesheetUrl('style.css');
		if ( $style )	$this->headLink()->appendStylesheet($style);
		
		# Done
		return $this->headLink();
	}
	
	public function appendScripts ( ) {
		# Style
		$script = $this->getScriptUrl('script.js');
		if ( $style )	$this->headScript()->appendFile($style);
		
		# Done
		return $this->headScript();
	}
	
	public function getBrowserStylesheetUrl ( ) {
		# Attempt Browser
		$file = 'browser/'.$GLOBALS['BROWSER']['browser'].$GLOBALS['BROWSER']['version'].'.css';
		$url = $this->getStylesheetUrl($file);
		
		# Done
		return $url;
}