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
class Beans_Customer extends Beans {

	protected $_auth_role_perm = "customer_read";
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _return_customers_array($customers)
	{
		$return_array = array();
		
		foreach( $customers as $customer )
			$return_array[] = $this->_return_customer_element($customer);
		
		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer
	@description Represents a customer in the system.
	@attribute id INTEGER 
	@attribute first_name STRING
	@attribute last_name STRING
	@attribute company_name STRING
	@attribute email STRING
	@attribute phone_number STRING
	@attribute fax_number STRING
	@attribute default_billing_address_id INTEGER The ID for the default #Beans_Customer_Address# for billing the customer.
	@attribute default_shipping_address_id INTEGER The ID for the default #Beans_Customer_Address# for shipping to the customer.
	@attribute default_account OBJECT The default #Beans_Account# for the customer - Empty if not set.
	@attribute sales_count INTEGER The total number of sales attached to this customer.
	@attribute sales_total DECIMAL The total value of all sales attached to this customer.
	@attribute balance_pending DECIMAL The total balance that currently unpaid.
	@attribute balance_pastdue DECIMAL The total balance that is currently past due.
	---BEANSENDSPEC---
	 */

	private $_return_customer_element_cache = array();
	protected function _return_customer_element($customer)
	{
		$return_object = new stdClass;

		if( get_class($customer) != "Model_Entity_Customer" AND
			$customer->type != "customer" )
			throw new Exception("Invalid Customer.");

		if( isset($this->_return_customer_element_cache[$customer->id]) )
			return $this->_return_customer_element_cache[$customer->id];

		$return_object->id = $customer->id;
		$return_object->first_name = $customer->first_name;
		$return_object->last_name = $customer->last_name;
		$return_object->company_name = $customer->company_name;
		$return_object->email = $customer->email;
		$return_object->phone_number = $customer->phone_number;
		$return_object->fax_number = $customer->fax_number;

		// Default Shipping and Billing - should these be objects?
		$return_object->default_billing_address_id = $customer->default_billing_address_id;
		$return_object->default_shipping_address_id = $customer->default_shipping_address_id;
		
		$return_object->default_account = ( $customer->default_account_id ? $this->_return_account_element($customer->default_account) : (object)array() );
		
		// Account Balance AND Past-Due Balance
		$account_balances = DB::query(Database::SELECT, 'SELECT COUNT(id) as count, SUM(total) AS total, SUM(balance) AS balance, IF(date_due < DATE("'.date('Y-m-d').'"),TRUE,FALSE) as past_due FROM forms WHERE entity_id = "'.$customer->id.'" GROUP BY past_due')->execute()->as_array();
		
		$return_object->sales_count = 0;
		$return_object->sales_total = 0.00;
		$return_object->balance_pending = 0.00;
		$return_object->balance_pastdue = 0.00;
		foreach( $account_balances as $account_balance )
		{
			$return_object->sales_count += $account_balance['count'];
			$return_object->sales_total = $this->_beans_round( $return_object->sales_total + $account_balance['total'] );
			if( $account_balance['past_due'] )
				$return_object->balance_pastdue = $this->_beans_round( $return_object->balance_pastdue + $account_balance['balance'] );
			else
				$return_object->balance_pending = $this->_beans_round( $return_object->balance_pending + $account_balance['balance'] );
		}

		$this->_return_customer_element_cache[$customer->id] = $return_object;
		return $this->_return_customer_element_cache[$customer->id];
	}

	protected function _return_customer_addresses_array($addresses)
	{
		$return_array = array();

		foreach( $addresses as $address )
			$return_array[] = $this->_return_customer_address_element($address);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Address
	@description Represents a customer address.
	@attribute id INTEGER 
	@attribute first_name STRING
	@attribute last_name STRING
	@attribute company_name STRING
	@attribute address1 STRING
	@attribute address2 STRING
	@attribute city STRING
	@attribute state STRING
	@attribute zip STRING
	@attribute country STRING
	@attribute standard STRING A single-line summary for displaying the address.
	---BEANSENDSPEC---
	 */

	private $_return_customer_address_element_cache = array();
	protected function _return_customer_address_element($address)
	{
		$return_object = new stdClass;

		if( get_class($address) != "Model_Entity_Address" )
			throw new Exception("Invalid Address.");

		if( isset($this->_return_customer_address_element_cache[$address->id]) )
			return $this->_return_customer_address_element_cache[$address->id];

		$return_object->id = $address->id;
		$return_object->customer_id = $address->entity_id;
		$return_object->first_name = $address->first_name;
		$return_object->last_name = $address->last_name;
		$return_object->company_name = $address->company_name;
		$return_object->address1 = $address->address1;
		$return_object->address2 = $address->address2;
		$return_object->city = $address->city;
		$return_object->state = $address->state;
		$return_object->zip = $address->zip;
		$return_object->country = $address->country;

		$return_object->standard = 
			( $return_object->first_name ? $return_object->first_name.' ' : '' ).
			( $return_object->last_name ? $return_object->last_name.' ' : '' ).
			( $return_object->company_name ? $return_object->company_name.' ' : '' ).
			( $return_object->address1 ? $return_object->address1.' ' : '' ).
			( $return_object->address2 ? $return_object->address2.' ' : '' ).
			( $return_object->city ? $return_object->city.', ' : '' ).
			( $return_object->state ? $return_object->state.' ' : '' ).
			( $return_object->zip ? $return_object->zip.' ' : '' ).
			( $return_object->country ? $return_object->country.' ' : '' );

		$this->_return_customer_address_element_cache[$address->id] = $return_object;
		return $this->_return_customer_address_element_cache[$address->id];
	}

	protected function _default_customer()
	{
		$customer = ORM::Factory("entity_customer");

		$customer->default_shipping_address_id = NULL;
		$customer->default_billing_address_id = NULL;
		$customer->first_name = NULL;
		$customer->last_name = NULL;
		$customer->company_name = NULL;
		$customer->email = NULL;
		$customer->phone_number = NULL;
		$customer->fax_number = NULL;

		return $customer;
	}

	protected function _validate_customer($customer)
	{
		if( get_class($customer) != "Model_Entity_Customer" )
			throw new Exception("Invalid Customer.");

		if( ! $customer->first_name OR
			! strlen($customer->first_name) )
			throw new Exception("Invalid customer first name: none provided.");

		if( strlen($customer->first_name) > 64 )
			throw new Exception("Invalid customer first name: maximum of 64 characters.");

		if( ! $customer->last_name OR
			! strlen($customer->last_name) )
			throw new Exception("Invalid customer last name: none provided.");
		
		if( strlen($customer->last_name) > 64 )
			throw new Exception("Invalid customer last name: maximum of 64 characters.");

		if( strlen($customer->company_name) > 64 )
			throw new Exception("Invalid customer company name: maximum of 64 characters.");

		/*
		if( ! $customer->email OR 
			! strlen($customer->email) )
			throw new Exception("Invalid customer email: none provided");
		*/
	
		if( $customer->email AND 
			strlen($customer->email) > 255 )
			throw new Exception("Invalid customer email: maximum of 254 characters.");

		if( $customer->email AND 
			! filter_var($customer->email,FILTER_VALIDATE_EMAIL) )
			throw new Exception("Invalid customer email: invalid email address.");

		if( strlen($customer->phone_number) > 32 )
			throw new Exception("Invalid customer phone number: maximum of 32 characters.");

		if( strlen($customer->fax_number) > 32 )
			throw new Exception("Invalid customer fax number: maximum of 32 characters.");

		if( $customer->default_billing_address_id )
		{
			$default_billing_address = $this->_load_customer_address($customer->default_billing_address_id);
			if( ! $default_billing_address->loaded() OR
				$default_billing_address->entity_id != $customer->id )
				throw new Exception("Invalid customer default billing address: not found.");
		}
		
		if( $customer->default_shipping_address_id )
		{
			$default_shipping_address = $this->_load_customer_address($customer->default_shipping_address_id);
			if( ! $default_shipping_address->loaded() OR
				$default_shipping_address->entity_id != $customer->id )
				throw new Exception("Invalid customer default billing address: not found.");
		}

		if( $customer->default_account_id )
		{
			$default_account = $this->_load_account($customer->default_account_id);
			if( ! $default_account->loaded() )
				throw new Exception("Invalid customer default account receivable: not found.");
			if( ! $default_account->receivable )
				throw new Exception("Invalid customer default account receivable: must be asset.");
		}
	}

	protected function _load_customer($id)
	{
		return ORM::Factory('entity_customer')->where('type','=','customer')->where('id','=',$id)->find();
	}

	protected function _load_customer_sale($id)
	{
		$sale = ORM::Factory('form_sale',$id);

		return ( $sale->type == "sale" ? $sale : ORM::Factory('form_sale',-1) );
	}
	
	protected function _default_customer_address()
	{
		$customer_address = ORM::Factory('entity_address');

		$customer_address->entity_id = NULL;
		$customer_address->first_name = NULL;
		$customer_address->last_name = NULL;
		$customer_address->company_name = NULL;
		$customer_address->address1 = NULL;
		$customer_address->address2 = NULL;
		$customer_address->city = NULL;
		$customer_address->state = NULL;
		$customer_address->zip = NULL;
		$customer_address->country = NULL;

		return $customer_address;
	}

	protected function _validate_customer_address($customer_address,$ignore_entity = FALSE)
	{
		if( get_class($customer_address) != "Model_Entity_Address" )
			throw new Exception("Invalid address.");

		if( ! $ignore_entity ) 
		{
			if( ! $customer_address->entity_id OR 
				! strlen($customer_address->entity_id) )
				throw new Exception("Invalid address customer ID (entity_id): none provided.");

			if( ! $this->_load_customer($customer_address->entity_id)->loaded() )
				throw new Exception("Invalid address customer ID (entity_id): customer not found.");
		}
		
		if( ! $customer_address->first_name OR
			! strlen($customer_address->first_name) )
			throw new Exception("Invalid address first name: none provided.");

		if( strlen($customer_address->first_name) > 64 )
			throw new Exception("Invalid address first name: maximum of 64 characters.");

		if( ! $customer_address->last_name OR
			! strlen($customer_address->last_name) )
			throw new Exception("Invalid address last name: none provided.");

		if( strlen($customer_address->last_name) > 64 )
			throw new Exception("Invalid address last name: maximum of 64 characters.");

		if( strlen($customer_address->company_name) > 64 )
			throw new Exception("Invalid address company name: maximum of 64 characters.");

		if( ! $customer_address->address1 OR 
			! strlen($customer_address->address1) )
			throw new Exception("Invalid address primary street: none provided.");

		if( strlen($customer_address->address2) > 128 )
			throw new Exception("Invalid address primary street: maximum of 128 characters.");

		if( strlen($customer_address->address2) > 128 )
			throw new Exception("Invalid address secondary street: maximum of 128 characters.");

		if( ! $customer_address->city OR
			! strlen($customer_address->city) )
			throw new Exception("Invalid address city: none provided.");

		if( strlen($customer_address->city) > 64 )
			throw new Exception("Invalid address city: maximum of 64 characters.");

		if( strlen($customer_address->state) > 64 )
			throw new Exception("Invalid address state: maximum of 64 characters.");

		if( ! $customer_address->zip OR 
			! strlen($customer_address->zip) )
			throw new Exception("Invalid address postal code: none provided.");

		if( strlen($customer_address->zip) > 32 )
			throw new Exception("Invalid address postal code: maximum of 32 characters.");

		if( ! $customer_address->country OR 
			! strlen($customer_address->country) )
			throw new Exception("Invalid address country: none provided.");

		if( strlen($customer_address->country) != 2 )
			throw new Exception("Invalid address country: must be 2 characters.");

	}

	protected function _load_customer_address($id)
	{
		return ORM::Factory('entity_address',$id);
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Sale
	@description A customer sale.
	@attribute id INTEGER 
	@attribute customer OBJECT The #Beans_Customer# the sale belongs to.
	@attribute account OBJECT The #Beans_Account# that is the AR for the sale.
	@attribute sent STRING The sent status of the current state of the sale: 'phone', 'email', 'both', or NULL
	@attribute refund_sale_id INTEGER The ID of a corresponding sale in a refund pair.
	@attribute date_created STRING
	@attribute date_billed STRINg
	@attribute date_due STRING
	@attribute date_cancelled STRING
	@attribute create_transaction_id INTEGER The ID for the #Beans_Transaction# tied to the sale creation.
	@attribute invoice_transaction_id INTEGER The ID for the #Beans_Transaction# tied to invoicing the sale.
	@attribute cancel_transaction_id INTEGER The ID for the #Beans_Transaction# tied to cancelling the sale.
	@attribute subtotal DECIMAL
	@attribute total DECIMAL
	@attribute taxestotal DECIMAL
	@attribute balance DECIMAL
	@attribute sale_number STRING
	@attribute order_number STRING
	@attribute po_number STRING
	@attribute quote_number STRING
	@attribute billing_address OBJECT The #Beans_Customer_Address# used to bill this sale.
	@attribute shipping_address OBJECT The #Beans_Customer_Address# used to ship this sale.
	@attribute lines ARRAY An array of #Beans_Customer_Sale_Line#.
	@attribute taxes ARRAY An array of #Beans_Customer_Sale_Tax# - these are the applied taxes and their totals.
	@attribute payments ARRAY An array of the #Beans_Customer_Payment# that this sale is tied to.
	@attribute status STRING A short description of the status of the sale.
	@attribute title STRING A short description of the sale.
	---BEANSENDSPEC---
	 */

	private $_return_customer_sale_element_cache = array();
	protected function _return_customer_sale_element($sale)
	{
		$return_object = new stdClass;

		if( get_class($sale) != "Model_Form_Sale" AND
			$sale->type != "sale" )
			throw new Exception("Invalid Customer Sale.");

		if( isset($this->_return_customer_sale_element_cache[$sale->id]) )
			return $this->_return_customer_sale_element_cache[$sale->id];

		$return_object->id = $sale->id;
		$return_object->customer = $this->_return_customer_element($sale->entity);
		
		$return_object->account = $this->_return_account_element($sale->account);
		
		$return_object->sent = ( $sale->sent AND strlen($sale->sent) )
							 ? $sale->sent
							 : FALSE;

		// Refund
		$return_object->refund_sale_id = ( $sale->refund_form->loaded() )
										   ? $sale->refund_form->id
										   : NULL;

		$return_object->date_created = $sale->date_created;
		$return_object->date_billed = $sale->date_billed;
		$return_object->date_due = $sale->date_due;
		$return_object->date_cancelled = $sale->date_cancelled;

		$return_object->create_transaction_id = $sale->create_transaction_id;
		$return_object->invoice_transaction_id = $sale->invoice_transaction_id;
		$return_object->cancel_transaction_id = $sale->cancel_transaction_id;
		
		$return_object->subtotal = $sale->amount;
		$return_object->total = $sale->total;
		$return_object->taxestotal = $this->_beans_round( $return_object->total - $return_object->subtotal );
		$return_object->balance = $sale->balance;
		
		$return_object->sale_number = $sale->code;
		$return_object->order_number = $sale->reference;
		$return_object->po_number = $sale->alt_reference;
		$return_object->quote_number = $sale->aux_reference;

		$return_object->billing_address = ( $sale->billing_address_id ) 
										? $this->_return_customer_address_element($sale->billing_address)
										: NULL;
		$return_object->shipping_address = ( $sale->shipping_address_id )
										 ? $this->_return_customer_address_element($sale->shipping_address)
										 : NULL;

		$return_object->lines = $this->_return_form_lines_array($sale->form_lines->find_all());
		$return_object->taxes = $this->_return_form_taxes_array($sale->form_taxes->find_all());

		$return_object->payments = $this->_return_form_payments_array($sale->account_transaction_forms->find_all());

		$return_object->status = $this->_customer_sale_status($return_object);
		$return_object->title = ( $sale->date_billed )
							  ? "Sales Invoice ".$sale->code
							  : "Sales Order ".$sale->code;

		$this->_return_customer_sale_element_cache[$sale->id] = $return_object;
		return $this->_return_customer_sale_element_cache[$sale->id];
	}

	protected function _return_form_payments_array($account_transaction_forms)
	{
		$return_array = array();

		foreach( $account_transaction_forms as $account_transaction_form )
			if( $account_transaction_form->account_transaction->transaction->payment )
				$return_array[] = $this->_return_form_payment_element($account_transaction_form);

		return $return_array;
	}

	protected function _return_form_payment_element($account_transaction_form)
	{
		$return_object = new stdClass;

		$return_object->id = $account_transaction_form->account_transaction->transaction->id;
		$return_object->date = $account_transaction_form->account_transaction->transaction->date;
		$return_object->amount = $account_transaction_form->amount;

		return $return_object;
	}

	protected function _customer_sale_status($sale)
	{
		if( $sale->date_cancelled AND 
			$sale->balance != 0 )
			return "Pending Refund";
		if( $sale->date_cancelled )
			return "Deleted";
		if( $sale->refund_sale_id AND
			$sale->balance != 0 )
			return "Refund Pending";
		if( $sale->refund_sale_id )
			return "Refunded";
		if( $sale->balance == 0 )
			return "Paid";
		if( ! $sale->sent AND 
			! $sale->date_billed )
			return "SO Not Sent";
		if( $sale->date_billed AND 
			! $sale->sent )
			return "Invoice Not Sent";
		if( $sale->date_billed )
			return "Due ".$sale->date_due;

		return "SO Sent";
	}

	protected function _return_form_lines_array($lines)
	{
		$return_array = array();

		foreach( $lines as $line )
			$return_array[] = $this->_return_form_line_element($line);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Sale_Line
	@description A line item on a customer sale.
	@attribute id INTEGER 
	@attribute account OBJECT The #Beans_Account# tied to this line.
	@attribute description STRING
	@attribute amount DECIMAL
	@attribute quantity INTEGER
	@attribute total DECIMAL
	@attribute line_taxes ARRAY An array of #Beans_Customer_Sale_Line_Tax# for this line.
	---BEANSENDSPEC---
	 */

	private $_return_form_line_element_cache = array();
	protected function _return_form_line_element($line)
	{
		$return_object = new stdClass;

		if( get_class($line) != "Model_Form_Line" )
			throw new Exception("Invalid Line.");

		if( isset($this->_return_form_line_element[$line->id]) )
			return $this->_return_form_line_element[$line->id];

		$return_object->id = $line->id;

		$return_object->account = $this->_return_account_element($line->account);

		$return_object->description = $line->description;
		$return_object->amount = $line->amount;
		$return_object->quantity = $line->quantity;
		$return_object->total = $line->total;

		$return_object->line_taxes = $this->_return_line_taxes_array($line->form_line_taxes->find_all());
		
		$this->_return_form_line_element[$line->id] = $return_object;
		return $this->_return_form_line_element[$line->id];
	}

	protected function _return_line_taxes_array($line_taxes)
	{
		$return_array = array();

		foreach( $line_taxes as $line_tax )
			$return_array[] = $this->_return_line_tax_element($line_tax);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Sale_Line_Tax
	@description A tax applied to a single line item.
	@attribute id INTEGER 
	@attribute tax OBJECT The #Beans_Tax# tied to this line tax.
	---BEANSENDSPEC---
	 */

	private $_return_line_tax_element_cache = array();
	protected function _return_line_tax_element($line_tax)
	{
		$return_object = new stdClass;

		if( get_class($line_tax) != "Model_Form_Line_Tax" )
			throw new Exception("Invalid Line Tax.");

		if( isset($this->_return_line_tax_element_cache[$line_tax->id]) )
			return $this->_return_line_tax_element_cache[$line_tax->id];
		
		$return_object->id = $line_tax->id;
		$return_object->tax = $this->_return_tax_element($line_tax->tax);
		
		$this->_return_line_tax_element_cache[$line_tax->id] = $return_object;
		return $this->_return_line_tax_element_cache[$line_tax->id];
	}

	protected function _return_form_taxes_array($form_taxes)
	{
		$return_array = array();

		foreach( $form_taxes as $form_tax )
			$return_array[] = $this->_return_form_tax_element($form_tax);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Sale_Tax
	@description A summary of a tax applied to one or more lines on a sale.
	@attribute id INTEGER 
	@attribute tax OBJECT The #Beans_Tax# tied to this line tax.
	@attribute amount DECIMAL The line total that the tax is applied to ( if percent is not NULL ).
	@attribute fee DECIMAL The flat fee for the tax.
	@attribute percent DECIMAL The percent applied to the amount.
	---BEANSENDSPEC---
	 */

	private $_return_form_tax_element_cache = array();
	protected function _return_form_tax_element($form_tax)
	{
		$return_object = new stdClass;

		if( get_class($form_tax) != "Model_Form_Tax" )
			throw new Exception("Invalid Sale Tax.");

		if( isset($this->_return_form_tax_element_cache[$form_tax->id]) )
			return $this->_return_form_tax_element_cache[$form_tax->id];
		
		$return_object->id = $form_tax->id;
		// $return_object->sale_id = $form_tax->form_id; // *** TRIM ***
		$return_object->tax = $this->_return_tax_element($form_tax->tax);
		$return_object->amount = $form_tax->amount;
		$return_object->total = $form_tax->total;
		$return_object->fee = $form_tax->fee;
		$return_object->percent = $form_tax->percent;
		
		$this->_return_form_tax_element_cache[$form_tax->id] = $return_object;
		return $this->_return_form_tax_element_cache[$form_tax->id];
	}

	private $_return_tax_element_cache = array();
	protected function _return_tax_element($tax)
	{
		$return_object = new stdClass;

		if( get_class($tax) != "Model_Tax" OR 
			! $tax->loaded() )
			throw new Exception("Invalid Tax Object.");

		if( isset($this->_return_tax_element_cache[$tax->id]) )
			return $this->_return_tax_element_cache[$tax->id];

		$return_object->id = $tax->id;
		$return_object->code = $tax->code;
		$return_object->name = $tax->name;
		$return_object->percent = $tax->percent;
		$return_object->fee = $tax->fee;
		$return_object->account = $this->_return_account_element($tax->account);

		$this->_return_tax_element_cache[$tax->id] = $return_object;
		return $this->_return_tax_element_cache[$tax->id];
	}


	// *** DUPLICATE FUNCTION ***
	protected function _load_account($id)
	{
		return ORM::Factory('account',$id);
	}

	// *** DUPLICATE FUNCTION ***
	private $_return_account_element_cache = array();
	protected function _return_account_element($account)
	{
		$return_object = new stdClass;

		// Verify this model.
		if( ! $account OR
			! $account->loaded() OR
			get_class($account) != "Model_Account" )
			throw new Exception("Invalid Account.");

		if( isset($this->_return_account_element_cache[$account->id]) )
			return $this->_return_account_element_cache[$account->id];

		// Account Details
		$return_object->id = $account->id;
		$return_object->parent_account_id = $account->parent_account_id;
		$return_object->name = $account->name;
		$return_object->code = $account->code;
		$return_object->reconcilable = $account->reconcilable ? TRUE : FALSE;
		$return_object->terms = (int)$account->terms;
		$return_object->balance = (float)$account->balance;
		$return_object->deposit = $account->deposit ? TRUE : FALSE;
		$return_object->payment = $account->payment ? TRUE : FALSE;
		$return_object->receivable = $account->receivable ? TRUE : FALSE;
		$return_object->payable = $account->payable ? TRUE : FALSE;
		$return_object->writeoff = $account->writeoff ? TRUE : FALSE;
		
		// Account Type
		$return_object->type = $this->_return_account_type_element($account->account_type);
		
		$this->_return_account_element_cache[$account->id] = $return_object;
		return $this->_return_account_element_cache[$account->id];
	}

	// *** DUPLICATE FUNCTION ***
	private $_return_account_type_element_cache = array();
	protected function _return_account_type_element($account_type)
	{
		$return_object = new stdClass;

		if( get_class($account_type) != "Model_Account_Type" )
			throw new Exception("Invalid Account Type.");

		if( isset($this->_return_account_type_element_cache[$account_type->id]) ) 
			return $this->_return_account_type_element_cache[$account_type->id];

		$return_object->id = $account_type->id;
		$return_object->name = $account_type->name;
		$return_object->code = $account_type->code;
		$return_object->table_sign = $account_type->table_sign;

		$this->_return_account_type_element_cache[$account_type->id] = $return_object;
		return $this->_return_account_type_element_cache[$account_type->id];
	}

	// *** DUPLICATE FUNCTION ***
	protected function _load_transaction($id)
	{
		return ORM::Factory('transaction',$id);
	}

	protected function _return_customer_payments_array($payments)
	{
		$return_array = array();

		foreach( $payments as $payment )
			$return_array[] = $this->_return_customer_payment_element($payment);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Payment
	@description A payment on one or more customer sales.
	@attribute id INTEGER 
	@attribute number STRING The check or reference number
	@attribute description STRING
	@attribute date STRING
	@attribute amount DECIMAL Total received.
	@attribute account_transactions ARRAY An array of the #Beans_Account_Transaction# that make up this transaction.
	@attribute sale_payments ARRAY An array of #Beans_Customer_Payment_Sale# representing the amounts paid on each sale.
	@attribute deposit_transaction OBJECT The #Beans_Transaction# representing the split going into a cash account.
	@attribute writeoff_transaction OBJECT The #Beans_Transaction# representing the writeoff transaction if it exists.
	@attribute reconciled BOOLEAN Whether one or more of the account transactions was reconciled.
	---BEANSENDSPEC---
	 */

	private $_return_customer_payment_element_cache = array();
	protected function _return_customer_payment_element($payment)
	{
		$return_object = new stdClass;

		if( get_class($payment) != "Model_Transaction" OR 
			$payment->payment != "customer" )
			throw new Exception("Invalid Customer Payment.");

		if( isset($this->_return_customer_payment_element_cache[$payment->id]) )
			return $this->_return_customer_payment_element_cache[$payment->id];

		$return_object->id = $payment->id;
		$return_object->number = $payment->code;
		$return_object->description = $payment->description;
		$return_object->date = $payment->date;
		$return_object->payment = $payment->payment;	// Somewhat redundant.
		$return_object->amount = 0.00;
		// $return_object->amount = $payment->amount;
		
		// This _return_account_transactions_array is different from Beans_Account->_return_account_transactions_array()
		// Does not dump full form information.
		// see below: _return_account_transaction_element()
		$return_object->account_transactions = $this->_return_account_transactions_array($payment->account_transactions->find_all());
		
		$return_object->sale_payments = $this->_return_customer_payment_sales_array($payment->account_transactions->find_all());

		$return_object->deposit_transaction = FALSE;
		$return_object->writeoff_transaction = FALSE;
		
		foreach( $return_object->account_transactions as $account_transaction )
		{
			if( isset($account_transaction->transfer) AND 
				$account_transaction->transfer ) 
			{
				$return_object->deposit_transaction = $account_transaction;
				$return_object->amount = $this->_beans_round(
					$return_object->amount +
					$account_transaction->amount *
					$account_transaction->account->type->table_sign
				);
			}
			if( isset($account_transaction->writeoff) AND 
				$account_transaction->writeoff ) 
			{
				$return_object->writeoff_transaction = $account_transaction;
				/*
				$return_object->amount = $this->_beans_round(
					$return_object->amount +
					$account_transaction->amount *
					$account_transaction->account->type->table_sign
				);
				*/
			}
		}

		if( ! $return_object->deposit_transaction )
			throw new Exception("Invalid payment - no deposit account found.");

		$return_object->reconciled = FALSE;
		
		// V2Item
		// Might want to replace this with a reconciled flag on the transaction model.
		$i = 0;
		while( 	! $return_object->reconciled AND $i < count($return_object->account_transactions) )
			if( $return_object->account_transactions[$i++]->reconciled )
				$return_object->reconciled = TRUE;
		
		$this->_return_customer_payment_element_cache[$payment->id] = $return_object;
		return $this->_return_customer_payment_element_cache[$payment->id];
	}

	
	protected function _validate_customer_payment($payment)
	{
		if( get_class($payment) != "Model_Transaction" )
			throw new Exception("Invalid Payment Object.");

		if( ! $payment->date )
			throw new Exception("Invalid payment date: none provided.");

		if( $payment->date != date("Y-m-d",strtotime($payment->date)) )
			throw new Exception("Invalid payment date: must be in format YYYY-MM-DD.");

		if( $this->_check_books_closed($payment->date) )
			throw new Exception("Invalid payment date: that financial year is already closed.");

		if( $payment->code AND 
			strlen($payment->code) > 16 )
			throw new Exception("Invalid payment number: maximum length of 16 characters.");

		if( $payment->description AND 
			strlen($payment->description) > 128 )
			throw new Exception("Invalid payment description: maximum length of 128 characters.");
	}
	
	protected function _default_customer_payment()
	{
		$transaction = ORM::Factory('transaction');

		$transaction->code = NULL;
		$transaction->description = NULL;
		$transaction->date = NULL;
		$transaction->payment = "customer";
		
		return $transaction;
	}
	
	protected function _load_customer_payment($id)
	{
		return ORM::Factory('transaction',$id);
	}

	protected function _return_account_transactions_array($account_transactions)
	{
		$return_array = array();

		foreach( $account_transactions as $account_transaction )
			$return_array[] = $this->_return_account_transaction_element($account_transaction);

		return $return_array;
	}

	private $_return_account_transaction_element_cache = array();
	protected function _return_account_transaction_element($account_transaction)
	{
		$return_object = new stdClass;

		if( ! $account_transaction->loaded() OR
			get_class($account_transaction) != "Model_Account_Transaction" )
			throw new Exception("Invalid Account Transaction.");

		if( isset($this->_return_account_transaction_element_cache[$account_transaction->id]) )
			return $this->_return_account_transaction_element_cache[$account_transaction->id];

		$return_object->id = $account_transaction->id;
		$return_object->amount = (float)$account_transaction->amount;
		$return_object->balance = (float)$account_transaction->balance;
		$return_object->reconciled = $account_transaction->account_reconcile_id ? TRUE : FALSE;

		$return_object->transfer = ( $account_transaction->transfer ) ? TRUE : FALSE;
		$return_object->writeoff = ( $account_transaction->writeoff ) ? TRUE : FALSE;

		// *** FAT ***
		$return_object->account = $this->_return_account_element($account_transaction->account);
		
		$this->_return_account_transaction_element_cache[$account_transaction->id] = $return_object;
		return $this->_return_account_transaction_element_cache[$account_transaction->id];
	}
	
	protected function _return_customer_payment_sales_array($account_transactions)
	{
		$return_array = array();

		foreach( $account_transactions as $account_transaction )
		{
			if( ! $account_transaction->transfer AND
				! $account_transaction->writeoff )
			{
				foreach( $account_transaction->account_transaction_forms->find_all() as $account_transaction_form ) 
				{
					$return_array[$account_transaction_form->form_id] = $this->_return_customer_payment_sale_element($account_transaction_form);
				}
			}
		}

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Customer_Payment_Sale
	@description A payment a customer sale.
	@attribute id INTEGER 
	@attribute sale OBJECT The #Beans_Customer_Sale# this credit/debit applies to.
	@attribute amount DECIMAL The amount actually paid by the customer.
	@attribute writeoff_amount DECIMAL The writeoff amount ( if any ).
	---BEANSENDSPEC---
	 */

	private $_return_customer_payment_sale_element_cache = array();
	protected function _return_customer_payment_sale_element($account_transaction_form)
	{
		$return_object = new stdClass;

		if( get_class($account_transaction_form) != "Model_Account_Transaction_Form" )
			throw new Exception("Invalid Transaction Sale.");

		if( isset($this->_return_customer_payment_sale_element_cache[$account_transaction_form->id]) )
			return $this->_return_customer_payment_sale_element_cache[$account_transaction_form->id];

		$return_object->id = $account_transaction_form->id;
		
		// V2Item - Determine if we can remove sale from this and replace with sale_id
		$return_object->sale = $this->_return_customer_sale_element($account_transaction_form->form);
		$return_object->amount = $account_transaction_form->amount - $account_transaction_form->writeoff_amount;
		$return_object->writeoff_amount = $account_transaction_form->writeoff_amount;

		$this->_return_customer_payment_sale_element_cache[$account_transaction_form->id] = $return_object;
		return $this->_return_customer_payment_sale_element_cache[$account_transaction_form->id];
	}

	protected function _return_customer_sales_array($sales)
	{
		$return_array = array();

		foreach( $sales as $sale )
			$return_array[] = $this->_return_customer_sale_element($sale);

		return $return_array;
	}

	protected function _default_customer_sale()
	{
		$sale = ORM::Factory('form_sale');

		$sale->entity_id = NULL;
		$sale->account_id = NULL;
		$sale->total = NULL;
		$sale->balance = 0.00;
		$sale->shipping_address_id = NULL;
		$sale->billing_address_id = NULL;
		$sale->sent = NULL;
		$sale->date_created = NULL;
		$sale->date_due = NULL;
		$sale->code = NULL;
		$sale->reference = NULL;
		$sale->alt_reference = NULL;
		$sale->create_transaction_id = NULL;
		$sale->invoice_transaction_id = NULL;

		return $sale;
	}

	protected function _validate_customer_sale($sale)
	{
		if( get_class($sale) != "Model_Form_Sale" AND 
			$sale->type != "sale" )
			throw new Exception("Invalid Sale.");

		if( ! $sale->entity_id )
			throw new Exception("Invalid sale customer: none provided.");

		if( ! $this->_load_customer($sale->entity_id)->loaded() )
			throw new Exception("Invalid sale customer: customer not found.");

		if( ! $sale->account_id )
			throw new Exception("Invalid sale account receivable: none provided.");

		// Verify Account - Must be valid and marked as "receivable"
		$account = $this->_load_account($sale->account_id);
		
		if( ! $account->loaded() )
			throw new Exception("Invalid sale account receivable: account not found.");

		if( ! $account->receivable )
			throw new Exception("Invalid sale account receivable: account not receivable.");

		if( $account->reserved )
			throw new Exception("Invalid sale account receivable: cannot be a reserved account.");

		if( $sale->refund_form_id )
		{
			$refund_sale = $this->_load_customer_sale($sale->refund_form_id);

			if( ! $refund_sale->loaded() )
				throw new Exception("Invalid sale refund sale ID: sale not found.");

			if( $refund_sale->refund_form->loaded() AND
				$refund_sale->refund_form_id != $sale->id )
				throw new Exception("Invalid sale refund sale ID: sale already has refund.");
		}

		if( $sale->billing_address_id )
		{
			$billing_address = $this->_load_customer_address($sale->billing_address_id);

			if( ! $billing_address->loaded() )
				throw new Exception("Invalid sale billing address: address not found.");

			if( $billing_address->entity_id != $sale->entity_id )
				throw new Exception("Invalid sale billing address: does not belong to customer.");
		}

		if( $sale->shipping_address_id )
		{
			$shipping_address = $this->_load_customer_address($sale->shipping_address_id);

			if( ! $shipping_address->loaded() )
				throw new Exception("Invalid sale shipping address: address not found.");

			if( $shipping_address->entity_id != $sale->entity_id )
				throw new Exception("Invalid sale shipping address: does not belong to customer.");
		}
		
		if( ! $sale->code OR 
			! strlen($sale->code) )
			throw new Exception("Invalid sale number: none provided.");

		if( strlen($sale->code) > 16 )
			throw new Exception("Invalid sale number: maximum of 16 characters.");

		if( (
				$sale->reference OR
				strlen($sale->reference)
			) AND 
			strlen($sale->reference) > 16 )
			throw new Exception("Invalid sale order number: maximum of 16 characters.");

		if( (
				$sale->alt_reference OR
				strlen($sale->alt_reference)
			) AND 
			strlen($sale->alt_reference) > 16 )
			throw new Exception("Invalid sale purchase order number: maximum of 16 characters.");

		if( (
				$sale->aux_reference OR
				strlen($sale->aux_reference)
			) AND 
			strlen($sale->aux_reference) > 16 )
			throw new Exception("Invalid sale quote number: maximum of 16 characters.");

		if( $sale->sent AND
			! (
				$sale->sent == "print" OR
				$sale->sent == "email" OR
				$sale->sent == "both"
			) )
			throw new Exception("Invalid sale sent value: must be 'print', 'email', 'both' or NULL.");

		if( ! $sale->date_created OR 
			! strlen($sale->date_created) )
			throw new Exception("Invalid sale date: none provided.");

		if( $sale->date_created != date("Y-m-d",strtotime($sale->date_created)) )
			throw new Exception("Invalid sale date: must be in YYYY-MM-DD format.");

		if( $this->_check_books_closed($sale->date_created) )
				throw new Exception("Sale could not be created.  The financial year has been closed already.");
		
	}

	protected function _default_form_line()
	{
		$form_line = ORM::Factory('form_line');

		$form_line->form_id = NULL;
		$form_line->account_id = NULL;
		$form_line->description = NULL;
		$form_line->amount = NULL;
		$form_line->quantity = NULL;
		$form_line->total = NULL;

		return $form_line;
	}

	protected function _load_form_line($id)
	{
		return ORM::Factory('form_line',$id);
	}

	protected function _validate_customer_sale_line($sale_line)
	{
		if( get_class($sale_line) != "Model_Form_Line" )
			throw new Exception("Invalid Sale Line.");

		if( ! $sale_line->account_id )
			throw new Exception("Invalid sale line account ID: none provided.");

		$account = $this->_load_account($sale_line->account_id);

		if( ! $account->loaded() )
			throw new Exception("Invalid sale line account ID: account not found.");

		if( ! $account->parent_account_id )
			throw new Exception("Invalid sale line account ID: cannot be a top-level account.");

		if( $account->reserved )
			throw new Exception("Invalid ".$form_type." line account ID: cannot be a reserved account.");

		if( $account->account_type->code != "income" )
			throw new Exception("Invalid sale line account ID: account must be income.");

		if( ! $sale_line->description OR
			! strlen($sale_line->description) )
			throw new Exception("Invalid sale line description: none provided.");

		if( strlen($sale_line->description) > 128 )
			throw new Exception("Invalid sale line description: maximum of 128 characters.");

		if( ! strlen($sale_line->amount) )
			throw new Exception("Invalid sale line amount: none provided.");

		if( ! strlen($sale_line->quantity) )
			throw new Exception("Invalid sale line quantity: none provided.");

		if( $sale_line->quantity <= 0 )
			throw new Exception("Invalid sale line quantity: must be greater than zero.");

	}

	protected function _default_form_line_tax()
	{
		$form_line_tax = ORM::Factory('form_line_tax');

		$form_line_tax->form_line_id = NULL;
		$form_line_tax->tax_id = NULL;
		
		return $form_line_tax;
	}

	protected function _load_form_line_tax($id)
	{
		return ORM::Factory('form_line_tax',$id);
	}

	protected function _validate_customer_sale_line_tax($sale_line_tax)
	{
		if( get_class($sale_line_tax) != "Model_Form_Line_Tax" )
			throw new Exception("Invalid Sale Line Tax.");
		
		if( ! $sale_line_tax->tax_id )
			throw new Exception("Invalid sale line tax tax ID: none provided.");

		$tax = $this->_load_tax($sale_line_tax->tax_id);

		if( ! $tax->loaded() )
			throw new Exception("Invalid sale line tax tax ID: tax not found.");
	}

	protected function _load_tax($id)
	{
		return ORM::Factory('tax',$id);
	}

	protected function _default_form_tax()
	{
		$form_tax = ORM::Factory('form_tax');

		$form_tax->tax_id = NULL;
		$form_tax->form_id = NULL;
		$form_tax->percent = NULL;
		$form_tax->fee = NULL;
		$form_tax->amount = 0.00;
		$form_tax->quantity = 0;
		$form_tax->total = 0.00;

		return $form_tax;
	}

	protected function _tax_adjust_balance($tax_id,$amount)
	{
		DB::query(NULL,'UPDATE taxes SET balance = balance + '.$amount.', total = total + '.$amount.' WHERE id = "'.$tax_id.'"')->execute();
	}
}