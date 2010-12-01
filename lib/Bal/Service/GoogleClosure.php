<?php
/**
 * http://code.google.com/p/php-closure/source/browse/trunk/php-closure.php
 */

class Bal_Service_GoogleClosure {
	
	/** The Default Compilation Level to Use **/
	protected $_compilationLevel = 'SIMPLE_OPTIMIZATIONS';
	
	/** The Default Warning Level to Use **/
	protected $_warningLevel = 'DEFAULT';
	
	/**
	 * Compile a series of Files to the Output Path using the Google closure Web Service
	 **/
	public function compile ( $files, $output_path ) {
		$compiled = $this->getResultFromFiles($files);
		file_put_contents($output_path,$compiled);
	}
	
	/**
	 * Takes a File Group to be sent, and converts it into a series of Request Groups.
	 * These Request Groups can then be sent to Google Closure
	 **/	 
	protected function generateRequestGroups ( $fileGroup ) {
		# Prepare
		$requestGroups = array();
		$requestGroup = array();
		$total_size = $file_size = 0;
		$max_size = 800*1024;
		
		# Split into Size Groups
		foreach ( $fileGroup as $file ) {
			$file_path = $file['path'];
			$file_size = filesize($file_path);
			$total_size += $file_size;
			
			# Reached the end of a request
			if ( $total_size > $max_size ) {
				# Append requestGroup to requestGroups
				$requestGroups[] = $requestGroup;
				
				# Reset with Current File
				$total_size = $file_size;
				$requestGroup = array();
			}
			
			# Add file to the request
			$requestGroup[] = $file;
		}
		
		# Append requestGroup to requestGroups
		$requestGroups[] = $requestGroup;
		
		# Return requestGroups
		return $requestGroups;
	}
	
	/**
	 * Send a Request Group to Google Closure
	 **/
	protected function sendRequestGroup ( $request_group  ) {
		# Perform Request
		$params = $this->generateParamsFromRequestGroup($request_group);
		$response = $this->sendRequestViaCurl($params);
		$result = $this->getResultFromResponse($response);
		
		# Return result
		return $result;
	}
	
	/**
	 * Send a File Group
	 **/
	protected function sendFileGroup ( $file_group ) {
		$result = '';
		
		$request_groups = $this->generateRequestGroups($file_group);
		foreach ( $request_groups as $request_group ) {
			$result .= ' '.$this->sendRequestGroup($request_group);
		}
		
		return $result;
	}
	
	/**
	 * Gets the Result by processing and sending the group of passed files
	 **/
	protected function getResultFromFiles ( $files ) {
		# Prepare
		$result = '';
		$group = array();
		
		# Combine
		foreach ( $files as $file ) {
			if ( $file['minified'] ) {
				$result .= ' '.$this->sendFileGroup($group); $group = array();
				$result .= ' '.file_get_contents($file['path']);
			}
			else {
				$group[] = $file;
			}
		}
		
		# Finish Up
		$result .= ' '.$this->sendFileGroup($group); $group = array();
		
		# Return result
		return $result;
	}
	
	/**
	 * Process the Response and Return the Result
	 */
	protected function getResultFromResponse ( $response ) {
		# Prepare
		$result = '';
	
		# Check Response
		if ( strpos($response, '<?xml') !== 0 ) {
			# Error
			throw new Bal_Exception(array(
				'Errors occurred when using the Google Closure Service.',
				'response' => $response
			));
		}
		
		# Process Response
		$responseTree = $this->parseXml($response);
		$responseValue = $responseTree[0]['value'];
		
		# Cycle through Responses
		foreach ( $responseValue as $node ) {
			# Reset Response
	    	$code = $originalSize = $originalGzipSize = $compressedSize = $compressedGzipSize = $compileTime = $warnings = $errors = '';

			# Handle Tags
			switch ( $node['tag'] ) {
				case 'compiledCode':
					$code = $node['value'];
					break;
				
				case 'warnings':
					$warnings = $node['value'];
					break;
				
				case 'serverErrors':
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
		    
			# Append the code
			if ( $code ) {
				$result .= ' '.$code;
			}
		}
	
		# No errors, but our result is empty - should be an error
		if ( !$result && !$errors && !$warnings  ) {
			$errors = 'Result is empty';
		}
		
		# We have an error
		if ( $errors ) {
			$error = $this->flattenResponseDetail($errors);
			$warning = $this->flattenResponseDetail($warnings);
				
			# Error
			throw new Bal_Exception(array(
				'Errors occurred when using the Google Closure Service.',
				'error' => $error,
				'warning' => $warning
			));
		}
		
		# Return result
		return $result;
	}
	
	/**
	 * Flatten a Response Detail
	 **/
	protected function flattenResponseDetail ( $errors ) {
		$error = '';
		if ( is_array($errors) ) {
			foreach ( $errors as $_error ) {
				$error .= $_error['value'];
			}
		}
		else {
			$error = $errors;
		}
		return $error;
	}
	
	/**
	 * Convert a Series of Params to a String ready for sending
	 **/
	protected function generateParamsString ( $params ) {
		# Prepare
		$parts = array();;
		
		# Convert Params to Parts
		foreach ( $params as $key => $value) {
			$parts[] = preg_replace('/_[0-9]$/', '', $key) . '=' . urlencode($value);
		}
		
		# Generate String
		$result = implode('&', $parts);
		
		# Return result
		return $result;
	}
	
	/**
	 * Generate a series of Params for Sending from the Group of Files
	 **/
	protected function generateParamsFromRequestGroup ( $request_group ) {
		# Prepare
		$params = array();
		$compilationLevel = $this->_compilationLevel;
		
		# Add Code
		$params['js_code'] = '';
		foreach ( $request_group as $file ) {
			$file_code = file_get_contents($file['path']);
			$params['js_code'] .= $file_code;
			if ( !empty($file['compilationlevel']) ) {
				$compilationLevel = $file['compilationlevel'];
			}
		}
		
		# Add Params
		$params['compilation_level'] = $compilationLevel;
		$params['output_format'] = 'xml';
		$params['warning_level'] = $this->_warningLevel;
		$params['use_closure_library'] = 'true';
		$params['output_info_1'] = 'compiled_code';
		$params['output_info_2'] = 'statistics';
		$params['output_info_3'] = 'warnings';
		$params['output_info_4'] = 'errors';
		
		# Return params
		return $params;
	}
	
	/**
	 * Send the Request via CURL
	 **/
	protected function sendRequestViaCurl ( $params ) {
		# Generate Params String
		$paramsString = $this->generateParamsString($params);
		
		# Generate Options
	    $options = array( 
	        CURLOPT_POST => 1, 
	        CURLOPT_HEADER => 0, 
	        CURLOPT_URL => 'closure-compiler.appspot.com/compile', 
	        CURLOPT_FRESH_CONNECT => 1, 
	        CURLOPT_RETURNTRANSFER => 1, 
	        CURLOPT_FORBID_REUSE => 1, 
	        CURLOPT_TIMEOUT => 60, 
	        CURLOPT_POSTFIELDS => $paramsString
	    ); 
		
		# Open
		$channel = curl_init();
	    curl_setopt_array($channel, $options);
	     
	    # Perform Request
	    if ( !$result = curl_exec($channel) ) { 
			throw new Exception('Could not connect to Google Closure Service: '.curl_error($channel));
	    } 
	    
	    # Close Channel
	    curl_close($channel);
	    
	    # Return result
	    return $result;
	}
	
	/**
	 * Parse a XML Response
	 **/
	protected function parseXml($data) {
		$data = str_replace('&lt;', '---LTLTLTLT---', $data);
		$xml = new XMLReader();
		$xml->xml($data);
		return $this->parseXmlHelper($xml);
	}

	protected function parseXmlHelper($xml) {
		$tree = null; 
		while( $xml->read() ) {
			switch ( $xml->nodeType ) { 
				case XMLReader::END_ELEMENT:
					return $tree; 
					
				case XMLReader::ELEMENT: 
					$node = array(
						'tag' => $xml->name,
						'value' => $xml->isEmptyElement ? '' : $this->parseXmlHelper($xml)
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
