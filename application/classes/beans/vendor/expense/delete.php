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
@action Beans_Vendor_Expense_Delete
@description Delete a vendor expense.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Expense# to remove.
---BEANSENDSPEC---
*/
class Beans_Vendor_Expense_Delete extends Beans_Vendor_Expense {

	protected $_auth_role_perm = "vendor_expense_write";
	
	protected $_id;
	protected $_expense;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_expense = $this->_load_vendor_expense($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_expense->loaded() )
			throw new Exception("Expense could not be found.");

		if( $this->_check_books_closed($this->_expense->date_created) )
			throw new Exception("Expense could not be deleted.  The financial year has been closed already.");

		if( $this->_expense->refund_form_id AND 
			$this->_expense->refund_form_id > $this->_expense->id )
			throw new Exception("Expense could not be deleted - it has a refund attached to it.");
		
		// Determine if this expense transaction is RECONCILED.
		foreach( $this->_expense->create_transaction->account_transactions->find_all() as $account_transaction )
			if( $account_transaction->account_reconcile_id )
				throw new Exception("Expense could not be deleted. The payment has already been reconciled to an account.  Are you trying to create a refund?");

		// Cancel Transaction.
		if( $this->_expense->create_transaction_id )
		{
			$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_expense->create_transaction_id,
				'form_type_handled' => 'expense',
				'payment_type_handled' => 'expense',
			)));
			$account_transaction_delete_result = $account_transaction_delete->execute();

			if( ! $account_transaction_delete_result->success )
				throw new Exception("Error cancelling account transaction: ".$account_transaction_delete_result->error);
		}

		foreach( $this->_expense->form_lines->find_all() as $expense_line )
			$expense_line->delete();
		
		// Remove the refund form from the corresponding form.
		if( $this->_expense->refund_form->loaded() )
		{
			$this->_expense->refund_form->refund_form_id = NULL;
			$this->_expense->refund_form->save();
		}

		$this->_expense->delete();
		
		return (object)array();
	}
}