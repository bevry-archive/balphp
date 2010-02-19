<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008-2009 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage bal
 * @version 0.2.0-final, December 9, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008-2009, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */
require_once 'Zend/Log.php';
class Bal_Log extends Zend_Log {
	
    /*const INSERT	= 8;
    const SAVE		= 9;
    const DELETE	= 10;
    const SUCCESS	= 11;*/

	protected $_Writer = null;
	protected $_Session;
	
    /**
     * Class constructor.  Startup log writers
     * @return void
     */
	public function __construct ( ) {
		# Parent Construct
		$result = parent::__construct(); // will handle priorities for us
		
		# Return result
		return $result;
	}
	
	/**
	 * Load Stored Events
	 * @return
	 */
	public function loadEvents ( ) {
		# Check Session Status
		$Session = $this->getSession();
		if ( !empty($Session->events) ) {
			# Load cached events
			$events = $Session->events;
			$this->addEvents($events);
		}
		
		# Chain
		return $this;
	}
	
	/**
	 * Store an Event
	 * @param array $event
	 * @return
	 */
	public function storeEvent ( $event ) {
		# Prepare
		$Session = $this->getSession();
		
		# Store
		$Session->events[] = $event;
		
		# Chain
		return $this;
	}
	
	/**
	 * Clear Stored Events
	 * @return
	 */
	public function clearEvents ( ) {
		# Prepare
		$Session = $this->getSession();
		
		# Store
		$Session->events = array();
		
		# Chain
		return $this;
	}
	
	/**
	 * Fetch the Log Session
	 * @return Zend_Session_Namespace
	 */
	public function getSession ( ) {
		# Prepare
		$Session = null;
		
		# Handle
		if ( !$this->_Session ) {
			$Session = new Zend_Session_Namespace('Log');
			if ( empty($Session->events) )
				$Session->events = array();
			$this->_Session = $Session;
		}
		else {
			$Session = $this->_Session;
		}
		
		# Return Session
		return $Session;
	}
	
	/**
	 * Add Events
	 * @param array $event
	 * @return
	 */
	public function addEvents ( array $events ) {
		# Add events
		foreach ( $events as $event ) {
			$this->addEvent($event);
		}

		# chain
		return $this;
	}
	
	/**
	 * Add Event
	 * @param array $event
	 * @return
	 */
	public function addEvent ( $event ) {
		# abort if rejected by the global filters
        foreach ($this->_filters as $filter) {
            if (! $filter->accept($event)) {
                return;
            }
        }
		
		# send to each writer
        foreach ($this->_writers as $writer) {
            $writer->write($event);
        }

		# chain
		return $this;
	}
	
    /**
     * Get the Log Instance
     * @return Zend_Log
     */
	public static function getInstance ( ) {
		return Zend_Registry::get('Log');
	}
	
	/**
	 * Set the Writer used to render the log
	 * @param Zend_Log_Writer_Abstract $Writer
	 * @return
	 */
	public function setRenderWriter ( $Writer ) {
		# Add Writer
		$this->addWriter($Writer);
		# Set Default
		$this->_Writer = $Writer;
		# Chain
		return $this;
	}
	
	/**
	 * Get the Render Writer
	 * @return Zend_Log_Writer_Abstract
	 */
	public function getRenderWriter ( ) {
		return $this->_Writer;
	}
	
    /**
     * Get the log entries
     * @return array
     */
	public function getEvents ( ) {
		return $this->getRenderWriter()->events;
	}
	
    /**
     * Get a rendered list of log entries
     * @return array
     */
	public function render ( ) {
		# Render
		$cli = empty($_SERVER['HTTP_HOST']);
		$render = $this->getRenderWriter()->render();
		return $cli ? strip_tags($render) : $render;
	}
	
	
}
