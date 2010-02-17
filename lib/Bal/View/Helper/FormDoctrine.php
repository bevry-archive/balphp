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
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: FormText.php 18951 2009-11-12 16:26:19Z alexander $
 */


/**
 * Abstract class for extension
 */
require_once 'Zend/View/Helper/FormElement.php';


/**
 * Helper to generate a "text" element
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_FormDoctrine extends Zend_View_Helper_FormElement
{
	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	public $view;
	
	/**
	 * Apply View
	 * @param Zend_View_Interface $view
	 */
	public function setView (Zend_View_Interface $view) {
		# Set
		$this->view = $view;
		
		# Chain
		return $this;
	}
	
    /**
     * Generates a 'text' element.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are used in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function formDoctrine($name, $value = null, $attribs = null, $table = null, $field = null) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$result = '';
		
		# Fetch Info
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable
		
		# Prepare Attributes
		array_keys_ensure($attribs, array('table','field','class'));
		if ( !$table ) $table = $attribs['table'];
		if ( !$field ) $field = $attribs['field'];
		
		# Fetch Table Information
		$Table = Bal_Form_Doctrine::getTable($table);
		$properties = array();
		if ( $Table->hasRelation($field) ) {
			$Relation = $Table->getRelation($field);
			$RelationTable = $Relation->getTable();
		}
		elseif ( $Table->hasField($field) ) {
			$properties = $Table->getDefinitionOf($field);
			array_keys_ensure($properties, array('length'), null);
			$length = $properties['length'];
		}
		
		# Determine Type
		if ( delve($attribs,'type') ) {
			# Overide type
			$type = delve($attribs,'type');
		}
		elseif ( $Table->hasRelation($field) ) {
			# Relation
			$type = 'relation';
		}
		elseif ( $Table->hasField($field) ) {
			# Column
			$type = $Table->getTypeOf($field);
		
			# Custom Types
			switch ( true ) {
				case real_value(delve($properties,'extra.password')):
					$type = 'password';
					$value = null;
					break;
				
				
				case real_value(delve($properties,'extra.csv')):
					$type = 'csv';
					$value = null;
					break;
				
				default:
					break;
			}
		}
		else {
			# Unkown
		}
		
		# Formify
		$tableLower = strtolower($table);
		$fieldLower = strtolower($field);
		
		# Discover
		switch ( $type ) {
			case 'relation':
				# Determine
				$text_column = null;
				switch ( true ) {
					case $RelationTable->hasField('title'):
						$text_column = 'title';
						break;
					case $RelationTable->hasField('name'):
						$text_column = 'name';
						break;
					case $RelationTable->hasField('code'):
						$text_column = 'code';
						break;
					case $RelationTable->hasField('displayname'):
						$text_column = 'displayname';
						break;
					case $RelationTable->hasField('fullname'):
						$text_column = 'fullname';
						break;
					default:
						$text_column = 'id';
						
				}
				
				# Fetch
				try {
					$relations = $RelationTable->createQuery()->select('id, '.$text_column.' as text')->setHydrationMode(Doctrine::HYDRATE_ARRAY)->execute();
				}
				catch ( Exception $Exception ) {
					$relations = array();
					$Relations = delve($Table,$field);
					if ( $Relations ) foreach ( $Relations as $relation ) {
						$relations[] = array('id'=>$relation['id'],'text'=>$relation[$text_column]);
					}
				}
				
				# Options
				$options = array();
				foreach ( $relations as $relation ) {
					$options[$relation['id']] = $relation['text'];
				}
				
				# Display
				if ( empty($options) ) {
					$result .= '<span class="form-empty">'.$Locale->translate('none').'</span>';
				}
				else {
					if ( count($options) === 1 ) {
						$attribs['disabled'] = $attribs['readonly'] = true;
					}
					elseif ( $Relation->getType() === Doctrine_Relation::MANY ) {
						$attribs['multiple'] = true;
					}
					$result .= $this->view->formSelect($name, $value, $attribs, $options);
				}
				break;
				
			case 'enum':
				$options = $Table->getEnumValues($field);
				$options = array_flip($options);
				foreach ( $options as $enum => &$text ) {
					$text = $Locale->translate_default($tableLower.'-'.$fieldLower.'-'.$enum, array(), ucfirst($enum));
				}
				if ( count($options) === 1 ) {
					$attribs['disabled'] = $attribs['readonly'] = true;
				}
				$result .= $this->view->formSelect($name, $value, $attribs, $options);
				break;
			
			case 'bool':
			case 'boolean':
				$result .= $this->view->formBoolean($name, $value, $attribs);
				break;
			
			case 'timestamp':
			case 'datetime':
				$result .= $this->view->formDatetime($name, $value, $attribs);
				break;
				
			case 'date':
				$result .= $this->view->formDate($name, $value, $attribs);
				break;
				
			case 'time':
				$result .= $this->view->formTime($name, $value, $attribs);
				break;
			
			case 'currency':
				$result .= $this->view->formCurrency($name, $value, $attribs);
				break;
				
			case 'integer':
			case 'decimal':
			case 'float':
				$result .= $this->view->formNumber($name, $value, $attribs);
				break;
				
			case 'password':
				if ( $length && $length <= 255 ) {
					$attribs['maxlength'] = $length;
				}
				$attribs['autocomplete'] = 'off';
				$attribs['class'] .= ' sparkle-password';
				$result .= $this->view->formPassword($name, $value, $attribs);
				break;
			
			case 'text':
			case 'string':
				if ( $length && $length <= 255 ) {
					$attribs['maxlength'] = $length;
					$result .= $this->view->formText($name, $value, $attribs);
					break;
				}
			case 'textarea':
				$attribs['class'] .= ' autogrow';
				$result .= $this->view->formTextarea($name, $value, $attribs);
				break;
			
			case 'csv':
				$result .= $this->view->formCsv($name, $value, $attribs);
				break;
				
			case 'hidden':
				$result .= $this->view->formHidden($name, $value, $attribs);
				break;
				
			default:
				throw new Zend_Exception('error-unkown_input_type-'.$type);
				break;
		}
		
		# Done
		return $result;
    }
}
