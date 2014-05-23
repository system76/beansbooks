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

class Beans_Vendor_Payment_Calibrate extends Beans_Vendor_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

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
			throw new Exception("Restricted to internal calls.");

		if( ! $this->_transaction_purchase_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO account.");

		if( ! $this->_transaction_purchase_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO Line account.");

		if( ! $this->_transaction_purchase_prepaid_purchase_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default deferred asset account.");

		$valid_field = FALSE;

		$payment_ids_query = 	' SELECT DISTINCT(transactions.id) as payment_id FROM transactions '.
		// 					 	' RIGHT JOIN account_transactions ON transactions.id = account_transactions.transaction_id '.
							 	' WHERE '.
							 	' transactions.payment = "vendor" AND ';

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
			$after_payment = $this->_load_vendor_payment($this->_data->after_payment_id);

			if( ! $after_payment->loaded() )
				throw new Exception("Invalid after_payment_id: vendor payment not found.");

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
			$before_payment = $this->_load_vendor_payment($this->_data->before_payment_id);

			if( ! $before_payment->loaded() )
				throw new Exception("Invalid before_payment_id: vendor payment not found.");

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
			$payment = $this->_load_vendor_payment($payment_id['payment_id']);

			$this->_calibrate_vendor_payment_transaction($payment);
		}
	}

	protected function _calibrate_vendor_payment_transaction($payment)
	{	
		if( ! $payment->loaded() )
			throw new Exception("Payment could not be found.");

		$payment_object = $this->_return_vendor_payment_element($payment);

		$update_transaction_data = new stdClass;
		$update_transaction_data->code = $payment->reference;
		$update_transaction_data->description = $payment->description;
		$update_transaction_data->date = $payment->date;
		$update_transaction_data->payment = "vendor";
		$update_transaction_data->entity_id = $payment->vendor_id;
		
		$purchase_account_transfers = array();
		$purchase_account_transfers_forms = array();
		$purchase_account_transfer_total = 0.00;

		$writeoff_account_transfer_total = 0.00;
		$writeoff_account_transfers_forms = array();

		// Write once for cleaner code
		$purchase_account_transfers[$this->_transaction_purchase_account_id] = 0.00;
		$purchase_account_transfers_forms[$this->_transaction_purchase_account_id] = array();
		$purchase_account_transfers[$this->_transaction_purchase_line_account_id] = 0.00;
		$purchase_account_transfers_forms[$this->_transaction_purchase_line_account_id] = array();
		$purchase_account_transfers[$this->_transaction_purchase_prepaid_purchase_account_id] = 0.00;
		$purchase_account_transfers_forms[$this->_transaction_purchase_prepaid_purchase_account_id] = array();
		
		foreach( $payment_object->purchase_payments as $purchase_id => $purchase_payment )
		{
			$purchase = $this->_load_vendor_purchase($purchase_id);
			
			$purchase_balance = $this->_get_form_effective_balance($purchase, $payment->date, $payment->id);

			$purchase_payment_amount = ( isset($purchase_payment->amount) ? ( -1 * $purchase_payment->amount ) : 0 );
			$purchase_writeoff_amount = ( isset($purchase_payment->writeoff_amount) ? ( -1 * $purchase_payment->writeoff_amount ) : FALSE );
			
			$purchase_transfer_amount = $purchase_payment_amount;// - $purchase_writeoff_amount;

			$purchase_writeoff_amount = ( isset($purchase_payment->writeoff_amount) AND 
										  $purchase_payment->writeoff_amount )
									  ? $this->_beans_round( $purchase_balance - $purchase_transfer_amount )
									  : FALSE;
			$purchase_payment_amount = ( $purchase_writeoff_amount ) 
									 ? $this->_beans_round( $purchase_transfer_amount + $purchase_writeoff_amount )
									 : $purchase_transfer_amount;

			// Apply to Realized Accounts
			if( (
					$purchase->date_billed AND 
					$purchase->invoice_transaction_id AND 
					strtotime($purchase->date_billed) <= strtotime($update_transaction_data->date)
				) OR
				(
					$purchase->date_cancelled AND 
					$purchase->cancel_transaction_id AND 
					strtotime($purchase->date_cancelled) <= strtotime($update_transaction_data->date)
				) ) 
			{
				// AP
				if( ! isset($purchase_account_transfers[$purchase->account_id]) )
					$purchase_account_transfers[$purchase->account_id] = 0.00;

				if( ! isset($purchase_account_transfers_forms[$purchase->account_id]) )
					$purchase_account_transfers_forms[$purchase->account_id] = array();

				$purchase_account_transfers[$purchase->account_id] = $this->_beans_round(
					$purchase_account_transfers[$purchase->account_id] +
					$purchase_payment_amount
				);

				// Writeoff
				if( $purchase_writeoff_amount )
					$writeoff_account_transfer_total = $this->_beans_round( 
						$writeoff_account_transfer_total +
						$purchase_writeoff_amount 
					);
				
				$purchase_account_transfers_forms[$purchase->account_id][] = (object)array(
					"form_id" => $purchase_id,
					"amount" => $purchase_payment_amount * -1 ,
					"writeoff_amount" => ( $purchase_writeoff_amount )
									  ? $purchase_writeoff_amount * -1
									  : NULL,
				);
			}
			// Apply to Pending / Deferred Acounts
			else
			{
				// Pending AP
				$purchase_account_transfers[$this->_transaction_purchase_account_id] = $this->_beans_round(
					$purchase_account_transfers[$this->_transaction_purchase_account_id] +
					$purchase_payment_amount
				);

				// Pending AP
				$purchase_account_transfers[$this->_transaction_purchase_line_account_id] = $this->_beans_round(
					$purchase_account_transfers[$this->_transaction_purchase_line_account_id] -
					$purchase_payment_amount
				);
				
				// Pending COGS
				$purchase_account_transfers[$this->_transaction_purchase_prepaid_purchase_account_id] = $this->_beans_round(
					$purchase_account_transfers[$this->_transaction_purchase_prepaid_purchase_account_id] +
					$purchase_payment_amount
				);

				// Writeoff
				if( $purchase_writeoff_amount )
					$writeoff_account_transfer_total = $this->_beans_round( 
						$writeoff_account_transfer_total +
						$purchase_writeoff_amount 
					);
				
				$purchase_account_transfers_forms[$this->_transaction_purchase_account_id][] = (object)array(
					"form_id" => $purchase_id,
					"amount" => $purchase_payment_amount * -1 ,
					"writeoff_amount" => ( $purchase_writeoff_amount )
									  ? $purchase_writeoff_amount * -1
									  : NULL,
				);
			}
		}

		$writeoff_account = FALSE;
		if( $writeoff_account_transfer_total != 0.00 )
		{
			$writeoff_account = $payment_object->writeoff_transaction->account;
			$purchase_account_transfers[$writeoff_account->id] = $writeoff_account_transfer_total;
			$purchase_account_transfers_forms[$writeoff_account->id] = $writeoff_account_transfers_forms;
		}

		$payment_account = $payment_object->payment_transaction->account;

		// All of the accounts on purchases are Accounts Payable and should be assets.
		// But to be on the safe side we're going to do table sign adjustments to be on the safe side.
		foreach( $purchase_account_transfers as $account_id => $transfer_amount )
		{
			$account = $this->_load_account($account_id);

			if( ! $account->loaded() )
				throw new Exception("System error: could not load account with ID ".$account_id);
			
			$purchase_account_transfers[$account_id] = ( $writeoff_account AND 
													  $writeoff_account->id == $account_id )
												  ? ( $transfer_amount * -1 * $payment_account->type->table_sign )
												  : ( $transfer_amount * $payment_account->type->table_sign );
		}

		$purchase_account_transfers[$payment_account->id] = $payment_object->amount
														 * $payment_account->type->table_sign;

		$update_transaction_data->account_transactions = array();

		foreach( $purchase_account_transfers as $account_id => $amount )
		{
			if( $amount != 0.00 )
			{
				$account_transaction = new stdClass;

				$account_transaction->account_id = $account_id;
				$account_transaction->amount = $amount;

				if( $account_transaction->account_id == $payment_account->id )
					$account_transaction->transfer = TRUE;

				if( $writeoff_account AND 
					$account_transaction->account_id == $writeoff_account->id )
					$account_transaction->writeoff = TRUE;

				if( isset($purchase_account_transfers_forms[$account_id]) )
				{
					$account_transaction->forms = array();

					foreach($purchase_account_transfers_forms[$account_id] as $form)
						$account_transaction->forms[] = (object)array(
							'form_id' => $form->form_id,
							'amount' => $form->amount,
							'writeoff_amount' => $form->writeoff_amount,
						);
				}

				$update_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$update_transaction_data->id = $payment->id;
		$update_transaction_data->payment_type_handled = 'vendor';

		$update_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($update_transaction_data));
		$update_transaction_result = $update_transaction->execute();

		if( ! $update_transaction_result->success )
			throw new Exception("An error occurred creating that payment: ".$update_transaction_result->error);

		return (object)array();
	}
}