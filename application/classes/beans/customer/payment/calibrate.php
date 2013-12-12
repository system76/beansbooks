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
class Beans_Customer_Payment_Calibrate extends Beans_Customer_Payment {

	protected $_auth_role_perm = "customer_payment_write";

	protected $_id;
	protected $_payment;

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

		$this->_payment = $this->_load_customer_payment($this->_id);

		$this->_transaction_sale_account_id = $this->_beans_setting_get('sale_default_account_id');
		$this->_transaction_sale_line_account_id = $this->_beans_setting_get('sale_default_line_account_id');
		$this->_transaction_sale_tax_account_id = $this->_beans_setting_get('sale_default_tax_account_id');
		$this->_transaction_sale_deferred_income_account_id = $this->_beans_setting_get('sale_deferred_income_account_id');
		$this->_transaction_sale_deferred_liability_account_id = $this->_beans_setting_get('sale_deferred_liability_account_id');
	}

	protected function _execute()
	{
		if( ! $this->_beans_internal_call() )
			throw new Exception("Restricted to internal calls.");

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

		if( ! $this->_payment->loaded() )
			throw new Exception("Payment could not be found.");

		$payment_object = $this->_return_customer_payment_element($this->_payment);

		$update_transaction_data = new stdClass;
		$update_transaction_data->code = $this->_payment->reference;
		$update_transaction_data->description = $this->_payment->description;
		$update_transaction_data->date = $this->_payment->date;
		$update_transaction_data->payment = "customer";
		
		// Array of IDs for sales to have their invoices updated.
		$sales_invoice_update = array();

		$sale_account_transfers = array();
		$sale_account_transfers_forms = array();
		
		$writeoff_account_transfer_total = 0.00;
		$writeoff_account_transfers_forms = array();

		foreach( $payment_object->sale_payments as $sale_id => $sale_payment )
		{
			$sale = $this->_load_customer_sale($sale_id);
			
			// Get the sale total, tax total, and balance as of the payment date.
			$sale_line_total = $sale->amount;
			$sale_tax_total = $this->_beans_round( $sale->total - $sale->amount );
			
			$sale_balance = 0.00;
			foreach( $sale->account_transaction_forms->find_all() as $account_transaction_form )
			{
				if( (
						$account_transaction_form->account_transaction->transaction->payment AND 
						(
							strtotime($account_transaction_form->account_transaction->transaction->date) < strtotime($update_transaction_data->date) OR
							(
								strtotime($account_transaction_form->account_transaction->transaction->date) == strtotime($update_transaction_data->date) AND 
								$account_transaction_form->account_transaction->transaction->id < $payment_object->id
							)
						)
					) OR
					$account_transaction_form->account_transaction->transaction_id == $sale->create_transaction_id )
				{
					$sale_balance = $this->_beans_round(
						$sale_balance +
						$account_transaction_form->amount
					);
				}
			}

			// This makes the math a bit easier to read.
			$sale_paid = $sale->total + $sale_balance;

			$sale_payment_amount = ( isset($sale_payment->amount) ? $sale_payment->amount : 0 );
			$sale_writeoff_amount = ( isset($sale_payment->writeoff_amount) ? $sale_payment->writeoff_amount : FALSE );

			// Money Received / Paid = Bank
			$sale_transfer_amount = $sale_payment_amount;// - $sale_writeoff_amount;

			$sale_writeoff_amount = ( $sale_writeoff_amount )
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
					strtotime($sale->date_billed) <= strtotime($payment_object->date)
				) OR
				(
					$sale->date_cancelled AND 
					$sale->cancel_transaction_id AND 
					strtotime($sale->date_cancelled) <= strtotime($payment_object->date)
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
				if( (
						$sale->date_billed AND 
						$sale->invoice_transaction_id 
					) OR 
					(
						$sale->date_cancelled AND 
						$sale->cancel_transaction_id
					) )
					$sales_invoice_update[] = $sale->id;

				$income_transfer_amount = 0.00;
				$tax_transfer_amount = 0.00;
				
				if( $sale_paid < $sale_line_total )
				{
					$income_transfer_amount = $this->_beans_round(
						$income_transfer_amount + 
						(
							( ( $sale_line_total - $sale_paid ) <= $sale_payment_amount )
							? ( $sale_line_total - $sale_paid )
							: $sale_payment_amount
						)
					);
				}

				if( $income_transfer_amount < $sale_payment_amount AND 
					( $income_transfer_amount + $sale_paid - $sale_line_total ) < ( $sale_tax_total ) ) 
				{
					$remaining_tax_balance = ( $sale_tax_total + $sale_line_total - $sale_paid - $income_transfer_amount );
					$remaining_payment_amount = ( $sale_payment_amount - $income_transfer_amount );
					$tax_transfer_amount = $this->_beans_round(
						$tax_transfer_amount + 
						(
							( $remaining_tax_balance <= $remaining_payment_amount )
							? $remaining_tax_balance
							: $remaining_payment_amount
						)
					);
				}

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
			$writeoff_account = $payment_object->writeoff_transaction->account;
			$sale_account_transfers[$payment_object->writeoff_transaction->account->id] = $writeoff_account_transfer_total;
			$sale_account_transfers_forms[$payment_object->writeoff_transaction->account->id] = $writeoff_account_transfers_forms;
		}

		$deposit_account = $payment_object->deposit_transaction->account;

		// All of the accounts on sales are Accounts Receivable and should be assets.
		// But to be on the safe side we're going to do table sign adjustments to be on the safe side.
		foreach( $sale_account_transfers as $account_id => $transfer_amount )
			$sale_account_transfers[$account_id] = ( $writeoff_account AND 
													  $writeoff_account->id == $account_id )
												  ? ( $transfer_amount * $deposit_account->type->table_sign )
												  : ( $transfer_amount * -1 * $deposit_account->type->table_sign );
		
		$sale_account_transfers[$deposit_account->id] = $payment_object->amount
														* $deposit_account->type->table_sign;

		$update_transaction_data->account_transactions = array();

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

			$update_transaction_data->account_transactions[] = $account_transaction;
		}

		$update_transaction_data->id = $this->_payment->id;
		$update_transaction_data->payment_type_handled = 'customer';

		$update_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($update_transaction_data));
		$update_transaction_result = $update_transaction->execute();

		if( ! $update_transaction_result->success )
			throw new Exception("Update failure - could not update payment: ".$update_transaction_result->error);

		// Update invoices if necessary
		$invoice_update_errors = '';
		foreach( $sales_invoice_update as $sale_id ) 
		{
			$customer_sale_invoice_update = new Beans_Customer_Sale_Invoice_Update($this->_beans_data_auth((object)array(
				'id' => $sale_id,
			)));
			$customer_sale_invoice_update_result = $customer_sale_invoice_update->execute();

			if( ! $customer_sale_invoice_update_result->success ) 
			{
				$invoice_update_errors .= "UNEXPECTED ERROR: Error updating customer sale invoice transaction. ".$customer_sale_invoice_update_result->error;
			}
		}

		if( $invoice_update_errors )
			throw new Exception($invoice_update_errors);

		return (object)array();
	}
}