<?php
require_once 'Bal/Exception.php';

abstract class Bal_Payment_Model_Abstract {
	
	/**
	 * Store of Model's data
	 * @var array $_data
	 */
	protected $_data = array();
	
	/**
	 * Construct our Model
	 * @param array|object $data[optional]
	 * @return $this
	 */
	public function __construct ( $data = null ) {
		# Merge
		if ( $data ) {
			$this->merge($data);
		}
		
		# Chain
		return $this;
	}
	
	/**
	 * Gets a list of keys that are possible
	 * @return array
	 */
	public function getKeys ( ) {
		# Fetch
		$keys = array_keys($this->_data);
		
		# Return keys
		return $keys;
	}
	
	/**
	 * Checks if the $key exists in our data
	 * @param string $key
	 * @return boolean
	 */
	protected function hasKey ( $key ) {
		# Prepare
		$keys = $this->getKeys();
		
		# Check
		if ( !in_array($key, $keys) ) {
			throw new Bal_Exception(array(
				'They desired key ['.$key.'] does not exist in our model',
				'key' => $key,
				'keys' => $keys
			));
		}
		
		# Return true
		return true;
	}
	
	/**
	 * Allows get to data properties directly
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	protected function _get ( $key ) {
		$this->hasKey($key);
		return $this->_data[$key];
	}
	
	/**
	 * Allows set to data properties directly
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	protected function _set ( $key, $value ) {
		$this->hasKey($key);
		$this->_data[$key] = $value;
		return $this;
	}
	
	
	/**
	 * Allows get to data properties directly - also allows for getters
	 * @param string $key
	 * @return mixed
	 */
	public function get ( $key ) {
		# Prepare
		$result = null;
		
		# Handle
		$getter = 'get'.str_replace(' ','',ucwords(str_replace('_',' ',$key)));
		if ( method_exists($this, $getter) ) {
			# Use Getter
			$result = $this->$getter($key);
		}
		else {
			# Direct
			$result = $this->_get($key);
		}
		
		# Return result
		return $result;
	}
	
	/**
	 * Allows set to data properties directly - also allows for setters
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function set ( $key, $value ) {
		# Handle
		$setter = 'set'.str_replace(' ','',ucwords(str_replace('_',' ',$key)));
		if ( method_exists($this, $setter) ) {
			# Use Setter
			$this->$setter($value);
		}
		else {
			# Use Direct
			$this->_set($key, $value);
		}
		
		# Chain
		return $this;
	}
	
	/**
	 * Applies an array or object to our model
	 * @param array|object $merge
	 * @return $this
	 */
	public function merge ( $merge ) {
		# Handle
		if ( is_array($merge) ) {
			# Cycle
			foreach ( $merge as $key => $value ) {
				# Set
				$this->set($key, $value);
			}
		}
		elseif ( is_object($merge) ) {
			# Convert to array and recurse
			$this->merge($merge->toArray());
		}
		
		# Chain
		return $this;
	}
	
	/**
	 * Convert our Model into an array
	 * @return array
	 */
	public function toArray ( ) {
		$data = $this->_data;
		return to_array_deep($this->_data);
	}
	
	/**
	 * @see self::get
	 * @param string $key
	 * @return mixed
	 */
	public function __get ( $key ) {
		return $this->get($key);
	}
	
	/**
	 * @see self::set
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function __set ( $key, $value ) {
		return $this->set($key,$value);
	}
	
}
