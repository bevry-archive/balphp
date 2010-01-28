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

	protected $Writer = null;
	
	public function __construct ( ) {
		# Parent Construct
		return parent::__construct(); // will handle priorities for us
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
	 * GEt the Render Writer
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
		return $this->getRenderWriter()->render();
	}
	
	
}
