<?php
class Bal_Doctrine_Record_Listener_Html extends Doctrine_Record_Listener {
	
	protected $_default = false;
	
	/**
	 * Construct our HTML santizer for Doctrine Record
	 * @param object $default [optional]
	 * @return
	 */
	public function __construct ( $default = false ) {
		$this->_default = $default;
	}
	
	/**
	 * Apply HTML sanitization automaticly on a save.
	 * @param object $event
	 * @return
	 */
    public function preSave (Doctrine_Event $Event) {
    	$Record = $Event->getInvoker();
    	$Table = $Record->getTable();
		$columns = $Table->getColumns();
		foreach ( $columns as $column => $properties ) {
			$field = $Table->getFieldName($column);
			$orig = $value = $Record->get($field);
			if ( empty($value) || $properties['type'] !== 'string' || !is_string($value) ) continue;
			
			# Prepare
			$mode = isset($properties['extra']['html']) ? $properties['extra']['html'] : $this->_default;
			if ( !$mode )
				$mode = 'none';
			elseif ( $mode === true )
				$mode = 'normal';
			
			# Format
			$value = format_to_output($value, $mode);
			
			# Apply
			if ( $value !== $orig ) // only call the setter if the value has actually changed, prevents special setters form overloading
				$Record->set($field, $value);
		}
		
		// Done
		return true;
    }
	
}