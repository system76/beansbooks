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
@action Beans_Vendor_Payment_Update
@description Update a vendor payment.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Payment# to update.
@optional payment_account_id INTEGER The ID of the #Beans_Account# you are making this payment from.
@optional writeoff_account_id INTEGER The ID of the #Beans_Account# to write off balances on.  This is required only if you have writeoff_balance as tru for any pruchases.
@optional amount DECIMAL The total payment amount being received.
@optional date STRING The date of the payment.
@optional number STRING A reference number.
@optional description STRING
@optional check_number STRING A transaction or check number.
@required purchases ARRAY An array of objects representing the amount received for each sale.
@required @attribute purchases purchase_id INTEGER The ID for the #Beans_Vendor_Purchase# being paid.
@required @attribute purchases amount DECIMAL The amount being paid.
@required @attribute purchases invoice_number STRING The invoice number being paid.
@required @attribute purchases date_billed STRING The bill date in YYYY-MM-DD format.
@optional @attribute purchases writeoff_balance BOOLEAN Write off the remaining balance of the sale.
@returns payment OBJECT The updated #Beans_Vendor_Payment#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Payment_Update extends Beans_Vendor_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

	protected $_id;
	protected $_data;
	protected $_old_payment;
	protected $_payment;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;
		$this->_old_payment = $this->_load_vendor_payment($this->_id);
		$this->_payment = $this->_default_vendor_payment();

		$this->_data = $data;
	}

	protected function _execute()
	{
		if( ! $this->_old_payment->loaded() )
			throw new Exception("Payment could not be found.");

		if( ! $this->_old_payment->payment != "vendor" )
			throw new Exception("That transaction is not a payment.");

		if( $this->_old_payment->account_transactions->where('account_reconcile_id','IS NOT',NULL)->count_all() )
			throw new Exception("Payment cannot be changed after it has been reconciled.");

		// Check for some basic data.
		if( ! isset($this->_data->payment_account_id) )
			throw new Exception("Invalid payment account ID: none provided.");

		$payment_account = $this->_load_account($this->_data->payment_account_id);

		if( ! $payment_account->loaded() )
			throw new Exception("Invalid payment account ID: not found.");

		if( ! $payment_account->deposit )
			throw new Exception("Invalid payment account ID: account must be marked as payment.");

		if( ! $this->_data->amount )
			throw new Exception("Invalid payment amount: none provided.");

		// Formulate data request object for Beans_Account_Transaction_Create
		$update_transaction_data = new stdClass;

		$update_transaction_data->code = ( isset($this->_data->number) )
									   ? $this->_data->number
									   : $this->_old_payment->code;

		$update_transaction_data->description = ( isset($this->_data->description) )
											  ? $this->_data->description
											  : $this->_old_payment->description;

		$update_transaction_data->date = ( isset($this->_data->date) )
									   ? $this->_data->date
									   : $this->_old_payment->date;

		$update_transaction_data->reference = ( isset($this->_data->check_number) )
											? $this->_data->check_number
											: $this->_old_payment->reference;

		$update_transaction_data->payment = "vendor";

		$purchase_account_transfers = array();
		$purchase_account_transfers_forms = array();
		$purchase_account_transfer_total = 0.00;

		$writeoff_account_transfer_total = 0.00;
		$writeoff_account_transfers_forms = array();

		if( ! $this->_data->purchases OR 
			! count($this->_data->purchases) )
			throw new Exception("Please provide at least one purchase for this payment.");

		$vendor_id = FALSE;

		$calibrate_payments = array();

		$handles_purchases_ids = array();

		foreach( $this->_data->purchases as $purchase_payment )
		{
			if( ! isset($purchase_payment->purchase_id) OR 
				! $purchase_payment->purchase_id )
				throw new Exception("Invalid payment purchase ID: none provided.");

			$purchase = $this->_load_vendor_purchase($purchase_payment->purchase_id);
			
			if( ! $purchase->loaded() )
				throw new Exception("Invalid payment purchase: purchase not found.");
			
			if( ! $purchase_payment->amount )
				throw new Exception("Invalid payment purchase amount: none provided.");

			if( in_array($purchase->id, $handles_purchases_ids) )
				throw new Exception("Invalid payment purchase: PO ID ".$purchase->id." cannot be in payment more than once.");

			$handles_purchases_ids[] = $purchase->id;

			if( ! $vendor_id )
				$vendor_id = $purchase->entity_id;
			else if( $vendor_id != $purchase->entity_id )
				throw new Exception("Invalid payment purchase ".$purchase->code." included: vendor mismatch. All purchase purchases must belong to the same vendor.");

			if( ! $purchase->date_billed AND 
				! $purchase->invoice_transaction_id AND 
				( 
					( isset($purchase_payment->invoice_number) AND $purchase_payment->invoice_number ) OR
					( isset($purchase_payment->date_billed) AND $purchase_payment->date_billed ) 
				) )
			{
				$vendor_purchase_invoice = new Beans_Vendor_Purchase_Invoice($this->_beans_data_auth((object)array(
					'id' => $purchase->id,
					'invoice_number' => $purchase_payment->invoice_number,
					'date_billed' => $purchase_payment->date_billed,
					'validate_only' => $this->_validate_only,
				)));
				$vendor_purchase_invoice_result = $vendor_purchase_invoice->execute();

				if( ! $vendor_purchase_invoice_result->success )
					throw new Exception("Invalid purchase order invoice information for ".$purchase->code.": ".$vendor_purchase_invoice_result->error);
			}
			else if( $purchase->date_billed AND 
					 $purchase->invoice_transaction_id AND 
					 ( isset($purchase_payment->invoice_number) AND $purchase_payment->invoice_number ) )
			{
				$vendor_purchase_update_invoice = new Beans_Vendor_Purchase_Update_Invoice($this->_beans_data_auth((object)array(
					'id' => $purchase->id,
					'invoice_number' => $purchase_payment->invoice_number,
					'validate_only' => $this->_validate_only,
				)));
				$vendor_purchase_update_invoice_result = $vendor_purchase_update_invoice->execute();

				if( ! $vendor_purchase_update_invoice_result->success )
					throw new Exception("Invalid purchase order invoice information for ".$purchase->code.": ".$vendor_purchase_update_invoice_result->error);
			}
			else if( ! $purchase->date_billed AND 
				! $purchase->invoice_transaction_id )
				throw new Exception("Invalid payment purchase: ".$purchase->code." has not been invoiced.  Please include an invoice number and date.");

			if( strtotime($purchase->date_billed) > strtotime($update_transaction_data->date) )
				throw new Exception("Invalid payment purchase: ".$purchase->code." cannot be paid before its invoice date: ".$purchase->date_billed.".");
			
			// Simplifies copied code.
			$purchase_id = $purchase->id;

			$purchase_balance = 0.00;
			foreach( $purchase->account_transaction_forms->find_all() as $account_transaction_form )
			{
				if( $this->_old_payment->id == $account_transaction_form->account_transaction->transaction->id ) 
				{
					// NADA
				}
				else if( (
						$account_transaction_form->account_transaction->transaction->payment AND 
						(
							strtotime($account_transaction_form->account_transaction->transaction->date) < strtotime($update_transaction_data->date) OR
							(
								strtotime($account_transaction_form->account_transaction->transaction->date) == strtotime($update_transaction_data->date) AND 
								$account_transaction_form->account_transaction->transaction->id < $this->_old_payment->id
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
							strtotime($account_transaction_form->account_transaction->transaction->date) > strtotime($update_transaction_data->date) OR
							(
								strtotime($account_transaction_form->account_transaction->transaction->date) == strtotime($update_transaction_data->date) AND 
								$account_transaction_form->account_transaction->transaction->id > $this->_old_payment->id
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

			$purchase_transfer_amount = $purchase_payment->amount;
			$purchase_writeoff_amount = ( isset($purchase_payment->writeoff_balance) AND
										  $purchase_payment->writeoff_balance )
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

		$vendor = $this->_load_vendor($vendor_id);

		// Keep the description clean.
		if( $update_transaction_data->description )
		{
			$update_transaction_data->description = ( strpos($update_transaction_data->description,"Vendor Payment Recorded: ") !== FALSE )
												  ? $update_transaction_data->description
												  : "Vendor Payment Recorded: ".$update_transaction_data->description;
		}
		else
		{
			$update_transaction_data->description = "Vendor Payment Recorded: ".$vendor->company_name;
		}

		

		$writeoff_account = FALSE;
		if( $writeoff_account_transfer_total != 0.00 )
		{
			// We need to add in a write-off.
			if( ! isset($this->_data->writeoff_account_id) )
				throw new Exception("Invalid payment write-off account ID: none provided.");

			$writeoff_account = $this->_load_account($this->_data->writeoff_account_id);

			if( ! $writeoff_account->loaded() )
				throw new Exception("Invalid payment write-off account ID: account not found.");

			if( ! $writeoff_account->writeoff )
				throw new Exception("Invalid payment write-off account ID: account must be marked as write-off.");

			if( isset($purchase_account_transfers[$writeoff_account->id]) )
				throw new Exception("Invalid payment write-off account ID: account cannot be tied to any other transaction in the payment.");

			$purchase_account_transfers[$writeoff_account->id] = $writeoff_account_transfer_total;
			$purchase_account_transfers_forms[$writeoff_account->id] = $writeoff_account_transfers_forms;
		}

		// All of the accounts on purchases are Accounts Payable and should be assets.
		// But to be on the safe side we're going to do table sign adjustments to be on the safe side.
		foreach( $purchase_account_transfers as $account_id => $transfer_amount )
		{
			$account = $this->_load_account($account_id);

			if( ! $account->loaded() )
				throw new Exception("System error: could not load account with ID ".$account_id);
			
			$purchase_account_transfers[$account_id] = ( $writeoff_account AND 
													  $writeoff_account->id == $account_id )
												  ? ( $transfer_amount * -1 * $payment_account->account_type->table_sign )
												  : ( $transfer_amount * $payment_account->account_type->table_sign );
		}

		$purchase_account_transfers[$payment_account->id] = $this->_data->amount
														 * -1 // FLIP THE SIGN
														 * $payment_account->account_type->table_sign;

		$update_transaction_data->account_transactions = array();

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

			$update_transaction_data->account_transactions[] = $account_transaction;
		}

		$vendor = $this->_load_vendor($vendor_id);

		// Shouldn't change...
		// $update_transaction_data->entity_id = $vendor_id;
		$update_transaction_data->id = $this->_old_payment->id;
		$update_transaction_data->payment_type_handled = 'vendor';
		
		$update_transaction = new Beans_Account_Transaction_Update($this->_beans_data_auth($update_transaction_data));
		$update_transaction_result = $update_transaction->execute();

		if( ! $update_transaction_result->success )
			throw new Exception("An error occurred creating that payment: ".$update_transaction_result->error);

		if( count($calibrate_payments) )
			usort($calibrate_payments, array($this,'_calibrate_payments_sort') );

		// Calibrate Payments
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

		return(object)array(
			"payment" => $this->_return_vendor_payment_element($this->_load_vendor_payment($update_transaction_result->data->transaction->id)),
		);
	}
}