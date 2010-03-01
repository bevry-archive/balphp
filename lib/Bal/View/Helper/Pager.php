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
require_once 'Zend/View/Helper/Abstract.php';


/**
 * Helper to generate a "text" element
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Pager extends Bal_View_Helper_Abstract {
	
	protected $_options = array();
	
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
		# Apply
		$this->view = $view;
		
		# Done
		return true;
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
    public function Pager ( array $options ) {
		# Apply
		$this->setOptions($options);
		
		# Chain
		return $this;
    }

	public function setOptions ( array $options ) {
		# Prepare
		$keys = array('first','last','current','pages','items','chunk','start','finish','total','count');
		array_keys_keep_ensure($options, $keys, null);
		
		# Apply
		$this->_options = $options;
		
		# Chain
		return $this;
	}
	
	public function renderPages ( ) {
		# Prepare
		$options = $this->_options;
		extract($options);
		$result = '';
		
		# Render
		ob_start();
		?><ul class="pages">
			<? foreach( $pages as $page ): ?>
				<? if ( is_string($page['title']) ) $page['title'] = $this->view->locale()->translate($page['title']); ?>
				<? if ( $page['disabled'] ): ?>
					<li class="page disabled">
						<span><?=$page['title']?></span>
					</li>
				<? elseif ( $page['selected'] ): ?>
					<li class="page selected">
						<span><?=$page['title']?></span>
					</li>
				<? else: ?>
					<li class="page normal">
						<a href="<?=$this->view->url(array('page'=>$page['number']))?>" class="ajaxy ajaxy__page">
							<?=$page['title']?>
						</a>
					</li>
				<? endif; ?>
			<? endforeach; ?>
		</ul><?
		$result = ob_get_contents();
		ob_end_clean();
		
		# Done
		return $result;
	}
	
	public function renderTotals ( $wrap = true ) {
		# Prepare
		$options = $this->_options;
		extract($options);
		$result = '';
		
		if ( $last == 1 ) {
			$total = $count;
			$text = $this->view->locale()->translate('pager-totals-single', compact('start','total'));
		} elseif ( $current == $last ) {
			$finish = $total = $start+$count;
			$text = $this->view->locale()->translate('pager-totals-actual', compact('start','finish','total'));
		} else {
			$text = $this->view->locale()->translate('pager-totals-approx', compact('start','finish','total'));
		}
		
		# Render
		$result .=
			($wrap?'<div class="totals">':'')
				.$text
			.($wrap?'</div>':'');
		
		# Done
		return $result;
	}
	
	public function render ( ) {
		# Render
		$result = $this->renderTotals() . $this->renderPages();
		
		# Done
		return $result;
	}
}
