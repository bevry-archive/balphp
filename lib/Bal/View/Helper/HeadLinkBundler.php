<?

class Bal_View_Helper_HeadLinkBundler extends Zend_View_Helper_HeadLink {
	
	# =========================
	# Custom: Variables
	
	protected $_extension = 'css';
	protected $_compiledOffset = null;
	
	protected $_compiler = 'scaffold';
	protected $_compilerPath = null;
	
	# =========================
	# Custom: Handling
	
	protected function isCompressable($item){
		return ($item->rel === 'stylesheet') && (!empty($item->href) || !empty($item->source));
	}
	
	protected function addFile($url){
		$this->appendStylesheet($url);
	}
	
	# =========================
	# Custom: Paths
	
	protected function getCachePath ( ) {
		return CACHE_STYLES_PATH;
	}
	
	protected function getCacheUrl ( ) {
		return CACHE_STYLES_URL;
	}
	
	# =========================
	# Custom: Compilers
	
	protected function compileConcat ( $paths, $path ) {
		file_put_contents($path,'');
		foreach ( $paths as $file ) {
			file_put_contents($path,file_get_contents($file),FILE_APPEND);
		}
	}
	
	protected function compileCsscaffold ( $paths, $path ) {
		# Load Scaffold
		require_once CSSCAFFOLD_PATH.'/libraries/Bootstrap.php';
		if ( !defined('SCAFFOLD_PRODUCTION') ) define('SCAFFOLD_PRODUCTION',PRODUCTION_MODE);
		
		# Scaffold Config
		$config = Bal_App::getConfig('compiler.csscaffold.config', array());
		$options = Bal_App::getConfig('compiler.csscaffold.options', array());
		
		# Compile
		$result = Scaffold::parse($paths,$config,$options,false);
		
		# Write
		$result = file_put_contents($path, $result) !== false;
		
		# Return result
		return $result;
	}
	
	protected function compileScaffold ( $paths, $path ) {
		# Load Scaffold
		require_once SCAFFOLD_PATH.'/lib/Scaffold/Environment.php';
		Scaffold_Environment::auto_load();
		
		# Scaffold Config
		$config = Bal_App::getConfig('compiler.scaffold.config', array());
		
		# The container creates Scaffold objects
		$Container = Scaffold_Container::getInstance(SCAFFOLD_PATH,$config);
		
		# This is where the magic happens
		$Scaffold = $Container->build();
		
		# Compile
		$result = '';
		foreach ( $paths as $file ) {
			// Get the sources
			$Source = $Scaffold->getSource($file, $config);
			
			// Compiles the source object
			$Source = $Scaffold->compile($Source);
			
			// Append Result
			$result .= $Source->contents;
		}
		
		# Write
		$result = file_put_contents($path, $result) !== false;
		
		# Return result
		return $result;
	}
	
	# =========================
	# Generic
	
	public function setCompiler( $value ) {
		$this->_compiler = $value;
		return $this;
	}
	
	public function setCompilerPath( $value ) {
		$this->_compilerPath = $value;
		return $this;
	}
	
	public function setCompiledOffset ( $value ) {
		$this->_compiledOffset = $value;
		return $this;
	}
	
	protected function compile($paths,$path){
		$compiler = str_replace(' ','',ucwords(str_replace('-',' ',$this->_compiler)));
		$function = 'compile'.$compiler;
		
		if ( method_exists($this,$function) ) {
			$this->$function($paths,$path);
		}
		else {
			throw new Exception('Compiler ['.$this->_compiler.']['.$function.'] not supported.');
		}
		
		return true;
	}
	
	public function toString ($indent = null) {
		# Sort the Items
        $this->getContainer()->ksort();
		
		# Prepare
		$files = array();
		$paths = array();
		$urls = array();
		$hash = '';
		$refresh = false;
		$error = false;
		
		# Cycle Through the Items
        foreach ($this as $key => $item) {
			# Is the Item Valid?
            if ( !$this->isCompressable($item) ) {
				continue;
            }
			
			# Handle Item (Source or URL)
			if ( !empty($item->source) ) {
				# Generate File Name
				$source = $item->source;
				$filename = md5($source).'.'.$this->_extension;
				$path = $this->getCachePath().'/'.$filename;
				$url = ROOT_URL.$this->getCacheUrl().'/'.$filename;
				
				# Write to file
				if ( !file_exists($path) ) {
					// we can do an if here, as the filename is based on the contents, if the contents has changed the filename would be different
					file_put_contents($path, $source);
				}
			}
			else {
				# Determine Full URL
				$url = $item->href;
				if ( strpos($url,BASE_URL) === 0 || strpos($url,'/') === 0 ) {
					$url = ROOT_URL.$url;
				}
				elseif ( strpos($url,'http') === false ) {
					$url = ROOT_URL.BASE_URL.$url;
				}
				
				# Determine Original Path
				if ( strpos($url,ROOT_URL) === 0 ) {
					// We are a local file, so determine the full path from the base url
					$path = DOCUMENT_ROOT.'/'.preg_replace('/\?.*/','',str_replace(ROOT_URL,'',$url));
					if ( !is_file($path) ) {
						$path = null;
					}
				}
			}
			
			# Ensure Correct Path
			$path = str_replace('//','/',$path);
			
			# Apply
			$files[$key] = array(
				'url' => $url,
				'path' => $path
			);
			$paths[] = $path;
			$hash .= $url;
		}
		
		# Hash
		$hash = md5($hash);
		$compiledFileUrl = $this->getCacheUrl().'/'.$hash.'.'.$this->_extension;
		$compiledFilePath = $this->getCachePath().'/'.$hash.'.'.$this->_extension;
		
		# Get last modified time of cache file
		if ( is_file($compiledFilePath) ) {
			$compiledFilemtime = filemtime($compiledFilePath);
		}
		else {
			touch($compiledFilePath);
			$refresh = true;
			$compiledFilemtime = 0;
		}
		
		# Refresh the Cache File
		if ( $refresh ) {
			try {
				$error = !$this->compile($paths,$compiledFilePath);
				if ( $error ) {
					throw new Exception('Compilation failed.');
				}
			}
			catch ( Exception $Exception ) {
				# Log the Event and Continue
				$Exceptor = new Bal_Exceptor($Exception);
				$Exceptor->log();
				$error = true;
			}
			$compiledFilemtime = filemtime($compiledFilePath);
		}
		
		# Delete the Files
		if ( !$error ) {
			foreach ( $files as $key => $url ) {
				unset($this[$key]);
			}
			
			# Use the Cached File
			$this->addFile($compiledFileUrl.'?'.$compiledFilemtime);
		}
		
		# Let's hand back to our parent
		return parent::toString($indent);
	}
    
}
