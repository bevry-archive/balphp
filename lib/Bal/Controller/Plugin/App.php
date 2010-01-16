<?php
require_once 'Zend/Controller/Plugin/Abstract.php';
class Bal_Controller_Plugin_App extends Bal_Controller_Plugin_Abstract {
	
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
		
		# Done
		return true;
	}
	
	# -----------
	# Authentication
	
	/**
	 * Logout the User
	 * @param bool $redirect
	 */
	public function logout ( ) {
		# Log
		//Bal_Log::getInstance()->log('system-logout', Bal_Log::SUCCESS);
		
		# Locale
	   	Zend_Registry::get('Locale')->clearLocale();
	   	
		# Logout
		$this->getAuth()->clearIdentity();
		Zend_Session::forgetMe();
		
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
		//Bal_Log::getInstance()->log('system-login', Bal_Log::SUCCESS, array('data'=>$User->toArray()));
		return $this->login($User->username, $User->password, $locale, $remember);
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
		}
		
		# Passed
		
		# RememberMe
		if ( $remember ) {
			$rememberMe = $this->getConfig('bal.auth.remember');
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
		
		# Done
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
	 * Do we have a User
	 * @return bool
	 */
	public function hasUser ( ) {
		# Check
		return !empty($this->_User);
	}
	
	/**
	 * Return the logged in User
	 * @return Doctrine_Record
	 */
	public function getUser ( ) {
		# Return
		if ( $this->_User === null ) {
			$User = $this->setUser();
		}
		return $this->_User;
	}
	
	/**
	 * Sets the logged in User
	 * @return Doctrine_Record
	 */
	public function setUser ( ) {
		$Auth = $this->getAuth();
		$this->_User = $Auth->hasIdentity() ? Doctrine::getTable('User')->find($Auth->getIdentity()) : false;
		return $this->_User;
	}
	
	# -----------
	# Authorisation
	
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
		
		# Create User Acl
		$AclUser = new Zend_Acl_Role('user-'.$User->id);
		
		# Add User Roles to Acl
		/* What we do here is add the user role to the ACL.
		 * We also make it so the user role inherits from the actual roles
		 */
		$Roles = $User->Roles; $roles = array();
		foreach ( $Roles as $Role ) {
			$roles[] = 'role-'.$Role->code;
		}
		$Acl->addRole($AclUser, $roles);
		
		# Add User Permissions to Acl
		$Permissions = $User->Permissions; $permissions = array();
		foreach ( $Permissions as $Permission ) {
			$permissions[] = 'permission-'.$Permission->code;
		}
		$Acl->allow($AclUser, null, $permissions);
		
		# Done
		return true;
	}
	
	public function loadAcl ( Zend_Acl $Acl = null ) {
		# Fetch ACL
		$Acl = $this->getAcl($Acl);
		
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
			var_dump($role);
			$AclRole = new Zend_Acl_Role($role);
			$Acl->addRole($AclRole);
			$permissions = array();
			foreach ( $Role['Permissions'] as $Permission ) {
				$permissions[] = 'permission-'.$Permission['code'];
			}
			$Acl->allow($AclRole, null, $permissions);
		}
		
		# Done
		return true;
	}
	
	/**
	 * Do we have that Acl entry?
	 * @param string $role
	 * @param string $action
	 * @param mixed $resource
	 * @param bool
	 */
	public function hasAclEntry ( $role, $action, $resource, Zend_Acl $Acl = null ) {
		# Prepare
		$Acl = $this->getAcl($Acl);
		
		# Check
		return $Acl->isAllowed($role, $action, $resource);
	}
	
	/**
	 * Does the loaded User have that Permission?
	 * @param string $action
	 * @param mixed $permissions [optional]
	 * @return bool
	 */
	public function hasPermission ( $action, $permissions = null ) {
		# Prepare
		if ( $permissions === null ) {
			// Shortcut simplified
			$permissions = $action;
			$action = null;
		}
		
		# Fetch
		$User = $this->getUser();
		
		# Check
		if ( $User && $User->id && ($result = $this->hasAclEntry('user-'.$User->id, $action, $permissions)) ) {
			return $result;
		}
		
		# Done
		return false;
	}
	
	# -----------
	# VIEWS

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
		
		# Done
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
		
		# Done
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
		
		# Done
		return $theme_layouts_path;
	}
	
	# -----------
	# AREAS
	
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
		
		# Done
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
		
		# Done
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
		
		# Done
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
		
		# Done
		return $theme;
	}
	
	/**
	 * Get the theme of the current area
	 */
	public function getAreaTheme ( $area = null ) {
		# Prepare
		if ( empty($area) ) $area = $this->getArea();
		
		# Handle
		$theme = $this->getConfig('bal.areaThemes.'.$area);
		
		# Done
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
		
		# Done
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
		
		# Done
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
	
	# -----------
	# Menu
	
	
	public function getFileUrl ( $file ) {
		# Prepare
		$publicPath = $this->getPublicPath();
		$publicUrl = $this->getPublicUrl();
		$themePath = $this->getThemePath();
		$themeUrl = $this->getThemeUrl();
		$result = false;
		
		# Handle
		if ( file_exists($themePath . DIRECTORY_SEPARATOR . $file) ) {
			$result = $themeUrl . '/' . $file;
		} elseif ( file_exists($publicPath . DIRECTORY_SEPARATOR . $file) ) {
			$result = $publicUrl . '/' . $file;
		}
		
		# Done
		return $result;
	}
	
	# -----------
	# Menu
	
	
	/**
	 * Activate a Navigation Menu Item
	 * @return
	 */
	public function activateNavigationItem ( Zend_Navigation $Menu, $id, $parents = true ) {
		# Find Current
		$Item = $Menu->findBy('id', $id);
		
		# Check
		if ( !$Item ) {
			return false;
		}
		
		# Active Current
		$Item->active = true;
		
		# Activate Parents
		if ( $parents ) {
			$tmpItem = $Item;
			while ( !empty($tmpItem->parent) ) {
				$tmpItem = $tmpItem->parent;
				$tmpItem->active = true;
			}
		}
		
		# Done
		return true;
	}
	
	
	# -----------
	# Doctrine
	
	
	/**
	 * Determine and return a Record of $type using in
	 * @param string $type The table/type of the record
	 * @param mixed ... [optional] The input used to determine the record
	 * @return
	 */
	public function getRecord ( $type ) {
		# Prepare
		$Record = null;
		$args = func_get_args(); array_shift($args); // pop first (type)
		
		# Handle
		foreach ( $args as $in ) {
			if ( $in instanceof $type ) {
				$Record = $in;
			} elseif ( is_object($in) ) {
				if ( !empty($in->id) )
					$Record = $this->getRecord($type, $in->id);
			} elseif ( is_numeric($in) ) {
				$Record = Doctrine::getTable($type)->find($in);
			} elseif ( is_string($in) ) {
				if ( Doctrine::getTable($type)->hasColumn($in) )
					$Record = Doctrine::getTable($type)->findByCode($in);
			} elseif ( is_array($in) ) {
				if ( !empty($in['id']) ) {
					$Record = $this->getRecord($type, $in['id']);
				} elseif ( !empty($in['code']) ) {
					$Record = $this->getRecord($type, $in['code']);
				}
			}
			
			if ( !empty($Record->id) ) {
				break;
			}
		}
		
		# Check
		if ( empty($Record) ) {
			$Record = new $type;
		}
		
		# Done
		return $Record;
	}
	
	
	# -----------
	# Paging
	
	
	/**
	 * Get the Pager
	 * @param integer $page_current [optional] Which page are we on?
	 * @param integer $page_items [optional] How many items per page?
	 * @return
	 */
	public function getPager($DQ, $page_current = 1, $page_items = 10){
		# Fetch
		$Pager = new Doctrine_Pager(
			$DQ,
			$page_current,
			$page_items
		);
		
		# Return
		return $Pager;
	}
	
	/**
	 * Get the Pages
	 * @param unknown_type $Pager
	 * @param unknown_type $PagerRange
	 * @param unknown_type $page_current
	 */
	public function getPages($Pager, $PagerRange, $page_current = 1){
		# Paging
		$page_first = $Pager->getFirstPage();
		$page_last = $Pager->getLastPage();
		$Pages = $PagerRange->rangeAroundPage();
		foreach ( $Pages as &$Page ) {
			$Page = array(
				'number' => $Page,
				'title' => $Page
			);
		}
		$Pages[] = array('number' => $Pager->getPreviousPage(), 'title' => 'prev');
		$Pages[] = array('number' => $Pager->getNextPage(), 'title' => 'next');
		foreach ( $Pages as &$Page ) {
			$page = $Page['number'];
			$Page['selected'] = $page == $page_current;
			if ( is_numeric($Page['title']) ) {
				$Page['disabled'] = $page < $page_first || $page > $page_last;
			} else {
				$Page['disabled'] = $page < $page_first || $page > $page_last || $page == $page_current;
			}
		}
		
		# Done
		return $Pages;
	}
	
	/**
	 * Get the Paging Details
	 * @param unknown_type $DQ
	 * @param unknown_type $page_current
	 * @param unknown_type $page_items
	 * @param unknown_type $pages_chunk
	 */
	public function getPaging($DQ, $page_current = 1, $page_items = 5, $pages_chunk = 5){
		# Prepare
		$page_current = intval($page_current);
		$page_items = intval($page_items);
		$pages_chunk = intval($pages_chunk);
		
		# Fetch
		$Pager = $this->getPager($DQ, $page_current, $page_items);
		
		# Results
		$PagerRange = new Doctrine_Pager_Range_Sliding(array(
				'chunk' => $pages_chunk
    		),
			$Pager
		);
		$Items = $Pager->execute();
		
		# Get Pages
		$Pages = $this->getPages($Pager, $PagerRange, $page_current);
		
		# Check page current
		$page_first = $Pager->getFirstPage();
		$page_last = $Pager->getLastPage();
		if ( $page_current > $page_last ) $page_current = $page_last;
		elseif ( $page_current < $page_first ) $page_current = $page_first;
		
		# Done
		return array($Items, $Pages, $page_current);
	}
	
}
