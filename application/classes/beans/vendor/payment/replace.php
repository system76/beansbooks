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
@action Beans_Vendor_Payment_Replace
@description Replace a transaction in the journal with a payment by adding purchases to it.
@required auth_uid
@required auth_key
@required auth_expiration
@required transaction_id INTEGER The ID of the #Beans_Transaction# you are replacing.
@required payment_account_id INTEGER The ID of the #Beans_Account# you are making this payment from.
@optional writeoff_account_id INTEGER The ID of the #Beans_Account# to write off balances on.  This is required only if you have writeoff_balance as tru for any pruchases.
@required amount DECIMAL The total payment amount being received.
@optional number STRING A reference number.
@optional description STRING
@optional check_number STRING A transaction or check number.
@required purchases ARRAY An array of objects representing the amount received for each sale.
@required @attribute purchases purchase_id INTEGER The ID for the #Beans_Vendor_Purchase# being paid.
@required @attribute purchases amount DECIMAL The amount being paid.
@required @attribute purchases invoice_number STRING The invoice number being paid.
@required @attribute purchases date_billed STRING The bill date in YYYY-MM-DD format.
@optional @attribute purchases writeoff_balance BOOLEAN Write off the remaining balance of the sale.
@returns payment OBJECT The resulting #Beans_Vendor_Payment#.
---BEANSENDSPEC---
*/

class Beans_Vendor_Payment_Replace extends Beans_Vendor_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

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
		$this->_payment = $this->_default_vendor_payment();

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
		$this->_data->description = NULL; // Preserve Format // "Payment Recorded: ".$this->_transaction->description;

		// Create w/ validation on.
		$this->_data->validate_only = TRUE;
		$vendor_payment_create_validate = new Beans_Vendor_Payment_Create($this->_beans_data_auth($this->_data));
		$vendor_payment_create_validate_result = $vendor_payment_create_validate->execute();

		if( ! $vendor_payment_create_validate_result->success )
			throw new Exception($vendor_payment_create_validate_result->error);

		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_transaction->id,
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("An error occurred when cancelling the previous transaction: ".$account_transaction_delete_result->error);
		
		// Create w/o validation
		$this->_data->validate_only = FALSE;
		$vendor_payment_create = new Beans_Vendor_Payment_Create($this->_beans_data_auth($this->_data));
		
		return $this->_beans_return_internal_result($vendor_payment_create->execute());
	}
}