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
    public function formDoctrine($name, $value = null, $attribs = null, $table, $field) {
		# Prepare
		$result = '';
		
		# Prepare Attributes
		if ( !is_array($attribs) ) $attribs = empty($attribs) ? array() : array($attribs);
		array_keys_ensure($attribs, array('class'), '');
		
		# Fetch Table Information
		$Table = Doctrine::getTable($table);
		if ( $Table->hasRelation($field) ) {
			# Relation
			$type = 'relation';
			$Relation = $Table->getRelation($field);
			$RelationTable = $Relation->getTable();
		} else {
			# Column
			$type = $Table->getTypeOf($field);
			$properties = $Table->getDefinitionOf($field);
			array_keys_ensure($properties, array('length'), null);
			$length = $properties['length'];
		}
		
		# Formify
		$table = strtolower($table);
		$field = strtolower($field);
		
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
					case $RelationTable->hasField('fullname'):
						$text_column = 'fullname';
						break;
					default:
						$text_column = 'id';
						
				}
				# Fetch
				$relations = $RelationTable->createQuery()->select('id, '.$text_column.' as text')->setHydrationMode(Doctrine::HYDRATE_ARRAY)->execute();
				$options = array();
				foreach ( $relations as $relation ) {
					$options[$relation['id']] = $relation['text'];
				}
				# Display
				$this->formSelect($name, $value, $attribs, $options);
				break;
				
			case 'enum':
				$options = $Table->getEnumValues($field);
				$options = array_flip($options);
				foreach ( $options as $enum => &$text ) {
					$text = $this->view->locale()->translate_default($table.'-'.$field.'-'.$enum, array(), $enum);
				}
				$result .= $this->view->formSelect($name, $value, $attribs, $options);
				break;
			
			case 'bool':
			case 'boolean':
				$result .= $this->view->formBoolean($name, $value, $attribs);
				break;
			
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
			
			case 'text':
			case 'string':
				if ( $length <= 255 ):
					$_attribs = $attribs; $_attribs['maxlength'] = $length;
					$result .= $this->view->formText($name, $value, $_attribs);
					break;
				endif;
			case 'textarea':
				$result .= $this->view->formTextarea($name, $value, $attribs);
				break;
			
			default:
				throw new Zend_Exception('error-unkown_input_type');
				break;
		}
		
		# Done
		return $result;
    }
}
