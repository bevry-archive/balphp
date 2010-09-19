<?php
abstract class Bal_Controller_Plugin_App_Abstract extends Bal_Controller_Plugin_Abstract {

	# ========================
	# VARIABLES
	
	protected $_User = null;
	
	protected $_options = array(
	);
	
	
	# ========================
	# CONSTRUCTORS
	
	/**
	 * Construct
	 * @param array $options
	 */
	public function __construct ( array $options = array() ) {
		# Options
		$this->mergeOptions($options);
		
		# Identity
		$this->getIdentity();
		
		# URL Paths
		$this->updateUrlPaths();
		
		# Return true
		return true;
	}
	
	static public function getInstance ( ) {
		return Zend_Controller_Front::getInstance()->getPlugin('Bal_Controller_Plugin_App');
	}
	
	/**
	 * Will get a Plugin
	 * @param {string} $plugin
	 * @return {Router}
	 */
	protected function getPlugin ( $plugin ) {
		return Zend_Controller_Front::getInstance()->getPlugin($plugin);
	}
	
	# ========================
	# Authentication
	
	/**
	 * Logout the User
	 * @param bool $redirect
	 */
	public function logout ( ) {
		# Prepare
		//$Log = Bal_Log::getInstance();
		
		# Locale
	   	Zend_Registry::get('Locale')->clearLocale();
	   	
		# Logout
		$this->getAuth()->clearIdentity();
		Zend_Session::forgetMe();
		
		# Reset User
		$this->resetUser();
		
		# Create Log Message
		//$log_details = array();
		//$Log->log(array('log-user_logout',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		
		# Chain
		return $this;
	}
	
	/**
	 * Login the User
	 * @param User $User
	 * @param string $locale
	 * @param mixed $remember
	 * @return bool
	 */
	public function loginUser ( $User, $locale = null, $remember = null ) {
		# Prepare
		//$Log = Bal_Log::getInstance();
		
		# Login
		$result = $this->login($User->username, $User->password, $locale, $remember);
		
		# Log
		//if ( $result ) {
		//	# Create Log Message
		//	$log_details = $User->toArray();
		//	$Log->log(array('log-user_login',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		//}
		
		# Return result
		return $result;
	}

	/**
	 * Login the User
	 * @param string $username
	 * @param string $password
	 * @param string $locale
	 * @param mixed $remember
	 * @return bool
	 */
	public function login ( $username, $password, $locale = null, $remember = null ) {
		# Prepare
		$Session = new Zend_Session_Namespace('login'); // not sure why needed but it is here
		$Auth = $this->getAuth();
		
		# Load
		$AuthAdapter = new Bal_Auth_Adapter_Doctrine($username, $password);
		$AuthResult = $Auth->authenticate($AuthAdapter);
		
		# Check
		if ( !$AuthResult->isValid() ) {
			# Failed
			$error = implode($AuthResult->getMessages(),"\n");
			$error = empty($error) ? 'The credentials that were supplied are invalid' : $error;
			throw new Zend_Auth_Exception($error);
			return false;
		}
		
		# Passed
		
		# RememberMe
		if ( $remember ) {
			$rememberMe = $this->getConfig('auth.remember');
			if ( $rememberMe ) {
				$rememberMe = strtotime($rememberMe)-time();
				Zend_Session::rememberMe($rememberMe);
			}
		}
		
		# Set Locale
		if ( $locale ) {
   			$Locale = Zend_Registry::get('Locale');
			$Locale->setLocale($locale);
		}
		
		# Flush User
		$this->setUser();
		
		# Acl
		$this->loadUserAcl();
		
		# Admin cookies
		if ( $this->hasPermission('admin') ) {
			// Enable debug
			setcookie('debug','secret',0,'/');
		}
		
		# Return true
		return true;
	}
	
	/**
	 * Get the Zend Auth
	 * @return Zend_Auth
	 */
	public function getAuth ( ) {
		# Return the Zend_Auth Singleton
		return Zend_Auth::getInstance();
	}
	
	/**
	 * Do we have an Identity
	 * @return bool
	 */
	public function hasIdentity ( ) {
		# Check
		return $this->getIdentity() ? true : false;
	}
	
	/**
	 * Return the logged in Identity
	 * @return Doctrine_Record
	 */
	public function getIdentity ( ) {
		# Fetch
		return $this->getAuth()->getIdentity();
	}
	
	/**
	 * Return the logged in User
	 * @return Doctrine_Record
	 */
	public function getUser ( ) {
		# Fetch
		$User = $this->_User;
		
		# Return
		if ( $User === null ) {
			$User = $this->setUser();
		}
		
		# Return result
		return $User;
	}
	
	/**
	 * Do we have a User
	 * @return bool
	 */
	public function hasUser ( ) {
		# Fetch
		$User = $this->getUser();
		
		# Determine
		$result = $User ? true : false;
		
		# Return result
		return $result;
	}
	
	/**
	 * Clear User
	 * @return bool
	 */
	public function clearUser ( ) {
		# Clear
		$this->_User = null;
		# chain
		return $this;
	}
	
	/**
	 * Reset User
	 * @return bool
	 */
	public function resetUser ( ) {
		# Clear
		$this->clearUser();
		# Fetch
		$User = $this->getUser();
		# Return User
		return $User;
	}
	
	/**
	 * Sets the logged in User
	 * @return Doctrine_Record
	 */
	public function setUser ( ) {
		# Prepare
		$Auth = $this->getAuth();
		# Fetch
		$User = $Auth->hasIdentity() ? Doctrine::getTable('User')->find($Auth->getIdentity()) : false;
		# Apply
		$this->_User = $User;
		# Return User
		return $User;
	}
	
	
	# ========================
	# AUTHORISATION
	
	/**
	 * Return the Zend Registry
	 * @return Zend_Registry
	 */
	public function getRegistry ( ) {
		return Zend_Registry::getInstance();
	}
	
	/**
	 * Return the applied Acl
	 * @param Zend_Acl $Acl [optional]
	 * @return Zend_Acl
	 */
	public function getAcl ( Zend_Acl $Acl = null ) {
		# Check
		if ( $Acl) {
			return $Acl;
		}
		
		# Check
		if ( !Zend_Registry::isRegistered('acl') ) {
			# Create
			$Acl = new Zend_Acl();
			$this->setAcl($Acl); // Temp assign to ensure we don't repeat
			$this->loadAcl($Acl);
			$this->setAcl($Acl);
		}
		else {
			# Load
			$Acl = Zend_Registry::get('acl');
		}
		
		# Return
		return $Acl;
	}
	
	/**
	 * Apply the Acl
	 * @param Zend_Acl $Acl [optional]
	 */
	public function setAcl ( Zend_Acl $Acl ) {
		# Set
		$Acl = Zend_Registry::set('acl', $Acl);
		
		# Chain
		return $this;
	}
	
	protected $_Role = null;
	
	public function getRole ( ) {
		$Role = $this->_Role;
		return $Role;
	}
	
	public function setRole ( Zend_Acl_Role $Role ) {
		$this->_Role = $Role;
		return $this;
	}
	
	public function getDefaultResource ( ) {
		return null;
	}
	
	/**
	 * Load the User into the Acl
	 * @param Doctrine_Record $User [optional]
	 * @param Zend_Acl $Acl [optional]
	 * @return bool
	 */
	public function loadUserAcl ( $User = null, Zend_Acl $Acl = null ) {
		# Ensure User
		if ( !$User && !($User = $this->getUser()) ) return false;
		
		# Fetch ACL
		$Acl = $this->getAcl($Acl);
		$AclUser = new Zend_Acl_Role('user-'.$User->id);
		
		# Check Applied
		if ( $Acl->hasRole($AclUser) ) {
			return null; // already called before
		}
		
		# Add User Roles to Acl
		/* What we do here is add the user role to the ACL.
		 * We also make it so the user role inherits from the actual roles
		 */
		$Roles = $User->Roles; $roles = array();
		foreach ( $Roles as $Role ) {
			$roles[] = 'role-'.$Role->code;
		}
		if ( !empty($roles) ) 
			$Acl->addRole($AclUser, $roles);
		
		# Add User Permissions to Acl
		$Permissions = $User->Permissions; $permissions = array();
		foreach ( $Permissions as $Permission ) {
			$permissions[] = 'permission-'.$Permission->code;
		}
		if ( !empty($permissions) ) 
			$Acl->allow($AclUser, $this->getDefaultResource(), $permissions);
		
		# Add Role to Acl / Set Role
		if ( !$Acl->hasRole($AclUser) ) {
			$Acl->addRole($AclUser);
		}
		$this->setRole($AclUser);
		
		# Return true
		return true;
	}
	
	public function loadAcl ( Zend_Acl $Acl = null ) {
		# Fetch ACL
		$Acl = $this->getAcl($Acl);
		
		# Add Default Resource
		$defaultResource = $this->getDefaultResource();
		if ( $defaultResource )
			$Acl->add(new Zend_Acl_Resource($defaultResource));
		
		# Add Permissions to Acl
		$Permissions = Doctrine::getTable('Permission')->findAll(Doctrine::HYDRATE_ARRAY);
		foreach ( $Permissions as $Permission ) {
			$permission = 'permission-'.$Permission['code'];
			$Acl->add(new Zend_Acl_Resource($permission));
		}
		
		# Add Roles to Acl
		$Roles = Doctrine::getTable('Role')->createQuery()->select('r.code, rp.code')->from('Role r, r.Permissions rp')->setHydrationMode(Doctrine::HYDRATE_ARRAY)->execute();
		foreach ( $Roles as $Role ) {
			$role = 'role-'.$Role['code'];
			$AclRole = new Zend_Acl_Role($role);
			$Acl->addRole($AclRole);
			$permissions = array();
			foreach ( $Role['Permissions'] as $Permission ) {
				$permissions[] = 'permission-'.$Permission['code'];
			}
			$Acl->allow($AclRole, $this->getDefaultResource(), $permissions);
		}
		
		# Load the User Acl
		$this->loadUserAcl();
		
		# Return true
		return true;
	}
	
	/**
	 * Do we have that Acl entry?
	 * @param string $role
	 * @param string $action
	 * @param mixed $resource
	 * @param bool
	 */
	public function hasAclEntry ( $role, $resource, $privilege, Zend_Acl $Acl = null ) {
		# Prepare
		if ( empty($Acl) ) 
			$Acl = $this->getAcl($Acl);
		
		# Check
		$result = $Acl->isAllowed($role, $resource, $privilege);
		
		# Return result
		return $result;
	}
	
	/**
	 * Does the loaded User have that Permission?
	 * @param string $resource
	 * @param mixed $privileges [optional]
	 * @return bool
	 */
	public function hasPermission ( $resource, $privileges = null ) {
		# Prepare
		if ( $privileges === null ) {
			// Shortcut simplified
			$privileges = $resource;
			$resource = $this->getDefaultResource();
		}
		
		# Fetch
		$Acl = $this->getAcl(); // Load roles etc
		$Role = $this->getRole();
		
		# Check
		if ( $Role && ($result = $this->hasAclEntry($Role, $resource, $privileges)) ) {
			return $result;
		}
		
		# Return false
		return false;
	}
	
	
	# ========================
	# LAYOUT AREAS
	
	protected $_area = false;
	protected $_theme = false;
	protected $_layout = false;
	
	/**
	 * Set the current area
	 */
	public function setArea ( $area ) {
		# Handle
		$this->_area = $area;
		$theme = $this->getAreaTheme($area);
		$this->setTheme($theme);
		
		# Return this
		return $this;
	}
	
	/**
	 * Get the current area
	 */
	public function getArea ( $area = null ) {
		# Handle
		$area = $area ? $area : $this->_area;
		if ( empty($area) ) {
			$area = 'default';
		}
		
		# Return area
		return $area;
	}
	
	
	/**
	 * Set the current area
	 */
	public function setTheme ( $theme ) {
		# Handle
		$this->_theme = $theme;
		$theme_layouts_path = $this->getThemeLayoutsPath($theme);
		$this->getMvc()->setLayoutPath($theme_layouts_path);
		
		# Update URL Paths
		$this->updateUrlPaths();
		
		# Return this
		return $this;
	}
	
	/**
	 * Get the current theme
	 */
	public function getTheme ( $theme = null ) {
		# Prepare
		$theme = $theme ? $theme : $this->_theme;
		if ( empty($theme) ) {
			$theme = $this->getAreaTheme();
		}
		
		# Check
		if ( empty($theme) ) {
			throw new Zend_Exception('Could not find theme.');
			return false;
		}
		
		# Ensure Existance
		$theme_path = $this->getThemePath($theme);
		if ( empty($theme_path) ) {
			return false;
		}
		
		# Return theme
		return $theme;
	}
	
	/**
	 * Get the theme of the current area
	 */
	public function getAreaTheme ( $area = null ) {
		# Prepare
		if ( empty($area) ) $area = $this->getArea();
		
		# Handle
		$theme = $this->getConfig('areaThemes.'.$area);
		
		# Return theme
		return $theme;
	}
	
	/**
	 * Get the url for an area
	 */
	public function getAreaUrl ( $area = null, $prefix = false ) {
		# Get the theme for the area
		$theme = $this->getAreaTheme($area);
		
		# Get the url of the theme
		$url = $this->getThemeUrl($theme, $prefix);
		
		# Return url
		return $url;
	}
	
	/**
	 * Get the path for an area layouts directory
	 */
	public function getAreaLayoutsPath ( $area = null ) {
		# Prepare
		$theme = $this->getAreaTheme($area);
		
		# Handle
		$path = $this->getThemeLayoutsPath($theme);
		
		# Return path
		return $path;
	}
	
	/**
	 * Get the current layout
	 */
	public function setLayout ( $layout ) {
		$this->_layout = $layout;
		$this->getMvc()->setLayout($layout);
		return $this;
	}
	
	
	public function startMvc ( ) {
		Zend_Layout::startMvc();
		return $this;
	}
	
	public function getMvc ( ) {
		return Zend_Layout::getMvcInstance();
	}
	
	# ========================
	# URLS + PATHS
	
	/**
	 * Get the URL Plugin
	 */
	public function getUrl ( ) {
		return $this->getPlugin('Bal_Controller_Plugin_Url');
	}
	
	/**
	 * Get the APPLICATION_ENV value
	 * @param string $env [optional] to compare
	 * @return boolean
	 */
	public function getApplicationEnvironment ( $env = null ) {
		if ( $env ) {
			return APPLICATION_ENV === $env;
		}
		return APPLICATION_ENV;
	}
	
	/**
	 * Get the root url for the site
	 * @return string
	 */
	public function getRootUrl ( ) {
		return $this->getUrl()->getRootUrl();
	}
	
	/**
	 * Get the base url for the site
	 * @param bool $prefix
	 * @return string
	 */
	public function getBaseUrl ( $prefix = false ) {
		return $this->getUrl()->getBaseUrl($prefix);
	}

	/**
	 * Get the url for the public area
	 * @see getBaseUrl
	 * @param bool $prefix
	 * @return string
	 */
	public function getPublicUrl ( $prefix = false ) {
		return $this->getUrl()->getPublicUrl($prefix);
	}
	
	/**
	 * Get the path of the public directory
	 */
	public function getPublicPath ( ) {
		return PUBLIC_PATH;
	}
	
	/**
	 * Get the path of the themes directory
	 */
	public function getThemesPath ( ) {
		return THEMES_PATH;
	}
	
	/**
	 * Get the url of the themes directory
	 */
	public function getThemesUrl ( ) {
		return THEMES_URL;
	}
	
	/**
	 * Get the path of the current theme
	 */
	public function getThemePath ( $theme = null ) {
		# Prepare
		if ( empty($theme) ) $theme = $this->getTheme($theme);
		
		# Handle
		$theme_path = $this->getThemesPath() . DIRECTORY_SEPARATOR . $theme;
		
		# Check
		if ( empty($theme_path) ) {
			throw new Zend_Exception('Could not find theme path.');
			return false;
		}
		
		# Return theme_path
		return $theme_path;
	}
	
	/**
	 * Get the url of the current theme
	 */
	public function getThemeUrl ( $theme = null, $prefix = false ) {
		# Prepare
		if ( empty($theme) ) $theme = $this->getTheme($theme);
		
		# handle
		$theme_url = $this->getThemesUrl() . '/' . $theme;
		$prefix = $prefix ? $this->getRootUrl() : '';
		$url = $prefix.$theme_url;
		
		# Return url
		return $url;
	}
	
	/**
	 * Get the layouts path of the current theme
	 */
	public function getThemeLayoutsPath ( $theme = null ) {
		# Prepare
		if ( empty($theme) ) $theme = $this->getTheme($theme);
		
		# Handle
		$theme_layouts_path = $this->getThemePath($theme) . DIRECTORY_SEPARATOR . 'layouts';
		
		# Return theme_layouts_path
		return $theme_layouts_path;
	}
	
	public function updateUrlPaths ( ) {
		# Prepare
		$paths = array();
		
		# Add Theme Path
		if ( $this->getTheme() ) {
			$paths[] = array(
				'path' => $this->getThemePath(),
				'url' => $this->getThemeUrl()
			);
		}
		
		# Add Public Path
		$paths[] = array(
			'path' => $this->getPublicPath(),
			'url' => $this->getPublicUrl()
		);
		
		# Apply Paths
		$this->getUrl()->renege('paths',$paths);
		
		# Chain
		return $this;
	}
	
	public function getPublicFileUrl ( $file ) {
		# Prepare
		$Url = $this->getUrl()->paths(array(
			'path' => $this->getPublicPath(),
			'url' => $this->getPublicUrl()
		));
		
		# Handle
		$result = $Url->getFileUrl($file);
		$Url->clear();
		
		# Return result
		return $result;
	}
	
	public function getThemeFileUrl ( $file ) {
		# Prepare
		$Url = $this->getUrl()->paths(array(
			'path' => $this->getThemePath(),
			'url' => $this->getThemeUrl()
		));
		
		# Handle
		$result = $Url->getFileUrl($file);
		$Url->clear();
		
		# Return result
		return $result;
	}
	
	public function getFileUrl ( $file, $for = null ) {
		# Prepare
		$result = null;
		
		# Handle
		if ( $for ) {
			if ( $for === 'theme' ) {
				$result = $this->getThemeFileUrl($file);
			}
			else {
				$result = $this->getPublicFileUrl($file);
			}
		}
		else {
			$result = $this->getUrl()->getFileUrl($file);
		}
		
		# Return result
		return $result;
	}
	
	
	# ========================
	# DOCTINRE: PAGING
	
	
	/**
	 * Get the Pager
	 * @param integer $page_current [optional] Which page are we on?
	 * @param integer $page_items [optional] How many items per page?
	 * @return
	 */
	public function getPager($DQ, $page_current = 1, $page_items = 10){
		# Forward
		return Bal_Doctrine_Core::getPager($DQ,$page_current,$page_items);
	}
	
	/**
	 * Get the Pages
	 * @param unknown_type $Pager
	 * @param unknown_type $PagerRange
	 * @param unknown_type $page_current
	 */
	public function getPages($Pager, $PagerRange, $page_current = 1){
		# Forward
		return Bal_Doctrine_Core::getPages($Pager,$PagerRange,$page_current);
	}
	
	/**
	 * Get the Paging Details
	 * @param unknown_type $DQ
	 * @param unknown_type $page_current
	 * @param unknown_type $page_items
	 * @param unknown_type $pages_chunk
	 */
	public function getPaging($DQ, $page_current = 1, $page_items = 5, $pages_chunk = 5){
		# Forward
		return Bal_Doctrine_Core::getPaging($DQ,$page_current,$page_items,$pages_chunk);
	}
	
	
	
	# ========================
	# GETTERS: SEARCH
	
	/**
	 * Prepares and Returns the Search Session Namespace
	 * @return Zend_Session_Namespace
	 */
	public function fetchSearchSession ( ) {
		# Session
		$Session = new Zend_Session_Namespace('Search');
		if ( !is_array($Session->searches) )	$Session->searches = array();
		if ( !is_array($Session->last) )		$Session->last = null;
		
		# Return session
		return $Session;
	}
	
	/**
	 * Generates an unused search code
	 * @return string code
	 */
	public function generateSearchCode ( ) {
		# Session
		$Session = $this->fetchSearchSession();
		
		# Generate
		while ( delve($Session, ($code = rand(1,1000)), null) !== null ) {
			
		}
		
		# Return code
		return $code;
	}
	
	public function fetchSearch ( $code = null ) {
		# Prepare
		$Request = $this->getRequest();
		$Session = $this->fetchSearchSession();
		
		# Fetch
		if ( $code === null) {
			$code = fetch_param('search.code', $Request->getParam('code', null));
		}
		$create = fetch_param('search.create', false);
		$query = fetch_param('search.query', $Request->getParam('query', null));
		if ( is_array($query) ) {
			array_hydrate($query);
		}
		$search = $code ? delve($Session->searches, $code) : null;
		if ( $query && $search && $search['query'] !== $query ) {
			$create = true;
		}
		
		# Do we want to fetch the last code?
		if ( $code === 'last' ) {
			# Fetch last
			$code = $Session->searches['last'];
		}
		
		# oldCode
		$oldCode = $code;
		
		# Discover
		if ( empty($code) || !$search || $create ) {
			# Create Search Query
			
			# Create
			if ( !$code || $create ) {
				$code = $this->generateSearchCode();
			}
			
			# Secure
			sanitize($query);
			
			# Apply
			$Session->searches['last'] = $code;
			$Session->searches[$code]  = $query;
		}
		else {
			# Use Cached Search Query
			# We have a code, it exists in our searches, and we do not want to create a new one
			
			# Fetch
			$query = $Session->searches[$code];
			
			# Secure
			sanitize($query);
			
			# Apply
			$Session->searches['last'] = $code;
		}
		
		# Done
		$search = array();
		$search['code'] = $code;
		$search['query'] = $query;
		$search['create'] = $create;
		$search['oldCode'] = $oldCode;
		return $search;
	}
	
	
	# ========================
	# GETTERS
	
	
	/**
	 * Determines and returns the label value for the passed $Record
	 * @version 1.1, April 12, 2010
	 * @param mixed $Item
	 * @return string
	 */
	public function getItemLabel ( $Item ) {
		# Forward
		return Bal_Doctrine_Core::getRecordLabel($Item);
	}
	
	/**
	 * Get a Record based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public function fetchRecord ( $table, array $params = array() ) {
		# Forward
		return Bal_Doctrine_Core::fetchRecord($table,$params);
	}
	
	/**
	 * Get Records based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public function fetchRecords ( $table, array $params = array() ) {
		# Forward
		return Bal_Doctrine_Core::fetchRecords($table,$params);
	}
	
}
