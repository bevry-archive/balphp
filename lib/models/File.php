<?php

/**
 * File
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6365 2009-09-15 18:22:38Z jwage $
 */
class Bal_File extends Base_Bal_File {

	/**
	 * Apply modifiers
	 * @return
	 */
	public function setUp ( ) {
		//$this->hasAccessor('url',   'getUrl');
		$this->hasMutator('upload', 'setUpload');
		$this->hasMutator('file',   'setFile');
		parent::setUp();
	}
	
	/**
	 * Set the physical file using a $_FILE
	 * @return
	 */
	public function setUpload ( $file ) {
		# Fetech Config
		$uploads_path = Bal_App::getConfig('uploads_path');
		$uploads_url = Bal_App::getConfig('uploads_url');
		
		# Check the file
		$error = delve($file,'error');
		if ( $error ) {
			switch ( $error ) {
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
		$tmp_name = delve($file,'tmp_name');
		if ( !$tmp_name || !is_uploaded_file($tmp_name) ) {
			throw new Doctrine_Exception('error-application-file-invalid');
			return false;
		}
		
		# Prepare file
		$file_name = $file['name'];
		if ( strpos($file_name, '.') === 0 ) $file_name = 'file'.$file_name; // prevent .htaccess uploads and other dogies
		$file_title = $file_name;
		$file_old_path = $file['tmp_name'];
		$file_new_path = $uploads_path . DIRECTORY_SEPARATOR . $file_name;
		$exist_attempt = 0;
		$extension = get_extension($file_name); if ( !$extension ) $extension = 'file';
		while ( file_exists($file_new_path) ) {
			// File already exists
			// Pump exist attempts
			++$exist_attempt;
			// Add the attempt to the end of the file
			$file_name = get_filename($file_title, false) . $exist_attempt . '.' . $extension;
			$file_new_path = $uploads_path . DIRECTORY_SEPARATOR . $file_name;
		}
		
		# Move file
		$success = move_uploaded_file($file_old_path, $file_new_path);
		if ( !$success ) {
			throw new Doctrine_Exception('Unable to upload the file.');
			return false;
		}
		
		# Prepare File Url
		$this->url = $uploads_url . '/' . rawurlencode($file_name);
		
		# Continue to set the file
		return $this->setFile($file_new_path);
	}
	
	/**
	 * Set the physical file using a $_FILE
	 * @return
	 */
	public function setFile ( $file ) {
		# Check
		if ( is_array($file) ) {
			return $this->setUpload($file);
		}
		
		# Prepare
		$file_path  = realpath($file);
		$file_title = $file_name = basename($file_path);
		$file_type  = get_filetype($file_path);
		$file_size  = filesize($file_path);
		
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
						$file_path = dirname($file_path) . DIRECTORY_SEPARATOR . $file_name; // immediate file path, so that 3rd party apis know where the file is - does risk curruption due to out of date value, but risk we are prepared to take
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
		if ( empty($this->title) ) $this->title = $file_title;
		$this->code = $file_name;
		$this->name = $file_name;
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
	 * Ensure Level
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureFile ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('postDelete')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$File = $Event->getInvoker();
		
		# Delete
		if ( $Event_type === 'postDelete' ) {
			# Get Path
			$file_path = $File->path;
		
			# Delete the file
			unlink($file_path);
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Consistency
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensure ( $Event, $Event_type ){
		return Bal_Doctrine_Core::ensure($Event,$Event_type,array(
			'ensureFile'
		));
	}
	
	/**
	 * preSave Event
	 * @return
	 */
	public function preSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * postSave Event
	 * @return
	 */
	public function postSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			$Invoker->save();
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * postDelete Event
	 * @return
	 */
	public function postDelete ( $Event ) {
		# Prepare
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * Fetch the File
	 * @param mixed $file
	 * @return mixed null to delete, false means none
	 */
	public static function fetchFile ( $file ) {
		# Prepare
		$File = false;
		
		# Create File
		if ( is_array($file) ) {
			if ( delve($file,'_delete_') ) {
				# Delete File
				$File = null;
			}
			elseif ( delve($file,'id') ) {
				# Database File
				$File = Doctrine::getTable('File')->find(delve($file,'id'));
				if ( !delve($File,'id') ) {
					$File = null;
				}
			}
			elseif ( delve($file,'tmpname') ) {
				# File Upload
				if ( !delve($file,'error') ) {
					#  File Upload
					$File = new File();
					$File->file = $file;
				}
			}
			elseif ( delve($file,'file') ) {
				if ( !delve($file,'error') && !delve($file,'file.error') ) {
					# File Upload or Actual File
					$File = new File();
					$File->file = delve($file,'file');
				}
			}
		}
		elseif ( is_object($file) && $file instanceOf File ) {
			$File = $file;
		}
		elseif ( is_string($file) || is_numeric($file) ) {
			# Database File
			$File = Doctrine::getTable('File')->findOneByIdOrCode($file,$file);
			if ( !delve($File,'id') ) {
				$File = null;
			}
		}
		elseif ( $file === null ) {
			$File = null;
		}
		
		# Return File
		return $File;
	}
	
	
	# ========================
	# CRUD HELPERS
	
	
	/**
	 * Fetch all the records for public access
	 * @version 1.0, April 12, 2010
	 * @return mixed
	 */
	public static function fetch ( array $params = array() ) {
		# Prepare
		Bal_Doctrine_Core::prepareFetchParams($params,array('fetch','Root','Parent','User','Author'));
		extract($params);
		
		# Query
		$Query = Doctrine_Query::create();
		
		# Prepare
		$ListQuery = Doctrine_Query::create()->select('m.*, ma.*')->from('File m, m.Author')->orderBy('m.code ASC')->setHydrationMode(Doctrine::HYDRATE_ARRAY);
		
		# Handle
		if ( $fetch === 'list' ) {
			$Query
				->select('File.id, File.type, File.humantype, File.code, File.size, Author.id, Author.code, Author.displayname')
				->from('File,Content.Author Author')
				->orderBy('File.code ASC')
				;
		}
		else {
			$Query
				->select('File.*, Author.id, Author.code, Author.displayname')
				->from('File,Content.Author Author')
				->orderBy('File.code ASC')
				;
		}
		
		# Criteria
		if ( $User ) {
			$User = Bal_Doctrine_Core::resolveId($User);
			$Query->andWhere('Author.id = ?', $User);
		}
		if ( $Author ) {
			$Author = Bal_Doctrine_Core::resolveId($Author);
			$Query->andWhere('Author.id = ?', $Author);
		}
		if ( $Parent ) {
			$Parent = Bal_Doctrine_Core::resolveId($Parent);
			$Query->andWhere('Parent.id = ?', $Parent);
		}
		if ( $Root ) {
			$Query->andWhere('NOT EXISTS (SELECT ContentOrphan.id FROM Content ContentOrphan WHERE ContentOrphan.id = Content.Parent_id)');
		}
		
		# Fetch
		$result = Bal_Doctrine_Core::prepareFetchResult($params,$Query);
		
		# Done
		return $result;
	}
	
}
