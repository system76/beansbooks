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
@action Beans_Vendor_Expense_Refund
@description Create a refund for a vendor expense.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Vendor_Expense# that is being refunded.
@required vendor_id INTEGER The ID for the #Beans_Vendor# this will belong to.
@required account_id INTEGER The ID for the #Beans_Account# this expense is paid with.
@required date_created STRING The date of the expense in YYYY-MM-DD format.
@optional expense_number STRING An expense number to reference this expense.  If none is created, it will auto-generate.
@optional invoice_number STRING An invoice number to reference this expense.
@optional so_number STRING A sales order number to reference this expense.
@optional remit_address_id INTEGER The ID of the #Beans_Vendor_Address# to remit payment to.
@required lines ARRAY An array of objects representing line items for the expense.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity INTEGER The number of units.
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the cost of the expense towards.
@returns expense OBJECT The resulting #Beans_Vendor_Expense#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Expense_Refund extends Beans_Vendor_Expense {

	protected $_auth_role_perm = "vendor_expense_write";
	
	protected $_id;
	protected $_data;			// Will be passed along.
	protected $_expense;		// expense that is being refunded.
	
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
			throw new Exception("That expense could not be found.");

		if( $this->_expense->refund_form->loaded() )
			throw new Exception("That expense already belongs to a refund-set.");

		$this->_data->vendor_id = $this->_expense->entity_id;
		$this->_data->refund_expense_id = $this->_expense->id;
		$this->_data->expense_number = "R".$this->_expense->code;
		
		$create_expense = new Beans_Vendor_expense_Create($this->_beans_data_auth($this->_data));
		$create_expense_result = $create_expense->execute();

		if( ! $create_expense_result->success )
			throw new Exception($create_expense_result->error);

		$this->_expense->refund_form_id = $create_expense_result->data->expense->id;
		$this->_expense->save();
		
		return (object)array(
			"expense" => $create_expense_result->data->expense,
		);
	}
}