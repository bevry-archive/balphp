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
		# Clear Remote Events
		$Log = Bal_App::getLog();
		$Log->clearEvents();
		
		# Prepare
		$Locale = Bal_App::getLocale();
		$events = $this->events;
		$result = '';
		
		# Cycle
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
				ob_start();
				var_dump($event['details']);
				$_details = ob_get_contents();
				ob_end_clean();
				$result .= '<pre class="details">'.htmlspecialchars($_details).'</pre>';
			}
			
			# Finish event
			$result .= '</li>';
		}
		
		# Check
		if ( !empty($result) ) {
			$class = array('log');  if ( $this->isFriendly() ) $class[] = 'friendly';
			$class = implode(' ',$class);
			$result = '<ul class="'.$class.'">'.$result.'</ul>';
		}
		
		# Done
		return $result;
    }
	
    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     */
    public function _write($event) {
		# Store Event Remotely
		$Log = Bal_App::getLog();
		$Log->storeEvent($event);
		
		# Store Event Locally
        $this->events[] = $event;
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

    /**
     * Create a new instance of Zend_Log_Writer_Mock
     * 
     * @param  array|Zend_Config $config
     * @return Zend_Log_Writer_Mock
     * @throws Zend_Log_Exception
     */
    static public function factory($config) 
    {
        return new self();
    }
	
}