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
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: FormButton.php 20096 2010-01-06 02:05:09Z bkarwin $
 */


/**
 * Abstract class for extension
 */
require_once 'Zend/View/Helper/HtmlElement.php';


/**
 * Helper to generate a "button" element
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Bal_View_Helper_HtmlElement extends Zend_View_Helper_HtmlElement
{
    /**
     * Generates a Html Element
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are extracted in place of added parameters.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function HtmlElement($name, $content = null, $attribs = null, $open = true, $close = true)
    {
		# prepare
        $xhtml = '';
		
		# Prepare Attributes
		array_keys_ensure($attribs, array('class'), '');
		
		# open
		if ( $open )
			$xhtml .=
				'<'.$name.
				$this->_htmlAttribs($attribs);

       	# add content and end tag
		if ( $close )
			$xhtml .=
				is_null($content) ? ' />' : $content.'</'.$name.'>';
		
		# return
        return $xhtml;
    }

	public function htmlAttribs ( $attribs ) {
		return $this->_htmlAttribs($attribs);
	}
	
}
