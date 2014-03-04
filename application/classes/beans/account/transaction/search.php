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
@optional sort_by The sort pattern for the returned results: 'newest', 'oldest'
@optional page The page to return.
@optional page_size The number of results on each page.
@optional account_id The ID of the #Beans_Account# to restrict the search to.
@optional not_reconciled BOOLEAN to set restricting the search to unreconciled transactions.
@optional date Limit the search to a specific YYYY-MM-DD date.
@optional date_after Limit the search to transactions strictly after ( > ) a specific date.
@optional date_before Limit the search to transactions strictly before ( < ) a specific date.
@optional after_transaction_id Limit the search to transactions strictly after the one provided.
@optional before_transaction_id Limit the search to transactions strictly before the one provided.
@returns transactions An array of #Beans_Transaction#.
@returns total_results Total number of results.
@returns sort_by The sort method used in the returned results.
@returns pages The total number of pages of results.
@returns page The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Search extends Beans_Account_Transaction {

	private $_data;

	private $_page;
	private $_page_size;
	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'date' => 'desc',
			'close_books' => 'asc',
			'id' => 'desc',
			//'sort' => 'desc'
		),
		'oldest' => array(
			'date' => 'asc',
			'close_books' => 'desc',
			'id' => 'asc',
		)
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;

		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) &&
							isset($this->_sort_by_patterns[strtolower($data->sort_by)]) )
						? strtolower($data->sort_by)
						: "newest";
	}

	protected function _execute()
	{
		// Simplifies query manipulation a bit.
		$transaction_ids_query_where = ' id IS NOT NULL AND ';

		if( isset($this->_data->account_id) )
			$transaction_ids_query_where .= ' account_id = '.$this->_data->account_id.' AND ';

		if( isset($this->_data->reconciled) &&
			$this->_data->reconciled ) 
			$transaction_ids_query_where .= ' account_reconcile_id IS NOT NULL AND ';
		else if( isset($this->_data->reconciled) &&
				 ! $this->_data->reconciled )
			$transaction_ids_query_where .= ' account_reconcile_id IS NULL AND ';

		if( isset($this->_data->date) )
		{
			if( $this->_data->date != date("Y-m-d",strtotime($this->_data->date)) )
				throw new Exception("Invalid date provided: must be in YYYY-MM-DD format.");

			$transaction_ids_query_where .= ' date = DATE("'.$this->_data->date.'") AND ';
		}

		if( isset($this->_data->date_after) )
		{
			if( $this->_data->date_after != date("Y-m-d",strtotime($this->_data->date_after)) )
				throw new Exception("Invalid date_after provided: must be in YYYY-MM-DD format.");

			$transaction_ids_query_where .= ' date > DATE("'.$this->_data->date_after.'") AND ';
		}

		if( isset($this->_data->date_before) )
		{
			if( $this->_data->date_before != date("Y-m-d",strtotime($this->_data->date_before)) )
				throw new Exception("Invalid date_before provided: must be in YYYY-MM-DD format.");

			$transaction_ids_query_where .= ' date < DATE("'.$this->_data->date_before.'") AND ';
		}

		if( isset($this->_data->after_transaction_id) )
		{
			// Load transaction to validate.
			$after_transaction = $this->_load_transaction($this->_data->after_transaction_id);

			if( ! $after_transaction->loaded() )
				throw new Exception("Invalid after_transaction_id provided: transaction not found.");

			
			$transaction_ids_query_where .= ' ( '.
			' 	( date > DATE("'.$after_transaction->date.'") ) OR '.
			' 	( date = DATE("'.$after_transaction->date.'") AND close_books < '.( $after_transaction->close_books ? '1' : '0' ).' ) OR '.
			' 	( date = DATE("'.$after_transaction->date.'") AND close_books <= '.( $after_transaction->close_books ? '1' : '0' ).' AND transaction_id > '.$after_transaction->id.' ) '.
			') AND ';
		}

		if( isset($this->_data->before_transaction_id) )
		{
			$before_transaction = $this->_load_transaction($this->_data->before_transaction_id);

			if( ! $before_transaction->loaded() )
				throw new Exception("Invalid before_transaction_id provided: transaction not found.");

			
			$transaction_ids_query_where .= ' ( '.
			' 	( date < DATE("'.$before_transaction->date.'") ) OR '.
			' 	( date = DATE("'.$before_transaction->date.'") AND close_books > '.( $before_transaction->close_books ? '1' : '0' ).' ) OR '.
			' 	( date = DATE("'.$before_transaction->date.'") AND close_books >= '.( $before_transaction->close_books ? '1' : '0' ).' AND transaction_id < '.$before_transaction->id.' ) '.
			') AND ';
		}

		if( strlen($transaction_ids_query_where) > strlen(' id IS NOT NULL AND ') )
			$transaction_ids_query_where = str_replace(' id IS NOT NULL AND', '' , $transaction_ids_query_where);

		$transaction_ids_query_where = substr($transaction_ids_query_where, 0, -4).' ';

		// Get Total
		$transaction_count_query = 'SELECT IFNULL(COUNT(DISTINCT(transaction_id)),0) as total FROM account_transactions WHERE '.$transaction_ids_query_where;
		$transaction_count_result = DB::Query(Database::SELECT, $transaction_count_query)->execute()->as_array();

		$total_results = $transaction_count_result[0]['total'];
		$pages = ceil($total_results / $this->_page_size) + 1;

		// Get Transaction IDs
		$transaction_ids_query = 'SELECT DISTINCT(transaction_id) FROM account_transactions WHERE '.$transaction_ids_query_where;
		
		$transaction_ids_query .= 'ORDER BY ';
		foreach( $this->_sort_by_patterns[$this->_sort_by] as $field => $order )
			$transaction_ids_query .= $field.' '.$order.', ';

		$transaction_ids_query = substr($transaction_ids_query, 0, -2).' ';

		$transaction_ids_query .= ' LIMIT '.$this->_page_size.' OFFSET '.floor( $this->_page * $this->_page_size ).' ';

		$transaction_ids_result = DB::Query(Database::SELECT, $transaction_ids_query)->execute()->as_array();

		if( ! count($transaction_ids_result) )
			return (object)array(
				"total_results" => 0,
				"sort_by" => $this->_sort_by,
				"pages" => $pages,
				"page" => $this->_page,
				"transactions" => array(),
			);

		$transaction_ids = array();
		foreach( $transaction_ids_result as $transaction_id_result )
			$transaction_ids[] = $transaction_id_result['transaction_id'];

		$transactions = ORM::Factory('transaction')->
			where('id','IN',$transaction_ids)->
			order_by(DB::Expr('FIELD(`id`, '.implode(',',$transaction_ids).')'))->
			find_all();
		
		return (object)array(
			"total_results" => $total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $pages,
			"page" => $this->_page,
			"transactions" => $this->_return_transactions_array($transactions),
		);
	}

}