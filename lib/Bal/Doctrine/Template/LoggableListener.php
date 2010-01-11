<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Listener for the Timestampable behavior which automatically sets the created
 * and updated columns when a record is inserted and updated.
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin Lupton <contact@balupton.com>
 */
class Bal_Doctrine_Template_LoggableListener extends Doctrine_Record_Listener {
	/**
	 * Array of timestampable options
	 * @var string
	 */
	protected $_options = array();

	/**
	 * __construct
	 *
	 * @param string $options
	 * @return void
	 */
	public function __construct ( array $options ) {
		$this->_options = $options;
	}

	/**
	 * Log Insert
	 * @param Doctrine_Event $event
	 * @return void
	 */
	public function postInsert ( Doctrine_Event $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		if ( !$this->_options['insert'] ) return;
		
		# Published
		Bal_Log::getInstance()->log('orm-insert', Bal_Log::DEBUG, array('action'=>'insert','data'=>$Invoker->toArray(),'table'=>$Invoker->getTable()->getTableName()));
		
		# Done
		return true;
	}
	
	/**
	 * Log Save
	 * @param Doctrine_Event $Event
	 */
	public function postSave ( Doctrine_Event $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		if ( !$this->_options['save'] ) return;
		
		# Published
		Bal_Log::getInstance()->log('orm-save', Bal_Log::DEBUG, array('action'=>'save','data'=>$Invoker->toArray(),'table'=>$Invoker->getTable()->getTableName()));
		
		# Done
		return true;
	}

	/**
	 * Log Delete
	 * @param Doctrine_Event $Event
	 * @return string
	 */
	public function postDelete ( Doctrine_Event $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		if ( !$this->_options['delete'] ) return;
		
		# Published
		Bal_Log::getInstance()->log('orm-delete', Bal_Log::DEBUG, array('action'=>'delete','data'=>$Invoker->toArray(),'table'=>$Invoker->getTable()->getTableName()));
		
		# Done
		return true;
	}
}
