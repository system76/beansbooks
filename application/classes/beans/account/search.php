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
@action Beans_Account_Search
@description Returns a list of all accounts.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by The sort pattern for the returned results: 'newest', 'oldest', 'alpha', 'ralpha'
@returns accounts An array of #Beans_Account# sorted ( by default, 'newest' ).
@returns total_results Total number of results.
@returns sort_by The sort method used in the returned results.
---BEANSENDSPEC---
*/
class Beans_Account_Search extends Beans_Account {

	protected $_accounts;
	
	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'id' => 'desc',
		),
		'oldest' => array(
			'id' => 'asc',
		),
		'alpha' => array(
			'name' => 'asc',
		),
		'ralpha' => array(
			'name' => 'desc',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_accounts = ORM::Factory('account')->distinct(TRUE);
		
		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";
	}

	protected function _execute()
	{
		$result_object = $this->_find_accounts();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"accounts" => $this->_return_accounts_array($result_object->accounts),
		);
	}

	protected function _find_accounts()
	{
		$return_object = new stdClass;

		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$accounts_count = clone($this->_accounts);
		$return_object->total_results = $accounts_count->count_all('account.id');
		$return_object->pages = 1;
		$return_object->page = 0;

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_accounts = $this->_accounts->
					order_by($column,$direction);

		$return_object->accounts = $this->_accounts->find_all();

		return $return_object;
	}
}