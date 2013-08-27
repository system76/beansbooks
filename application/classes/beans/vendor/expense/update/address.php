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
@action Beans_Vendor_Expense_Update_Address
@description Update the address information on an expense.  This is useful if you want to only update a single attribute without having to re-create or update the entire expense.  
@deprecated This function no longer serves a purpose for the distributed front-end and could be removed at a later date.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Expense# to update.
@optional remit_address_id INTEGER The ID of the #Beans_Vendor_Address# to assign for the remit address.
@returns expense OBJECT The updated #Beans_Vendor_Expense#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Expense_Update_Address extends Beans_Vendor_Expense {

	protected $_auth_role_perm = "vendor_expense_write";
	
	protected $_data;
	protected $_id;
	protected $_expense;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_expense = $this->_load_vendor_expense($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_expense->loaded() )
			throw new Exception("Expense could not be found.");

		if( isset($this->_data->remit_address_id) )
			$this->_expense->remit_address_id = $this->_data->remit_address_id;

		$this->_validate_vendor_expense($this->_expense);

		$this->_expense->save();

		return (object)array(
			"expense" => $this->_return_vendor_expense_element($this->_expense),
		);
	}
}