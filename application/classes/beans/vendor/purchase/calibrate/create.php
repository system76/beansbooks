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

class Beans_Vendor_Purchase_Calibrate_Create extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";

	protected $_data;

	protected $_transaction_purchase_account_id;
	protected $_transaction_purchase_line_account_id;
	protected $_transaction_purchase_prepaid_purchase_account_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;

		$this->_transaction_purchase_account_id = $this->_beans_setting_get('purchase_default_account_id');
		$this->_transaction_purchase_line_account_id = $this->_beans_setting_get('purchase_default_line_account_id');
		$this->_transaction_purchase_prepaid_purchase_account_id = $this->_beans_setting_get('purchase_prepaid_purchase_account_id');
	}

	protected function _execute()
	{
		if( ! $this->_beans_internal_call() )
			throw new Exception("This API function is restricted to internal use only.");

		if( ! $this->_transaction_purchase_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO account.");

		if( ! $this->_transaction_purchase_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO Line account.");

		if( ! $this->_transaction_purchase_prepaid_purchase_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default deferred asset account.");

		$valid_field = FALSE;

		$purchases = ORM::Factory('form_purchase')->
			where('type','=','purchase')->
			and_where_open();

		if( isset($this->_data->ids) )
		{
			if( ! is_array($this->_data->ids) )
				throw new Exception("Invalid ids provided: not an array.");

			$valid_field = TRUE;

			$purchases = $purchases->
				and_where_open()->
					where('id','IN',$this->_data->ids)->
					where('date_created','IS NOT',NULL)->
				and_where_close();
		}

		if( isset($this->_data->date_after) ||
			isset($this->_data->date_before) )
		{
			if( ! isset($this->_data->date_after) || 
				! $this->_data->date_after || 
				date("Y-m-d",strtotime($this->_data->date_after)) != $this->_data->date_after )
				throw new Exception("Missing or invalid date_after: must be in YYYY-MM-DD format.");

			if( ! isset($this->_data->date_before) || 
				! $this->_data->date_before || 
				date("Y-m-d",strtotime($this->_data->date_before)) != $this->_data->date_before )
				throw new Exception("Missing or invalid date_before: must be in YYYY-MM-DD format.");

			$valid_field = TRUE;

			$purchases = $purchases->
				and_where_open()->
					where('date_created','>=',$this->_data->date_after)->
					where('date_created','<=',$this->_data->date_before)->
				and_where_close();
		}

		if( ! $valid_field )
			throw new Exception("Must provide either ids or date_after and date_before.");

		$purchases = $purchases->
			and_where_close()->
			find_all();

		foreach( $purchases as $purchase )
			$this->_calibrate_purchase_create($purchase);

		return (object)array();

	}

	protected function _calibrate_purchase_create($purchase)
	{
		// Should be impossible - but catches bugs from the above query...
		if( ! $purchase->date_created )
			return;

		// If the books have been closed for the active date, we have to assume that due-diligence has been done
		// to prevent a bad transaction from being put into the journal and simply move on.
		if( $this->_check_books_closed($purchase->date_created) )
			return;

		$purchase_create_transaction_data = new stdClass;
		$purchase_create_transaction_data->code = $purchase->code;
		$purchase_create_transaction_data->description = "Purchase ".$purchase->code;
		$purchase_create_transaction_data->date = $purchase->date_created;
		$purchase_create_transaction_data->account_transactions = array();
		$purchase_create_transaction_data->entity_id = $purchase->entity_id;
		$purchase_create_transaction_data->form_type = 'purchase';
		$purchase_create_transaction_data->form_id = $purchase->id;

		$account_transactions = array(
			$this->_transaction_purchase_prepaid_purchase_account_id => 0.00,
			$this->_transaction_purchase_line_account_id => 0.00,
			$this->_transaction_purchase_account_id => 0.00,
		);

		$account_transactions[$this->_transaction_purchase_account_id] = $purchase->total;

		foreach( $purchase->form_lines->find_all() as $purchase_line )
		{
			$account_transactions[$this->_transaction_purchase_line_account_id] = $this->_beans_round( 
				$account_transactions[$this->_transaction_purchase_line_account_id] + 
				( $purchase_line->amount * $purchase_line->quantity )
			);
		}

		foreach( $account_transactions as $account_id => $amount )
		{
			if( $purchase->total == 0.00 ||
				$amount != 0.00 )
			{
				$account_transaction = new stdClass;

				$account_transaction->account_id = $account_id;
				$account_transaction->amount = ( $account_id == $this->_transaction_purchase_account_id )
											 ? ( $amount )
											 : ( $amount * -1 );

				if( $account_transaction->account_id == $this->_transaction_purchase_account_id )
				{
					$account_transaction->forms = array(
						(object)array(
							"form_id" => $purchase->id,
							"amount" => $account_transaction->amount,
						),
					);
				}

				$purchase_create_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$purchase_create_transaction_result = FALSE;

		if( $purchase->create_transaction_id )
		{
			$purchase_create_transaction_data->id = $purchase->create_transaction_id;
			$purchase_create_transaction_data->form_type_handled = "purchase";
			$purchase_create_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($purchase_create_transaction_data));
			$purchase_create_transaction_result = $purchase_create_transaction->execute();
		}
		else
		{
			$purchase_create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($purchase_create_transaction_data));
			$purchase_create_transaction_result = $purchase_create_transaction->execute();
		}

		if( ! $purchase_create_transaction_result->success )
			throw new Exception("Error creating purchase transaction in journal: ".$purchase_create_transaction_result->error."<br><br><br>\n\n\n".print_r($purchase_create_transaction_data->account_transactions,TRUE));

		if( ! $purchase->create_transaction_id )
		{
			$purchase->create_transaction_id = $purchase_create_transaction_result->data->transaction->id;
			$purchase->save();
		}
	}

}