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
@action Beans_Customer_Sale_Cancel
@description Cancel a customer sale. This should be called if you cannot delete a sale because it has payments tied to it.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer_Sale# to cancel.
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Cancel extends Beans_Customer_Sale {

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
			throw new Exception("Sale could not be found.");

		if( $this->_sale->date_cancelled || 
			$this->_sale->cancel_transaction_id )
			throw new Exception("Sale has already been cancelled.");

		if( $this->_check_books_closed($this->_sale->date_created) )
			throw new Exception("Sale could not be cancelled.  The financial year has been closed already.");

		if( $this->_sale->refund_form_id AND 
			$this->_sale->refund_form_id > $this->_sale->id )
			throw new Exception("Sale could not be cancelled - it has a refund attached to it.");

		$date_cancelled = date("Y-m-d");

		$calibrate_payments = array();

		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// TODO - REPLACE WITH Calibrate_Payments with form_ids array ?
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( $account_transaction_form->account_transaction->transaction_id == $this->_sale->create_transaction_id OR
				(
					$account_transaction_form->account_transaction->transaction->payment AND 
					strtotime($account_transaction_form->account_transaction->date) <= strtotime($date_cancelled) 
				) )
			{
				// NADA
			}
			else if( $account_transaction_form->account_transaction->transaction->payment AND 
					 strtotime($date_cancelled) <= strtotime($account_transaction_form->account_transaction->transaction->date) AND
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
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		// // // // // // // // // // // // // // // // // // // // // // // // // 
		
		$this->_sale->date_cancelled = $date_cancelled;
		$this->_sale->save();

		$sale_calibrate = new Beans_Customer_Sale_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_sale->id),
		)));
		$sale_calibrate_result = $sale_calibrate->execute();

		if( ! $sale_calibrate_result->success )
		{
			$this->_sale->date_cancelled = NULL;
			$this->_sale->save();

			throw new Exception("Error trying to cancel sale: ".$sale_calibrate_result->error);
		}

		// Reload Sale
		$this->_sale = $this->_load_customer_sale($this->_sale->id);

		// If we're successful - reverse taxes.
		if( $this->_sale->date_billed )
		{
			foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
				$this->_tax_adjust_balance($sale_tax->tax_id,( -1 * $sale_tax->total) );
		}

		// Remove the refund form from the corresponding form.
		if( $this->_sale->refund_form->loaded() )
		{
			$this->_sale->refund_form->refund_form_id = NULL;
			$this->_sale->refund_form->save();
		}
		
		// Re-Calibrate Payments
		if( count($calibrate_payments) )
			usort($calibrate_payments, array($this,'_journal_usort') );

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

		// Reload Sale per Payment Calibration.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);































		// Create Cancel Transaction
		$sale_cancel_transaction_data = new stdClass;
		$sale_cancel_transaction_data->code = $this->_sale->code;
		$sale_cancel_transaction_data->description = "Sale Cancelled ".$this->_sale->code;
		$sale_cancel_transaction_data->date = $date_cancelled;
		$sale_cancel_transaction_data->account_transactions = array();
		$sale_cancel_transaction_data->entity_id = $this->_sale->entity_id;
		$sale_cancel_transaction_data->form_type = 'sale';
		$sale_cancel_transaction_data->form_id = $this->_sale->id;

		$calibrate_payments = array();

		$sale_balance = 0.00;
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( $account_transaction_form->account_transaction->transaction_id == $this->_sale->create_transaction_id OR
				(
					$account_transaction_form->account_transaction->transaction->payment AND 
					strtotime($account_transaction_form->account_transaction->date) <= strtotime($sale_cancel_transaction_data->date) 
				) )
			{
				$sale_balance = $this->_beans_round(
					$sale_balance +
					$account_transaction_form->amount
				);
			}
			else if( $account_transaction_form->account_transaction->transaction->payment AND 
					 strtotime($sale_cancel_transaction_data->date) <= strtotime($account_transaction_form->account_transaction->transaction->date) AND
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

		$account_transactions = array();

		// If Invoiced - We reverse the income & tax due, 
		// and put the total into the AR account.
		if( $this->_sale->date_billed )
		{
			// Total into AR
			$account_transactions[$this->_sale->account_id] = $this->_sale->total;

			// Income Lines
			foreach( $this->_sale->form_lines->find_all() as $sale_line )
			{
				if( ! isset($account_transactions[$sale_line->account_id]) )
					$account_transactions[$sale_line->account_id] = 0.00;

				$account_transactions[$sale_line->account_id] = $this->_beans_round(
					$account_transactions[$sale_line->account_id] - // DECREASING
					$sale_line->total
				);
			}

			// Taxes
			foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
			{
				if( ! isset($account_transactions[$sale_tax->tax->account_id]) )
					$account_transactions[$sale_tax->tax->account_id] = 0.00;

				$account_transactions[$sale_tax->tax->account_id] = $this->_beans_round(
					$account_transactions[$sale_tax->tax->account_id] - // DECREASING
					$sale_tax->total
				);
			}
		}
		// Not invoiced - we put the total into the pending AR account
		// and zero out the deferred/pending income/tax accounts
		else
		{
			// Get some transfer values.
			// $sale_balance is defined above
			$sale_line_total = $this->_sale->amount;
			$sale_tax_total = $this->_beans_round( $this->_sale->total - $this->_sale->amount );
			$sale_paid = $this->_sale->total + $sale_balance;

			$deferred_amounts = $this->_calculate_deferred_invoice($sale_paid, $sale_line_total, $sale_tax_total);

			$income_transfer_amount = $deferred_amounts->income_transfer_amount;
			$tax_transfer_amount = $deferred_amounts->tax_transfer_amount;
			
			// Total into Pending AR AND AR
			$account_transactions[$this->_transaction_sale_account_id] = ( -1 ) * $sale_balance;
			$account_transactions[$this->_sale->account_id] = $sale_paid;
			
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
		}

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
				
				$sale_cancel_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$sale_cancel_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($sale_cancel_transaction_data));
		$sale_cancel_transaction_result = $sale_cancel_transaction->execute();

		if( ! $sale_cancel_transaction_result->success )
			throw new Exception("Error creating cancellation transaction in journal: ".$sale_cancel_transaction_result->error);

		$this->_sale->cancel_transaction_id = $sale_cancel_transaction_result->data->transaction->id;
		$this->_sale->date_cancelled = $date_cancelled;
		$this->_sale->save();

		// If we're successful - reverse taxes.
		if( $this->_sale->date_billed )
		{
			foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
				$this->_tax_adjust_balance($sale_tax->tax_id,( -1 * $sale_tax->total) );
		}

		// Remove the refund form from the corresponding form.
		if( $this->_sale->refund_form->loaded() )
		{
			$this->_sale->refund_form->refund_form_id = NULL;
			$this->_sale->refund_form->save();
		}
		
		// Re-Calibrate Payments
		if( count($calibrate_payments) )
			usort($calibrate_payments, array($this,'_journal_usort') );

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

		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}