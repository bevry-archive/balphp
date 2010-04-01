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
	 * Returns @see Bal_Controller_Plugin_App
	 */
	public function getApp(){
		return Bal_Controller_Plugin_App::getInstance();
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
	# NAVIGATION
	
	public function getNavigation ( $code ) {
		# Prepare
		$NavigationMenu = delve($this->view,'Navigation.'.$code);
		if ( !$NavigationMenu ) throw new Zend_Exception('Could not find Navigation Menu: '.$code);
		
		# Return
		return $NavigationMenu;
	}
	
	public function getNavigationMenu ( $code) {
		# Prepare
		$NavigationMenu = $this->getNavigation($code);
		if ( !$NavigationMenu ) throw new Zend_Exception('Could not find Navigation Menu: '.$code);
		
		# Render
		$result = $this->view->navigation()->menu()->setContainer($NavigationMenu);
		
		# Return
		return $result;
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
	
	public function headLink ( array $options = array() ) {
		# Options
		$default = array(
			'offset'=>100,
			'csscaffold'=>false,
			'locale'=>true,
			'browser'=>true,
			'style'=>true,
			'theme'=>true,
			'favicon'=>true
		);
		$options = handle_options($default,$options,true);
		extract($options);
		
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		
		# Locale
		if ( $locale ) {
			$url = $this->getLocaleStylesheetUrl();
			if ( $url )	$this->view->headLink()->offsetSetStylesheet($offset+0, $url);
		}
		
		# Browser
		if ( $browser ) {
			$url = $this->getBrowserStylesheetUrl();
			if ( $url )	$this->view->headLink()->offsetSetStylesheet($offset+1, $url);
		}
	
		# Style
		if ( $style ) {
			$url = $this->getStylesheetUrl('style.css', 'public');
			if ( $url )	$this->view->headLink()->offsetSetStylesheet($offset+2, $url);
		}
		
		# Theme
		if ( $theme ) {
			$url = $this->getStylesheetUrl($layout === 'layout' ? 'style.css' : 'style-'.$layout.'.css', 'theme');
			if ( $url )	$this->view->headLink()->offsetSetStylesheet($offset+3, $url);
		}
		
		# Favicon
		if ( $favicon ) {
			$url = $App->getFileUrl('favicon.ico');
			$this->view->headLink(array('rel' => 'icon', 'href' => $url, 'type' => 'image/x-icon'), 'PREPEND');
		}
		
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