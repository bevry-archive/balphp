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
		$siteName = delve($applicationConfig, 'site.name', basename(APPLICATION_ROOT_PATH));
		
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
			if ( delve($args,'secret') !== delve($applicationConfig,'setup.secret') ) {
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
			
			# Install with default data
			case 'install':
				$ensure = array(
					'perm-init',
					'log-clean',
					'media-clean',
					'index-create',
					'data-model-clean',
					'data-schema-create',
					'data-model-create',
					'data-create',
					'data-import',
					'data-migrate-clean',
					'data-migrate-create',
					'index-update',
					'perm-secure'
				);
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: install ['.implode(array_keys($args),',').']'."\n";
				break;
			
			# Install with the dump data
			case 'install-dump':
				$ensure = array(
					'perm-init',
					'log-clean',
					'media-clean',
					'index-create',
					'data-model-clean',
					'data-schema-create',
					'data-model-create',
					'data-dump-use', // difference to install
					'data-create',
					'data-import',
					'data-migrate-clean',
					'data-migrate-create',
					'index-update',
					'perm-secure'
				);
				array_keys_ensure($args, $ensure, true);
				echo 'Setup: mode: install ['.implode(array_keys($args),',').']'."\n";
				break;
			
			# Install with the same data
			case 'update':
				$ensure = array(
					'perm-init',
					'data-model-clean',
					'data-schema-create',
					'data-model-create',
					'data-migrate-update',
					'data-migrate-install',
					'schema-create',
					'index-update',
					'perm-secure'
				);
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
		
		
		# Permissions: perm-init
		if ( delve($args,'perm-init') ) {
			echo '- [perm-init] -'."\n";
			echo 'Permissions: Initialise Permissions'."\n";
			# Run a Bunch of Command Line Stuff
			$cwd = APPLICATION_ROOT_PATH;
			$commands = array(
				"mkdir -p ".
					"$cwd/application/models/Base $cwd/application/data/dump ".
					"$cwd/application/data/logs $cwd/application/data/logs/payment ",
				// Standard Files
				"chmod -R 755 ".
					"$cwd ",
				// Writeable Files
				"chmod -R 777 ".
					"$cwd/application/data/dump $cwd/application/data/schema ".
					"$cwd/application/models $cwd/application/models/*.php $cwd/application/models/Base $cwd/application/models/Base/*.php ".
					"$cwd/public/media/deleted $cwd/public/media/images  $cwd/public/media/invoices $cwd/public/media/uploads ".
					"$cwd/application/data/logs $cwd/application/data/logs/payment ".
					"$cwd/application/config/application.ini ",
				// Executable Files
				"chmod +x ".
					"$cwd ".
					"$cwd/index.php ".
					"$cwd/application/models/*.php ".
					"$cwd/application/models/Base/*.php ".
					"$cwd/public/media/*.php ".
					"$cwd/scripts/*.php $cwd/scripts/setup $cwd/scripts/doctrine ",
			);
			$result = systems($commands);
		}
		
		
		# Clear: log-clean
		if ( delve($args,'log-clean') ) {
			echo '- [log-clean] -'."\n";
			echo 'Log: Cleaning Logs'."\n";
			# Delete the contents of media dirs; uploads and images
			$logs_path = LOGS_PATH;
	
			# Check
			if ( empty($logs_path) ) {
				throw new Zend_Exception('You must first create your logs paths');
			}
	
			# Scan directories
			$scan = scan_dir($logs_path,array('return_dirs'=>false));
	
			# Wipe files
			foreach ( $scan as $filepath => $filename ) {
				echo 'Clear: Deleted the file ['.$filepath.']'."\n";
				unlink($filepath);
			}
		}
		
		
		# Media: media-clean
		if ( delve($args,'media-clean') ) {
			echo '- [media-clean] -'."\n";
			echo 'Media: Cleaning Media'."\n";
			# Delete the contents of media dirs; uploads and images
			$images_path = IMAGES_PATH;
			$upload_path = UPLOADS_PATH;
	
			# Check
			if ( empty($images_path) ) {
				throw new Zend_Exception('You must first create your media paths');
			}
	
			# Scan directories
			$scan = scan_dir($images_path,array('return_dirs'=>false))+scan_dir($upload_path,array('return_dirs'=>false));
	
			# Wipe files
			foreach ( $scan as $filepath => $filename ) {
				echo 'Media: Deleted the file ['.$filepath.']'."\n";
				unlink($filepath);
			}
		}
		
		
		# Lucene
		$data_index_path = delve($applicationConfig,'data.index_path');
		$data_lucence = $data_index_path ? true : false;
		
		# Lucence: index-create
		if ( delve($args,'index-create') && $data_index_path ) {
			echo '- [index-create] -'."\n";
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
		$data_migrations_path = delve($applicationConfig,'data.migrations_path');
		$data_path_to_use = $data_fixtures_path;
		$data_yaml_schema_path = delve($applicationConfig,'data.yaml_schema_path');
		$data_yaml_schema_file_path = delve($applicationConfig,'data.yaml_schema_file_path');
		$data_yaml_schema_includes = delve($applicationConfig,'data.yaml_schema_includes');
		$data_models_path = delve($applicationConfig,'data.models_path');
		$data_models_generate = delve($applicationConfig,'data.models.generate',false);
		
		# Doctrine: data-compile
		if ( delve($args,'data-compile') && !empty($data_compile_generate) ) {
			$data_compile_drivers = delve($applicationConfig,'data.compile.drivers',array());
			$data_compile_path = delve($applicationConfig,'data.compile.path');
			echo '- [data-compile] -'."\n";
			echo 'Doctrine: Compiling Doctrine to ['.$data_compile_path.'] with drivers ['.implode(',',$data_compile_drivers).']'."\n";
			Doctrine_Core::compile($data_compile_path, $data_compile_drivers);
		}
		
		# Doctrine: data-model-clean
		if ( delve($args,'data-model-clean') ) {
			echo '- [data-model-clean] -'."\n";
			if ( $data_models_generate ) {
				echo 'Doctrine: Cleaning Models from Base directory'."\n";
				
				# Scan directory
				$scan = scan_dir($data_models_path.'/Base',array('return_dirs'=>false));
	
				# Wipe files
				foreach ( $scan as $filepath => $filename ) {
					echo 'Doctrine: Deleted the Base Model ['.$filepath.']'."\n";
					unlink($filepath);
				}
			} else {
				echo 'Doctrine: Cleaning Models Skipped...'."\n";
			}
		}
		
		# Doctrine: data-migrate-clean
		if ( delve($args,'data-migrate-clean') ) {
			echo '- [data-migrate-clean] -'."\n";
			echo 'Doctrine: Cleaning the Migrations ['.$data_migrations_path.']'."\n";
			# Scan directory
			$scan = scan_dir($data_migrations_path,array('return_dirs'=>false));
	
			# Wipe files
			foreach ( $scan as $filepath => $filename ) {
				echo 'Doctrine: Deleted the Migration File ['.$filepath.']'."\n";
				unlink($filepath);
			}
		}
		
		# Doctrine: data-schema-create
		if ( delve($args,'data-schema-create') ) {
			echo '- [data-schema-create] -'."\n";
			echo 'Doctrine: Regenerating YAML Schema ['.$data_yaml_schema_file_path.']'."\n";
			echo "\t".implode("\n\t",$data_yaml_schema_includes)."\n";
			$yaml_schema = '';
			foreach ( $data_yaml_schema_includes as $yaml_include ) {
				$yaml_schema .= file_get_contents($yaml_include)."\n\n# ^ $yaml_include\n\n";
			}
			file_put_contents($data_yaml_schema_file_path, $yaml_schema);
		}
		
		# Doctrine: data-dump-create
		if ( delve($args,'data-dump-create') ) {
			echo '- [data-dump-create] -'."\n";
			# Import Models
			$this->doctrineModels();
			# Overrides
			require_once ('Doctrine/Task/DumpData.php');
			# Perform the Dump
			echo 'Doctrine: Performing the Dump ['.$data_dump_path.']'."\n";
			Doctrine_Core::dumpData(
				$data_dump_path,
				false
			);
		}
		
		# Doctrine: data-dump-use
		if ( delve($args,'data-dump-use') ) {
			$data_path_to_use = $data_dump_path;
			echo '- [data-dump-use] -'."\n";
			echo 'Doctrine: Using the Dump Path ['.$data_path_to_use.']'."\n";
		}
		
		# Doctrine: data-model-create
		if ( delve($args,'data-model-create') ) {
			echo '- [data-model-create] -'."\n";
			# Check Generate Models
			if ( $data_models_generate ) {
				echo 'Doctrine: Generating Models...'."\n";
				# Importer
				$Import = new Doctrine_Import_Schema();
				$Import->setOptions(delve($applicationConfig,'data.models.options'));
				$Import->importSchema(
					$data_yaml_schema_path,
					'yml',
					$data_models_path
				);
				//non pear style: Doctrine_Core::generateModelsFromYaml($applicationConfig['data']['yaml_schema_path'],$applicationConfig['data']['models_path']);
			}
			else {
				echo 'Doctrine: Generate Models Skipped...'."\n";
			}
		}
		
		# Doctrine: data-create
		if ( delve($args,'data-create') ) {
			echo '- [data-create] -'."\n";
			# Reset Database
			echo 'Doctrine: Create Database...'."\n";
			Doctrine_Core::dropDatabases();
			Doctrine_Core::createDatabases();
		}
		
		# Doctrine: data-import
		if ( delve($args,'data-import') ) {
			echo '- [data-import] -'."\n";
			# Import Models
			$this->doctrineModels();
			# Create Tables
			echo 'Doctrine: Create Tables...'."\n";
			Doctrine_Core::createTablesFromModels();
			# Import Data to Database
			echo 'Doctrine: Import Data to Database...'."\n";
			Doctrine_Core::loadData(
				$data_path_to_use
			);
		}
		
		# Doctrine: data-migrate-create
		if ( delve($args,'data-migrate-create') ) {
			echo '- [data-migrate-create] -'."\n";
			# Initialise Migrations
			echo 'Doctrine: Creating Initial Migration...'."\n";
			$this->doctrineTask('GenerateMigrationsDb');
		}
		
		# Doctrine: data-migrate-update
		if ( delve($args,'data-migrate-update') ) {
			echo '- [data-migrate-update] -'."\n";
			# Update Migrations
			echo 'Doctrine: Update Migrations...'."\n";
			$this->doctrineTask('GenerateMigrationsDiff');
		}
		
		# Doctrine: data-migrate-install
		if ( delve($args,'data-migrate-install') ) {
			echo '- [data-migrate-install] -'."\n";
			# Perform Migrations
			echo 'Doctrine: Performing Migration ['.$data_migrations_path.']'."\n";
			Doctrine_Core::migrate($data_migrations_path);
		}
		
		# Lucene: index-update
		if ( delve($args,'index-update') && $data_lucence ) {
			echo '- [index-update] -'."\n";
			echo 'Lucene: Optimising the Lucene Index ['.$data_index_path.']'."\n";
			$Index = Zend_Registry::get('Index');
			$Index->optimize();
		}
		
		# Permissions: perm-secure
		if ( delve($args,'perm-secure') ) {
			echo '- [perm-secure] -'."\n";
			echo 'Permissions: Securing Permissions'."\n";
			# Run a Bunch of Command Line Stuff
			$cwd = APPLICATION_ROOT_PATH;
			$commands = array(
				// Writeable Files
				"chmod -R 755 ".
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
	
	public function doctrineModels ( ) {
		echo 'Doctrine: Load Models...'."\n";
		Doctrine_Core::loadModels(
			self::getConfig('data.models_path')
		);
	}
	
	public function doctrineTask ( $task, array $args = array() ) {
		# Prepare
		$Application = $this->getApplication();
		$this->bootstrapCli();
		
		# Prepare Config
		
		# Store Config for Cli and Copy some values to make b/c
		$doctrineConfig = self::getConfig('data');
		$doctrineConfig['data_dump_path'] = $doctrineConfig['dump_path'];
		$doctrineConfig['data_fixtures_path'] = $doctrineConfig['fixtures_path'];
		unset($doctrineConfig['generate_models']);
		
		# Load Cli
		$task_class = 'Doctrine_Task_'.$task;
		$Task = new $task_class();
        $Task->setArguments(array_merge($doctrineConfig,$args));
        if ( !$Task->validate() ) {
            throw new Doctrine_Cli_Exception('Required arguments missing');
        }
        $Task->execute();
		
		# Chain
		return $this;
	}
	
	public function doctrineCli ( ) {
		# Prepare
		$Application = $this->getApplication();
		$this->bootstrapCli();
		
		# Store Config for Cli and Copy some values to make b/c
		$doctrineConfig = self::getConfig('data');
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
	
	static public function getDispatcher ( ) {
		return self::getFrontController()->getDispatcher();
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
	
	static public function getActionControllerView ( ) {
		return self::getView();
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
	 * Get the Request
	 */
	static public function getRequest ( ) {
		return self::getFrontController()->getRequest();
	}
	
	/**
	 * Get the Response
	 */
	static public function getResponse ( ) {
		return self::getFrontController()->getResponse();
	}
	
	/**
	 * Determine and return the result of the desired param of $param
	 * If we could not find the param, then return $default
	 * @version 1.1, April 12, 2010
	 * @param string $param
	 * @param mixed $default
	 * @return mixed
	 */
	public static function fetchParam ( $param, $default = null ) {
		# Prepare
		$Request = self::getRequest();
		
		# Fetch result
		$result = fetch_param($param, $Request->getParam($param, $default));
		
		# Return result
		return $result;
	}
	
	/**
	 * Determines it the param exists
	 * @version 1.1, April 12, 2010
	 * @param string $param
	 * @return mixed
	 */
	public static function hasParam ( $param ) {
		# Prepare
		$Request = self::getRequest();
		
		# Fetch result
		$result = has_param($param) ? true : ($Request->getParam($param) !== null);
		
		# Return result
		return $result;
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
