<?php
class Bal_Controller_Action_Helper_App extends Bal_Controller_Action_Helper_Abstract {

	# ========================
	# VARIABLES
	
	
	protected $_options = array(
		'logged_out_forward' => array('login'),
		'logged_in_forward' => array('index')
	);
	
	
	# ========================
	# CONSTRUCTORS
	
	
	/**
	 * Construct
	 * @param array $options
	 */
	public function __construct ( array $options = array() ) {
		# Prepare
		$result = true;
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($options) : $result;
	}
	
	
	# ========================
	# NAVIGATION
	
	
	/**
	 * Make an Nagivation Item active
	 * @param string $menu
	 * @param string $id
	 * @param boolean $prefix [optional] Whether or not to prefix the id with the menu name
	 * @return bool
	 */
	public function applyNavigation ( ) {
		# Prepare
		$View = $this->getActionControllerView();
		$Request = $this->getActionControllerRequest();
		
		# Module Config
		$module = $Request->getModuleName();
		$module_path = Bal_App::getFrontController()->getModuleDirectory($module);
		$module_config_path = $module_path . '/config';
		
		# Navigation
		$NavData = file_get_contents($module_config_path . '/nav.json');
		$NavData = Zend_Json::decode($NavData, Zend_Json::TYPE_ARRAY);
		
		# Apply Navigation Menus
		$View->Navigation = array();
		foreach ( $NavData as $key => $value ) {
			$code = $Menu = null;
			if ( is_array($value) && !isset($value[0]) ) {
				foreach ( $value as $_key => $_value ) {
					$code = $key.'.'.$_key;
					$Menu = new Zend_Navigation($_value);
					$this->ApplyNavigationMenu($code, $Menu);
				}
			}
			else {
				$code = $key;
				$Menu = new Zend_Navigation($value);
				$this->ApplyNavigationMenu($code, $Menu);
			}
		}
		
		# Chain
		return $this;
	}
	
	public function applyNavigationMenu ( $code, Zend_Navigation $Navigation ) {
		# Prepare
		$View = $this->getActionControllerView();
		
		# Apply
		array_apply($View, 'Navigation.'.$code, $Navigation);
		
		# Done
		return true;
	}
	
	/**
	 * Make an Nagivation Item active
	 * @param string $menu
	 * @param string $id
	 * @param boolean $prefix [optional] Whether or not to prefix the id with the menu name
	 * @return bool
	 */
	public function activateNavigationItem ( $code, $id, $prefix = false, $error = true ) {
		# Prepare
		$View = $this->getActionControllerView();
		
		# Find
		$NavigationMenu = delve($View, 'Navigation.'.$code);
		if ( !$NavigationMenu ) throw new Zend_Exception('Could not find Navigation Menu: '.$code);
		
		# Prefix
		if ( $prefix ) {
			$id = str_replace('.','-',$code).'-'.$id;
		}
		
		# Activiate
		$result = $this->activateNavigationMenuItem($NavigationMenu, $id);
		if ( !$result && $error ) throw new Zend_Exception('Could not find Navigation Menu Item: '.$code.' -> '.$id);
		
		# Return Result
		return $result;
	}
	
	
	# ========================
	# AUTHENTICATION
	
	/**
	 * Logout the User
	 * @param mixed $redirect
	 */
	public function logout ( $redirect = true ) {
		# Logout
		$this->getApp()->logout();
		
		# Forward
		if ( $redirect ) $this->forwardOut($redirect);
		
		# Done
		return $this;
	}

	/**
	 * Login the User and forward
	 * @see forwardIn
	 * @see forwardOut
	 * @param string $username
	 * @param string $password
	 * @param string $locale
	 * @param string $remember
	 * @param bool $logged_out_forward
	 * @param bool $logged_in_forward
	 * @return bool
	 */
	public function loginForward ( $username, $password, $locale = null, $remember = null, $logged_out_forward = false, $logged_in_forward = false ) {
		$this->getApp()->login($username, $password, $locale, $remember);
		return $this->authenticate($logged_out_forward, $logged_in_forward);
	}
	
	/**
	 * Forward the Request
	 * @param mixed $redirect
	 */
	public function forward ($redirect) {
		$Redirector = $this->getActionController()->getHelper('Redirector');
		call_user_func_array(array($Redirector,'gotoSimple'), $redirect);
		return $this;
	}
	
	
	/**
	 * Forward the Request if Logged In
	 * @see forward
	 * @param mixed $redirect
	 */
	public function forwardIn ($redirect = true) {
		if ( $redirect === true ) $redirect = $this->getOption('logged_in_forward');
		return $this->forward($redirect);
	}
	
	/**
	 * Forward the Request if Logged Out
	 * @see forward
	 * @param mixed $redirect
	 */
	public function forwardOut ($redirect = true) {
		if ( $redirect === true ) $redirect = $this->getOption('logged_out_forward');
		return $this->forward($redirect);
	}
	
	/**
	 * Authenticate and Forward if need be
	 * @see forwardIn
	 * @see forwardOut
	 * @param bool $logged_out_forward
	 * @param bool $logged_in_forward
	 * @return bool
	 */
	public function authenticate ($logged_out_forward = false, $logged_in_forward = false) {
		# Prepare
		$result = null;
		
		# Check Login Status
		if ( $this->getApp()->hasIdentity() ) {
			# Logged In
			# Forward
			if ( $logged_in_forward ) {
				$this->forwardIn($logged_in_forward);
			}
			# Done
			$result = true;
		}
		else {
			# Logged Out
			# Forward
			if ( $logged_out_forward ) {
				$this->forwardOut($logged_out_forward);
			}
			# Done
			$result = false;
		}
		
		# Done
		return $result;
	}
	
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
		
}
