<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Form
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** @see Zend_Validate_Interface */
require_once 'Zend/Form.php';

/**
 * Zend_Form
 *
 * @category   Zend
 * @package    Zend_Form
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Form.php 20096 2010-01-06 02:05:09Z bkarwin $
 */
class Bal_Form_Doctrine
{
	
	public static function getTable ( $table ) {
		# Prepare
		$Table = null;
		
		# Fetch
		if ( is_object($table) && $table instanceOf Doctrine_Table ) 
			$Table = $table;
		else {
			$tableName = self::getTableName($table);
			$Table = Doctrine::getTable($table);
		}
		
		# Return Table
		return $Table;
	}
	
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
	
	public static function getFormName ( $table ) {
		# Prepare
		$tableName = self::getTableName($table);
		
		# Handle
		$formName = $tableName;
		
		# Return
		return $tableName;
	}
	
	public static function getElementName ( $table, $fieldName ) {
		# Prepare
		
		# Handle
		$elementName = $fieldName;
		
		# Return
		return $elementName;
	}
	
	public static function getGroupName ( $table, $group ) {
		# Prepare
		
		# Handle
		$groupName = $group;
		
		# Return
		return $groupName;
	}
	
	public static function applyElementRelationProperties ( Zend_Form_Element &$Element, $table, $fieldName, $Record = null ) {
		# Prepare
		$Table = Bal_Form_Doctrine::getTable($table);
		$tableName = Bal_Form_Doctrine::getTableName($table);
		
		# Prepare
		$Relation = $Table->getRelation($fieldName);
		$relation = $Relation->toArray();
		
		# Apply Value
		if ( $Record ) {
			$relations = delve($Record,$fieldName);
			if ( $Relation->getType() === Doctrine_Relation::ONE ) {
				$relations = array($relations);
			}
			$value = array();
			if ( $relations ) foreach ( $relations as $_relation ) {
				$value[] = $_relation['id'];
			}
			$Element->setValue($value);
		}
		
		# Handle
		$relationFieldName = $Relation->getLocalFieldName();
		$relationOwner = $Table->hasField($relationFieldName); // $Relation->offsetGet('owningSide');
		if ( $relationOwner ) {
			# Editable, With Restraints
			self::applyElementFieldProperties($Element, $table, $relationFieldName, $Record);
			$Element->setAttrib('relationStatus', 'editable');
		}
		else {
			if ( $Relation->offsetGet('refTable') ) {
				# Linkable, No Restraints
				$Element->setAttrib('relationStatus', 'linkable');
			}
			else {
				# Disabled, Not Owning Side
				$Element->setAttrib('readonly', true);
				$Element->setAttrib('disabled', true);
				$Element->setAttrib('relationStatus', 'disabled');
			}
		}
		
		# Retuen Element
		return $Element;
	}
	
	public static function applyElementFieldProperties ( Zend_Form_Element &$Element, $table, $fieldName, $Record = null ) {
		# Prepare
		$Table = Bal_Form_Doctrine::getTable($table);
		$tableName = Bal_Form_Doctrine::getTableName($table);
		
		# Prepare
		$properties = $Table->getDefinitionOf($fieldName);
		
		# Apply Value
		if ( $Record ) {
			$value = delve($Record,$fieldName);
			$Element->setValue($value);
		}
		
		# Auto
		$auto = real_value(delve($properties,'extra.auto')) || real_value(delve($properties,'autoincrement'));
		$Element->setIgnore($auto);
		$Element->setAttrib('readonly', $auto ? true : null);
		$Element->setAttrib('disabled', $auto ? true : null);
		
		# Required
		$notblank = real_value(delve($properties,'notblank',false));
		$notnull = real_value(delve($properties,'notnull',$notblank));
		$Element->setRequired($notblank);
		$Element->setAllowEmpty(!$notnull && !$notblank);
		$Element->setAutoInsertNotEmptyValidator(false);
		
		# Attribs
		$Element->setAttrib('auto', $auto);
		$Element->setAttrib('notnull', $notnull);
		$Element->setAttrib('notblank', $notblank);
		
		# Return Element
		return $Element;
	}
	
	public static function applyElementProperties ( Zend_Form_Element &$Element, $table, $fieldName, $Record = null ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$tableName = Bal_Form_Doctrine::getTableName($table);
		$tableNameLower = strtolower($tableName);
		$Table = Bal_Form_Doctrine::getTable($table);
	
		# Handle
		$hasRelation = $Table->hasRelation($fieldName);
		$hasField = $Table->hasField($fieldName);
		if ( $hasRelation || $hasField ) {
			# Prepare Names
			$fieldNameLower = strtolower($fieldName);
			$name = $fieldName;
			
			# Prepare Attributes
			$label = $Locale->translate_default($tableNameLower.'-'.$fieldNameLower.'-title', array(), ucwords(str_replace('_', ' ',$fieldName)));
			$description = $Locale->translate_default($tableNameLower.'-'.$fieldNameLower.'-description', array(), '');
			
			# Apply Attributes
			$Element->setName($name);
			$Element->setLabel($label);
			$Element->setDescription($description);
			
			# Handle
			if ( $hasRelation ) {
				self::applyElementRelationProperties($Element, $table, $fieldName, $Record);
			}
			elseif ( $hasField ) {
				self::applyElementFieldProperties($Element, $table, $fieldName, $Record);
			}
			
		}
		
		# Return Element
		return $Element;
	}
	
	public static function generateElement ( Zend_Form $Form, $table, $fieldName, $Record = null, array $options = array() ) {
		# Prepare Element
		$elementName = self::getElementName($table, $fieldName);
		$elementType = 'doctrine'; //delve($options,'type','doctrine');
		
		# Create Element
		$Element = $Form->createElement($elementType, $elementName, $options);
		if ( $elementType === 'doctrine' ) {
			$Element->setTableAndField($table,$fieldName,$Record);
		} else {
			self::applyElementProperties($Element,$table,$fieldName,$Record);
		}
		
		# Options
		$Element->setOptions($options);
		
		# Done
		return $Element;
	}
	
	public static function createForm ( $table ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$tableName = self::getTableName($table);
		$tableNameLower = strtolower($tableName);
		$Form = new Zend_Form();
		$Form->setElementsBelongTo($tableNameLower);
		$formName = self::getFormName($table);
		
		# Path
		$Form->addPrefixPath('Bal_Form', 'Bal/Form');
		
		# Apply Labels
		$formLabel = $Locale->translate_default($tableNameLower.'-form-title', array(), ucwords($formName));
		$formDescription = $Locale->translate_default($tableNameLower.'-form-description', array(), false);
		if ( $formLabel ) $Form->setLegend($formLabel);
		if ( is_string($formDescription) ) $Form->setDescription($formDescription);
		
		# Return Form
		return $Form;
	}
	
	public static function generateForm ( $table, $Record = null ) {
		# Prepare
		$Form = self::createForm($table);
		$Table = self::getTable($table);
		
		# Add Elements
		$columns = $Table->getColumns();
		$Relations = $Table->getRelations();
		$fields = array_merge($columns,$Relations);
		foreach ( $fields as $fieldName => $properties ) {
			# Create Element
			$Element = self::generateElement($Form,$table,$fieldName,$Record);
			$Form->addElement($Element);
		}
		
		# Return Form
		return $Form;
	}
	
	public static function addIdElement ( Zend_Form &$Form, $table, $Record = null ) {
		# Id Value
		$idValue = delve($Record,'id');
		if ( !$idValue ) {
			# Having this as an empty causing problems on save
			return false;
		}
		
		# Create
		$Element = $Form->createElement('hidden', 'id',
			array('label'=>'','disableLoadDefaultDecorators'=>true,'decorators'=>array('ViewHelper'))
		);
		
		# Value
		$Element->setValue($idValue);
		
		# Add
		$Form->addElement($Element);
		
		# Return Element
		return $Element;
	}
	
	public static function addElements ( Zend_Form &$Form, $table, $elements, $Record = null ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$tableName = self::getTableName($table);
		$Table = self::getTable($table);
		
		# Cycle through Elements
		if ( is_simple_array($elements) ) {
			# No Grouping
			foreach ( $elements as $fieldName ) {
				$Element = self::generateElement($Form,$table, $fieldName, $Record);
				$Elements[] = $Element;
			}
		}
		else {
			# Display Grouping
			foreach ( $elements as $groupName => $groupElements ) {
				# Elements
				$groupFields = array();
				$Elements = array();
				foreach ( $groupElements as $value ) {
					# Prepare
					$options = array();
					if ( is_array($value) ) {
						$fieldName = delve($value,'name');
						$options = $value;
					}
					else {
						$fieldName = $value;
					}
					
					# Create
					$Element = self::generateElement($Form, $table, $fieldName, $Record, $options);
					
					# Add
					$Elements[] = $Element;
					$groupFields[] = self::getElementName($table, $fieldName);
				}
				
				# Add Elements to Form
				$Form->addElements($Elements);
				
				# Prepare Labels
				$groupAttribs = array();
				$groupLabel = $Locale->translate_default($tableName.'-form-group-'.$groupName.'-title', array(), ucwords($groupName));
				$groupDescription = $Locale->translate_default($tableName.'-form-group-'.$groupName.'-description', array(), '');
				if ( $groupLabel ) $groupAttribs['legend'] = $groupLabel;
				if ( $groupDescription ) $groupAttribs['description'] = $groupDescription;
				
				# Create Group, Add to Form
				$Form->addDisplayGroup($groupFields,$groupName,$groupAttribs);
			}
		}
		
		# Return Form
		return $Form;
	}
	
	public static function fetchForm ( $table, $Record = null ) {
		# Prepare
		$tableName = self::getTableName($table);
		$Form = null;
		
		# Check to see if table has form
		if ( method_exists($tableName, 'fetchForm') )
			$Form = $tableName::fetchForm($Record);
		else
			$Form = self::generateForm($table, $Record);
		
		# Return Form
		return $Form;
	}
	
	public static function getLabelColumnNames ( ) {
		return array('label','displayname','fullname','username','name','title','code','id');
	}
	
	public static function getRecordLabel ( $Record ) {
		# Prepare
		$label = null;
		$labelColumnNames = self::getLabelColumnNames();
		
		# Handle
		foreach ( $labelColumnNames as $labelColumnName ) {
			$value = delve($Record,$labelColumnName);
			if ( $value ) {
				$label = $value;
				break;
			}
		}
		
		# Return label
		return $label;
	}
	
	public static function getTableLabelColumnName ( $table ) {
		# Prepare
		$Table = self::getTable($table);
		$titleColumnName = null;
		$labelColumnNames = self::getLabelColumnNames();
		
		# Handle
		foreach ( $labelColumnNames as $labelColumnName ) {
			if ( $Table->hasField($labelColumnName) ) {
				$titleColumnName = $labelColumnName;
				break;
			}
		}
		
		# Return titleColumn
		return $titleColumnName;
	}
	
}
