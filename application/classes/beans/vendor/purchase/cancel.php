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
@action Beans_Vendor_Purchase_Cancel
@description Cancel a vendor purchase. This should be called if you cannot delete a purchase because it has payments tied to it.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Purchase# to cancel.
@returns sale OBJECT The updated #Beans_Vendor_Purchase#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Cancel extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";

	protected $_id;
	protected $_purchase;

	protected $_transaction_purchase_account_id;
	protected $_transaction_purchase_line_account_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);

		$this->_transaction_purchase_account_id = $this->_beans_setting_get('purchase_default_account_id');
		$this->_transaction_purchase_line_account_id = $this->_beans_setting_get('purchase_default_line_account_id');
	}

	protected function _execute()
	{
		if( ! $this->_transaction_purchase_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO account.");

		if( ! $this->_transaction_purchase_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO Line account.");

		if( ! $this->_purchase->loaded() )
			throw new Exception("Purchase purchase could not be found.");

		if( $this->_purchase->date_cancelled || 
			$this->_purchase->cancel_transaction_id )
			throw new Exception("Purchase has already been cancelled.");

		if( $this->_check_books_closed($this->_purchase->date_created) )
			throw new Exception("Purchase purchase could not be deleted.  The financial year has been closed already.");

		if( $this->_purchase->refund_form_id AND 
			$this->_purchase->refund_form_id > $this->_purchase->id )
			throw new Exception("Purchase could not be cancelled - it has a refund attached to it.");

		$date_cancelled = date("Y-m-d");
		
		// Create Cancel Transaction
		$purchase_cancel_transaction_data = new stdClass;
		$purchase_cancel_transaction_data->code = $this->_purchase->code;
		$purchase_cancel_transaction_data->description = "Purchase Cancelled ".$this->_purchase->code;
		$purchase_cancel_transaction_data->date = $date_cancelled;
		$purchase_cancel_transaction_data->account_transactions = array();
		$purchase_cancel_transaction_data->form_type = 'purchase';
		$purchase_cancel_transaction_data->form_id = $this->_purchase->id;

		$calibrate_payments = array();

		$purchase_balance = 0.00;
		foreach( $this->_purchase->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( $account_transaction_form->account_transaction->transaction_id == $this->_purchase->create_transaction_id OR
				(
					$account_transaction_form->account_transaction->transaction->payment AND 
					strtotime($account_transaction_form->account_transaction->date) <= strtotime($purchase_cancel_transaction_data->date) 
				) )
			{
				$purchase_balance = $this->_beans_round(
					$purchase_balance +
					$account_transaction_form->amount
				);
			}
			else if( $account_transaction_form->account_transaction->transaction->payment AND 
					 strtotime($purchase_cancel_transaction_data->date) <= strtotime($account_transaction_form->account_transaction->transaction->date) AND
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

		// If Invoiced - Reverse AP Account
		if( $this->_purchase->date_billed )
		{
			$account_transactions[$this->_purchase->account_id] = ( -1 ) * $this->_purchase->total;

			// Income Lines
			foreach( $this->_purchase->form_lines->find_all() as $purchase_line )
			{
				if( ! isset($account_transactions[$purchase_line->account_id]) )
					$account_transactions[$purchase_line->account_id] = 0.00;

				$account_transactions[$purchase_line->account_id] = $this->_beans_round(
					$account_transactions[$purchase_line->account_id] + // INCREASING
					$purchase_line->total
				);
			}
		}
		
		// Not Invoiced - Reverse Pending AP Account
		else
		{
			$purchase_line_total = $this->_purchase->amount;
			
			// Total into Pending AR AND AR
			$account_transactions[$this->_transaction_purchase_account_id] = ( -1 ) * $purchase_line_total;
			$account_transactions[$this->_transaction_purchase_line_account_id] = $purchase_line_total;
		}

		foreach( $account_transactions as $account_id => $amount ) 
		{
			if( $amount != 0.00 ) 
			{
				$account_transaction = new stdClass;
				$account_transaction->account_id = $account_id;
				$account_transaction->amount = $amount;
				if( $account_id == $this->_transaction_purchase_account_id OR 
					$account_id == $this->_purchase->account_id )
					$account_transaction->forms = array(
						(object)array(
							"form_id" => $this->_purchase->id,
							"amount" => $account_transaction->amount,
						),
					);
				
				$purchase_cancel_transaction_data->account_transactions[] = $account_transaction;
			}
		}

		$purchase_cancel_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($purchase_cancel_transaction_data));
		$purchase_cancel_transaction_result = $purchase_cancel_transaction->execute();

		if( ! $purchase_cancel_transaction_result->success )
			throw new Exception("Error creating cancellation transaction in journal: ".$purchase_cancel_transaction_result->error);

		$this->_purchase->cancel_transaction_id = $purchase_cancel_transaction_result->data->transaction->id;
		$this->_purchase->date_cancelled = $date_cancelled;
		$this->_purchase->save();

		// Remove the refund form from the corresponding form.
		if( $this->_purchase->refund_form->loaded() )
		{
			$this->_purchase->refund_form->refund_form_id = NULL;
			$this->_purchase->refund_form->save();
		}
		
		// Re-Calibrate Payments
		if( count($calibrate_payments) )
			usort($calibrate_payments, array($this,'_journal_usort') );

		foreach( $calibrate_payments as $calibrate_payment )
		{
			$beans_calibrate_payment = new Beans_Vendor_Payment_Calibrate($this->_beans_data_auth((object)array(
				'id' => $calibrate_payment->id,
			)));
			$beans_calibrate_payment_result = $beans_calibrate_payment->execute();

			// V2Item
			// Fatal error!  Ensure coverage or ascertain 100% success.
			if( ! $beans_calibrate_payment_result->success )
				throw new Exception("UNEXPECTED ERROR: Error calibrating linked payments!".$beans_calibrate_payment_result->error);
		}

		$this->_purchase = $this->_load_vendor_purchase($this->_purchase->id);
		
		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
		);
	}
}