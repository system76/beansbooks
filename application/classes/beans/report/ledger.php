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

class Beans_Report_Ledger extends Beans_Report {

	protected $_date_start;
	protected $_date_end;
	protected $_account_id;
	protected $_account_transactions;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_account_id = ( isset($data->account_id) )
						   ? $data->account_id
						   : NULL;

		$this->_date_start = ( isset($data->date_start) )
					 ? $data->date_start
					 : NULL;

		$this->_date_end = ( isset($data->date_end) )
					 ? $data->date_end
					 : NULL;

	}

	protected function _execute()
	{
		if( ! $this->_account_id ) 
			throw new Exception("Invalid report account: none provided.");

		$account = $this->_load_account($this->_account_id);

		if( ! $account->loaded() )
			throw new Exception("Invalid report account: not found.");

		if( ! $this->_date_start ) 
			throw new Exception("Invalid report start date: none provided.");

		if( $this->_date_start != date("Y-m-d",strtotime($this->_date_start)) )
			throw new Exception("Invalid report start date: must be in format YYYY-MM-DD.");

		if( ! $this->_date_end ) 
			throw new Exception("Invalid report end date: none provided.");

		if( $this->_date_end != date("Y-m-d",strtotime($this->_date_end)) )
			throw new Exception("Invalid report end date: must be in format YYYY-MM-DD.");

		$this->_account_transactions = ORM::Factory('account_transaction')->
			join('transactions','right')->
			on('transactions.id','=','account_transaction.transaction_id');

		$this->_account_transactions = $this->_account_transactions->
			where('account_transaction.account_id','=',$account->id)->
			where('transactions.date','>=',$this->_date_start)->
			where('transactions.date','<=',$this->_date_end);

		$this->_account_transactions = $this->_account_transactions->
			order_by('transactions.date','asc')->
			order_by('transactions.id','asc');
		
		$this->_account_transactions = $this->_account_transactions->find_all();

		return (object)array(
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'account' => $this->_return_account_element($account),
			'account_transactions' => $this->_return_ledger_transactions_array($this->_account_transactions),
		);
	}

	// This is a special return type for the ledger, and we don't want to confuse
	// it with return_account_transactions_array from Class_Accounts
	protected function _return_ledger_transactions_array($account_transactions)
	{
		$return_array = array();

		foreach( $account_transactions as $account_transaction )
			$return_array[] = $this->_return_ledger_transaction_element($account_transaction);
		
		return $return_array;
	}

	protected function _return_ledger_transaction_element($account_transaction)
	{
		return (object)array(
			'date' => $account_transaction->transaction->date,
			'check_number' => $account_transaction->transaction->reference,
			'number' => $account_transaction->transaction->code,
			'description' => $account_transaction->transaction->description,
			'debit' => ( $account_transaction->amount < 0 ? $account_transaction->amount : FALSE ),
			'credit' => ( $account_transaction->amount > 0 ? $account_transaction->amount : FALSE ),
			'balance' => $account_transaction->balance,
		);
	}

}