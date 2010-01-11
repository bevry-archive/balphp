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
class Bal_Doctrine_Template_File extends Bal_Doctrine_Template_Abstract {
	
    /**
     * Array of options
     * @var string
     */
    protected $_options = array(
		'name' => array(
			'disabled'		=>	false,
        	'name'			=>	'name',
			'type'			=>	'string',
			'length'		=>	255,
	        'options'       =>  array(
				'unique'	=>	true,
				'notblank'	=>	true
			)
		),
		'title' => array(
			'disabled'		=>	false,
        	'name'			=>	'title',
			'type'			=>	'string',
			'length'		=>	255,
	        'options'       =>  array(
				'notblank'	=>	true,
			)
		),
		'extension' => array(
			'disabled'		=>	false,
        	'name'			=>	'extension',
			'type'			=>	'string',
			'length'		=>	5,
	        'options'       =>  array(
				'notblank'	=>	true,
			)
		),
		'path' => array(
			'disabled'		=>	false,
        	'name'			=>	'path',
			'type'			=>	'string',
			'length'		=>	255,
	        'options'       =>  array(
				'notblank'	=>	true,
			)
		),
		'size' => array(
			'disabled'		=>	false,
        	'name'			=>	'size',
			'type'			=>	'integer',
			'length'		=>	4,
	        'size'       	=>  array(
				'default'	=>	0, 
				'notnull'	=>	true,
			)
		),
		'type' => array(
			'disabled'		=>	false,
	        'name'          =>  'type',
	        'type'          =>  'enum',
	        'length'        =>  11,
	        'options'       =>  array(
				'values'	=>	array('file','document','image','video','audio','web','application','archive','unkown'),
				'default'	=>	'unkown',
				'notblank'	=>	true
			)
		),
		'mimetype' => array(
			'disabled'		=>	false,
        	'name'			=>	'mimetype',
			'type'			=>	'string',
			'length'		=>	20,
	        'options'       =>  array(
				'notblank'	=>	true,
			)
		),
		'humantype' => array(
			'disabled'		=>	false,
        	'name'			=>	'humantype',
			'type'			=>	'string',
			'length'		=>	20,
	        'options'       =>  array(
				'notblank'	=>	true,
			)
		),
		'width' => array(
			'disabled'		=>	false,
        	'name'			=>	'width',
			'type'			=>	'integer',
			'length'		=>	2,
	        'size'       	=>  array(
				'notnull'	=>	false,
			)
		),
		'height' => array(
			'disabled'		=>	false,
        	'name'			=>	'height',
			'type'			=>	'integer',
			'length'		=>	2,
	        'size'       	=>  array(
				'notnull'	=>	false,
			)
		),
		'url' => array(
			'disabled'		=>	false,
        	'name'			=>	'url',
			'type'			=>	'string',
			'length'		=>	255,
	        'options'       =>  array(
				'notblank'	=>	true,
			)
		),
    );

    /**
     * Set table definition
     * @return void
     */
    public function setTableDefinition() {
    	# Prepare
    	$column_helpers = array_keys($this->_options);
    	
    	# Handle
		$this->hasColumnHelpers($this->_options, $column_helpers);
		
		# Behaviors
        $sluggable0 = new Doctrine_Template_Sluggable(array(
             'name' => 'code',
             'canUpdate' => true,
             'fields' => 
             array(
              0 => 'name',
             ),
             ));
        $bal_doctrine_template_auditable0 = new Bal_Doctrine_Template_Auditable(array(
             'status' => 
             array(
              'disabled' => true,
             ),
             'enabled' => 
             array(
              'disabled' => true,
             ),
             'author' => 
             array(
              'disabled' => false,
             ),
             'authorstr' => 
             array(
              'disabled' => true,
             ),
             'created_at' => 
             array(
              'disabled' => false,
             ),
             'updated_at' => 
             array(
              'disabled' => false,
             ),
             'published_at' => 
             array(
              'disabled' => true,
             ),
             ));
        $searchable0 = new Doctrine_Template_Searchable(array(
             'fields' => 
             array(
              0 => 'code',
              1 => 'title',
              2 => 'path',
              3 => 'type',
             ),
             ));
        $this->actAs($sluggable0);
        $this->actAs($bal_doctrine_template_auditable0);
        $this->actAs($searchable0);
		
        # Listeners
        $this->addListener(new Bal_Doctrine_Template_FileListener($this->_options));
        
        # Done
        return true;
    }
	
    /**
     * Setup table relations
     * @return void
     */
    public function setUp(){
    	# Getters/Setters
    	if ( $this->optionEnabled('url') )
			$this->getInvoker()->hasAccessor('url', 'getUrl');
		
		# Must Haves
		$this->hasMutator('file', 'setFile');
        
		# Relations
		$this->hasOneHelper($this->_options['author']);
        
        # Done
        return true;
	}
	
	/**
	 * Get the url for the file
	 * @return string
	 */
	public function getUrl ( ) {
		$name = $this->name;
		if ( empty($name) ) {
			return null;
		}
		$url = UPLOADS_URL.'/'.rawurlencode($name);
		return $url;
	}
	
	/**
	 * Set the physical file using a $_FILE
	 * @return
	 */
	public function setFile ( $file ) {
		# Configuration
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Check the file
		if ( !empty($file['error']) ) {
			$error = $file['error'];
			switch ( $file['error'] ) {
				case UPLOAD_ERR_INI_SIZE :
					$error = 'ini_size';
					break;
				case UPLOAD_ERR_FORM_SIZE :
					$error = 'form_size';
					break;
				case UPLOAD_ERR_PARTIAL :
					$error = 'partial';
					break;
				case UPLOAD_ERR_NO_FILE :
					$error = 'no_file';
					break;
				case UPLOAD_ERR_NO_TMP_DIR :
					$error = 'no_tmp_dir';
					break;
				case UPLOAD_ERR_CANT_WRITE :
					$error = 'cant_write';
					break;
				default :
					$error = 'unknown';
					break;
			}
			throw new Doctrine_Exception('error-application-file-' . $error);
			return false;
		}
		if ( empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) ) {
			throw new Doctrine_Exception('error-application-file-invalid');
			return false;
		}
		
		# Prepare file
		$file_name = $file['name'];
		if ( strpos($file_name, '.') === 0 ) $file_name = 'file'.$file_name; // prevent .htaccess uploads and other dogies
		$file_title = $file_name;
		$file_old_path = $file['tmp_name'];
		$file_new_path = UPLOADS_PATH . DIRECTORY_SEPARATOR . $file_name;
		$exist_attempt = 0;
		$extension = get_extension($file_name); if ( !$extension ) $extension = 'file';
		while ( file_exists($file_new_path) ) {
			// File already exists
			// Pump exist attempts
			++$exist_attempt;
			// Add the attempt to the end of the file
			$file_name = get_filename($file_title, false) . $exist_attempt . '.' . $extension;
			$file_new_path = UPLOADS_PATH . DIRECTORY_SEPARATOR . $file_name;
		}
		
		# Move file
		$success = move_uploaded_file($file_old_path, $file_new_path);
		if ( !$success ) {
			throw new Doctrine_Exception('Unable to upload the file.');
			return false;
		}
		
		# Prepare
		$file_path = realpath($file_new_path);
		$file_type = get_filetype($file_path);
		$file_size = filesize($file_path);
		
		# Image
		if ( $file_type === 'image' ) {
			# Dimensions
			$image_dimensions = image_dimensions($file_path);
			if ( !empty($image_dimensions) ) {
				// It is not a image we can modify
				$this->width = 0;
				$this->height = 0;
			} else {
				$this->width = $image_dimensions['width'];
				$this->height = $image_dimensions['height'];
			}
			# Compress
			$image_info = image_read($file_path, true);
			if ( $image_info ) {
				$image_info['image_type'] = IMAGETYPE_JPEG;
				$image_info = image_write($image_info, true);
				if ( $image_info ) {
					$image_info = image_compress($image_info, true);
					if ( $image_info ) {
						$file_title = get_filename($file_title,false) . '.jpg';
						$file_name = get_filename($file_name,false) . '.jpg';
						$file_path = UPLOADS_PATH . DIRECTORY_SEPARATOR . $file_name;
						$image_contents = $image_info['image'];
						file_put_contents($file_path, $image_contents, LOCK_EX);
						$file_path = realpath($file_path);
						$file_size = strlen($image_contents);
					} else {
						// Is not an image we can compress
						//echo '!compress';
					}
				} else {
					// Is not an image we can write to
					//echo '!write';
				}
			} else {
				// Is not an image we can read from
				//echo '!read';
			}
		}
		
		# Delete Previous
		if ( $this->path !== $file_path && file_exists($this->path) ) {
			unlink($this->path);
		}
		
		# Secure
		$file_mimetype = trim_mime_type(get_mime_type($file_path));
		$file_humantype = filetype_human($file_path);
		$file_extension = get_extension($file_path);
		
		# Apply
		$this->code = $file_name;
		$this->name = $file_name;
		$this->title = $file_title;
		$this->path = $file_path;
		$this->size = $file_size;
		$this->mimetype = $file_mimetype;
		$this->humantype = $file_humantype;
		$this->extension = $file_extension;
		$this->type = $file_type;
		
		# Done
		return true;
	}

	/**
	 * Download the physical file
	 * @return
	 */
	public function download ( ) {
		global $Application;
		
		# Get path
		$file_path = $this->path;
		
		# Output result and download
		become_file_download($file_path, null, null);
		die();
	}

	
	/**
	 * Ensure Consistency
	 * @return bool
	 */
	public function ensureFileConsistency(){
		# Prepare
		$save = false;
		
		# Url
		if ( $this->_get('url') !== $this->getUrl() ) {
			$this->_set('url', $this->getUrl(), false); // false at end to prevent comparison
			$save = true;
		}
		
		# Done
		return $save;
	}
	
	
}
