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

// TODO DOCME
// Add documentation for this method even though it's not meant to be used by the API.
class Beans_Auth_User_Create extends Beans_Auth {

	protected $_auth_role_perm = "setup";

	protected $_data;
	protected $_user;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
		$this->_user = $this->_default_user();
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

		$this->_user->name = isset($this->_data->name) ? $this->_data->name : NULL;
		$this->_user->email = isset($this->_data->email) ? $this->_data->email : NULL;
		$this->_user->role_id = isset($this->_data->role_id) ? $this->_data->role_id : NULL;
		$this->_user->password = isset($this->_data->password) ? '1' : NULL;
		$this->_user->password_change = ( isset($this->_data->password_change) AND $this->_data->password_change ) ? TRUE : FALSE;

		$this->_validate_user($this->_user);

		$this->_user->save();

		$this->_user->password = $this->_beans_auth_password($this->_user->id,$this->_data->password);
		$this->_user->save();

		return (object)array(
			"user" => $this->_return_user_element($this->_user),
		);
	}
}