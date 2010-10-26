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
		if ( !$NavigationMenu ) {
			throw new Zend_Exception('Could not find Navigation Menu: '.$code);
		}
		
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
			if ( !$NavigationMenu ) {
				throw new Zend_Exception('Could not find Navigation Menu: '.$code);
			}
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
		$headLink = $this->view->getHelper('HeadLinkBundler');
		
		# Options
		$default = array_merge(
			array(
				'csscaffold'			=> false,
				'favicon'				=> true,
				'jquery_ui'				=> 210,
				'jquery_sparkle'		=> 230,
				'jquery_lightbox'		=> 250,
				'syntax_highlighter'	=> 300,
				'editor'				=> 400,
				'style'					=> 500,
				'theme'					=> 600,
				'locale'				=> 700,
				'browser'				=> 800,
				'feeds'					=> 900
			),
			$App->getConfig('headLink', array())
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
			$url = $this->getMobileStylesheetUrl();
			if ( $url )	$headLink->offsetSetStylesheet($browser, $url);
		}
		
		# jQuery UI
		if ( $jquery_ui ) {
			$headLink->offsetSetStylesheet($jquery_ui, $script_url.'/jquery-ui-1.8.5.custom/css/cupertino/jquery-ui-1.8.5.custom.css');
		}
		
		# jQuery Sparkle
		if ( $jquery_sparkle ) {
			$jquery_sparkle_url = $script_url.'/jquery-sparkle';
			$headLink->offsetSetStylesheet($jquery_sparkle, $jquery_sparkle_url.'/styles/jquery.sparkle.min.css');
		}
		
		# jQuery Lightbox
		if ( $jquery_lightbox ) {
			$jquery_lightbox_url = $script_url.'/jquery-lightbox';
			$headLink->offsetSetStylesheet($jquery_lightbox, $jquery_lightbox_url.'/styles/jquery.lightbox.min.css');
		}
		
		# Editor
		if ( $editor ) {
			switch ( $this->getConfig('editor.code') ) {
				case 'bespin':
					$bespin_url = $script_url.'/bespin-0.9a2-custom';
					$headLink->headLink(
						array(
							'id' => 'bespin_base',
							'rel' => '',
							'href' => $bespin_url
						),
						'PREPEND'
					);
					$headLink->offsetSetStylesheet($editor, $bespin_url.'/BespinEmbedded.css');
					break;
				
				case 'aloha':
					break;
					
				case 'tinymce':
					break;
				
				default:
					throw new Exception('Unknown Editor Code');
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
			if ( $csscaffold ) $url .= '?scaffold';
			if ( $url )	$headLink->offsetSetStylesheet($theme, $url);
		}
		
		# Favicon
		if ( $favicon ) {
			$url = $App->getFileUrl('favicon.ico');
			$this->view->headLink(array('rel' => 'icon', 'href' => $url, 'type' => 'image/x-icon'), 'PREPEND');
		}
		
		# Feeds
		if ( $feeds ) {
			$url = $App->getUrl()->route('feed')->action('rss')->toString();
			$this->view->headLink(array('rel' => 'alternate', 'title' => 'RSS Feed', 'href' => $url, 'type' => 'application/rss+xml'), 'PREPEND');
			//$url = $App->getUrl()->route('feed')->action('atom')->toString();
			//$this->view->headLink(array('rel' => 'alternate', 'title' => 'Atom Feed', 'href' => $url, 'type' => 'application/atom+xml'), 'PREPEND');
		}
		
		# Return headLink
		return $headLink;
	}
	
	public function headScript ( array $options = array() ) {
		# Prepare
		$App = $this->getApp();
		$layout = $App->getMvc()->getLayout();
		$browserInfo = $this->getBrowserInfo();
		$headScript = $this->view->getHelper('HeadScriptBundler');
		
		# Options
		$default = array_merge(
			array(
				'modernizr'				=> 100,
				'json' 					=> 110,
				'ie9_js'				=> 120,
				'jquery' 				=> 200,
				'jquery_ui' 			=> 210,
				'jquery_plugins' 		=> 220,
				'jquery_sparkle' 		=> 230,
				'jquery_ajaxy' 			=> 240,
				'jquery_lightbox' 		=> 250,
				'syntax_highlighter'	=> 300,
				'editor' 				=> 400,
				'script' 				=> 500,
				'theme' 				=> 600,
				'compiled'				=> 10
			),
			$App->getConfig('headScript', array())
		);
		$options = handle_options($default,$options,true);
		extract($options);
		
		# Compiled
		$headScript->setCompiledOffset($compiled);
		
		# URLs
		$public_url = $App->getPublicUrl();
		$back_url = $App->getAreaUrl('back');
		$front_url = $App->getAreaUrl('front');
		$script_url = PUBLIC_SCRIPTS_URL;
		
		# Modernizr
		if ( $modernizr ) {
			$headScript->offsetSetFile($modernizr, $script_url.'/modernizr-1.5.js');
		}
	
		# jQuery
		if ( $jquery ) {
			$headScript->offsetSetFile($jquery, $script_url.'/jquery-1.4.3.js');
		}
		
		# jQuery UI
		if ( $jquery_ui ) {
			$headScript->offsetSetFile($jquery_ui, $script_url.'/jquery-ui-1.8.5.custom/js/jquery-ui-1.8.5.custom.min.js');
	  		$headScript->offsetSetScript($jquery_ui+1,'$.datepicker.setDefaults({dateFormat: "yy-mm-dd"});');
	    }
		
		# JSON
		if ( $json ) {
			$headScript->offsetSetScript($json, 'if ( typeof JSON === "undefined" ) $.appendScript("'.$script_url.'/json2.min.js");');
	    }
		
		# jQuery Plugins
		if ( $jquery_plugins ) {
			$headScript->offsetSetFile($jquery_plugins, $script_url.'/jquery.autogrow.min.js');
	    }
		
		# jQuery Sparkle
		if ( $jquery_sparkle ) {
			// Prepare
			$jquery_sparkle_url = $script_url.'/jquery-sparkle';
			$headScript->offsetSetFile($jquery_sparkle, $jquery_sparkle_url.'/scripts/jquery.sparkle'.(APPLICATION_ENV === 'production' ? '.min' : '').'.js');
			$headScript->offsetSetScript($jquery_sparkle+1,'$.Help.setDefaults({icon: \'<img src="'.$back_url.'/images/help.png" alt="help" class="help-icon" />\'});');
	    }
	
		# jQuery Ajaxy
		if ( $jquery_ajaxy ) {
			// Prepare
			$jquery_ajaxy_url = $script_url.'/jquery-ajaxy';
			$headScript->offsetSetFile($jquery_ajaxy, $jquery_ajaxy_url.'/scripts/jquery.ajaxy'.(APPLICATION_ENV === 'production' ? '.min' : '').'.js');
		}
		
		# jQuery Lightbox
		if ( $jquery_lightbox ) {
			$jquery_lightbox_url = $script_url.'/jquery-lightbox';
			$headScript->offsetSetFile($jquery_lightbox, $jquery_lightbox_url.'/scripts/jquery.lightbox'.(APPLICATION_ENV === 'production' ? '.min' : '').'.js');
		}
		
		# Syntax Highlighter
		if ( $syntax_highlighter ) {
			$sh_url = $script_url.'/jquery-syntaxhighlighter';
			$headScript->offsetSetFile($syntax_highlighter, $sh_url.'/scripts/jquery.syntaxhighlighter'.(APPLICATION_ENV === 'production' ? '.min' : '').'.js');
			$headScript->offsetSetScript($syntax_highlighter+1,
				'$.SyntaxHighlighter.init({
					"defaults": {
						"toolbar":false
					}
				});'
			);
		}
		
		# Editor
		if ( $editor ) {
			switch ( $this->getConfig('editor.code') ) {
				case 'tinymce':
					$tiny_mce_url = $script_url.'/tiny_mce-3.2.7';
					$headScript->offsetSetFile($editor,$tiny_mce_url.'/jquery.tinymce.js');
					$headScript->offsetSetScript($editor+1,'$.Tinymce.applyConfig("default",{script_url: "'.$tiny_mce_url.'/tiny_mce.js", content_css: "'.$front_url.'/styles/content.css"});');
					break;
				
				case 'aloha':
					# Preset Urls
					$aloha_url = $script_url.'/aloha-editor/WebContent';
					$aloha_plugins_cms_url = $script_url.'/aloha-plugins';
					# Include Include
					$headScript->offsetSetFile($editor++, $aloha_url.'/core/include.js', 'text/javascript');
					$headScript->prependScript('window.GENTICS_Aloha_base = "'.$aloha_url.'";');
					# Include Files
					$aloha_plugins = array(
						'plugins/eu.iksproject.plugins.Loader/plugin.js',
						'plugins/com.gentics.aloha.plugins.Format/plugin.js',
						'plugins/com.gentics.aloha.plugins.Table/plugin.js',
						'plugins/com.gentics.aloha.plugins.List/plugin.js',
						'plugins/com.gentics.aloha.plugins.Link/plugin.js',
						'plugins/com.gentics.aloha.plugins.GCN/plugin.js',
						'plugins/com.gentics.aloha.plugins.Image/plugin.js'
					);
					$aloha_plugins_cms = array(
						'com.bal.aloha.plugins.Attacher/plugin.js'
					);
					for ( $i=0,$n=sizeof($aloha_plugins); $i<$n; ++$i ) {
						$headScript->offsetSetFile($editor++, $aloha_url.'/'.$aloha_plugins[$i]);
					}
					for ( $i=0,$n=sizeof($aloha_plugins_cms); $i<$n; ++$i ) {
						$headScript->offsetSetFile($editor++, $aloha_plugins_cms_url.'/'.$aloha_plugins_cms[$i]);
					}
					break;
					
				case 'bespin':
					$bespin_url = $script_url.'/bespin-0.9a2-custom';
					$headScript->offsetSetFile($editor,$bespin_url.'/BespinEmbedded.js');
					break;
				
				default:
					throw new Exception('Unknown Editor Code');
					break;
			}
		}
			
		# Script
		if ( $script ) {
			$url = $this->getScriptUrl('script.js', 'public');
			if ( $url )	{
				$headScript->offsetSetFile($script, $url);
			}
		}
		
		# Theme
		if ( $theme ) {
			$url = $this->getScriptUrl($layout === 'layout' ? 'script.js' : 'script-'.$layout.'.js', 'theme');
			if ( $url )	$headScript->offsetSetFile($theme, $url);
		}
		
		# Return headScript
		return $headScript;
	}
	
	public function getBrowserInfo() {
		return get_browser_info();
	}
	
	public function isBrowser($browser, $version = null, $mobile = null){
		$browserInfo = $this->getBrowserInfo();
		$result = $browserInfo['browser'] === $browser && (!$mobile || $browserInfo['mobile'] === $mobile);
		if ( $version ) {
			if ( is_array($version) ) {
				$result = $result && version_compare($browserInfo['version'], $version[1], $version[0]);
			}
			else {
				$result = $result && ($browserInfo['version'] === $version);
			}
		}
		return $result;
	}
	
	public function getHtmlClassAttribute ( ) {
		$browserInfo = $this->getBrowserInfo();
		$class = array();
		$class[] = $browserInfo['browser'];
		$class[] = $browserInfo['environment'];
		if ( $browserInfo['version'] ) $class[] = $browserInfo['browser'].$browserInfo['version'];
		return implode(' ',$class);
	}
	
	public function getMobileStylesheetUrl ( ) {
		# Prepare
		$browser = $this->getBrowserInfo();
		$url = false;
		
		if ( $browser['mobile'] ) {
			# Attempt Browser with Version
			$file = 'browser/'.$browser['browser'].$browser['version'].'mobile.css';
			$url = $this->getStylesheetUrl($file);
			if ( !$url ) {
				# Attempt Browser without Version
				$file = 'browser/'.$browser['browser'].'mobile.css';
				$url = $this->getStylesheetUrl($file);
				if ( !$url ) {
					# Attempt Just Mobile
					$file = 'browser/mobile.css';
					$url = $this->getStylesheetUrl($file);
				}
			}
		}
		
		# Return url
		return $url;
	}
	
	public function getBrowserStylesheetUrl ( ) {
		# Prepare
		$browser = $this->getBrowserInfo();
		
		# Attempt Browser with Version
		$file = 'browser/'.$browser['browser'].$browser['version'].'.css';
		$url = $this->getStylesheetUrl($file);
		if ( !$url ) {
			# Attempt Browser without Version
			$file = 'browser/'.$browser['browser'].'.css';
			$url = $this->getStylesheetUrl($file);
		}
		
		# Return url
		return $url;
	}
	
	public function footer ( ) {
		# Prepare
		$analytics_code = $this->app()->getConfig('services.analytics.code');
		$reinvigorate_code = $this->app()->getConfig('services.reinvigorate.code');
		
		# Analytics
		if ( $analytics_code ) : ?>
			<script type="text/javascript">
			/*<![CDATA[*/
				var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
				document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
			/*]]>*/
			</script><script type="text/javascript">
			/*<![CDATA[*/
				// Setup Google Analytics
				var pageTracker = _gat._getTracker("<?=$analytics_code?>");
				pageTracker._initData();
				if ( Modernizr||false ) {
					pageTracker._setCustomVar(1, "html5.boxshadow", Modernizr.boxshadow ? "yes" : "no" , 2 );
					pageTracker._setCustomVar(2, "html5.multiplebgs", Modernizr.multiplebgs ? "yes" : "no", 2 );
					pageTracker._setCustomVar(3, "html5.fontface", Modernizr.fontface ? "yes" : "no", 2 );
					pageTracker._setCustomVar(4, "html5.csstransitions", Modernizr.csstransitions ? "yes" : "no", 2 );
					pageTracker._setCustomVar(5, "html5.borderradius", Modernizr.borderradius ? "yes" : "no", 2 );
				}
				// Check for Ajax Redirect
				if ( !(document.location.hash && jQuery && (jQuery.Ajaxy||false)) ) {
					// We do not wish to track if we are doing a Ajaxy redirect
					pageTracker._trackPageview();
				}
			/*]]>*/
			</script>
			<?
		endif;
		
		# ReInvigorate
		if ( $reinvigorate_code ) : ?>
			<script type="text/javascript" src="http://include.reinvigorate.net/re_.js"></script>
			<script type="text/javascript">
			/*<![CDATA[*/
			try {
				// Setup ReInvigorate
				reinvigorate.code = "<?=$reinvigorate_code?>";
				reinvigorate.url_filter = function(url) {
					if(url == reinvigorate.session.url && reinvigorate.url_override != null) {
						reinvigorate.session.url = url = reinvigorate.url_override;
					}
					return url.replace(/^https?:\/\/(www\.)?/,"http://");
				}
				reinvigorate.ajax_track = function(url) {
					reinvigorate.url_override = url;
					reinvigorate.track(reinvigorate.code);
				}
				// Check for Ajax Redirect
				if ( !(document.location.hash && jQuery && (jQuery.Ajaxy||false)) ) {
					// We do not wish to track if we are doing a Ajaxy redirect
					reinvigorate.url_override = null;
					reinvigorate.track(reinvigorate.code);
				}
			} catch(err) {}
			/*]]>*/
			</script>
			<?
		endif;
		
		# Done
		return;
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