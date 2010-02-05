<?php
class Bal_View_Helper_App extends Zend_View_Helper_Abstract {

	# ========================
	# CONSTRUCTORS
	
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
	 * Self reference
	 */
	public function app ( ) {
		# Chain
		return $this;
	}
	
	# ========================
	# PARENT
	
	/**
	 * The App Plugin
	 * @var Bal_Controller_Plugin_App
	 */
	protected $_App = null;
	
	
	/**
	 * Returns @see Bal_Controller_Plugin_App
	 */
	public function getApp(){
		# Done
		return $this->_App;
	}
	
	/**
	 * Magic
	 * @return mixed
	 */
	function __call ( $method, $args ) {
		$Parent = $this->getApp();
		if ( method_exists($Parent, $method) ) {
			return call_user_func_array(array($Parent, $method), $args);
		} else {
			throw new Zend_Exception('Could not find the method: '.$method);
		}
		return false;
	}
	
	# ========================
	# CUSTOM
	
	public function get ( ) {
		# Prepare
		$result = null;
		$args = func_get_args();
		
		# Cycle
		$result = delver_array($this->view, $args);
		
		# Done
		return $result;
	}
	
	public function getStylesheetUrl ( $file, $for = null ) {
		$file = 'styles/' . $file;
		$url = $this->getApp()->getFileUrl($file, $for);
		return $url;
	}
	
	public function getScriptUrl ( $file, $for = null ) {
		$file = 'scripts/' . $file;
		$url = $this->getApp()->getFileUrl($file, $for);
		return $url;
	}
	
	public function getLocaleStylesheetUrl ( $for = null ) {
		# Attempt Locale
		$file = 'locale/'.$this->view->locale()->getFullLocale().'.css';
		$url = $this->getStylesheetUrl($file, $for);
		
		# Attempt Language
		if ( !$url ) {
			$file = 'locale/'.$this->view->locale()->getLanguage().'.css';
			$url = $this->getStylesheetUrl($file, $for);
		}
		
		# Done
		return $url;
	}
	
	public function headStyle ( ) {
		return $this->view->headStyle();
	}
	
	public function headTitle ( ) {
		return $this->view->headTitle();
	}
	
	public function headMeta ( ) {
		# Meta
		$this->view->headMeta();
		
		# Done
		return $this->view->headMeta();
	}
	
	public function headLink ( $offset = 100 ) {
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
		$style = $this->getStylesheetUrl('style.css', 'public');
		if ( $style )	$this->view->headLink()->offsetSetStylesheet($offset+2, $style);
		$style = $this->getStylesheetUrl($layout === 'layout' ? 'style.css' : 'style-'.$layout.'.css', 'theme');
		if ( $style )	$this->view->headLink()->offsetSetStylesheet($offset+3, $style);
		
		# Favicon
		$this->view->headLink(array('rel' => 'icon', 'href' => $App->getFileUrl('favicon.ico'), 'type' => 'image/x-icon'), 'PREPEND');
		
		# Done
		return $this->view->headLink();
	}
	
	public function headScript ( $offset = 100 ) {
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		
		# Style
		$script = $this->getScriptUrl('script.js', 'public');
		if ( $script )	$this->view->headScript()->offsetSetFile($offset+0, $script);
		$script = $this->getScriptUrl($layout === 'layout' ? 'script.js' : 'script-'.$layout.'.js', 'theme');
		if ( $script )	$this->view->headScript()->offsetSetFile($offset+1, $script);
		
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