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
@action Beans_Customer_Payment_Create
@description Create a new customer payment.
@required auth_uid
@required auth_key
@required auth_expiration
@required deposit_account_id INTEGER The ID of the #Beans_Account# you are depositing into.
@optional writeoff_account_id INTEGER The ID of the #Beans_Account# to write off balances to.
@optional adjustment_account_id INTEGER The ID of the #Beans_Account# for an adjusting entry.
@required amount DECIMAL The total payment amount being received.
@optional adjustment_amount DECIMAL The amount for an adjusting entry.
@required date STRING The date of the payment.
@optional number STRING A transaction or check number.
@optional description STRING
@required sales ARRAY An array of objects representing the amount received for each sale.
@required @attribute sales sale_id INTEGER The ID for the #Beans_Customer_Sale# being paid.
@required @attribute sales amount DECIMAL The amount being paid.
@optional @attribute sales writeoff_balance BOOLEAN Write off the remaining balance of the sale.
@returns payment OBJECT The resulting #Beans_Customer_Payment#.
---BEANSENDSPEC---
*/
class Beans_Customer_Payment_Create extends Beans_Customer_Payment {

	protected $_auth_role_perm = "customer_payment_write";

	protected $_validate_only;
	protected $_data;
	protected $_payment;

	protected $_transaction_sale_account_id;
	protected $_transaction_sale_line_account_id;
	protected $_transaction_sale_tax_account_id;
	protected $_transaction_sale_deferred_income_account_id;
	protected $_transaction_sale_deferred_liability_account_id;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_payment = $this->_default_customer_payment();

		$this->_data = $data;

		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
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

		// Check for some basic data.
		if( ! isset($this->_data->deposit_account_id) )
			throw new Exception("Invalid payment deposit account ID: none provided.");

		$deposit_account = $this->_load_account($this->_data->deposit_account_id);

		if( ! $deposit_account->loaded() )
			throw new Exception("Invalid payment deposit account ID: not found.");

		if( ! $deposit_account->deposit )
			throw new Exception("Invalid payment deposit account ID: account must be marked as deposit.");

		if( ! $this->_data->amount )
			throw new Exception("Invalid payment amount: none provided.");

		// Formulate data request object for Beans_Account_Transaction_Create
		$create_transaction_data = new stdClass;

		$create_transaction_data->code = ( isset($this->_data->number) )
									   ? $this->_data->number
									   : NULL;

		$create_transaction_data->description = ( isset($this->_data->description) )
											  ? $this->_data->description
											  : NULL;
		
		$create_transaction_data->description = "Customer Payment Recorded".( $create_transaction_data->description ? ': '.$create_transaction_data->description : '' );

		$create_transaction_data->date = ( isset($this->_data->date) )
									   ? $this->_data->date
									   : NULL;

		$create_transaction_data->payment = "customer";

		$sale_account_transfers = array();
		$sale_account_transfers_forms = array();
		
		$writeoff_account_transfer_total = 0.00;
		$writeoff_account_transfers_forms = array();

		if( ! $this->_data->sales OR 
			! count($this->_data->sales) )
			throw new Exception("Please provide at least one sale for this payment.");

		$handled_sales_ids = array();

		foreach( $this->_data->sales as $sale_payment )
		{
			if( ! isset($sale_payment->sale_id) OR 
				! $sale_payment->sale_id )
				throw new Exception("Invalid payment sale ID: none provided.");

			$sale = $this->_load_customer_sale($sale_payment->sale_id);
			
			if( ! $sale->loaded() )
				throw new Exception("Invalid payment sale: sale not found.");
			
			if( ! $sale_payment->amount )
				throw new Exception("Invalid payment sale amount: none provided.");

			if( in_array($sale->id, $handled_sales_ids) )
				throw new Exception("Invalid payment sale: sale ".$sale->code." cannot be in payment more than once.");

			$handled_sales_ids[] = $sale->id;

			$sale_id = $sale->id;

			// Get the sale total, tax total, and balance as of the payment date.
			$sale_line_total = $sale->amount;
			$sale_tax_total = $this->_beans_round( $sale->total - $sale->amount );
			
			$sale_balance = $this->_get_form_effective_balance($sale,$create_transaction_data->date,NULL);
			
			// This makes the math a bit easier to read.
			$sale_paid = $sale->total + $sale_balance;

			// Money Received / Paid = Bank
			$sale_transfer_amount = $sale_payment->amount;

			$sale_writeoff_amount = ( isset($sale_payment->writeoff_balance) AND 
									  $sale_payment->writeoff_balance )
								  ? $this->_beans_round( 0.00 - $sale_balance - $sale_transfer_amount )
								  : FALSE;
			
			// AR Adjustment
			$sale_payment_amount = ( $sale_writeoff_amount )
								  ? $this->_beans_round( $sale_transfer_amount + $sale_writeoff_amount )
								  : $sale_transfer_amount;

			// Another variable to simplify code.
			$sale_transaction_account_id = FALSE;

			if( (
					$sale->date_billed AND 
					$sale->invoice_transaction_id AND 
					strtotime($sale->date_billed) <= strtotime($create_transaction_data->date)
				) OR
				(
					$sale->date_cancelled AND 
					$sale->cancel_transaction_id AND 
					strtotime($sale->date_cancelled) <= strtotime($create_transaction_data->date)
				) ) 
			{
				// Sale AR
				$sale_transaction_account_id = $sale->account_id;

				if( ! isset($sale_account_transfers[$sale_transaction_account_id]) )
					$sale_account_transfers[$sale_transaction_account_id] = 0.00;

				$sale_account_transfers[$sale_transaction_account_id] = $this->_beans_round(
					$sale_account_transfers[$sale_transaction_account_id] +
					$sale_payment_amount
				);
				
				if( ! isset($sale_account_transfers_forms[$sale_transaction_account_id]) )
					$sale_account_transfers_forms[$sale_transaction_account_id] = array();

				$sale_account_transfers_forms[$sale_transaction_account_id][] = (object)array(
					"form_id" => $sale_id,
					"amount" => $sale_payment_amount,
					"writeoff_amount" => ( $sale_writeoff_amount )
									  ? $sale_writeoff_amount
									  : NULL,
				);
			}
			else
			{
				$deferred_amounts = $this->_calculate_deferred_payment($sale_payment_amount, $sale_paid, $sale_line_total, $sale_tax_total);
				
				$income_transfer_amount = $deferred_amounts->income_transfer_amount;
				$tax_transfer_amount = $deferred_amounts->tax_transfer_amount;

				if( $income_transfer_amount )
				{
					if( ! isset($sale_account_transfers[$this->_transaction_sale_deferred_income_account_id]) )
						$sale_account_transfers[$this->_transaction_sale_deferred_income_account_id] = 0.00;

					if( ! isset($sale_account_transfers[$this->_transaction_sale_line_account_id]) )
						$sale_account_transfers[$this->_transaction_sale_line_account_id] = 0.00;

					$sale_account_transfers[$this->_transaction_sale_deferred_income_account_id] = $this->_beans_round(
						$sale_account_transfers[$this->_transaction_sale_deferred_income_account_id] +
						$income_transfer_amount
					);

					$sale_account_transfers[$this->_transaction_sale_line_account_id] = $this->_beans_round(
						$sale_account_transfers[$this->_transaction_sale_line_account_id] - 
						$income_transfer_amount
					);
				}

				if( $tax_transfer_amount )
				{
					if( ! isset($sale_account_transfers[$this->_transaction_sale_deferred_liability_account_id]) )
						$sale_account_transfers[$this->_transaction_sale_deferred_liability_account_id] = 0.00;

					if( ! isset($sale_account_transfers[$this->_transaction_sale_tax_account_id]) )
						$sale_account_transfers[$this->_transaction_sale_tax_account_id] = 0.00;

					$sale_account_transfers[$this->_transaction_sale_deferred_liability_account_id] = $this->_beans_round(
						$sale_account_transfers[$this->_transaction_sale_deferred_liability_account_id] +
						$tax_transfer_amount
					);

					$sale_account_transfers[$this->_transaction_sale_tax_account_id] = $this->_beans_round(
						$sale_account_transfers[$this->_transaction_sale_tax_account_id] - 
						$tax_transfer_amount
					);
				}

				if( ! isset($sale_account_transfers[$this->_transaction_sale_account_id]) )
					$sale_account_transfers[$this->_transaction_sale_account_id] = 0.00;

				$sale_account_transfers[$this->_transaction_sale_account_id] = $this->_beans_round(
					$sale_account_transfers[$this->_transaction_sale_account_id] + 
					$sale_payment_amount
				);

				if( ! isset($sale_account_transfers_forms[$this->_transaction_sale_account_id]) )
					$sale_account_transfers_forms[$this->_transaction_sale_account_id] = array();

				$sale_account_transfers_forms[$this->_transaction_sale_account_id][] = (object)array(
					"form_id" => $sale_id,
					"amount" => $sale_payment_amount,
					"writeoff_amount" => ( $sale_writeoff_amount )
									  ? $sale_writeoff_amount
									  : NULL,
				);
			}

			if( $sale_writeoff_amount )
			{
				$writeoff_account_transfer_total = $this->_beans_round(
					$writeoff_account_transfer_total + 
					$sale_writeoff_amount
				);
			}
		}

		$writeoff_account = FALSE;
		if( $writeoff_account_transfer_total != 0.00 )
		{
			// We need a write-off account.
			if( ! isset($this->_data->writeoff_account_id) OR 
				! $this->_data->writeoff_account_id )
				throw new Exception("Invalid payment write-off account ID: none provided.");

			$writeoff_account = $this->_load_account($this->_data->writeoff_account_id);

			if( ! $writeoff_account->loaded() )
				throw new Exception("Invalid payment write-off account ID: account not found.");

			if( ! $writeoff_account->writeoff )
				throw new Exception("Invalid payment write-off account ID: account must be marked as write-off.");

			if( isset($sale_account_transfers[$writeoff_account->id]) )
				throw new Exception("Invalid payment write-off account ID: account cannot be tied to any other transaction in the payment.");

			$sale_account_transfers[$writeoff_account->id] = $writeoff_account_transfer_total;
			$sale_account_transfers_forms[$writeoff_account->id] = $writeoff_account_transfers_forms;
		}

		$adjustment_account = FALSE;
		if( (
				isset($this->_data->adjustment_account_id) AND 
				strlen($this->_data->adjustment_account_id) 
			) OR 
			(
				isset($this->_data->adjustment_amount) AND 
				$this->_data->adjustment_amount
			) )
		{
			if( ! isset($this->_data->adjustment_account_id) OR 
				! $this->_data->adjustment_account_id )
				throw new Exception("Invalid adjustment account ID: none provided.");

			$adjustment_account = $this->_load_account($this->_data->adjustment_account_id);

			if( ! $adjustment_account->loaded() )
				throw new Exception("Invalid adjustment account ID: account not found.");

			if( isset($sale_account_transfers[$adjustment_account->id]) )
				throw new Exception("Invalid adjustment account ID: account cannot be tied to any other transaction in the payment.");

			$sale_account_transfers[$adjustment_account->id] = 
				$this->_data->adjustment_amount * -1;
		}

		// All of the accounts on sales are Accounts receivable and should be assets.
		// But to be on the safe side we're going to do table sign adjustments.
		foreach( $sale_account_transfers as $account_id => $transfer_amount )
		{
			$account = $this->_load_account($account_id);

			if( ! $account->loaded() )
				throw new Exception("System error: could not load account with ID ".$account_id);

			$sale_account_transfers[$account_id] = ( 
														( $writeoff_account AND $writeoff_account->id == $account_id ) OR 
														( $adjustment_account AND $adjustment_account->id == $account_id ) 
													)
												  ? ( $transfer_amount * $deposit_account->account_type->table_sign )
												  : ( $transfer_amount * -1 * $deposit_account->account_type->table_sign );
		}

		// V2Item - Validate amount to avoid funky non-zero errors.
		$sale_account_transfers[$deposit_account->id] = $this->_data->amount
														* $deposit_account->account_type->table_sign;

		$create_transaction_data->account_transactions = array();

		foreach( $sale_account_transfers as $account_id => $amount )
		{
			$account_transaction = new stdClass;

			$account_transaction->account_id = $account_id;
			$account_transaction->amount = $amount;

			if( $account_transaction->account_id == $deposit_account->id )
				$account_transaction->transfer = TRUE;

			if( $writeoff_account AND 
				$account_transaction->account_id == $writeoff_account->id )
				$account_transaction->writeoff = TRUE;

			if( $adjustment_account AND 
				$account_transaction->account_id == $adjustment_account->id )
				$account_transaction->adjustment = TRUE;

			if( isset($sale_account_transfers_forms[$account_id]) )
			{
				$account_transaction->forms = array();

				foreach($sale_account_transfers_forms[$account_id] as $form)
					$account_transaction->forms[] = (object)array(
						'form_id' => $form->form_id,
						'amount' => $form->amount,
						'writeoff_amount' => $form->writeoff_amount,
					);
			}

			$create_transaction_data->account_transactions[] = $account_transaction;
		}
		
		if( $this->_validate_only )
			$create_transaction_data->validate_only = TRUE;
		
		$create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction_data));
		$create_transaction_result = $create_transaction->execute();

		if( ! $create_transaction_result->success )
			throw new Exception("An error occurred creating that payment: ".$create_transaction_result->error);

		if( $this->_validate_only )
			return (object)array();

		// Recalibrate Customer Invoices / Cancellations
		$customer_sale_calibrate_invoice = new Beans_Customer_Sale_Calibrate_Invoice($this->_beans_data_auth((object)array(
			'ids' => $handled_sales_ids,
		)));
		$customer_sale_calibrate_invoice_result = $customer_sale_calibrate_invoice->execute();

		if( ! $customer_sale_calibrate_invoice_result->success )
			throw new Exception("UNEXPECTED ERROR: COULD NOT CALIBRATE CUSTOMER SALES: ".$customer_sale_calibrate_invoice_result->error);

		// Recalibrate Customer Invoices / Cancellations
		$customer_sale_calibrate_cancel = new Beans_Customer_Sale_Calibrate_Cancel($this->_beans_data_auth((object)array(
			'ids' => $handled_sales_ids,
		)));
		$customer_sale_calibrate_cancel_result = $customer_sale_calibrate_cancel->execute();

		if( ! $customer_sale_calibrate_cancel_result->success )
			throw new Exception("UNEXPECTED ERROR: COULD NOT CALIBRATE CUSTOMER SALES: ".$customer_sale_calibrate_cancel_result->error);

		// Recalibrate any payments tied to these sales AFTER this transaction date.
		$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
			'form_ids' => $handled_sales_ids,
			'after_payment_id' => $create_transaction_result->data->transaction->id,
		)));
		$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

		if( ! $customer_payment_calibrate_result->success )
			throw new Exception("UNEXPECTED ERROR: COULD NOT CALIBRATE CUSTOMER PAYMENTS: ".$customer_sale_calibrate_result->error);

		return (object)array(
			"payment" => $this->_return_customer_payment_element($this->_load_customer_payment($create_transaction_result->data->transaction->id)),
		);
	}
}