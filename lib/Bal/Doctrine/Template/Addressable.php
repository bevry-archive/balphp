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
class Bal_Doctrine_Template_Addressable extends Bal_Doctrine_Template_Abstract {
	
    /**
     * Array of options
     * @var string
     */
    protected $_options = array(
		'address1' => array(
			'disabled'		=>	false,
        	'name'			=>	'address1',
			'type'			=>	'string',
			'length'		=>	60
		),
		'address2' => array(
			'disabled'		=>	false,
        	'name'			=>	'address2',
			'type'			=>	'string',
			'length'		=>	60
		),
		'suburb' => array(
			'disabled'		=>	false,
        	'name'			=>	'suburb',
			'type'			=>	'string',
			'length'		=>	60
		),
		'state' => array(
			'disabled'		=>	false,
        	'name'			=>	'state',
			'type'			=>	'string',
			'length'		=>	60
		),
		'country' => array(
			'disabled'		=>	false,
        	'name'			=>	'country',
			'type'			=>	'string',
			'length'		=>	60
		),
		'country_code' => array(
			'disabled'		=>	false,
        	'name'			=>	'country_code',
			'type'			=>	'string',
			'length'		=>	2,
            'country'		=> 	true
		),
		'postcode' => array(
			'disabled'		=>	false,
        	'name'			=>	'postcode',
			'type'			=>	'string',
			'length'		=>	10
		)
    );

    /**
     * Set table definition
     * @return void
     */
    public function setTableDefinition() {
    	# Prepare
    	$column_helpers = array('address1','address2','suburb','state','country','postcode');
    	
    	# Handle
		$this->hasColumnHelpers($this->_options, $column_helpers);
		
		# Done
		return true;
    }
	
    /**
     * Setup table relations
     * @return void
     */
    public function setUp(){
    	# Handle
        $this->getInvoker()->hasAccessor('address', 'getAddress');
        
        # Done
        return true;
	}
	
	
	/**
	 * Get the address
	 * @return string
	 */
	public function getAddress ( ) {
		# Handle
		$address = $this->address1;
		if ( !$this->_options['address2']['disabled'] ){
			$address .= "\n" . $this->address2;
		}
		
		# Done
		return $address;
	}
	
}