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
@action Beans_Tax_Payment_Search
@description Search for tax payments.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by STRING The sort pattern for the returned results: 'newest', 'oldest'
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional search_date STRING Search payments on a specific YYYY-MM-DD date.
@optional search_date_before STRING Search payments before a specific YYYY-MM-DD date.
@optional tax_id INTEGER The ID of the #Beans_Tax# to limit payments to.
@optional tax_name STRING A generic query string to search tax names.
@optional include_details BOOLEAN Whether or not to include liabilities on returned tax items.
@returns payments ARRAY An array of #Beans_Tax_Payment#.
@returns total_results INTEGER Total number of results.
@returns sort_by STRING The sort method used in the returned results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Tax_Payment_Search extends Beans_Tax_Payment {

	protected $_auth_role_perm = "vendor_payment_read";

	protected $_payments;
	protected $_page;
	protected $_page_size;
	protected $_search_date;
	protected $_search_date_before;
	protected $_search_tax_id;
	protected $_search_tax_name;
	protected $_include_details;
	
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
		
		$this->_payments = ORM::Factory('tax_payment')->join('taxes','LEFT')->on('taxes.id','=','tax_payment.tax_id');

		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		$this->_search_date = ( isset($data->search_date) )
							 ? $data->search_date
							 : FALSE;

		$this->_search_date_before = ( isset($data->search_date_before) )
							 ? $data->search_date_before
							 : FALSE;


		$this->_search_tax_id = ( isset($data->search_tax_id) )
							 ? $data->search_tax_id
							 : FALSE;

		$this->_search_tax_name = ( isset($data->search_tax_name) )
							 ? $data->search_tax_name
							 : FALSE;

		$this->_include_details = ( isset($data->include_details) && $data->include_details )
								? TRUE
								: FALSE; 


	}

	protected function _execute()
	{
		if( $this->_search_date )
		{
			if( $this->_search_date != date("Y-m-d",strtotime($this->_search_date)) )
				throw new Exception("Invalid search date. Please format as YYYY-MM-DD.");

			$this->_payments = $this->_payments->
				where('tax_payment.date','=',$this->_search_date);
		}

		if( $this->_search_date_before )
		{
			if( $this->_search_date_before != date("Y-m-d",strtotime($this->_search_date_before)) )
				throw new Exception("Invalid search before date. Please format as YYYY-MM-DD.");

			$this->_payments = $this->_payments->
				where('tax_payment.date','<',$this->_search_date_before);
		}

		if( $this->_search_tax_id )
		{
			if( ! $this->_load_tax($this->_search_tax_id)->loaded() )
				throw new Exception("Invalid search tax ID: not found.");

			$this->_payments = $this->_payments->
				where('tax_payment.tax_id','=',$this->_search_tax_id);
		}

		if( $this->_search_tax_name ) 
			$this->_payments = $this->_payments->
				where('taxes.name','LIKE','%'.$this->_search_tax_name.'%');

		$result_object = $this->_find_payments();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"payments" => $this->_return_tax_payments_array($result_object->payments, $this->_include_details),
		);
	}

	private function _find_payments()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");
		
		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$payments_count = clone($this->_payments);
		$return_object->total_results = $payments_count->count_all();
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_payments = $this->_payments->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_payments = $this->_payments->
					order_by($column,$direction);

		$return_object->payments = $this->_payments->find_all();

		return $return_object;
	}
}