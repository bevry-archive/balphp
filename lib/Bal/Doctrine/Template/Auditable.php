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
			'disabled'		=>	false,
        	'name'			=>	'created_at',
			'type'			=>	'timestamp'
		),
		'updated_at' => array(
			'disabled'		=>	false,
        	'name'			=>	'updated_at',
			'type'			=>	'timestamp'
		),
		'published_at' => array(
			'disabled'		=>	false,
        	'name'			=>	'published_at',
			'type'			=>	'timestamp',
	        'options'       =>  array(
				'notblank'	=>	true
			)
		),
		'status' => array(
			'disabled'		=>	false,
	        'name'          =>  'status',
	        'type'          =>  'enum',
	        'length'        =>  10,
	        'options'       =>  array(
				'values'	=>	array('pending','published','deprecated'),
				'default'	=>	'published',
				'notnull'	=>	true
			)
		),
		'enabled' => array(
			'disabled'		=>	false,
	        'name'          =>  'enabled',
	        'type'          =>  'boolean',
	        'length'        =>  1,
	        'options'       =>  array(
				'default'	=>	true,
				'notnull'	=>	true
			)
		),
		'author' => array(
			'disabled'		=>	false,
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
			'disabled'		=>	false,
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
    	# Handle
		$this->hasColumnHelper($this->_options['published_at']);
		$this->hasColumnHelper($this->_options['author']);
		$this->hasColumnHelper($this->_options['enabled']);
		$this->hasColumnHelper($this->_options['status']);
		
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
    	if ( !$this->_options['authorstr']['disabled'] )
			$this->getInvoker()->hasMutator('authorstr', 'setAuthorstr');
        
		# Relations
		$this->hasOneHelper($this->_options['author']);
        
        # Done
        return true;
	}

	/**
	 * Sets the authorstr field
	 * @param int $position [optional] defaults to id
	 * @return bool
	 */
	public function setAuthorstr ( $author = null ) {
		# Default
		if ( is_null($author) ) {
			if ( isset($this->Author) && $this->Author->exists() ) {
				$author = $this->Author->displayname;
			}
		}
		
		# Has changed?
		if ( $this->authorstr != $author ) {
			$this->_set('authorstr', $author);
			return true;
		}
		
		# No Change
		return false;
	}
	

	/**
	 * Ensure Consistency
	 * @return bool
	 */
	public function ensureAuditableConsistency ( ) {
		# Prepare
		$save = false;
		
		# Author
    	if ( !$this->_options['authorstr']['disabled'] && $this->setAuthorstr() ) {
			$save = true;
		}
		
		# Done
		return $save;
	}
	
}
