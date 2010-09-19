<?php
class Bal_Doctrine_Record_Listener_Ensure extends Doctrine_Record_Listener {
	
	protected $_default = true;
	
	/**
	 * Construct
	 * @param object $default [optional]
	 * @return
	 */
	public function __construct ( $default = true ) {
		$this->_default = $default;
	}
	
	/**
	 * Ensure the consistency of a record
	 * @param Doctrine_Record $Record
	 * @return boolean whether or not to save the record
	 */
	public function ensureConsistency ( Doctrine_Record $Record ) {
		# Prepare
		$save = false;
		
		# Cycle
		if ( method_exists($Record, 'ensure') ) {
			if ( $Record->ensure() ) {
				$save = true;
			}
		}
		
		# Done
		return $save;
	}
	
	/**
	 * preSave Event
	 * @param Doctrine_Event $Event
	 * @return true
	 */
    public function preSave ( Doctrine_Event $Event ) {
    	# Prepare
		$Record = $Event->getInvoker();
		echo 'preSave:'.get_class($Record)."\n";
		
		# Ensure Consistency
		$this->ensureConsistency($Record);
		
		# Done
		return true;
    }
	
	/**
	 * preValidate Event
	 * @param Doctrine_Event $Event
	 * @return true
	 */
    public function preValidate ( Doctrine_Event $Event ) {
    	# Prepare
		$Record = $Event->getInvoker();
		echo 'preValidate:'.get_class($Record)."\n";
		
		# Ensure Consistency
		$this->ensureConsistency($Record);
		
		# Done
		return true;
    }

	/**
	 * postSave Event
	 * @param Doctrine_Event $Event
	 * @return true
	 */
    public function postSave ( Doctrine_Event $Event ) {
    	# Prepare
		$Record = $Event->getInvoker();
		
		# Ensure Consistency
		if ( $this->ensureConsistency($Record) ) {
			$Record->save();
		}
		
		# Done
		return true;
    }
	

}