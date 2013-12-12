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
class Beans_Customer_Sale_Invoice_Update extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_id;
	protected $_sale;

	protected $_transaction_sale_account_id;
	protected $_transaction_sale_line_account_id;
	protected $_transaction_sale_tax_account_id;
	protected $_transaction_sale_deferred_income_account_id;
	protected $_transaction_sale_deferred_liability_account_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);

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

		if( ! $this->_sale->loaded() )
			throw new Exception("That sale could not be found.");

		if( ! $this->_sale->date_billed ||
			! $this->_sale->invoice_transaction_id )
			throw new Exception("Invoices can only be updated once they've been billed.");
		
		$sale_invoice_transaction_data = new stdClass;
		$sale_invoice_transaction_data->code = $this->_sale->code;
		$sale_invoice_transaction_data->description = "Invoice - Sale ".$this->_sale->code;
		$sale_invoice_transaction_data->date = $this->_sale->date_billed;
		
		$account_transactions = array();

		// Get some basics to create our split transaction.
		$sale_line_total = $this->_sale->amount;
		$sale_tax_total = $this->_beans_round( $this->_sale->total - $this->_sale->amount );
		
		$sale_balance = 0.00;
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( (
					$account_transaction_form->account_transaction->transaction->payment AND 
					strtotime($account_transaction_form->account_transaction->transaction->date) < strtotime($sale_invoice_transaction_data->date) 
				) OR
				$account_transaction_form->account_transaction->transaction_id == $this->_sale->create_transaction_id )
			{
				$sale_balance = $this->_beans_round(
					$sale_balance +
					$account_transaction_form->amount
				);
			}
		}

		$sale_paid = $this->_sale->total + $sale_balance;

		// Reverse our payment math into Deferred Income / Pending Income && Tax
		// i.e. fill income_transfer_total
		// then fill tax_transfer_total
		$income_transfer_amount = 0.00;
		$tax_transfer_amount = 0.00;
		
		if( $sale_paid != 0.00 )
		{
			$income_transfer_amount = $this->_beans_round(
				$income_transfer_amount +
				(
					( $sale_line_total < $sale_paid )
					? $sale_line_total 
					: $sale_paid
				)
			);

			if( ( $sale_paid - $sale_line_total ) > 0 )
			{
				$tax_transfer_amount = $this->_beans_round(
					$tax_transfer_amount + 
					(
						( $sale_tax_total < ( $sale_paid - $sale_line_total ) )
						? $sale_tax_total
						: ( $sale_paid - $sale_line_total )
					)
				);
			}
		}

		// Fill transactions.
		$account_transactions[$this->_transaction_sale_account_id] = ( $sale_balance * -1 );
		$account_transactions[$this->_sale->account_id] = ( $sale_balance );

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
		foreach( $this->_sale->form_lines->find_all() as $sale_line )
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
		foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
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
					$account_id == $this->_sale->account_id )
					$account_transaction->forms = array(
						(object)array(
							"form_id" => $this->_sale->id,
							"amount" => $account_transaction->amount,
						),
					);
				
				$sale_invoice_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$sale_invoice_transaction_data->id = $this->_sale->invoice_transaction_id;
		$sale_invoice_transaction_data->form_type_handled = "sale";
		$sale_invoice_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($sale_invoice_transaction_data));
		$sale_invoice_transaction_result = $sale_invoice_transaction->execute();

		if( ! $sale_invoice_transaction_result->success )
			throw new Exception("Could not create invoice transaction: ".$sale_invoice_transaction_result->error);

		// We need to reload the sale so that we can get the correct balance, etc.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}