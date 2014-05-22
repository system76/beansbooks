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
class Beans_Vendor_Purchase_Invoice_Update extends Beans_Vendor_Purchase_Invoice {

	protected $_auth_role_perm = "vendor_purchase_write";

	protected $_id;
	protected $_purchase;

	protected $_transaction_purchase_account_id;
	protected $_transaction_purchase_line_account_id;
	protected $_transaction_purchase_prepaid_purchase_account_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);
		
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

		if( ! $this->_purchase->loaded() )
			throw new Exception("That purchase could not be found.");

		if( ! $this->_purchase->date_billed )
			throw new Exception("Invoices can only be updated after they have been billed.");

		$purchase_invoice_transaction_data = new stdClass;
		$purchase_invoice_transaction_data->code = $this->_purchase->code;
		$purchase_invoice_transaction_data->description = "Invoice - Purchase ".$this->_purchase->code;
		$purchase_invoice_transaction_data->date = $this->_purchase->date_billed;
		$purchase_invoice_transaction_data->entity_id = $this->_purchase->entity_id;
		$purchase_invoice_transaction_data->form_type = 'purchase';
		$purchase_invoice_transaction_data->form_id = $this->_purchase->id;

		$account_transactions = array();

		foreach( $this->_purchase->form_lines->find_all() as $purchase_line )
		{
			if( ! isset($account_transactions[$purchase_line->account_id]) )
				$account_transactions[$purchase_line->account_id] = 0.00;

			$account_transactions[$purchase_line->account_id] = $this->_beans_round(
				$account_transactions[$purchase_line->account_id] -
				$purchase_line->total
			);
		}

		// Misc.
		$account_transactions[$this->_transaction_purchase_line_account_id] = $this->_purchase->total;
		$account_transactions[$this->_transaction_purchase_account_id] = (-1) * $this->_purchase->total;
		$account_transactions[$this->_purchase->account_id] = $this->_purchase->total;
		
		// Associate array over to objects.
		$purchase_invoice_transaction_data->account_transactions = array();

		foreach( $account_transactions as $account_id => $amount ) 
		{
			$account_transaction = new stdClass;
			$account_transaction->account_id = $account_id;
			$account_transaction->amount = $amount;

			if( $account_transaction->account_id == $this->_transaction_purchase_account_id OR
				$account_transaction->account_id == $this->_purchase->account_id )
			{
				$account_transaction->forms = array(
					(object)array(
						"form_id" => $this->_purchase->id,
						"amount" => $account_transaction->amount,
					),
				);
			}
			
			$purchase_invoice_transaction_data->account_transactions[] = $account_transaction;
		}

		$purchase_invoice_transaction_data->id = $this->_purchase->invoice_transaction_id;
		$purchase_invoice_transaction_data->form_type_handled = "purchase";
		$purchase_invoice_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($purchase_invoice_transaction_data));
		$purchase_invoice_transaction_result = $purchase_invoice_transaction->execute();

		if( ! $purchase_invoice_transaction_result->success )
			throw new Exception("Could not create invoice transaction: ".$purchase_invoice_transaction_result->error);

		$this->_purchase = $this->_load_vendor_purchase($this->_purchase->id);
		
		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
		);
	}
}