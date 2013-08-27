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
@action Beans_Customer_Payment_Search
@description Search for customer payments.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by STRING The sort pattern for the returned results: 'newest', 'oldest'
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional include_invoices BOOLEAN Include invoices when searching.
@optional keywords STRING A generic search query string.  Searches by payment date or amount.  If search_flag_include_invoices is TRUE, then will search for matches to Sale Number, Order Number, and PO Number.
@returns payments ARRAY An array of #Beans_Customer_Payment#.
@returns total_results INTEGER Total number of results.
@returns sort_by STRING The sort method used in the returned results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Customer_Payment_Search extends Beans_Customer_Payment {

	protected $_payments;
	protected $_page;
	protected $_page_size;

	private $_search_keywords;
	
	private $_search_flag_include_invoices;
	
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
		
		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		$this->_search_keywords = isset($data->keywords)
								? $data->keywords
								: FALSE;

		$this->_search_flag_include_invoices = isset($data->include_invoices)
									 ? ( $data->include_invoices ? TRUE : FALSE )
									 : NULL;
		
	}

	protected function _execute()
	{
		$this->_payments = ORM::Factory('transaction')->
			distinct(TRUE)->
			join('account_transactions','LEFT')->
			on('account_transactions.transaction_id','=','transaction.id');

		$this->_payments = $this->_payments->
			where('transaction.payment','=','customer')->
			and_where_open();

		// Prevent empty and_where_open
		$this->_payments = $this->_payments->
			where('transaction.id','IS NOT',NULL);

		if( $this->_search_flag_include_invoices AND
			$this->_search_keywords )
		{
			// Get our account_transaction_ids to match with forms
			$account_transaction_ids_query = DB::select(array('account_transaction_forms.account_transaction_id','account_transaction_id'))->
				from('account_transaction_forms')->
				join('forms','LEFT')->
				on('account_transaction_forms.form_id','=','forms.id')->
				where('forms.type','=','sale')->
				and_where_open();

			foreach( explode(' ',$this->_search_keywords) as $keyword )
				if( trim($keyword) && $keyword = trim($keyword) )
					$account_transaction_ids_query = $account_transaction_ids_query->
						and_where_open()->
							or_where('forms.total','=',$this->_beans_round(floatval($keyword)))->
							or_where('forms.code','LIKE','%'.$keyword.'%')->			// Sale Order Number
							or_where('forms.reference','LIKE','%'.$keyword.'%')->		// Order Number
							or_where('forms.alt_reference','LIKE','%'.$keyword.'%')->	// PO Number
							//or_where('forms.date_created','=',$keyword)->				// Uncomment to include date
						and_where_close();

			$account_transaction_ids_query = $account_transaction_ids_query->
				and_where_close();

			$account_transaction_ids_result = $account_transaction_ids_query->execute()->as_array();

			$account_transaction_ids = array();

			foreach( $account_transaction_ids_result as $result )
				$account_transaction_ids[] = $result['account_transaction_id'];

			// Really beefy - but maintains the best or / and conditionals.
			if( count($account_transaction_ids) )
			{
				foreach( explode(' ',$this->_search_keywords) as $keyword )
					if( trim($keyword) && $keyword = trim($keyword) )
						$this->_payments = $this->_payments->
							and_where_open()->
								or_where('transaction.date','=',$keyword)->
								or_where('transaction.amount','=',$this->_beans_round($keyword))->
								or_where('account_transactions.id','IN',$account_transaction_ids)->
							and_where_close();
			}
			else
			{
				foreach( explode(' ',$this->_search_keywords) as $keyword )
					if( trim($keyword) && $keyword = trim($keyword) )
						$this->_payments = $this->_payments->
							and_where_open()->
								or_where('transaction.date','=',$keyword)->
								or_where('transaction.amount','=',$this->_beans_round($keyword))->
							and_where_close();
			}

		}
		else if( $this->_search_keywords )
			foreach( explode(' ',$this->_search_keywords) as $keyword )
				if( trim($keyword) && $keyword = trim($keyword) )
					$this->_payments = $this->_payments->
						and_where_open()->
							or_where('transaction.date','=',$keyword)->
							or_where('transaction.amount','=',$keyword)->
						and_where_close();
		
		$this->_payments = $this->_payments->and_where_close();

		$result_object = $this->_find_payments();

		// V2Item - Consider returning attribute-only customer payment models to reduce load times.
		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"payments" => $this->_return_customer_payments_array($result_object->payments),
		);
	}

	protected function _find_payments()
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
		$return_object->total_results = $payments_count->count_all('transaction.id');
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