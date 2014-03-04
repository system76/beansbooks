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
	protected $_old_transaction;
	protected $_transaction;
	protected $_account_transactions;
	protected $_account_transactions_forms;
	protected $_data;
	protected $_affected_account_ids;
	protected $_payment_type_handled;
	protected $_form_type_handled;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
		$this->_id = ( isset($data->id) ) 
				   ? $data->id
				   : 0;
		$this->_old_transaction = $this->_load_transaction($this->_id);
		$this->_transaction = $this->_default_transaction();
		$this->_account_transactions = array();
		$this->_account_transactions_forms = array();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;

		$this->_payment_type_handled = ( isset($data->payment_type_handled) )
									 ? $data->payment_type_handled
									 : FALSE;
		$this->_form_type_handled = ( isset($data->form_type_handled) )
								  ? $data->form_type_handled
								  : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_old_transaction->loaded() )
			throw new Exception("Invalid transaction: could not be found.");

		if( $this->_check_books_closed($this->_old_transaction->date) )
			throw new Exception("FYE for that transaction has been closed.");

		if( $this->_old_transaction->close_books )
			throw new Exception("Close books transactions cannot be changed.");

		// VALIDATE IF TIED TO A FORM.
		if( (
				$this->_old_transaction->create_form->loaded() AND
				( 
					! $this->_beans_internal_call() OR 
					$this->_form_type_handled != $this->_old_transaction->create_form->type
				)
			) OR
			(
				$this->_old_transaction->invoice_form->loaded() AND
				(
					! $this->_beans_internal_call() OR 
					$this->_form_type_handled != $this->_old_transaction->invoice_form->type 
				)
			) )
			throw new Exception("Transaction is tied to a ".
				( $this->_old_transaction->create_form->loaded() ? $this->_old_transaction->create_form->type : $this->_old_transaction->invoice_form->type ).
				" and must be cancelled through the appropriate interface.");

		if( $this->_old_transaction->tax_payment->loaded() )
			throw new Exception("Transaction is tied to a tax remittance and must be updated through the appropriate interface.");

		if( $this->_old_transaction->payment AND 
			(
				! $this->_beans_internal_call() OR 
				$this->_payment_type_handled != $this->_old_transaction->payment 
			) )
			throw new Exception("Invalid transaction: payments cannot be created or changed via this method.");

		// Basic info for new transaction.
		$this->_transaction->code = ( isset($this->_data->code) AND $this->_data->code )
								  ? $this->_data->code
								  : $this->_old_transaction->code;

		$this->_transaction->description = ( isset($this->_data->description) AND $this->_data->description )
										 ? $this->_data->description
										 : $this->_old_transaction->description;

		$this->_transaction->date = ( isset($this->_data->date) AND $this->_data->date )
									  ? $this->_data->date
									  : $this->_old_transaction->date;

		$this->_transaction->reference = ( isset($this->_data->reference) AND $this->_data->reference )
									   ? $this->_data->reference
									   : $this->_old_transaction->reference;

		$this->_transaction->entity_id = ( $this->_beans_internal_call() AND
											   isset($this->_data->entity_id) AND
											   $this->_data->entity_id )
										   ? $this->_data->entity_id
										   : $this->_old_transaction->entity_id;

		$this->_transaction->form_type = $this->_old_transaction->form_type;
		$this->_transaction->form_id = $this->_old_transaction->form_id;

		// If this was previously a payment we retain that attribute
		// However, if this is a new payment ( i.e. Payment/Replace ) then we want 
		// to apply the new flag appropriately.
		if( $this->_old_transaction->payment )
			$this->_transaction->payment = $this->_old_transaction->payment;
		else if ( $this->_beans_internal_call() AND 
				  isset($this->_data->payment) AND 
				  $this->_data->payment )
			$this->_transaction->payment = $this->_data->payment;

		// TODO - If changing FYE transactions is enabled, add close_books here.

		$this->_validate_transaction($this->_transaction);

		$current_account_transactions = $this->_old_transaction->account_transactions->find_all();

		// We'll need to save the account_reconcile_id tied to any reconciled transactions.
		$account_transactions_account_reconcile_id = array();
		
		// Any transactions that are reconciled cannot be changed.
		foreach( $current_account_transactions as $current_account_transaction )
		{
			if( $current_account_transaction->account_reconcile_id )
			{
				$unchanged = FALSE;
				foreach( $this->_data->account_transactions as $new_account_transaction )
					if( isset($new_account_transaction->account_id) AND
						$new_account_transaction->account_id == $current_account_transaction->account_id AND
						$new_account_transaction->amount == $current_account_transaction->amount )
						$unchanged = TRUE;
				
				if( ! $unchanged )
				{
					throw new Exception("Invalid transaction: transfer of ".
						( $current_account_transaction->amount * $current_account_transaction->account->account_type->table_sign )." ".
						( ( ( $current_account_transaction->amount * $current_account_transaction->account->account_type->table_sign ) > 0 ) ? "to" : "from" ).
						" ".$current_account_transaction->account->name." cannot be changed as it has been reconciled.");
				}
				
				// Save flags for copying.
				$account_transactions_account_reconcile_id[$current_account_transaction->account_id] = $current_account_transaction->account_reconcile_id;
			}
		}

		// Create our new transaction.

		$this->_transaction->amount = 0.00;
		$transaction_balance = 0;

		// Make sure there's only one account_transaction per account.
		$account_ids = array();
		foreach( $this->_data->account_transactions as $account_transaction )
		{
			if( ! isset($account_transaction->account_id) ||
				! isset($account_transaction->amount) )
				throw new Exception("Account transaction missing required info: account_id and amount are required.");
			
			if( in_array($account_transaction->account_id, $account_ids) )
				throw new Exception("You can only have one split per account.");
			
			$account_ids[] = $account_transaction->account_id;
		}
		
		if( ! count($this->_data->account_transactions) )
			throw new Exception("No account transactions were provided.");

		$calibrate_form_ids = array();

		foreach( $this->_data->account_transactions as $account_transaction )
		{
			$new_account_transaction = $this->_default_account_transaction();

			$new_account_transaction->account_id = $account_transaction->account_id;
			$new_account_transaction->amount = $account_transaction->amount;
			$new_account_transaction->date = $this->_transaction->date;

			$new_account_transaction->account_reconcile_id = ( isset($account_transactions_account_reconcile_id[$new_account_transaction->account_id]) )
														   ? $account_transactions_account_reconcile_id[$new_account_transaction->account_id]
														   : NULL;
			if( isset($account_transaction->transfer) AND 
				$account_transaction->transfer )
				$new_account_transaction->transfer = TRUE;

			if( isset($account_transaction->writeoff) AND 
				$account_transaction->writeoff )
				$new_account_transaction->writeoff = TRUE;

			// TODO - If we ever allow updating close books transactions, 
			// then add that above for $this->_transaction and add the proper logic here.
			$new_account_transaction->close_books = FALSE;//( $account_transaction->close_books ) ? TRUE : FALSE;

			$this->_validate_account_transaction($new_account_transaction);

			$transaction_balance = $this->_beans_round(($transaction_balance + $new_account_transaction->amount));
			
			// Update transaction amount with credits only to get the total moving in a single direction.
			$this->_transaction->amount += ( $new_account_transaction->amount > 0 )
										? $new_account_transaction->amount
										: 0;

			$this->_account_transactions[$new_account_transaction->account_id] = $new_account_transaction;

			$this->_account_transactions_forms[$new_account_transaction->account_id] = array();

			if( $this->_beans_internal_call() AND 
				isset($account_transaction->forms) AND
				is_array($account_transaction->forms) AND
				count($account_transaction->forms) )
			{
				$account_transaction_form_total = 0.00;

				foreach( $account_transaction->forms as $form )
				{
					if( ! in_array($form->form_id, $calibrate_form_ids) )
						$calibrate_form_ids[] = $form->form_id;

					$new_account_transaction_form = $this->_default_account_transaction_form();

					$new_account_transaction_form->form_id = ( isset($form->form_id) )
														   ? $form->form_id
														   : NULL;

					$new_account_transaction_form->amount = ( isset($form->amount) )
														  ? $form->amount
														  : NULL;

					$new_account_transaction_form->writeoff_amount = ( isset($form->writeoff_amount) )
																   ? $form->writeoff_amount
																   : NULL;

					$this->_validate_account_transaction_form($new_account_transaction_form);

					$account_transaction_form_total = $this->_beans_round( $account_transaction_form_total + $new_account_transaction_form->amount);

					$this->_account_transactions_forms[$new_account_transaction->account_id][] = $new_account_transaction_form;
				}
			}

		}
		
		if( $transaction_balance != 0.00 )
			throw new Exception("Those transactions did not have a zero-sum.");
		
		if( $this->_validate_only )
			return (object)array();

		// Remove old account transactions and their forms.
		foreach( $current_account_transactions as $current_account_transaction )
		{
			foreach( $current_account_transaction->account_transaction_forms->find_all() as $current_account_transaction_form )
			{
				if( ! in_array($current_account_transaction_form->form_id, $calibrate_form_ids) )
					$calibrate_form_ids[] = $current_account_transaction_form->form_id;

				$current_account_transaction_form->delete();
			}

			$this->_account_transaction_remove($current_account_transaction);
		}

		// Remove old transaction, but save ID.
		$this->_transaction->id = $this->_old_transaction->id;
		$this->_old_transaction->delete();
		
		// Save new transaction
		$this->_transaction->save();

		// Save account transactions
		foreach( $this->_account_transactions as $account_id => $_account_transaction )
		{
			$this->_account_transactions[$account_id]->transaction_id = $this->_transaction->id;

			// Insert transaction and save ID.
			$this->_account_transactions[$account_id]->id = $this->_account_transaction_insert($_account_transaction);

			// Add forms to account transaction.
			if( isset($this->_account_transactions_forms[$account_id]) AND 
				count($this->_account_transactions_forms[$account_id]) )
			{
				foreach( $this->_account_transactions_forms[$account_id] as $account_transaction_form )
				{
					$account_transaction_form->account_transaction_id = $_account_transaction->id;
					$account_transaction_form->save();
				}
			}
		}

		// Re-calibrate all affected form balances.
		foreach( $calibrate_form_ids as $calibrate_form_id )
			$this->_form_balance_calibrate($calibrate_form_id);

		return (object)array(
			"transaction" => $this->_return_transaction_element($this->_transaction),
		);
	}
}