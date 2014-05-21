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
@action Beans_Customer_Sale_Create
@description Create a new customer sale.
@required auth_uid
@required auth_key
@required auth_expiration
@required customer_id INTEGER The ID for the #Beans_Customer# this will belong to.
@required date_created STRING The date of the sale in YYYY-MM-DD format.
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
@returns sale OBJECT The resulting #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Create extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_data;
	protected $_sale;
	protected $_sale_lines;
	protected $_sale_lines_taxes;
	protected $_sale_taxes;
	protected $_account_transactions;

	protected $_transaction_sale_account_id;
	protected $_transaction_sale_line_account_id;
	protected $_transaction_sale_tax_account_id;

	protected $_date_billed;
	protected $_date_due;

	/**
	 * Create a new customer sale.
	 * @param stdClass $data Object to create new sale.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		$this->_sale = $this->_default_customer_sale();
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
		$this->_date_due = ( isset($this->_data->date_due) )
						 ? $this->_data->date_due
						 : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_transaction_sale_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO account.");

		if( ! $this->_transaction_sale_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO Line account.");

		if( ! $this->_transaction_sale_tax_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default SO Tax account.");
		
		// Independently validate $this->_date_billed
		if( $this->_date_billed AND 
			$this->_date_billed != date("Y-m-d",strtotime($this->_date_billed)) )
			throw new Exception("Invalid invoice date: must be in YYYY-MM-DD format.");

		// Three laws.
		$temp_date_created = ( isset($this->_data->date_created) )
						   ? $this->_data->date_created
						   : date("Y-m-d");

		if( $temp_date_created != date("Y-m-d",strtotime($temp_date_created)) )
			throw new Exception("Invalid sale date: must be in YYYY-MM-DD format.");

		if( $this->_date_billed AND 
			strtotime($this->_date_billed) < strtotime($temp_date_created) )
			throw new Exception("Invalid invoice date: must on or after the date of the sale order.");

		if( $this->_date_due AND 
			$this->_date_due != date("Y-m-d",strtotime($this->_date_due)) )
			throw new Exception("Invalid due date: must be in YYYY-MM-DD format.");

		if( $this->_date_due AND 
			strtotime($this->_date_due) < strtotime($this->_date_billed) )
			throw new Exception("Invalid due date: must be on or after the bill date.");

		$this->_sale->entity_id = ( isset($this->_data->customer_id) )
								? (int)$this->_data->customer_id
								: NULL;

		$this->_sale->account_id = ( isset($this->_data->account_id) )
								 ? $this->_data->account_id
								 : NULL;
		
		$this->_sale->refund_form_id = ( isset($this->_data->refund_sale_id) )
									 ? $this->_data->refund_sale_id
									 : NULL;
		
		$this->_sale->sent = ( isset($this->_data->sent) )
						   ? $this->_data->sent
						   : NULL;

		$this->_sale->date_created = ( isset($this->_data->date_created) )
								   ? $this->_data->date_created
								   : NULL;

		$this->_sale->code = ( isset($this->_data->sale_number) AND 
						   $this->_data->sale_number )
						   ? $this->_data->sale_number
						   : "AUTOGENERATE";

		$this->_sale->reference = ( isset($this->_data->order_number) )
								? $this->_data->order_number
								: NULL;

		$this->_sale->alt_reference = ( isset($this->_data->po_number) )
									? $this->_data->po_number
									: NULL;

		$this->_sale->aux_reference = ( isset($this->_data->quote_number) )
									? $this->_data->quote_number
									: NULL;

		$this->_sale->billing_address_id = ( isset($this->_data->billing_address_id) )
										 ? (int)$this->_data->billing_address_id
										 : NULL;

		$this->_sale->shipping_address_id = ( isset($this->_data->shipping_address_id) )
										  ? (int)$this->_data->shipping_address_id
										  : NULL;

		// Handle Default Account Receivable
		
		// Customer Default Account Receivable
		if( $this->_sale->account_id === NULL )
		{
			$customer = $this->_load_customer($this->_sale->entity_id);
			if( $customer->loaded() AND 
				$customer->default_account_id )
				$this->_sale->account_id = $customer->default_account_id;
		}

		// Default Account Receivable
		if( $this->_sale->account_id === NULL ) {
			$this->_sale->account_id = $this->_beans_setting_get('account_default_receivable');
		}

		// Make sure we have good sale information before moving on.
		$this->_validate_customer_sale($this->_sale);

		$this->_sale->total = 0.00;
		$this->_sale->amount = 0.00;
		
		if( ! isset($this->_data->lines) OR 
			! is_array($this->_data->lines) OR
			! count($this->_data->lines) )
			throw new Exception("Invalid sale line items: none provided.");

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
		
		// Save Sale + Children
		$this->_sale->save();

		foreach( $this->_sale_lines as $j => $sale_line )
		{
			$sale_line->form_id = $this->_sale->id;
			$sale_line->save();
			/*
			if( ! isset($this->_account_transactions[$this->_transaction_sale_line_account_id]) )
				$this->_account_transactions[$this->_transaction_sale_line_account_id] = 0.00;

			$this->_account_transactions[$this->_transaction_sale_line_account_id] = $this->_beans_round( 
				$this->_account_transactions[$this->_transaction_sale_line_account_id] + 
				( $sale_line->amount * $sale_line->quantity )
			);
			*/
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
			/*
			if( ! isset($this->_account_transactions[$this->_transaction_sale_tax_account_id]) )
				$this->_account_transactions[$this->_transaction_sale_tax_account_id] = 0.00;

			$this->_account_transactions[$this->_transaction_sale_tax_account_id] = $this->_beans_round( 
				$this->_account_transactions[$this->_transaction_sale_tax_account_id] + 
				$this->_sale_taxes[$t]->total 
			);
			*/
			$this->_sale->total = $this->_beans_round( $this->_sale->total + $this->_sale_taxes[$t]->total );
		}

		if( $this->_sale->code == "AUTOGENERATE" )
			$this->_sale->code = $this->_sale->id;
		
		/*
		// Generate the account transaction for this SO.
		$this->_account_transactions[$this->_transaction_sale_account_id] = $this->_sale->total;
		*/
		
		/*
		// Generate Account Transaction
		$sale_create_transaction_data = new stdClass;
		$sale_create_transaction_data->code = $this->_sale->code;
		$sale_create_transaction_data->description = "Sale ".$this->_sale->code;
		$sale_create_transaction_data->date = $this->_sale->date_created;
		$sale_create_transaction_data->account_transactions = array();
		$sale_create_transaction_data->entity_id = $this->_sale->entity_id;
		$sale_create_transaction_data->form_type = 'sale';
		$sale_create_transaction_data->form_id = $this->_sale->id;

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

			$sale_create_transaction_data->account_transactions[] = $account_transaction;
		}
		
		$account_create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($sale_create_transaction_data));
		$account_create_transaction_result = $account_create_transaction->execute();

		if( ! $account_create_transaction_result->success )
		{
			// We've had an account transaction failure and need to delete the sale we just created.
			$delete_sale = new Beans_Customer_Sale_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_sale->id,
			)));
			$delete_sale_result = $delete_sale->execute();

			// NOW WE HAVE A REALLY BIG PROBLEM ON OUR HANDS.
			if( ! $delete_sale_result->success )
				throw new Exception("Error creating account transaction for sale. COULD NOT DELETE SALE! ".$delete_sale_result->error);
			
			throw new Exception("Error creating account transaction: ".$account_create_transaction_result->error);
		}

		// We're good!
		$this->_sale->create_transaction_id = $account_create_transaction_result->data->transaction->id;
		$this->_sale->save();
		*/
		
		$this->_sale->save();

		$sale_calibrate = new Beans_Customer_Sale_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_sale->id),
		)));
		$sale_calibrate_result = $sale_calibrate->execute();

		if( ! $sale_calibrate_result->success )
		{
			// We've had an account transaction failure and need to delete the sale we just created.
			$delete_sale = new Beans_Customer_Sale_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_sale->id,
			)));
			$delete_sale_result = $delete_sale->execute();

			// NOW WE HAVE A REALLY BIG PROBLEM ON OUR HANDS.
			if( ! $delete_sale_result->success )
				throw new Exception("Error creating account transaction for sale. COULD NOT DELETE SALE! ".$delete_sale_result->error);

			throw new Exception("Error trying to create sale: ".$sale_calibrate_result->error);
		}

		// Reload the sale.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);

		// TODO - IMPROVE...
		if( $this->_date_billed )
		{
			$customer_sale_invoice = new Beans_Customer_Sale_Invoice($this->_beans_data_auth((object)array(
				'id' => $this->_sale->id,
				'date_billed' => $this->_date_billed,
				'date_due' => ( $this->_date_due ? $this->_date_due : FALSE ),
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

			return $this->_beans_return_internal_result($customer_sale_invoice_result);
		}

		// We need to reload the sale so that we can get the correct balance, etc.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}