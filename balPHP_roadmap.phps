<?php	
	// =============================================
	// Copyright, Benjamin Arthur Lupton, 7th November 2008, All Rights Reserved
	
	// balPHP roadmap to be reached by January 2009
	
	// =============================================
	// Configuration
	
	// Define Databases
	$datastore = array(
		'default' => 'balupton.com',
		'balupton.com' => array(
			'connection' => array(
				'host' => 'localhost',
				'user' => 'balupton',
				'pass' => 'password',
				'post' => '80',
				'name' => 'balupton.com'
			),
			'class_tables' => array( // Used to map a Data Table to a Class Table
				'users' => array( // Inserted by the User class
					'title' => array(
						0 => 'Users',
						1 => 'User'
					),
					'class' => 'User',
					'prefix' => 'user',
					'primary_key' => 'id',
					'columns' => array(
						'ID' => array(
							'type' => 'number',
							'length' => '11',
							'description' => 'The ID of the User'
						),
						'code' => array(
							'type' => 'text',
							'length' => '11',
							'description' => 'The code (textual ID) of the user'
						),
						'firstname' => array(
							'type' => 'text',
							'length' => '15'
						),
						'lastname' => array(
							'type' => 'text',
							'length' => '15'
						),
						'fullname' => array(
							'type' => 'text',
							'length' => '30',
							'virtual' => 'SELECT CONCAT(`users.firstname`, " ", `users.lastname`)'
						),
						'profile' => array(
							'type' => 'html',
							'length' => 0 // infinity, so text,
						),
						'company' => array(
							'type' => 'reference',
							'references' => array(
								'value' => 'companies.id',
								'title' => 'companies.title',
								'where' => '`companies.broke` IS false',
							),
							'display' => 'dropdown'
						)
					)
				)
			),
			'data_tables' => array( // Generated from the database
				'pages' => array(
					'title' => array( // custom, set by the user
						0 => 'Pages',
						1 => 'Page'
					),
					'class' => null, // custom, set by the user, if NULL use DataObject
					'prefix' => 'user', // custom, set by the user
					'primary_key' => 'id',
					'columns' => array(
						'ID' => array(
							'type' => 'number',
							'length' => '11',
							'description' => 'The ID of the User'
						),
						'code' => array(
							'type' => 'text',
							'length' => '11',
							'description' => 'The code (textual ID) of the user' // custom, set by the user
						),
						'content' => array(
							'type' => 'html',
							'length' => 0 // infinity, so text,
						)
					)
				)
			),
			'table_nicknames' => array( // Defaulted by class, Customized by Admin
				'user' => array('users', 'people')
			),
		)
	);
	
	// =============================================
	// DataStore
	
	// Create DataStore
	$DataStore = new DataStore($datastore, DataStore::NEW__CONNECT_DEFAULT);// NEW__CONNECT_NONE, NEW__CONNECT_ALL, NEW__CONNECT_DEFAULT
	
	// Set our default database
	//$DataStore->default('balupton.com');
	
	// Reference our default database
	$DataBase =& $DataStore->DataBase('balupton.com');
	
	// Connect to our default database
	//$DataBase->connect();
	
	// =============================================
	// Rebuild Definitions
	
	// Only needed if we do not want to use cached, if minor things have changes
	$DataStore->rebuild_definitions();
	// Use to resolve conflicts (new table) etc
	$DataStore->resolve_conflicts(DataStore::RESOLVE_CONFLICTS__FORCE); // DataStore::RESOLVE_CONFLICTS__FORCE, DataStore::RESOLVE_CONFLICTS__AUTOMATIC, DataStore::RESOLVE_CONFLICTS__VERBOSE
	
	// =============================================
	// Get a User
	
	// Get a User
	$User = new User(1); // 1 is ID
	$User = new User('admin'); // admin is code
	$User = new User(DataObject::NEW__REQUEST, false); // build from request
	$User = new User(DataObject::NEW__REQUEST, true); // load from request
	$User = new User(DataObject::NEW__REQUEST, true); // perform request
	
	// Get some User properties
	$User__fullname = $User->get('name'); // text types are always stripped
	$User__Company = $User->get('company', DataObject::GET__CLASS);
	$User__profile = $User->get('profile'); // html types are not stripped, as intended
	
	// Output
	echo "{$User__fullname} ({$User->ID}) works for the company {$User__Company->get('title')}"
	
	// =============================================
	// Update a Page
	
	$Page = new DataObject('pages', DataObject::NEW__REQUEST, false);
	$Page->set('title', 'Welcome');
	$Page->set('content', '<strong>content</strong>');
	$Page->update();
	
	$Page->display('update'); // displays update form
	Display::form($Page->data);
	
	//Display::WYSIWYG($page->get('content'));
	
	// =============================================
	// Traverse
	
	$DataSet = $DataStore->query(array(
		'query' => 'SELECT `users.id`, `users.fullname`, `users.email`, (SELECT `companies.title` FROM `companies` WHERE `users.company` IS `companies.id`) FROM `users`'
	);
	while ( $DataRow = $DataSet->next() )
	{
		$User = new User($DataRow->getTable('users'), false); // no action, load from the array
		$Company__title = $DataRow->get('companies.title');
		echo "{$User->get('fullname')} ({$User->ID}) works for the company {$Company__title}";
	}
	
	// =============================================
	// Display dropdown of all the Users, with ID as value, and fullname as title
	
	$DataSet = $DataStore->query(array(
		'query' => 'SELECT `users.ID` as `value`, `users.fullname` as `title` FROM `users`'
	);
	Display::dropdown($DataSet->all(DataSet::ALL__ASSOC));
	
	// =============================================
	// Multiple Database Query
	
	$DataSet = $DataStore->query(array(
		'query' => 'SELECT `beta_DB.users.ID` as `beta_ID`, `live_DB.users.id` as `beta_ID`  FROM `beta_DB.users`, `live_DB.users`'
	);
	Display::table($DataSet->all(DataSet::ALL__ASSOC));
	
	// =============================================
	// Login
	
	$System->login($User);
	$System->login($username, $password);
	
	// =============================================
	// Templating
	
	Display::set_template_directory(TEMPLATE_DIR);
	Display::set_skin_directory(SKIN_DIR);
	Display::template_engine($Smarty, Display::TEMPLATE_ENGINE__SMARTY); // Must manually import smarty
	
	Display::assign_pair($key, $value);
	Display::assign_array(array());
	Display::assign_object($DataObject->all(DataObject::ALL__TEMPLATE)); // will remove all sensitive data 
	
	Display::template_display('index'); // notice no file extension, this is so if we are using a template engine it is supported
	Display::template_fetch('index');
	
	Display::template_display('index', Display::TEMPLATE__ISOLATE); // Isolate so it only has access to Display::get
	
	// =============================================
	// Display our Log
	
	if ( $Log->status === Log::STATUS__ERROR )
	{	// We have an error
		if ( DEBUG )
			$Log->display(Log::DISPLAY__VERBOSE);
		else
		{	// We are not a smart person
			$Log->display(Log::FRIENDLY);
			$Log->email(ADMIN_EMAIL);
		}
	}
	
	// =============================================
	
?>