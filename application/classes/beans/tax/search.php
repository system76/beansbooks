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
@action Beans_Tax_Search
@description Search for taxes.
@required auth_uid
@required auth_key
@required auth_expiration
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional search_name STRING A generic query string to search for the name.
@optional search_code STRING A word query string to search codes for.
@optional search_and BOOLEAN Perform the search by requiring a match on all submitted parameters.
@returns taxes ARRAY An array of #Beans_Tax#.
@returns total_results INTEGER Total number of results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Tax_Search extends Beans_Tax {

	protected $_auth_role_perm = "login";
	
	protected $_taxes;
	private $_page;
	private $_page_size;
	private $_search_name;
	private $_search_code;
	private $_search_and;
	private $_search_include_hidden;

	/**
	 * Search by ID.
	 * @param array $data Array of parameters by keys:
	 *                    'id' => ID of the tax to lookup.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_taxes = ORM::Factory('tax')->distinct(TRUE);

		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_search_and = isset($data->search_and)
						   ? ( $data->search_and ? TRUE : FALSE )
						   : FALSE;

		$this->_search_code = ( isset($data->search_code) AND strlen($data->search_code) )
							 ? $data->search_code
							 : FALSE;

		$this->_search_name = ( isset($data->search_name) AND strlen($data->search_name) )
							 ? $data->search_name
							 : FALSE;

		$this->_search_include_hidden = ( isset($data->search_include_hidden) AND $data->search_include_hidden )
									  ? TRUE 
									  : FALSE;
	}

	protected function _execute()
	{
		if( $this->_search_code )
		{
			if( $this->_search_and )
				$this->_taxes = $this->_taxes->and_where_open();
			else
				$this->_taxes = $this->_taxes->or_where_open();
			
			$this->_taxes = $this->_taxes->
				where('code','LIKE','%'.$this->_search_code.'%');

			if( $this->_search_and )
				$this->_taxes = $this->_taxes->and_where_close();
			else
				$this->_taxes = $this->_taxes->or_where_close();
		}

		if( $this->_search_name )
		{
			if( $this->_search_and )
				$this->_taxes = $this->_taxes->and_where_open();
			else
				$this->_taxes = $this->_taxes->or_where_open();
			
			$this->_taxes = $this->_taxes->
				where('name','LIKE','%'.$this->_search_name.'%');

			if( $this->_search_and )
				$this->_taxes = $this->_taxes->and_where_close();
			else
				$this->_taxes = $this->_taxes->or_where_close();
		}

		if( ! $this->_search_include_hidden )
			$this->_taxes = $this->_taxes->where('visible','=',TRUE);
		else
			$this->_taxes = $this->_taxes->where('visible','IS NOT',NULL);
		
		$result_object = $this->_find_taxes();

		return (object)array(
			"total_results" => $result_object->total_results,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"taxes" => $this->_return_taxes_array($result_object->taxes),
		);
	}

	protected function _find_taxes()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");

		$taxes_count = clone($this->_taxes);
		$return_object->total_results = $taxes_count->count_all('tax.id');
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_taxes = $this->_taxes->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		$return_object->taxes = $this->_taxes->find_all();

		return $return_object;
	}

}