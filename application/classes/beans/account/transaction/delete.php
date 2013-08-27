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
@action Beans_Account_Transaction_Delete
@description Delete an account transaction from the journal.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Transaction# to delete.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Delete extends Beans_Account_Transaction {

	protected $_auth_role_perm = "account_transaction_write";

	protected $_id;
	protected $_transaction;
	// Internal Flags
	protected $_payment_type_handled;
	protected $_form_type_handled;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_payment_type_handled = ( isset($data->payment_type_handled) )
									 ? $data->payment_type_handled
									 : FALSE;

		$this->_form_type_handled = ( isset($data->form_type_handled) )
								  ? $data->form_type_handled
								  : FALSE;

		$this->_transaction = $this->_load_transaction($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_transaction->loaded() )
			throw new Exception("Transaction could not be found.");

		if( $this->_check_books_closed($this->_transaction->date) )
			throw new Exception("FYE for that transaction has been closed.");

		if( $this->_transaction->close_books )
			throw new Exception("Close books transactions cannot be deleted.");

		// If this transaction is tied to a form, we limit the action to 
		// internal requests only ( and require that form type to be specified 
		// as a safety check ).
		if( (
				$this->_transaction->create_form->loaded() AND
				( 
					! $this->_beans_internal_call() OR 
					$this->_form_type_handled != $this->_transaction->create_form->type
				)
			) OR
			(
				$this->_transaction->invoice_form->loaded() AND
				(
					! $this->_beans_internal_call() OR 
					$this->_form_type_handled != $this->_transaction->invoice_form->type 
				)
			) )
			throw new Exception("Transaction is tied to a ".
				( $this->_transaction->create_form->loaded() ? $this->_transaction->create_form->type : $this->_transaction->invoice_form->type ).
				" and must be cancelled through the appropriate interface.");

		// Same case as above, but for handling a payment.
		if( $this->_transaction->payment AND 
			(
				! $this->_beans_internal_call() OR 
				$this->_payment_type_handled != $this->_transaction->payment 
			) )
			throw new Exception("Transaction is a ".$this->_transaction->payment." payment and must be cancelled through the appropriate interface.");

		if( $this->_transaction->tax_payment->loaded() AND 
			( 
				! $this->_beans_internal_call() OR 
				$this->_payment_type_handled != "tax" 
			) )
			throw new Exception("Transaction is tied to a tax remittance and must be cancelled through the appropriate interface.");

		$form_ids = array();

		foreach( $this->_transaction->account_transactions->find_all() as $account_transaction )
		{
			if( $account_transaction->account_reconcile_id )
				throw new Exception("Cannot delete a transaction that has been reconciled.");

			foreach( $account_transaction->account_transaction_forms->find_all() as $account_transaction_form )
				$form_ids[] = $account_transaction_form->form_id;
		}

		// LOCK TABLE
		DB::query(NULL, 'START TRANSACTION')->execute();

		// Delete everything and then shift balances.
		foreach( $this->_transaction->account_transactions->find_all() as $account_transaction )
			$this->_account_balance_remove_transaction($account_transaction);
		
		$this->_transaction->delete();

		foreach( $form_ids as $form_id )
			$this->_form_balance_calibrate($form_id);
		
		// UNLOCK TABLE
		DB::query(NULL, 'COMMIT;')->execute();
		

		return (object)array();
	}
}