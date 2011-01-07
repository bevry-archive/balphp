<?

class Bal_View_Helper_HeadScriptBundler extends Zend_View_Helper_HeadScript {

	# =========================
	# Custom: Variables

	protected $_extension = 'js';
	protected $_compiledOffset = null;

	protected $_compiler = 'closure-webservice';
	protected $_compilerPath = null;

    /**
     * Optional allowed attributes for script tag
     * @var array
     */
    protected $_optionalAttributes = array(
        'charset', 'defer', 'language', 'src', 'compress'
    );

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

	protected function compileConcat ( $files, $output_path ) {
		file_put_contents($files,'');
		foreach ( $files as $file ) {
			$file_path = $file['path'];
			file_put_contents($output_path,file_get_contents($file_path),FILE_APPEND);
		}
	}

	protected function compileClosureWebservice ( $files, $output_path ) {
		$Compiler = new Bal_Service_GoogleClosure();
		$Compiler->compile($files,$output_path);
	}

	protected function compileClosureCompiler ( $files, $output_path ) {
		# Check
		if ( !$this->_compilerPath ) {
			throw new Exception('Unknown Google Closure Compiler Path');
		}

		# Prepare Output File
		file_put_contents($output_path,'');

		# Compile Each File and Append to Output File
		foreach ( $files as $file ) {
			$out_path = $file_path = $file['path'];
			if ( $file['compress'] ) {
				$compressed_path = $file['path'].'.compressed';
				$command = 'java -jar '.$this->_compilerPath.' --js_output_file '.$compressed_path.' --js '.$file_path;
				$out_path = $compressed_path;
			}
			file_put_contents($output_path,file_get_contents($out_path),FILE_APPEND);
		}
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

	protected function isMinified ( $path ) {
		$minified = preg_match('/[\.\/\-](min(ified)?|compressed)[\.\/\-]/i', $path);
		return $minified ? true : false;
	}

	protected function compile($files,$path){
		$compiler = str_replace(' ','',ucwords(str_replace('-',' ',$this->_compiler)));
		$function = 'compile'.$compiler;

		if ( method_exists($this,$function) ) {
			$this->$function($files,$path);
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
		$hash = '';
		$sourcePath = '';
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
				$filename = 'source-'.md5($source).'.'.$this->_extension;
				$sourcePath = $this->getCachePath().'/'.$filename;
				$url = ROOT_URL.$this->getCacheUrl().'/'.$filename;

				# Write to file
				if ( !file_exists($sourcePath) ) {
					// we can do an if here, as the filename is based on the contents, if the contents has changed the filename would be different
					file_put_contents($sourcePath, $source);
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
					$sourcePath = DOCUMENT_ROOT.'/'.preg_replace('/\?.*/','',str_replace(ROOT_URL,'',$url));
					if ( !is_file($sourcePath) ) {
						$sourcePath = null;
					}
				}
			}

			# Determine Cache Path
			$cachePath = $this->getCachePath().'/cached-'.preg_replace('/[^a-zA-Z0-9\.]+/','-',$url);

			# Ensure Correct Path
			$sourcePath = $sourcePath ? str_replace('//','/',$sourcePath) : false;

			# Apply
			$minified = $this->isMinified($cachePath);
			$compress = !$minified && (isset($item->attributes['compress']) && $item->attributes['compress'] !== 'false');
			$files[$key] = array(
				'url' => $url,
				'sourcePath' => $sourcePath,
				'cachePath' => $cachePath,
				'path' => $cachePath,
				'minified' => $minified,
				'compress' => $compress
			);
			$hash .= $url;
		}

		# Hash
		$hash = md5($hash);
		$compiledFileName = 'compiled-'.$hash.'.'.$this->_extension;
		$compiledFileUrl = $this->getCacheUrl().'/'.$compiledFileName;
		$compiledFilePath = $this->getCachePath().'/'.$compiledFileName;

		# Get last modified time of cache file
		if ( is_file($compiledFilePath) && filesize($compiledFilePath) ) {
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
			$sourcePath = $file['sourcePath'];
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
			if ( $sourcePath ) {
				$filemtime = filemtime($sourcePath);
			}
			else {
				$filemtime = $cacheFilemtime;
			}

			# Should we refresh?
			if ( $filemtime >= $cacheFilemtime ) {
				$refresh = true;
				if ( strstr($url,'?') || !$sourcePath ) {
					$contents = file_get_contents($url);
				}
				else {
					$contents = file_get_contents($sourcePath);
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
				$error = !$this->compile($files,$compiledFilePath);
				if ( $error ) {
					throw new Exception('log-scripts-refreshed-failed');
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
		if ( $refresh && !$error ) {
			$Log = Bal_Log::getInstance();
			$log_details = array();
			$Log->log(array('log-scripts-refreshed',$log_details),Bal_Log::NOTICE,array('friendly'=>true,'class'=>'success','details'=>$log_details));
		}

		# Return result
		return $result;
	}

}
