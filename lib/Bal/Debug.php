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
 * @package    Zend_Debug
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Debug.php 16971 2009-07-22 18:05:45Z mikaelkael $
 */

/**
 * Concrete class for generating debug dumps related to the output source.
 *
 * @category   Zend
 * @package    Zend_Debug
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

class Bal_Debug extends Zend_Debug
{

    /**
     * @var string
     */
    protected static $_sapi = null;

    /**
     * Get the current value of the debug output environment.
     * This defaults to the value of PHP_SAPI.
     *
     * @return string;
     */
    public static function getSapi()
    {
        if (self::$_sapi === null) {
            self::$_sapi = PHP_SAPI;
        }
        return self::$_sapi;
    }

    /**
     * Set the debug ouput environment.
     * Setting a value of null causes Zend_Debug to use PHP_SAPI.
     *
     * @param string $sapi
     * @return void;
     */
    public static function setSapi($sapi)
    {
        self::$_sapi = $sapi;
    }

    /**
     * Debug helper function.  This is a wrapper for var_dump() that adds
     * the <pre /> tags, cleans up newlines and indents, and runs
     * htmlentities() before output.
     *
     * @param  mixed  $var   The variable to dump.
     * @param  string $label OPTIONAL Label to prepend to output.
     * @param  bool   $echo  OPTIONAL Echo output if true.
     * @return string
     */
    public static function dump($var, $label=null, $echo=true) {
    	require_once 'dBug.php';
		new dBug($var);
    }
    
    public static $options = array(
    	'debugStart'	=> '<div class="debug">',
    	'debugEnd'		=> '</div>',
    	
    	'nameStart'		=> '<span class="name">',
    	'nameEnd'		=> ':</span>',
    	'valueStart'	=> '<span class="value">',
    	'valueEnd'		=> '</span>',
    	'typeStart'		=> '<span class="type">',
    	'typeEnd'		=> '</span>',
    
    	'booleanStart'	=> '<span class="var boolean">',
    	'booleanEnd'	=> '</span>',
    	'integerStart'	=> '<span class="var integer">',
    	'integerEnd'	=> '</span>',
    	'doubleStart'	=> '<span class="var double">',
    	'doubleEnd'		=> '</span>',
    	'stringStart'	=> '<span class="var string">',
    	'stringEnd'		=> '</span>',
    	'nullStart'		=> '<span class="var null">',
    	'nullEnd'		=> '</span>',
    	'resourceStart'	=> '<span class="var resource">',
    	'resourceEnd'	=> '</span>',
    	'arrayStart'	=> '<span class="var array">',
    	'arrayEnd'		=> '</span>',
    	'objectStart'	=> '<span class="var object">',
    	'objectEnd'		=> '</span>',
    	'methodStart'	=> '<span class="var method">',
    	'methodEnd'		=> '</span>',
    
    	'helpStart'		=> '<span class="help">',
    	'helpEnd'		=> '</span>'
    );
    
    public static function render($value, $name=null, $level=0) {
    	$type = strtolower(gettype($value));
    	switch ( $type ) {
    		case 'string':
    			if ( strpos($value,"\n") || strpos($value,"\t") ) {
    				$value = self::escape($value,false,true);
    				$value = '<pre>'.$value.'</pre>';
    				$return = self::renderVariable($value,$name,$type,false);
    			} else {
    				$return = self::renderVariable($value,$name);
    			}
    			break;
    			
    		case 'boolean':
			case 'integer':
    		case 'double':
    		case 'null':
    			$return = self::renderVariable($value,$name);
    			break;
    			
    		case 'resource':
    			$return = self::renderVariable(substr($value,0,5).'...',$name);
    			break;
    		
    		case 'array':
    			$return = '';
    			foreach ( $value as $key => $val ) {
    				$return .= self::render($val,$key,$level+1);
    			}
    			$return = self::renderVariable($return,$name,$type,false);
    			break;
    			
    		case 'object':
    			$class = get_class($value);
    			$parents = self::parents($class, true);
    			$type .= ' < '.implode(' < ', $parents);
    			//
    			$return = '';
    			$vars = get_object_vars($value);
    			foreach ( $vars as $key => $val ) {
    				$return .= self::render($val,$key,$level+1);
    			}
    			$methods = get_class_methods($value);
    			foreach ( $methods as $key => $val ) {
    				$return .= self::renderVariable($val,$key,'method',false);
    			}
    			//
    			$return = self::renderVariable($return,$name,$type,false);
    			break;
    	}
    	return !$level ? self::$options['debugStart'].$return.self::$options['debugEnd'] : $return;
    }
    
    public static function renderVariable ( $value, $name=null, $type=null, $escape=true ) {
    	if ( !$type ) {
    		$type = strtolower(gettype($value));
    	}
    	$parts = explode(' < ', $type);
    	$classtype = $rawtype = $parts[0];
    	if ( !empty($parts[1]) ) {
    		$classtype = $parts[1];
    	}
    	return
    		self::$options[$rawtype.'Start'].
		    	(	$name===null ? '' :
		    		self::$options['nameStart'].
		    			self::escape($name,false,true).
		    		self::$options['nameEnd']
		    	).
		    	self::$options['typeStart'].
		    		self::escape($type,false,true).
		    	self::$options['typeEnd'].
    			self::$options['helpStart'].
    				self::getHelpLinks($value,$classtype).
    			self::$options['helpEnd'].
		    	self::$options['valueStart'].
		    		($escape?self::escape($value,true,true):$value).
		    	self::$options['valueEnd'].
    		self::$options[$rawtype.'End']
		;
    }
    
    public static function escape($value, $type=true, $html=true){
    	if ( $type ) {
    		$value = var_export($value,true);
    	}
    	if ( $html ) {
    		$value = htmlentities($value);
    	}
    	return $value;
    }
    
    public static function parents($class, $self=true){
    	$parents = array();
    	if ( $self ) $parents[] = $class;
    	$_class = $class; while ( $_class = get_parent_class($_class) ) {
    		$parents[] = $_class;
    	}
    	return $parents;
    }
    
    public static function getHelpLinks ( $value, $type=null ) {
    	$help = self::getHelpArray($value, $type);
    	$result = '';
    	foreach ( $help as $name => $url ) {
    		$result .= '<a href="'.$url.'" title="Documentation for: '.$name.'" class="help-link">'.$name.'</a>';
    	}
    	return $result;
    }
    
    public static function getHelpArray ( $value, $type=null ) {
    	# Prepare
    	$has = self::parents($type, true);
    	$has[] = $type;
    	$has[] = gettype($value);
    	array_clean($has);
    	
    	# Search
    	$has = array_flip($has);
    	$help = array_intersect_key(self::$_helps, $has);
    	
    	# Done
    	return $help;
    }
    
    public static $_helps = array(
    	'Zend_View' => 'http://framework.zend.com/manual/en/zend.view.html',
    	'Zend_View_Helper_Headlink' => 'http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.headlink',
    	'Zend_View_Helper_Abstract' => 'http://framework.zend.com/manual/en/zend.view.helpers.html',
    	'Zend_Navigation' => 'http://framework.zend.com/manual/en/zend.navigation.html',
    	'Doctrine_Record' => 'http://www.doctrine-project.org/documentation/manual/1_2/en/component-overview#record',
    	'Doctrine_Record_Abstract' => 'http://www.doctrine-project.org/Doctrine_Record/1_2'
    );
    
}
