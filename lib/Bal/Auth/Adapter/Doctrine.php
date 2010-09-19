<?php
require_once 'Zend/Auth/Adapter/Interface.php';
class Bal_Auth_Adapter_Doctrine implements Zend_Auth_Adapter_Interface {
	
	const FAILURE_IDENTITY_NOT_FOUND_MESSAGE = 'The identity could not be found';
	const FAILURE_CREDENTIAL_INVALID_MESSAGE = 'The credentials that were supplied are invalid';
	const FAILURE_IDENTITY_INACTIVE_MESSAGE = 'The identity you chose is currently inactive';
	
	private $_username;
	private $_password;
	protected $_options = array(
		'tableName' => 'User',
		'identityColumn' => 'username',
		'credentialColumn' => 'password',
		'activeColumn' => false
	);
	
	
	# -----------
	# Construction
	
    /**
     * Sets username and password for authentication
     * @return void
     */
    public function __construct($username, $password, $options = array()) {
        $this->_username = $username;
        $this->_password = $password;
		# Apply the options
		$this->mergeOptions($options);
    }
	
	# -----------
	# Options
	
	public function getOption ( $name, $default = null ) {
		return empty($this->_options[$name]) ? $default : $this->_options[$name];
	}
	
	public function setOption ( $name, $value ) {
		$this->_options[$name] = $value;
	}
	
	public function mergeOptions ( $options ) {
		$this->_options = array_merge($this->_options, $options);
	}
	
	# -----------
	# Options
	
	public function setTableName ($value) {
		$this->setOption('tableName', $value);
		return $this;
	}
	public function setIdentityColumn ($value) {
		$this->setOption('identityColumn', $value);
		return $this;
	}
	public function setCredentialColumn ($value) {
		$this->setOption('credentialColumn', $value);
		return $this;
	}
	public function setActiveColumn ($value) {
		$this->setOption('activeColumn', $value);
		return $this;
	}
	
	
	# -----------
	# Authentication
	
    /**
     * Performs an authentication attempt using Doctrine User class.
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate ( ) {
		# Prepare
    	$Result = null;
		
		# Prepare Options
		$tableName = $this->getOption('tableName');
		$identityColumn = $this->getOption('identityColumn');
		$credentialColumn = $this->getOption('credentialColumn');
		$activeColumn = $this->getOption('activeColumn');
		
		# Prepre Credentials
		$username = $this->_username;
		$password = $this->_password;
		
		# Attempt
    	try {
    		# Prepare Query
			$Query = Doctrine_Query::create()
			    ->from($tableName.' u')
			    ->where('u.'.$identityColumn.' = ?', $username);
			
			# Fetch User
			$User = $Query->fetchOne();
			
			# Check
			if ( empty($User) ) {
				# Invalid user
				$Result = new Zend_Auth_Result(
		            Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
		            null,
		            array(self::FAILURE_IDENTITY_NOT_FOUND_MESSAGE)
				);
			}
			else {
				# Check Credentials
				$credentialsMatch = false;
				if ( method_exists($tableName, 'compareCredentials') )
					$credentialsMatch = $User->compareCredentials($username,$password);
				elseif ( $credentialColumn )
					$credentialsMatch = $User->get($credentialColumn) === $password;
				else
					$credentialsMatch = true;
				
				# Check Credentials
				if ( !$credentialsMatch ) {
					# Invalid Credentials
					$Result = new Zend_Auth_Result(
			            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
			            $User->id,
			            array(self::FAILURE_CREDENTIAL_INVALID_MESSAGE)
					);
				}
				else {
					# Check Activate
					$isActive = false;
					if ( method_exists($tableName, 'isActive') )
						$isActive = $User->isActive();
					elseif ( $activeColumn )
						$isActive = $User->get($activeColumn) ? true : false;
					else
						$isActive = true;
					
					# Check Enabled
					if ( !$isActive ) {
						# Account Disabled
						$Result = new Zend_Auth_Result(
				            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
				            $User->id,
				            array(self::FAILURE_IDENTITY_INACTIVE_MESSAGE)
						);
					}
					else {
						# Everything went well
						$Result = new Zend_Auth_Result(
				            Zend_Auth_Result::SUCCESS,
				            $User->id,
				            array()
						);
					}
				}
			}
    	}
		catch ( Exception $e ) {
			# Error
    		throw new Zend_Auth_Adapter_Exception($e->getMessage());
    	}
		
		# Done
		return $Result;
    }
}
