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
@action Beans_Vendor_Expense_Update_Check
@description Update the check number on an expense.  This is useful if you want to only update a single attribute without having to re-create or update the entire expense.  
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Expense# to update.
@optional check_number STRING 
@returns expense OBJECT The updated #Beans_Vendor_Expense#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Expense_Update_Check extends Beans_Vendor_Expense {

	protected $_auth_role_perm = "vendor_expense_write";
	
	protected $_id;
	protected $_data;
	protected $_expense;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;
		
		$this->_data = $data;
		
		$this->_expense = $this->_load_vendor_expense($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_expense->loaded() )
			throw new Exception("Expense could not be found.");

		if( $this->_expense->create_transaction->account_transactions->where('account_reconcile_id','IS NOT',NULL)->count_all() )
			throw new Exception("Expense check number cannot be changed after it has been reconciled.");

		if( isset($this->_data->check_number) AND 
			strlen($this->_data->check_number) > 16 )
			throw new Exception("Invalid expense check number: can be no more than 16 characters.");

		if( isset($this->_data->check_number) )
			$this->_expense->create_transaction->reference = $this->_data->check_number;
		
		$this->_expense->create_transaction->save();

		// Reload
		$this->_expense = $this->_load_vendor_expense($this->_expense->id);

		return (object)array(
			"expense" => $this->_return_vendor_expense_element($this->_expense),
		);
	}
}