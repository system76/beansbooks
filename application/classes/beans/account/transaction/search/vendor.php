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
class Beans_Account_Transaction_Search_Vendor extends Beans_Account_Transaction_Search {

	protected $_transactions;
	private $_page;
	private $_page_size;

	private $_search_vendor_id;
	private $_search_keywords;
	private $_search_check_number;
	private $_search_date;

	private $_search_and;

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
		'checknewest' => array(
			'reference' => 'desc',
			'date' 		=> 'desc',
			'id' 		=> 'desc',
		),
		'checkoldest' => array(
			'reference' => 'asc',
			'date'		=> 'asc',
			'id'		=> 'asc',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_transactions = ORM::Factory('transaction')->distinct(TRUE);
		
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
		$this->_search_vendor_id = ( isset($data->vendor_id) ) 
								 ? $data->vendor_id 
								 : FALSE;

		$this->_search_keywords = ( isset($data->search_keywords) ) 
								? $data->search_keywords 
								: FALSE;

		$this->_search_date = ( isset($data->date) )
							 ? $data->date
							 : FALSE;

		$this->_search_check_number = ( isset($data->check_number) )
									? $data->check_number
									: FALSE;

		$this->_search_and = ( isset($data->search_and) ) 
						   ? ( $data->search_and ? TRUE : FALSE )
						   : TRUE;
	}

	protected function _execute()
	{
		if( ! $this->_search_vendor_id )
			throw new Exception("Missing required parameter: vendor_id");

		// This effectively limits the results only to a vendor with the ID.
		$this->_transactions = $this->_transactions->
			where('entity_id','=',$this->_search_vendor_id)->
			and_where_open()->
				or_where('payment','=','vendor')->
				or_where('payment','=','expense')->
			and_where_close();

		if( $this->_search_keywords OR $this->_search_date OR $this->_search_check_number )
		{
			$this->_transactions = $this->_transactions->and_where_open();

			if( $this->_search_keywords )
			{
				$forms = ORM::Factory('form')->where('entity_id','=',$this->_search_vendor_id);
				$query = FALSE;
				foreach( explode(' ',$this->_search_keywords) as $keyword )
				{
					$term = trim($keyword);

					if( $term )
					{
						$query = TRUE;
						$forms = $forms->
							and_where_open()->
							or_where('code','LIKE','%'.$term.'%')->
							or_where('reference','LIKE','%'.$term.'%')->
							or_where('alt_reference','LIKE','%'.$term.'%')->
							or_where('aux_reference','LIKE','%'.$term.'%')->
							and_where_close();
					}
				}
				if( $query )
				{
					$forms = $forms->find_all();
				}
				else
				{
					$forms = array();
				}

				$form_ids = array();
				foreach( $forms as $form )
					$form_ids[] = $form->id;

				$account_transactions = ORM::Factory('account_transaction')->join('account_transaction_forms','LEFT')->on('account_transaction_forms.account_transaction_id','=','account_transaction.id');
				$account_transactions = $account_transactions->where('account_transaction_forms.form_id','IN',$form_ids);
				$account_transactions = $account_transactions->find_all();

				$transaction_ids = array();
				foreach( $account_transactions as $account_transaction )
					$transaction_ids[] = $account_transaction->transaction_id;

				if( $this->_search_and )
					$this->_transactions = $this->_transactions->where('id','IN',$transaction_ids);
				else
					$this->_transactions = $this->_transactions->or_where('id','IN',$transaction_ids);
			}

			if( $this->_search_date )
			{
				if( $this->_search_and )
					$this->_transactions = $this->_transactions->where('transaction.date','=',$this->_search_date);
				else
					$this->_transactions = $this->_transactions->or_where('transaction.date','=',$this->_search_date);
			}

			if( $this->_search_check_number )
			{
				if( $this->_search_and )
					$this->_transactions = $this->_transactions->where('transaction.reference','LIKE','%'.$this->_search_check_number.'%');
				else
					$this->_transactions = $this->_transactions->or_where('transaction.reference','LIKE','%'.$this->_search_check_number.'%');
			}

			$this->_transactions = $this->_transactions->and_where_close();
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

}