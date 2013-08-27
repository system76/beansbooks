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
@action Beans_Vendor_Expense_Search
@description Search for vendor expenses.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by STRING The sort pattern for the returned results: 'newest', 'oldest'
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional search_vendor_id INTEGER Limit the search to a specific #Beans_Vendor#.
@optional keywords STRING A generic query string that will be compared to both vendors ( name, company name, phone number ) and expenses ( total, Expense Number, Invoice Number, and SO Number ).
@returns expenses ARRAY An array of #Beans_Vendor_Expense#.
@returns total_results INTEGER Total number of results.
@returns sort_by STRING The sort method used in the returned results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Vendor_Expense_Search extends Beans_Vendor_Expense {

	protected $_expenses;
	private $_page;
	private $_page_size;
	
	private $_search_vendor_id;
	private $_search_keywords;

	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'date_created' => 'desc',
			'id' => 'desc',
		),
		'oldest' => array(
			'date_created' => 'asc',
			'id' => 'asc',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		$this->_search_vendor_id = isset($data->search_vendor_id)
								   ? (int)$data->search_vendor_id
								   : FALSE;

		$this->_search_keywords = isset($data->keywords)
								? $data->keywords
								: FALSE;

	}

	protected function _execute()
	{
		$this->_expenses = ORM::Factory('form_expense')->
			distinct(TRUE)->
			join('entities','LEFT')->
			on('form_expense.entity_id','=','entities.id');

		$this->_expenses = $this->_expenses->
			where('form_expense.type','=','expense')->
			and_where_open();

		$this->_expenses = $this->_expenses->
			where('form_expense.id','IS NOT',NULL);

		if( $this->_search_vendor_id )
			$this->_expenses = $this->_expenses->
				where('entities.id','=',$this->_search_vendor_id);

		if( $this->_search_keywords )
			foreach( explode(' ',$this->_search_keywords) as $keyword )
				if( trim($keyword) && $keyword = trim($keyword) )
					$this->_expenses = $this->_expenses->
						and_where_open()->
							or_where('entities.first_name','LIKE','%'.$keyword.'%')->
							or_where('entities.last_name','LIKE','%'.$keyword.'%')->
							or_where('entities.company_name','LIKE','%'.$keyword.'%')->
							or_where('entities.phone_number','LIKE','%'.$keyword.'%')->
							or_where('form_expense.total','=',$this->_beans_round(floatval($keyword)))->
							or_where('form_expense.code','LIKE','%'.$keyword.'%')->				// Expense Number
							or_where('form_expense.reference','LIKE','%'.$keyword.'%')->		// Invoice Number
							or_where('form_expense.alt_reference','LIKE','%'.$keyword.'%')->	// SO Number
							or_where('form_expense.date_created','=',$keyword)->
						and_where_close();

		$this->_expenses = $this->_expenses
			->and_where_close();

		$result_object = $this->_find_expenses();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"expenses" => $this->_return_vendor_expenses_array($result_object->expenses),
		);
	}

	protected function _find_expenses()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");

		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$expenses_count = clone($this->_expenses);
		$return_object->total_results = $expenses_count->count_all('form_expense.id');
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_expenses = $this->_expenses->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_expenses = $this->_expenses->
					order_by($column,$direction);

		$return_object->expenses = $this->_expenses->find_all();

		return $return_object;
	}

}
