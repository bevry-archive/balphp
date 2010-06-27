<?php

/**
 * Container of Generic Doctrine Functions
 * @version 1.1, April 12, 2010
 */
abstract class Bal_Doctrine_Core {
	
	# ========================
	# STANDARD
	
	/**
	 * Returns the current Identity
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function getIdentity ( ) {
		return Bal_App::getIdentity();
	}
	
	
	# ========================
	# LABELS
	
	/**
	 * Returns a series of field names expected to be the label field
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function getLabelFieldNames ( ) {
		return array('label','displayname','fullname','username','name','title','code','id');
	}
	
	/**
	 * Determines and returns the label value for the passed $Record
	 * @version 1.1, April 12, 2010
	 * @param mixed $Record
	 * @return string
	 */
	public static function getRecordLabel ( $Record ) {
		# Prepare
		$label = null;
		$labelFieldNames = self::getLabelFieldNames();
		
		# Handle
		foreach ( $labelFieldNames as $labelFieldName ) {
			$value = delve($Record,$labelFieldName);
			if ( $value ) {
				$label = $value;
				break;
			}
		}
		
		# Return label
		return $label;
	}
	
	/**
	 * Get the field type of a field, taking onto account special types
	 * @param mixed $Table - can also be record is fine
	 * @param string $fieldName
	 * @return string
	 */
	public static function getFieldType ( $Table, $fieldName ) {
		# Prepare
		$Table = self::getTable($Table);
		$type = null;
		
		# Fetch Table Information
		if ( $Table->hasRelation($fieldName) ) {
			# Relation Type
			$type = 'relation';
		}
		elseif ( $Table->hasField($fieldName) ) {
			# Field
			$properties = $Table->getDefinitionOf($fieldName);
			
			# Determine Type
			switch ( true ) {
				case real_value(delve($properties,'extra.password')):
					$type = 'password';
					break;
				
				case real_value(delve($properties,'extra.rating')):
					$type = 'rating';
					break;
				
				case real_value(delve($properties,'extra.csv')):
					$type = 'csv';
					break;
				
				case real_value(delve($properties,'extra.currency')):
					$type = 'currency';
					break;
				
				default:
					$type = $Table->getTypeOf($fieldName);
					break;
			}
		}
		else {
			throw new Bal_Exception('Could not determine the field type for ['.$fieldName.']');
		}
		
		# Return type
		return $type;
	}
	
	/**
	 * Determines and returns the label field name for the passed $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $Record
	 * @return string
	 */
	public static function getTableLabelFieldName ( $table ) {
		# Prepare
		$Table = self::getTable($table);
		$labelFieldNames = self::getLabelFieldNames();
		$result = null;
		
		# Handle
		foreach ( $labelFieldNames as $labelFieldName ) {
			if ( $Table->hasField($labelFieldName) ) {
				$result = $labelFieldName;
				break;
			}
		}
		
		# Return result
		return $result;
	}
	
	
	# ========================
	# TABLES
	
	/**
	 * Determine and return the desired $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table
	 * @return Doctrine_Table
	 */
	public static function getTable ( $table ) {
		# Prepare
		$Table = null;
		
		# Fetch
		if ( is_object($table) && $table instanceOf Doctrine_Table ) 
			$Table = $table;
		else {
			$tableComponentName = self::getTableComponentName($table);
			$Table = Doctrine::getTable($table);
		}
		
		# Return Table
		return $Table;
	}
	
	/**
	 * Determine and return a field name based upon the $Table $field
	 * @version 1.1, April 12, 2010
	 * @param mixed $table
	 * @return string
	 */
	public static function getFieldName ( $table, $field ) {
		# Prepare
		$Table = Bal_Doctrine_Core::getTable($table);
		
		# Handle
		$fieldName = $Table->getFieldName($field);
		
		# Return fieldName
		return $fieldName;
	}
	
	
	/**
	 * Determine and return the Table's tableComponentName for the desired $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table
	 * @return string
	 */
	public static function getTableName ( $table ) {
		# Prepare
		$tableName = null;
		
		# Handle
		if ( is_string($table) ) {
			$tableName = $table;
		}
		elseif ( is_object($table) ) {
			if ( $table instanceOf Doctrine_Table ) {
				$tableName = $table->getTableName();
			}
			elseif ( $table instanceOf Doctrine_Record ) {
				$tableName = $table->getTable()->getTableName();
			}
		}
		
		# Return table name
		return $tableName;
	}
	
	/**
	 * Determine and return the Table's tableComponentName for the desired $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table
	 * @return string
	 */
	public static function getTableComponentName ( $table ) {
		# Prepare
		$tableComponentName = null;
		
		# Handle
		if ( is_string($table) ) {
			$tableComponentName = $table;
		}
		elseif ( is_object($table) ) {
			if ( $table instanceOf Doctrine_Table ) {
				$tableComponentName = $table->getComponentName();
			}
			elseif ( $table instanceOf Doctrine_Record ) {
				$tableComponentName = $table->getTable()->getComponentName();
			}
		}
		
		# Return table name
		return $tableComponentName;
	}
	
	# ========================
	# CRUD HELPERS
	
	/**
	 * Determine and return the Table's listingFields for the desired $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table
	 * @return string
	 */
	public static function fetchListingFields ( $table ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($table);
		$fields = null;
		
		# Check to see if table has form
		if ( method_exists($tableComponentName, 'fetchListingFields') ) {
			$fields = call_user_func_array($tableComponentName.'::fetchListingFields', array());
		} else {
			$labelColumnName = self::getTableLabelFieldName($table);
			$fields = array($labelColumnName);
		}
		
		# Return columns
		return $fields;
	}
	
	
	# ========================
	# PARAMS
	
	
	/**
	 * Determine and return the value of the param associated with the item $tableComponentName
	 * @version 1.1, April 12, 2010
	 * @param string $tableComponentName
	 * @return mixed
	 */
	public static function fetchItemParam ( $tableComponentName, $default = null ) {
		# Prepare
		$item = false;
		
		# Check
		if ( $tableComponentName ) {
			# Fetch item
			$item = Bal_App::fetchParam($tableComponentName, Bal_App::fetchParam(strtolower($tableComponentName), $default));
		}
		
		# Return item
		return $item;
	}
	
	/**
	 * Fetch the item param, but if not an array, clear it
	 * @version 1.1, April 12, 2010
	 * @param string $tableComponentName
	 * @return array
	 */
	public static function fetchItemData ( $tableComponentName, $default = null ) {
		# Prepare
		$item = self::fetchItemParam($tableComponentName, $default);
		
		# Check
		if ( !is_array($item) ) {
			$item = array();
		}
		
		# Return item
		return $item;
	}
	
	/**
	 * Determines if the item data exists
	 * @version 1.0, May 05, 2010
	 * @param string $tableComponentName
	 * @return mixed
	 */
	public static function hasItemData ( $tableComponentName ) {
		# Prepare
		$exists = false;
		
		# Check
		if ( $tableComponentName ) {
		 	# Fetch item
			$exists = Bal_App::hasParam($tableComponentName, Bal_App::hasParam(strtolower($tableComponentName))) ? true : false;
		}
		
		# Return exists
		return $exists;
	}
	
	/**
	 * Determine and return the value of the param associated with the item $tableComponentName
	 * We also check the code and id params by default ($only=false)
	 * @version 1.1, April 12, 2010
	 * @param string 			$tableComponentName
	 * @param array 			$options [optional]
	 * 							The following options are provided:
	 * 								only: 		If true, we will only attempt to fetch the identifier from the standard item param, and not id and code params
	 * 								param: 		If specified, we use this as the item param
	 * @return mixed
	 */
	public static function fetchItemIdentifier ( $tableComponentName, array $options = array() ) {
		# Prepare
		$only = delve($options,'only',false);
		$param = delve($options,'param',$tableComponentName);
		$item = null;
		
		# Fetch item
		if ( $tableComponentName ) {
			# Fetch item
			$item = Bal_App::fetchParam(strtolower($tableComponentName), Bal_App::fetchParam($tableComponentName, null));
		}
		
		# Handle Array
		if ( is_array($item) ) {
			# Fetch Id from Array
			$item = delve($item,'id');
		}
		
		# Handle Empty
		if ( !$item && !$only ) {
			# Try Generic Params
			$item = Bal_App::fetchParam('code', false);
			if ( !$item ) $item = Bal_App::fetchParam('id', false);
		}
		
		# Return item
		return $item;
	}
	
	
	
	# ========================
	# FETCH HELPERS
	
	
	/**
	 * Resolve the ID of a Record
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function resolveId ( $value ) {
		# Prepare
		$result = null;
		
		# Handle
		if ( is_numeric($value) ) {
			# result
			$result = $value;
		}
		else {
			# other
			$result = delve($value,'id');
		}
		
		# Postpare
		$result = real_value($result);
		
		# Return result
		return $result;
	}
	
	/**
	 * Resolve the ID or Code of a Record
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function resolveIdOrCode ( $value ) {
		# Prepare
		$result = null;
		
		# Handle
		if ( is_numeric($value) ) {
			# result
			$result = $value;
		} elseif ( is_string($value) ) {
			# result
			$result = $value;
		} else {
			# other
			$result = delve($value,'id',delve($value,'code'));
		}
		
		# Postpare
		$result = real_value($result);
		
		# Return result
		return $result;
	}
	
	/**
	 * Grab the appropriate id or code, and return the value and type
	 * @throws Bal_Exception
	 * @version 1.0, May 05, 2010
	 * @return array
	 */
	public static function resolveIdentifier ( $table, $value ) {
		# Prepare
		$Table = self::getTable($table);
		$result = null;
		
		# Handle
		$value = self::resolveIdOrCode($value);
		if ( is_string($value) ) {
			if ( $Table->hasField('code') || $Table->hasColumn('code') || $Table->hasTemplate('Sluggable') ) {
				$column = 'code';
			}
			else {
				throw new Bal_Exception(array(
					'Could not resolve the identifier',
					'table' => $table,
					'value' => $value
				));
			}
		}
		else {
			$column = 'id';
		}
		
		# Return result
		return compact('column','value');
	}
	
	
	/**
	 * Fetch the Resources of a User
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function prepareFetchParams( array &$params, array $keep = array() ) {
		# Prepare
		$_keep = array('returnQuery','orderBy','hydrationMode','limit','where','search','paging');
		$keep = array_merge($keep,$_keep);
		array_keys_ensure($params,$keep);
		
		# Prepare
		if ( $params['hydrationMode'] === null )
			 $params['hydrationMode'] = Doctrine::HYDRATE_RECORD; // Doctrine::HYDRATE_ARRAY;
		if ( $params['returnQuery'] === null )
			 $params['returnQuery'] = false;
		
		# Return
		return $params;
	}
	
	/**
	 * Fetch the Resources of a User
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function prepareFetchResult( array $params, Doctrine_Query $Query, $table ) {
		# Prepare
		$keep = array('returnBaseQuery','returnQuery','orderBy','hydrationMode','limit','where','search','paging','relations','select','from');
		array_keys_keep_ensure($params,$keep);
		extract($params);
		
		# Check
		if ( $returnBaseQuery ) {
			return $Query;
		}
		
		# Prepare
		$Table = self::getTable($table);
		$tableComponentName = self::getTableComponentName($Table);
		$labelFieldName = self::getTableLabelFieldName($Table);
		$listingFields = self::fetchListingFields($Table);
		
		# Criteria
		if ( $select ) {
			# Replace Select
			$Query->select($select);
		}
		if ( $from ) {
			# Replace From
			$Query->from($from);
		}
		if ( $orderBy ) {
			# Replace Order
			$Query->orderBy($orderBy);
		}
		elseif ( $orderBy === null ) {
			# Add Default Order
			$Query->addOrderBy($labelFieldName.' ASC');
		}
		if ( $hydrationMode ) {
			# Add HydrationMode
			$Query->setHydrationMode($hydrationMode);
		}
		if ( $limit ) {
			# Add Limit
			$Query->limit($limit);
		}
		if ( $where && is_array($where) ) {
			# Add Wheres
			foreach ( $where as $_key => $_value ) {
				if ( is_array($_value) ) {
					$Query->andWhereIn($_key, $_value);
				} else {
					$Query->andWhere($_key.' = ?', $_value);
				}
			}
		}
		if ( $search ) {
			# Add Search
			if ( method_exists($Table,'search') ) {
				$Query = Doctrine::getTable($tableComponentName)->search($search, $Query);
			} else {
				$Query = $Query->andWhere($labelFieldName.' LIKE ?', '%'.$search.'%');
			}
		}
		if ( $relations ) {
			# Add Relations
			foreach ( $listingFields as $field ) {
				if ( $Table->hasRelation($field) && !$Query->contains($tableComponentName.'.'.$field.' '.$field) ) {
					$Query
						->addSelect($field.'.*')
						->addFrom($tableComponentName.'.'.$field.' '.$field)
						;
				}
			}
		}
		
		# Handle
		if ( $limit === 1 && !$returnQuery ) {
			# Return only one
			$result = $Query->execute();
			if ( $result ) {
				$result = $result[0];
			}
		}
		else {
			# Return many
			if ( $returnQuery ) {
				# Just Query
				$result = $Query;
			} elseif ( $paging ) {
				# With Paging
				$_paging = array(
					'page' => get_param('page', 1),
					'items' => Bal_App::getConfig('bal.paging.items'),
					'chunk' => Bal_App::getConfig('bal.paging.chunk')
				);
				if ( $paging === true ) {
					$paging = $_paging;
				} elseif ( is_array($paging) ) {
					$paging = array_merge($_paging, $paging);
				} else {
					throw new Zend_Exception('Unknown $paging type');
				}
				# Fetch
				$result = self::getPaging($Query, $paging['page'], $paging['items'], $paging['chunk']);
			} else {
				# Just Results
				$result = $Query->execute();
			}
		}
		
		# Return
		return $result;
	}
	
	
	# ========================
	# FETCH RECORD
	
	/**
	 * Trigger the Records fetch command, or emulate it
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public static function fetch ( $table, array $params = array() ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($table);
		$labelFieldName = self::getTableLabelFieldName($table);
		
		# Check
		if ( method_exists($tableComponentName,'fetch') ) {
			# Forward
			$result = call_user_func(array($tableComponentName,'fetch'),$params);
		}
		else {
			# Prepare
			self::prepareFetchParams($params);
			
			# Query
			$Query = Doctrine_Query::create()
				->select($tableComponentName.'.*')
				->from($tableComponentName)
				->orderBy($tableComponentName.'.'.$labelFieldName.' ASC');
			
			# Basic Support
			switch ( true ) {
				// array('User'=>3), $item = 3;
				case $item = delve($params,$tableComponentName):
					$identifier = Bal_Doctrine_Core::resolveIdentifier($item);
					$Query->andWhere(
						$tableComponentName.'.'.$identifer['column'].' = ?',
						$identifer['value']
					);
					break;
				
				default:
					break;
			}
			
			# Fetch
			$result = self::prepareFetchResult($params,$Query,$tableComponentName);
		}
		
		# Return result
		return $result;
	}
	
	/**
	 * Get a Record based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public static function fetchRecord ( $table, array $params = array() ) {
		# Force
		$params['limit'] = 1;
		
		# Fetch
		$result = self::fetch($table,$params);
		
		# Return result
		return $result;
	}
	
	/**
	 * Get Records based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public static function fetchRecords ( $table, array $params = array() ) {
		# Fetch
		$result = self::fetch($table,$params);
		
		# Return result
		return $result;
	}
	
	/**
	 * Get Query based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return Doctrine_Query
	 */
	public static function fetchQuery ( $table, array $params = array() ) {
		# Force
		$params['returnQuery'] = true;
		
		# Fetch
		$result = self::fetch($table,$params);
		
		# Return result
		return $result;
	}
	
	/**
	 * Get the Base Query used for fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return Doctrine_Query
	 */
	public static function fetchBaseQuery ( $table, array $params = array() ) {
		# Force
		$params['returnBaseQuery'] = true;
		
		# Fetch
		$result = self::fetch($table,$params);
		
		# Return result
		return $result;
	}
	
	# ========================
	# CRUD RECORD
	
	/**
	 * Get a Record by resolving, and verifying. We may also create if desired.
	 * @version 1.1, April 12, 2010
	 * @param mixed $table
	 * @param mixed	$record
	 * @param array $options [optional]		The following options are provided:
	 * 											verify: If true, we will verify the Record, if array we will verify with options [false by default]
	 * 										We also forward the options to
	 * 											@see self::resolveRecord
	 * @return Doctrine_Record
	 */
	public static function getRecord ( $tableComponentName, $record, array $options = array() ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($tableComponentName);
		$Record = null;
		
		# Prepare Options
		array_keys_ensure($options,array('verify'),false);
		
		# Handle
		try {
			# Resolve
			$Record = self::resolveRecord($tableComponentName, array($record), $options);
			
			# Verify
			if ( $Record && $options['verify'] ) {
				$verify = $options['verify'];
				if ( !is_array($verify) ) $verify = array();
				self::verifyRecord($Record, $verify);
			}
		}
		catch ( Exception $Exception ) {
			# Reset
			$Record = false;
		
			# Log the Event and Continue
			$Exceptor = new Bal_Exceptor($Exception);
			$Exceptor->log();
		}
		
		# Return Record
		return $Record;
	}
	
	/**
	 * Get a Record determined by the series of passed arguments
	 * @version 1.1, April 12, 2010
	 * @param string	$tableComponentName		The table/type of the record
	 * @param array 	$inputs					The input used to determine the record
	 * @param array 	$options [optional]		The following options are provided:
	 * 											create: Create the Record if it wasn't found? [default=true]
	 * 											fetch: Options to pass to fetch
	 * @return Doctrine_Record
	 */
	public static function resolveRecord ( $tableComponentName, array $inputs, array $options = array() ) {
		# Prepare
		$Record = null;
		$tableComponentName = self::getTableComponentName($tableComponentName);
		
		# Prepare Options
		array_keys_ensure($options,array('create'),true);
		
		# Cycle through Arguments
		foreach ( $inputs as $in ) {
			
			# Handle
			if ( $in instanceof $tableComponentName ) {
				# Is our Record
				$Record = $in;
			}
			elseif ( is_numeric($in) || is_string($in) ) {
				# Is a Record Identifier (hopefully)
				$Record = self::fetchRecord($tableComponentName, array($tableComponentName=>$in)+delve($options,'fetch',array()));
			}
			elseif ( is_array($in) ) {
				# Is a Array
				if ( !empty($in['id']) ) {
					# Which has a Record ID
					$Record = self::resolveRecord($tableComponentName, array($in['id']));
				}
				elseif ( !empty($in['code']) ) {
					# Which has a Record Code
					$Record = self::resolveRecord($tableComponentName, array($in['code']));
				}
			}
			elseif ( is_object($in) ) {
				# Is some other Record
				throw new Bal_Exception(array(
					'resolveRecord was passed a record that we did not desire',
					'Record' => $in
				));
			}
			
			# Check Find
			if ( delve($Record,'id') ) {
				# We found a Record, Stop cycling
				break;
			}
		}
		
		# Check
		if ( $Record && $Record instanceof $tableComponentName ) {
			# Good
		}
		else {
			# Bad
			$Record = $options['create'] ? new $tableComponentName() : null;
		}
		
		# Return Record
		return $Record;
	}
	
	/**
	 * Apply $data properly to the doctrine $Record, with $options
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Record 	$Record
	 * @param array 			$data
	 * @param array 			$options [optional]
	 * 							The following options are provided:
	 * 								data:			Perhaps we pass data over here instead
	 * 								keep: 			Keep only these keys for the data
	 * 								remove: 		Remove these keys from the data
	 * 								empty: 			Empty these keys from the data
	 * 								ensure: 		Ensure these keys exist within the data (Useful for relations)
	 * 								clean: 			If these keys are empty, remove them
	 * 								always_save: 	Always save relations
	 * 								apply: 			Force these field-values onto the data
	 * 								default: 		If these field-values do not exist in the data, add them
	 * @return Doctrine_Record
	 */
	public static function applyRecord ( Doctrine_Record $Record, array $data, array $options = array() ) {
		# Prepare
		$Table = $Record->getTable();
		$data = delve($options,'data',$data);
		
		# Prepare Options
		array_keys_keep_ensure($options,array('keep','remove','empty','ensure','clean','always_save','apply','default'),null);
		extract($options);
		
		# Prepare
		if ( $clean )
			array_keys_clean($data, $clean);
		if ( $keep )
			array_keys_keep($data, $keep);
		if ( $remove )
			array_keys_unset($data, $remove);
		if ( $empty )
			array_keys_unset_empty($data, $empty);
		if ( $ensure )
			array_keys_ensure($data, $ensure);
		
		# Clean special values
		array_clean_form($data);
		
		# Fetch extra item data
		$apply = delve($options,'apply',array());
		
		# Fetch default item data
		$default = delve($options,'default',array());
		
		# Merge
		$data = array_merge($default,$data,$apply);
		
		# Check that we have something to do
		if ( !$data ) {
			return $Record;
		}
		
		# Cycle through values applying each one
		foreach ( $data as $key => $value ) {
			# Prepare
			$skip = false;
			
			# Check Relation
			if ( $Table->hasRelation($key) ) {
				# Is Relation
				$Relation = $Table->getRelation($key);
				$RelationTable = $Relation->getTable();
				if ( !is_object($value) ) {
					// We are a scalar
					if ( $Relation->getType() === Doctrine_Relation::MANY ) {
						# Clear all previously, will re-apply on set
						$Record->unlink($key);
						
						# Many Type, Needs Doctrine_Collection
						if ( !$value ) {
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
									if ( !$_value ) continue;
									# Create
									$valueRecord = self::getRecord($RelationTable, $_value);
									self::applyRecord($valueRecord,$_value);
									if ( $always_save || $valueRecord->id )
										$valueRecord->save(); // save if exists, for some reason the values don't apply otherwise
									$_values[] = $valueRecord; 
								}
								$value = $_values;
							}
							else {
								# Find Multiple
								if ( !is_array($value) ) $value = array($value);
								$skip = true;
								$Record->link($key, $value);
								// $value = $RelationTable->createQuery()->select('*')->whereIn('id', $value)->execute();
								// ^ should be an array of ids, so use link instead, doesn't really matter
							}
						}
						
						# Done Multiple
						
					} else {
						# One Type, Needs Record
						if ( !$value || delve($value,'_delete_') ) {
							# Empty
							$value = null;
						}
						elseif ( $value ) {
							# Type
							if ( is_array($value) ) {
								# Prepare
								unset($value['_delete_']);
								# Create
								$valueRecord = self::getRecord($RelationTable, $value);
								self::applyRecord($valueRecord,$value);
								if ( $always_save || $valueRecord->id )
									$valueRecord->save(); // save if exists, for some reason the values don't apply otherwise
								$value = $valueRecord; 
							}
							else {
								# Discover
								$value = $RelationTable->find($value);
							}
						}
						
						# Done One
					}
				}
			}
			
			# Check if we accept null
			$properties = $Table->getDefinitionOf($key);
			$notblank = real_value(delve($properties,'notblank',false));
			$notnull = real_value(delve($properties,'notnull',$notblank));
			if ( $value === '' && !$notnull ) {
				$value = null;
			}
			
			# Apply
			if ( !$skip ) {
				$Record->set($key, $value);
			}
		}
		// $Item->merge($item);
		// ^ Always fires special setters this way
		
		# Return Record
		return $Record;
	}
	
	
	/**
	 * Delete the Item properly
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Record $Record
	 * @param array 			$options [optional]
	 * 							The following options are provided:
	 * 								verify: 		If true, we will verify the Record, if array we will verify with options [false by default]
	 * 								transact: 		Whether or not to perform a transaction [true by default]
	 * 								log: 			Whether or not to log [true by default]
	 * 								throw: 			Whether or not to throw errors [false by default]
	 * @return bool 			Whether or not the record was deleted
	 */
	public static function deleteRecord ( Doctrine_Record $Record, array $options = array() ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($Record);
		$tableComponentNameLower = strtolower($tableComponentName);
		$result = false;
		
		# Prepare Options
		array_keys_keep_ensure($options,array('verify','transact','log','throw'));
		extract($options);
		
		# Default Options
		if ( $throw === null )		$throw = false;
		if ( $transact === null )	$transact = true;
		if ( $log === null )		$log = true;
		
		# Prepare
		if ( $transact )
			$Connection = Bal_App::getDataConnection();
		if ( $log )
			$Log = Bal_App::getLog();
		
		
		# Handle
		try {
			# Handle
			if ( $Record && $Record->exists() ) {
				# Start
				if ( $transact )
					$Connection->beginTransaction();
				
				# Verify
				$verify_options = delve($options,'verify',array());
				self::verifyRecord($Record, $verify_options);
			
				# Extract while we can
				$RecordArray = $Record->toArray(true);
		
				# Delete
				$Record->delete();
			
				# Commit
				if ( $transact ) {
					$Connection->commit();
				}
				$result = true;
		
				# Log
				if ( $log ) {
					$log_details = array(
						$tableComponentName	=> $RecordArray
					);
					$Log->log(array('log-'.$tableComponentNameLower.'-delete',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
				}
			}
			else {
				throw new Zend_Exception('error-'.$tableComponentNameLower.'-missing');
			}
		}
		catch ( Exception $Exception ) {
			# Rollback
			if ( $transact )
				$Connection->rollback();
			
			# Handle
			if ( $throw ) {
				throw $Exception;
			}
			else {
				# Log the Event and Continue
				$Exceptor = new Bal_Exceptor($Exception);
				$Exceptor->log();
			}
		}
		
		# Return result
		return $result;
	}
	
	/**
	 * Save the Record properly with $options
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Record 	$Record
	 * @param array 			$options [optional]
	 * 							The following options are provided:
	 * 								verify: 		If true, we will verify the Record, if array we will verify with options [false by default]
	 * 								transact: 		Whether or not to perform a transaction [true by default]
	 * 								log: 			Whether or not to log [true by default]
	 * 								throw: 			Whether or not to throw errors [false by default]
	 * @return bool
	 */
	public static function saveRecord ( Doctrine_Record $Record, array $options = array() ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($Record);
		$tableComponentNameLower = strtolower($tableComponentName);
		$result = false;
		
		# Prepare Options
		array_keys_keep_ensure($options,array('verify','transact','log','throw'));
		extract($options);
		
		# Default Options
		if ( $throw === null )		$throw = false;
		if ( $transact === null )	$transact = true;
		if ( $log === null )		$log = true;
		
		# Prepare
		if ( $transact )
			$Connection = Bal_App::getDataConnection();
		if ( $log )
			$Log = Bal_App::getLog();
		
		
		# Handle
		try {
			# Start
			if ( $transact )
				$Connection->beginTransaction();
			
			# Verify
			$verify_options = delve($options,'verify',array());
			self::verifyRecord($Record, $verify_options);
			
			# Save Record
			$Record->save();
			
			# Finish
			if ( $transact ) {
				$Connection->commit();
			}
			$result = true;
			
			# Log
			if ( $log ) {
				$log_details = array(
					$tableComponentName			=> $Record->toArray(true),
					$tableComponentName.'_url'	=> Bal_App::getActionControllerView()->url()->item($Record)->toString()
				);
				$Log->log(array('log-'.$tableComponentNameLower.'-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
			}
		}
		catch ( Exception $Exception ) {
			# Reset
			$Record = false;
			
			# Rollback
			if ( $transact )
				$Connection->rollback();
			
			# Handle
			if ( $throw ) {
				throw $Exception;
			}
			else {
				# Log the Event and Continue
				$Exceptor = new Bal_Exceptor($Exception);
				$Exceptor->log();
			}
		}
		
		# Return result
		return $result;
	}
	
	# ========================
	# CRUD ITEM
	
	/**
	 * Fetch a Record based upon it's fetchItemIdentifier result
	 * If a Record was not found, create one depending on $create
	 * A custom query can be passed via $Query
	 * 
	 * @version 1.1, April 12, 2010
	 * @param mixed 				$tableComponentName
	 * @param mixed		 			$item
	 * @param array 				$options [optional]
	 * 								@see self::getRecord
	 * @return Doctrine_Record
	 */
	public static function getItem ( $tableComponentName, $item = null, array $options = array() ) {
		# Get Item
		if ( $item === null ) {
			$item = self::fetchItemIdentifier($tableComponentName, $options);
		}
		$Item = self::getRecord($tableComponentName, $item, $options);
		
		# Return Item
		return $Item;
	}
	
	/**
	 * Fetch a Item (AND REQUEST DATA) based upon it's fetchItemIdentifier result
	 * If a Record was not found, create one depending on $create
	 * A custom query can be passed via $Query
	 * 
	 * @version 1.1, April 12, 2010
	 * @param mixed 				$tableComponentName
	 * @param mixed		 			$item
	 * @param array 				$options [optional]
	 * 								@see self::getItem
	 * 								@see self::applyRecord
	 * @return Doctrine_Record
	 */
	public static function fetchItem ( $tableComponentName, $item = null, array $options = array() ) {
		# Get Item
		$Item = self::getItem($tableComponentName,$item,$options);
		
		# Check
		if ( $Item ) {
			# Fetch item data
			if ( !array_key_exists('data',$options) ) {
				$options['data'] = self::fetchItemData($tableComponentName);
			}
			
			# Apply Item
			self::applyRecord($Item, array(), $options);
		}
		
		# Return Item
		return $Item;
	}
	
	/**
	 * Fetch the Item, and Save the Item if forced, or if POST detected
	 * @version 1.1, April 12, 2010
	 * @param mixed 				$tableComponentName
	 * @param mixed		 			$item
	 * @param array 				$options [optional]e
	 * 								Additionally we pass the options to:
	 * 									@see self::fetchItem
	 * 									@see self::saveItem
	 * @return Doctrine_Record
	 */
	public static function fetchAndSaveItem ( $tableComponentName, $item = null, array $options = array() ) {
		# Prepare
		$Item = self::fetchItem($tableComponentName, $item, $options);
		
		# Save the Item
		if ( $Item ) self::saveItem($Item,$options);
		
		# Return Item
		return $Item;
	}
	
	/**
	 * Save the Item if forced, or if POST detected
	 * @version 1.1, April 12, 2010
	 * @param mixed 				$tableComponentName
	 * @param mixed		 			$item
	 * @param array 				$options [optional]
	 * 								We use the following options
	 * 									force:	Whether or not to force the delete
	 * 								Additionally we pass the options to:
	 * 									@see self::saveRecord
	 * @return bool					Whether or not we saved
	 */
	public static function saveItem ( Doctrine_Record $Record, array $options = array() ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($Record);
		$result = false;
		
		# Determine if we want to save
		if ( !empty($options['force']) || self::hasItemData($tableComponentName) ) {
			# Save Item
			$result = self::saveRecord($Record, $options);
		}
		
		# Return result
		return $result;
	}
	
	/**
	 * Fetch the Item, and Delete the Item if forced, or if POST detected
	 * @version 1.1, April 12, 2010
	 * @param mixed 				$tableComponentName
	 * @param mixed		 			$item
	 * @param array 				$options [optional]e
	 * 								Additionally we pass the options to:
	 * 									@see self::fetchItem
	 * 									@see self::saveItem
	 * @return bool
	 */
	public static function fetchAndDeleteItem ( $tableComponentName, $item = null, array $options = array() ) {
		# Prepare
		$Item = self::getItem($tableComponentName, $item, $options);
		
		# Save the Item
		$result = self::deleteItem($Item,$options);
		
		# Return result
		return $result;
	}
	
	/**
	 * Delete the Item if forced, or if POST detected
	 * @version 1.1, April 12, 2010
	 * @param mixed 				$tableComponentName
	 * @param mixed		 			$item
	 * @param array 				$options [optional]
	 * 								We use the following options
	 * 									force:	Whether or not to force the delete
	 * 								Additionally we pass the options to:
	 * 									@see self::deleteRecord
	 * @return bool					Whether or not the record was deleted
	 */
	public static function deleteItem ( Doctrine_Record $Record, array $options = array() ) {
		# Prepare
		$tableComponentName = self::getTableComponentName($Record);
		$result = false;
		
		# Determine if we want to save
		if ( $Record->id && (!empty($options['force']) || self::hasItemData($tableComponentName)) ) {
			# Save Item
			$result = self::deleteRecord($Record, $options);
		}
		
		# Return result
		return $result;
	}
	
	# ========================
	# ENSURE HELPERS
	
	/**
	 * Verify the Record by cycling through it's checks - Called from within the record
	 * MADE for the soul purpose of being called by Doctrine_Record::verify()
	 * @version 1.0, April 16, 2010
	 * @param Doctrine_Record $Record
	 * @param array $params
	 * @param array $checks
	 * @return boolean	wheter or not to save / can also throw exceptions
	 */
	public static function verify ( Doctrine_Record $Record, array $params, array $checks ){
		# Prepare
		$verify = array();
		
		# Verify All
		$verify_all = delve($params, 'all', true);
		
		# Ensure
		foreach ( $checks as $check ) {
			# Fetch
			$check_params = delve($params,$check,true);
			# Fire?
			if ( $verify_all && $check_params ) {
				# Prepare
				if ( !is_array($check_params) ) {
					// for instance if it was true, to signify the check should run
					$check_params = array();
				}
				# Fire
				$verify[] = $Record->$check($check_params);
			}
		}
		
		# Result
		$result = in_array(true,$verify);
		
		# Return result
		return $result;
	}
	
	/**
	 * Verify the Record meets it's requirements before we save
	 * MADE for the soul purpose of calling Doctrine_Record::verify
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Record $Record
	 * @param array $options [optional] - Data to be passed to the verifier
	 * @return boolean - is valid?
	 */
	public static function verifyRecord ( Doctrine_Record $Record, array $params = array() ) {
		# Prepare
		$result = true;
		
		# Check to see if table has form
		if ( method_exists($Record, 'verify') ) {
			# Has Verifier
			$result = $Record->verify($params);
		} else {
			# No Verifier
			# No Action
		}
		
		# Return result
		return $result;
	}
	
	/**
	 * Ensure Consistency
	 * MADE for the soul purpose of being called by Doctrine_Record::ensure()
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $Event_type
	 * @param array $checks
	 * @return boolean	wheter or not to save
	 */
	public static function ensure ( Doctrine_Event $Event, $Event_type, array $checks ){
		# Prepare
		$Invoker = $Event->getInvoker();
		$ensure = array();
		
		# Ensure
		foreach ( $checks as $check ) {
			$ensure[] = $Invoker->$check($Event,$Event_type);
		}
		
		# Result
		$result = in_array(true,$ensure);
		
		# Return result
		return $result;
	}
	
	
	/**
	 * Ensure a One Relation that has a cache
	 * MADE for the soul purpose of being called by Doctrine_Record::ensureSomething()
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $relation
	 * @return bool
	 */
	public static function ensureOne ( Doctrine_Event $Event, $relation ) {
		# Prepare
		$save = false;
		
		# Fetch
		$Invoker = $Event->getInvoker();
		
		# Prepare Arguments
		$down = strtolower($relation);
		$up = $relation;
		
		# Result
		$result = isset($Invoker->$up) ? $Invoker->$up->title : null;
		
		# Ensure
		if ( $Invoker->get($down) != $result ) {
			$Invoker->set($down, $result, false);
			$save = true;
		}
		
		# Done
		return $save;
	}
	
	/**
	 * Ensure a Many Relation that has a cache
	 * MADE for the soul purpose of being called by Doctrine_Record::ensureSomething()
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $relation
	 * @return bool
	 */
	public static function ensureMany ( Doctrine_Event $Event, $relation ) {
		# Prepare
		$save = false;
		$result = array();
		
		# Fetch
		$Invoker = $Event->getInvoker();
		
		# Prepare Arguments
		$down = strtolower($relation);
		$up = $relation;
		
		# Result
		foreach ( $Invoker->$up as $Item ) {
			$result[] = $Item->title;
		}
		$result = implode(', ', $result);
		
		# Ensure
		if ( $Invoker->get($down) != $result ) {
			$Invoker->set($down, $result, false);
			$save = true;
		}
		
		# Done
		return $save;
	}
	
	/**
	 * Ensure Tags
	 * MADE for the soul purpose of being called by Doctrine_Record::ensureSomething()
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $tagRelation
	 * @param string $tagField
	 * @return boolean	wheter or not to save
	 */
	public static function ensureTags ( Doctrine_Event $Event, $tagRelation, $tagField ) {
		# Prepare
		$save = false;
		
		# Fetch
		$Invoker = $Event->getInvoker();
		$modified = $Invoker->getModified();
		$modifiedLast = $Invoker->getLastModified();
		$tagRelationNames = $tagRelation.'Names';
		
		# Fetch
		$tagsSystemOrig = $Invoker->$tagRelationNames;
		$tagsUserOrig = $Invoker->$tagField;
		$tagsSystem = prepare_csv_str($tagsSystemOrig);
		$tagsUser = prepare_csv_str($tagsUserOrig);
		$tagsUserNewer = array_key_exists($tagField, $modified);
		$tagsSystemNewer = !array_key_exists($tagField, $modified) && !array_key_exists($tagField, $modifiedLast);
		$tagsDiffer = $tagsUser != $tagsSystem;
		
		# TagField > TagField
		if ( ($tagsDiffer || $tagsUserOrig != $tagsUser) && $tagsUserNewer ) {
			# TagField is newer than TagField
			
			# Save TagField
			$Invoker->set($tagField, $tagsUser, false); // false at end to prevent comparison
			
			# Save
			$save = true;
		}
		
		# TagField > TagRelation
		if ( $tagsDiffer && !$tagsSystemNewer ) {
			# TagField is newer than TagRelation
			
			# Check whether we can save
			if ( $Invoker->id ) {
				# Save TagRelation
				$Invoker->$tagRelation = $tagsUser;
				
				# Save
				$save = true;
			}
		}
		
		# TagRelation > $TagField
		if ( $tagsDiffer && $tagsSystemNewer ) {
			# TagRelation is newer than TagField
			
			# Save TagField
			$Invoker->set($tagField, $tagsSystem, false); // false at end to prevent comparison
			
			# Save
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	
	# ========================
	# SET HELPERS
	
	
	/**
	 * Set a File Attachment for a Record
	 * @version 1.1, April 12, 2010
	 * @return string
	 */
	public static function presetFileAttachment ( Doctrine_Record $Record, $what, $file ) {
		# Prepare
		$File = File::fetchFile($file);
		$result = false;
		
		# Apply File
		if ( $File === null || $File ) {
			if ( isset($Record->$what) ) {
				$Record->$what->delete();
			}
			$result = $File ? $File : null;
		}
		
		# Done
		return $result;
	}
	
	
	# ========================
	# PAGING
	
	
	/**
	 * Get the Pager
	 * @param integer $page_current [optional] Which page are we on?
	 * @param integer $page_items [optional] How many items per page?
	 * @return
	 */
	public static function getPager($DQ, $page_current = 1, $page_items = 10){
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
	public static function getPages($Pager, $PagerRange, $page_current = 1){
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
	public static function getPaging($DQ, $page_current = 1, $page_items = 5, $pages_chunk = 5){
		# Prepare
		$page_current = intval($page_current);
		$page_items = intval($page_items);
		$pages_chunk = intval($pages_chunk);
		
		# Fetch
		$Pager = self::getPager($DQ, $page_current, $page_items);
		
		# Results
		$PagerRange = new Doctrine_Pager_Range_Sliding(array(
				'chunk' => $pages_chunk
    		),
			$Pager
		);
		$Items = $Pager->execute();
		$Items_count = count($Items);
		
		# Get Pages
		$Pages = self::getPages($Pager, $PagerRange, $page_current);
		
		# Check page current
		$page_first = $Pager->getFirstPage();
		$page_last = $Pager->getLastPage();
		if ( $page_current > $page_last ) $page_current = $page_last;
		elseif ( $page_current < $page_first ) $page_current = $page_first;
		
		# Totals
		$total = $page_last*$page_items;
		$finish = $page_last==$page_current ? $total : $page_current*$page_items;
		$start = ($page_current-1)*$page_items+1;
		
		# Done
		return array($Items, array(
			'first' => $page_first,
			'last' => $page_last,
			'current' => $page_current,
			'pages' => $Pages,
			'items' => $page_items,
			'count' => $Items_count,
			'chunk' => $pages_chunk,
			'start' => $start,
			'finish' => $finish,
			'total' => $total
		));
	}
	
}