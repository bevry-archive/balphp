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
	 * Determine and return the Table's componentName for the desired $table
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
			// in call_user_func_array to prevent issue on older php version, rather than just doing $tableComponentName::fetchForm
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
	public static function fetchItemParam ( $tableComponentName ) {
		# Prepare
		$item = false;
		
		# Check
		if ( !$tableComponentName ) return $item;
		
		# Fetch item
		$item = Bal_App::fetchParam($tableComponentName, Bal_App::fetchParam(strtolower($tableComponentName), false));
		
		# Return item
		return $item;
	}
	
	/**
	 * Determine and return the value of the param associated with the item $tableComponentName
	 * We also check the code and id params by default ($only=false)
	 * @version 1.1, April 12, 2010
	 * @param string $tableComponentName
	 * @param bool $only [optional]
	 * @return mixed
	 */
	public static function fetchItemIdentifier ( $tableComponentName, $only = false ) {
		# Fetch item
		$item = self::fetchItemParam($tableComponentName);
		
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
	 * Fetch the Resources of a User
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function resolveId ( $value ) {
		$id = is_numeric($value) ? $value : delve($value, 'id');
		$id = real_value($id);
		return $id;
	}
	
	/**
	 * Fetch the Resources of a User
	 * @version 1.1, April 12, 2010
	 * @return array
	 */
	public static function prepareFetchParams( array &$params, array $keep ) {
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
		$keep = array('returnQuery','orderBy','hydrationMode','limit','where','search','paging','relations','select','from');
		array_keys_keep_ensure($params,$keep);
		extract($params);
		
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
				if ( $Table->hasRelation($field) ) {
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
			if ( !empty($result) ) {
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
					'items' => self::getConfig('bal.paging.items'),
					'chunk' => self::getConfig('bal.paging.chunk')
				);
				if ( $paging === true ) {
					$paging = $_paging;
				} elseif ( is_array($paging) ) {
					$paging = array_merge($_paging, $paging);
				} else {
					throw new Zend_Exception('Unkown $paging type');
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
	 * Get a Record based upon fetch standards
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param array $params [optional]
	 * @return mixed
	 */
	public static function fetchRecord ( $table, array $params = array() ) {
		# Prepare
		$componentName = self::getTableComponentName($table);
		
		# Apply
		$params['limit'] = 1;
		
		# Fetch
		$result = $componentName::fetch($params);
		
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
		# Prepare
		$componentName = self::getTableComponentName($table);
		
		# Fetch
		$result = $componentName::fetch($params);
		
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
		# Prepare
		$componentName = self::getTableComponentName($table);
		
		# Apply
		$params['returnQuery'] = true;
		
		# Fetch
		$result = $componentName::fetch($params);
		
		# Return result
		return $result;
	}
	
	# ========================
	# CRUD RECORD
	
	
	/**
	 * Get a Record determined by the series of passed arguments
	 * @version 1.1, April 12, 2010
	 * @param string $table The table/type of the record
	 * @param mixed ... [optional] The input used to determine the record
	 * @return Doctrine_Record
	 */
	public static function getRecord ( $table ) {
		# Prepare
		$Record = null;
		$args = func_get_args(); array_shift($args); // pop first (type)
		$Table = self::getTable($table);
		$tableComponentName = $Table->getComponentName();
		
		# Cycle through Arguments
		foreach ( $args as $in ) {
			
			# Handle
			if ( $in instanceof $tableComponentName ) {
				# Is our Record
				$Record = $in;
			} elseif ( is_object($in) ) {
				# Is some Record
				if ( !empty($in->id) )
					$Record = self::getRecord($tableComponentName, $in->id);
			} elseif ( is_numeric($in) ) {
				# Is a Record ID
				$Record = Doctrine::getTable($tableComponentName)->find($in);
			} elseif ( is_string($in) ) {
				# Is a Record Code
				if ( Doctrine::getTable($tableComponentName)->hasColumn($in) )
					$Record = Doctrine::getTable($tableComponentName)->findByCode($in);
			} elseif ( is_array($in) ) {
				# Is a Array
				if ( !empty($in['id']) ) {
					# Which has a Record ID
					$Record = self::getRecord($tableComponentName, $in['id']);
				} elseif ( !empty($in['code']) ) {
					# Which has a Record Code
					$Record = self::getRecord($tableComponentName, $in['code']);
				}
			}
			
			# Check Find
			if ( !delve($Record,'id') ) {
				# We found a Record, Stop cycling
				break;
			}
		}
		
		# Create if Empty
		if ( empty($Record) ) {
			$Record = new $tableComponentName;
		}
		
		# Return Record
		return $Record;
	}
	
	/**
	 * Apply $data properly to the doctrine $Record, with $options
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Record $Record
	 * @param array $data
	 * @param array $options [optional] - keep, remove, empty
	 * @return Doctrine_Record
	 */
	public static function applyRecord ( Doctrine_Record $Record, array $data, array $options = array() ) {
		# Prepare
		$Table = $Record->getTable();
		
		# Prepare Options
		array_keys_keep_ensure($options,array('keep','remove','empty'));
		extract($options);
		
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
									$valueRecord = self::getRecord($RelationTable, $_value);
									self::applyRecord($valueRecord,$_value);
									if ( $valueRecord->id )
										$valueRecord->save(); // save if exists, for some reason the values don't apply otherwise
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
									$valueRecord = self::getRecord($RelationTable, $value);
									self::applyRecord($valueRecord,$value);
									if ( $valueRecord->id )
										$valueRecord->save(); // save if exists, for some reason the values don't apply otherwise
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
		
		# Return Record
		return $Record;
	}
	
	
	/**
	 * Apply $data properly to the doctrine $Record and save it, with $options
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Record $Record
	 * @param array $data
	 * @param array $options [optional] - keep, remove, empty
	 * @return Doctrine_Record
	 */
	public static function saveRecord ( Doctrine_Record $Record, array $data, array $options = array() ) {
		# Apply
		self::applyRecord($Record, $data, $options);
		
		# Save
		$Record->save();
		
		# Return Record
		return $Record;
	}
	
	
	# ========================
	# CRUD ITEM
	
	/**
	 * Fetch a Item based upon it's fetchItemIdentifier result
	 * If a Record was not found, create one depending on $create
	 * A custom query can be passed via $Query
	 * 
	 * @version 1.1, April 12, 2010
	 * @param string $table
	 * @param Doctrine_Record $Record
	 * @param array $options [optional] - Query, create, only
	 * @return Doctrine_Record
	 */
	public static function fetchItem ( $table, $Record = null, array $options = array() ) {
		# Prepare
		$item = $Item = false;
		$tableComponentName = self::getTableComponentName($table);
		
		# Prepare Options
		array_keys_keep_ensure($options,array('Query','create','only'));
		extract($options);
		
		# Check
		if ( is_object($Record) && ($Record instanceOf $tableComponentName) ) {
			return $Record;
		}
		
		# Fetch Param
		$item = self::fetchItemIdentifier($tableComponentName, $only);
		
		# Load
		if ( $item ) {
			# Prepare Query
			if ( $Query === null ) {
				$Query = self::fetchQuery($tableComponentName);
			}
			
			# Search Query
			$fetch = false;
			if ( is_numeric($item) ) {
				$Query->andWhere($tableComponentName.'.id = ?', $item);
				$fetch = true;
			}
			elseif ( is_string($item) ) {
				$Query->andWhere($tableComponentName.'.code = ?', $item);
				$fetch = true;
			}
			
			# Fetch
			if ( $fetch ) {
				$Item = $Query->fetchOne();
			}
		}
		
		# Create if empty?
		if ( $create && !delve($Item,'id') && $tableComponentName ) {
			$Item = new $tableComponentName();
		}
		
		# Return Item
		return $Item;
	}
	
	/**
	 * Save the Item properly with $options
	 * @version 1.1, April 12, 2010
	 * @param string $table
	 * @param Doctrine_Record $Record
	 * @param array $options [optional] - keep, remove, empty | Query, create, only
	 * @return Doctrine_Record
	 */
	public static function saveItem ( $table, Doctrine_Record $Record = null, array $options = array() ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Request = Bal_App::getRequest();
		$Log = Bal_App::getLog();
		$item = $Item = null;
		$Table = self::getTable($table);
		$tableComponentName = self::getTableComponentName($table);
		$tableComponentNameLower = strtolower($tableComponentName);
		
		# Fetch
		$Item = self::fetchItem($table,$Record,$options);
		$item = self::fetchItemParam($tableComponentName);
		
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
			self::saveRecord($Item, $item, $options);
			
			# Stop Duplicates
			$Request->setPost($tableComponentName, $Item->id);
			
			# Finish
			$Connection->commit();
			
			# Log
			$log_details = array(
				$tableComponentName			=> $Item->toArray(),
				$tableComponentName.'_url'	=> self::getActionControllerView()->url()->item($Item)->toString()
			);
			$Log->log(array('log-'.$tableComponentNameLower.'-save',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
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
	
	/**
	 * Delete the Item properly
	 * @version 1.1, April 12, 2010
	 * @param string $table
	 * @param Doctrine_Record $Record
	 * @return Doctrine_Record
	 */
	public static function deleteItem ( $table, Doctrine_Record $Record = null ) {
		# Prepare
		$Connection = Bal_App::getDataConnection();
		$Log = Bal_App::getLog();
		$result = true;
		$Table = self::getTable($table);
		$tableComponentName = self::getTableComponentName($table);
		$tableComponentNameLower = strtolower($tableComponentName);
		
		# Fetch
		$Item = self::fetchItem($table, $Record);
		$item = self::fetchItemParam($tableComponentName);
		
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
					$tableComponentName	=> $ItemArray
				);
				$Log->log(array('log-'.$tableComponentNameLower.'-delete',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
			}
			else {
				throw new Zend_Exception('error-'.$tableComponentNameLower.'-missing');
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
	
	# ========================
	# ENSURE HELPERS
	
	
	/**
	 * Ensure Consistency
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $Event_type
	 * @return boolean	wheter or not to save
	 */
	public static function ensure ( $Event, $Event_type, array $checks ){
		# Prepare
		$ensure = array();
		$Invoker = $Event->getInvoker();
		
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
	 * @version 1.1, April 12, 2010
	 * @return bool
	 */
	public static function ensureOne ( $Event, $relation ) {
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
	 * @version 1.1, April 12, 2010
	 * @return bool
	 */
	public static function ensureMany ( $Event, $relation ) {
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
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $Event_type
	 * @return boolean	wheter or not to save
	 */
	public static function ensureTags ( $Event, $tagRelation, $tagField ) {
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
	
}