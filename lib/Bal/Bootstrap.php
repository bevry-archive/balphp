<?php

class Bal_Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	/**
	 * Initialise our Locale
	 * @return
	 */
	protected function _initLocale ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('balphp');
		
		# Set Local Cache
		if ( is_dir(CACHE_LOCALE_PATH) ) {
			$Cache = Zend_Cache::factory(
				'Core',
				'File',
				array(
					'lifetime' => 120,
					'automatic_serialization' => true
				),
				array(
					'cache_dir' => CACHE_LOCALE_PATH
				)
			);
			Zend_Currency::setCache($Cache);
		}
		
		# Locale
		$Locale = new Bal_Locale($this->getOption('locale'));
		
		# Return true
		return true;
	}

	/**
	 * Initialise our Mail
	 * @return
	 */
	protected function _initMail ( ) {
		# Prepare
		$this->bootstrap('config');
		$this->bootstrap('balphp');
		
		# Load Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Prepare Options
		$use_mail = delve($applicationConfig, 'mail.send_email', true);
		if ( !$use_mail ) {
			return false;
		} elseif ( DEBUG_MODE && !is_connected() ) {
			return false;
		}
		
		# Fetch
		$smtp_host = delve($applicationConfig, 'mail.transport.smtp.host', 'localhost');
		$smtp_config = delve($applicationConfig, 'mail.transport.smtp.config');
		if ( empty($smtp_config) )
			$smtp_config = array();
		
		# Apply
		$Transport = new Zend_Mail_Transport_Smtp($smtp_host, $smtp_config);
		Zend_Mail::setDefaultTransport($Transport);
		
		# Return true
		return true;
	}

	/**
	 * Initialise our Log
	 * @return
	 */
	protected function _initLog ( ) {
		# Prepare Loads
		$this->bootstrap('config');
		$this->bootstrap('autoload');
		$this->bootstrap('balphp');
		
		# Load Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Prepare Options
		$use_mail = delve($applicationConfig, 'mail.send_email', true);
		$friendly = delve($applicationConfig, 'error.friendly', true) || DEBUG_MODE;
		if ( $use_mail ) $this->bootstrap('mail');
		
		# Create Log
		$Log = new Bal_Log();
		Zend_Registry::set('Log', $Log);
		
		# Default Writer
		$Writer_Rich = new Bal_Log_Writer_Rich();
		//$Writer_Rich->setFormatter(new Zend_Log_Formatter_Simple('%message%'));
		$Writer_Rich->isFriendly($friendly);
		$Log->setRenderWriter($Writer_Rich);
		
		# Create Writer: SysLog
		//$Writer_Syslog = new Zend_Log_Writer_Syslog();
		//$Log->addWriter($Writer_Syslog);
		
		# Create Writer: Firebug
		if ( DEBUG_MODE ) {
			//$Writer_Firebug = new Zend_Log_Writer_Firebug();
		//$Log->addWriter($Writer_Firebug);
		}
		
		# Check if we are online so that we may send the log via email
		if ( $use_mail ) {
			# Mail
			$mail = $applicationConfig['mail'];
			$Mail = new Zend_Mail();
			$Mail->setFrom($mail['from']['address'], $mail['from']['name']);
			$Mail->addTo($mail['log']['address'], $mail['log']['name']);
		
			# Create Writer: Email
			$Writer_Mail = new Zend_Log_Writer_Mail($Mail);
			$Writer_Mail->setSubjectPrependText('Error Log');
			$Writer_Mail->addFilter(Zend_Log::CRIT);
			$Log->addWriter($Writer_Mail);
		}
		
		# Return true
		return true;
	}

	/**
	 * Initialise our View
	 * @return
	 */
	protected function _initView ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		
		# Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Initialize view
		$View = new Zend_View();
		$View->doctype('HTML5');
		$View->headMeta()->setHttpEquiv('Content-Type', 'text/html; charset=utf-8');
		
		# Customise View
		$View->headTitle($applicationConfig['site']['title'])
			->setSeparator($applicationConfig['site']['separator']);
		$View->headMeta()
			->setHttpEquiv('Content-Type', 'text/html; charset=utf-8')
			->appendName('author', $applicationConfig['site']['author']['title'])
			->appendName('generator', $applicationConfig['site']['generator'])
			->appendName('description', $applicationConfig['site']['description'])
			->appendName('keywords', $applicationConfig['site']['keywords']);
		
		# Add it to the ViewRenderer
		$ViewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$ViewRenderer->setView($View);
		
		# Return View
		return $View;
	}

	/**
	 * Initialise our Presentation
	 * @return
	 */
	protected function _initPresentation ( ) {
		# Prepare
		$this->bootstrap('view');
		$this->bootstrap('config');
		$this->bootstrap('app');
		$View = $this->getResource('view');
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Layout
		$this->bootstrap('frontController');
		$FrontController = Zend_Controller_Front::getInstance();
		$App = $FrontController->getPlugin('Bal_Controller_Plugin_App');
		$App->startMvc();
		
		# View Helpers
		$View->addHelperPath(BALPHP_PATH . '/Bal/View/Helper', 'Bal_View_Helper');
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our routes/routing/router
	 * @return
	 */
	protected function _initRoutes ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$routeConfig = null;
		
		# Read Configuration
		if ( is_readable(ROUTES_COMPILED_FILE_PATH) && filemtime(ROUTES_COMPILED_FILE_PATH) > filemtime(ROUTES_FILE_PATH) ) {
			$routeConfig = unserialize(file_get_contents(ROUTES_COMPILED_FILE_PATH));
		}
		if ( !$routeConfig ) {
			$routeConfig = sfYaml::load(ROUTES_FILE_PATH);
			file_put_contents(ROUTES_COMPILED_FILE_PATH, serialize($routeConfig));
		}
		
		# Convert to Zend Config
		$routeConfig = new Zend_Config($routeConfig[APPLICATION_ENV]);
		
		# Route
		$FrontController = Zend_Controller_Front::getInstance();
		if ( defined('BASE_URL') ) {
			$FrontController->setBaseUrl(BASE_URL);
		} else {
			define('BASE_URL', rtrim($FrontController->getBaseUrl(), '/'));
		}
		$router = $FrontController->getRouter();
		$router->removeDefaultRoutes();
		
		# Apply
		$router->addConfig($routeConfig, 'routes');
		
		# Location
		# $resources = $this->getOption('resources');
		# $FrontController->addModuleDirectory($resources['frontController']['moduleDirectory']);
		

		# Return true
		return true;
	}

	/**
	 * Initialise Zend's Autoloader, used for plugins etc
	 * +CU (Doctrine Forms)
	 * @return
	 */
	protected function _initAutoload ( ) {
	$_args = func_get_args(); Bootstrapr::log(__FILE__,__LINE__,__CLASS__,__FUNCTION__,$_args); unset($_args);
		# Initialise Zend's Autoloader, used for plugins etc
		$Autoloader = Zend_Loader_Autoloader::getInstance();
		$Autoloader->registerNamespace('Bal_');
		
		# Action Controllers
		Zend_Controller_Action_HelperBroker::addPrefix('Bal_Controller_Action_Helper_');
		
		# Return Autoloader
		return $Autoloader;
	}

	/**
	 * Initialise Lucence Index
	 * @return
	 */
	protected function _initIndex ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		
		# Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# Check
		if ( empty($applicationConfig['data']['index_path']) ) {
			return true;
		}
		
		# Initialise
		$Index = Zend_Search_Lucene::create($applicationConfig['data']['index_path']);
		Zend_Registry::set('Index', $Index);
		
		# Return true
		return true;
	}

	/**
	 * Initialise our Config
	 * @return array
	 */
	protected function _initConfig ( ) {
		# Prepare
		$this->bootstrap('autoload');
		
		# Load
		if ( !Zend_Registry::isRegistered('applicationConfig') ) {
			$applicationConfig = $this->getOptions();
			Zend_Registry::set('applicationConfig', $applicationConfig);
		}
		
		# Return true
		return true;
	}

	/**
	 * Initialise our Defaults
	 * @return
	 */
	protected function _initDefaults ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		
		# Load Front Controller
		$FrontController = Zend_Controller_Front::getInstance();
		
		# Apply
		$FrontController->setDefaultControllerName('front')->setDefaultAction('index');
		
		# Error Handler
		$FrontController = Zend_Controller_Front::getInstance();
		$FrontController->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(array('module' => 'default', 'controller' => 'error', 'action' => 'error')));
		
		# Exceptions
		$applicationConfig = Zend_Registry::get('applicationConfig');
		$throw_exceptions = $applicationConfig['error']['throw'];
		$FrontController->throwExceptions($throw_exceptions);
		
		# Module Specific Error Controllers
		# $FrontController->registerPlugin(new Bal_Controller_Plugin_ErrorControllerSelector());
		
		# Return true
		return true;
	}

	protected function _initApp ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		$this->bootstrap('routes');
		$this->bootstrap('balphp');
		$this->bootstrap('mail'); // we require mailing in case something goes wrong
		$this->bootstrap('log'); // we require logging in various areas
		
		
		# Load
		$FrontController = Zend_Controller_Front::getInstance();
		
		# Register URL Plugin
		if ( !$FrontController->hasPlugin('Bal_Controller_Plugin_Url') ) {
			
			# Create
			$App = new Bal_Controller_Plugin_Url();
			
			# Register
			$FrontController->registerPlugin($App);
		}
		
		# Register App Plugin
		if ( !$FrontController->hasPlugin('Bal_Controller_Plugin_App') ) {
			
			# Create
			$App = new Bal_Controller_Plugin_App();
			
			# Configure
			$applicationConfig = $App->getConfig();
			$appConfig = delve($applicationConfig, 'app', array());
			$App->mergeOptions($appConfig);
			
			# Register
			$FrontController->registerPlugin($App);
		}
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our Data.
	 * Options: +VALIDATE_ALL
	 * @return
	 */
	protected function _initData ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		
		# Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		$compile_use = $applicationConfig['data']['compile']['use'];
		$compile_path = $applicationConfig['data']['compile']['path'];
		
		# Autoload
		if ( $compile_use && file_exists($compile_path) ) {
			require_once($compile_path);
		}
		
		# Doctrine
		$this->bootstrap('doctrine');
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our Doctrine ORM.
	 * Options: +VALIDATE_ALL
	 * @return
	 */
	protected function _initDoctrine ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		$this->bootstrap('balphp');
		$Autoloader = Zend_Loader_Autoloader::getInstance();
		
		# Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		$dsn = $applicationConfig['data']['connection_string'];
		$extensions_path = $applicationConfig['data']['extensions_path'];
		$models_path = $applicationConfig['data']['models_path'];
		$autoloader = $applicationConfig['data']['autoloader'];
		$baseClassPrefix = $applicationConfig['data']['models']['options']['baseClassPrefix'];
		$compile_use = $applicationConfig['data']['compile']['use'];
		$cache_dsn = $applicationConfig['data']['cache_dsn'];
		$cache_lifespan = $applicationConfig['data']['cache_lifespan'];
		
		# Load
		if ( !$compile_use ) {
			require_once(DOCTRINE_PATH . DIRECTORY_SEPARATOR . 'Doctrine.php');
			if ( !class_exists('sfYaml') ) {
				require_once(implode(DIRECTORY_SEPARATOR, array(DOCTRINE_PATH, 'Doctrine', 'Parser', 'sfYaml', 'sfYaml.php')));
			}
			$Autoloader->pushAutoloader(array('Doctrine', 'autoload'), 'Doctrine_');
			//$Autoloader->pushAutoloader(array('Doctrine', 'autoload'), 'sfYaml');
		}
		
		# Overides
		Bal_Framework::import(array('Doctrine'));
		
		# Autoload Models
		if ( true ) {
			# Autoload Models
			$Autoloader->pushAutoloader(array('Doctrine', 'modelsAutoload'));
			# Autoload Namespaces
			//$model_dirs = scan_dir($models_path, array('skip_files'=>true,'recurse'=>false));
			//foreach ( $model_dirs as $model_dir_path => $model_dir_filename ) {
			//	$Autoloader->registerNamespace($model_dir_filename.'_');
			//}
		}
		else {
			# Autoload PEAR Models
			$model_dirs = scan_dir($models_path, array('skip_files'=>true,'recurse'=>false));
			foreach ( $model_dirs as $model_dir_path => $model_dir_filename ) {
				$resourceLoader = new Zend_Loader_Autoloader_Resource(array(
					'basePath'  => $model_dir_path,
					'namespace' => $model_dir_filename,
				));
				$Autoloader->pushAutoloader($resourceLoader);
			}
			# Load Standard Models
			$model_files = scan_dir($models_path, array('skip_dirs'=>true,'recurse'=>false));
			foreach ( $model_files as $model_file_path => $model_file_filename ) {
				require_once($model_file_path);
			}
		}
		
		# Autoload Extensions
		$Autoloader->pushAutoloader(array('Doctrine', 'extensionsAutoload'));
		
		# Override Generator - Fixes Pear Style Record Generators
		if ( !$compile_use ) {
			require_once(implode(DIRECTORY_SEPARATOR, array(BALPHP_PATH, 'Doctrine', 'Record', 'Generator.php')));
		}
		
		# Apply Paths
		Doctrine_Core::setPath(DOCTRINE_PATH);
		Doctrine_Core::setExtensionsPath($extensions_path);
		Doctrine_Core::setModelsDirectory($models_path);
		
		# Get Manager
		$Manager = Doctrine_Manager::getInstance();
		
		# Use Cache?
		if ( $cache_dsn ) {
			# Open Cache Connection
			$CacheConnection = $Manager->openConnection('sqlite:///'.CACHE_PATH.'/cache.db');
			
			# Apply Query Cache
			$QueryCacheDriver = new Doctrine_Cache_Db(array('connection' => $CacheConnection, 'tableName' =>'query'));
			try { $QueryCacheDriver->createTable(); } catch ( Exception $Exception ) { }
			$Manager->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $QueryCacheDriver);
			
			# Apply Result Cache
			$ResultCacheDriver = new Doctrine_Cache_Db(array('connection' => $CacheConnection, 'tableName' =>'result'));
			try { $ResultCacheDriver->createTable(); } catch ( Exception $Exception ) { }
			$Manager->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE, $ResultCacheDriver);
			$Manager->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE_LIFESPAN, $cache_lifespan);
			
		}
		
		# Apply Config
		$Manager->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_EMPTY_TO_NULL | Doctrine_Core::PORTABILITY_RTRIM);
		$Manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
		$Manager->setAttribute(Doctrine_Core::ATTR_VALIDATE, Doctrine_Core::VALIDATE_ALL);
		$Manager->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);
		
		# Apply Extensions
		$Manager->registerExtension('Taggable');
		
		# Cache
		//$cacheConn = Doctrine_Manager::connection(new PDO('sqlite::memory:'));
		//$cacheDriver = new Doctrine_Cache_Db(array('connection' => $cacheConn,'tableName' => 'cache'));
		//$manager->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
		//$manager->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);
		

		# Prepare MySQL Connection by Attaching Socket to DSN
		if ( strstr($dsn,'mysql') && !strstr($dsn,'unix_socket=') ) {
			$unix_socket = ini_get('mysql.default_socket');
			if ( $unix_socket ) {
				$dsn .= ';unix_socket=' . $unix_socket;
			}
		}
		
		# Create Connection
		$Connection = $Manager->openConnection($dsn);
		
		# Profile Connection
		if ( PROFILE_MODE ) {
			$Profiler = new Doctrine_Connection_Profiler();
			$Connection->setListener($Profiler);
			Zend_Registry::set('Profiler', $Profiler);
		}
		
		# Transaction
		//$Connection->beginTransaction();
		
		# Store
		Bal_App::setDataConnection($Connection);
		Bal_App::setDataManager($Manager);
		
		# Return Manager
		return $Manager;
	}
	
	/**
	 * Initialise our Doctrine Listeners.
	 * @return
	 */
	protected function _initDoctrineListeners ( ) {
		# Prepare
		$this->bootstrap('autoload');
		$this->bootstrap('config');
		$this->bootstrap('doctrine');
		
		# Config
		$applicationConfig = Zend_Registry::get('applicationConfig');
		$models_path = $applicationConfig['data']['models_path'];
		$base_path = MODELS_PATH.DIRECTORY_SEPARATOR.'Base';
		
		# Apply Listener To Tables - Ensure it will run
		if ( true  ) {
			$models = scan_dir($base_path, array('return_dirs'=>false,'skip_dirs'=>true));
			foreach ( $models as $model_path => $model_filename ) {
				$class_name = rstrip($model_filename,'.php');
				Doctrine_Core::getTable($class_name)->addRecordListener(new Bal_Doctrine_Record_Listener_Html(false));
			}
		}
		elseif ( false ) {
			$Manager = Bal_App::getDataManager();
			Doctrine_Core::loadModels($base_path);
			$models = Doctrine_Core::getLoadedModelFiles();
			foreach ( $models as $tableName => $modelPath ) {
				Doctrine_Core::getTable($tableName)->addRecordListener(new Bal_Doctrine_Record_Listener_Html(false));
			}
			$Manager->addRecordListener(new Bal_Doctrine_Record_Listener_Html(false));
		}
		elseif ( false ) {
			$Manager = Bal_App::getDataManager();
			$models = Doctrine_Core::getLoadedModelFiles();
			foreach ( $models as $tableName => $modelPath ) {
				Doctrine_Core::getTable($tableName)->addRecordListener(new Bal_Doctrine_Record_Listener_Html(false));
			}
			$Manager->addRecordListener(new Bal_Doctrine_Record_Listener_Html(false));
		}
		
		# Return true
		return true;
	}

	/**
	 * Initialise our balPHP Library
	 * @return
	 */
	protected function _initBalphp ( ) {
		# Prepare
		$this->bootstrap('autoload');
		global $Application;
		
		# balPHP
		Bal_Framework::import(array('core','Zend'));
		
		# Params
		hydrate_request_init();
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our balcms module
	 * @return
	 */
	protected function _initBalcms ( ) {
		# Prepare
		$this->bootstrap('presentation');
		$View = $this->getResource('view');
		$applicationConfig = Zend_Registry::get('applicationConfig');
		
		# View Helpers
		$View->addHelperPath(APPLICATION_PATH.'/modules/balcms/views/helpers', 'Balcms_View_Helper');
		$View->addScriptPath(APPLICATION_PATH.'/modules/balcms/views/scripts');
		
		# Widgets
		if ( array_key_exists('widgets', $applicationConfig) )
		$View->getHelper('widget')->addWidgets($applicationConfig['widgets']['widget']);
		
		# Return true
		return true;
	}

	/**
	 * Initialise our modules
	 * @return
	 */
	protected function _initModules ( ) {
		# Bootstrap
		$this->bootstrap('balcms');
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our setup script
	 * @return
	 */
	protected function _initScriptSetup ( ) {
		# Bootstrap
		$this->bootstrap('locale');
		$this->bootstrap('modules');
				
		# Return true
		return true;
	}
	
	/**
	 * Initialise our cron script
	 * @return
	 */
	protected function _initScriptCron ( ) {
		# Bootstrap
		$this->bootstrap('locale');
		$this->bootstrap('modules');
		$this->bootstrap('DoctrineListeners');
		$this->bootstrap('frontController');
		$FrontController = $this->getResource('frontController');
		if ( $FrontController->getRequest() === null ) {
			$Request = new Zend_Controller_Request_Http();
    		$FrontController->setRequest($Request);
    	}
		$FrontController->setBaseUrl(BASE_URL); // must be done after the request creation
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our doctrine script
	 * @return
	 */
	protected function _initScriptDoctrine ( ) {
		# Bootstrap
		$this->bootstrap('modules');
		
		# Return true
		return true;
	}
	
	/**
	 * Initialise our paypal script
	 * @return
	 */
	protected function _initScriptPaypal ( ) {
		# Bootstrap
		$this->bootstrap('data');
		
		# Return true
		return true;
	}
	
	
}

