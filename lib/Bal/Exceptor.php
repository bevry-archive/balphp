<?php

class Bal_Exceptor {
	
	protected $Exception = null;
	protected $class = null;
	protected $type = null;
	protected $code = null;
	protected $id = null;
	protected $priority = null;
	protected $messages = array();
	
	public function __construct ( Exception $Exception ) {
		# Apply
		$this->setException($Exception);
		
		# Done
		return true;
	}
	
	public function getLocale ( ) {
		return Bal_Locale::getInstance();
	}
	
	public function setException ( Exception $Exception ) {
		# Prepare
		$Locale = $this->getLocale();
		$this->Exception = $Exception;
		$this->messages = array();
		$this->class = $this->type = $this->id = null;
		
		# Apply
		$this->class = get_class($Exception);
		$this->code = $Exception->getCode();
		$this->priority = Bal_Log::CRIT;
		
		# Handle
		switch ( $this->class ) {
			case 'Zend_Controller_Dispatcher_Exception':
				# Apply
				$this->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
				$this->id = 'error-application-404';
				$this->priority = Bal_Log::ERR;
				# Message
				$message = $Exception->getMessage();
				$message = $Locale->translate($message);
				$this->messages[] = $message;
				break;
			
			case 'Zend_Controller_Action_Exception':
				if ( 404 == $Exception->getId() ) {
					# Apply
					$this->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION;
					$this->id = 'error-application-404';
					$this->priority = Bal_Log::ERR;
					# Message
					$message = $Exception->getMessage();
					$message = $Locale->translate($message);
					$this->messages[] = $message;
				} else {
					# Type
					$this->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
					# Id
					$this->id = 'error-application';//'-'.$this->id;
					$this->priority = Bal_Log::CRIT;
					# Message
					$message = $Exception->getMessage();
					$message = $Locale->translate($message);
					$this->messages[] = $message;
				}
				break;
			
			case 'Doctrine_Connection_Mysql_Exception':
				# Apply
				$this->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
				$this->id = 'error-doctrine-connection';
				$this->priority = Bal_Log::CRIT;
				# Message
				$message = $Exception->getPortableMessage();
				$message = $Locale->translate($message);
				$this->messages[] = $message;
				break;
				
			case 'Doctrine_Validator_Exception':
				# Apply
				$this->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
				$this->id = 'error-doctrine-validation';
				$this->priority = Bal_Log::ERR;
				# Fetch Invalids
				$invalidRecords = $Exception->getInvalidRecords();
				# Cycle Through
				foreach ( $invalidRecords as $Record ) {
					# Fetch Errors
					$ErrorStack = $Record->getErrorStack()->toArray();
					# Cycle Through
					foreach ( $ErrorStack as $field => $errors ) {
						foreach ( $errors as $error ) {
							# Prepare
							$Table = $Record->getTable();
							$message = array(
								'id' 	=> 'error-doctrine-validation-'.$error,
								'table' => strtolower($Table->getComponentName()),
								'field' => $field,
								'value' => $Record->get($field)
							);
							# Handle Special Errors (ones which we can get more info)
							switch ( $error ) {
								case 'type':
									$type = $Table->getTypeOf($field);
									$message['type'] = $type;
									break;
								case 'length':
									$properties = $Table->getDefinitionOf($field);
									$message['length'] = $properties['length'];
								default:
									break;
							}
							# Translate
							$message['field'] = $Locale->translate($message['table'].'-field-'.$message['field']);
							$message['plural'] = $Locale->translate($message['table'].'-title-plural');
							$message['singular'] = $Locale->translate($message['table'].'-title-singular');
							$message['ownership'] = $Locale->translate($message['table'].'-title-ownership');
							$message = $Locale->translate($message['id'], $message);
							# Apply
							$this->messages[] = $message;
						}
					}
				}
				break;
				
			default:
				# Apply
				$this->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
				$this->id = 'error-application';//'-'.$this->id; // error-application
				$this->priority = Bal_Log::CRIT;
				# Message
				$message = $Exception->getMessage();
				$message = $Locale->translate($message);
				$this->messages[] = $message;
				break;
		}
		
		# Chain
		return $this;
	}
	
	public function getClass ( ) {
		return $this->class;
	}
	
	public function getType ( ) {
		return $this->type;
	}
	
	public function getCode ( ) {
		return $this->code;
	}
	
	public function getId ( ) {
		return $this->id;
	}
	
	public function getTitle ( ) {
		$Locale = $this->getLocale();
		return $Locale->translate($this->id);
	}
	
	public function getMessages ( ) {
		return $this->messages;
	}
	
	public function getPriority ( ) {
		return $this->priority;
	}
	
	public function log ( ) {
		# Prepare
		$Log = Bal_Log::getInstance();
		
		# Extra Information
		$info = array(
			'details' => array(
				'server'	=> $_SERVER,
				'request'	=> array(
					'get'		=> $_GET,
					'post'		=> $_POST,
					'session'	=> $_SESSION,
					'cookie'	=> $_COOKIE,
					'params' 	=> $_REQUEST,
				),
				'exceptor' => $this->toArray()
			)
		);
		
		# Log Exception
		$Log->log($this->Exception, $this->getPriority());
		$Log->log($info, Bal_Log::DEBUG);
		
		# Chain
		return $this;
	}
	
	public function toArray() {
		return array(
			'messages'	=> $this->getMessages(),
			'type'		=> $this->getType(),
			'code'		=> $this->getCode(),
			'id'		=> $this->getId(),
			'priority'	=> $this->getPriority()
		);
	}
	
	public function toString ( ) {
		return var_export($this->toArray(), true);
	}
	
	public function __toString ( ) {
		return $this->toString();
	}
	
}

