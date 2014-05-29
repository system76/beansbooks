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

/*
---BEANSAPISPEC---
@action Beans_Account_Type_Search
@description Returns a list of all account types.
@required auth_uid
@required auth_key
@required auth_expiration
@returns account_types An array of #Beans_Account_Type#.
@returns total_results Total number of results.
---BEANSENDSPEC---
*/
class Beans_Account_Type_Search extends Beans_Account_Type {

	protected $_account_types;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_account_types = ORM::Factory('account_type');
	}

	protected function _execute()
	{
		$result_object = $this->_find_account_types();

		return (object)array(
			"total_results" => $result_object->total_results,
			"account_types" => $this->_return_account_types_array($result_object->account_types),
		);
	}

	protected function _find_account_types()
	{
		$return_object = new stdClass;

		$account_types_count = clone($this->_account_types);
		$return_object->total_results = $account_types_count->count_all();
		
		$return_object->account_types = $this->_account_types->find_all();

		return $return_object;
	}

}