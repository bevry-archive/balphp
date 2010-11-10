<?

class Bal_View_Helper_HeadScriptBundler extends Zend_View_Helper_HeadScript {
	
	# =========================
	# Custom: Variables
	
	protected $_extension = 'js';
	protected $_compiledOffset = null;
	
	protected $_compiler = 'closure-webservice';
	protected $_compilerPath = null;
	
	# =========================
	# Custom: Handling
	
	protected function isCompressable($item){
		return ($item->type === 'text/javascript') && (!empty($item->attributes['src']) || !empty($item->source));
	}
	
	protected function addFile($url){
		if ( $this->_compiledOffset !== null ) 
			$this->offsetSetFile($this->_compiledOffset, $url);
		else
			$this->prependFile($url);
	}
	
	# =========================
	# Custom: Paths
	
	protected function getCachePath ( ) {
		return CACHE_SCRIPTS_PATH;
	}
	
	protected function getCacheUrl ( ) {
		return CACHE_SCRIPTS_URL;
	}
	
	# =========================
	# Custom: Compilers
	
	protected function compileConcat ( $paths, $path ) {
		file_put_contents($path,'');
		foreach ( $paths as $file ) {
			file_put_contents($path,file_get_contents($file),FILE_APPEND);
		}
	}
	
	protected function compileClosureWebservice ( $paths, $path ) {
		$Compiler = new Bal_Service_GoogleClosure();
		$Compiler->compile($paths,$path);
	}
	
	protected function compileClosureCompiler ( $paths, $path ) {
		$command = 'java -jar '.$this->_compilerPath.' --compilation_level WHITESPACE_ONLY --js_output_file '.$path.' --js '.implode($paths,' --js=');
		$result = system($command);
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
				$url = $item->attributes['src'];
				if ( (BASE_URL && strpos($url,BASE_URL) === 0) || strpos($url,'/') === 0 ) {
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
			
			# Determine Cache Path
			$cachePath = $this->getCachePath().'/'.preg_replace('/[^a-zA-Z0-9\.]+/','-',$url);
			
			# Ensure Correct Path
			$path = str_replace('//','/',$path);
			
			# Apply
			$files[$key] = array(
				'url' => $url,
				'path' => $path,
				'cachePath' => $cachePath
			);
			$paths[] = $cachePath;
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
		
		# Determine if Refresh is Needed
		foreach ( $files as $key => $file ) {
			# Preset
			$url = $file['url'];
			$path = $file['path'];
			$cachePath = $file['cachePath'];
			
			# Determine Cache Modified Time
			if ( !is_file($cachePath) ) {
				touch($cachePath);
				$cacheFilemtime = 0;
			}
			else {
				$cacheFilemtime = filemtime($cachePath);
			}
			
			# Determine Real Modified Time
			if ( $path ) {
				$filemtime = filemtime($path);
			}
			else {
				$filemtime = $cacheFilemtime;
			}
			
			# Should we refresh?
			if ( $filemtime >= $cacheFilemtime ) {
				$refresh = true;
				if ( strstr($url,'?') || !$path ) {
					$contents = file_get_contents($url);
				}
				else {
					$contents = file_get_contents($path);
				}
				file_put_contents($cachePath,$contents);
			}
			elseif ( $filemtime >= $compiledFilemtime ) {
				$refresh = true;
			}
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
			$compiledUrl = $compiledFileUrl.'?'.$compiledFilemtime;
			$this->addFile($compiledUrl);
		}
		
		# Let's hand back to our parent
		$result = parent::toString($indent);
		
		# Add Refresh Status
		if ( $refresh ) {
			$result .= "\n<!--[Scripts Bundled + Refreshed]-->\n";
		}
		
		# Return result
		return $result;
	}

}
