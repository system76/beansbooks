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
class Beans_Account_Transaction_Search_Form extends Beans_Account_Transaction_Search {

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

	protected $_form_id;
	
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
						
		$this->_form_id = ( isset($data->form_id) )
						? $data->form_id
						: FALSE;

		// Re-declare $this->_transactions to use proper joins for a form search.
		$this->_transactions = ORM::Factory('transaction')->DISTINCT(TRUE)->
			join('account_transactions','RIGHT')->on('account_transactions.transaction_id','=','transaction.id')->
			join('account_transaction_forms','RIGHT')->on('account_transaction_forms.account_transaction_id','=','account_transactions.id');
	}

	protected function _execute()
	{
		// Internal Only Right Now
		// Consider making public.
		if( ! $this->_beans_internal_call() )
			throw new Exception("Restricted to internal calls.");

		if( ! $this->_form_id )
			throw new Exception("Invalid form ID: none provided.");

		$this->_transactions = $this->_transactions->
			where('account_transaction_forms.form_id','=',$this->_form_id);

		$result_object = $this->_find_transactions();

		return (object)array(
			"total_results" => $result_object->total_results,
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