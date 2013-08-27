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

class Beans_Auth_User_Delete extends Beans_Auth_User {

	protected $_auth_role_perm = "setup";

	protected $_id;
	protected $_user;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_user = $this->_load_user($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_user->loaded() )
			throw new Exception("User could not be found.");

		// First User Check
		$first_user = ORM::Factory('user')->order_by('id','asc')->find();

		if( $this->_user->id == $first_user->id )
			throw new Exception("Cannot delete the master account for this domain.");

		$this->_user->delete();
		
		return (object)array(
			"success" => TRUE,
			"auth_error" => "",
			"error" => "",
			"data" => (object)array(),
		);
	}
}