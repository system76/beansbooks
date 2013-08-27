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
@action Beans_Account_Transaction_Cancel
@deprecated This action has been replaced with Beans_Account_Transaction_Delete.
@description Cancel an account transaction by creating a reverse transaction.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Transaction# to cancel.
@optional date The date to create the cancellation on ( defaults to today ).
@returns transaction The resulting #Beans_Transaction#.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Cancel extends Beans_Account_Transaction {

	protected $_auth_role_perm = "account_transaction_write";

	protected $_id;
	protected $_date; 	// Optional date to cancel the transaction on.
						// Defaults to today.
	protected $_transaction;

	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_date = ( isset($data->date) AND $data->date )
					 ? $data->date
					 : FALSE;

		$this->_transaction = $this->_load_transaction($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_transaction->loaded() )
			throw new Exception("Transaction could not be found.");

		if( $this->_transaction->cancel_transaction->loaded() )
			throw new Exception("Transaction already belongs to a cancel-pair.");

		if( $this->_transaction->close_books )
			throw new Exception("Close books transactions cannot be cancelled.");

		$cancel_transaction_data = new stdClass;

		$cancel_transaction_data->code = $this->_transaction->code;
		$cancel_transaction_data->description = "Cancelled Transaction ".$this->_transaction->code;
		$cancel_transaction_data->cancel_transaction_id = $this->_transaction->id;
		$cancel_transaction_data->date = ( $this->_date )
									   ? $this->_date
									   : date("Y-m-d");

		$cancel_transaction_data->account_transactions = array();

		foreach( $this->_transaction->account_transactions->find_all() as $account_transaction )
		{
			if( $account_transaction->account_reconcile_id )
				throw new Exception("That transaction has already been reconciled on an account and cannot be changed.");
			
			$new_account_transaction = new stdClass;
			$new_account_transaction->account_id = $account_transaction->account_id;
			$new_account_transaction->amount = ( -1 * $account_transaction->amount );

			foreach( $account_transaction->account_transaction_forms->find_all() as $account_transaction_form )
			{
				if( ! isset($new_account_transaction->forms) )
					$new_account_transaction->forms = array();

				$new_account_transaction->forms[] = (object)array(
					"form_id" => $account_transaction_form->form_id,
					"amount" => $this->_beans_round( -1 * $account_transaction_form->amount),
				);
			}

			$cancel_transaction_data->account_transactions[] = $new_account_transaction;
		}
		
		$account_transaction_create = new Beans_Account_Transaction_Create($this->_beans_data_auth($cancel_transaction_data));
		$account_transaction_create_result = $account_transaction_create->execute();

		if( ! $account_transaction_create_result->success )
			throw new Exception("Error creating cancel transaction: ".$account_transaction_create_result->error);

		$this->_transaction->cancel_transaction_id = $account_transaction_create_result->data->transaction->id;
		$this->_transaction->save();

		return (object)array(
			"transaction" => $account_transaction_create_result->data->transaction,
		);
	}
}