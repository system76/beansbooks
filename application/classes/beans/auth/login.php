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
class Beans_Auth_Login extends Beans_Auth {

	protected $_auth_role_perm = FALSE;

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}

	protected function _execute()
	{
		if( ! isset($this->_data->email) OR 
			! $this->_data->email OR 
			! isset($this->_data->password) OR
			! $this->_data->password )
			throw new Exception("Login error: missing email and/or password.");

		$user = ORM::Factory('user')->where('email','LIKE',$this->_data->email)->find();

		if( ! $user->loaded() )
			throw new Exception("Login error: that email address was not found.");

		if( $user->password != $this->_beans_auth_password($user->id,$this->_data->password) )
			throw new Exception("Login error: that password was incorrect.");

		if( ! $user->role->loaded() )
			throw new Exception("Login error: that user does not have any defined role.");

		if( $user->password_change )
		{
			$user->reset = $this->_generate_reset($user->id);
			$user->reset_expiration = time() + ( 2 * 60 );
			$user->save();

			return (object)array(
				"reset" => $user->reset,
			);
		}

		$expiration = ( $user->role->auth_expiration_length != 0 )
					? ( time() + $user->role->auth_expiration_length )
					: rand(11111,99999);

		$user->auth_expiration = $expiration;
		$user->save();

		return (object)array(
			"auth" => $this->_return_auth_element($user,$expiration),
		);
	}
}