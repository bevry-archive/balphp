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
 * @package    Zend_Log
 * @subpackage Writer
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Mock.php 16971 2009-07-22 18:05:45Z mikaelkael $
 */

/** Zend_Log_Writer_Abstract */
require_once 'Zend/Log/Writer/Abstract.php';

/**
 * @category   Zend
 * @package    Zend_Log
 * @subpackage Writer
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Mock.php 16971 2009-07-22 18:05:45Z mikaelkael $
 */
class Bal_Log_Writer_Rich extends Zend_Log_Writer_Abstract
{
    /**
     * array of log events
     */
    public $events = array();
	
    /**
     * shutdown called?
     */
    public $shutdown = false;
	
	/**
	 */
	protected $_friendly = false;
	
    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     */
    public function _write($event) {
        $this->events[] = $event;
    }

	/**
	 * Handle whether or not we are friendly
	 */
	public function isFriendly ( $friendly = null ) {
		if ( $friendly !== null ) {
			$this->_friendly = $friendly;
		}
		return $this->_friendly ? true : false;
	}
	
    /**
     * Render the Log
     *
     * @param  array  $event  event data
     * @return void
     */
    public function render ( ) {
		# Prepare
		$Locale = Bal_Locale::getInstance();
		$events = $this->events;
		$result = '';
		
		# Cycle
		$class = array('log');  if ( $this->isFriendly() ) $class[] = 'friendly';
		$class = implode(' ',$class);
		$result .= '<ul class="'.$class.'">';
		foreach ( $events as $event ) {
			# Prepare
			array_keys_ensure($event,array('class','friendly','timestamp','message','details'));
			
			# Check Friendly
			if ( !$event['friendly'] && $this->isFriendly() ) {
				continue;
			}
			
			# Class
			$class = array('event',strtolower($event['priorityName']),$event['class']);
			$class = implode(' ',$class);
			
			# Start Event
			$result .= '<li class="'.$class.'">';
			
			# Prepare Message
			$message = $event['message'];
			$message_info = null;
			if ( is_array($message) ) {
				$message_info = $message[1];
				$message = $message[0];
			} elseif ( is_object($message) ) {
				if ( method_exists($message,'toString') ) {
					if ( method_exists($message,'toArray') ) {
						$message_info = $message->toArray();
					}
					$message = $message->toString();
				}
				$message = strval($message);
			}
			
			# Apply Timestamp
			if ( !$this->isFriendly() ) {
				$result .=
					'<span class="timestamp">'.$Locale->datetime($event['timestamp']).'</span>';
			}
			
			# Apply Message
			$result .=
				'<span class="message">'.$Locale->translate($message, $message_info).'</span>';
			
			# Apply Details
			if ( !$this->isFriendly() ) {
				$result .=
					'<span class="details">'.var_export($event['details'],true).'</span>';
			}
			
			# Finish event
			$result .= '</li>';
		}
		$result .= '</ul>';
		
		# Done
		return $result;
    }
	
    /**
     * Record shutdown
     *
     * @return void
     */
    public function shutdown()
    {
        $this->shutdown = true;
    }
	
}