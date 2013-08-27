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

	protected $_id;
	protected $_payment;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_payment = $this->_load_vendor_payment($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_beans_internal_call() )
			throw new Exception("Restricted to internal calls.");
		
		if( ! $this->_payment->loaded() )
			throw new Exception("Payment could not be found.");

		$payment_object = $this->_return_vendor_payment_element($this->_payment);

		$create_transaction_data = new stdClass;
		$create_transaction_data->code = $this->_payment->reference;
		$create_transaction_data->description = $this->_payment->description;
		$create_transaction_data->date = $this->_payment->date;
		$create_transaction_data->payment = "vendor";
		
		$purchase_account_transfers = array();
		$purchase_account_transfers_forms = array();
		$purchase_account_transfer_total = 0.00;

		$writeoff_account_transfer_total = 0.00;
		$writeoff_account_transfers_forms = array();

		foreach( $payment_object->purchase_payments as $purchase_id => $purchase_payment )
		{
			$purchase = $this->_load_vendor_purchase($purchase_id);
			
			$purchase_balance = 0.00;
			foreach( $purchase->account_transaction_forms->find_all() as $account_transaction_form )
			{
				if( (
						$account_transaction_form->account_transaction->transaction->payment AND 
						(
							strtotime($account_transaction_form->account_transaction->transaction->date) < strtotime($create_transaction_data->date) OR
							(
								strtotime($account_transaction_form->account_transaction->transaction->date) == strtotime($create_transaction_data->date) AND 
								$account_transaction_form->account_transaction->transaction->id < $this->_payment->id
							)
						)
					) OR
					$account_transaction_form->account_transaction->transaction_id == $purchase->create_transaction_id )
				{
					$purchase_balance = $this->_beans_round(
						$purchase_balance +
						$account_transaction_form->amount
					);
				}
				else if( $account_transaction_form->account_transaction->transaction->payment AND 
						(
							strtotime($account_transaction_form->account_transaction->transaction->date) > strtotime($create_transaction_data->date) OR
							(
								strtotime($account_transaction_form->account_transaction->transaction->date) == strtotime($create_transaction_data->date) AND 
								$account_transaction_form->account_transaction->transaction->id > $this->_payment->id
							)
						) AND
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

		$create_transaction_data->account_transactions = array();

		foreach( $purchase_account_transfers as $account_id => $amount )
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

			$create_transaction_data->account_transactions[] = $account_transaction;
		}

		// Check that our data is good.
		$create_transaction_data->validate_only = TRUE;

		$validate_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction_data));
		$validate_transaction_result = $validate_transaction->execute();

		if( ! $validate_transaction_result->success )
			throw new Exception("An error occurred when re-calibrating that payment: ".$validate_transaction_result->error);

		$create_transaction_data->validate_only = FALSE;

		// Keep same ID / Order.
		$create_transaction_data->force_id = $this->_payment->id;

		// Before we create the new payment, we remove the old one to avoid any systemic errors in journals.
		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_payment->id,
			'payment_type_handled' => 'vendor',
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("Update failure - could not cancel previous payment: ".$account_transaction_delete_result->error);

		$create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction_data));
		$create_transaction_result = $create_transaction->execute();

		if( ! $create_transaction_result->success )
			throw new Exception("An error occurred creating that payment: ".$create_transaction_result->error);

		return (object)array();
	}
}