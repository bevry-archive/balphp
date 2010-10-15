<?php
/**
 * http://code.google.com/p/php-closure/source/browse/trunk/php-closure.php
 */

class Bal_Service_GoogleClosure {
	
	protected $_srcsToBeMinified = array();
	protected $_compilationLevel = 'SIMPLE_OPTIMIZATIONS';
	protected $_warningLevel = 'DEFAULT';
	
	public function compile ( $paths, $path ) {
		$this->_srcsToBeMinified = $paths;
		$compiled = $this->_performRequests();
		file_put_contents($path,$compiled);
	}

	protected function _readSources($sources) {
		$code = '';
		foreach ( $sources as $src ) {
			$code .= file_get_contents($src);
		}
		return $code;
	}
	
	protected function _getParamList ( $sources ) {
		$params = array();
		$params['js_code'] = $this->_readSources($sources);
		$params['compilation_level'] = $this->_compilationLevel;
		$params['output_format'] = 'xml';
		$params['warning_level'] = $this->_warningLevel;
		$params['use_closure_library'] = 'true';
		$params['output_info_1'] = 'compiled_code';
		$params['output_info_2'] = 'statistics';
		$params['output_info_3'] = 'warnings';
		$params['output_info_4'] = 'errors';
		return $params;
	}
	
	protected function _getParams ( $sources ) {
		$params = array();
		$paramList = $this->_getParamList($sources);
		foreach ( $paramList as $key => $value) {
			$params[] = preg_replace('/_[0-9]$/', '', $key) . '=' . urlencode($value);
		}
		return implode('&', $params);
	}
	
	protected function _getSourceGroups ( ) {
		$requests = array(array());
		$currentRequest = &$requests[0];
		$requestsCount = 1;
		
		$currentSize = 0;
		$sizeLimit = 500*1024;
		$filesThisRequest = array();
		foreach ( $this->_srcsToBeMinified as $src ) {
			$currentSize += filesize($src);
			if ( $currentSize > $sizeLimit ) {
				$currentSize = 0;
				$requests[] = array();
				$currentRequest = &$requests[$requestsCount++];
			}
			$currentRequest[] = $src;
		}
		
		return $requests;
	}
	
	protected function _sendRequests ( ) {
		$requests = array();
		$groups = $this->_getSourceGroups();
		$return = '';
		
		foreach ( $groups as $group ) {
			$data = $this->_getParams($group);
			$referer = @$_SERVER['HTTP_REFERER'] or '';
			$fp = fsockopen('closure-compiler.appspot.com', 80) or die('Unable to open socket');;
		
			if ( $fp ) {
				fputs($fp, "POST /compile HTTP/1.1\r\n");
				fputs($fp, "Host: closure-compiler.appspot.com\r\n");
				fputs($fp, "Referer: $referer\r\n");
				fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
				fputs($fp, "Content-length: ". strlen($data) ."\r\n");
				fputs($fp, "Connection: close\r\n\r\n");
				fputs($fp, $data); 

				$result = ''; 
				while (!feof($fp)) {
					$result .= fgets($fp, 128);
				}
			
				fclose($fp);
			}
			else {
				throw new Exception('Could not connect to Google Closure Service');
			}

			$data = substr($result, (strpos($result, "\r\n\r\n")+4));
			if (strpos(strtolower($result), 'transfer-encoding: chunked') !== FALSE) {
				$data = $this->_unchunk($data);
			}
			
			$requests[] = $data;
		}
		
		return $requests;
	}

	protected function _performRequests ( ) {
		$return = '';
		$requests = $this->_sendRequests();
		
		foreach ( $requests as $request ) {
	    	$code = $originalSize = $originalGzipSize = $compressedSize = $compressedGzipSize = $compileTime = $warnings = $errors = '';
			$tree = $this->_parseXml($request);
			$result = $tree[0]['value'];
		    foreach ( $result as $node ) {
				switch ( $node['tag'] ) {
					case 'compiledCode':
						$code = $node['value'];
						break;
					
					case 'warnings':
						$warnings = $node['value'];
						break;
					
					case 'errors':
						$errors = $node['value'];
						break;
					
					case 'statistics':
						foreach ($node['value'] as $stat) {
							switch ($stat['tag']) {
								case 'originalSize':
								case 'originalGzipSize':
								case 'compressedSize':
								case 'compressedGzipSize':
								case 'compileTime':
									$var = $stat['tag'];
									$$var = $stat['value'];
									break;
							
								default:
									break;
							}
						}
						break;
				}
		    }
			
			if ( !$code && !$errors ) {
				$errors = 'Result is empty';
			}
			
			if ( $errors ) {
				throw new Exception('Errors occurred when using the Google Closure Service: '.$errors);
			}
		
			$return .= $code.' ';
		}
		
		return $return;
	}
	
	protected function _unchunk($data) {
		$fp = 0;
		$outData = '';
		while ($fp < strlen($data)) {
			$rawnum = substr($data, $fp, strpos(substr($data, $fp), "\r\n") + 2);
			$num = hexdec(trim($rawnum));
			$fp += strlen($rawnum);
			$chunk = substr($data, $fp, $num);
			$outData .= $chunk;
			$fp += strlen($chunk);
		}
		return $outData;
	}

	protected function _parseXml($data) {
		$data = str_replace('&lt;', '---LTLTLTLT---', $data);
		$xml = new XMLReader();
		$xml->xml($data);
		return $this->_parseXmlHelper($xml);
	}

	protected function _parseXmlHelper($xml) {
		$tree = null; 
		while( $xml->read() ) {
			switch ( $xml->nodeType ) { 
				case XMLReader::END_ELEMENT:
					return $tree; 
					
				case XMLReader::ELEMENT: 
					$node = array(
						'tag' => $xml->name,
						'value' => $xml->isEmptyElement ? '' : $this->_parseXmlHelper($xml)
					); 
					if ( $xml->hasAttributes ) {
						while ( $xml->moveToNextAttribute() ) {
							$node['attributes'][$xml->name] = str_replace('---LTLTLTLT---', '<', $xml->value);
						}
					}
					$tree[] = $node; 
					break; 
					
				case XMLReader::TEXT:
				case XMLReader::CDATA: 
					$tree .=  str_replace('---LTLTLTLT---', '<', $xml->value);
					break;
					
				default:
					throw new Exception('Unknown node type');
					break;
			}
		}
		return $tree;
	} 

}
