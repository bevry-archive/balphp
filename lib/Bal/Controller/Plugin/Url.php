<?php
class Bal_Controller_Plugin_Url extends Zend_Controller_Plugin_Abstract {

	# ========================
	# STATE VARIABLES
	
	protected $_params = array();
	protected $_route  = null;
	protected $_reset  = true;
	protected $_encode = true;
	protected $_url    = null;
	protected $_mode   = null;
	protected $_free   = array();
	
	/**
	 * A set of paths to look through for the file
	 */
	protected $_paths = array();
	
	
	# ========================
	# NORMAL VARIABLES
	
	/**
	 * Default Route
	 */
	protected $_default_route = null;
	
	/**
	 * Default Paths
	 */
	protected $_default_paths = array();
	
	/**
	 * The last backtrace so that we know who the offender may be in the is not cleared instance
	 */
	protected $_last_backtrace;
	
	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	public $view;
	
	
	# ========================
	# STATE CONSTRUCTORS
	
    /**
     * Generates an url given the name of a route.
     * @param  {array}		$urlOptions Options passed to the assemble method of the Route object.
     * @param  {mixed}		$name The name of a Route to use. If null it will use the current Route
     * @param  {boolean}	$reset Whether or not to reset the route defaults with those provided
     * @return {string}		Url for the link href attribute.
     */
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true) {
		# Prepare
		$result = false;
		
		# Handle Request
		if ( empty($urlOptions) && empty($name) ) {
			if ( !$this->isCleared() ) {
				throw new Bal_Exception(array(
					'Url Plugin: You have forgotten to clear the old url before creating a new.',
					'last_backtrace' => $this->_last_backtrace
				));
			}
			$result = $this;
			# Save stacktrace
			$this->_last_backtrace = get_backtrace_slim();
		}
		else {
			$result = $this->apply($urlOptions,$name,$reset,$encode)->toString();
		}
		
		# Return result
		return $result;
    }
	
	static public function getInstance ( ) {
		return Zend_Controller_Front::getInstance()->getPlugin('Bal_Controller_Plugin_Url');
	}
	
	/**
	 * Will check if the state is cleared
	 * @return boolean
	 */
	public function isCleared ( ) {
		return !$this->_params && !$this->_route && $this->_reset && $this->_encode && !$this->_url && !$this->_mode && empty($this->_paths) && empty($this->_free);
	}

	/**
	 * Will clear the state
	 * @return this
	 */
	public function clear ( ) {
		$this->free();
		$this->_params = array();
		$this->_route  = null;
		$this->_reset  = true;
		$this->_encode = true;
		$this->_url    = null;
		$this->_mode   = null;
		$this->_paths  = array();
		return $this;
	}
	
	/**
	 * Will apply a state
	 * @param {array} $params
	 * @param {string} $route
	 * @param {boolean} $reset
	 * @param {boolean} $encode
	 * @return this
	 */
	public function apply ( $params, $route, $reset, $encode ) {
		$this->clear();
		return $this->params($params)->route($route)->reset($reset)->encode($encode);
	}
	
	# ========================
	# STATE SETTERS
	
	/**
	 * Will replace the URL Paths we check with $paths
	 * @param {array} $paths
	 * @return this
	 */
	public function paths ( array $paths ) {
		$this->_paths = array();
		if ( array_key_exists('path',$paths) ) {
			$this->path($paths['path'], $paths['url']);
		}
		else {
			foreach ( $paths as $path ) {
				$this->path($path['path'], $path['url']);
			}
		}
		return $this;
	}
	
	/**
	 * Will add a URL Path to the set of Paths we check
	 * @param {string} $path
	 * @param {string} $url
	 * @return this
	 */
	public function path ( $path, $url ) {
		if ( !in_array($path,$this->_paths) ) {
			$this->_paths[] = array('path'=>$path,'url'=>$url);
		}
		return $this;
	}
	
	/**
	 * Will apply an array of params to the URL
	 * @param {array} $params
	 * @return this
	 */
	public function params ( array $params ) {
		foreach ( $params as $key => $value ) {
			$this->param($key,$value);
		}
		return $this;
	}
	
	/**
	 * Will apply a URL param to the URL
	 * @param {string} $name
	 * @param {mixed} $value
	 * @return this
	 */
	public function param ( $name, $value ) {
		$this->_params[$name] = $value;
		return $this;
	}
	
	/**
	 * Will set the mode to use for the URL
	 * @param {string|null} $mode
	 * @return this
	 */
	public function mode ( $mode ) {
		$this->_mode = $mode;
		return $this;
	}
	
	/**
	 * Will set the mode to use for the URL to full
	 * @return this
	 */
	public function full ( ) {
		return $this->mode('full');
	}
	
	/**
	 * Will set the mode to use for the URL to short
	 * @return this
	 */
	public function short ( ) {
		return $this->mode('short');
	}
	
	/**
	 * Will set the page URL param
	 * @param {mixed} $value
	 * @return this
	 */
	public function page ( $value ) {
		return $this->param('page',$value);
	}
	
	/**
	 * Will set the action URL param
	 * @param {mixed} $value
	 * @return this
	 */
	public function action ( $value ) {
		return $this->param('action',$value);
	}
	
	/**
	 * Will set the controller URL param
	 * @param {mixed} $value
	 * @return this
	 */
	public function controller ( $value ) {
		return $this->param('controller',$value);
	}
	
	/**
	 * Will set a default value
	 * @param {string} $what
	 * @param {mixed} $value
	 * @return this
	 */
	public function renege ( $what, $value ) {
		$var = '_default_'.$what;
		$this->$var = $value;
		return $this;
	}
	
	/**
	 * Will set the route to use for the URL
	 * @param {string} $value
	 * @return this
	 */
	public function route ( $value ) {
		$this->_route = $value;
		return $this;
	}
	
	/**
	 * Will set the reset value to use for the URL
	 * @param {boolean} $value
	 * @return this
	 */
	public function reset ( $value ) {
		$this->_reset = $value;
		return $this;
	}
	
	/**
	 * Will set the encode value to use for the URL
	 * @param {boolean} $value
	 * @return this
	 */
	public function encode ( $value ) {
		$this->_encode = $value;
		return $this;
	}
	
	/**
	 * Will set the URL to the $value
	 * @param {string} $value
	 * @return this
	 */
	public function hard ( $value ) {
		$this->_url = $value;
		return $this;
	}
	
	/**
	 * Free anything that needs cleaning/freeling
	 */
	public function free ( ) {
		if ( FREE_RESOURCES ) {
			foreach ( $this->_free as $Item ) {
				$Item->free(true);
			}
		}
		$this->_free = array();
	}
	
	/**
	 * Will assemble the URL
	 * @return {string} url
	 */
	public function assemble ( ) {
		# Prepare
		$url = false;
		
		# Handle
		if ( $this->_url !== null ) {
			$url = $this->_url;
		} else {
			$Router = $this->getRouter();
			$params = $this->_params;
			$route = $this->_route ? $this->_route : $this->_default_route;
			$reset = $this->_reset;
			$encode = $this->_encode;
			try {
        		$url = $Router->assemble($params, $route, $reset, $encode);
			}
			catch ( Exception $e ) {
				$Exceptor = new Bal_Exceptor($e);
				$Exceptor->log();
				throw new Bal_Exception(array(
					'Could not assemble URL',
					'params' => $params,
					'route' => $route,
					'reset' => $reset,
					'encode' => $encode
				));
			}
		}
		
		# Url Mode
		switch ( $this->_mode ) {
			case 'full':
				if ( begins_with($url,'/') ) {
					$url = $this->getBaseUrl(true) . $url;
				}
				break;
			
			case 'short':
				# Later we should use j.mp here
				break;
			
			case 'default':
			default:
				break;
		}
		
		# Return
		return $url;
	}
	
	/**
	 * Will fetch the URL as a String and Clear our State
	 * @return {string} url
	 */
	public function toString ( ) {
		# Prepare
		$url = '';
		
		# Assemble
		$url = $this->assemble();
			
		# Clear our URL for the next
		$this->clear();
		
		# Return url
		return $url;
	}
	
	/**
	 * @alias self::toString
	 */
	public function __toString ( ) {
		return $this->toString();
	}
	
	# ========================
	# HELPERS
	
	/**
	 * Will get the Router
	 * @return {Router}
	 */
	protected function getRouter ( ) {
		return Zend_Controller_Front::getInstance()->getRouter();
	}
	
	/**
	 * Will get a Plugin
	 * @param {string} $plugin
	 * @return {Router}
	 */
	protected function getPlugin ( $plugin ) {
		return Zend_Controller_Front::getInstance()->getPlugin($plugin);
	}
	
	/**
	 * Will get an Item
	 * @param {string} $table
	 * @param {string} $item
	 * @return {Router}
	 */
	protected function getItem ( $table, $item ) {
		return Bal_Doctrine_Core::getItem($table,$item);
	}
	
	# ========================
	# STATE MODIFIERS
	
	/**
	 * Apply a Map as the URL State
	 * @param {Map} $map
	 * @return this
	 */
	public function map ( $map ) {
		$Route = delve($map,'Route',delve($map,'Map'));
		if ( !$Route ) {
			$pathColumn = Bal_App::getConfig('routing.defaults.pathColumn','path');
			if ( delve($map,$pathColumn) ) {
				$Route = $map;
			}
			else {
				throw new Bal_Exception(array(
					'Could not resolve the Map',
					'map' => is_object($map) ? get_class($map) : $map
				));
			}
		}
		return $this->route('map')->param('Map',$Route);
	}
	
	/**
	 * Apply a Search as the URL State
	 * @param {string} $query [optional]
	 * @param {string} $code [optional]
	 * @return this
	 */
	public function search ( $query = null, $code = null ) {
		# Apply
		if ( is_array($query) ) {
			extract($query);
		}
		if ( $query ) {
			$this->param('query',$query);
		}
		else {
			if ( !$code ) $code = $this->getPlugin('Bal_Controller_Plugin_App')->generateSearchCode();
			$this->param('code',$code);
		}
		
		# Chain
		return $this;
	}
	
	/**
	 * Apply an Item into the URL State
	 * @param {Item} $Item
	 * @param {string} $param [optional]
	 * @param {boolean} $error [optional]
	 * @return this
	 */
	public function item ( $Item, $param = null, $error = true ) {
		# Ensure Item
		$code = $id = null;
		if ( is_numeric($Item) ) {
			$id = $Item;
		}
		elseif ( is_string($Item) ) {
			$code = $Item;
		}
		elseif ( is_object($Item) || is_array($Item) ) {
			$code = delve($Item,'code');
			$id = delve($Item,'id');
		}
		
		# Apply Item
		if ( $code ) {
			if ( !$param ) $param = 'code';
			$this->param($param,$code);
		}
		elseif ( $id ) {
			if ( !$param ) $param = 'id';
			$this->param($param,$id);
		}
		elseif ( $error) {
			// throw new Zend_Exception('Empty item was passed to url::item()');
		}
		
		# Chain
		return $this;
	}
	
	
	/**
	 * @alias self::file
	 */
	public function content ( $input ) {
		# Prepare
		if ( is_string($input) ) {
			$Item = $this->getItem('Content',$input);
			$this->_free[] = $Item;
		}
		else {
			$Item = $input;
		}
		
		# Fetch
		if ( !delve($Item,'id') ) {
			throw new Bal_Exception(array(
				'Could not resolve the Content Item',
				'input' => $input
			));
		}
		$result = $this->map($Item);
		
		# Return result
		return $result;
	}
	
	/**
	 * @alias self::file
	 */
	public function media ( $Item ) {
		return $this->file($Item);
	}
	
	/**
	 * Apply the URL for a File
	 * @param {File|string} $Item	Either a File Object or a string to a file path
	 * @return this
	 */
	public function file ( $Item ) {
		# Prepare
		if ( is_string($Item) && strstr($Item,'/') ) {
			$url = $this->getFileUrl($Item);
		}
		else {
			if ( is_string($Item) ) {
				$Item = $this->getItem('File',$Item);
				$this->_free[] = $Item;
			}
			$url = delve($Item,'url',false);
		}
		
		# Fetch
		$result = $this->hard($url);
		
		# Return result
		return $result;
	}
	
	/**
	 * Apply the URL for a User
	 * @param {User} $Item
	 * @return this
	 */
	public function user ( $Item ) {
		# Prepare
		if ( is_string($Item) ) {
			$Item = $this->getItem('User',$Item);
			$this->_free[] = $Item;
		}
		
		# Fetch
		$result = $this->route('default')->action('user')->item($Item);
		
		# Return result
		return $result;
	}
	
	/**
	 * Apply the Activation URL for a User
	 * @param {User} $Item
	 * @return this
	 */
	public function userActivate ( $Item ) {
		# Prepare
		if ( is_string($Item) ) {
			$Item = $this->getItem('User',$Item);
			$this->_free[] = $Item;
		}
		
		# Fetch
		$result = $this->route('default')->action('user-activate')->item($Item)->param('uid',delve($Item,'uid'));
		
		# Return result
		return $result;
	}
	
	/**
	 * Apply the URL for a Message
	 * @param {Message} $Item
	 * @return this
	 */
	public function message ( $Item ) {
		# Prepare
		if ( is_string($Item) ) {
			$Item = $this->getItem('Message',$Item);
			$this->_free[] = $Item;
		}
		
		# Fetch
		$result = $this->route('default')->action('message')->item($Item);
		
		# Return result
		return $result;
	}
	
	# ========================
	# SYSTEM URLS
	
	/**
	 * Get the root url for the site
	 * @return string
	 */
	public function getRootUrl ( ) {
		return ROOT_URL;
	}
	
	/**
	 * Get the base url for the site
	 * @param bool $prefix
	 * @return string
	 */
	public function getBaseUrl ( $prefix = false ) {
		$prefix = $prefix ? $this->getRootUrl() : '';
		$suffix = BASE_URL;
		return $prefix.$suffix;
	}

	/**
	 * Get the url for the public area
	 * @see getBaseUrl
	 * @param bool $prefix
	 * @return string
	 */
	public function getPublicUrl ( $prefix = false ) {
		$prefix = $prefix ? $this->getRootUrl() : '';
		$suffix = PUBLIC_URL;
		return $prefix.$suffix;
	}
	
	/**
	 * Get the path of the public directory
	 */
	public function getPublicPath ( ) {
		return PUBLIC_PATH;
	}
	
	# ========================
	# FILE URLS
	
	public function getFileUrl ( $file ) {
		# Prepare
		$file_url = false;
		$paths = !empty($this->_paths) ? $this->_paths : $this->_default_paths;
		
		# Check
		if ( empty($paths) ) {
			throw new Bal_Exception(array(
				'Url Plugin: There are no paths set to search for files.',
				'last_backtrace' => $this->_last_backtrace
			));
		}
		
		# Handle
		foreach ( $paths as $path ) {
			$file_path = $path['path'] . DIRECTORY_SEPARATOR . $file;
			if ( file_exists($file_path) && filesize($file_path) ) {
				$file_url = $path['url']. '/' . $file;
				break;
			}
			else {
				// echo '<!-- Could not find the file: ['.$file_path.'] ['.$file.']-->'."\n";
			}
		}
		
		# Return file_url
		return $file_url;
	}
	
}