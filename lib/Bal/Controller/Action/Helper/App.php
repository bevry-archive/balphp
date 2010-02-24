<?php
class Bal_Controller_Action_Helper_App extends Bal_Controller_Action_Helper_Abstract {

	# ========================
	# VARIABLES
	
	
	protected $_options = array(
		'logged_out_forward' => array(
			array('action'=>'login')
		),
		'logged_in_forward' => array(
			array('action'=>'login')
		)
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
		$NavData = array();
		
		# Module Config
		$module = $Request->getModuleName();
		$module_path = Bal_App::getFrontController()->getModuleDirectory($module);
		$module_config_path = $module_path . '/config';
		$config_path = Bal_App::getConfig('config_path');
		
		# Navigation
		$nav_path = $module_config_path . '/nav.json';
		if ( is_readable($nav_path) ) {
			$NavData = file_get_contents($nav_path);
			$NavData = Zend_Json::decode($NavData, Zend_Json::TYPE_ARRAY);
		}
		
		# Navigation Override
		$nav_override_path = $config_path . '/nav.json';
		if ( is_readable($nav_override_path) ) {
			$NavDataOver = file_get_contents($nav_override_path);
			$NavDataOver = Zend_Json::decode($NavDataOver, Zend_Json::TYPE_ARRAY);
			$NavDataOver = delve($NavDataOver,$module);
			if ( !empty($NavDataOver) ) {
				$NavData = array_merge_recursive_keys($NavData, $NavDataOver);
			}
		}
		
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
		
		# ACL
		Zend_View_Helper_Navigation::setDefaultAcl($this->getAcl());
		Zend_View_Helper_Navigation::setDefaultRole($this->getRole());
		
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
	
	
	/**
	 * Has an Nagivation Item?
	 * @param string $menu
	 * @param string $id
	 * @param boolean $prefix [optional] Whether or not to prefix the id with the menu name
	 * @return bool
	 */
	public function hasNavigationItem ( $code, $id, $prefix = false ) {
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
		$result = $this->hasNavigationMenuItem($NavigationMenu, $id);
		
		# Return Result
		return $result;
	}
	
	/**
	 * Has a Navigation Menu Item?
	 * @return
	 */
	public function hasNavigationMenuItem ( Zend_Navigation $Menu, $id ) {
		# Find Current
		$Item = $Menu->findBy('id', $id);
		
		# Check
		if ( !$Item ) {
			return false;
		}
		
		# Done
		return true;
	}
	
	/**
	 * Activate a Navigation Menu Item
	 * @return
	 */
	public function activateNavigationMenuItem ( Zend_Navigation $Menu, $id, $parents = true ) {
		# Find Current
		$Item = $Menu->findBy('id', $id);
		
		# Check
		if ( !$Item ) {
			return false;
		}
		
		# Check Permission
		if ( !Bal_App::getView()->navigation()->accept($Item) ) {
			throw new Zend_Exception('Identity does not have permission to activate that menu item: '.$id);
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
		call_user_func_array(array($Redirector,'gotoRoute'), $redirect);
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
	
	
	
	# ========================
	# LOG
	
	public function prepareLog ( $store = null, $log_request = null ) {
		# Prepare
		$Log = Bal_App::getLog();
		if ( $store === null || $log_request === null ) {
			$xhr = $this->getActionControllerRequest()->isXmlHttpRequest();
			if ( !$xhr && !empty($_REQUEST['ajax']) ) $xhr = true;
			# Apply
			if ( $xhr ) {
				if ( $store === null ) $store = false;
				if ( $log_request === null ) $log_request = false;
			} else {
				if ( $store === null ) $store = $this->getConfig('bal.log.store');
				if ( $log_request === null ) $log_request = $this->getConfig('bal.log.request');
			}
		}
		
		# Enable Sore
		if ( $store ) {
			$Log->enableStore($store);
		}
		
		# Log Request Details
		if ( $log_request ) {
			global $_SESSION;
			$details = array(
				'server'	=> $_SERVER,
				'request'	=> array(
					'get'		=> $_GET,
					'post'		=> $_POST,
					'files'		=> $_FILES,
					'session'	=> $_SESSION,
					'cookie'	=> $_COOKIE,
					'params' 	=> $_REQUEST,
				)
			);
			$Log->log('log-request_details', Bal_Log::DEBUG, array('details'=>$details));
		}
		
		# Chain
		return $this;
	}
	
	# ========================
	# ITEMS
	
	public function applyRecord ( Doctrine_Record $Record, $data, $keep = null, $remove = null, $empty = null ) {
		# Prepare
		$Table = $Record->getTable();
		
		# Prepare
		if ( !empty($keep) )
			array_keys_keep($data, $keep);
		if ( !empty($remove) )
			array_keys_unset($data, $remove);
		if ( !empty($empty) )
			array_keys_unset_empty($data, $empty);
		
		# Clean special values
		array_clean_form($data);
		
		# Cycle through values applying each one
		if ( !empty($data) ) 
		foreach ( $data as $key => $value ) {
			# Prepare
			
			# Check Relation
			if ( $Table->hasRelation($key) ) {
				# Is Relation
				$Relation = $Table->getRelation($key);
				$RelationTable = $Relation->getTable();
				if ( !is_object($value) ) {
					if ( $Relation->getType() === Doctrine_Relation::MANY ) {
						# Many Type, Needs Doctrine_Collection
						if ( empty($value) ) {
							# Empty
							$value = new Doctrine_Collection($RelationTable);
						}
						else {
							# Discover
							if ( is_array($value) && is_array(array_first($value)) ) {
								# Create Multiple
								$_values = new Doctrine_Collection($RelationTable);
								foreach ( $value as $_value ) {
									if ( delve($_value,'_delete_') ) continue;
									unset($_value['_delete_']);
									if ( empty($_value) ) continue;
									# Create
									$valueRecord = $this->getRecord($RelationTable, $_value);
									$this->applyRecord($valueRecord,$_value);
									$_values[] = $valueRecord; 
								}
								$value = $_values;
							}
							else {
								# Find Multiple
								if ( !is_array($value) ) $value = array($value);
								$value = $RelationTable->createQuery()->select('*')->whereIn('id', $value)->execute();
							}
						}
						
						# Clear all previously, will re-apply on set
						$Record->unlink($key);
						
						# Done Multiple
						
					} else {
						# One Type, Needs Record
						if ( empty($value) || delve($value,'_delete_') ) {
							# Empty
							$value = null;
						}
						else {
							# Prepare
							unset($value['_delete_']);
							
							# Check
							if ( !empty($value) ) {
								if ( is_array($value) && !delve($value,'_delete_') ) {
									# Create
									$valueRecord = $this->getRecord($RelationTable, $value);
									$this->applyRecord($valueRecord,$value);
									$value = $valueRecord; 
								}
								else {
									# Discover
									$value = $RelationTable->find($value);
								}
							}
						}
						
						# Done One
					}
				}
			}
			
			# Apply
			$Record->set($key, $value);
		}
		// $Item->merge($item);
		// ^ Always fires special setters this way
		
		# Chain
		return $this;
	}
	
	public function saveRecord ( Doctrine_Record $Record, $data = null, $keep = null, $remove = null, $empty = null ) {
		# Apply
		$this->applyRecord($Record, $data, $keep, $remove, $empty);
		
		# Save
		$Record->save();
		
		# Chain
		return $this;
	}
	
	public function saveItem ( $table, $record = null, $Query = null, $keep = null, $remove = null, $empty = null ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Request = $this->getRequest();
		$Log = Bal_App::getLog();
		$item = $Item = null;
		$Table = Bal_Form_Doctrine::getTable($table);
		$tableName = Bal_Form_Doctrine::getTableName($table);
		$tableNameLower = strtolower($tableName);
		
		# Fetch
		$Item = $this->fetchItem($table,$record,$Query);
		$item = $this->fetchItemParams($tableName);
		
		# Handle
		try {
			# Check Existance of Save
			if ( empty($item) || !is_array($item) ) {
				# Return Found/New Content
				return $Item;
			}
			
			# Start
			$Connection->beginTransaction();
			
			# Save
			$this->saveRecord($Item, $item, $keep, $remove, $empty);
			
			# Stop Duplicates
			$Request->setPost($tableName, $Item->id);
			
			# Finish
			$Connection->commit();
			
			# Log
			$log_details = array(
				$tableName			=> $Item->toArray(),
				$tableName.'_url'	=> $this->getActionControllerView()->url()->item($Item)->toString()
			);
			$Log->log(array('log-'.$tableNameLower.'-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}
		catch ( Exception $Exception ) {
			# Rollback
			$Connection->rollback();
			
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Done
		return $Item;
	}
	
	public function deleteItem ( $table, $record = null ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Log = Bal_App::getLog();
		$result = true;
		$Table = Bal_Form_Doctrine::getTable($table);
		$tableName = Bal_Form_Doctrine::getTableName($table);
		$tableNameLower = strtolower($tableName);
		
		# Fetch
		$Item = $this->fetchItem($table, $record);
		$item = $this->fetchItemParams($tableName);
		
		# Handle
		try {
			# Start
			$Connection->beginTransaction();
			
			# Handle
			if ( $Item && $Item->exists() ) {
				# Extract
				$ItemArray = $Item->toArray(true);
		
				# Delete
				$Item->delete();
			
				# Commit
				$Connection->commit();
		
				# Log
				$log_details = array(
					$tableName	=> $ItemArray
				);
				$Log->log(array('log-'.$tableNameLower.'-delete',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
			}
			else {
				throw new Zend_Exception('error-'.$tableNameLower.'-missing');
			}
		}
		catch ( Exception $Exception ) {
			# Rollback
			$Connection->rollback();
			
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
			
			# Error
			$result = false;
		}
		
		# Return result
		return $result;
	}
	
}
