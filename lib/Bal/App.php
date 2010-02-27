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
		if ( !$Registry->isRegistered('App') ) {
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
		$Application = $this->_Application;
		if ( empty($Application) ) {
			global $Application;
		}
		return $Application;
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
		
		# Chain
		return $this;
	}
	
	public function setup ( ) {
		# Prepare
		$Application = $this->getApplication();
		$this->bootstrap();
		
		# Prepare Config
		$applicationConfig = self::getConfig();
		$siteName = delve($applicationConfig, 'bal.site.name', basename(APPLICATION_ROOT_PATH));
		
		# Prepare Arguments
		$cli = false;
		$args = array();
		if ( !empty($_GET) ) {
			$args =& $_GET;
		} elseif ( empty($_SERVER['HTTP_HOST']) ) {
			$cli = true;
			$args =& $_SERVER['argv'];
		}
		
		# Prepare Non-Cli
		if ( !$cli ) {
			# Prepare Headers
			header('Content-Type: text/plain');
			
			# Check Secret
			if ( delve($args,'secret') !== delve($applicationConfig,'bal.setup.secret') ) {
				throw new Zend_Exception('Trying to setup without the secret! Did we not tell you? Maybe it is for good reason!');
			}
		}
			
		# Intro
		echo 'Welcome to ['.$siteName.']'."\n";
		
		# Prepare Cli
		if ( $cli ) {
			# Ensure Args
			$argc = count($args);
			if ( $argc == 1 ) {
				# Read Mode
				$args['mode'] = readstdin('What would you like to do?', array('install','update','cancel','custom ...'), false);
			} else {
				# Use Custom + Additional? Modes
				$modes = $args; array_shift($modes);
				$args['mode'] = array_shift($modes);
				foreach ( $modes as $mode ) {
					$args[$mode] = true;
				}
				unset($modes);
			}
		}
		
		# Handle
		$mode = delve($args,'mode');
		switch ( $mode ) {
			
			case 'install':
				$ensure = array('createindex', 'cleanmodels', 'regenschema', 'reload', 'optimiseindex', 'media', 'permissions', 'resecure');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: install ['.implode(array_keys($args),',').']'."\n";
				break;
			
			case 'install-dump':
				$ensure = array('createindex', 'cleanmodels', 'usedump', 'regenschema', 'reload', 'optimiseindex', 'media', 'permissions', 'rescure');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: install ['.implode(array_keys($args),',').']'."\n";
				break;
			
			case 'refresh':
				$ensure = array('createindex', 'usedump', 'makedump', 'regenschema', 'reload', 'optimiseindex', 'permissions', 'rescure');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: reload ['.implode(array_keys($args),',').']'."\n";
				break;
				
			case 'update':
				$ensure = array('optimiseindex');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: update ['.implode(array_keys($args),',').']'."\n";
				break;
			
			case 'cancel':
			case null:
				echo 'Setup has been cancelled.'."\n\n";
				return;
				break;
			
			case 'custom':
				echo 'Setup: mode: custom ['.implode(array_keys($args),',').']'."\n";
				break;
				
			default:
				$ensure = compact('mode');
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: default ['.implode(array_keys($args),',').']'."\n";
				break;
		}
		
		
		# Debug: debug
		if ( !$cli && delve($args,'debug') ) {
			echo 'Debug: Enabling Debug Mode'."\n";
			setcookie('debug',DEBUG_SECRET,0,'/');
		}
		
		
		# Permissions: permissions
		if ( delve($args,'permissions') ) {
			echo '- [permissions] -'."\n";
			echo 'Permissions: Setting up Permissions'."\n";
			# Run a Bunch of Command Line Stuff
			$cwd = APPLICATION_ROOT_PATH;
			$commands = array(
				"mkdir -p ".
					"$cwd/application/models/Base $cwd/application/data/dump ".
					"$cwd/scripts/paypal/logs ",
				// Standard Files
				"sudo chmod -R 755 ".
					"$cwd ",
				// Writeable Files
				"sudo chmod -R 777 ".
					"$cwd/application/data/dump $cwd/application/data/schema ".
					"$cwd/application/models $cwd/application/models/*.php $cwd/application/models/Base $cwd/application/models/Base/*.php ".
					"$cwd/public/media/deleted $cwd/public/media/images  $cwd/public/media/invoices $cwd/public/media/uploads ".
					"$cwd/scripts/paypal/logs ".
					"$cwd/application/config/application.ini ",
				// Executable Files
				"sudo chmod +x ".
					"$cwd ".
					"$cwd/index.php ".
					"$cwd/application/models/*.php ".
					"$cwd/application/models/Base/*.php ".
					"$cwd/public/media/*.php ".
					"$cwd/scripts/*.php $cwd/scripts/setup $cwd/scripts/doctrine ",
			);
			$result = systems($commands);
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
			$scan = scan_dir($images_path)+scan_dir($upload_path);
	
			# Wipe files
			foreach ( $scan as $filepath => $filename ) {
				echo 'Media: Deleted the File ['.$filepath.']'."\n";
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
		$data_compile_generate = delve($applicationConfig,'data.compile.generate',false);
		$data_fixtures_path = delve($applicationConfig,'data.fixtures_path');
		$data_dump_path = delve($applicationConfig,'data.dump_path');
		$data_path_to_use = $data_fixtures_path;
		$data_yaml_schema_path = delve($applicationConfig,'data.yaml_schema_path');
		$data_yaml_schema_file_path = delve($applicationConfig,'data.yaml_schema_file_path');
		$data_yaml_schema_includes = delve($applicationConfig,'data.yaml_schema_includes');
		$data_models_path = delve($applicationConfig,'data.models_path');
		
		# Doctrine: compile
		if ( delve($args,'compile') && !empty($data_compile_generate) ) {
			$data_compile_drivers = delve($applicationConfig,'data.compile.drivers',array());
			$data_compile_path = delve($applicationConfig,'data.compile.path');
			echo '- [compile] -'."\n";
			echo 'Doctrine: Compiling Doctrine to ['.$data_compile_path.'] with drivers ['.implode(',',$data_compile_drivers).']'."\n";
			Doctrine::compile($data_compile_path, $data_compile_drivers);
		}
		
		# Doctrine: cleanmodels
		if ( delve($args,'cleanmodels') ) {
			echo '- [cleanmodels] -'."\n";
			echo 'Doctrine: Cleaning models from Base directory'."\n";
			
			# Scan directory
			$scan = scan_dir($data_models_path.'/Base');
	
			# Wipe files
			foreach ( $scan as $filepath => $filename ) {
				echo 'Doctrine: Deleted the Base Model ['.$filepath.']'."\n";
				unlink($filepath);
			}
		}
		
		# Doctrine: usedump
		if ( delve($args,'usedump') ) {
			$data_path_to_use = $data_dump_path;
			echo '- [usedump] -'."\n";
			echo 'Doctrine: Using the Dump Path ['.$data_path_to_use.']'."\n";
		}
		
		# Doctrine: makedump
		if ( delve($args,'makedump') ) {
			echo '- [makedump] -'."\n";
			# Import Models
			echo 'Doctrine: Load Models...'."\n";
			Doctrine::loadModels(
				$data_models_path
			);
			# Overrides
			require_once ('Doctrine/Task/DumpData.php');
			# Perform the Dump
			echo 'Doctrine: Performing the Dump ['.$data_dump_path.']'."\n";
			Doctrine::dumpData(
				$data_dump_path,
				false
			);
		}
		
		# Doctrine: regenschema
		if ( delve($args,'regenschema') ) {
			echo '- [regenschema] -'."\n";
			echo 'Doctrine: Regenerating YAML Schema ['.$data_yaml_schema_file_path.']'."\n";
			echo "\t".implode("\n\t",$data_yaml_schema_includes)."\n";
			$yaml_schema = '';
			foreach ( $data_yaml_schema_includes as $yaml_include ) {
				$yaml_schema .= file_get_contents($yaml_include)."\n\n# ^ $yaml_include\n\n";
			}
			file_put_contents($data_yaml_schema_file_path, $yaml_schema);
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
			if ( delve($applicationConfig,'data.models.generate',false) ) {
				echo 'Doctrine: Generating Models...'."\n";
				# Importer
				$Import = new Doctrine_Import_Schema();
				$Import->setOptions(delve($applicationConfig,'data.models.options'));
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
		
		
		# Permissions: permissions
		if ( delve($args,'resecure') ) {
			echo '- [resecure] -'."\n";
			echo 'Permissions: Re-Securing Permissions'."\n";
			# Run a Bunch of Command Line Stuff
			$cwd = APPLICATION_ROOT_PATH;
			$commands = array(
				// Writeable Files
				"sudo chmod -R 755 ".
					"$cwd/application/data/dump $cwd/application/data/schema ".
					"$cwd/application/models $cwd/application/models/*.php $cwd/application/models/Base $cwd/application/models/Base/*.php "
					,
			);
			$result = systems($commands);
		}
		
		# Done
		echo '-'."\n";
		echo 'Completed Setup.'."\n".'-'."\n".'Output Log:'."\n".Bal_Log::getInstance()->render()."\n\n";
		
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
	
	static public function setDataConnection ( $Doctrine_Connection ) {
		return Zend_Registry::set('Doctrine_Connection', $Doctrine_Connection);
	}
	static public function getDataConnection ( ) {
		return Zend_Registry::get('Doctrine_Connection');
	}
	
	static public function setDataManager ( $Doctrine_Manager ) {
		return Zend_Registry::set('Doctrine_Manager', $Doctrine_Manager);
	}
	static public function getDataManager ( ) {
		return Zend_Registry::get('Doctrine_Manager');
	}
	
	static public function setLocale ( $Locale ) {
		return Zend_Registry::set('Locale', $Locale);
	}
	static public function getLocale ( ) {
		return Zend_Registry::get('Locale');
	}
	
	static public function setLog ( $Log ) {
		return Zend_Registry::set('Log', $Log);
	}
	static public function getLog ( ) {
		return Zend_Registry::get('Log');
	}
	
	static public function getBootstrap ( ) {
		return self::getInstance()->getApplication()->getBootstrap();
	}
	
	static public function getPlugin ( $plugin ) {
		return self::getFrontController()->getPlugin($plugin);
	}
	
	static public function getViewHelper ( $name ) {
    	return self::getView()->getHelper($name);
	}
	
	static public function getExistingActionHelper ( $name ) {
    	return Zend_Controller_Action_HelperBroker::hasHelper($name) ? Zend_Controller_Action_HelperBroker::getExistingHelper($name) : null;
	}
	
	static public function getStaticActionHelper ( $name ) {
    	return Zend_Controller_Action_HelperBroker::getStaticHelper($name);
	}
	
	static public function getSessionNamespace ( $name ) {
		return new Zend_Session_Namespace($name);
	}
	
	static public function getView ( $clone = false ) {
		# Prepare
		$View = null;
		$Bootstrap = self::getBootstrap();
		
		# Find
		if ( $Bootstrap->hasResource('view') && is_object($View = $Bootstrap->getResource('view')) && method_exists($View,'getScriptPaths') && ($tmp = $View->getScriptPaths()) && !empty($tmp) ) {
			// We can send mail
			$View = $clone ? clone $View : $View;
		}
		
		# Done
		return $View;
	}
	
	/**
	 * Get the Front Controller
	 */
	static public function getFrontController ( ) {
		return Zend_Controller_Front::getInstance();
	}
	
	/**
	 * Get the Front Controller's Router
	 */
	static public function getRouter ( ) {
		return self::getFrontController()->getRouter();
	}
	
	/**
	 * Gets the Application Configuration (as array) or specific config variable
	 * @param string $delve [optional]
	 * @param mixed $default [optional]
	 * @return mixed
	 */
	static public function getConfig ( $delve = null, $default = null ) {
		# Prepare:
		$applicationConfig = array();
		
		# Load
		if ( Zend_Registry::isRegistered('applicationConfig') ) {
			$applicationConfig = Zend_Registry::get('applicationConfig');
		}
		
		# Check
		if ( !$delve ) {
			return $applicationConfig;
		}
		
		# Delve
		$value = delve($applicationConfig, $delve, null);
		
		# Check
		if ( !$value ) {
			# Check Constant
			$const = strtoupper(str_replace('.','_',$delve));
			if ( defined($const) ) {
				$value = constant($const);
			} else {
				$value = $default;
			}
		}
		
		# Done
		return $value;
	}
	
}
