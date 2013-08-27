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

class Beans_Auth_User_Search extends Beans_Auth_Role {
	
	protected $_auth_role_perm = "setup";

	protected $_users;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_users = ORM::Factory('user');
	}

	protected function _execute()
	{
		$result_object = $this->_find_users();

		return (object)array(
			"total_results" => $result_object->total_results,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"users" => $this->_return_users_array($result_object->users),
		);
	}

	protected function _find_users()
	{
		$return_object = new stdClass;

		$users_count = clone($this->_users);
		$return_object->total_results = $users_count->count_all();
		$return_object->pages = 1;
		$return_object->page = 0;

		$return_object->users = $this->_users->order_by('id','ASC')->find_all();

		return $return_object;
	}
}