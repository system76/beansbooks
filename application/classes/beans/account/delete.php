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
@action Beans_Account_Delete
@description Delete an account.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The id of the #Beans_Account# to delete.
@required transfer_account_id The #Beans_Account# that will assume all of the transactions.
---BEANSENDSPEC---
*/
class Beans_Account_Delete extends Beans_Account {

	protected $_auth_role_perm = "account_write";
	
	protected $_id;
	protected $_transfer_account_id;
	protected $_account;
	protected $_transfer_account;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? $data->id
				   : 0;
		$this->_transfer_account_id = ( isset($data->transfer_account_id) )
									? $data->transfer_account_id
									: 0;

		$this->_account = $this->_load_account($this->_id);

		$this->_transfer_account = $this->_load_account($this->_transfer_account_id);
	}

	protected function _execute()
	{
		if( ! $this->_account->loaded() )
			throw new Exception("Account could not be found.");

		if( ORM::Factory('account')->where('parent_account_id','=',$this->_account->id)->count_all() )
			throw new Exception("Please remove all child accounts before deleting.");

		// Query for all transactions associated to this account.
		$transaction_id_rows = DB::query(Database::SELECT,'SELECT DISTINCT(transaction_id) as transaction_id FROM account_transactions WHERE account_id = "'.$this->_account->id.'"')->execute()->as_array();

		if( count($transaction_id_rows) AND 
			! $this->_transfer_account->loaded() )
			throw new Exception("Please select a transfer account.");

		if( count($transaction_id_rows) AND 
			$this->_account->id == $this->_transfer_account->id )
			throw new Exception("Transfer account cannot match the account being removed.");

		/*
		if( count($transaction_id_rows) > 0 )
			throw new Exception("Accounts with transactions currently cannot be removed.");
		*/
			
		// FORMS ?

		// Loop each transaction and update appropriately.
		foreach( $transaction_id_rows as $transaction_id_row )
		{
			$transaction = $this->_load_transaction($transaction_id_row['transaction_id']);

			if( ! $transaction->loaded() )
				throw new Exception("An unexpected error has occurred: transaction not found.");

			// Array for $account_id => $amount
			$new_account_transactions = array();
			$new_account_transactions[$this->_transfer_account->id] = 0.00;

			foreach( $transaction->account_transactions->find_all() as $account_transaction )
			{
				if( $account_transaction->account_reconcile_id ) 
					throw new Exception("Cannot delete accounts that have reconciled transactions.");

				if( $account_transaction->account_transaction_forms->count_all() ) 
					throw new Exception("This account contains transactions that are associated with a form ( invoice, purchase, payment, etc. ).  At this time transactions associated with a form cannot be transferred.");

				if( $account_transaction->account_id == $this->_account->id )
				{
					$new_account_transactions[$this->_transfer_account->id] = $this->_beans_round(
						$new_account_transactions[$this->_transfer_account->id] +
						(
							$account_transaction->amount *
							$account_transaction->account->account_type->table_sign *
							$this->_transfer_account->account_type->table_sign
						)
					);
				} else {
					if( ! isset($new_account_transactions[$account_transaction->account_id]) )
						$new_account_transactions[$account_transaction->account_id] = 0.00;

					$new_account_transactions[$account_transaction->account_id] = $this->_beans_round(
						$new_account_transactions[$account_transaction->account_id] +
						$account_transaction->amount
					);
				}
			}

			// Array for $account_id => $amount
			$account_transaction_update_data = new stdClass;
			$account_transaction_update_data->id = $transaction->id;
			$account_transaction_update_data->account_transactions = array();

			foreach( $new_account_transactions as $account_id => $amount )
				$account_transaction_update_data->account_transactions[] = (object)array(
					'account_id' => $account_id,
					'amount' => $amount,
				);

			$account_transaction_update = new Beans_Account_Transaction_Update($this->_beans_data_auth($account_transaction_update_data));
			$account_transaction_update_result = $account_transaction_update->execute();

			if( ! $account_transaction_update_result->success )
				throw new Exception("Error updating account transaction: ".$account_transaction_update_result->error);
		}

		// Now delete account.
		$this->_account->delete();
		
		return (object)array();
	}
}