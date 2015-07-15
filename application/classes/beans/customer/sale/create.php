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
@optional tax_exempt BOOLEAN If set to true, all lines will be marked as tax_exempt.
@optional tax_exempt_reason STRING An explanation for tax exemption.  Required if tax_exempt is true.  Will be set to NULL if tax_exempt is false.
@optional billing_address_id INTEGER The ID of the #Beans_Customer_Address# for billing this sale.
@optional shipping_address_id INTEGER The ID of the #Beans_Customer_Address# for shipping this sale.
@required lines ARRAY An array of objects representing line items for the sale.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity DECIMAL The number of units (up to three decimal places).
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the sale towards ( in terms of revenue ).
@optional @attribute lines tax_exempt BOOLEAN
@optional taxes ARRAY An array of objects representing taxes applicable to the sale.
@required @attribute taxes tax_id The ID of the #Beans_Tax# that should be applied.
@returns sale OBJECT The resulting #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Create extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_data;
	protected $_sale;
	protected $_sale_lines;
	protected $_sale_taxes;
	
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
		$this->_sale_taxes = array();
		
		$this->_date_billed = ( isset($this->_data->date_billed) )
							? $this->_data->date_billed
							: FALSE;
		$this->_date_due = ( isset($this->_data->date_due) )
						 ? $this->_data->date_due
						 : FALSE;
	}

	protected function _execute()
	{
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

		$this->_sale->tax_exempt = ( isset($this->_data->tax_exempt) AND 
									 $this->_data->tax_exempt )
								 ? TRUE
								 : FALSE;

		$this->_sale->tax_exempt_reason = ( $this->_sale->tax_exempt AND 
											isset($this->_data->tax_exempt_reason) AND 
											$this->_data->tax_exempt_reason )
										? $this->_data->tax_exempt_reason
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

		if( isset($this->_data->taxes) )
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
									 ? (float)$sale_line->quantity
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
			$this->_sale->total > $this->_load_customer_sale($this->_sale->refund_form_id)->total )
			throw new Exception("That refund total was greater than the original sale total.");
		
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

		if( $this->_sale->code == "AUTOGENERATE" )
			$this->_sale->code = $this->_sale->id;
		
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

			// TODO - 100PERCENTWORKING
			if( ! $delete_sale_result->success )
				throw new Exception("Error creating account transaction for sale. COULD NOT DELETE SALE! ".$delete_sale_result->error);

			throw new Exception("Error trying to create sale: ".$sale_calibrate_result->error);
		}

		// Reload the sale.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);

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

				// TODO - 100PERCENTWORKING
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