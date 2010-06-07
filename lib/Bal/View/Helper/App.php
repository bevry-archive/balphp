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
	
	public function getNavigationMenu ( $code ) {
		# Prepare
		if ( $code instanceOf Zend_Navigation ) {
			$NavigationMenu = $code;
		}
		elseif ( is_array($code) ) {
			$NavigationMenu = new Zend_Navigation($code);
		}
		else {
			$NavigationMenu = $this->getNavigation($code);
			if ( !$NavigationMenu ) throw new Zend_Exception('Could not find Navigation Menu: '.$code);
		}
		
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
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		$headLink = $this->view->headLink();
		
		# Options
		$default = array(
			'csscaffold'	=> false,
			'locale'		=> 10,
			'browser'		=> 20,
			'jquery_ui'		=> 30,
			'editor'		=> 40,
			'style'			=> 50,
			'theme'			=> 60,
			'favicon'		=> true
		);
		$options = handle_options($default,$options,true);
		extract($options);
		
		# URLs
		$public_url = $App->getPublicUrl();
		$script_url = $public_url.'/scripts';
		
		# Locale
		if ( $locale ) {
			$url = $this->getLocaleStylesheetUrl();
			if ( $url )	$headLink->offsetSetStylesheet($locale, $url);
		}
		
		# Browser
		if ( $browser ) {
			$url = $this->getBrowserStylesheetUrl();
			if ( $url )	$headLink->offsetSetStylesheet($browser, $url);
		}
	
		# jQuery UI
		if ( $jquery_ui ) {
			$jquery_ui_url = $script_url.'/jquery-ui-1.7.2';
			if ( $url )	$headLink->offsetSetStylesheet($jquery_ui, $jquery_ui_url.'/css/cupertino/jquery-ui-1.7.2.custom.css');
		}
		
		# Editor
		if ( $editor ) {
			switch ( $this->getConfig('bal.editor') ) {
				case 'bespin':
					$bespin_url = $script_url.'/bespin-0.7.3';
					$headLink->offsetSetStylesheet($editor, $bespin_url.'/BespinEmbedded.compressed.css');
					break;
				
				default:
					break;
			}
		}
		
		# Style
		if ( $style ) {
			$url = $this->getStylesheetUrl('style.css', 'public');
			if ( $url )	$headLink->offsetSetStylesheet($style, $url);
		}
		
		# Theme
		if ( $theme ) {
			$url = $this->getStylesheetUrl($layout === 'layout' ? 'style.css' : 'style-'.$layout.'.css', 'theme');
			if ( $csscaffold ) $url .= '?csscaffold';
			if ( $url )	$headLink->offsetSetStylesheet($theme, $url);
		}
		
		# Favicon
		if ( $favicon ) {
			$url = $App->getFileUrl('favicon.ico');
			$this->view->headLink(array('rel' => 'icon', 'href' => $url, 'type' => 'image/x-icon'), 'PREPEND');
		}
		
		# Return headLink
		return $headLink;
	}
	
	public function headScript ( array $options = array() ) {
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		$headScript = $this->view->headScript();
		
		# Options
		$default = array(
			'modernizr'			=> 30,
			'jquery' 			=> 40,
			'jquery_ui' 		=> 50,
			'json' 				=> 60,
			'jquery_plugins' 	=> 70,
			'jquery_sparkle' 	=> 80,
			'jquery_ajaxy' 		=> 90,
			'editor' 			=> 100,
			'script' 			=> 110,
			'theme' 			=> 120 
		);
		$options = handle_options($default,$options,true);
		extract($options);
		
		# URLs
		$public_url = $App->getPublicUrl();
		$back_url = $App->getAreaUrl('back');
		$front_url = $App->getAreaUrl('front');
		$script_url = $public_url.'/scripts';
		
		# Modernizr
		if ( $modernizr ) {
			$headScript->offsetSetFile($modernizr, $public_url.'/scripts/modernizr-1.1.min.js');
		}
	
		# jQuery
		if ( $jquery ) {
			$headScript->offsetSetFile($jquery, APPLICATION_ENV === 'production' ? 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js' : $public_url.'/scripts/jquery-1.4.2.js');
		}
		
		# jQuery UI
		if ( $jquery_ui ) {
			$jquery_ui_url = $script_url.'/jquery-ui-1.7.2';
			$headScript->offsetSetFile($jquery_ui, APPLICATION_ENV === 'production' ? 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js' : $jquery_ui_url.'/js/jquery-ui-1.7.2.custom.min.js');
	    }
		
		# JSON
		if ( $json ) {
			$headScript->offsetSetScript($json, 'if ( typeof JSON === "undefined" ) $.appendScript("'.$script_url.'/json2.js");');
	    }
		
		# jQuery Plugins
		if ( $jquery_plugins ) {
			$headScript->offsetSetFile($jquery_plugins, $public_url.'/scripts/jquery.autogrow.js');
	    }
		
		# jQuery Sparkle
		if ( $jquery_sparkle ) {
			$jquery_sparkle_url = $script_url.'/jquery-sparkle';
			$headScript->offsetSetFile($jquery_sparkle, $jquery_sparkle_url.'/scripts/jquery.sparkle.js');
			$headScript->offsetSetScript($jquery_sparkle+1,'$.Help.applyConfig("default",{icon: \'<img src="'.$back_url.'/images/help.png" alt="help" class="help-icon" />\'});');
	    }
	
		# jQuery Ajaxy
		if ( $jquery_ajaxy ) {
			$jquery_ajaxy_url = $script_url.'/scripts/ajaxy/js';
			$headScript->offsetSetFile($jquery_ajaxy, $jquery_ajaxy_url.'/jquery.history.js');
			$headScript->offsetSetFile($jquery_ajaxy+1, $jquery_ajaxy_url.'/jquery.ajaxy.js');
		}
		
		# Editor
		if ( $editor ) {
			switch ( $this->getConfig('bal.editor') ) {
				case 'tinymce':
					$tiny_mce_url = $script_url.'/tiny_mce-3.2.7';
					$headScript->offsetSetFile($editor,$tiny_mce_url.'/jquery.tinymce.js');
					$headScript->offsetSetScript($editor+1,'$.Tinymce.applyConfig("default",{script_url: "'.$tiny_mce_url.'/tiny_mce.js", content_css: "'.$front_url.'/styles/content.css"});');
					break;
					
				case 'bespin':
					$bespin_url = $script_url.'/bespin-0.7.3';
					$headScript->offsetSetFile($editor,$bespin_url.'/BespinEmbedded.js');
					break;
				
				default:
					break;
			}
		}
			
		# Script
		if ( $script ) {
			$url = $this->getScriptUrl('script.js', 'public');
			if ( $url )	$headScript->offsetSetFile($script, $url);
		}
		
		# Theme
		if ( $theme ) {
			$url = $this->getScriptUrl($layout === 'layout' ? 'script.js' : 'script-'.$layout.'.js', 'theme');
			if ( $url )	$headScript->offsetSetFile($theme, $url);
		}
		
		# Return headScript
		return $headScript;
	}
	
	public function getBrowserStylesheetUrl ( ) {
		# Attempt Browser
		$file = 'browser/'.$GLOBALS['BROWSER']['browser'].$GLOBALS['BROWSER']['version'].'.css';
		$url = $this->getStylesheetUrl($file);
		
		# Done
		return $url;
	}
	
	
	# ========================
	# GETTERS
	
	
	/**
	 * Get a Record based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public function fetchRecord ( $table, array $params = array() ) {
		# Force
		$params = array_merge($params,array(
			'hydrationMode' => Doctrine::HYDRATE_ARRAY,
			'returnQuery' => false
		));
		# Forward
		return $this->getApp()->fetchRecord($table,$params);
	}
	
	/**
	 * Get Records based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public function fetchRecords ( $table, array $params = array() ) {
		# Force
		$params = array_merge($params,array(
			'hydrationMode' => Doctrine::HYDRATE_ARRAY,
			'returnQuery' => false
		));
		# Forward
		return $this->getApp()->fetchRecords($table,$params);
	}
	
}