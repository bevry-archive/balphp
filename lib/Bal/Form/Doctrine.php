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
	
	
	# ========================
	# NAMES
	
	/**
	 * Determine and return a form name based upon the $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table The table/type of the record
	 * @return string
	 */
	public static function getFormName ( $table ) {
		# Prepare
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		
		# Handle
		$formName = $tableComponentName;
		
		# Return
		return $tableComponentName;
	}
	
	/**
	 * Determine and return a element name based upon the $table $field
	 * @version 1.1, April 12, 2010
	 * @param mixed $table The table/type of the record
	 * @return string
	 */
	public static function getElementName ( $table, $field ) {
		# Fetch
		$elementName = Bal_Doctrine_Core::getFieldName($table,$field);
		
		# Return elementName
		return $elementName;
	}
	
	# ========================
	# ELEMENT
	
	/**
	 * Apply relation properties of $table $fieldName to $Element
	 * @version 1.1, April 12, 2010
	 * @param Zend_Form_Element &$Element - The form element to apply to
	 * @param mixed $table - The table/type to use
	 * @param mixed $field - The field to use
	 * @param mixed $Record [optional] - The record to use to set the current value of the element
	 * @return Zend_Form_Element
	 */
	public static function applyElementRelationProperties ( Zend_Form_Element &$Element, $table, $field, $Record = null ) {
		# Prepare
		$Table = Bal_Doctrine_Core::getTable($table);
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		$fieldName = Bal_Doctrine_Core::getFieldName($Table,$field);
		
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
	
	/**
	 * Apply field properties of $table $fieldName to $Element
	 * @version 1.1, April 12, 2010
	 * @param Zend_Form_Element &$Element - The form element to apply to
	 * @param mixed $table - The table/type to use
	 * @param mixed $field - The field to use
	 * @param mixed $Record [optional] - The record to use to set the current value of the element
	 * @return Zend_Form_Element
	 */
	public static function applyElementFieldProperties ( Zend_Form_Element &$Element, $table, $field, $Record = null ) {
		# Prepare
		$Table = Bal_Doctrine_Core::getTable($table);
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		$fieldName = Bal_Doctrine_Core::getFieldName($Table,$field);
		
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
	
	/**
	 * Apply element properties of $table $fieldName to $Element
	 * @version 1.1, April 12, 2010
	 * @param Zend_Form_Element &$Element - The form element to apply to
	 * @param mixed $table - The table/type to use
	 * @param mixed $field - The field to use
	 * @param mixed $Record [optional] - The record to use to set the current value of the element
	 * @return Zend_Form_Element
	 */
	public static function applyElementProperties ( Zend_Form_Element &$Element, $table, $field, $Record = null ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$Table = Bal_Doctrine_Core::getTable($table);
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		$tableComponentNameLower = strtolower($tableComponentName);
		$fieldName = Bal_Doctrine_Core::getFieldName($Table,$field);
	
		# Handle
		$hasRelation = $Table->hasRelation($fieldName);
		$hasField = $Table->hasField($fieldName);
		if ( $hasRelation || $hasField ) {
			# Prepare Names
			$fieldNameLower = strtolower($fieldName);
			
			# Prepare Attributes
			$label = $Locale->translate_default($tableComponentNameLower.'-'.$fieldNameLower.'-title', array(), ucwords(str_replace('_', ' ',$fieldName)));
			$description = $Locale->translate_default($tableComponentNameLower.'-'.$fieldNameLower.'-description', array(), '');
			
			# Apply Attributes
			$Element->setName($fieldName);
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
	
	/**
	 * Generate a element of $table $fieldName to $Element
	 * @version 1.1, April 12, 2010
	 * @param Zend_Form_Element &$Element - The form element to apply to
	 * @param mixed $table - The table/type to use
	 * @param mixed $field - The field to use
	 * @param mixed $Record [optional] - The record to use to set the current value of the element
	 * @param array $options [optional] - Options that can be applied to the element
	 * @return Zend_Form_Element
	 */
	public static function generateElement ( Zend_Form $Form, $table, $field, $Record = null, array $options = array() ) {
		# Prepare Element
		$elementName = self::getElementName($table, $field);
		$elementType = 'doctrine'; //delve($options,'type','doctrine');
		
		# Create Element
		$Element = $Form->createElement($elementType, $elementName, $options);
		if ( $elementType === 'doctrine' ) {
			$Element->setTableAndField($table,$field,$Record);
		} else {
			self::applyElementProperties($Element,$table,$field,$Record);
		}
		
		# Options
		$Element->setOptions($options);
		
		# Done
		return $Element;
	}
	
	# ========================
	# FORM
	
	/**
	 * Create the skeleton form for the $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table - The table/type to use
	 * @return Zend_Form
	 */
	public static function createForm ( $table ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		$tableComponentNameLower = strtolower($tableComponentName);
		$Form = new Zend_Form();
		$Form->setElementsBelongTo($tableComponentName);
		$formName = self::getFormName($table);
		
		# Path
		$Form->addPrefixPath('Bal_Form', 'Bal/Form');
		
		# Apply Labels
		$formLabel = $Locale->translate_default($tableComponentNameLower.'-form-title', array(), ucwords($formName));
		$formDescription = $Locale->translate_default($tableComponentNameLower.'-form-description', array(), false);
		if ( $formLabel ) $Form->setLegend($formLabel);
		if ( is_string($formDescription) ) $Form->setDescription($formDescription);
		
		# Return Form
		return $Form;
	}
	
	/**
	 * Generate a complete form for the $table
	 * @version 1.1, April 12, 2010
	 * @param mixed $table - The table/type to use
	 * @param mixed $Record [optional] - The record to use to set the current values of the elements
	 * @return Zend_Form
	 * @see self::createForm
	 */
	public static function generateForm ( $table, $Record = null ) {
		# Prepare
		$Form = self::createForm($table);
		$Table = Bal_Doctrine_Core::getTable($table);
		
		# Fetch Field
		$columns = $Table->getColumns();
		$Relations = $Table->getRelations();
		
		# Cycle through Relations to Remove Local Fields
		foreach ( $Relations as $Relation ) {
			# Prepare
			$relationFieldName = $Relation->getLocalFieldName();
			$relationOwner = $Table->hasField($relationFieldName);
			$relationRefTable = $Relation->offsetGet('refTable');
			
			# Check
			if ( $relationOwner ) {
				# Remove Local Column in favour of Relation Field
				unset($columns[$relationFieldName]);
			}
			if ( $relationRefTable ) {
				# Remove Ref Table in favour of Relation Field
				unset($Relations[$relationRefTable->getComponentName()]);
			}
		}
		
		# Add Fields as Elements
		$fields = array_merge($columns,$Relations);
		foreach ( $fields as $columnName => $properties ) {
			# Create Element
			$fieldName = $Table->getFieldName($columnName);
			$Element = self::generateElement($Form,$table,$fieldName,$Record);
			$Form->addElement($Element);
		}
		
		# Return Form
		return $Form;
	}
	
	/**
	 * Add the ID field element to the $Form of $table
	 * @version 1.1, April 12, 2010
	 * @param Zend_Form &$Form - The Form to add the element to
	 * @param mixed $table - The table/type to use
	 * @param mixed $Record [optional] - The record to use to set the current values of the elements
	 * @return Zend_Form_Element
	 */
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
	
	/**
	 * Add the field elements to the $Form of $table
	 * @version 1.1, April 12, 2010
	 * @param Zend_Form &$Form - The Form to add the element to
	 * @param mixed $table - The table/type to use
	 * @param mixed $Record [optional] - The record to use to set the current values of the elements
	 * @return Zend_Form
	 */
	public static function addElements ( Zend_Form &$Form, $table, $elements, $Record = null ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		$Table = Bal_Doctrine_Core::getTable($table);
		
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
				$groupLabel = $Locale->translate_default($tableComponentName.'-form-group-'.$groupName.'-title', array(), ucwords($groupName));
				$groupDescription = $Locale->translate_default($tableComponentName.'-form-group-'.$groupName.'-description', array(), '');
				if ( $groupLabel ) $groupAttribs['legend'] = $groupLabel;
				if ( $groupDescription ) $groupAttribs['description'] = $groupDescription;
				
				# Create Group, Add to Form
				$Form->addDisplayGroup($groupFields,$groupName,$groupAttribs);
			}
		}
		
		# Return Form
		return $Form;
	}
	
	/**
	 * Fetch a custom or fallback and generate a standard form for $table. Using $Record for current values
	 * @version 1.1, April 12, 2010
	 * @param mixed $table - The table/type to use
	 * @param mixed $Record [optional] - The record to use to set the current values of the elements
	 * @return Zend_Form
	 */
	public static function fetchForm ( $table, $Record = null ) {
		# Prepare
		$tableComponentName = Bal_Doctrine_Core::getTableComponentName($table);
		$Form = null;
		
		# Check to see if table has form
		if ( method_exists($tableComponentName, 'fetchForm') ) {
			$Form = call_user_func_array($tableComponentName.'::fetchForm', array($Record));
			// in call_user_func_array to prevent issue on older php version, rather than just doing $tableComponentName::fetchForm
		} else {
			$Form = self::generateForm($table, $Record);
		}
		
		# Return Form
		return $Form;
	}
	
	
}
