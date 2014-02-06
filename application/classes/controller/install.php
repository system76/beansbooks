<?php defined('SYSPATH') or die('No direct access allowed.');
/*
BeansBooks
Copyright (C) System76, Inc.

This file is part of BeansBooks.

BeansBooks is free software; you can redistribute it and/or modify
it under the terms of the BeansBooks Public License.

BeansBooks is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the BeansBooks Public License for more details.

You should have received a copy of the BeansBooks Public License
along with BeansBooks; if not, email info@beansbooks.com.
*/

class Controller_Install extends Controller_View {

	public function before()
	{
		if( file_exists(APPPATH.'classes/beans/config.php') AND 
			filesize(APPPATH.'classes/beans/config.php') > 0 AND
			$this->request->action() != "finalize" AND
			$this->request->action() != "manual" )
		{
			$config_permissions = str_split(substr(decoct(fileperms(APPPATH.'classes/beans/config.php')),2));
			if( intval($config_permissions[count($config_permissions) - 3]) <= 6 AND 
				intval($config_permissions[count($config_permissions) - 2]) <= 6 AND 
				intval($config_permissions[count($config_permissions) - 1]) <= 0 )
				throw new HTTP_Exception_404("Page not found.");
		}

		parent::before();
	}

	public function action_index()
	{
		// Install!
	}

	public function action_check()
	{
		$version = explode('.', phpversion());
		if( $version[0] != "5" OR 
			(
				$version[0] == "5" AND 
				intval($version[1]) < 3 
			) )
			return $this->_view->send_error_message("PHP 5.3 Required: Found ".implode('.', $version));

		if( ! function_exists('mysql_connect') )
			return $this->_view->send_error_message("Missing MySQL support in PHP.");

		if( ! function_exists('imagepng') )
			return $this->_view->send_error_message("Missing GD support in PHP.");

		if( ! function_exists('mcrypt_encrypt') OR 
			! in_array('rijndael-128', mcrypt_list_algorithms()) OR
			! in_array('nofb', mcrypt_list_modes()) )
			return $this->_view->send_error_message("Missing MCrypt support or Rjindael 128.");

			$this->request->redirect('/install/database');
		
	}

	public function action_database()
	{
		if( count($this->request->post()) )
		{
			$hostname = $this->request->post('database_hostname');
			$database = $this->request->post('database_database');
			$username = $this->request->post('database_username');
			$password = $this->request->post('database_password');

			try {
				$config = $this->_create_database_config($hostname,$database,$username,$password);
				Session::instance('native')->set('config_database',$config);
				$this->request->redirect('/install/email');
			}
			catch( Exception $e )
			{
				$this->_view->send_error_message($e->getMessage());
			}
		}
	}

	public function action_email()
	{
		
		if( count($this->request->post()) )
		{
			$hostname = $this->request->post('email_hostname');
			$port = $this->request->post('email_port');
			$username = $this->request->post('email_username');
			$password = $this->request->post('email_password');
			$encryption = $this->request->post('email_encryption');
			$email_address = $this->request->post('email_address');

			try {
				$config = $this->_create_email_config($hostname,$port,$username,$password,$encryption);
				
				// email_address
				if( strlen($email_address) )
				{
					try
					{
						Email::connect($config);
						Email::send(
							$email_address, 
							$email_address, 
							"Beans Email Verification", 
							"You can ignore this email - it was a self-generated message ".
							"used to verify your email server credentials.", 
							FALSE
						);
					}
					catch( Exception $e )
					{
						return $this->_view->send_error_message("An error occurred when verifying your email settings: ".$e->getMessage());
					}
				}

				Session::instance('native')->set('config_email',$config);

				$this->request->redirect('/install/auth');
			}
			catch( Exception $e )
			{
				$this->_view->send_error_message($e->getMessage());
			}
		}

	}

	/*
	public function action_accounts()
	{
		if( count($this->request->post()) )
		{
			$accounts_options_choice = $this->request->post('accounts_options_choice');

			if( ! strlen($accounts_options_choice) OR 
				! isset($this->_accounts_options[$accounts_options_choice]) )
				$this->_view->send_error_message("Please select a default account set.");
			else
			{
				Session::instance('native')->set('accounts_options_choice',$accounts_options_choice);
				$this->request->redirect('/install/auth');
			}
		}

		$this->_view->accounts_options = $this->_accounts_options;
	}
	*/

	public function action_auth()
	{
		if( count($this->request->post()) )
		{
			$valid = TRUE;

			$email = $this->request->post('auth_email');
			$username = $this->request->post('auth_name');
			$password = $this->request->post('auth_password');

			if( ! $email )
			{
				$valid = FALSE;
				$this->_view->send_error_message("Please include a valid email address.");
			}
			else if( ! filter_var($email,FILTER_VALIDATE_EMAIL) )
			{
				$valid = FALSE;
				$this->_view->send_error_message("That email address was not valid.");
			}
			else if( $email != $this->request->post('auth_email_repeat') )
			{
				$valid = FALSE;
				$this->_view->send_error_message("Those email addresses did not match.");
			}

			if( ! $username )
			{
				$valid = FALSE;
				$this->_view->send_error_message("Please include a username.");
			}

			if( ! $password )
			{
				$valid = FALSE;
				$this->_view->send_error_message("Please include a password.");
			}
			else if( strlen($password) < 8 )
			{
				$valid = FALSE;
				$this->_view->send_error_message("Password must be at least 8 characters.");
			}
			else if( $password !== $this->request->post('auth_password_repeat') )
			{
				$valid = FALSE;
				$this->_view->send_error_message("Those passwords did not match.");
			}

			if( $valid )
			{
				Session::instance('native')->set('auth_email',$email);
				Session::instance('native')->set('auth_name',$username);
				Session::instance('native')->set('auth_password',$password);

				$this->request->redirect('/install/finalize');
			}
			
			// Save them some work.
			$this->_view->reflect_auth_email = $this->request->post('auth_email');
			$this->_view->reflect_auth_email_repeat = $this->request->post('auth_email_repeat');
			$this->_view->reflect_auth_name = $this->request->post('auth_name');
			$this->_view->reflect_auth_password = $this->request->post('auth_password');
			$this->_view->reflect_auth_password_repeat = $this->request->post('auth_password_repeat');
		}
	}

	public function action_finalize()
	{
		if( ! count($this->request->post()) ||
			! $this->request->post('install-step') )
		{
			if( ! Session::instance('native')->get('config_database') ||
				! Session::instance('native')->get('config_email') )
				return $this->request->redirect('/install/');

			// Write Config File.
			
			// Generate our secure hashes.
			time_nanosleep(rand(0,2), rand(0,999999999));
			$sha_hash = $this->_generate_random_string(128);
			time_nanosleep(rand(0,2), rand(0,999999999));
			$sha_salt = $this->_generate_random_string(128);
			time_nanosleep(rand(0,2), rand(0,999999999));
			$cookie_salt = $this->_generate_random_string(128);
			time_nanosleep(rand(0,2), rand(0,999999999));
			
			// Create encrypt config
			$config_encrypt = $this->_create_encrypt_config($this->_generate_random_string(64,TRUE));

			$config_database = Session::instance('native')->get('config_database');
			$config_email = Session::instance('native')->get('config_email');

			// Beans Configuration!
			$beans_config = array(
				'sha_hash' => $sha_hash,
				'sha_salt' => $sha_salt,
				'cookie_salt' => $cookie_salt,
				'modules' => array(
					'encrypt' => array(
						'default' => $config_encrypt,
					),
					'database' => array(
						'default' => $config_database,
					),
					'email' => $config_email,
				),
			);

			// Write Config File
			file_put_contents(APPPATH.'classes/beans/config.php', 
				'<?php defined(\'SYSPATH\') or die(\'No direct access allowed.\');'.
				"\n\n".
				'return '.
				var_export($beans_config,TRUE).
				';'
			);
		}

		$config_permissions = str_split(substr(decoct(fileperms(APPPATH.'classes/beans/config.php')),2));
		if( intval($config_permissions[count($config_permissions) - 3]) > 6 OR 
			intval($config_permissions[count($config_permissions) - 2]) > 6 OR 
			intval($config_permissions[count($config_permissions) - 1]) > 0 )
			return $this->_view->send_error_message("Error: The config file was successfully created, but the permissions must be changed. ".
				"Please change the mode on application/classes/beans/config.php to be at least as restrictive as 0660. ".
				"Once you've done so - you can click &quot;Finalize Installation&quot; again to finish the process.");

		if( count($this->request->post()) AND 
			$this->request->post('install-step') == "5" )
		{
			$db = database::instance();
			$db->connect();

			$tables = $db->query(Database::SELECT,'SHOW TABLES;')->as_array();
			if( count($tables) )
				return $this->_view->send_error_message("Error: database is not empty.");

			// Create Table Structure
			$database_tables_sql = file_get_contents(DOCROOT.'install_files/database_structure.sql');
			$database_tables = explode(';',$database_tables_sql);

			foreach( $database_tables as $database_table )
				( strlen(trim($database_table)) 
					? $db->query(NULL,$database_table)
					: NULL
				);

			$beans_setup_init = new Beans_Setup_Init((object)(array(
				'auth_uid' => "INSTALL",
				'auth_key' => "INSTALL",
				'auth_expiration' => "INSTALL",
				'default_account_set' => "full",
			)));
			$beans_setup_init_result = $beans_setup_init->execute();

			if( ! $beans_setup_init_result->success )
			{
				$this->_remove_sql_progress($db);
				return $this->_view->send_error_message("Error setting up initial table entries: ".$beans_setup_init_result->auth_error.$beans_setup_init_result->error);
			}
			else
			{
				// Create Admin Account
				$beans_create_user = new Beans_Auth_User_Create((object)array(
					'auth_uid' => "INSTALL",
					'auth_key' => "INSTALL",
					'auth_expiration' => "INSTALL",
					'name' => Session::instance('native')->get('auth_name'),
					'email' => Session::instance('native')->get('auth_email'),
					'password' => Session::instance('native')->get('auth_password'),
					'role_code' => 'admin',
				));
				$beans_create_user_result = $beans_create_user->execute();

				if( ! $beans_create_user_result->success )
				{
					$this->_view->send_error_message($beans_create_user_result->auth_error.$beans_create_user_result->error);
				}
				else
				{
					Session::instance('native')->destroy();
					$this->request->redirect('/');
				}
			}
		}
	}

	public function action_manual()
	{
		if( ! Kohana::$is_cli )
			$this->request->redirect('/');

		if( ! file_exists(APPPATH.'classes/beans/config.php') OR 
			filesize(APPPATH.'classes/beans/config.php') < 1 )
			die("Error: Missing config.php\n");
		
		// Check for required parameters.
		$auth_options = CLI::options('name','email','password','accounts','overwritedb','temppassword');
		
		if( ! $auth_options['name'] )
			die("Error: missing required option 'name'\n");

		if( ! $auth_options['email'] )
			die("Error: missing required option 'email'\n");

		if( ! $auth_options['password'] )
			die("Error: missing required option 'password'\n");

		if( ! $auth_options['accounts'] )
		{
			$auth_options['accounts'] = "full";
			echo "No default account set option provided, assuming full.\n";
		}

		$tables = DB::query(Database::SELECT,'SHOW TABLES;')->execute()->as_array();
		if( count($tables) AND 
			isset($auth_options['overwritedb']) AND 
			$auth_options['overwritedb'] == "yes" )
			$this->_remove_sql_progress();
		else if( count($tables) )
			die("Error: database table is not empty.\n");

		// Create Table Structure
		$database_tables_sql = file_get_contents(DOCROOT.'install_files/database_structure.sql');
		$database_tables = explode(';',$database_tables_sql);

		foreach( $database_tables as $database_table )
			( strlen(trim($database_table)) 
				? DB::query(NULL,$database_table)->execute()
				: NULL
			);
		
		$beans_setup_init = new Beans_Setup_Init((object)(array(
			'auth_uid' => "INSTALL",
			'auth_key' => "INSTALL",
			'auth_expiration' => "INSTALL",
			'default_account_set' => $auth_options['accounts'],
		)));
		$beans_setup_init_result = $beans_setup_init->execute();

		if( ! $beans_setup_init_result->success )
		{
			$this->_remove_sql_progress();
			die("Error setting up initial table entries: ".$beans_setup_init_result->auth_error.$beans_setup_init_result->error."\n");
		}

		// Create Admin Account
		$beans_create_user = new Beans_Auth_User_Create((object)array(
			'auth_uid' => "INSTALL",
			'auth_key' => "INSTALL",
			'auth_expiration' => "INSTALL",
			'name' => $auth_options['name'],
			'email' => $auth_options['email'],
			'password' => $auth_options['password'],
			'password_change' => ( isset($auth_options['temppassword']) && $auth_options['temppassword'] ? TRUE : FALSE ),
			'role_code' => 'admin',
		));
		$beans_create_user_result = $beans_create_user->execute();

		if( ! $beans_create_user_result->success )
		{
			$this->_remove_sql_progress();
			die("Error setting up user account: ".$beans_create_user_result->auth_error.$beans_create_user_result->error);
		}

		die("Success.\n");
	}

	private function _remove_sql_progress($db = NULL)
	{
		if( ! $db )
		{
			$tables = DB::query(Database::SELECT,'SHOW TABLES;')->execute()->as_array();
			$table_names = array();
			foreach( $tables as $table )
				foreach( $table as $name )
					$table_names[] = $name;
			DB::query(Database::DELETE,'DROP TABLE '.implode(', ',$table_names))->execute();
		}
		else
		{
			$tables = $db->query(Database::SELECT,'SHOW TABLES;')->execute()->as_array();
			$table_names = array();
			foreach( $tables as $table )
				foreach( $table as $name )
					$table_names[] = $name;
			$db->query(Database::DELETE,'DROP TABLE '.implode(', ',$table_names))->execute();
		}
	}

	
	/**
	 * Creates and verifies a database configuration.
	 * @param  String $hostname 
	 * @param  String $database 
	 * @param  String $username 
	 * @param  String $password 
	 * @return Array           Error message.  None if successful.
	 * @throws Exception If Bad data.
	 */
	private function _create_database_config($hostname,$database,$username,$password)
	{
		$config = array
		(
			'type'       => 'mysql',
			'connection' => array(
				'hostname'   => $hostname,
				'database'   => $database,
				'username'   => $username,
				'password'   => $password,
				'persistent' => FALSE,
			),
			'table_prefix' => '',
			'charset'      => 'utf8',
			'caching'      => FALSE,
			'profiling'    => TRUE,
		);

		try
		{
			$db = database::instance('test',$config);
			$db->connect();

			// Make sure this is empty.
			$tables = $db->query(Database::SELECT,'SHOW TABLES;',TRUE);

			if( count($tables) )
				throw new Exception("That database already has tables loaded.  Please select a different database or empty this database.<br>
					If you absolutely must use this database then please follow the manual configuration instructions in README.md.");

		}
		catch( Exception $e )
		{
			throw new Exception($e->getMessage());
		}

		return $config;
	}

	// V2Item - Add in more error handling for an inability to connect to the mail server.
	private function _create_email_config($hostname,$port,$username,$password,$encryption)
	{
		$config = array
		(
			'driver' => 'smtp',
			'options'	=> array(
				'hostname'	=>	$hostname,
				'port'		=>	$port,
				'username'	=>	$username,
				'password'	=>	$password,
				'encryption'=>	$encryption,
			),
		);
		
		return $config;
	}

	private function _create_encrypt_config($key)
	{
		return array(
			'key'	 => $key,
			'cipher' => MCRYPT_RIJNDAEL_128,
			'mode'   => MCRYPT_MODE_NOFB,
		);
	}

	private function _generate_random_string($length = 64,$alphanumonly = FALSE)
	{
		$return_string = '';
		$timesalt = explode(' ', microtime());
		mt_srand((float) $timesalt[1] + ((float) $timesalt[0] * rand(123456,654321)));

		$characters = array_merge(
			range('a','z'),
			array('~','`','!','@','#','$','%','^','&','*','(',')','-','_','=','+','|','{','}','[',']','<','>',',','.','?','/'),
			range('A','Z'),
			range(0,9)
		);

		if( $alphanumonly )
			$characters = array_merge(
				range('a','z'),
				range('A','Z'),
				range(0,9)
			);

		$characters_len = count($characters)-1;

		for( $i = 0; $i < $length; $i++ )
			$return_string .= $characters[mt_rand(0,$characters_len)];

		return $return_string;
	}

	protected $_accounts_options = array(
		/*
		'base' => array(
			'name' => "Top Level Only",
			'description' => "Only setup top level accounts such as assets, liabilities, etc.",
		),
		*/
		'minimal' => array(
			'name' => "Minimal",
			'description' => "A minimal structure for broken-out expenses, assets, liabilities, etc.",
		),
		'full' => array(
			'name' => "Full",
			'description' => "A nearly complete chart of accounts, missing only your business-specific accounts.",
		),
	);

}