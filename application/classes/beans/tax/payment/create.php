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
@action Beans_Tax_Payment_Create
@description Create a new tax payment.  This should be called even when you have no taxes collected for a time period.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required tax_id INTEGER The #Beans_Tax# this payment is being applied to.
@required payment_account_id INTEGER The #Beans_Account# being used to pay the remittance.
@optional writeoff_account_id INTEGER The #Beans_Account# that handles the write-off - only required if there is a writeoff_amount.
@required amount DECIMAL The total remitted.
@optional writeoff_amount DECIMAL The total amount to write-off.
@optional check_number STRING
@optional description STRING A description for the transaction.
@return payment OBJECT The resulting #Beans_Tax_Payment#.
---BEANSENDSPEC---
*/
class Beans_Tax_Payment_Create extends Beans_Tax_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

	protected $_payment;
	protected $_validate_only;
	protected $_data;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_payment = $this->_default_tax_payment();

		$this->_data = $data;

		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( ! isset($this->_data->tax_id) ) 
			throw new Exception("Invalid tax ID: none provided.");

		$tax = $this->_load_tax($this->_data->tax_id);

		if( ! $tax->loaded() )
			throw new Exception("Invalid tax ID: not found.");

		// Check for some basic data.
		if( ! isset($this->_data->payment_account_id) )
			throw new Exception("Invalid payment account ID: none provided.");

		$payment_account = $this->_load_account($this->_data->payment_account_id);

		if( ! $payment_account->loaded() )
			throw new Exception("Invalid payment account ID: not found.");

		if( ! $payment_account->payment )
			throw new Exception("Invalid payment account ID: account must be marked as payment.");

		if( ! $this->_data->amount )
			throw new Exception("Invalid payment amount: none provided.");

		// Payment.

		$this->_payment->amount = $this->_data->amount;
		$this->_payment->tax_id = $tax->id;

		$this->_payment->date = ( isset($this->_data->date) )
							  ? $this->_data->date
							  : NULL;

		$this->_payment->date_start = ( isset($this->_data->date_start) )
							  ? $this->_data->date_start
							  : NULL;
							  
		$this->_payment->date_end = ( isset($this->_data->date_end) )
							  ? $this->_data->date_end
							  : NULL;


		$this->_validate_tax_payment($this->_payment);

		if( $this->_payment->amount == 0 )
		{
			$this->_payment->save();
			$this->_tax_update_due_date($this->_payment->tax_id,$this->_payment->date);

			return (object)array(
				"payment" => $this->_return_tax_payment_element($this->_payment),
			);
		}

		// Formulate data request object for Beans_Account_Transaction_Create
		$create_transaction_data = new stdClass;

		$create_transaction_data->code = ( isset($this->_data->number) )
									   ? $this->_data->number
									   : NULL;

		$create_transaction_data->description = ( isset($this->_data->description) )
											  ? $this->_data->description
											  : NULL;

		if( ! $create_transaction_data->description ) 
			$create_transaction_data->description = "Tax Remittance: ".$tax->name;
		else 
			$create_transaction_data->description = "Tax Remittance: ".$create_transaction_data->description;

		$create_transaction_data->date = ( isset($this->_data->date) )
									   ? $this->_data->date
									   : NULL;

		$create_transaction_data->reference = ( isset($this->_data->check_number) )
											? $this->_data->check_number
											: NULL;

		if( ! $create_transaction_data->code AND 
			$create_transaction_data->reference ) 
			$create_transaction_data->code = $create_transaction_data->reference;

		// Positive Payment = Negative to Balance
		$create_transaction_data->account_transactions = array();
		$create_transaction_data->form_type = 'tax_payment';
		$create_transaction_data->form_id = $this->_payment->id;

		// Payment Account
		$create_transaction_data->account_transactions[] = (object)array(
			'account_id' => $payment_account->id,
			'transfer' => TRUE,
			'amount' => ( $this->_payment->amount * -1 * $payment_account->account_type->table_sign ),
		);

		if( isset($this->_data->writeoff_amount) AND
			$this->_data->writeoff_amount != 0.00 )
		{
			$writeoff_amount = ( isset($this->_data->writeoff_amount) )
							 ? $this->_data->writeoff_amount
							 : NULL;

			$writeoff_account_id = ( isset($this->_data->writeoff_account_id) )
								 ? $this->_data->writeoff_account_id
								 : NULL;

			if( ! $writeoff_amount ) 
				throw new Exception("That payment will require a specifc writeoff amount - please provide that value.");

			if( ! $writeoff_account_id )
				throw new Exception("That payment will require a writeoff - please provide a writeoff account ID.");

			$writeoff_account = $this->_load_account($writeoff_account_id);

			if( ! $writeoff_account->loaded() )
				throw new Exception("Invalid writeoff account: not found.");

			if( ! $writeoff_account->writeoff )
				throw new Exception("Invalid writeoff account: must be marked as a valid writeoff account.");

			$create_transaction_data->account_transactions[] = (object)array(
				'account_id' => $writeoff_account->id,
				'writeoff' => TRUE,
				'amount' => ( $writeoff_amount * -1 * $payment_account->account_type->table_sign ),
			);

			$this->_payment->amount = $this->_beans_round($this->_payment->amount + $writeoff_amount);
		}

		// Tax Account
		$create_transaction_data->account_transactions[] = (object)array(
			'account_id' => $tax->account_id,
			'amount' => ( $this->_payment->amount * $payment_account->account_type->table_sign ),
		);

		if( $this->_validate_only )
			$create_transaction_data->validate_only = TRUE;
		
		$create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction_data));
		$create_transaction_result = $create_transaction->execute();

		if( ! $create_transaction_result->success )
			throw new Exception("An error occurred creating that tax payment: ".$create_transaction_result->error);

		if( $this->_validate_only )
			return (object)array();
		
		// Assign transction to payment and save
		$this->_payment->transaction_id = $create_transaction_result->data->transaction->id;
		$this->_payment->save();

		// Update tax balance
		$this->_tax_payment_adjust_balance($this->_payment->tax_id,$this->_payment->amount);

		// Update tax due date.
		$this->_tax_update_due_date($this->_payment->tax_id,$this->_payment->date);

		return (object)array(
			"payment" => $this->_return_tax_payment_element($this->_payment),
		);
	}
}