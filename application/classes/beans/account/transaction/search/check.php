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
class Beans_Account_Transaction_Search_Check extends Beans_Account_Transaction_Search {

	protected $_transactions;
	private $_page;
	private $_page_size;

	private $_search_vendor_keywords;
	private $_search_customer_keywords;
	private $_search_check_number;
	private $_search_date;

	// V2Item - Should "newest" automatically reference check number given use case?
	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'date' => 'desc',
			'close_books' => 'asc',
			'id' => 'desc',
		),
		'oldest' => array(
			'date' => 'asc',
			'close_books' => 'desc',
			'id' => 'asc',
		),
		'checknewest' => array(
			'reference' => 'desc',
			'date' 		=> 'desc',
			'close_books' => 'asc',
			'id' 		=> 'desc',
		),
		'checkoldest' => array(
			'reference' => 'asc',
			'date'		=> 'asc',
			'close_books' => 'desc',
			'id'		=> 'asc',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_transactions = ORM::Factory('transaction')->distinct(TRUE)->join('entities','LEFT')->on('entities.id','=','transaction.entity_id');
		
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
		$this->_search_vendor_keywords = ( isset($data->vendor_keywords) ) 
								  ? $data->vendor_keywords 
								  : FALSE;

		$this->_search_customer_keywords = ( isset($data->customer_keywords) ) 
								  ? $data->customer_keywords 
								  : FALSE;

		$this->_search_date = ( isset($data->date) )
							 ? $data->date
							 : FALSE;

		$this->_search_check_number = ( isset($data->check_number) )
									? $data->check_number
									: FALSE;

	}

	protected function _execute()
	{
		// V2Item - Consider removing this restriction; though there isn't a use case for it.
		if( $this->_search_vendor_keywords AND 
			$this->_search_customer_keywords )
			throw new Exception("Cannot search both for vendors and customers.");

		// Require a check number.
		$this->_transactions = $this->_transactions->where('transaction.reference','IS NOT',NULL);

		if( $this->_search_vendor_keywords )
		{
			$this->_transactions = $this->_transactions->where('entities.type','=','vendor');
			foreach( explode(' ', $this->_search_vendor_keywords) as $keyword )
			{
				$term = trim($keyword);
				if( $term )
				{
					$this->_transactions = $this->_transactions->
						and_where_open()->
							or_where('entities.first_name','LIKE','%'.$term.'%')->
							or_where('entities.last_name','LIKE','%'.$term.'%')->
							or_where('entities.company_name','LIKE','%'.$term.'%')->
							or_where('entities.phone_number','LIKE','%'.$term.'%')->
						and_where_close();
				}
			}
		}

		if( $this->_search_customer_keywords )
		{
			$this->_transactions = $this->_transactions->where('entities.type','=','customer');
			foreach( explode(' ', $this->_search_vendor_keywords) as $keyword )
			{
				$term = trim($keyword);
				if( $term )
				{
					$this->_transactions = $this->_transactions->
						and_where_open()->
							or_where('entities.first_name','LIKE','%'.$term.'%')->
							or_where('entities.last_name','LIKE','%'.$term.'%')->
							or_where('entities.company_name','LIKE','%'.$term.'%')->
							or_where('entities.phone_number','LIKE','%'.$term.'%')->
						and_where_close();
				}
			}
		}

		if( $this->_search_date )
		{
			$this->_transactions = $this->_transactions->where('transaction.date','=',$this->_search_date);
		}

		if( $this->_search_check_number )
		{
			$this->_transactions = $this->_transactions->where('transaction.reference','LIKE','%'.$this->_search_check_number.'%');
		}

		$result_object = $this->_find_transactions();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"transactions" => $this->_return_transactions_array($result_object->transactions),
		);
	}

	protected function _find_transactions()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");
		
		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$transactions_count = clone($this->_transactions);
		$return_object->total_results = $transactions_count->count_all('transaction.id');
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_transactions = $this->_transactions->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_transactions = $this->_transactions->
					order_by($column,$direction);

		$return_object->transactions = $this->_transactions->find_all();

		return $return_object;
	}

}