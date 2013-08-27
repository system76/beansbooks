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
@action Beans_Account_Transaction_Match
@description Attempt to match #Beans_Account_Transaction# elements within a journal.  This receive and returns a unique type of object for this particular use case.
@required auth_uid
@required auth_key
@required auth_expiration
@required account_transactions An array of objects describing individual account transactions to match.
@required @attribute account_transactions account_id The ID of the #Beans_Account#.
@required @attribute account_transactions date The date of the transaction.
@required @attribute account_transactions amount The amount of the transaction.
@required @attribute account_transactions hash A unique identifier to match results with.
@optional @attribute account_transactions description A text description of the transaction.
@returns account_transactions An array of objects representing matched transactions.
@returns @attribute account_transactions hash The hash that was originally submitted with the account transaction.
@returns @attribute account_transactions duplicate A boolean to declare if this is suspected of being an exact duplicate of another transaction.
@returns @attribute account_transactions transaction The matched #Beans_Transaction# if one was found.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Match extends Beans_Account_Transaction {

	private $_account_transactions;
	private $_date_range_days;
	private $_ignore_payments;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_account_transactions = ( isset($data->account_transactions) AND is_array($data->account_transactions) )
							 ? $data->account_transactions
							 : array();

		$this->_date_range_days = ( isset($data->date_range_days) AND $data->date_range_days > 0 )
								? (int)$data->date_range_days
								: 3;

		$this->_ignore_payments = ( isset($data->ignore_payments) AND $data->ignore_payments )
								? TRUE
								: FALSE;
	}

	protected function _execute()
	{
		if( ! count($this->_account_transactions) )
			throw new Exception("No transactions provided to match.");

		foreach( $this->_account_transactions as $account_transaction )
		{
			if( ! isset($account_transaction->hash) )
				throw new Exception("Account transaction did not include a hash - include one to compare results.");

			// Try to find a duplicate.
			$transactions = ORM::Factory('transaction')->
				distinct(TRUE)->
				join('account_transactions','RIGHT')->
				on('account_transactions.transaction_id','=','transaction.id');
				
			$transactions = $transactions->
				where('transaction.date','>=',date("Y-m-d",strtotime($account_transaction->date." -".$this->_date_range_days." Days")));

			$transactions = $transactions->
				where('transaction.date','<=',date("Y-m-d",strtotime($account_transaction->date." +".$this->_date_range_days." Days")));

			$transactions = $transactions->
				where('account_transactions.amount','=',$account_transaction->amount);

			$transactions = $transactions->
				where('account_transactions.account_id','=',$account_transaction->account_id);

			// DO NOT MATCH FORMS
			if( $this->_ignore_payments )
				$transactions = $transactions->and_where_open()->
					or_where('transaction.payment','IS',NULL)->
					or_where('transaction.payment','=','')->
					and_where_close();
			
			$transactions = $transactions->limit(1)->order_by('transaction.date','DESC');

			$transaction = $transactions->find();

			if( ! $transaction->loaded() AND 
				isset($account_transaction->number) AND
				is_numeric($account_transaction->number) )
			{
				// Try a code matchup.
				$transactions = ORM::Factory('transaction')->
					distinct(TRUE)->
					join('account_transactions','RIGHT')->
					on('account_transactions.transaction_id','=','transaction.id');

				$transactions = $transactions->
					where('account_transactions.account_id','=',$account_transaction->account_id);

				$transactions = $transactions->
					where('transaction.code','=',$account_transaction->number);

				$transactions = $transactions->
					where('account_transactions.amount','=',$account_transaction->amount);

				$transactions = $transactions->
					where('transaction.date','>=',date("Y-m-d",strtotime($account_transaction->date." -".( 3 * $this->_date_range_days )." Days")));

				$transactions = $transactions->
					where('transaction.date','<=',date("Y-m-d",strtotime($account_transaction->date)));

				// DO NOT MATCH FORMS
				if( $this->_ignore_payments )
					$transactions = $transactions->and_where_open()->
						or_where('transaction.payment','IS',NULL)->
						or_where('transaction.payment','=','')->
						and_where_close();

				$transactions = $transactions->limit(1)->order_by('transaction.date','DESC');

				$transaction = $transactions->find();
			}

			// Default return values.
			$account_transaction->duplicate = FALSE;
			$account_transaction->transaction = NULL;

			if( $transaction->loaded() )
				$account_transaction->duplicate = TRUE;
			else
			{
				// Try to find a match for transfer account.
				$transactions = ORM::Factory('transaction')->
					distinct(TRUE)->
					join('account_transactions','LEFT')->
					on('account_transactions.transaction_id','=','transaction.id');

				$range = (int)log(abs($account_transaction->amount),10);
				$pos = ( $account_transaction->amount > 0 )
					 ? 1
					 : -1;

				$transactions = $transactions->
					where('account_transactions.account_id','=',$account_transaction->account_id);

				if( isset($account_transaction->description) AND 
					strlen($account_transaction->description) )
				{
					$transactions = $transactions->and_where_open();

					foreach( explode(' ', str_replace(range(0,9),' ',$account_transaction->description)) as $alphakeyword )
					{
						if( strlen(trim($alphakeyword)) >= 3 )
							$transactions = $transactions->
								where('transaction.description','LIKE','%'.trim($alphakeyword).'%');
					}

					$transactions = $transactions->and_where_close();
				}

				$transactions = $transactions->limit(1)->order_by(array(
					DB::Expr('transaction.date DESC'),
					DB::Expr('ABS('.$account_transaction->amount.'-account_transactions.amount) ASC'),
				));

				$transaction = $transactions->find();
			}

			if( $transaction->loaded() )
				$account_transaction->transaction = $this->_return_transaction_element($transaction);
			
		}

		return (object)array(
			"account_transactions" => $this->_account_transactions,
		);
	}
}