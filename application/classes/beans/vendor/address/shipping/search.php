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
@action Beans_Vendor_Address_Shipping_Search
@description Search for vendor shipping addresses.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by STRING The sort pattern for the returned results: 'newest', 'oldest'
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional search_keywords STRING A generic query string to filter addresses by all fields.
@returns addresses ARRAY An array of #Beans_Vendor_Address_Shipping#.
@returns total_results INTEGER Total number of results.
@returns sort_by STRING The sort method used in the returned results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Vendor_Address_Shipping_Search extends Beans_Vendor_Address {

	private $_addresses;
	private $_page;
	private $_page_size;
	private $_search_keywords;

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
		
		$this->_addresses = ORM::Factory('entity_address')->distinct(TRUE);

		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		$this->_search_keywords = isset($data->search_keywords)
								? $data->search_keywords
								: FALSE;
	}

	protected function _execute()
	{
		$this->_addresses = $this->_addresses->where('entity_id','=',0);

		if( $this->_search_keywords )
			foreach( explode(' ',$this->_search_keywords) as $keyword )
				if( trim($keyword) && $keyword = trim($keyword) )
					$this->_addresses = $this->_addresses->
						and_where_open()->
							or_where('first_name','LIKE','%'.$keyword.'%')->
							or_where('last_name','LIKE','%'.$keyword.'%')->
							or_where('company_name','LIKE','%'.$keyword.'%')->
							or_where('address1','LIKE','%'.$keyword.'%')->
							or_where('address2','LIKE','%'.$keyword.'%')->
							or_where('city','LIKE','%'.$keyword.'%')->
							or_where('state','LIKE','%'.$keyword.'%')->
							or_where('zip','LIKE','%'.$keyword.'%')->
							or_where('country','LIKE','%'.$keyword.'%')->
						and_where_close();

		$result_object = $this->_find_addresses();

		return (object)array(
			"total_results" => $result_object->total_results,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"addresses" => $this->_return_vendor_addresses_array($result_object->addresses),
		);
	}

	protected function _find_addresses()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");

		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$addresses_count = clone($this->_addresses);
		$return_object->total_results = $addresses_count->count_all('entity_address.id');
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_addresses = $this->_addresses->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_addresses = $this->_addresses->
					order_by($column,$direction);

		$return_object->addresses = $this->_addresses->find_all();

		return $return_object;
	}

}
