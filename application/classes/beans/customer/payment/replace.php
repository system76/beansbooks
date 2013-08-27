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
@action Beans_Customer_Payment_Replace
@description Replace a transaction in the journal with a payment.
@required auth_uid
@required auth_key
@required auth_expiration
@required transaction_id INTEGER The ID of the #Beans_Transaction# you are replacing.
@required deposit_account_id INTEGER The ID of the #Beans_Account# you are depositing into.
@optional writeoff_account_id INTEGER The ID of the #Beans_Account# to write off balances to.
@required amount DECIMAL The total payment amount being received.
@optional description STRING
@required sales ARRAY An array of objects representing the amount received for each sale.
@required @attribute sales id INTEGER The ID for the #Beans_Customer_Sale# being paid.
@required @attribute sales amount DECIMAL The amount being paid.
@optional @attribute sales writeoff_balance BOOLEAN Write off the remaining balance of the sale.
@returns payment OBJECT The resulting #Beans_Customer_Payment#.
---BEANSENDSPEC---
*/
class Beans_Customer_Payment_Replace extends Beans_Customer_Payment {

	protected $_auth_role_perm = "customer_payment_write";

	protected $_transaction_id;
	protected $_data;
	protected $_transaction;
	protected $_payment;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_transaction_id = ( isset($data->transaction_id) ) 
				   ? $data->transaction_id
				   : 0;

		$this->_transaction = $this->_load_transaction($this->_transaction_id);
		$this->_payment = $this->_default_customer_payment();

		$this->_data = $data;
	}

	protected function _execute()
	{
		if( ! $this->_transaction->loaded() )
			throw new Exception("Transaction could not be found.");

		if( $this->_transaction->account_transactions->where('account_reconcile_id','IS NOT',NULL)->count_all() )
			throw new Exception("Transaction cannot be changed after it has been reconciled.");

		$this->_data->date = $this->_transaction->date;
		// Don't replace CODE
		$this->_data->number = $this->_transaction->code;
		// Update Description
		$this->_data->description = NULL;

		
		// Create w/ validation on.
		$this->_data->validate_only = TRUE;
		$customer_payment_create_validate = new Beans_Customer_Payment_Create($this->_beans_data_auth($this->_data));
		$customer_payment_create_validate_result = $customer_payment_create_validate->execute();

		if( ! $customer_payment_create_validate_result->success )
			throw new Exception($customer_payment_create_validate_result->error);

		// Delete Transaction
		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_transaction->id,
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("An error occurred when cancelling the previous transaction: ".$account_transaction_delete_result->error);

		// Create w/o validation
		$this->_data->validate_only = FALSE;
		$customer_payment_create = new Beans_Customer_Payment_Create($this->_beans_data_auth($this->_data));
		
		return $this->_beans_return_internal_result($customer_payment_create->execute());
	}
}