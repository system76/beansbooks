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
@optional tax_exempt BOOLEAN If set to true, all lines will be marked as tax_exempt.
@optional tax_exempt_reason STRING An explanation for tax exemption.  Required if tax_exempt is true.  Will be set to NULL if tax_exempt is false.
@optional billing_address_id INTEGER The ID of the #Beans_Customer_Address# for billing this sale.
@optional shipping_address_id INTEGER The ID of the #Beans_Customer_Address# for shipping this sale.
@required lines ARRAY An array of objects representing line items for the sale.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity INTEGER The number of units.
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the sale towards ( in terms of revenue ).
@optional @attribute lines tax_exempt BOOLEAN
@optional taxes ARRAY An array of objects representing taxes applicable to the sale.
@required @attribute taxes tax_id The ID of the #Beans_Tax# that should be applied.
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Update extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";
	
	protected $_data;
	protected $_id;
	protected $_sale;
	protected $_sale_lines;
	protected $_sale_taxes;

	protected $_date_billed;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);

		$this->_sale_lines = array();
		$this->_sale_taxes = array();

		$this->_date_billed = ( isset($this->_data->date_billed) )
							? $this->_data->date_billed
							: FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("Sale could not be found.");

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

		if( isset($this->_data->tax_exempt) )
			$this->_sale->tax_exempt = ( $this->_data->tax_exempt ) ? TRUE : FALSE;

		if( isset($this->_data->tax_exempt_reason) )
		{
			$this->_sale->tax_exempt_reason = ( $this->_sale->tax_exempt AND
												strlen($this->_data->tax_exempt_reason) ) 
											? $this->_data->tax_exempt_reason 
											: NULL;
		}

		if( isset($this->_data->billing_address_id) )
			$this->_sale->billing_address_id = $this->_data->billing_address_id;

		if( isset($this->_data->shipping_address_id) )
			$this->_sale->shipping_address_id = $this->_data->shipping_address_id;
		
		// Field that can be updated ONLY after being invoiced.
		if( $this->_sale->date_billed )
		{
			/*
			if( isset($this->_data->date_billed) AND 
				strtotime($this->_data->date_billed) < strtotime($this->_sale->date_created) )
				throw new Exception("Invalid invoice date: must be on or after the creation date of ".$this->_sale->date_created.".");
			*/
			
			if( isset($this->_data->date_billed) )
				$this->_sale->date_billed = $this->_data->date_billed;

			if( isset($this->_data->date_due) )
				$this->_sale->date_due = $this->_data->date_due;

			if( strtotime($this->_sale->date_created) > strtotime($this->_sale->date_billed) )
				$this->_sale->date_created = $this->_sale->date_billed;
		}

		// Make sure we have good sale information before moving on.
		$this->_validate_customer_sale($this->_sale);

		$this->_sale->total = 0.00;
		$this->_sale->amount = 0.00;

		if( ! isset($this->_data->lines) OR 
			! is_array($this->_data->lines) OR
			! count($this->_data->lines) )
			throw new Exception("Invalid sale lines: none provided.");

		if( $this->_data->taxes )
		{
			if( ! is_array($this->_data->taxes) )
				throw new Exception("Invalid sale taxes: must be an array.");

			foreach( $this->_data->taxes as $sale_tax )
			{
				$new_sale_tax = $this->_default_form_tax();

				$new_sale_tax->tax_id = ( isset($sale_tax->tax_id) )
									  ? (int)$sale_tax->tax_id
									  : NULL;

				if( ! $new_sale_tax->tax_id )
					throw new Exception("Invalid sale tax ID: none provided.");

				$tax = $this->_load_tax($new_sale_tax->tax_id);

				if( ! $tax->loaded() )
					throw new Exception("Invalid sale tax ID: tax not found.");

				$new_sale_tax->tax_percent = $tax->percent;
				$new_sale_tax->form_line_amount = 0.00;
				$new_sale_tax->form_line_taxable_amount = 0.00;
				$new_sale_tax->total = 0.00;

				if( ! isset($this->_sale_taxes[$new_sale_tax->tax_id]) )
					$this->_sale_taxes[$new_sale_tax->tax_id] = $new_sale_tax;
			}
		}

		foreach( $this->_data->lines as $sale_line )
		{
			$new_sale_line = $this->_default_form_line();

			$new_sale_line->account_id = ( isset($sale_line->account_id) )
										  ? (int)$sale_line->account_id
										  : NULL;

			$new_sale_line->tax_exempt = ( $this->_sale->tax_exempt ||
										   (
										   		isset($sale_line->tax_exempt) AND 
										    	$sale_line->tax_exempt 
										   ) )
									   ? TRUE
									   : FALSE;

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

			$new_sale_line->total = $this->_beans_round( 
				$new_sale_line->amount * 
				$new_sale_line->quantity 
			);

			if( ! $new_sale_line->tax_exempt )
			{
				foreach( $this->_sale_taxes as $tax_id => $sale_tax )
				{
					$this->_sale_taxes[$tax_id]->form_line_taxable_amount = $this->_beans_round(
						$this->_sale_taxes[$tax_id]->form_line_taxable_amount + 
						$new_sale_line->total 
					);
				}
			}

			$this->_sale_lines[] = $new_sale_line;
		}

		// If this is a refund we need to verify that the total is not greater than the original.
		if( $this->_sale->refund_form_id AND 
			$this->_sale->refund_form_id < $this->_sale->id AND
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
			$sale_line->delete();

		foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
			$sale_tax->delete();
		
		// Save Sale + Children
		$this->_sale->save();

		foreach( $this->_sale_lines as $j => $sale_line )
		{
			$sale_line->form_id = $this->_sale->id;
			$sale_line->save();
			
			$this->_sale->amount = $this->_beans_round( 
				$this->_sale->amount + 
				$sale_line->total 
			);
		}

		$this->_sale->total = $this->_beans_round( 
			$this->_sale->total + 
			$this->_sale->amount 
		);

		foreach( $this->_sale_taxes as $tax_id => $sale_tax )
		{
			$this->_sale_taxes[$tax_id]->form_id = $this->_sale->id;
			
			$this->_sale_taxes[$tax_id]->total = $this->_beans_round( 
				$sale_tax->tax_percent * 
				$sale_tax->form_line_taxable_amount 
			);
			
			$this->_sale_taxes[$tax_id]->form_line_amount = $this->_sale->amount;
			
			$this->_sale_taxes[$tax_id]->save();
			
			$this->_sale->total = $this->_beans_round( 
				$this->_sale->total + 
				$this->_sale_taxes[$tax_id]->total 
			);
		}

		$this->_sale->save();

		$sale_calibrate = new Beans_Customer_Sale_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_sale->id),
		)));
		$sale_calibrate_result = $sale_calibrate->execute();

		if( ! $sale_calibrate_result->success )
			throw new Exception("Error trying to create sale transactions: ".$sale_calibrate_result->error);
		
		// Reload the sale.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);

		// Upon updating a sale, if the total has changed we change the sent flag.
		if( $this->_sale->total != $sale_original_total AND 
			! isset($this->_data->sent) )
			$this->_sale->sent = FALSE;
		
		$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
			'form_ids' => array($this->_sale->id),
		)));
		$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

		if( ! $customer_payment_calibrate_result->success )
			throw new Exception("Error encountered when calibrating payments: ".$customer_payment_calibrate_result->error);
		
		// Update tax items only if we're successful 
		// AND
		// Only if we've billed this sales order.
		if( $this->_sale->date_billed )
		{
			$tax_item_action = 'invoice';
			if( $this->_sale->refund_form_id && 
				$this->_sale->refund_form_id < $this->_sale->id )
				$tax_item_action = 'refund';
			
			$this->_update_form_tax_items($this->_sale->id, $tax_item_action);
		}

		// We need to reload the sale so that we can get the correct balance, etc.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}