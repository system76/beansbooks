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
@action Beans_Vendor_Expense_Update
@description Update a new vendor expense.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Vendor_Expense# to update.
@optional account_id INTEGER The ID for the #Beans_Account# this expense is paid with.
@optional date_created STRING The date of the expense in YYYY-MM-DD format.
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
class Beans_Vendor_Expense_Update extends Beans_Vendor_Expense {

	protected $_auth_role_perm = "vendor_expense_write";
	
	protected $_attributes_only;
	protected $_data;
	protected $_id;
	protected $_expense;
	protected $_expense_lines;
	protected $_account_transactions;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;
		$this->_attributes_only = ( isset($this->_data->attributes_only) AND 
							 		$this->_data->attributes_only )
								? TRUE
								: FALSE;
		$this->_expense = $this->_load_vendor_expense($this->_id);

		$this->_expense_lines = array();
		$this->_account_transactions = array();
	}

	protected function _execute()
	{
		if( ! $this->_expense->loaded() )
			throw new Exception("Expense could not be found.");

		if( $this->_check_books_closed($this->_expense->date_created) &&
			$this->_expense->create_transaction_id &&
			$this->_expense->invoice_transaction_id &&
			$this->_expense->cancel_transaction_id )
			throw new Exception("Expense could not be updated.  The financial year has been closed already.");

		if( isset($this->_data->vendor_id) )
			$this->_expense->entity_id = $this->_data->vendor_id;

		if( isset($this->_data->account_id) )
			$this->_expense->account_id = $this->_data->account_id;

		if( isset($this->_data->sent) )
			$this->_expense->sent = $this->_data->sent;

		if( isset($this->_data->date_created) )
			$this->_expense->date_created = $this->_data->date_created;

		if( isset($this->_data->expense_number) )
			$this->_expense->code = $this->_data->expense_number;

		if( isset($this->_data->invoice_number) )
			$this->_expense->reference = $this->_data->invoice_number;

		if( isset($this->_data->so_number) )
			$this->_expense->alt_reference = $this->_data->so_number;

		if( isset($this->_data->remit_address_id) )
			$this->_expense->remit_address_id = $this->_data->remit_address_id;
		
		// Make sure we have good expense information before moving on.
		$this->_validate_vendor_expense($this->_expense);

		if( $this->_attributes_only )
		{
			$this->_expense->save();

			return (object)array(
				"success" => TRUE,
				"auth_error" => "",
				"error" => "",
				"data" => (object)array(
					"expense" => $this->_return_vendor_expense_element($this->_expense),
				),
			);
		}

		$this->_expense->total = 0.00;
		$this->_expense->amount = 0.00;

		if( ! isset($this->_data->lines) OR 
			! is_array($this->_data->lines) OR
			! count($this->_data->lines) )
			throw new Exception("Invalid expense lines: none provided.");

		$i = 0;

		foreach( $this->_data->lines as $expense_line )
		{
			$new_expense_line = $this->_default_form_line();

			$new_expense_line->account_id = ( isset($expense_line->account_id) )
										  ? (int)$expense_line->account_id
										  : NULL;

			$new_expense_line->description = ( isset($expense_line->description) )
										   ? $expense_line->description
										   : NULL;

			$new_expense_line->amount = ( isset($expense_line->amount) )
									  ? $this->_beans_round($expense_line->amount)
									  : NULL;

			$new_expense_line->quantity = ( isset($expense_line->quantity) )
										? (int)$expense_line->quantity
										: NULL;

			$this->_validate_form_line($new_expense_line);

			$new_expense_line->total = $this->_beans_round( $new_expense_line->amount * $new_expense_line->quantity );

			$new_expense_line_total = $this->_beans_round($new_expense_line->total);

			$this->_expense->amount = $this->_beans_round( $this->_expense->amount + $new_expense_line->total );
			
			$this->_expense_lines[$i] = $new_expense_line;

			$i++;

		}

		$this->_expense->total = $this->_beans_round( $this->_expense->total + $this->_expense->amount );
		
		// Cancel Account Transaction
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

			$this->_expense->create_transaction_id = NULL;
		}

		// Delete all current expense children.
		foreach( $this->_expense->form_lines->find_all() as $expense_line )
			$expense_line->delete();
		
		// Save expense + Children
		$this->_expense->save();

		// We "decrease" the account.
		$expense_account = $this->_load_account($this->_expense->account_id);
		$this->_account_transactions[$this->_expense->account_id] = $this->_expense->total;

		foreach( $this->_expense_lines as $j => $expense_line )
		{
			$expense_line->form_id = $this->_expense->id;
			$expense_line->save();

			if( ! isset($this->_account_transactions[$expense_line->account_id]) )
				$this->_account_transactions[$expense_line->account_id] = 0.00;

			$this->_account_transactions[$expense_line->account_id] = $this->_beans_round( $this->_account_transactions[$expense_line->account_id] + $this->_beans_round($expense_line->amount * $expense_line->quantity) );
		}

		// Generate Account Transaction
		$account_create_transaction_data = new stdClass;
		$account_create_transaction_data->code = ( isset($this->_data->check_number) )
											   ? $this->_data->check_number
											   : $this->_expense->create_transaction->code;
		$account_create_transaction_data->description = "Expense Recorded: ".$this->_expense->entity->company_name;
		$account_create_transaction_data->date = $this->_expense->date_created;
		$account_create_transaction_data->account_transactions = array();
		$account_create_transaction_data->payment = "expense";
		$account_create_transaction_data->reference = ( isset($this->_data->check_number) )
													? $this->_data->check_number
													: $this->_expense->create_transaction->reference;
		
		$account_create_transaction_data->form_type = 'expense';
		$account_create_transaction_data->form_id = $this->_expense->id;

		foreach( $this->_account_transactions as $account_id => $amount )
		{
			$account_transaction = new stdClass;

			$account_transaction->account_id = $account_id;
			$account_transaction->amount = ( $account_id == $this->_expense->account_id )
										 ? ( $amount )
										 : ( $amount * -1 );

			$account_transaction->forms = array(
				(object)array(
					"form_id" => $this->_expense->id,
					"amount" => $account_transaction->amount,
				),
			);
			
			$account_create_transaction_data->account_transactions[] = $account_transaction;
		}
		
		$account_create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($account_create_transaction_data));
		$account_create_transaction_result = $account_create_transaction->execute();

		if( ! $account_create_transaction_result->success )
		{
			// We've had an account transaction failure and need to delete the expense we just created.
			$delete_expense = new Beans_vendor_expense_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_expense->id,
			)));
			$delete_expense_result = $delete_expense->execute();

			// NOW WE HAVE A REALLY BIG PROBLEM ON OUR HANDS.
			if( ! $delete_expense_result->success )
				throw new Exception("Error creating account transaction for expense. COULD NOT DELETE EXPENSE! ".$delete_expense_result->error);
			
			throw new Exception("Error creating account transaction: ".$account_create_transaction_result->error);
		}

		// We're good!
		$this->_expense->create_transaction_id = $account_create_transaction_result->data->transaction->id;
		$this->_expense->save();

		$this->_expense = $this->_load_vendor_expense($this->_expense->id);
		
		return (object)array(
			"expense" => $this->_return_vendor_expense_element($this->_expense),
		);
	}
}