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
@action Beans_Account_Transaction_Update
@description Update a transaction in the journal.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Transaction# to update.
@required account_transactions An array of objects describing individual splits within the transaction.
@required @attribute account_transactions account_id The ID of the #Beans_Account#.
@required @attribute account_transactions amount The credit (+) or debit (-) to the account.
@optional date The date for the transaction.
@optional code A code to assign to the transaction for easy lookup.
@optional description A short string describing the transaction.
@optional reference The check ( or reference ) number tied to a transaction.
@returns transaction The resulting #Beans_Transaction#.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Update extends Beans_Account_Transaction {

	protected $_auth_role_perm = "account_transaction_write";
	
	protected $_validate_only;
	protected $_id;
	protected $_transaction;
	protected $_account_transactions;
	protected $_account_transactions_forms;
	protected $_data;
	protected $_affected_account_ids;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
		$this->_id = ( isset($data->id) ) 
				   ? $data->id
				   : 0;
		$this->_transaction = $this->_load_transaction($this->_id);
		$this->_new_transaction = $this->_default_transaction();
		$this->_account_transactions = array();
		$this->_account_transactions_forms = array();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_transaction->loaded() )
			throw new Exception("Invalid transaction: could not be found.");

		if( $this->_check_books_closed($this->_transaction->date) )
			throw new Exception("FYE for that transaction has been closed.");

		// VALIDATE IF TIED TO A FORM.
		if( $this->_transaction->create_form->loaded() OR
			$this->_transaction->invoice_form->loaded() )
			throw new Exception("Transaction is tied to a ".
				( $this->_transaction->create_form->loaded() ? $this->_transaction->create_form->type : $this->_transaction->invoice_form->type ).
				" and must be cancelled through the appropriate interface.");

		if( $this->_transaction->tax_payment->loaded() )
			throw new Exception("Transaction is tied to a tax remittance and must be cancelled through the appropriate interface.");

		if( $this->_transaction->payment ) 
			throw new Exception("Invalid transaction: payments cannot be created or changed via this method.");

		// Basic info for new transaction.
		$this->_new_transaction->code = ( isset($this->_data->code) )
								  ? $this->_data->code
								  : $this->_transaction->code;

		$this->_new_transaction->description = ( isset($this->_data->description) )
										 ? $this->_data->description
										 : $this->_transaction->description;

		$this->_new_transaction->date = ( isset($this->_data->date) )
									  ? $this->_data->date
									  : $this->_transaction->date;

		$this->_new_transaction->reference = ( isset($this->_data->reference) )
									   ? $this->_data->reference
									   : $this->_transaction->reference;

		$this->_new_transaction->entity_id = ( $this->_beans_internal_call() AND
											   isset($this->_data->entity_id) )
										   ? $this->_data->entity_id
										   : $this->_transaction->entity_id;

		$this->_validate_transaction($this->_new_transaction);

		$current_account_transactions = $this->_transaction->account_transactions->find_all();

		$account_transactions_account_reconcile_id = array();
		$account_transactions_writeoff = array();
		$account_transactions_transfer = array();

		// Any transactions that are reconciled or have forms cannot be changed.
		foreach( $current_account_transactions as $current_account_transaction )
		{
			if( $current_account_transaction->account_reconcile_id AND 
				$this->_transaction->date != $this->_new_transaction->date )
				throw new Exception("Invalid transaction date: cannot change the date of a transaction that has been reconciled.");

			if( $current_account_transaction->account_reconcile_id OR 
				$current_account_transaction->account_transaction_forms->count_all() )
			{
				$unchanged = FALSE;
				foreach( $this->_data->account_transactions as $new_account_transaction )
					if( $new_account_transaction->account_id == $current_account_transaction->account_id AND
						$new_account_transaction->amount == $current_account_transaction->amount )
						$unchanged = TRUE;
				
				if( ! $unchanged )
				{
					throw new Exception("Invalid transaction: transfer of ".
						( $current_account_transaction->amount * $current_account_transaction->account->account_type->table_sign )." ".
						( ( ( $current_account_transaction->amount * $current_account_transaction->account->account_type->table_sign ) > 0 ) ? "to" : "from" ).
						" ".$current_account_transaction->account->name." cannot be changed as it has ".
						( $current_account_transaction->account_reconcile_id ? "been reconciled" : "forms attached to it" ).
						".");
				}
				else if( $current_account_transaction->account_transaction_forms->count_all() )
				{
					$this->_account_transactions_forms[$current_account_transaction->account_id] = array();
					foreach( $current_account_transactions->account_transaction_forms->find_all() as $account_transaction_form )
					{
						$this->_account_transactions_forms[$current_account_transaction->account_id][] = $account_transaction_form;
					}
				}

				// Save flags for copying.
				$account_transactions_account_reconcile_id[$current_account_transaction->account_id] = $current_account_transaction->account_reconcile_id;
				$account_transactions_writeoff[$current_account_transaction->account_id] = $current_account_transaction->writeoff;
				$account_transactions_transfer[$current_account_transaction->account_id] = $current_account_transaction->transfer;
			}
		}

		$this->_new_transaction->amount = 0.00;

		$transaction_balance = 0;

		// Make sure there's only one account_transaction per account.
		$account_ids = array();
		foreach( $this->_data->account_transactions as $account_transaction )
		{
			if( in_array($account_transaction->account_id, $account_ids) )
				throw new Exception("You can only have one split per account.");
			
			$account_ids[] = $account_transaction->account_id;
		}
		
		foreach( $this->_data->account_transactions as $account_transaction )
		{
			$new_account_transaction = $this->_default_account_transaction();

			$new_account_transaction->account_id = $account_transaction->account_id;
			$new_account_transaction->amount = $account_transaction->amount;
			$new_account_transaction->date = $this->_new_transaction->date;

			$new_account_transaction->account_reconcile_id = ( isset($account_transactions_account_reconcile_id[$new_account_transaction->account_id]) )
														   ? $account_transactions_account_reconcile_id[$new_account_transaction->account_id]
														   : NULL;
			$new_account_transaction->writeoff = (	(
														isset($account_transactions_writeoff[$new_account_transaction->account_id]) AND 
														$account_transactions_writeoff[$new_account_transaction->account_id] 
													) OR 
													(
														isset($account_transaction->writeoff) AND
														$account_transaction->writeoff
													) )
											   ? TRUE
											   : FALSE;
			$new_account_transaction->transfer = (	(
														isset($account_transactions_transfer[$new_account_transaction->account_id]) AND 
														$account_transactions_transfer[$new_account_transaction->account_id] 
													) OR 
													(
														isset($account_transaction->transfer) AND
														$account_transaction->transfer
													) )
											   ? TRUE
											   : FALSE;

			// TODO - If we ever allow updating close books transactions, 
			// then add that above for $this->_new_transaction and add the proper logic here.
			$new_account_transaction->close_books = FALSE;//( $account_transaction->close_books ) ? TRUE : FALSE;

			$this->_validate_account_transaction($new_account_transaction);

			$transaction_balance = $this->_beans_round(($transaction_balance + $new_account_transaction->amount));
			
			// Update transaction amount with credits only to get the total moving in a single direction.
			$this->_new_transaction->amount += ( $new_account_transaction->amount > 0 )
										? $new_account_transaction->amount
										: 0;

			$this->_account_transactions[$new_account_transaction->account_id] = $new_account_transaction;

		}

		$transacts = array();

		foreach( $this->_account_transactions as $_account_transaction )
			$transacts[] = (object)array(
				'account_id' => $_account_transaction->account_id,
				'amount' => $_account_transaction->amount,
				'account_reconcile_id' => $_account_transaction->account_reconcile_id,
				'writeoff' => $_account_transaction->writeoff,
				'transfer' => $_account_transaction->transfer,
			);

		if( $transaction_balance != 0.00 )
			throw new Exception("Those transactions did not have a zero-sum.");
		
		if( $this->_validate_only )
			return (object)array();

		// Remove current account transactions and adjust balances.
		foreach( $current_account_transactions as $current_account_transaction )
			$this->_account_transaction_remove($current_account_transaction);

		// Remove old transaction, but save ID.
		$_old_transaction_id = $this->_transaction->id;
		$this->_transaction->delete();

		// Save new transaction.
		$this->_new_transaction->id = $_old_transaction_id;
		$this->_new_transaction->save();

		// Insert new transactions
		foreach( $this->_account_transactions as $i => $_account_transaction )
		{
			$this->_account_transactions[$i]->transaction_id = $this->_new_transaction->id;

			// Insert transaction and save ID.
			$this->_account_transactions[$i]->id = $this->_account_transaction_insert($_account_transaction);

			// Update forms with new account transaction id.
			if( isset($this->_account_transactions_forms[$_account_transaction->account_id]) AND 
				count($this->_account_transactions_forms[$_account_transaction->account_id]) )
			{
				foreach( $this->_account_transactions_forms[$_account_transaction->account_id] as $account_transaction_form )
				{
					$account_transaction_form->account_transaction_id = $_account_transaction->id;
					$account_transaction_form->save();
				}
			}
		}
		
		return (object)array(
			"transaction" => $this->_return_transaction_element($this->_new_transaction),
		);
	}
}