<?php

class Bal_App {
	
	protected $_Application;
	
	public function __construct ( Zend_Application $Application = null ) {
		if ( $Application )
			$this->setApplication($Application);
	}
	
    /**
     * Get the App Instance
     * @return Bal_App
     */
	public static function getInstance ( Zend_Application $Application = null ) {
		# Prepare
		$Registry = Zend_Registry::getInstance();
		# Apply
		if ( !$Registry->isRegistered('App') && $Application ) {
			$App = new Bal_App($Application);
			$Registry->set('App',$App);
		}
		# Return
		return $Registry->get('App');
	}
	
	public function setApplication ( Zend_Application $Application ) {
		# Apply
		$this->_Application = $Application;
		
		# Chain
		return $this;
	}
	
	public function getApplication ( ) {
		return $this->_Application;
	}
	
	protected function bootstrapCli ( ) {
		# Prepare CLI
		ini_set('html_errors', 0);
		ini_set('implicit_flush', 1);
		ini_set('max_execution_time', 0);
		ini_set('register_argc_argv', 1);
		ini_set('output_buffering', 0);
		ini_set('max_input_time', 0);
		
		# Prepare
		$this->bootstrap();
		
		# Chain
		return $this;
	}
	
	protected function bootstrap ( ) {
		# Prepare
		$Application = $this->getApplication();
		
		# Bootstrap
		$Application->bootstrap('config');
		$Application->bootstrap('mail'); // required for email
		$Application->bootstrap('log');
		$Application->bootstrap('doctrine');
		$Application->bootstrap('balphp');
		$Application->bootstrap('app');
		$Application->bootstrap('locale');
		$Application->bootstrap('presentation'); // required for messages
		
		# Custom Overwrites
		require_once ('Doctrine/Task/DumpData.php');
		
		# Chain
		return $this;
	}
	
	public function setup ( ) {
		# Prepare
		$Application = $this->getApplication();
		$this->bootstrap();
		
		# Prepare Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Prepare Arguments
		$cli = false;
		$args = array();
		if ( !empty($_GET) ) {
			$args =& $_GET;
		} elseif ( empty($_SERVER['HTTP_HOST']) ) {
			$cli = true;
			$args =& $_SERVER['argv'];
		}
		
		# Prepare
		if ( $cli ) {
			# Ensure Args
			if ( count($args) == 1 ) {
				$args['mode'] = readstdin('What would you like to do?', array('install','update'));
			}
		}
		else {
			# Prepare Headers
			header('Content-Type: text/plain');
			
			# Check Secret
			if ( delve($args,'secret') !== delve($applicationConfig,'bal.setup.secret') ) {
				throw new Zend_Exception('Trying to setup without the secret! Did we not tell you? Maybe it is for good reason!');
			}
		}
		
		# Handle
		$mode = delve($args,'mode');
		switch ( $mode ) {
			
			case 'install':
				$ensure = array('createindex', 'reload', 'optimiseindex', 'media');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: install ['.implode(array_keys($args),',').']'."\n";
				break;
			
			case 'update':
				$ensure = array('optimiseindex');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: update ['.implode(array_keys($args),',').']'."\n";
				break;
			
			case 'debug':
				$ensure = array('debug');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: debug ['.implode(array_keys($args),',').']'."\n";
				break;
			
			default:
				echo 'Setup: mode: normal ['.implode(array_keys($args),',').']'."\n";
				break;
		}
		
		
		# Debug: debug
		if ( !$cli && delve($args,'debug') ) {
			echo 'Debug: Enabling Debug Mode'."\n";
			setcookie('debug',DEBUG_SECRET,0,'/');
		}
		
		
		# Media: media
		if ( delve($args,'media') ) {
			echo '- [media] -'."\n";
			echo 'Media: Preparing Media'."\n";
			# Delete the contents of media dirs; uploads and images
			$images_path = IMAGES_PATH;
			$upload_path = UPLOADS_PATH;
	
			# Check
			if ( empty($images_path) ) {
				throw new Zend_Exception('You must first create your media paths');
			}
	
			# Scan directories
			$scan = scan_dir($images_path,null,null,$images_path.'/')+scan_dir($upload_path,null,null,$upload_path.'/');
	
			# Wipe files
			foreach ( $scan as $file ) {
				echo 'Media: Deleted the File ['.$file.']'."\n";
				unlink($file);
			}
		}
		
		
		# Lucene
		$data_index_path = delve($applicationConfig,'data.index_path');
		$data_lucence = $data_index_path ? true : false;
		
		# Lucence: createindex
		if ( delve($args,'createindex') && $data_index_path ) {
			echo '- [createindex] -'."\n";
			echo 'Lucene: Create Lucence Index ['.$data_index_path.']'."\n";
			$Index = Zend_Search_Lucene::create(
				$data_index_path
			);
			Zend_Registry::set('Index', $Index);
		} else {
			$Application->bootstrap('index');
		}
		
		
		# Doctrine
		$data_fixtures_path = delve($applicationConfig,'data.fixtures_path');
		$data_dump_path = delve($applicationConfig,'data.dump_path');
		$data_path_to_use = $data_fixtures_path;
		$data_yaml_schema_path = delve($applicationConfig,'data.yaml_schema_path');
		$data_models_path = delve($applicationConfig,'data.models_path');
		
		# Doctrine: usedump
		if ( delve($args,'usedump') ) {
			$data_path_to_use = $data_dump_path;
			echo '- [usedump] -'."\n";
			echo 'Doctrine: Using the Dump Path ['.$data_path_to_use.']'."\n";
		}
		
		# Doctrine: makedump
		if ( delve($args,'makedump') ) {
			echo '- [makedump] -'."\n";
			echo 'Doctrine: Performing the Dump ['.$data_dump_path.']'."\n";
			Doctrine::dumpData(
				$data_dump_path.'/data.yml',
				false
			);
		}
		
		# Doctrine: reload
		if ( delve($args,'reload') ) {
			echo '- [reload] -'."\n";
			echo 'Doctrine: Re-Installing the Database ['.$data_path_to_use.']'."\n";
			# Reset Database
			echo 'Doctrine: Reseting Database...'."\n";
			Doctrine::dropDatabases();
			Doctrine::createDatabases();
			# Check Generate Models
			if ( delve($applicationConfig,'data.generate_models',false) ) {
				echo 'Doctrine: Generating Models...'."\n";
				# Importer
				$Import = new Doctrine_Import_Schema();
				$Import->setOptions(delve($applicationConfig,'data.generate_models_options'));
				$Import->importSchema(
					$data_yaml_schema_path,
					'yml',
					$data_models_path
				);
				//non pear style: Doctrine::generateModelsFromYaml($applicationConfig['data']['yaml_schema_path'],$applicationConfig['data']['models_path']);
			}
			else {
				echo 'Doctrine: Generate Models Skipped...'."\n";
			}
			# Import Models
			echo 'Doctrine: Load Models...'."\n";
			Doctrine::loadModels(
				$data_models_path
			);
			# Create Tables
			echo 'Doctrine: Create Tables...'."\n";
			Doctrine::createTablesFromModels();
			# Import Data
			echo 'Doctrine: Import Data...'."\n";
			Doctrine::loadData(
				$data_path_to_use
			);
		}
		
		# Lucene: index
		if ( delve($args,'optimiseindex') && $data_lucence ) {
			echo '- [optimiseindex] -'."\n";
			echo 'Lucene: Optimising the Lucence Index ['.$data_index_path.']'."\n";
			$Index = Zend_Registry::get('Index');
			$Index->optimize();
		}
		
		# Done
		echo '-'."\n";
		echo 'Completed Setup.'."\n".'-'."\n".'Output Log:'."\n".Bal_Log::getInstance()->render();
		
		# Chain
		return $this;
	}
	
	public function doctrineCli ( ) {
		# Prepare
		$Application = $this->getApplication();
		$this->bootstrapCli();
		
		# Prepare Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Store Config for Cli and Copy some values to make b/c
		$doctrineConfig = $applicationConfig['data'];
		$doctrineConfig['data_dump_path'] = $doctrineConfig['dump_path'];
		$doctrineConfig['data_fixtures_path'] = $doctrineConfig['fixtures_path'];
		unset($doctrineConfig['generate_models']);
		
		# Load Cli
		$Formatter = new Doctrine_Cli_Formatter(); //Doctrine_Cli_AnsiColorFormatter();
		$Cli = new Doctrine_Cli($doctrineConfig, $Formatter);
		$Cli->run($_SERVER['argv']);
		
		# Chain
		return $this;
	}
	
}
