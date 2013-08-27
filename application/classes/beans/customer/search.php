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
@action Beans_Customer_Search
@description Search for customers.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by STRING The sort pattern for the returned results: 'newest', 'oldest'
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional search_email STRING Search a wildcard match on an email address.
@optional search_name STRING Search a wildcard match on a name ( including first, last, and company name ).
@optional search_number STRING Search a wildcard match on a phone or fax number.
@optional search_and BOOLEAN Require all provided fields to match.
@returns customers ARRAY An array of #Beans_Customer#.
@returns total_results INTEGER Total number of results.
@returns sort_by STRING The sort method used in the returned results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Customer_Search extends Beans_Customer {

	protected $_customers;
	private $_page;
	private $_page_size;
	private $_search_email;
	private $_search_name;
	private $_search_number;
	private $_search_and;

	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'id' => 'desc',
		),
		'oldest' => array(
			'id' => 'asc',
		),
	);
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_customers = ORM::Factory('entity_customer')->distinct(TRUE);
		
		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		$this->_search_and = isset($data->search_and)
						   ? ( $data->search_and ? TRUE : FALSE )
						   : FALSE;

		$this->_search_email = ( isset($data->search_email) AND strlen($data->search_email) )
							 ? $data->search_email
							 : FALSE;

		$this->_search_name = ( isset($data->search_name) AND strlen($data->search_name) )
							 ? $data->search_name
							 : FALSE;

		$this->_search_number = ( isset($data->search_number) AND strlen($data->search_number) )
							 ? $data->search_number
							 : FALSE;

	}

	protected function _execute()
	{
		$this->_customers = $this->_customers->where('type','=','customer')->and_where_open();

		$parameter_added = FALSE;

		if( $this->_search_name )
		{
			$this->_customers = $this->_search_and ? $this->_customers->and_where_open() : $this->_customers->or_where_open();
			
			foreach( explode(' ',$this->_search_name) as $search_name_term )
			{
				if( trim($search_name_term) )
				{
					$parameter_added = TRUE;
					$this->_customers = $this->_customers->or_where('first_name','LIKE','%'.$search_name_term.'%');
					$this->_customers = $this->_customers->or_where('last_name','LIKE','%'.$search_name_term.'%');
					$this->_customers = $this->_customers->or_where('company_name','LIKE','%'.$search_name_term.'%');
				}
			}

			$this->_customers = $this->_search_and ? $this->_customers->and_where_close() : $this->_customers->or_where_close();
		}

		if( $this->_search_email )
		{
			$this->_customers = $this->_search_and ? $this->_customers->and_where_open() : $this->_customers->or_where_open();
			
			foreach( explode(' ',$this->_search_email) as $search_email_term )
			{
				if( trim($search_email_term) )
				{
					$parameter_added = TRUE;
					$this->_customers = $this->_customers->or_where('email','LIKE','%'.$search_email_term.'%');
				}
			}
			
			$this->_customers = $this->_search_and ? $this->_customers->and_where_close() : $this->_customers->or_where_close();
		}

		if( $this->_search_number )
		{
			$this->_customers = $this->_search_and ? $this->_customers->and_where_open() : $this->_customers->or_where_open();
			
			foreach( explode(' ',$this->_search_number) as $search_number_term )
			{
				if( trim($search_number_term) )
				{
					$parameter_added = TRUE;
					$this->_customers = $this->_customers->or_where('phone_number','LIKE','%'.$search_number_term.'%');
					$this->_customers = $this->_customers->or_where('fax_number','LIKE','%'.$search_number_term.'%');
				}
			}

			$this->_customers = $this->_search_and ? $this->_customers->and_where_close() : $this->_customers->or_where_close();
		}
		
		if( ! $parameter_added )
			$this->_customers = $this->_customers->where('id','IS NOT',NULL);

		$this->_customers = $this->_customers->and_where_close();

		$result_object = $this->_find_customers();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"customers" => $this->_return_customers_array($result_object->customers),
		);
	}

	protected function _find_customers()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");

		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$customers_count = clone($this->_customers);
		$return_object->total_results = $customers_count->count_all('entity_customer.id');
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_customers = $this->_customers->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_customers = $this->_customers->
					order_by($column,$direction);

		$return_object->customers = $this->_customers->find_all();

		return $return_object;
	}

}