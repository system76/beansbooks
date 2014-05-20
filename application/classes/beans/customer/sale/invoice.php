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
@action Beans_Customer_Sale_Invoice
@description Convert a customer sale into an invoice.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Customer_Sale#.
@required date_billed STRING The bill date in YYYY-MM-DD for the sale.
@optional date_due STRING The due date in YYYY-MM-DD for the sale.  If not provided, will default to the date_billed + the terms days on the AR #Beans_Account#.
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Invoice extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_id;
	protected $_sale;
	protected $_date_billed;
	protected $_date_due;		// Override.

	protected $_validate_only;

	protected $_transaction_sale_account_id;
	protected $_transaction_sale_line_account_id;
	protected $_transaction_sale_tax_account_id;
	protected $_transaction_sale_deferred_income_account_id;
	protected $_transaction_sale_deferred_liability_account_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_validate_only = ( isset($data->validate_only) AND 
								  $data->validate_only )
							  ? TRUE
							  : FALSE;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);

		// Date Billed defaults to today, or the SO date if it is in the future.
		$this->_date_billed = ( isset($data->date_billed) )
							? $data->date_billed
							: ( 
								strtotime(date("Y-m-d")) < strtotime($this->_sale->date_created) 
								? $this->_sale->date_created
								: date("Y-m-d") 
							);

		$this->_date_due = ( isset($data->date_due) )
						 ? $data->date_due
						 : FALSE;

		$this->_transaction_sale_account_id = $this->_beans_setting_get('sale_default_account_id');
		$this->_transaction_sale_line_account_id = $this->_beans_setting_get('sale_default_line_account_id');
		$this->_transaction_sale_tax_account_id = $this->_beans_setting_get('sale_default_tax_account_id');
		$this->_transaction_sale_deferred_income_account_id = $this->_beans_setting_get('sale_deferred_income_account_id');
		$this->_transaction_sale_deferred_liability_account_id = $this->_beans_setting_get('sale_deferred_liability_account_id');
	}

	protected function _execute()
	{
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

		if( $this->_sale->date_cancelled )
			throw new Exception("A sale cannot be converted to an invoice after it has been cancelled.");

		if( $this->_sale->date_billed )
			throw new Exception("That sale has already been converted to an invoice.");

		if( $this->_date_billed != date("Y-m-d",strtotime($this->_date_billed)) )
			throw new Exception("Invalid invoice date: must be in YYYY-MM-DD format.");

		if( strtotime($this->_date_billed) < strtotime($this->_sale->date_created) )
			throw new Exception("Invalid invoice date: must be on or after the creation date.");

		if( $this->_date_due AND 
			$this->_date_due != date("Y-m-d",strtotime($this->_date_due)) )
			throw new Exception("Invalid due date: must be in YYYY-MM-DD format.");

		if( $this->_date_due AND 
			strtotime($this->_date_due) < strtotime($this->_date_billed) )
			throw new Exception("Invalid due date: must be on or after the bill date.");

		$sale_invoice_transaction_data = new stdClass;
		$sale_invoice_transaction_data->code = $this->_sale->code;
		$sale_invoice_transaction_data->description = "Invoice - Sale ".$this->_sale->code;
		$sale_invoice_transaction_data->date = $this->_date_billed;
		$sale_invoice_transaction_data->entity_id = $this->_sale->entity_id;
		$sale_invoice_transaction_data->form_type = 'sale';
		$sale_invoice_transaction_data->form_id = $this->_sale->id;
		
		$account_transactions = array();

		$calibrate_payments = array();

		// Get some basics to create our split transaction.
		$sale_line_total = $this->_sale->amount;
		$sale_tax_total = $this->_beans_round( $this->_sale->total - $this->_sale->amount );
		
		$sale_balance = 0.00;
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( $account_transaction_form->account_transaction->transaction_id == $this->_sale->create_transaction_id OR
				(
					$account_transaction_form->account_transaction->transaction->payment AND 
					strtotime($account_transaction_form->account_transaction->date) <= strtotime($sale_invoice_transaction_data->date) 
				) )
			{
				$sale_balance = $this->_beans_round(
					$sale_balance +
					$account_transaction_form->amount
				);
			}
			else if( $account_transaction_form->account_transaction->transaction->payment AND 
					 strtotime($sale_invoice_transaction_data->date) <= strtotime($account_transaction_form->account_transaction->transaction->date) AND
					 ! in_array((object)array(
						'id' => $account_transaction_form->account_transaction->transaction->id,
						'date' => $account_transaction_form->account_transaction->transaction->date,
					), $calibrate_payments) )
			{
					$calibrate_payments[] = (object)array(
						'id' => $account_transaction_form->account_transaction->transaction->id,
						'date' => $account_transaction_form->account_transaction->transaction->date,
					);
			}
		}

		$sale_paid = $this->_sale->total + $sale_balance;

		$deferred_amounts = $this->_calculate_deferred_invoice($sale_paid, $sale_line_total, $sale_tax_total);

		$income_transfer_amount = $deferred_amounts->income_transfer_amount;
		$tax_transfer_amount = $deferred_amounts->tax_transfer_amount;
		
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

		$sale_invoice_transaction_data->validate_only = $this->_validate_only;

		$sale_invoice_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($sale_invoice_transaction_data));
		$sale_invoice_transaction_result = $sale_invoice_transaction->execute();

		if( ! $sale_invoice_transaction_result->success )
			throw new Exception("Could not create invoice transaction: ".$sale_invoice_transaction_result->error);

		if( $this->_validate_only )
			return (object)array();
		
		if( count($calibrate_payments) )
			usort($calibrate_payments, array($this,'_journal_usort') );

		// Calibrate Payments
		foreach( $calibrate_payments as $calibrate_payment )
		{
			$beans_calibrate_payment = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
				'id' => $calibrate_payment->id,
			)));
			$beans_calibrate_payment_result = $beans_calibrate_payment->execute();

			// V2Item
			// Fatal error!  Ensure coverage or ascertain 100% success.
			if( ! $beans_calibrate_payment_result->success )
				throw new Exception("UNEXPECTED ERROR: Error calibrating linked payments!".$beans_calibrate_payment_result->error);
		}

		// Update tax balances only if we're successful.
		foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
			$this->_tax_adjust_balance($sale_tax->tax_id,$sale_tax->total);
		
		$this->_sale->sent = NULL;
		$this->_sale->date_billed = $this->_date_billed;
		$this->_sale->date_due = ( $this->_date_due )
							   ? $this->_date_due
							   : date("Y-m-d",strtotime($this->_sale->date_billed.' +'.$this->_sale->account->terms.' Days'));
		$this->_sale->invoice_transaction_id = $sale_invoice_transaction_result->data->transaction->id;

		$this->_sale->save();

		$payment_calibrate_errors = '';

		// Is this sale tied to any payments that need to be calibrated?
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( $account_transaction_form->account_transaction->transaction->payment == "customer" )
			{
				$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
					'id' => $account_transaction_form->account_transaction->transaction->id,
				)));
				$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

				if( ! $customer_payment_calibrate_result->success )
					$payment_calibrate_errors .= $customer_payment_calibrate_result->error;

			}
		}
		
		if( $payment_calibrate_errors )
			throw new Exception("ERROR Encountered when calibrating payments tied to that sale/invoice: ".$payment_calibrate_errors." Sale was properly converted to an invoice, but all payments must be updated.");

		// We need to reload the sale so that we can get the correct balance, etc.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}