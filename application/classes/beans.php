<?php defined('SYSPATH') or die('No direct script access.');
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

class Beans {

	protected $_BEANS_VERSION = '1.1.1';

	private $_beans_settings;
	private $_beans_config;
	protected $_sha_hash;
	protected $_sha_salt;

	// $_auth_role_perm should be defined within each class to set the required 
	// role booleans for access.  Setting it to FALSE will require no auth,
	// and setting it to "login" requires a user to authenticate but have no role.
	// The only action that should have FALSE is login.
	protected $_auth_role_perm = FALSE;
	protected $_auth_user = FALSE;
	protected $_auth_error = FALSE;
	protected $_auth_internal = FALSE;
	
	private $_cache_auth_uid = FALSE;
	private $_cache_auth_key = FALSE;
	private $_cache_auth_expiration = FALSE;

	// Logging
	// By default we do not log requests.
	protected $_logged_request = FALSE;
	protected $_logged_endpoints = array(
		'create',
		'update',
		'delete',
		'cancel',
	);
	protected $_logged_request_data = FALSE;
	
	public function __construct($data = NULL)
	{
		if( ! defined('LOCAL') )
			define('LOCAL',DOCROOT.'local'.DIRECTORY_SEPARATOR);

		if( file_exists(APPPATH.'classes/beans/config.php') AND 
			filesize(APPPATH.'classes/beans/config.php') > 0 )
		{
			// Grab config for encryption keys.
			$this->_beans_config = include APPPATH.'classes/beans/config.php';
			$this->_sha_hash = $this->_beans_config['sha_hash'];
			$this->_sha_salt = $this->_beans_config['sha_salt'];
		}

		// For logging.
		$this->_logged_request_data = ( $data ? $data : (object)array() );
		
		// Setup auth ( we're not necessarilly storing $data objects )
		$this->_auth_user = $this->_beans_auth_verification($data);
		
		if( $this->_auth_role_perm == "login" AND 
			! $this->_auth_user )
			$this->_auth_error = "Must be logged in to access this.";
		else if( 	$this->_auth_role_perm AND 
					! $this->_auth_user )
			$this->_auth_error = "Invalid user credentials.";
		else if( 	$this->_auth_role_perm AND 
					$this->_auth_role_perm != "login" AND 
					! $this->_auth_user->role->{$this->_auth_role_perm} )
			$this->_auth_error = "User does not have access permission to that feature.";
		
		if( isset($data->auth_internal) )
			$this->_auth_internal = $data->auth_internal;

		// Load Settings
		$this->_beans_settings_load();
	}

	public function execute()
	{
		try
		{
			if( ! $this->_beans_config )
				throw new Beans_Setup_Exception("Missing configuration file!");

			if( $this->_auth_error )
				throw new Beans_Auth_Exception($this->_auth_error);

			if( $this->_BEANS_VERSION != $this->_get_current_beans_version() &&
				strpos(strtolower(get_called_class()),'beans_setup_update') === FALSE &&
				strpos(strtolower(get_called_class()),'beans_setup_init') === FALSE )
				throw new Beans_Setup_Exception('BeansBooks must be updated before any further action.');

			$data = $this->_execute();

			// Add Log Item.			
			if( ! $this->_beans_internal_call() AND
				( 
					$this->_logged_request OR 
					(
						strrpos(get_called_class(),'_') !== FALSE AND 
						in_array(strtolower(substr(get_called_class(),( 1 + strrpos(get_called_class(),'_') ))), $this->_logged_endpoints)
					)
				) )
			{
				$log = ORM::Factory('log');
				$log->user_id = ( $this->_auth_user AND isset($this->_auth_user->id) )
							  ? $this->_auth_user->id
							  : 0;
				$log->action = get_called_class();
				$log->timestamp = time();
				$log->object_id = ( isset($this->_id) AND $this->_id )
								? $this->_id
								: NULL;
				// Remove Auth Info
				unset($this->_logged_request_data->auth_uid);
				unset($this->_logged_request_data->auth_key);
				unset($this->_logged_request_data->auth_expiration);
				
				$log->data = ( count(get_object_vars($this->_logged_request_data)) )
						   ? json_encode($this->_logged_request_data)
						   : NULL;
				$log->save();
			}


			return (object)array(
				"success" => TRUE,
				"config_error" => "",
				"auth_error" => "",
				"error" => "",
				"data" => $data,
			);
		}
		catch( Beans_Setup_Exception $e )
		{
			return (object)array(
				"success" => FALSE,
				"config_error" => $e->getMessage(),
				"auth_error" => "",
				"error" => "",
				"data" => (object)array(),
			);
		}
		catch( Beans_Auth_Exception $e )
		{
			return (object)array(
				"success" => FALSE,
				"config_error" => "",
				"auth_error" => $e->getMessage(),
				"error" => "",
				"data" => (object)array(),
			);
		}
		catch( Exception $e )
		{
			return (object)array(
				"success" => FALSE,
				"config_error" => "",
				"auth_error" => "",
				"error" => $e->getMessage(),
				"data" => (object)array(),
			);
		}

	}

	// Override in each action.
	protected function _execute()
	{
		return (object)array();
	}

	protected function _beans_return_internal_result($result)
	{
		if( $result->success )
			return $result->data;
		else if( strlen($result->auth_error) )
			throw new Beans_Auth_Exception($result->auth_error);
		else if( strlen($result->config_error) )
			throw new Beans_Setup_Exception($result->config_error);
		else if( strlen($result->error) )
			throw new Exception($result->error);

		throw new Exception("An unknown and unhandled error occurred.");
	}

	protected function _get_current_beans_version()
	{
		// Just to make sure this isn't called before settings are loaded.
		if( ! isset($this->_beans_settings) ||
			! isset($this->_beans_settings->LOCAL) )
			return FALSE;

		$version = $this->_beans_setting_get('BEANS_VERSION');

		if( ! $version )
		{
			$version = '1.0.0';
			$this->_beans_setting_set('BEANS_VERSION','1.0.0');
			$this->_beans_settings_save();
		}

		return $version;
	}

	// V2Item - Cache these settings in memory using APC or something similar.
	protected function _beans_settings_load()
	{
		$this->_beans_settings = new stdClass;
		
		if( ! $this->_beans_config )
			return;

		foreach( ORM::Factory('setting')->find_all() as $setting )
			$this->_beans_settings->{$setting->key} = $setting->value;

		// RESERVED
		$this->_beans_settings->LOCAL = LOCAL;

		return $this->_beans_settings;
	}

	/**
	 * Return all valid keys.
	 * @return Array
	 */
	protected function _beans_settings_keys()
	{
		$keys = array();
		if( ! isset($this->_beans_settings) )
			return $keys;

		foreach( $this->_beans_settings as $key => $val )
			$keys[] = $key;

		return $keys;
	}

	protected function _beans_settings_dump()
	{
		if( ! isset($this->_beans_settings) )
			return FALSE;

		return $this->_beans_settings;
	}

	protected function _beans_settings_save()
	{
		if( ! isset($this->_beans_settings) )
			$this->_beans_settings = new stdClass;

		foreach( $this->_beans_settings as $key => $value )
		{
			if( $key != "LOCAL" )
			{
				$setting = ORM::Factory('setting')->where('key','=',$key)->find();
				if( ! $setting->loaded() )
				{
					$setting = ORM::Factory('setting');
					$setting->key = $key;
				}

				$setting->value = $value;
				$setting->save();
			}
		}
	}

	protected function _beans_setting_get($key = NULL, $default = NULL)
	{
		if( ! $key OR
			! isset($this->_beans_settings) OR 
			! isset($this->_beans_settings->{$key}) )
			return $default;

		return $this->_beans_settings->{$key};
	}

	protected function _beans_setting_set($key = NULL, $value = NULL)
	{
		if( ! isset($this->_beans_settings) )
			$this->_beans_settings = new stdClass;

		$this->_beans_settings->{$key} = $value;
	}

	/**
	 * Beans Authentication
	 */
	
	// Add cached auth info to requests.
	protected function _beans_data_auth($data = NULL)
	{
		if( $data === NULL )
			$data = new stdClass;

		if( is_array($data) )
			$data = (object)$data;

		if( ! is_object($data) OR
			get_class($data) != "stdClass" )
			$data = new stdClass;

		// Set our auth keys.
		$data->auth_uid = $this->_cache_auth_uid;
		$data->auth_expiration = $this->_cache_auth_expiration;
		$data->auth_key = $this->_cache_auth_key;
		$data->auth_internal = $this->_beans_internal_auth_key();

		return $data;
	}
	
	/**
	 * Verify a set of authentication data.  Returns Model_User on success.
	 * @param  String $auth_uid 
	 * @param  String $auth_key 
	 * @param  String $auth_expiration 
	 * @return Model_User
	 */
	protected function _beans_auth_verification($data = NULL)
	{
		if( ! isset($data->auth_uid) OR 
			! $data->auth_uid OR
			! isset($data->auth_key) OR
			! $data->auth_key OR
			! isset($data->auth_expiration) OR
			! $data->auth_expiration )
			return FALSE;

		// If we're installing, there is a single use case in which we grant access.
		// REQUIREMENTS:
		// COUNT of ID in transactions / users MUST BE 0
		if( $data->auth_uid === "INSTALL" AND 
			$data->auth_key === "INSTALL" AND
			$data->auth_expiration === "INSTALL" AND
			ORM::Factory('user')->count_all() == 0 AND 
			ORM::Factory('transaction')->count_all() == 0 )
		{
			$this->_cache_auth_uid = $data->auth_uid;
			$this->_cache_auth_key = $data->auth_key;
			$this->_cache_auth_expiration = $data->auth_expiration;

			$user = new stdClass;
			$user->role = new stdClass;
			$user->role->INSTALL = "INSTALL";
			$user->role->setup = TRUE;			// V2Item - Replace these with a global install permission?
			$user->role->account_read = TRUE;	// 
			$user->role->account_write = TRUE;	// 

			return $user;
		}

		// If we're updating, there is a single use case in which we grant access.
		if( $data->auth_uid === "UPDATE" AND 
			$data->auth_key === "UPDATE" AND 
			$data->auth_expiration === "UPDATE" AND 
			strpos(strtolower(get_called_class()),'beans_setup_update') !== FALSE )
		{
			$this->_cache_auth_uid = $data->auth_uid;
			$this->_cache_auth_key = $data->auth_key;
			$this->_cache_auth_expiration = $data->auth_expiration;

			$user = new stdClass;
			$user->role = new stdClass;
			$user->role->UPDATE = TRUE;
			
			return $user;
		}

		// Lookup user.
		$user = ORM::Factory('user',$data->auth_uid);

		if( ! $user->loaded() )
			return FALSE;

		if( $data->auth_expiration != $user->auth_expiration )
			return FALSE;

		if( $user->role->auth_expiration_length != 0 AND 
			$user->auth_expiration < time() )
			return FALSE;

		if( $data->auth_key != $this->_beans_auth_generate($user->id,$user->password,$user->auth_expiration) )
			return FALSE;

		$this->_cache_auth_uid = $data->auth_uid;
		$this->_cache_auth_key = $data->auth_key;
		$this->_cache_auth_expiration = $data->auth_expiration;

		return $user;
	}

	protected function _beans_internal_auth_key()
	{
		if( ! $this->_auth_user ) 
			return FALSE;

		return md5($this->_cache_auth_uid.$this->_cache_auth_key.$this->_cache_auth_expiration);

		// so s3cret!
		return md5(spl_object_hash($this->_auth_user));
	}

	protected function _beans_internal_call()
	{
		return ( $this->_beans_internal_auth_key() == $this->_auth_internal );
	}

	protected function _beans_auth_generate($auth_uid = NULL,$password_hash = NULL,$auth_expiration = NULL)
	{
		if( ! $auth_uid OR 
			! $auth_expiration OR 
			! $password_hash )
			return FALSE;

		if( strlen($password_hash) != 128 )
			return FALSE;

		return hash_hmac("sha512",substr($password_hash,0,64).md5($auth_uid.$auth_expiration).substr($password_hash,64),$this->_sha_hash);
	}

	// V2Item - This will be transitioned to non-decimal values once those currencies are officially supported.
	/**
	 * Global rounding.  
	 * @param  float  $value
	 * @param  integer $decimals
	 * @return float
	 */
	protected function _beans_round($value,$decimals = 2)
	{
		return round(floatval($value),$decimals,PHP_ROUND_HALF_UP);
	}

	/**
	 * Checks if the books have been closed on a specific year ( passed by $date YYYY-MM-DD )
	 * @param  String $date YYYY-MM-DD Date to check.
	 * @return bool       True / False depending on if books are closed.
	 * @throws Exception If Invalid $date format ( must be YYYY of YYYY-MM-DD )
	 */
	protected function _check_books_closed($date)
	{
		if( date("Y-m-d",strtotime($date)) != $date )
			throw new Exception("Invalid date provided. Must be YYYY-MM-DD.");
		
		$fye_transaction = ORM::Factory('transaction')->
			where('close_books','IS NOT',NULL)->
			where('close_books','>=',substr($date,0,7).'-00')->
			find();
		
		if( $fye_transaction->loaded() )
			return TRUE;

		return FALSE;
	}

	// Sort mechanism for usort() to properly order journal entries.
	// Priority is date, close_books, id
	protected function _journal_usort($a,$b)
	{
		if( strtotime($a->date) < strtotime($b->date) ) 
			return -1;
		else if( strtotime($a->date) > strtotime($b->date) )
			return 1;

		// Reverse order - close books transactions take place "in between dates"
		if( $a->closebooks > $b->closebooks )
			return -1;
		else if( $a->closebooks < $b->closebooks )
			return 1;

		return ( $a->id < $b->id ? -1 : 1 );
	}


	// Would love to replace this with a query - requires adding fields to account_transaction_forms
	// Some enumerated field that could be "create", "invoice", "cancel", "payment"
	protected function _get_form_effective_balance($form,$date,$transaction_id)
	{
		$sale_balance = 0.00;

		foreach( $form->account_transaction_forms->find_all() as $account_transaction_form )
		{
			// If a transaction is either the creation transaction for this form OR
			// it is a payment that occurred on or before the creation date AND
			// optionally before the transaction_id - then we add it into the balance.
			if( (
					$account_transaction_form->account_transaction->transaction_id == $form->create_transaction_id OR
					( 
						$account_transaction_form->account_transaction->transaction->payment 
						AND 
						( 
							strtotime($account_transaction_form->account_transaction->date) < strtotime($date) OR
							(
								$account_transaction_form->account_transaction->date == $date &&
								(
									! $transaction_id || 
									$account_transaction_form->account_transaction->transaction_id < $transaction_id
								)
							)
						)
					) 
				) )
			{
				$sale_balance = $this->_beans_round(
					$sale_balance +
					$account_transaction_form->amount
				);
			}
		}

		return $sale_balance;
	}

}