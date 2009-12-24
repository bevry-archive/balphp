<?php
require_once 'Zend/Controller/Action/Helper/Abstract.php';
class Bal_Controller_Action_Helper_Former extends Zend_Controller_Action_Helper_Abstract {

	
	public function fetch ( $fields ) {
		$result = array(
			'post' => array(),
			'files' => array()
		);
		
		$this->appendFields($result, $fields);
		
		return $result;
	}
	
	public function appendFields ( &$arr, $fields ) {
		$result = $this->appendField($result, null, $fields);
		return $result;
	}
	
	public function appendField ( &$arr, $keys, $value ) {
		# Prepare
		if ( !is_array($keys) ) $keys = empty($keys) ? array() : array($keys);
		
		# Handle
		if ( is_array($value) ) {
			# Cycle
			foreach ( $value as $key_ => $value_ ) {
				$this->appendField($arr, $keys, $value_);
			}
		} else {
			# Prepare
			$key = array_last($keys);
			$type = 'normal';
			if ( is_integer($key) ) {
				$key = $value;
			} else {
				if ( !empty($value) ) {
					if ( strpos($value, ',') !== false ) {
						$type = 'enum';
					} else {
						$type = $value;
					}
				}
			}
			
			# Handle
			switch ( $type ) {
				case 'FILE':
					$field_value = $_FILES[array_shift($keys)];
					$key_parts = $keys;
					$key_parts[] = $key;
					foreach ( $field_value as $field_part => &$field_part_value ) {
						foreach ( $key_parts as $key_part ) {
							$field_part_value = $field_part_value[$key_part];
						}
					}
					array_apply($arr['files'], $keys, $field_value);
					break;
					
				case 'enum':
				case 'normal':
				default:
					$field_value = array_delve($_POST, $keys);
					array_apply($arr['post'], $keys, $field_value);
					break;
			}
		}
		
		# Done
		return $arr;
	}
	
}