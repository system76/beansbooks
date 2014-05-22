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

		$payment_ids_query = 	' SELECT DISTINCT(transactions.id) as payment_id FROM transactions '.
		// 					 	' RIGHT JOIN account_transactions ON transactions.id = account_transactions.transaction_id '.
							 	' WHERE '.
							 	' transactions.payment = "customer" AND ';

		if( isset($this->_data->ids) )
		{
			if( ! is_array($this->_data->ids) )
				throw new Exception("Invalid ids provided: not an array.");

			$payment_ids_query .= ' transactions.id IN ('.implode(',',$this->_data->ids).') AND ';
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

			$payment_ids_query .= ' ( '.
								  ' 	transactions.date >= "'.$this->_data->date_after.'" AND '.
								  ' 	transactions.date <= "'.$this->_data->date_before.'" '.
								  ' ) AND ';
		}

		if( isset($this->_data->after_payment_id) )
		{
			$after_payment = $this->_load_customer_payment($this->_data->after_payment_id);

			if( ! $after_payment->loaded() )
				throw new Exception("Invalid after_payment_id: customer payment not found.");

			$valid_field = TRUE;

			$payment_ids_query .= ' ( '.
								  ' 	( '.
								  '			transactions.date > "'.$after_payment->date.'" '.
								  '		) OR '.
								  '		( '.
								  ' 		transactions.date = "'.$after_payment->date.'" AND '.
								  '			transactions.id > '.$after_payment->id.' '.
								  '		) '.
								  ' ) AND ';
		}

		if( isset($this->_data->before_payment_id) )
		{
			$before_payment = $this->_load_customer_payment($this->_data->before_payment_id);

			if( ! $before_payment->loaded() )
				throw new Exception("Invalid before_payment_id: customer payment not found.");

			$valid_field = TRUE;

			$payment_ids_query .= ' ( '.
								  ' 	( '.
								  '			transactions.date < "'.$before_payment->date.'" '.
								  '		) OR '.
								  '		( '.
								  ' 		transactions.date = "'.$before_payment->date.'" AND '.
								  '			transactions.id < '.$before_payment->id.' '.
								  '		) '.
								  ' ) AND ';
		}

		if( isset($this->_data->form_ids) )
		{
			if( ! is_array($this->_data->form_ids) )
				throw new Exception("Invalid form_ids provided: not an array.");

			$valid_field = TRUE;

			// Quick query to grab available transaction_ids
			$transaction_ids_query = ' SELECT DISTINCT(account_transactions.transaction_id) as transaction_id FROM account_transactions INNER JOIN '.
									 ' account_transaction_forms ON account_transactions.id = account_transaction_forms.account_transaction_id '.
									 ' WHERE '.
									 ' account_transaction_forms.form_id IN ('.implode(',',$this->_data->form_ids).') ';
			$transaction_ids = DB::Query(Database::SELECT, $transaction_ids_query)->execute()->as_array();

			$transaction_ids_array = array();
			foreach( $transaction_ids as $transaction_id )
				$transaction_ids_array[] = $transaction_id['transaction_id'];

			if( count($transaction_ids_array) )
				$payment_ids_query .= ' transactions.id IN ('.implode(',',$transaction_ids_array).') AND ';
			else
				$payment_ids_query .= ' transactions.id IS NULL AND ';	// SHOULD PRODUCE NO RESULTS
		}

		if( ! $valid_field )
			throw new Exception("Must provide either ids, date_after and date_before, after_payment_id, before_payment_id, or form_ids.");

		// Remove last "AND "
		$payment_ids_query = substr($payment_ids_query,0,-4);

		// Order properly.
		$payment_ids_query .= ' ORDER BY transactions.date ASC, transactions.id ASC ';

		$payment_ids = DB::Query(Database::SELECT, $payment_ids_query)->execute()->as_array();

		foreach( $payment_ids as $payment_id )
		{
			$payment = $this->_load_customer_payment($payment_id['payment_id']);

			$this->_calibrate_payment_transaction($payment);
		}
	}

	protected function _calibrate_payment_transaction($payment)
	{
		if( ! is_array($this->_data->form_ids) )
				throw new Exception("Invalid form_ids provided: not an array.");

		$payment_object = $this->_return_customer_payment_element($payment);

		$update_transaction_data = new stdClass;
		$update_transaction_data->code = $payment->reference;
		$update_transaction_data->description = $payment->description;
		$update_transaction_data->date = $payment->date;
		$update_transaction_data->payment = "customer";
		
		/*
		// Array of IDs for sales to have their invoices updated.
		$sales_invoice_update = array();
		$sales_cancel_update = array();
		*/
		
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
			
			$sale_balance = $this->_get_form_effective_balance($sale,$update_transaction_data->date,$payment->id);
		
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
					(
						strtotime($sale->date_billed) < strtotime($payment_object->date) OR 
						(
							$sale->date_billed == $payment_object->date &&
							$sale->invoice_transaction_id < $payment->id
						)
					)
				) OR
				(
					$sale->date_cancelled AND 
					$sale->cancel_transaction_id AND 
					(
						strtotime($sale->date_cancelled) < strtotime($payment_object->date) OR 
						(
							$sale->date_cancelled == $payment_object->date &&
							$sale->invoice_transaction_id < $payment->id
						)
					)
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

		$update_transaction_data->id = $payment->id;
		$update_transaction_data->payment_type_handled = 'customer';

		$update_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($update_transaction_data));
		$update_transaction_result = $update_transaction->execute();

		if( ! $update_transaction_result->success )
			throw new Exception("Update failure - could not update payment: ".$update_transaction_result->error);

		return (object)array();
	}
}