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

class Beans_Auth_User_Update extends Beans_Auth {

	protected $_auth_role_perm = "setup";

	protected $_id;
	protected $_data;
	protected $_user;

	/**
	 * Create a new auth user.
	 * 		Required parameters:
	 * 			name
	 * 			email
	 * 			password
	 * 			role_id
	 * 			
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_user = $this->_load_user($this->_id);
	}

	protected function _execute()
	{
		// One-off use case of looking up role by code.
		if( isset($this->_data->role_code) AND 
			$this->_data->role_code )
		{
			$role = ORM::Factory('role')->where('code','=',$this->_data->role_code)->find();

			if( $role->loaded() )
				$this->_data->role_id = $role->id;
		}

		if( isset($this->_data->name) )
			$this->_user->name = $this->_data->name;

		if( isset($this->_data->email) )
			$this->_user->email = $this->_data->email;

		if( isset($this->_data->role_id) )
			$this->_user->role_id = $this->_data->role_id;

		if( isset($this->_data->password) )
		{
			$this->_user->password = $this->_beans_auth_password($this->_user->id,$this->_data->password);
		}

		$this->_validate_user($this->_user);

		$this->_user->save();

		return (object)array(
			"user" => $this->_return_user_element($this->_user),
		);
	}
}