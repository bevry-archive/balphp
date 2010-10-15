<?

class Bal_View_Helper_HeadScriptBundler extends Zend_View_Helper_HeadScript {
	
	protected $_compilerPath = null;
	protected $_compiler = 'closure-webservice';
	protected $_compiledOffset = null;
	
	public function setCompiledOffset ( $value ) {
		$this->_compiledOffset = $value;
	}
	
	protected function isCompressable($item){
		return isset($item->attributes['src']) && ($item->type === 'text/javascript');
	}
	
	protected function compileClosureWebservice ( $paths, $path ) {
		$Compiler = new Bal_Service_GoogleClosure();
		$Compiler->compile($paths,$path);
	}
	
	protected function compileClosureCompiler ( $paths, $path ) {
		$command = 'java -jar '.$this->_compilerPath.' --js_output_file='.$path.' --js='.implode($paths,' --js=');
		`$command`;
	}
	
	protected function compile($paths,$path){
		switch ( $this->_compiler ) {
			case 'closure-webservice':
				$this->compileClosureWebservice($paths,$path);
				break;
			
			case 'closure-compiler':
				$this->compileClosureCompiler($paths,$path);
				break;
			
			default:
				throw new Exception('Unknown compiler');
				break;
		}
	}
	
	protected function getCachePath ( ) {
		return CACHE_SCRIPTS_PATH;
	}
	
	protected function getCacheUrl ( ) {
		return CACHE_SCRIPTS_URL;
	}
	
	public function toString ($indent = null) {
		# Sort the Items
        $this->getContainer()->ksort();
		
		# Prepare
		$files = array();
		$paths = array();
		$hash = '';
		$refresh = false;
		
		# Cycle Through the Items
        foreach ($this as $key => $item) {
			# Is the Item Valid?
            if ( !$this->isCompressable($item) ) {
				continue;
            }
			
			# Determine Full URL
			$url = $item->attributes['src'];
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
			
			# Determine Cache Path
			$cachePath = $this->getCachePath().'/'.preg_replace('/[^a-zA-Z0-9\.]+/','-',$url);
			
			# Apply
			$files[$key] = array(
				'url' => $url,
				'path' => $path,
				'cachePath' => $cachePath
			);
			$paths[] = $cachePath;
			$hash .= $url;
		}
		
		# Delete the Files
		foreach ( $files as $key => $url ) {
			unset($this[$key]);
		}
		
		# Hash
		$hash = md5($hash);
		$compiledFileUrl = $this->getCacheUrl().'/'.$hash.'.js';
		$compiledFilePath = $this->getCachePath().'/'.$hash.'.js';
		
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
				file_put_contents($cachePath,file_get_contents($url));
			}
			elseif ( $filemtime >= $compiledFilemtime ) {
				$refresh = true;
			}
		}
		
		# Refresh the Cache File
		if ( $refresh ) {
			$this->compile($paths,$compiledFilePath);
			$compiledFilemtime = filemtime($compiledFilePath);
		}
		
		# Use the Cached File
		if ( $this->_compiledOffset !== null ) 
			$this->offsetSetFile($this->_compiledOffset, $compiledFileUrl.'?'.$compiledFilemtime);
		else
			$this->prependFile($compiledFileUrl.'?'.$compiledFilemtime);
		
		# Let's hand back to our parent
		return parent::toString($indent);
	}
    
}
