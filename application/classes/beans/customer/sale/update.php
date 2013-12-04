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
@action Beans_Customer_Sale_Update
@description Update a customer sale.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Customer_Sale# to update.
@optional date_created STRING The date of the sale in YYYY-MM-DD format.
@optional date_billed STRING The bill date in YYYY-MM-DD for the sale; adding this will automatically convert it to an invoice.
@optional date_due STRING The due date in YYYY-MM-DD for the sale; adding this will automatically convert it to an invoice.
@optional account_id INTEGER The ID for the Accounts Receivable #Beans_Account# this sale will be tied to.
@optional sent STRING STRING The sent status for the sale: "email", "phone", or "both".
@optional sale_number STRING A customer sale number to reference this sale.  If none is created, it will auto-generate.
@optional order_number STRING An order number to help identify this sale.
@optional po_number STRING A purchase order number to help identify this sale.
@optional quote_number STRING A quote number to help identify this sale.
@optional billing_address_id INTEGER The ID of the #Beans_Customer_Address# for billing this sale.
@optional shipping_address_id INTEGER The ID of the #Beans_Customer_Address# for shipping this sale.
@required lines ARRAY An array of objects representing line items for the sale.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity INTEGER The number of units.
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the sale towards ( in terms of revenue ).
@optional @attribute lines sale_line_taxes ARRAY An array of objects denoting which taxes apply to this line item.  Each object has a tax_id property that is an integer representing the applicable #Beans_Tax#.
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Update extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";
	
	protected $_data;
	protected $_id;
	protected $_sale;
	protected $_sale_lines;
	protected $_sale_lines_taxes;
	protected $_sale_taxes;
	protected $_account_transactions;

	protected $_transaction_sale_account_id;
	protected $_transaction_sale_line_account_id;
	protected $_transaction_sale_tax_account_id;

	protected $_date_billed;

	/**
	 * Create a new customer sale.
	 * @param stdClass $data Object to create new sale.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);

		$this->_sale_lines = array();
		$this->_sale_lines_taxes = array();
		$this->_sale_taxes = array();
		$this->_account_transactions = array();

		$this->_transaction_sale_account_id = $this->_beans_setting_get('sale_default_account_id');
		$this->_transaction_sale_line_account_id = $this->_beans_setting_get('sale_default_line_account_id');
		$this->_transaction_sale_tax_account_id = $this->_beans_setting_get('sale_default_tax_account_id');

		$this->_date_billed = ( isset($this->_data->date_billed) )
							? $this->_data->date_billed
							: FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("Sale could not be found.");

		if( ! $this->_transaction_sale_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO account.");

		if( ! $this->_transaction_sale_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO Line account.");

		if( ! $this->_transaction_sale_tax_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO Tax account.");
		
		if( $this->_check_books_closed($this->_sale->date_created) )
			throw new Exception("Sale could not be updated.  The financial year has been closed already.");

		$sale_original_total = $this->_sale->total;

		if( $this->_sale->date_cancelled )
			throw new Exception("A sale cannot be updated after it has been cancelled.");

		if( isset($this->_data->account_id) )
			$this->_sale->account_id = $this->_data->account_id;

		if( isset($this->_data->sent) )
			$this->_sale->sent = $this->_data->sent;

		if( isset($this->_data->date_created) )
			$this->_sale->date_created = $this->_data->date_created;

		if( isset($this->_data->sale_number) )
			$this->_sale->code = $this->_data->sale_number;

		if( isset($this->_data->order_number) )
			$this->_sale->reference = $this->_data->order_number;

		if( isset($this->_data->quote_number) )
			$this->_sale->aux_reference = $this->_data->quote_number;

		if( isset($this->_data->po_number) )
			$this->_sale->alt_reference = $this->_data->po_number;

		if( isset($this->_data->billing_address_id) )
			$this->_sale->billing_address_id = $this->_data->billing_address_id;

		if( isset($this->_data->shipping_address_id) )
			$this->_sale->shipping_address_id = $this->_data->shipping_address_id;

		// Field that can be updated ONLY after being invoiced.
		if( $this->_sale->date_billed )
		{
			if( isset($this->_data->date_billed) )
				$this->_sale->date_billed = $this->_data->date_billed;

			if( isset($this->_data->date_due) )
				$this->_sale->date_due = $this->_data->date_due;
		}

		// Make sure we have good sale information before moving on.
		$this->_validate_customer_sale($this->_sale);

		$this->_sale->total = 0.00;
		$this->_sale->amount = 0.00;

		if( ! isset($this->_data->lines) OR 
			! is_array($this->_data->lines) OR
			! count($this->_data->lines) )
			throw new Exception("Invalid sale lines: none provided.");

		$i = 0;

		foreach( $this->_data->lines as $sale_line )
		{
			$new_sale_line = $this->_default_form_line();

			$new_sale_line->account_id = ( isset($sale_line->account_id) )
										  ? (int)$sale_line->account_id
										  : NULL;

			$new_sale_line->description = ( isset($sale_line->description) )
										   ? $sale_line->description
										   : NULL;

			$new_sale_line->amount = ( isset($sale_line->amount) )
									  ? $this->_beans_round($sale_line->amount)
									  : NULL;

			$new_sale_line->quantity = ( isset($sale_line->quantity) )
										? (int)$sale_line->quantity
										: NULL;

			// Handle Default Income Account
			if( $new_sale_line->account_id === NULL ) {
				$new_sale_line->account_id = $this->_beans_setting_get('account_default_income');
			}

			$this->_validate_customer_sale_line($new_sale_line);

			$new_sale_line->total = $this->_beans_round( $new_sale_line->amount * $new_sale_line->quantity );

			$this->_sale_lines_taxes[$i] = array();

			if( isset($sale_line->sale_line_taxes) AND 
				$new_sale_line->amount != 0 )
			{
				if( ! is_array($sale_line->sale_line_taxes) )
					throw new Exception("Invalid sale line taxes: must be array.");

				foreach( $sale_line->sale_line_taxes as $sale_line_tax )
				{
					$new_sale_line_tax = $this->_default_form_line_tax();

					$new_sale_line_tax->tax_id = ( isset($sale_line_tax->tax_id) )
												  ? (int)$sale_line_tax->tax_id
												  : NULL;

					if( ! $new_sale_line_tax->tax_id )
						throw new Exception("Invalid sale line tax ID: none provided.");

					$tax = $this->_load_tax($new_sale_line_tax->tax_id);

					if( ! $tax->loaded() )
						throw new Exception("Invalid sale line tax ID: tax not found.");

					$new_sale_line_tax->tax_id = $tax->id;

					$this->_validate_customer_sale_line_tax($new_sale_line_tax);

					if( ! isset($this->_sale_taxes[$tax->id]) ) {
						$this->_sale_taxes[$tax->id] = $this->_default_form_tax();
						$this->_sale_taxes[$tax->id]->tax_id = $tax->id;
						$this->_sale_taxes[$tax->id]->fee = $tax->fee;
						$this->_sale_taxes[$tax->id]->percent = $tax->percent;
						$this->_sale_taxes[$tax->id]->amount = 0.00;
					}

					$this->_sale_taxes[$tax->id]->amount = $this->_beans_round( $this->_sale_taxes[$tax->id]->amount + $new_sale_line->total );
					$this->_sale_taxes[$tax->id]->quantity += $new_sale_line->quantity;

					$this->_sale_lines_taxes[$i][] = $new_sale_line_tax;
				}
			}

			$this->_sale_lines[$i] = $new_sale_line;

			$i++;
		}

		// If this is a refund we need to verify that the total is not greater than the original.
		if( $this->_sale->refund_form_id AND 
			$this->_sale->total > $this->_load_customer_sale($this->_sale->refund_form_id)->total )
			throw new Exception("That refund total was greater than the original sale total.");
		
		// Delete Account Transaction
		if( $this->_sale->create_transaction->loaded() )
		{
			$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_sale->create_transaction_id,
				'form_type_handled' => 'sale',
			)));
			$account_transaction_delete_result = $account_transaction_delete->execute();

			if( ! $account_transaction_delete_result->success )
				throw new Exception("Error cancelling account transaction: ".$account_transaction_delete_result->error);

			$this->_sale->create_transaction_id = NULL;
		}

		// Delete all current sale children.
		foreach( $this->_sale->form_lines->find_all() as $sale_line )
		{
			foreach( $sale_line->form_line_taxes->find_all() as $sale_line_tax )
				$sale_line_tax->delete();
			
			$sale_line->delete();
		}

		// Reverse current taxes if billed.
		if( $this->_sale->date_billed )
		{
			foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
				$this->_tax_adjust_balance($sale_tax->tax_id,( -1 * $sale_tax->total) );
		}

		foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
			$sale_tax->delete();
		
		// Save Sale + Children
		$this->_sale->save();

		foreach( $this->_sale_lines as $j => $sale_line )
		{
			$sale_line->form_id = $this->_sale->id;
			$sale_line->save();

			if( ! isset($this->_account_transactions[$this->_transaction_sale_line_account_id]) )
				$this->_account_transactions[$this->_transaction_sale_line_account_id] = 0.00;

			$this->_account_transactions[$this->_transaction_sale_line_account_id] = $this->_beans_round( 
				$this->_account_transactions[$this->_transaction_sale_line_account_id] + 
				( $sale_line->amount * $sale_line->quantity )
			);
			
			foreach( $this->_sale_lines_taxes[$j] as $sale_line_tax )
			{
				$sale_line_tax->form_line_id = $sale_line->id;
				$sale_line_tax->save();
			}
			
			$this->_sale->amount = $this->_beans_round( $this->_sale->amount + $sale_line->total );
		}

		$this->_sale->total = $this->_beans_round( $this->_sale->total + $this->_sale->amount );

		foreach( $this->_sale_taxes as $t => $sale_tax )
		{
			$this->_sale_taxes[$t]->form_id = $this->_sale->id;
			$this->_sale_taxes[$t]->total = 0.00;

			if( $sale_tax->fee )
				$this->_sale_taxes[$t]->total = $this->_beans_round( $this->_sale_taxes[$t]->total + ( $sale_tax->fee * $sale_tax->quantity ) );
			
			if( $sale_tax->percent )
				$this->_sale_taxes[$t]->total = $this->_beans_round( $this->_sale_taxes[$t]->total + ( $sale_tax->percent * $sale_tax->amount ) );
			
			$this->_sale_taxes[$t]->save();

			if( ! isset($this->_account_transactions[$this->_transaction_sale_tax_account_id]) )
				$this->_account_transactions[$this->_transaction_sale_tax_account_id] = 0.00;

			$this->_account_transactions[$this->_transaction_sale_tax_account_id] = $this->_beans_round( 
				$this->_account_transactions[$this->_transaction_sale_tax_account_id] + 
				$this->_sale_taxes[$t]->total 
			);

			$this->_sale->total = $this->_beans_round( $this->_sale->total + $this->_sale_taxes[$t]->total );
		}

		// We need to make sure we're "increasing" this account.
		$this->_account_transactions[$this->_transaction_sale_account_id] = $this->_sale->total;
		
		// Generate Account Transaction
		$account_create_transaction_data = new stdClass;
		$account_create_transaction_data->code = $this->_sale->code;
		$account_create_transaction_data->description = "Sale ".$this->_sale->code;
		$account_create_transaction_data->date = $this->_sale->date_created;
		$account_create_transaction_data->account_transactions = array();

		foreach( $this->_account_transactions as $account_id => $amount )
		{
			$account_transaction = new stdClass;

			$account_transaction->account_id = $account_id;
			$account_transaction->amount = ( $account_id == $this->_transaction_sale_account_id )
										 ? ( $amount * -1 )
										 : ( $amount );

			// Realistically, this will only hit on the first condition, but the logic applies
			// throughout the form process.  The AR or Pending AR account manages the form balance.
			if( $account_transaction->account_id == $this->_transaction_sale_account_id OR
				$account_transaction->account_id == $this->_sale->account_id )
			{
				// Add a form for the sale.
				// This updates the balance on the form.
				$account_transaction->forms = array(
					(object)array(
						"form_id" => $this->_sale->id,
						"amount" => $account_transaction->amount,	// This works because the account for the sale is table_sign -1
					),
				);
			}

			
			$account_create_transaction_data->account_transactions[] = $account_transaction;
		}
		
		$account_create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($account_create_transaction_data));
		$account_create_transaction_result = $account_create_transaction->execute();

		if( ! $account_create_transaction_result->success )
		{
			// V2Item
			// Fatal error!  Ensure coverage or ascertain 100% success.
			throw new Exception("Error creating account transaction: ".$account_create_transaction_result->error);
		}

		// We're good!
		$this->_sale->create_transaction_id = $account_create_transaction_result->data->transaction->id;
		
		// Upon updating a sale, if the total has changed we change the sent flag.
		if( $this->_sale->total != $sale_original_total AND 
			! isset($this->_data->sent) )
			$this->_sale->sent = FALSE;
		
		$this->_sale->save();


		// This might be excessive - consider removing... only appropriate use case is on create?
		// Breakdown = above date_billed and date_due is only allowed if date_billed is set... etc.
		if( $this->_date_billed AND 
			! $this->_sale->date_billed )
		{
			$customer_sale_invoice = new Beans_Customer_Sale_Invoice($this->_beans_data_auth((object)array(
				'id' => $this->_sale->id,
				'date_billed' => $this->_date_billed,
			)));
			$customer_sale_invoice_result = $customer_sale_invoice->execute();

			// If it fails - we undo everything ( this was a single request, after all ).
			if( ! $customer_sale_invoice_result->success )
			{
				$delete_sale = new Beans_Customer_Sale_Delete($this->_beans_data_auth((object)array(
					'id' => $this->_sale->id,
				)));
				$delete_sale_result = $delete_sale->execute();

				if( ! $delete_sale_result->success )
					throw new Exception("Error creating account transaction for sale. COULD NOT DELETE SALE! ".$delete_sale_result->error);
				
				throw new Exception("Error creating sale invoice: ".$customer_sale_invoice_result->error);
			}

			return $customer_sale_invoice_result;
		}

		$calibrate_payments = array();
		
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
		{
			if( $account_transaction_form->account_transaction->transaction->payment AND
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

		if( $this->_sale->date_billed )
		{
			// This also re-calibrates any payments tied to the sale/invoice...
			$customer_sale_invoice_update = new Beans_Customer_Sale_Invoice_Update($this->_beans_data_auth((object)array(
				'id' => $this->_sale->id,
			)));
			$customer_sale_invoice_update_result = $customer_sale_invoice_update->execute();

			if( ! $customer_sale_invoice_update_result->success ) 
				$invoice_update_errors .= "UNEXPECTED ERROR: Error updating customer sale invoice transaction. ".$customer_sale_invoice_update_result->error;
		}

		if( count($calibrate_payments) )
			usort($calibrate_payments, array($this,'_calibrate_payments_sort') );

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

		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}