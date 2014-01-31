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
@action Beans_Account_Calibrate
@description Calibrate the balances of an account in the case of a fatal error.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The id of the #Beans_Account# to calibrate.
@returns account The #Beans_Account# that was requested.
@returns calibrated_balance_shift The amount that the entire account was shifted by.
@returns calibrated_transactions An array of all transactions in the journal including a previous and calibrated balance.
@returns @attribute calibrated_transactions id The ID of the #Beans_Account_Transaction#
@returns @attribute calibrated_transactions date The date of the transaction
@returns @attribute calibrated_transactions transaction_id The ID of the parent #Beans_Transaction#
@returns @attribute calibrated_transactions amount The transaction amount
@returns @attribute calibrated_transactions balance The previous balance
@returns @attribute calibrated_transactions calibrated_balance The new balance
---BEANSENDSPEC---
*/
class Beans_Account_Calibrate extends Beans_Account {
	
	private $_id;
	private $_account;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_account = $this->_load_account($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_account->loaded() )
			throw new Exception("Account could not be found.");

		$previous_shift = 0.00;
		$previous_balance = 0.00;
		$calibrated_balance = 0.00;
		$calibrated_balance_shift = 0.00;

		$calibrated_transactions = array();

		// Look up all transactions in order.
		$account_transactions = DB::Query(Database::SELECT,'SELECT id,date,transaction_id,amount,balance FROM account_transactions WHERE account_id = "'.$this->_account->id.'" ORDER BY date ASC, close_books DESC, transaction_id ASC')->execute()->as_array();

		foreach( $account_transactions as $account_transaction )
		{
			$calibrated_balance = $account_transaction['balance'];

			if( round(( $account_transaction['amount'] + $previous_balance ),2) != $calibrated_balance )
				$calibrated_balance = round(( $account_transaction['amount'] + $previous_balance ),2);

			if( $previous_shift != round( $calibrated_balance - $account_transaction['balance'] , 2) )
				$calibrated_balance_shift = round( $calibrated_balance_shift + ( $calibrated_balance - $account_transaction['balance'] ), 2);

			$calibrated_transactions[] = (object)array(
				'id' => $account_transaction['id'],
				'date' => $account_transaction['date'],
				'transaction_id' => $account_transaction['transaction_id'],
				'amount' => $account_transaction['amount'],
				'balance' => $account_transaction['balance'],
				'calibrated_balance' => $calibrated_balance,
			);

			// V2Item
			// There should be a faster way to do this that involves tracking changes in previous_shift and then applying that amount going forward.

			// Update if necessary.
			if( $account_transaction['balance'] != $calibrated_balance )
				DB::Query(Database::UPDATE,'UPDATE account_transactions SET balance = '.$calibrated_balance.' WHERE id = "'.$account_transaction['id'].'"')->execute();

			$previous_balance = $calibrated_balance;
			$previous_shift = $calibrated_balance - $account_transaction['balance'];
		}

		$this->_account_balance_calibrate($this->_account->id);

		return (object)array(
			'account' => $this->_return_account_element($this->_account),
			'calibrated_transactions' => $calibrated_transactions,
			'calibrated_balance_shift' => $calibrated_balance_shift,
		);
	}
}