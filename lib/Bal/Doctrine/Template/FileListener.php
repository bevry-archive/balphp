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
class Bal_Doctrine_Template_FileListener extends Doctrine_Record_Listener {
	/**
	 * Array of options
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
	 * preSave
	 * @param Doctrine_Event $Event
	 */
	public function preSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$save = false;
		
		# Ensure
		if ( $Invoker->ensureFileConsistency() ) {
			$save = true;
		}
		
		# Done
		return true;
	}
	
	/**
	 * postSave
	 * @param Doctrine_Event $Event
	 * @return string
	 */
	public function postSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$save = false;
	
		# Ensure
		if ( $Invoker->ensureFileConsistency() ) {
			$save = true;
		}
		
		# Apply
		if ( $save ) {
			$Invoker->save();
		}
		
		# Done
		return true;
	}
	
	/**
	 * Delete the physical file
	 * @return
	 */
	public function postDelete ( $Event ) {
		global $Application;
		# Prepare
		$Invoker = $Event->getInvoker();
		
		# Get Path
		$file_path = $Invoker->path;
		
		# Delete the file
		if ( $file_path ) unlink($file_path);
		
		# Done
		return true;
	}
}
