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

class Beans_Auth extends Beans {

	/**
	 * Empty constructor to pull in Beans data.
	 * @param stdClass $data
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	/**
	 * Hash a password.
	 * @param  Integer $user_id
	 * @param  String $password
	 * @return String 
	 */
	protected function _beans_auth_password($user_id = NULL,$password = NULL)
	{
		if( ! $user_id OR 
			! $password )
			return FALSE;

		// Fairly simple but effective salt and hash.
		// Works best with a salt > 30 or 40 characters... must be > 10 for an realisitic salt variable.
		return hash_hmac("sha512",$password.substr($this->_sha_salt,(intval($user_id) % 10),(strlen($this->_sha_salt)-10)),$this->_sha_hash);
	}


	protected function _return_auth_element($user,$expiration)
	{
		$return_object = new stdClass;

		if( ! $user->loaded() OR
			get_class($user) != "Model_User" )
			throw new Exception("Invalid User.");

		$return_object->user = $this->_return_user_element($user);
		$return_object->auth_uid = $user->id;
		$return_object->auth_expiration = $expiration;
		$return_object->auth_key = $this->_beans_auth_generate($user->id,$user->password,$expiration);
		
		return $return_object;
	}

	/**
	 * Returns an array of User Elements formatted for a return object.
	 * @param  Array $users An iterative array of Model_User (ORM)
	 * @return Array           Array of User Elements (Array)
	 * @throws Exception If User object is not valid.
	 */
	protected function _return_users_array($users)
	{
		$return_array = array();
		
		foreach( $users as $user )
			$return_array[] = $this->_return_user_element($user);
		
		return $return_array;
	}

	/**
	 * Returns the information for a user in addition to the role.
	 * @param  Model_User $user
	 * @return stdClass		stdClass of all properties for this user.
	 * @throws Exception If User object is not valid.
	 */
	protected function _return_user_element($user)
	{
		$return_object = new stdClass;

		if( ! $user->loaded() OR
			get_class($user) != "Model_User" )
			throw new Exception("Invalid User.");

		$return_object->id = $user->id;
		$return_object->name = $user->name;
		$return_object->email = $user->email;
		$return_object->role = $this->_return_role_element($user->role);
		$return_object->current_auth_expiration = $user->auth_expiration;

		return $return_object;
	}

	/**
	 * Validate User Element
	 */
	protected function _validate_user($user)
	{
		if( get_class($user) != "Model_User" )
			throw new Exception("Invalid User.");

		if( ! $user->name OR
			! strlen($user->name) )
			throw new Exception("Invalid user name: none provided.");

		if( strlen($user->name) > 64 )
			throw new Exception("Invalid user name: maximum of 64 characters.");

		if( ! $user->email OR
			! strlen($user->email) )
			throw new Exception("Invalid email address: none provided.");

		if( ! filter_var($user->email,FILTER_VALIDATE_EMAIL) )
			throw new Exception("Invalid email address: invalid email address.");

		if( ORM::Factory('user')->where('id','!=',$user->id)->where('email','LIKE',$user->email)->count_all() > 0 )
			throw new Exception("Invalid email address: already present in system.");

		if( ! $user->password OR 
			! strlen($user->password) )
			throw new Exception("Invalid user password: none provided.");

		if( ! $user->role_id OR 
			! strlen($user->role_id) )
			throw new Exception("Invalid user role: none provided.");

		$role = $this->_load_role($user->role_id);

		if( ! $role->loaded() )
			throw new Exception("Invalid user role: not found.");

		if( $role->user_limit AND
			$role->users->where('id','!=',$user->id)->count_all() >= $role->user_limit ) 
			throw new Exception("That role can have a maxmium of ".$role->user_limit." users and already has that many.");
		
	}

	protected function _default_user()
	{
		$user = ORM::Factory('user');

		$user->name = NULL;
		$user->email = NULL;
		$user->password = NULL;
		$user->role_id = NULL;

		return $user;
	}

	protected function _load_user($user_id)
	{
		return ORM::Factory('user',$user_id);
	}

	protected function _load_role($role_id)
	{
		return ORM::Factory('role',$role_id);
	}

	/**
	 * Returns an array of User Elements formatted for a return object.
	 * @param  Array $users An iterative array of Model_User (ORM)
	 * @return Array           Array of User Elements (Array)
	 * @throws Exception If User object is not valid.
	 */
	protected function _return_roles_array($roles)
	{
		$return_array = array();
		
		foreach( $roles as $role )
			$return_array[] = $this->_return_role_element($role);
		
		return $return_array;
	}

	/**
	 * Returns the access and information tied to a role.
	 * @param  Model_Role $role
	 * @return stdClass		stdClass of all properties for this role.
	 * @throws Exception If Role object is not valid.
	 */
	public function _return_role_element($role)
	{
		$return_object = new stdClass;

		if( ! $role->loaded() OR
			get_class($role) != "Model_Role" )
			throw new Exception("Invalid Role.");
		
		$return_object->id = $role->id;
		$return_object->name = $role->name;
		$return_object->code = $role->code;
		$return_object->description = $role->description;
		$return_object->auth_expiration_length = $role->auth_expiration_length;
		$return_object->customer_read = $role->customer_read ? TRUE : FALSE;
		$return_object->customer_write = $role->customer_write ? TRUE : FALSE;
		$return_object->customer_sale_read = $role->customer_sale_read ? TRUE : FALSE;
		$return_object->customer_sale_write = $role->customer_sale_write ? TRUE : FALSE;
		$return_object->customer_payment_read = $role->customer_payment_read ? TRUE : FALSE;
		$return_object->customer_payment_write = $role->customer_payment_write ? TRUE : FALSE;
		$return_object->vendor_read = $role->vendor_read ? TRUE : FALSE;
		$return_object->vendor_write = $role->vendor_write ? TRUE : FALSE;
		$return_object->vendor_expense_read = $role->vendor_expense_read ? TRUE : FALSE;
		$return_object->vendor_expense_write = $role->vendor_expense_write ? TRUE : FALSE;
		$return_object->vendor_purchase_read = $role->vendor_purchase_read ? TRUE : FALSE;
		$return_object->vendor_purchase_write = $role->vendor_purchase_write ? TRUE : FALSE;
		$return_object->vendor_payment_read = $role->vendor_payment_read ? TRUE : FALSE;
		$return_object->vendor_payment_write = $role->vendor_payment_write ? TRUE : FALSE;
		$return_object->account_read = $role->account_read ? TRUE : FALSE;
		$return_object->account_write = $role->account_write ? TRUE : FALSE;
		$return_object->account_transaction_read = $role->account_transaction_read ? TRUE : FALSE;
		$return_object->account_transaction_write = $role->account_transaction_write ? TRUE : FALSE;
		$return_object->account_reconcile = $role->account_reconcile ? TRUE : FALSE;
		$return_object->books = $role->books ? TRUE : FALSE;
		$return_object->reports = $role->reports ? TRUE : FALSE;
		$return_object->setup = $role->setup ? TRUE : FALSE;

		return $return_object;
	}

	protected function _generate_reset($id)
	{
		$reset = '';
		while( strlen($reset) < 128 ) {
			$reset = $reset.substr(md5(''.rand(1111111,9999999).microtime().rand(1111111,9999999)),rand(0,16),rand(1,16));
		}

		return substr($reset,0,128);
	}

}