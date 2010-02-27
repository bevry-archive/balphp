<?php

class Bal_Application extends Zend_Application {
	
    /**
     * Load configuration file of options
     *
     * @param  string $file
     * @throws Zend_Application_Exception When invalid configuration file is provided
     * @return array
     */
    protected function _loadConfig($file)
    {
		# Prepare
		$options = array();
		
		# Handle
		$configs = explode(PATH_SEPARATOR,$file);
		foreach ( $configs as $config ) {
			$options = $this->mergeOptions($options,parent::_loadConfig($config));
		}
		
		# Return options
		return $options;
    }


}