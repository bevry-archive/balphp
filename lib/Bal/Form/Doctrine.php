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
			$Table = $Table;
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
	
	public static function generateForm ( $from, $Record = null ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$tableName = self::getTableName($from);
		$tableNameLower = strtolower($tableName);
		$Table = self::getTable($from);
		$Form = new Zend_Form_SubForm();
		$Form->setElementsBelongTo($tableNameLower);
		
		# Generate New Form
		$columns = $Table->getColumns();
		foreach ( $columns as $column => $properties ) {
			# Create Element
			$field = $column;
			$name = $field;
			$Element = new Bal_Form_Element_Doctrine($name); //$Form->createElement('doctrine', $name);
			$Element->setOptions(array('table'=>$tableName,'field'=>$field));
			
			# Prepare Attributes
			$label = $Locale->translate_default($tableNameLower.'-form-element-'.$field.'-title', array(), ucwords(str_replace('_', ' ',$field)));
			$description = $Locale->translate_default($tableNameLower.'-form-element-'.$field.'-description', array(), '');
			
			# Apply Attributes
			$Element->setName($name);
			$Element->setLabel($label);
			$Element->setDescription($description);
			if ( $Record ) {
				$value = delve($Record,$field);
				$Element->setValue($value);
			}
			
			# Auto
			$auto = real_value(delve($properties,'extra.auto')) || real_value(delve($properties,'autoincrement'));
			$Element->setIgnore($auto);
			$Element->setAttrib('readonly', $auto ? true : null);
			$Element->setAttrib('disabled', $auto ? true : null);
			
			# Required
			$notnull = real_value(delve($properties,'notnull'));
			$notblank = real_value(delve($properties,'notblank'));
			$required = $notnull || $notblank;
			$Element->setRequired($required);
			$Element->setAllowEmpty(!$notblank);
			$Element->setAutoInsertNotEmptyValidator(false);
			
			# Add Element
			$Form->addElement($Element);
		}
		
		# Relations
		$Relations = $Table->getRelations();
		foreach ( $Relations as $relationName => $Relation ) {
			# Create Element
			$field = $relationName;
			$name = $field;
			$Element = new Bal_Form_Element_Doctrine($name); //$Form->createElement('doctrine', $name);
			$Element->setOptions(array('table'=>$tableName,'field'=>$field));
			
			# Prepare Attributes
			$label = $Locale->translate_default($tableNameLower.'-form-element-'.$field.'-title', array(), ucwords(str_replace('_', ' ',$field)));
			$description = $Locale->translate_default($tableNameLower.'-form-element-'.$field.'-description', array(), '');
			
			# Apply Attributes
			$Element->setName($name);
			$Element->setLabel($label);
			$Element->setDescription($description);
			if ( $Record ) {
				$relations = delve($Record,$field);
				$value = array();
				if ( $relations ) foreach ( $relations as $relation ) {
					$value[] = $relation['id'];
				}
				$Element->setValue($value);
			}
			
			# Add Element
			$Form->addElement($Element);
		}
		
		# Return Form
		return $Form;
	}
	
	public static function fetchForm ( $from, $Record = null ) {
		# Prepare
		$tableName = self::getTableName($from);
		$Form = null;
		
		# Check to see if table has form
		if ( method_exists($tableName, 'fetchForm') )
			$Form = $tableName::fetchForm($Record);
		else
			$Form = self::generateForm($from, $Record);
		
		# Return Form
		return $Form;
	}
	
}
