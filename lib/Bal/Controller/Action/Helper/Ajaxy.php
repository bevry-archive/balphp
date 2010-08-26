<?php
/**
 * Ajaxy Action Helper
 * @depends Bal_Controller_Action_Helper_Abstract		balphp/lib/Bal/Controller/Action/Helper/Abstract.php
 * @depends array_keys_keep_ensure						balphp/lib/core/functions/_arrays.funcs.php
 * @depends array_clean									balphp/lib/core/functions/_arrays.funcs.php
 * @depends ends_with									balphp/lib/core/functions/_strings.funcs.php
 */
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
	 * To use, replace your $this->render('page') call with:
	 *	// Perform an Ajaxy Response or just render normally if applicable
	 *	$this->getHelper('Ajaxy')->render(array(
	 * 		//	The template that we would like to render for non Ajaxy Requests
	 *		'template' => 'page',
	 * 		//	The Ajaxy Controller that this response is for
	 *		'controller' => 'page',
	 * 		//	Optionally we can specify this, it is used for Ajaxy Requests
	 *		'routes' => array(
	 * 			//	We work by specifying a route, and then it's options
	 * 			//	In this instance, we specify page-:page, more on this soon
	 *			'page-:page' => array(
	 * 				//	The template for this route
	 *				'template' => 'page',
	 * 				//	The controller for this route
	 *				'controller' => 'page'
	 *			),
	 * 			//	This is our subpage route that we would like to use
	 *			'page-:page-subpage-:subpage' => array(
	 * 				//	The template for this route
	 *				'template' => 'page/subpage',
	 * 				//	The controller for this route
	 *				'controller' => 'subpage'
	 *			)
	 * 			//	Now lets talk about these routes
	 * 			//	Where we have a [:variable] that is replaced with the variables value stored in the view
	 * 			//	This route will only be reached if the above route fails.
	 * 			//	For instance, if we go to the URL page/one, then page/two.
	 * 			//	Then we still be using the [page-:page] route. As the two requests evaluated to:
	 * 			//		page-one
	 * 			//		page-two
	 * 			//	If however we went from page/two, page/one, page/one/subpage/one, then the requests will evualate to:
	 * 			//		page-two
	 * 			//		page-one
	 * 			//		page-one-subpage-one
	 * 			//	This is because the last request was able to trickle down the first Ajaxy Route as that was the same.
	 * 			//	This allows back and forward history to correctly render the correct section.
	 * 			//	To best understand this, let's show a series of urls and their corresponding Ajaxy information:
	 * 			//		URL							Ajaxy Route					Ajaxy Controller		Rendered Template
	 * 			//		page/one					page-one					page					page.phtml
	 * 			//		page/two					page-two					page					page.phtml
	 * 			//		page/two/subpage/one		page-two-subpage-one		subpage					page/subpage.phtml
	 * 			//		page/two/subpage/two		page-two-subpage-two		subpage					page/subpage.phtml
	 * 			//		page/one					page-one					page					page.phtml
	 * 			//		page/two/subpage/one		page-one					page					page.phtml
	 * 			//	The last request evaluates to the Ajaxy Controller [page], as if we used the [subpage] controller
	 * 			//	then we would be rendering the second page's first subpage in the first page! Instead of in the second page!
	 * 			//	That's the complicated issue. But once you get that, it's smooth sailing.
	 *		),
	 * 		//	Finally here is data we would like to send with our Ajaxy Response
	 * 		//	If this is a space separated string then we will fetch the variables from the view
	 * 		//	Alternatively this can be a associative array containing the data we would like to send 
	 *		'data' => 'page subpage'
	 *	));
	 * If any of these instructions were not clear enough then make a post here with your feedback and we'll try our best to help you out:
	 * http://getsatisfaction.com/balupton/products/balupton_jquery_ajaxy
	 * @param array $params
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

