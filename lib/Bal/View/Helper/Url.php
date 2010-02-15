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
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Url.php 20096 2010-01-06 02:05:09Z bkarwin $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Url.php';

/**
 * Helper for making easy links and getting urls that depend on the routes and router
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Bal_View_Helper_Url extends Zend_View_Helper_Url
{
	
	# ========================
	# VARIABLES
	
	protected $_default_route = 'default';
	
	protected $_params = array();
	protected $_route  = null;
	protected $_reset  = true;
	protected $_encode = true;
	protected $_url    = null;
	
	
	# ========================
	# CONSTRUCTORS
	
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
     * Generates an url given the name of a route.
     *
     * @access public
     *
     * @param  array $urlOptions Options passed to the assemble method of the Route object.
     * @param  mixed $name The name of a Route to use. If null it will use the current Route
     * @param  bool $reset Whether or not to reset the route defaults with those provided
     * @return string Url for the link href attribute.
     */
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true) {
		# Prepare
		$result = false;
		
		# Clear Information
		$this->clear();
		
		# Handle Request
		if ( empty($urlOptions) && empty($name) ) {
			$result = $this;
		}
		else {
			$result = $this->apply($urlOptions,$name,$reset,$encode)->toString();
		}
		
		# Return result
		return $result;
    }

	public function clear ( ) {
		$this->_params = array();
		$this->_route  = null;
		$this->_reset  = true;
		$this->_encode = true;
		$this->_url    = null;
		return $this;
	}
	
	public function apply ( $params, $route, $reset, $encode ) {
		return $this->params($params)->route($route)->reset($reset)->encode($encode);
	}
	
	public function params ( array $params ) {
		foreach ( $params as $key => $value ) {
			$this->param($key,$value);
		}
		return $this;
	}
	
	public function param ( $name, $value ) {
		$this->_params[$name] = $value;
		return $this;
	}

	public function page ( $value ) {
		return $this->param('page',$value);
	}
	
	public function action ( $value ) {
		return $this->param('action',$value);
	}
	
	public function renege ( $what, $value ) {
		$var = '_default_'.$what;
		$this->$var = $value;
		return $this;
	}
	
	public function route ( $value ) {
		$this->_route = $value;
		return $this;
	}
	
	public function reset ( $value ) {
		$this->_reset = $value;
		return $this;
	}
	
	public function encode ( $value ) {
		$this->_encode = $value;
		return $this;
	}
	
	public function hard ( $value ) {
		$this->_url = $value;
		return $this;
	}
	
	public function map ( $map ) {
		$Route = delve($map,'Route');
		if ( !$Route )
			if ( array_key_exists('path', $map) ) {
				$Route = $map;
		}
		return $this->route('map')->param('Map',$Route);
	}
	
	public function search ( $query = null, $code = null ) {
		# Apply
		if ( $query ) {
			$this->param('query',$query);
		}
		else {
			if ( !$code ) $code = $this->view->app()->generateSearchCode();
			$this->param('code',$code);
		}
		
		# Chain
		return $this;
	}
	
	public function item ( $item ) {
		# Ensure Item
		$id = $code = null;
		if ( is_numeric($item) ) {
			$id = $item;
		}
		elseif ( is_string($item) ) {
			$code = $item;
		}
		elseif ( is_object($item) || is_array($item) ) {
			$code = delve($item,'code');
			$id = delve($item,'id');
		}
		
		# Apply Item
		if ( $code ) {
			$this->param('code',$code);
		}
		elseif ( $id ) {
			$this->param('id',$id);
		}
		else {
			$result = false;
		}
		
		# Chain
		return $this;
	}
	
	public function assemble ( ) {
		# Prepare
		$url = false;
		
		# Handle
		if ( !empty($this->_url) )
			$url = $this->_url;
		else {
			$Router = $this->_getRouter();
			$params = $this->_params;
			$route = $this->_route ? $this->_route : $this->_default_route;
			$reset = $this->_reset;
			$encode = $this->_encode;
        	$url = $Router->assemble($params, $route, $reset, $encode);
		}
		
		# Return
		return $url;
	}
	
	protected function _getRouter ( ) {
		return Zend_Controller_Front::getInstance()->getRouter();
	}
	
	public function toString ( ) {
		return $this->__toString();
	}
	
	public function __toString ( ) {
		return $this->assemble();
	}
	
	
	public function content ( $Item ) {
		return $this->map($Item);
	}
	
	public function media ( $Item ) {
		return $this->hard(delve($Item,'url'));
	}
	
	public function user ( $Item ) {
		return $this->route('default')->action('user')->item($Item);
	}
	
	public function userActivate ( $Item ) {
		return $this->route('default')->action('user-activate')->item($Item);
	}
	
	public function message ( $Item ) {
		return $this->route('default')->action('message')->item($Item);
	}
	
}
