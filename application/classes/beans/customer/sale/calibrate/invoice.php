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

class Beans_Customer_Sale_Calibrate_Invoice extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_data;

	protected $_transaction_sale_account_id;
	protected $_transaction_sale_line_account_id;
	protected $_transaction_sale_tax_account_id;
	protected $_transaction_sale_deferred_income_account_id;
	protected $_transaction_sale_deferred_liability_account_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;

		$this->_transaction_sale_account_id = $this->_beans_setting_get('sale_default_account_id');
		$this->_transaction_sale_line_account_id = $this->_beans_setting_get('sale_default_line_account_id');
		$this->_transaction_sale_tax_account_id = $this->_beans_setting_get('sale_default_tax_account_id');
		$this->_transaction_sale_deferred_income_account_id = $this->_beans_setting_get('sale_deferred_income_account_id');
		$this->_transaction_sale_deferred_liability_account_id = $this->_beans_setting_get('sale_deferred_liability_account_id');
	}

	protected function _execute()
	{
		if( ! $this->_beans_internal_call() )
			throw new Exception("This API function is restricted to internal use only.");

		if( ! $this->_transaction_sale_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO receivable account.");

		if( ! $this->_transaction_sale_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO line account.");

		if( ! $this->_transaction_sale_tax_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO tax account.");

		if( ! $this->_transaction_sale_deferred_income_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO deferred income account.");

		if( ! $this->_transaction_sale_deferred_liability_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO deferred liability account.");

		$valid_field = FALSE;

		$sales = ORM::Factory('form_sale')->
			where('type','=','sale')->
			and_where_open();

		if( isset($this->_data->ids) )
		{
			if( ! is_array($this->_data->ids) )
				throw new Exception("Invalid ids provided: not an array.");

			$valid_field = TRUE;

			$sales = $sales->
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

			$sales = $sales->
				and_where_open()->
					where('date_billed','>=',$this->_data->date_after)->
					where('date_billed','<=',$this->_data->date_before)->
				and_where_close();
		}

		if( ! $valid_field )
			throw new Exception("Must provide either ids or date_after and date_before.");

		$sales = $sales->
			and_where_close()->
			find_all();

		foreach( $sales as $sale )
			$this->_calibrate_sale_invoice($sale);

		return (object)array();
	}

	protected function _calibrate_sale_invoice($sale)
	{
		// Just to be safe in case the above passes a bad sale.
		if( ! $sale->date_billed )
			return;

		$sale_invoice_transaction_data = new stdClass;
		$sale_invoice_transaction_data->code = $sale->code;
		$sale_invoice_transaction_data->description = "Invoice - Sale ".$sale->code;
		$sale_invoice_transaction_data->date = $sale->date_billed;
		$sale_invoice_transaction_data->entity_id = $sale->entity_id;
		$sale_invoice_transaction_data->form_type = 'sale';
		$sale_invoice_transaction_data->form_id = $sale->id;
		
		$account_transactions = array();

		// Get some basics to create our split transaction.
		$sale_line_total = $sale->amount;
		$sale_tax_total = $this->_beans_round( $sale->total - $sale->amount );
		
		// Get a bunch of values for our split...
		$sale_balance = $this->_get_form_effective_balance($sale,$sale->date_billed,$sale->invoice_transaction_id);
		$sale_paid = $sale->total + $sale_balance;
		$deferred_amounts = $this->_calculate_deferred_invoice($sale_paid, $sale_line_total, $sale_tax_total);
		$income_transfer_amount = $deferred_amounts->income_transfer_amount;
		$tax_transfer_amount = $deferred_amounts->tax_transfer_amount;
		
		// Fill transactions.
		$account_transactions[$this->_transaction_sale_account_id] = ( $sale_balance * -1 );
		$account_transactions[$sale->account_id] = ( $sale_balance );

		// Deferred Income
		$account_transactions[$this->_transaction_sale_deferred_income_account_id] = ( $income_transfer_amount * -1 );
		
		// Pending Income
		$account_transactions[$this->_transaction_sale_line_account_id] = ( -1 ) * $this->_beans_round( 
			$sale_line_total + 
			$account_transactions[$this->_transaction_sale_deferred_income_account_id] 
		);

		// Deferred Taxes
		$account_transactions[$this->_transaction_sale_deferred_liability_account_id] = ( $tax_transfer_amount * -1 );
		
		// Pending Taxes
		$account_transactions[$this->_transaction_sale_tax_account_id] = ( -1 ) * $this->_beans_round( 
			$sale_tax_total + 
			$account_transactions[$this->_transaction_sale_deferred_liability_account_id] 
		);

		// Income Lines
		foreach( $sale->form_lines->find_all() as $sale_line )
		{
			if( ! isset($account_transactions[$sale_line->account_id]) )
				$account_transactions[$sale_line->account_id] = 0.00;

			$account_transactions[$sale_line->account_id] = $this->_beans_round(
				$account_transactions[$sale_line->account_id] +
				$sale_line->total
			);
		}

		// Taxes
		$sale_tax_total = 0.00;
		foreach( $sale->form_taxes->find_all() as $sale_tax )
		{
			if( ! isset($account_transactions[$sale_tax->tax->account_id]) )
				$account_transactions[$sale_tax->tax->account_id] = 0.00;

			$account_transactions[$sale_tax->tax->account_id] = $this->_beans_round(
				$account_transactions[$sale_tax->tax->account_id] +
				$sale_tax->total
			);
		}
		
		// Associate array over to objects.
		$sale_invoice_transaction_data->account_transactions = array();

		foreach( $account_transactions as $account_id => $amount ) 
		{
			if( $amount != 0.00 ) 
			{
				$account_transaction = new stdClass;
				$account_transaction->account_id = $account_id;
				$account_transaction->amount = $amount;
				if( $account_id == $this->_transaction_sale_account_id OR 
					$account_id == $sale->account_id )
					$account_transaction->forms = array(
						(object)array(
							"form_id" => $sale->id,
							"amount" => $account_transaction->amount,
						),
					);
				
				$sale_invoice_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$sale_invoice_transaction_result = FALSE;

		if( $sale->invoice_transaction_id )
		{
			$sale_invoice_transaction_data->id = $sale->invoice_transaction_id;
			$sale_invoice_transaction_data->form_type_handled = "sale";
			$sale_invoice_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($sale_invoice_transaction_data));
			$sale_invoice_transaction_result = $sale_invoice_transaction->execute();
		}
		else
		{
			$sale_invoice_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($sale_invoice_transaction_data));
			$sale_invoice_transaction_result = $sale_invoice_transaction->execute();
		}
		
		if( ! $sale_invoice_transaction_result->success )
			throw new Exception("Could not create invoice transaction: ".$sale_invoice_transaction_result->error);

		if( ! $sale->invoice_transaction_id )
		{
			$sale->invoice_transaction_id = $sale_invoice_transaction_result->data->transaction->id;
			$sale->save();
		}
	}
}