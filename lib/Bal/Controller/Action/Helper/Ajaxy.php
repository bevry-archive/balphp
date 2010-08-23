<?php
class Bal_Controller_Action_Helper_Ajaxy extends Bal_Controller_Action_Helper_Abstract
{
	# ========================
	# VARIABLES
	
	protected $_Json = null;
	protected $_Session = null;
	protected $_xhr = null;
	
	protected $_data = array(
		'redirected' => false
	);
	
	protected $_options = array(
	);
	
	
	# ========================
	# CONSTRUCTORS
	
	
	/**
	 * Construct
	 * @param array $options
	 */
	public function __construct ( array $options = array() ) {
		# Prepare
		$result = true;
		
		# Prepare
		$Json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$Session = new Zend_Session_Namespace('Ajaxy');
		
		# Apply
		$this->_Json = $Json;
		$this->_Session = $Session;
		
		# Store whether we are a ajax request
		$this->_Session->xhr = $this->isAjax();
		$this->_Session->served = false;
		$this->_Session->last_url = $this->getURL();
		$this->_Session->last_hit = time();
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($options) : $result;
	}
	
	/**
	 * Get URL
	 * @return
	 */
	public function getURL ( ) {
		return $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
	}
	
	/**
	 * Check if we are a Ajaxy request
	 * @return
	 */
	public function isAjax ( ) {
		if ( $this->_xhr === null ) {
			$xhr = $this->getActionControllerRequest()->isXmlHttpRequest();
			if ( !$xhr && !empty($_REQUEST['ajax']) ) $xhr = 'param';
			/*
			 * Perhaps we have come from a redirect of a AJAX request
			 * So check if the last request was an ajax request
			 * And check if the last request was not sent
			 * And that we have been accessed in an appropriate redirect timeframe
			 */
			if ( $this->_Session->xhr && !$this->_Session->served && strtotime('-5 seconds', time()) < $this->_Session->last_hit ) {
				# We came from a redirect from a ajaxy request
				if ( !$xhr ) $xhr = true;
				# Save some data into data
				$this->_data['redirected']['to'] = $this->getURL();
			}
			# Log the XHR into the Session
			$this->_xhr = $xhr;
		}
		return $this->_xhr;
	}
	
	/**
	 * Send JSON Data
	 * @param object $data
	 * @return
	 */
	public function send ( $data ) {
		$this->_Json->sendJson($data);
	}
	
	/**
	 * Render our Action, via html or json depending on request
	 * @param string $html_view
	 * @param array $ajaxy_levels
	 * @return
	 */
    public function render ( $params ) {
		# Prepare Params
		if ( !is_array($params) ) {
			$params = array(
				'template' => $params
			);
		}
		$params = array_keys_keep_ensure($params, array('template','routes','controller','data'));
		
		# Extract
		$controller = $params['controller'];
		$template = $params['template'];
		$ajaxy_data = $params['data'];
		
		# Populate Routes
		$routes = array();
		force_array($params['routes']);
		foreach ( $params['routes'] as $route => $routeData ) {
			$route = preg_replace('/:(\w+)/ie', '\$this->getActionControllerView()->${1}', $route);
			$routeData['route'] = $route;
			if ( empty($routeData['controller']) ) $routeData['controller'] = $params['controller'];
			if ( empty($routeData['template']) ) $routeData['template'] = $params['template'];
			$routes[] = $routeData;
		}
		
		# Fetch
		$ajaxy_options = !empty($_REQUEST['Ajaxy']) ? $_REQUEST['Ajaxy'] : array();
		
    	# Save
		$routes_old = $this->_Session->ajaxy_routes;
		$this->_Session->ajaxy_routes = $routes;
		$this->_Session->served = true;
		$xhr = $this->isAjax();
		if ( $xhr ) {
			# JSON
			
			# Extract Data
			if ( is_string($ajaxy_data) ) {
				$ajaxy_data = explode(',',str_replace(' ',',',$ajaxy_data));
				array_clean($ajaxy_data);
				$ajaxy_data_new = array();
				foreach ( $ajaxy_data as $item ) {
					$ajaxy_data_new[$item] = $this->getActionControllerView()->$item;
				}
				$ajaxy_data = $ajaxy_data_new;
				unset($ajaxy_data_new);
			}
			$ajaxy_data['Ajaxy'] = $this->_data;
			
			# Discover controller
			foreach ( $routes as $i => $routeData ) {
				# Check old part
				if ( !isset($routes_old[$i]) ) {
					break;
				}
				
				# Route
				$route = $routeData['route'];
				$route_old = $routes_old[$i]['route'];
				
				# Extract
				$controller = $routeData['controller'];
				$template = $routeData['template'];
				
				# Compare old with new
				if ( $route_old != $route ) {
					# Mismatch
					break;
				}
			}
			
			# Check Template
			if ( !ends_with($template,'.phtml') ) $template .= '.phtml';
			$template = $this->getActionControllerRequest()->getControllerName().DIRECTORY_SEPARATOR.$template;
			
			# Perform Render
			$View = $this->getActionControllerView();
			$content = $View->render($template);
			$title = html_entity_decode(strip_tags($View->headTitle()->toString()));
			$data = array_merge($ajaxy_data,array(
				'controller' => $controller,
				'title' => $title,
				'content' => $content,
			));
			
			# Send
			if ( !empty($ajaxy_options['form'])) {
				# Form
				$val = json_encode($data);
      			$response = $this->_Json->getResponse();
				$response->clearHeaders()->clearBody();
				$response->setBody('<html><head></head><body><textarea class="response">'.$val.'</textarea></body></html>');
				$response->sendResponse();
				exit;
			}  elseif ( $xhr === 'param' ) {
				# Special
				$this->_Json->suppressExit = true;
				$this->_Json->sendJson($data);
      			$response = $this->_Json->getResponse();
				$response->setHeader('Content-Type', 'text/plain; charset=utf-8');
            	$response->sendResponse();
            	exit;
			} else {
				# Normal
				$this->_Json->sendJson($data);
			}
			
			
		} else {
			# HTML
			$this->getActionController()->render($template);
		}
    }
}

