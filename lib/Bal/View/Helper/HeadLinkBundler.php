<?

class Bal_View_Helper_HeadLinkBundler extends Zend_View_Helper_HeadLink {
	
	protected function isCompressable($item){
		return isset($item->href) && ($item->rel === 'stylesheet');
	}
	
	protected function compress($paths,$path){
		$command = 'java -jar '.YUI_COMPILER_FILE_PATH.' '.implode($paths,' ').' -o '.$path;
		`$command`;
	}
	
	protected function getCachePath ( ) {
		return CACHE_STYLES_PATH;
	}
	
	protected function getCacheUrl ( ) {
		return CACHE_STYLES_URL;
	}
	
	public function toString ($indent = null) {
		# Sort the Items
        $this->getContainer()->ksort();
		
		# Prepare
		$urls = array();
		$paths = array();
		$refresh = false;
		
		# Cycle Through the Items
        foreach ($this as $key => $item) {
			# Is the Item Valid?
            if ( !$this->isCompressable($item) ) {
				continue;
            }
			
			# Determine Full URL
			$url = $item->href;
			if ( strpos($url,BASE_URL) === 0 || strpos($url,'/') === 0 ) {
				$url = ROOT_URL.$url;
			}
			elseif ( strpos($url,'http') === false ) {
				$url = ROOT_URL.BASE_URL.$url;
			}
			
			# Determine Path
			$path = $this->getCachePath().'/'.preg_replace('/[^a-zA-Z0-9\.]+/','-',$url);
			if ( !is_file($path) ) {
				touch($path);
			}
			
			# Apply
			$urls[$key] = $url;
			$paths[$key] = $path;
		}
		
		# Delete the Items
		foreach ( $urls as $key => $url ) {
			unset($this[$key]);
		}
		
		# Hash
		$hash = md5(implode($urls));
		$cacheFileUrl = $this->getCacheUrl().'/'.$hash.'.css';
		$cacheFilePath = $this->getCachePath().'/'.$hash.'.css';
		$now = time();
		$yesterday = strtotime('-1 day');
		
		# Determine if Refresh is Needed
		if ( is_file($cacheFilePath) ) {
			# Get last modified time of cache file
			$cacheFilemtime = filemtime($cacheFilePath);
		
			# Determine if Refresh is Needed
			foreach ( $urls as $key => $url ) {
				# Preset
				$filemtime = $yesterday;
				
				# Determine File Modified Time
				if ( strpos($url,ROOT_URL) === 0 ) {
					// We are a local file, so determine the full path from the base url
					$path = APPLICATION_ROOT_PATH.'/'.preg_replace('/\?.*/','',str_replace(ROOT_URL.BASE_URL,'',$url));
					if ( is_file($path) ) {
						$filemtime = filemtime($path);
					}
				}
				
				# Determine It's Path
				$urlFilePath = $paths[$key];
				$urlFilemtime = filemtime($urlFilePath);
				
				# Should we refresh?
				if ( $filemtime > $urlFilemtime ) {
					$refresh = true;
					$urlContents = file_get_contents($url);
					file_put_contents($urlFilePath,$urlContents);
				}
				elseif ( $filemtime > $cacheFilemtime ) {
					$refresh = true;
				}
			}
		}
		else {
			// We need to refresh as we don't exist
			$refresh = true;
		}
		
		# Refresh the Cache File
		if ( $refresh ) {
			touch($cacheFilePath);
			$this->compress($paths,$cacheFilePath);
			$cacheFilemtime = filemtime($cacheFilePath);
		}
		
		# Use the Cached File
		$this->appendStylesheet($cacheFileUrl.'?'.$cacheFilemtime);
		
		# Let's hand back to our parent
		return parent::toString($indent);
	}
	
}
