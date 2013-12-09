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
@action Beans_Account_Transaction_Create
@description Create a new transaction in the journal.
@required auth_uid
@required auth_key
@required auth_expiration
@required account_transactions An array of objects describing individual splits within the transaction.
@required @attribute account_transactions account_id The ID of the #Beans_Account#.
@required @attribute account_transactions amount The credit (+) or debit (-) to the account.
@required date The date for the transaction.
@optional code A code to assign to the transaction for easy lookup.
@optional description A short string describing the transaction.
@optional reference The check ( or reference ) number tied to a transaction.
@returns transaction The resulting #Beans_Transaction#.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Create extends Beans_Account_Transaction {

	protected $_auth_role_perm = "account_transaction_write";
	
	protected $_validate_only;
	protected $_transaction;
	protected $_account_transactions;
	protected $_account_transactions_forms;
	protected $_data;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
		$this->_transaction = $this->_default_transaction();
		$this->_account_transactions = array();
		$this->_account_transactions_forms = array();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		// V2Item
		// Consider making the ability to force_id public.
		if( $this->_beans_internal_call() AND
			isset($this->_data->force_id) AND 
			$this->_data->force_id )
		{
			$transaction_id_check = $this->_load_transaction($this->_data->force_id);
			if( $transaction_id_check->loaded() )
				throw new Exception("Invalid transaction ID: already taken.");

			$this->_transaction->id = $this->_data->force_id;
		}

		if( $this->_beans_internal_call() AND 
			isset($this->_data->entity_id) AND
			$this->_data->entity_id )
		{
			$this->_transaction->entity_id = $this->_data->entity_id;
		}

		$this->_transaction->code = ( isset($this->_data->code) AND strlen($this->_data->code) )
								  ? $this->_data->code
								  : 'AUTOGENERATE';

		$this->_transaction->description = ( isset($this->_data->description) AND strlen($this->_data->description) )
										 ? $this->_data->description
										 : 'AUTOGENERATE';

		$this->_transaction->date = ( isset($this->_data->date) AND strlen($this->_data->date) )
								  ? $this->_data->date
								  : NULL;

		$this->_transaction->payment = ( $this->_beans_internal_call() AND isset($this->_data->payment) AND $this->_data->payment )
									 ? $this->_data->payment
									 : FALSE;

		$this->_transaction->reference = ( isset($this->_data->reference) AND $this->_data->reference )
									   ? $this->_data->reference
									   : NULL;


		$this->_transaction->close_books = ( $this->_beans_internal_call() AND
											 isset($this->_data->close_books) )
										 ? substr($this->_data->close_books,0,7).'-00'
										 : NULL;

		$this->_validate_transaction($this->_transaction);

		$this->_transaction->amount = 0.00;
		$transaction_balance = 0;

		// Make sure there's only one account_transaction per account.
		$account_ids = array();
		foreach( $this->_data->account_transactions as $account_transaction )
		{
			if( in_array($account_transaction->account_id, $account_ids) )
				throw new Exception("You can only have one split per account.");
			
			$account_ids[] = $account_transaction->account_id;
		}

		if( ! count($this->_data->account_transactions) )
			throw new Exception("No account transactions were provided.");
		
		foreach( $this->_data->account_transactions as $account_transaction )
		{
			$new_account_transaction = $this->_default_account_transaction();

			$new_account_transaction->account_id = $account_transaction->account_id;
			$new_account_transaction->amount = $account_transaction->amount;
			$new_account_transaction->date = $this->_transaction->date;

			if( isset($account_transaction->transfer) AND 
				$account_transaction->transfer )
				$new_account_transaction->transfer = TRUE;

			if( isset($account_transaction->writeoff) AND 
				$account_transaction->writeoff )
				$new_account_transaction->writeoff = TRUE;

			$new_account_transaction->close_books = ( $this->_transaction->close_books ) ? TRUE : FALSE;
							
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
		
		$this->_transaction->save();

		if( $this->_transaction->code == "AUTOGENERATE" )
			$this->_transaction->code = $this->_transaction->id;
		if( $this->_transaction->description == "AUTOGENERATE" )
			$this->_transaction->description = ( $this->_transaction->payment )
											 ? 'Payment '.$this->_transaction->code
											 : 'Transaction '.$this->_transaction->code;
		

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

		$update_form_ids = array();

		foreach( $this->_account_transactions as $account_id => $account_transaction )
		{
			foreach( $this->_account_transactions_forms[$account_id] as $account_transaction_form )
			{
				if( ! in_array($account_transaction_form->form_id, $update_form_ids) )
					$update_form_ids[] = $account_transaction_form->form_id;
			}
		}

		foreach( $update_form_ids as $update_form_id )
			$this->_form_balance_calibrate($update_form_id);

		return (object)array(
			"transaction" => $this->_return_transaction_element($this->_transaction),
		);
	}
}