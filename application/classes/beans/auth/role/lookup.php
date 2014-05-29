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
class Beans_Auth_Role_Lookup extends Beans_Auth_Role {
	
	protected $_auth_role_perm = "setup";

	protected $_id;
	protected $_role;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		// V2Item - Messy - clean this up.
		if( isset($data->role_code) )
		{
			$role = ORM::Factory('role')->where('code','=',$data->role_code)->find();

			if( $role->loaded() )
				$this->_id = $role->id;
		}

		$this->_role = $this->_load_role($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_role->loaded() )
			throw new Exception("Role could not be found.");

		return (object)array(
			"role" => $this->_return_role_element($this->_role),
			"users" => $this->_return_users_array($this->_role->users->find_all()),	// V2Item - Messy - clean this up.
		);
	}
}