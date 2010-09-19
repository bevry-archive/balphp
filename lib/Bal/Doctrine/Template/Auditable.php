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
 * Doctrine_Template_Item
 *
 * Easily track a balFramework changes
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin "balupton" Lupton <contact@balupton.com>
 */
class Bal_Doctrine_Template_Auditable extends Bal_Doctrine_Template_Abstract {
	
    /**
     * Array of options
     * @var string
     */
    protected $_options = array(
		'created_at' => array(
			'disabled'		=>	true,
        	'name'			=>	'created_at',
			'type'			=>	'timestamp'
		),
		'updated_at' => array(
			'disabled'		=>	true,
        	'name'			=>	'updated_at',
			'type'			=>	'timestamp'
		),
		'published_at' => array(
			'disabled'		=>	true,
        	'name'			=>	'published_at',
			'type'			=>	'timestamp',
	        'options'       =>  array(
				'notblank'	=>	true
			)
		),
		'status' => array(
			'disabled'		=>	true,
	        'name'          =>  'status',
	        'type'          =>  'enum',
	        'length'        =>  10,
	        'options'       =>  array(
				'values'	=>	array('pending','published','deprecated'),
				'default'	=>	'published',
				'notnull'	=>	true
			)
		),
		'Author' => array(
			'disabled'		=>	true,
	        'relation'     	=>  'Author',
	        'class'     	=>  'User',
	        'name'          =>  'user_id',
	        'type'          =>  'integer',
	        'length'        =>  2,
	        'options'       =>  array(
				'unsigned'	=>	true
			)
		),
		'authorstr' => array(
			'disabled'		=>	true,
        	'name'			=>	'authorstr',
			'type'			=>	'string',
			'length'		=>	50,
	        'options'       =>  array(
				'notnull'	=>	true,
				'default'	=>	''
			)
		),
    );

    /**
     * Set table definition
     * @return void
     */
    public function setTableDefinition() {
    	# Prepare
    	$column_helpers = array('published_at','Author','authorstr','status');
    	
    	# Handle
		$this->hasColumnHelpers($this->_options, $column_helpers);
		
		# Behaviors
        $timestampable0 = new Doctrine_Template_Timestampable(array(
			'created' => $this->_options['created_at'],
			'updated' => $this->_options['updated_at']
		));
        $this->actAs($timestampable0);
        
        # Listeners
        $this->addListener(new Bal_Doctrine_Template_AuditableListener($this->_options));
        
        # Done
        return true;
    }
	
    /**
     * Setup table relations
     * @return void
     */
    public function setUp(){
    	# Getters/Setters
    	//if ( !$this->_options['authorstr']['disabled'] )
		//	$this->getInvoker()->hasMutator('authorstr', 'setAuthorstr');
        
		# Relations
		$this->hasOneHelper($this->_options['Author']);
        
        # Done
        return true;
	}

	/**
	 * Ensure the authorstr field
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public function ensureAuthorstr ( Doctrine_Event $Event ) {
		# Prepare
		$Record = $Event->getInvoker();
		$author = null;
		
		# Default
		if ( $this->optionEnabled('Author') ) {
			if ( isset($Record->Author) && $Record->Author->exists() ) {
				$author = $Record->Author->displayname;
			}
			
			# Has changed?
			if ( $this->optionEnabled('authorstr') ) {
				if ( $Record->authorstr != $author ) {
					$Record->set('authorstr', $author, false);
					return true;
				}
			}
		}
		
		# No Change
		return false;
	}
	

	/**
	 * Ensure Consistency
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public function ensureAuditableConsistency ( Doctrine_Event $Event ) {
		# Prepare
		$save = false;
		
		# Author
    	if ( $this->ensureAuthorstr($Event) ) {
			$save = true;
		}
		
		# Done
		return $save;
	}
	
}
