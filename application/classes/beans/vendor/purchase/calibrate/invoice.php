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

class Beans_Vendor_Purchase_Calibrate_Invoice extends Beans_Vendor_Purchase {

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
					where('date_billed','IS NOT',NULL)->
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
					where('date_billed','>=',$this->_data->date_after)->
					where('date_billed','<=',$this->_data->date_before)->
				and_where_close();
		}

		if( ! $valid_field )
			throw new Exception("Must provide either ids or date_after and date_before.");

		$purchases = $purchases->
			and_where_close()->
			find_all();

		foreach( $purchases as $purchase )
			$this->_calibrate_purchase_invoice($purchase);

		return (object)array();

	}

	protected function _calibrate_purchase_invoice($purchase)
	{
		// Should be impossible - but catches bugs from the above query...
		if( ! $purchase->date_billed )
			return;

		$purchase_invoice_transaction_data = new stdClass;
		$purchase_invoice_transaction_data->code = $purchase->code;
		$purchase_invoice_transaction_data->description = "Invoice - Purchase ".$purchase->code;
		$purchase_invoice_transaction_data->date = $purchase->date_billed;
		$purchase_invoice_transaction_data->account_transactions = array();
		$purchase_invoice_transaction_data->entity_id = $purchase->entity_id;
		$purchase_invoice_transaction_data->form_type = 'purchase';
		$purchase_invoice_transaction_data->form_id = $purchase->id;

		$account_transactions = array();

		$purchase_line_total = $purchase->total;
		$purchase_balance = $this->_get_form_effective_balance($purchase, $purchase->date_billed, $purchase->invoice_transaction_id);
		$purchase_paid = $purchase_line_total + $purchase_balance;

		// AP Transfers
		$account_transactions[$this->_transaction_purchase_account_id] = ( $purchase_balance * -1 );
		$account_transactions[$purchase->account_id] = $purchase_balance;

		// Line Item Transfers
		$account_transactions[$this->_transaction_purchase_line_account_id] = ( $purchase_balance * -1 );
		$account_transactions[$this->_transaction_purchase_prepaid_purchase_account_id] = $purchase_paid;

		foreach( $purchase->form_lines->find_all() as $purchase_line )
		{
			if( ! isset($account_transactions[$purchase_line->account_id]) )
				$account_transactions[$purchase_line->account_id] = 0.00;

			$account_transactions[$purchase_line->account_id] = $this->_beans_round(
				$account_transactions[$purchase_line->account_id] -
				$purchase_line->total
			);
		}

		foreach( $account_transactions as $account_id => $amount ) 
		{
			if( $amount != 0.00 )
			{
				$account_transaction = new stdClass;
				$account_transaction->account_id = $account_id;
				$account_transaction->amount = $amount;

				if( $account_transaction->account_id == $this->_transaction_purchase_account_id OR
					$account_transaction->account_id == $purchase->account_id )
				{
					$account_transaction->forms = array(
						(object)array(
							"form_id" => $purchase->id,
							"amount" => $account_transaction->amount,
						),
					);
				}
				
				$purchase_invoice_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$purchase_invoice_transaction_result = FALSE;

		if( $purchase->invoice_transaction_id )
		{
			$purchase_invoice_transaction_data->id = $purchase->invoice_transaction_id;
			$purchase_invoice_transaction_data->form_type_handled = "purchase";
			$purchase_invoice_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($purchase_invoice_transaction_data));
			$purchase_invoice_transaction_result = $purchase_invoice_transaction->execute();
		}
		else
		{
			$purchase_invoice_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($purchase_invoice_transaction_data));
			$purchase_invoice_transaction_result = $purchase_invoice_transaction->execute();
		}

		if( ! $purchase_invoice_transaction_result->success )
			throw new Exception("Error creating purchase invoice transaction in journal: ".$purchase_invoice_transaction_result->error."<br><br><br>\n\n\n".print_r($purchase_invoice_transaction_data->account_transactions,TRUE));

		if( ! $purchase->invoice_transaction_id )
		{
			$purchase->invoice_transaction_id = $purchase_invoice_transaction_result->data->transaction->id;
			$purchase->save();
		}
	}

}