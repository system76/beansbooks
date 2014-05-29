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
class Beans_Auth_Role_Search extends Beans_Auth_Role {
	
	protected $_auth_role_perm = "setup";

	protected $_id;
	protected $_roles;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_roles = ORM::Factory('role');
	}

	protected function _execute()
	{
		$result_object = $this->_find_roles();

		return (object)array(
			"total_results" => $result_object->total_results,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"roles" => $this->_return_roles_array($result_object->roles),
		);
	}

	protected function _find_roles()
	{
		$return_object = new stdClass;

		$roles_count = clone($this->_roles);
		$return_object->total_results = $roles_count->count_all();
		$return_object->pages = 1;
		$return_object->page = 0;

		$return_object->roles = $this->_roles->order_by('id','ASC')->find_all();

		return $return_object;
	}
}