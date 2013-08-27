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
@action Beans_Account_Reconcile_Create
@description Reconcile a statement on an account.
@required auth_uid
@required auth_key
@required auth_expiration
@required account_id The ID of the #Beans_Account# being reconciled.
@required date The statement date.
@required balance_start The starting balance for the statement.
@required balance_end The ending balance for the statement.
@required account_transaction_ids An array of IDs representing the included #Beans_Account_Transaction# being reconciled.
@returns account_reconcile The resulting #Beans_Account_Reconcile#.
---BEANSENDSPEC---
*/
class Beans_Account_Reconcile_Create extends Beans_Account_Reconcile {

	protected $_account_id;
	protected $_account;
	protected $_account_reconcile;
	protected $_data;
	protected $_validate_only;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_account_id = ( isset($data->account_id) ) 
				   ? $data->account_id
				   : 0;

		$this->_data = $data;

		$this->_account = $this->_load_account($this->_account_id);

		$this->_account_reconcile = $this->_default_account_reconcile();

		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_account->loaded() )
			throw new Exception("Account could not be found.");

		$this->_account_reconcile->account_id = $this->_account->id;
		$this->_account_reconcile->date = $this->_data->date;
		$this->_account_reconcile->balance_start = $this->_beans_round($this->_data->balance_start);
		$this->_account_reconcile->balance_end = $this->_beans_round($this->_data->balance_end);

		// Make sure our base data is good.
		$this->_validate_account_reconcile($this->_account_reconcile);

		if( ! isset($this->_data->account_transaction_ids) OR
			! count($this->_data->account_transaction_ids) )
			throw new Exception("Invalid account transaction IDs: none provided.");

		// We'll push all account transactions into this array to quickly update once we've validated.
		$account_transactions = array();
		$account_transactions_amount_delta = 0.00;

		foreach( $this->_data->account_transaction_ids as $account_transaction_id )
		{
			$account_transaction = $this->_load_account_transaction($account_transaction_id);

			if( ! $account_transaction->loaded() )
				throw new Exception("Invalid account transaction ID: ".$account_transaction_id." - account transaction not found.");

			if( $account_transaction->account_reconcile_id )
				throw new Exception("Invalid account transaction ID: ".$account_transaction_id." - already reconciled.");

			$account_transactions_amount_delta = $this->_beans_round($account_transactions_amount_delta + $account_transaction->amount);

			$account_transactions[] = $account_transaction;
		}

		$account_transactions_amount_delta = $this->_account->account_type->table_sign * $account_transactions_amount_delta;

		// Validate delta.
		if( $this->_beans_round($account_transactions_amount_delta) != $this->_beans_round($this->_account_reconcile->balance_end - $this->_account_reconcile->balance_start) )
			throw new Exception("The sum of those transactions did not match the starting and ending balance.  Did you forget an account transaction?");
		
		if( $this->_validate_only )
			return (object)array();
		
		$this->_account_reconcile->save();

		foreach( $account_transactions as $account_transaction )
		{
			$account_transaction->account_reconcile_id = $this->_account_reconcile->id;
			$account_transaction->save();
		}

		return (object)array(
			"account_reconcile" => $this->_return_account_reconcile_element($this->_account_reconcile),
		);
	}
}