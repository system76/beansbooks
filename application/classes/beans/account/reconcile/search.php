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
@action Beans_Account_Reconcile_Search
@description Search through account reconciliations.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by The sort pattern for the returned results: 'newest', 'oldest'
@optional account_id The ID of the #Beans_Account# to restrict results to.
@returns total_results Total number of results.
@returns sort_by The sort method used in the returned results.
@returns pages The total number of pages of results.
@returns page The currently returned page index.
@returns account_reconciles An array of #Beans_Account_Reconcile#.
---BEANSENDSPEC---
*/
class Beans_Account_Reconcile_Search extends Beans_Account_Reconcile {

	protected $_account_reconciles;
	private $_page;
	private $_page_size;
	private $_search_account_id;
	
	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'date' => 'desc',
			'id' => 'desc',
		),
		'oldest' => array(
			'date' => 'asc',
			'id' => 'asc',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_account_reconciles = ORM::Factory('account_reconcile');
		
		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		// Check for search fields.
		$this->_search_account_id = ( isset($data->account_id) ) 
								  ? $data->account_id 
								  : FALSE;

	}

	protected function _execute()
	{
		if( $this->_search_account_id )
			$this->_account_reconciles = $this->_account_reconciles->
				where('account_id','=',$this->_search_account_id);

		$result_object = $this->_find_account_reconciles();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"account_reconciles" => $this->_return_account_reconciles_array($result_object->account_reconciles),
		);
	}

	protected function _find_account_reconciles()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");
		
		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");
		
		$account_reconciles_count = clone($this->_account_reconciles);
		$return_object->total_results = $account_reconciles_count->count_all();
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_account_reconciles = $this->_account_reconciles->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_account_reconciles = $this->_account_reconciles->
					order_by($column,$direction);

		$return_object->account_reconciles = $this->_account_reconciles->find_all();

		return $return_object;
	}

}