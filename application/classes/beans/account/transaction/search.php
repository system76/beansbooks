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
@action Beans_Account_Transaction_Search
@description Search for transactions.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by The sort pattern for the returned results: 'newest', 'oldest', 'checknewest', 'checkoldest'
@optional page The page to return.
@optional page_size The number of results on each page.
@optional account_id The ID of the #Beans_Account# to restrict the search to.
@optional not_reconciled BOOLEAN to set restricting the search to unreconciled transactions.
@optional date Limit the search to a specific YYYY-MM-DD date.
@optional date_after Limit the search to transactions strictly after ( > ) a specific date.
@optional date_before Limit the search to transactions strictly before ( < ) a specific date.
@optional code Search a wildcard match on a code for a transaction.
@optional close_books Search for transactions that represent the end of a fiscale period.
@optional check_number Search for a wildcard match on a check number.
@optional has_check_number BOOLEAN to return results only with a check number.
@returns transactions An array of #Beans_Transaction#.
@returns total_results Total number of results.
@returns sort_by The sort method used in the returned results.
@returns pages The total number of pages of results.
@returns page The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Search extends Beans_Account_Transaction {

	protected $_transactions;
	private $_page;
	private $_page_size;
	private $_search_account_id;
	private $_search_not_reconciled;
	private $_search_date;
	private $_search_date_after;
	private $_search_date_before;
	private $_search_code;
	private $_search_close_books;
	private $_search_check_number;
	private $_search_has_check_number;

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

		$this->_transactions = ORM::Factory('transaction')->distinct(TRUE)->join('account_transactions','LEFT')->on('account_transactions.transaction_id','=','transaction.id');
		
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

		$this->_search_not_reconciled = ( isset($data->not_reconciled) )
									  ? ( $data->not_reconciled ? TRUE : FALSE )
									  : FALSE;

		$this->_search_date = ( isset($data->date) )
							 ? $data->date
							 : FALSE;

		$this->_search_date_after = ( isset($data->date_after) )
							 ? $data->date_after
							 : FALSE;

		$this->_search_date_before = ( isset($data->date_before) )
							 ? $data->date_before
							 : FALSE;

		$this->_search_code = ( isset($data->search_code) )
							? $data->search_code
							: FALSE;

		$this->_search_close_books = ( isset($data->close_books) )
								   ? $data->close_books
								   : FALSE;

		$this->_search_check_number = ( isset($data->check_number) )
									? $data->check_number
									: FALSE;

		$this->_search_has_check_number = ( isset($data->has_check_number) )
										? ( $data->has_check_number ? TRUE : FALSE )
										: NULL;

	}

	protected function _execute()
	{
		if( $this->_search_account_id )
			$this->_transactions = $this->_transactions->
				where('account_transactions.account_id','=',$this->_search_account_id);

		if( $this->_search_not_reconciled )
			$this->_transactions = $this->_transactions->
				where('account_transactions.account_reconcile_id','IS',NULL);

		if( $this->_search_date AND 
			$this->_search_date != date("Y-m-d",strtotime($this->_search_date)) )
			throw new Exception("Invalid search date. Please format as YYYY-MM-DD.");

		if( $this->_search_date )
			$this->_transactions = $this->_transactions->
				where('transaction.date','=',$this->_search_date);

		if( $this->_search_date_after )
			$this->_transactions = $this->_transactions->
				where('transaction.date','>',$this->_search_date_after);

		if( $this->_search_date_before )
			$this->_transactions = $this->_transactions->
				where('transaction.date','<',$this->_search_date_before);

		if( $this->_search_code )
			$this->_transactions = $this->_transactions->
				where('transaction.code','LIKE','%'.$this->_search_code.'%');

		if( $this->_search_has_check_number !== NULL )
		{
			if( $this->_search_has_check_number )
			{
				$this->_transactions = $this->_transactions->
					where('transaction.reference','IS NOT',NULL);
			}
			else
			{
				$this->_transactions = $this->_transactions->
					where('transaction.reference','IS',NULL);	
			}
		}

		if( $this->_search_check_number )
			$this->_transactions = $this->_transactions->
				where('transaction.reference','LIKE','%'.$this->_search_check_number.'%');


		// Not totally happy with this - we should probably create a few calls that reside
		// within an Account_Closebooks parent class... i.e. Account_Closebooks_Check / Search / Create
		if( $this->_search_close_books )
			$this->_transactions = $this->_transactions->
				where('transaction.close_books','IS NOT',NULL)->
				where('transaction.close_books','=',$this->_search_close_books);
		
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