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
class Beans_Vendor extends Beans {

	protected $_auth_role_perm = "vendor_read";

	// The vendor ID assigned to shipping addresses for purchase orders.
	protected $_VENDOR_ADDRESS_SHIPPING_ENTITY_ID = 0;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _return_vendors_array($vendors)
	{
		$return_array = array();

		foreach( $vendors as $vendor )
			$return_array[] = $this->_return_vendor_element($vendor);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor
	@description Represents a vendor in the system.
	@attribute id INTEGER 
	@attribute first_name STRING
	@attribute last_name STRING
	@attribute company_name STRING
	@attribute display_name STRING Company Name if it exists, or else First Last.  Will always be Company Name as current spec requires one for Vendors.
	@attribute email STRING
	@attribute phone_number STRING
	@attribute fax_number STRING
	@attribute default_remit_address_id INTEGER The ID for the default #Beans_Vendor_Address# for paying the vendor.
	@attribute default_account OBJECT The default AR #Beans_Account# for the vendor - Empty if not set.
	@attribute expenses_count INTEGER The total number of expenses attached to this vendor.
	@attribute expenses_total DECIMAL The total value of all expenses attached to this vendor.
	@attribute purchases_count INTEGER The total number of purchases attached to this vendor.
	@attribute purchases_total DECIMAL The total value of all purchases attached to this vendor.
	@attribute total_count INTEGER The total number of expenses and purchases attached to this vendor.
	@attribute total_total DECIMAL The total value of all expenses and purchases attached to this vendor.
	@attribute balance_pending DECIMAL The total balance that currently unpaid.
	@attribute balance_pastdue DECIMAL The total balance that is currently past due.
	---BEANSENDSPEC---
	 */

	private $_return_vendor_element_cache = array();
	protected function _return_vendor_element($vendor)
	{
		$return_object = new stdClass;

		if( get_class($vendor) != "Model_Entity_Vendor" AND
			$vendor->type != "vendor" )
			throw new Exception("Invalid Vendor.");

		if( isset($this->_return_vendor_element_cache[$vendor->id]) )
			return $this->_return_vendor_element_cache[$vendor->id];

		$return_object->id = $vendor->id;
		$return_object->first_name = $vendor->first_name;
		$return_object->last_name = $vendor->last_name;
		$return_object->company_name = $vendor->company_name;
		$return_object->display_name = $return_object->company_name
									 ? $return_object->company_name
									 : $return_object->first_name.' '.$return_object->last_name;
		$return_object->email = $vendor->email;
		$return_object->phone_number = $vendor->phone_number;
		$return_object->fax_number = $vendor->fax_number;

		$return_object->default_remit_address_id = $vendor->default_remit_address_id;
		
		$return_object->default_account = ( $vendor->default_account_id ? $this->_return_account_element($vendor->default_account) : new stdClass );

		// Account Balance AND Past-Due Balance
		$account_balances = DB::query(Database::SELECT, 'SELECT COUNT(id) as count, SUM(total) AS total, SUM(balance) AS balance, IF(date_due < DATE("'.date('Y-m-d').'"),TRUE,FALSE) as past_due, type as form_type FROM forms WHERE entity_id = "'.$vendor->id.'" GROUP BY past_due,form_type')->execute()->as_array();
		
		$return_object->expenses_count = 0;
		$return_object->expenses_total = 0.00;
		$return_object->purchases_count = 0;
		$return_object->purchases_total = 0.00;
		// This name sucks - find something better.
		$return_object->total_count = 0;
		$return_object->total_total = 0.00;
		$return_object->balance_pending = 0.00;
		$return_object->balance_pastdue = 0.00;

		foreach( $account_balances as $account_balance )
		{
			if( $account_balance['form_type'] == "purchase" )
			{
				$return_object->purchases_count += $account_balance['count'];
				$return_object->purchases_total = $this->_beans_round( $return_object->purchases_total + $account_balance['total'] );
			}
			else if( $account_balance['form_type'] == "expense" )
			{
				$return_object->expenses_count += $account_balance['count'];
				$return_object->expenses_total = $this->_beans_round( $return_object->expenses_total + $account_balance['total'] );
			}
			else
			{
				// Somehow got an invoice in here.
			}

			$return_object->total_count += $account_balance['count'];
			$return_object->total_total = $this->_beans_round( $return_object->total_total + $account_balance['total'] );

			if( $account_balance['past_due'] )
				$return_object->balance_pastdue = $this->_beans_round( $return_object->balance_pastdue + $account_balance['balance'] );
			else
				$return_object->balance_pending = $this->_beans_round( $return_object->balance_pending + $account_balance['balance'] );
		}

		$this->_return_vendor_element_cache[$vendor->id] = $return_object;

		return $this->_return_vendor_element_cache[$vendor->id];
	}

	protected function _default_vendor()
	{
		$vendor = ORM::Factory("entity_vendor");

		$vendor->default_remit_address_id = NULL;
		$vendor->first_name = NULL;
		$vendor->last_name = NULL;
		$vendor->company_name = NULL;
		$vendor->email = NULL;
		$vendor->phone_number = NULL;
		$vendor->fax_number = NULL;

		return $vendor;
	}

	protected function _load_vendor($id)
	{
		return ORM::Factory('entity_vendor')->where('type','=','vendor')->where('id','=',$id)->find();
	}

	protected function _validate_vendor($vendor)
	{
		if( get_class($vendor) != "Model_Entity_Vendor" AND
			$vendor->type != "vendor" )
			throw new Exception("Invalid Vendor.");

		if( strlen($vendor->first_name) > 64 )
			throw new Exception("Invalid vendor first name: maximum of 64 characters.");

		if( strlen($vendor->last_name) > 64 )
			throw new Exception("Invalid vendor last name: maximum of 64 characters.");

		if( ! $vendor->company_name OR
			! strlen($vendor->company_name) )
			throw new Exception("Invalid vendor company name: none provided.");

		if( strlen($vendor->company_name) > 64 )
			throw new Exception("Invalid vendor company nane: maximum of 64 characters.");

		if( $vendor->email AND 
			strlen($vendor->email) > 255 )
			throw new Exception("Invalid vendor email: maximum of 254 characters.");

		if( $vendor->email AND
			! filter_var($vendor->email,FILTER_VALIDATE_EMAIL) )
			throw new Exception("Invalid vendor email: invalid email address.");

		if( strlen($vendor->phone_number) > 32 )
			throw new Exception("Invalid vendor phone number: maximum of 32 characters.");

		if( strlen($vendor->fax_number) > 32 )
			throw new Exception("Invalid vendor fax number: maximum of 32 characters.");

		if( $vendor->default_remit_address_id )
		{
			$default_remit_address = $this->_load_vendor_address($vendor->default_remit_address_id);
			if( ! $default_remit_address->loaded() OR
				$default_remit_address->entity_id != $vendor->id )
				throw new Exception("Invalid vendor default billing address: not found.");
		}
		
		if( $vendor->default_account_id )
		{
			$default_account = $this->_load_account($vendor->default_account_id);
			if( ! $default_account->loaded() )
				throw new Exception("Invalid vendor default account payable: not found.");
			if( ! $default_account->payable AND 
				! $default_account->payment )
				throw new Exception("Invalid vendor default account payable: must be payable be payable from.");
		}
	}

	protected function _return_vendor_addresses_array($addresses)
	{
		$return_array = array();

		foreach( $addresses as $address )
			$return_array[] = $this->_return_vendor_address_element($address);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Address
	@description Represents a vendor address.
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
	
	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Address_Shipping
	@description A generic address used for shipping purchases.  These are not tied to a specific vendor, but are all grouped together.  See !Beans_Vendor_Address_Shipping_Create! for an example.
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

	private $_return_vendor_address_element_cache = array();
	protected function _return_vendor_address_element($address)
	{
		$return_object = new stdClass;

		if( get_class($address) != "Model_Entity_Address" )
			throw new Exception("Invalid Address.");

		if( isset($this->_return_vendor_address_element_cache[$address->id]) )
			return $this->_return_vendor_address_element_cache[$address->id];

		$return_object->id = $address->id;
		$return_object->vendor_id = $address->entity_id;
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

		$this->_return_vendor_address_element_cache[$address->id] = $return_object;

		return $this->_return_vendor_address_element_cache[$address->id];
	}

	protected function _load_vendor_address($id)
	{
		return ORM::Factory('entity_address',$id);
	}

	protected function _default_vendor_address()
	{
		$vendor_address = ORM::Factory('entity_address');

		$vendor_address->entity_id = NULL;
		$vendor_address->first_name = NULL;
		$vendor_address->last_name = NULL;
		$vendor_address->company_name = NULL;
		$vendor_address->address1 = NULL;
		$vendor_address->address2 = NULL;
		$vendor_address->city = NULL;
		$vendor_address->state = NULL;
		$vendor_address->zip = NULL;
		$vendor_address->country = NULL;

		return $vendor_address;
	}

	protected function _validate_vendor_address($vendor_address,$ignore_entity = FALSE)
	{
		if( get_class($vendor_address) != "Model_Entity_Address" )
			throw new Exception("Invalid address.");

		if( ! $ignore_entity AND 
			$vendor_address->entity_id != 0 ) 
		{
			if( ! $vendor_address->entity_id OR 
				! strlen($vendor_address->entity_id) )
				throw new Exception("Invalid address vendor ID (entity_id): none provided.");

			if( ! $this->_load_vendor($vendor_address->entity_id)->loaded() )
				throw new Exception("Invalid address vendor ID (entity_id): vendor not found.");
		}
		
		if( $vendor_address->entity_id != 0 ) 
		{
			if( strlen($vendor_address->first_name) > 64 )
				throw new Exception("Invalid address first name: maximum of 64 characters.");

			if( strlen($vendor_address->last_name) > 64 )
				throw new Exception("Invalid address last name: maximum of 64 characters.");

			if( ! $vendor_address->company_name OR
				! strlen($vendor_address->company_name) )
				throw new Exception("Invalid address company name: none provided.");

			if( strlen($vendor_address->company_name) > 64 )
				throw new Exception("Invalid address company name: maximum of 64 characters.");
		}
		else
		{
			if( ! strlen($vendor_address->first_name) AND 
				! strlen($vendor_address->last_name) AND 
				! strlen($vendor_address->company_name) )
				throw new Exception("Invalid address name: Please provide at least one ship-to name.");
		}

		if( ! $vendor_address->address1 OR 
			! strlen($vendor_address->address1) )
			throw new Exception("Invalid address primary street: none provided.");

		if( strlen($vendor_address->address2) > 128 )
			throw new Exception("Invalid address primary street: maximum of 128 characters.");

		if( strlen($vendor_address->address2) > 128 )
			throw new Exception("Invalid address secondary street: maximum of 128 characters.");

		if( ! $vendor_address->city OR
			! strlen($vendor_address->city) )
			throw new Exception("Invalid address city: none provided.");

		if( strlen($vendor_address->city) > 64 )
			throw new Exception("Invalid address city: maximum of 64 characters.");

		if( strlen($vendor_address->state) > 64 )
			throw new Exception("Invalid address state: maximum of 64 characters.");

		if( ! $vendor_address->zip OR 
			! strlen($vendor_address->zip) )
			throw new Exception("Invalid address postal code: none provided.");

		if( strlen($vendor_address->zip) > 32 )
			throw new Exception("Invalid address postal code: maximum of 32 characters.");

		if( ! $vendor_address->country OR 
			! strlen($vendor_address->country) )
			throw new Exception("Invalid address country: none provided.");

		if( strlen($vendor_address->country) != 2 )
			throw new Exception("Invalid address country: must be 2 characters.");
	}
	

	protected function _return_vendor_expenses_array($expenses)
	{
		$return_array = array();
		
		foreach( $expenses as $expense )
			$return_array[] = $this->_return_vendor_expense_element($expense);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Expense
	@description A vendor expense.
	@attribute id INTEGER 
	@attribute vendor OBJECT The #Beans_Vendor# that this expense belongs to.
	@attribute account OBJECT The #Beans_Account# that was used to pay the expense.
	@attribute refund_expense_id INTEGER The ID of the #Beans_Vendor_Expense# that was a refund of ( or refunded ) this.
	@attribute remit_address OBJECT The remit #Beans_Vendor_Address#.
	@attribute date_created STRING 
	@attribute transaction OBJECT The #Beans_Transaction# representing the account in the journal.
	@attribute subtotal DECIMAL
	@attribute total DECIMAL
	@attribute balance DECIMAL 
	@attribute expense_number STRING
	@attribute invoice_number STRING
	@attribute so_number STRING 
	@attribute check_number STRING	
	@attribute lines ARRAY An array of #Beans_Vendor_Expense_Line#.
	---BEANSENDSPEC---
	 */

	private $_return_vendor_expense_element_cache = array();
	protected function _return_vendor_expense_element($expense)
	{
		$return_object = new stdClass;

		if( get_class($expense) != "Model_Form_Expense" AND
			$expense->type != "expense" )
			throw new Exception("Invalid Vendor Expense.");

		if( isset($this->_return_vendor_expense_element_cache[$expense->id]) )
			return $this->_return_vendor_expense_element_cache[$expense->id];

		$return_object->id = $expense->id;
		$return_object->vendor = $this->_return_vendor_element($expense->entity);
		
		// Account Receivable
		$return_object->account = $this->_return_account_element($expense->account);
		
		// Refund
		$return_object->refund_expense_id = ( $expense->refund_form->loaded() )
										   ? $expense->refund_form->id
										   : NULL;

		$return_object->remit_address = ( $expense->remit_address_id )
									  ? $this->_return_vendor_address_element($expense->remit_address)
									  : NULL;

		$return_object->date_created = $expense->date_created;
		// $return_object->date_due = $invoice->date_due;
		
		$return_object->transaction = $this->_return_transaction_element($expense->create_transaction);

		// Technically there's an "amount" field, but it doesn't apply.
		$return_object->subtotal = $expense->amount;
		$return_object->total = $expense->total;		// These are the same for now.
		$return_object->balance = $expense->balance;
		
		$return_object->expense_number = $expense->code;
		$return_object->invoice_number = $expense->reference;
		$return_object->so_number = $expense->alt_reference;

		$return_object->check_number = $expense->create_transaction->reference;

		$return_object->lines = $this->_return_form_lines_array($expense->form_lines->find_all());

		$this->_return_vendor_expense_element_cache[$expense->id] = $return_object;
		return $this->_return_vendor_expense_element_cache[$expense->id];
	}

	protected function _default_vendor_expense()
	{
		$expense = ORM::Factory('form_expense');

		$expense->entity_id = NULL;
		$expense->account_id = NULL;
		$expense->remit_address_id = NULL;
		$expense->total = NULL;
		$expense->balance = 0.00;
		$expense->sent = NULL;
		$expense->date_created = NULL;
		$expense->date_due = NULL;
		$expense->code = NULL;
		$expense->reference = NULL;
		$expense->alt_reference = NULL;
		$expense->create_transaction_id = NULL;
		$expense->invoice_transaction_id = NULL;
		
		return $expense;
	}

	protected function _load_vendor_expense($id)
	{
		$expense = ORM::Factory('form_expense',$id);

		return ( $expense->type == "expense" ? $expense : ORM::Factory('form_expense',-1) );
	}

	protected function _validate_vendor_expense($expense)
	{
		if( get_class($expense) != "Model_Form_Expense" )
			throw new Exception("Invalid Expense.");

		if( ! $expense->entity_id )
			throw new Exception("Invalid expense vendor: none provided.");

		if( ! $this->_load_vendor($expense->entity_id)->loaded() )
			throw new Exception("Invalid expense vendor: vendor not found.");

		if( ! $expense->account_id )
			throw new Exception("Invalid expense account payable: none provided.");

		// Verify Account - Must be valid and marked as "receivable"
		$account = $this->_load_account($expense->account_id);
		
		if( ! $account->loaded() )
			throw new Exception("Invalid expense account payment: account not found.");

		if( ! $account->payment )
			throw new Exception("Invalid expense account payment: account not payment.");

		if( $account->reserved )
			throw new Exception("Invalid sale account receivable: cannot be a reserved account.");

		if( $expense->refund_form_id )
		{
			$refund_expense = $this->_load_vendor_expense($expense->refund_form_id);

			if( ! $refund_expense->loaded() )
				throw new Exception("Invalid expense refund expense ID: expense not found.");

			if( $refund_expense->refund_form->loaded() AND
				$refund_expense->refund_form_id != $expense->id )
				throw new Exception("Invalid expense refund expense ID: expense already has refund.");
		}

		if( $expense->remit_address_id )
		{
			$remit_address = $this->_load_vendor_address($expense->remit_address_id);

			if( ! $remit_address->loaded() )
				throw new Exception("Invalid expense remit address: address not found.");

			if( $remit_address->entity_id != $expense->entity_id )
				throw new Exception("Invalid expense remit address: does not belong to vendor.");
		}

		if( ! $expense->code OR 
			! strlen($expense->code) )
			throw new Exception("Invalid expense number: none provided.");
		
		if( strlen($expense->code) > 16 )
			throw new Exception("Invalid expense number: maximum of 16 characters.");

		if( (
				$expense->reference OR
				strlen($expense->reference)
			) AND 
			strlen($expense->reference) > 16 )
			throw new Exception("Invalid expense purchase order number: maximum of 16 characters.");

		if( (
				$expense->alt_reference OR
				strlen($expense->alt_reference)
			) AND 
			strlen($expense->alt_reference) > 16 )
			throw new Exception("Invalid expense purchase order number: maximum of 16 characters.");

		if( ! $expense->date_created OR 
			! strlen($expense->date_created) )
			throw new Exception("Invalid expense date: none provided.");

		if( $expense->date_created != date("Y-m-d",strtotime($expense->date_created)) )
			throw new Exception("Invalid expense date: must be in YYYY-MM-DD format.");

		if( $this->_check_books_closed($expense->date_created) )
			throw new Exception("Expense could not be created.  The financial year has been closed already.");

	}
	
	protected function _return_vendor_purchases_array($purchases)
	{
		$return_array = array();

		foreach( $purchases as $purchase )
			$return_array[] = $this->_return_vendor_purchase_element($purchase);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Purchase
	@description A vendor purchase order.
	@attribute id INTEGER 
	@attribute vendor OBJECT The #Beans_Vendor# that this expense belongs to.
	@attribute account OBJECT The #Beans_Account# that was used to pay the expense.
	@attribute sent STRING The sent status of the purchase: 'email', 'print', 'both', or NULL
	@attribute refund_purchase_id INTEGER The ID of the #Beans_Vendor_Expense# that was a refund of ( or refunded ) this.
	@attribute remit_address OBJECT The remit #Beans_Vendor_Address#.
	@attribute shipping_address OBJECT The shipping #Beans_Vendor_Address_Shipping#.
	@attribute date_created STRING 
	@attribute create_transaction_id INTEGER The ID of the #Beans_Transaction# to create the purchase.
	@attribute invoice_transaction_id INTEGER The ID of the #Beans_Transaction# converting it to an invoice.
	@attribute subtotal DECIMAL
	@attribute total DECIMAL
	@attribute balance DECIMAL
	@attribute purchase_number STRING
	@attribute quote_number STRING
	@attribute so_number STRING
	@attribute invoice_number STRING
	@attribute lines ARRAY An array of #Beans_Vendor_Purchase_Line#.
	@attribute payments ARRAY An array of #Beans_Vendor_Payment# tied to this purchase.
	@attribute status STRING 
	---BEANSENDSPEC---
	 */

	private $_return_vendor_purchase_element_cache = array();
	protected function _return_vendor_purchase_element($purchase)
	{
		$return_object = new stdClass;

		if( get_class($purchase) != "Model_Form_Purchase" AND 
			$purchase->type != "purchase" )
			throw new Exception("Invalid Purchase.");

		if( isset($this->_return_vendor_purchase_element_cache[$purchase->id]) )
			return $this->_return_vendor_purchase_element_cache[$purchase->id];

		$return_object->id = $purchase->id;
		$return_object->vendor = $this->_return_vendor_element($purchase->entity);
		
		// Account Payable
		$return_object->account = $this->_return_account_element($purchase->account);
		
		$return_object->sent = ( $purchase->sent AND strlen($purchase->sent) )
							 ? $purchase->sent
							 : FALSE;
		
		// Refund
		$return_object->refund_purchase_id = ( $purchase->refund_form->loaded() )
										   ? $purchase->refund_form->id
										   : NULL;

		$return_object->date_cancelled = $purchase->date_cancelled;
		$return_object->date_created = $purchase->date_created;
		$return_object->date_billed = $purchase->date_billed;
		$return_object->date_due = $purchase->date_due;

		$return_object->create_transaction_id = $purchase->create_transaction_id;
		$return_object->invoice_transaction_id = $purchase->invoice_transaction_id;
		
		$return_object->subtotal = $purchase->amount;
		$return_object->total = $purchase->total;		
		$return_object->balance = $purchase->balance;
		
		$return_object->purchase_number = $purchase->code;
		$return_object->quote_number = $purchase->alt_reference;
		$return_object->so_number = $purchase->reference;
		$return_object->invoice_number = $purchase->aux_reference;

		$return_object->remit_address = ( $purchase->remit_address_id )
									  ? $this->_return_vendor_address_element($purchase->remit_address)
									  : NULL;

		$return_object->shipping_address = ( $purchase->shipping_address_id )
										 ? $this->_return_vendor_address_element($purchase->shipping_address)
										 : NULL;
		
		$return_object->lines = $this->_return_form_lines_array($purchase->form_lines->find_all());

		$return_object->payments = $this->_return_vendor_purchase_payments_array($purchase->account_transaction_forms->find_all());

		$return_object->status = $this->_vendor_purchase_status($return_object);

		$this->_return_vendor_purchase_element_cache[$purchase->id] = $return_object;
		return $this->_return_vendor_purchase_element_cache[$purchase->id];
	}

	protected function _return_vendor_purchase_payments_array($account_transaction_forms)
	{
		$return_array = array();

		// Cycle all forms - return for payment transactions.
		foreach( $account_transaction_forms as $account_transaction_form )
			if( $account_transaction_form->account_transaction->transaction->payment )
				$return_array[] = $this->_return_vendor_purchase_payment_element($account_transaction_form);

		return $return_array;
	}

	protected function _return_vendor_purchase_payment_element($account_transaction_form)
	{
		$return_object = new stdClass;

		$return_object->id = $account_transaction_form->account_transaction->transaction->id;
		$return_object->date = $account_transaction_form->account_transaction->transaction->date;
		$return_object->amount = $account_transaction_form->amount;

		return $return_object;
	}

	protected function _vendor_purchase_status($purchase)
	{

		if( $purchase->date_cancelled AND
			$purchase->balance != 0 )
			return "Cancelled: Refund Pending";
		if( $purchase->date_cancelled )
			return "Cancelled";
		if( $purchase->refund_purchase_id AND
			$purchase->balance != 0 )
			return "Refund Pending";
		if( $purchase->refund_purchase_id )
			return "Refunded";
		if( $purchase->balance == 0 )
			return "Paid";
		if( ! $purchase->sent AND 
			! $purchase->date_billed )
			return "PO Not Sent";
		if( $purchase->date_billed )
			return "Due ".$purchase->date_due;
		return "PO Sent";
	}

	protected function _default_vendor_purchase()
	{
		$purchase = ORM::Factory('form_purchase');

		$purchase->entity_id = NULL;
		$purchase->account_id = NULL;
		$purchase->total = NULL;
		$purchase->balance = 0.00;
		$purchase->shipping_address_id = NULL;
		$purchase->remit_address_id = NULL;
		$purchase->sent = NULL;
		$purchase->date_created = NULL;
		$purchase->date_due = NULL;
		$purchase->code = NULL;
		$purchase->reference = NULL;
		$purchase->alt_reference = NULL;
		$purchase->aux_reference = NULL;
		$purchase->create_transaction_id = NULL;
		$purchase->invoice_transaction_id = NULL;

		return $purchase;
	}

	protected function _load_vendor_purchase($id)
	{
		$purchase = ORM::Factory('form_purchase',$id);

		return ( $purchase->type == "purchase" ? $purchase : ORM::Factory('form_purchase',-1) );
	}

	protected function _validate_vendor_purchase($purchase)
	{
		if( get_class($purchase) != "Model_Form_Purchase" AND 
			$purchase->type != "purchase" )
			throw new Exception("Invalid Purchase.");

		if( ! $purchase->entity_id )
			throw new Exception("Invalid purchase vendor: none provided.");

		if( ! $this->_load_vendor($purchase->entity_id)->loaded() )
			throw new Exception("Invalid purchase vendor: vendor not found.");

		if( ! $purchase->account_id )
			throw new Exception("Invalid purchase account payable: none provided.");

		// Verify Account - Must be valid and marked as "receivable"
		$account = $this->_load_account($purchase->account_id);
		
		if( ! $account->loaded() )
			throw new Exception("Invalid purchase account payable: account not found.");

		if( ! $account->payable )
			throw new Exception("Invalid purchase account payable: account not payable.");

		if( $account->reserved )
			throw new Exception("Invalid sale account receivable: cannot be a reserved account.");

		if( $purchase->refund_form_id )
		{
			$refund_purchase = $this->_load_vendor_purchase($purchase->refund_form_id);

			if( ! $refund_purchase->loaded() )
				throw new Exception("Invalid purchase refund purchase ID: purchase not found.");

			if( $refund_purchase->refund_form->loaded() AND
				$refund_purchase->refund_form_id != $purchase->id )
				throw new Exception("Invalid purchase refund purchase ID: purchase already has refund.");
		}

		if( $purchase->remit_address_id )
		{
			$remit_address = $this->_load_vendor_address($purchase->remit_address_id);

			if( ! $remit_address->loaded() )
				throw new Exception("Invalid purchase remit address: address not found.");

			if( $remit_address->entity_id != $purchase->entity_id )
				throw new Exception("Invalid purchase remit address: does not belong to vendor.");
		}

		if( $purchase->shipping_address_id )
		{
			$shipping_address = $this->_load_vendor_address($purchase->shipping_address_id);

			if( ! $shipping_address->loaded() )
				throw new Exception("Invalid purchase shipping address: address not found.");

			if( $shipping_address->entity_id != $this->_VENDOR_ADDRESS_SHIPPING_ENTITY_ID )
				throw new Exception("Invalid purchase shipping address: does not belong to appropriate vendor.");
		}

		if( ! $purchase->code OR 
			! strlen($purchase->code) )
			throw new Exception("Invalid purchase invoice number: none provided.");

		if( strlen($purchase->code) > 16 )
			throw new Exception("Invalid purchase invoice number: maximum of 16 characters.");

		if( (
				$purchase->reference OR
				strlen($purchase->reference)
			) AND 
			strlen($purchase->reference) > 16 )
			throw new Exception("Invalid purchase quote number: maximum of 16 characters.");

		if( (
				$purchase->alt_reference OR
				strlen($purchase->alt_reference)
			) AND 
			strlen($purchase->alt_reference) > 16 )
			throw new Exception("Invalid purchase SO number: maximum of 16 characters.");

		if( (
				$purchase->aux_reference OR
				strlen($purchase->aux_reference) 
			) AND
			strlen($purchase->aux_reference) > 16 )
			throw new Exception("Invalid purchase vendor invoice number: maximum of 16 characters.");

		if( ! $purchase->date_created OR 
			! strlen($purchase->date_created) )
			throw new Exception("Invalid purchase date: none provided.");

		if( $purchase->date_created != date("Y-m-d",strtotime($purchase->date_created)) )
			throw new Exception("Invalid purchase date: must be in YYYY-MM-DD format.");

		if( $this->_check_books_closed($purchase->date_created) )
			throw new Exception("Purchase could not be created.  The financial year has been closed already.");

	}

	protected function _return_vendor_payments_array($payments)
	{
		$return_array = array();

		foreach( $payments as $payment )
			$return_array[] = $this->_return_vendor_payment_element($payment);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Payment
	@description A payment for one or more vendor purchases.
	@attribute id INTEGER 
	@attribute number STRING A reference number
	@attribute check_number STRING 
	@attribute description STRING
	@attribute date STRING
	@attribute amount DECIMAL Total received.
	@attribute vendor OBJECT The #Beans_Vendor# this payment was remitted to.
	@attribute account_transactions ARRAY An array of the #Beans_Account_Transaction# that make up this transaction.
	@attribute purchase_payments ARRAY An array of #Beans_Vendor_Payment_Purchase# representing the amounts paid on each sale.
	@attribute payment_transaction OBJECT The #Beans_Transaction# representing the split going out of a cash account.
	@attribute writeoff_transaction OBJECT The #Beans_Transaction# representing the writeoff transaction if it exists.
	@attribute reconciled BOOLEAN Whether one or more of the account transactions was reconciled.
	---BEANSENDSPEC---
	 */

	private $_return_vendor_payment_element_cache = array();
	protected function _return_vendor_payment_element($payment)
	{
		$return_object = new stdClass;

		if( ! $payment->loaded() OR
			get_class($payment) != "Model_Transaction" OR 
			$payment->payment != "vendor" )
			throw new Exception("Invalid Vendor Purchase Order Payment.");

		if( isset($this->_return_vendor_payment_element_cache[$payment->id]) )
			return $this->_return_vendor_payment_element_cache[$payment->id];

		$return_object->id = $payment->id;
		$return_object->number = $payment->code;
		$return_object->description = $payment->description;
		$return_object->date = $payment->date;
		$return_object->payment = $payment->payment;	// Somewhat redundant.
		//$return_object->amount = ( $payment->amount );	// REVERSED - THIS OK ? // Flip the sign - keep it straightforward and behind the scenes.
		$return_object->amount = 0.00;
		$return_object->check_number = $payment->reference;
		
		// Get the vendor for this payment.
		$vendor_ids = DB::query(Database::SELECT, 'SELECT DISTINCT(forms.entity_id) as vendor_id FROM account_transactions RIGHT JOIN account_transaction_forms ON account_transactions.id = account_transaction_forms.account_transaction_id RIGHT JOIN forms ON account_transaction_forms.form_id = forms.id WHERE account_transactions.transaction_id = "'.$payment->id.'"')->execute()->as_array();

		if( count($vendor_ids) != 1 )
			throw new Exception("Invalid Vendor Purchase Order Payment: More than one vendor found!");

		$return_object->vendor = $this->_return_vendor_element($this->_load_vendor($vendor_ids[0]['vendor_id']));

		// This _return_account_transactions_array is different from Beans_Account->_return_account_transactions_array()
		// Does not dump full form information.
		// see below: _return_account_transaction_element()
		$return_object->account_transactions = $this->_return_account_transactions_array($payment->account_transactions->find_all());
		
		$return_object->purchase_payments = $this->_return_vendor_payment_purchases_array($payment->account_transactions->find_all());

		foreach( $return_object->purchase_payments as $purchase_payment )
		{
			if( ! isset($return_object->remit_address) )
				$return_object->remit_address = $purchase_payment->purchase->remit_address;
		}
		
		$return_object->payment_transaction = FALSE;
		$return_object->writeoff_transaction = FALSE;
		
		foreach( $return_object->account_transactions as $account_transaction )
		{
			if( isset($account_transaction->transfer) AND 
				$account_transaction->transfer ) 
			{
				$return_object->payment_transaction = $account_transaction;
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
			}
		}
		
		$return_object->amount = $return_object->amount * -1;

		if( ! $return_object->payment_transaction )
			throw new Exception("Invalid payment - no deposit account found.");

		$return_object->reconciled = FALSE;
		
		// V2Item
		// Might want to replace this with a reconciled flag on the transaction model.
		$i = 0;
		while( 	! $return_object->reconciled AND $i < count($return_object->account_transactions) )
			if( $return_object->account_transactions[$i++]->reconciled )
				$return_object->reconciled = TRUE;
		
		$this->_return_vendor_payment_element_cache[$payment->id] = $return_object;
		return $this->_return_vendor_payment_element_cache[$payment->id];
	}

	protected function _default_vendor_payment()
	{
		$payment = ORM::Factory('transaction');

		$payment->code = NULL;
		$payment->description = NULL;
		$payment->date = NULL;
		$payment->payment = "vendor";
		
		return $payment;
	}

	protected function _load_vendor_payment($id)
	{
		return ORM::Factory('transaction',$id);
	}

	// Necessary ?  Check transaction validation, etc.
	protected function _validate_vendor_payment($payment)
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

		if( $payment->reference AND 
			strlen($payment->code) > 16 )
			throw new Exception("Invalid check number: maximum length of 16 characters.");

		if( $payment->description AND 
			strlen($payment->description) > 128 )
			throw new Exception("Invalid payment description: maximum length of 128 characters.");
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
	@object Beans_Vendor_Expense_Line
	@description A vendor expense line item.
	@attribute id INTEGER 
	@attribute account OBJECT The #Beans_Account# to categorize the cost.
	@attribute description STRING 
	@attribute amount DECIMAL
	@attribute quantity INTEGER Must be >= 0
	@attribute total DECIMAL The total from the amount and quantity.
	---BEANSENDSPEC---
	 */
	
	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Purchase_Line
	@description A vendor purchase line item.
	@attribute id INTEGER 
	@attribute account OBJECT The #Beans_Account# to categorize the cost.
	@attribute description STRING 
	@attribute amount DECIMAL
	@attribute quantity INTEGER Must be >= 0
	@attribute total DECIMAL The total from the amount and quantity.
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

		// *** FAT ***
		$return_object->account = $this->_return_account_element($line->account);

		$return_object->description = $line->description;
		$return_object->amount = $line->amount;
		$return_object->quantity = $line->quantity;
		$return_object->total = $line->total;
		
		$this->_return_form_line_element[$line->id] = $return_object;
		return $this->_return_form_line_element[$line->id];
	}

	protected function _load_form_line($id)
	{
		return ORM::Factory('form_line',$id);
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

	protected function _validate_form_line($form_line,$form_type = NULL)
	{
		if( get_class($form_line) != "Model_Form_Line" )
			throw new Exception("Invalid ".ucwords($form_type)." Line.");

		if( ! $form_line->account_id )
			throw new Exception("Invalid ".$form_type." line account ID: none provided.");

		$account = $this->_load_account($form_line->account_id);

		if( ! $account->loaded() )
			throw new Exception("Invalid ".$form_type." line account ID: account not found.");

		if( ! $account->parent_account_id )
			throw new Exception("Invalid ".$form_type." line account ID: cannot be a top-level account.");

		if( $account->reserved )
			throw new Exception("Invalid ".$form_type." line account ID: cannot be a reserved account.");

		// This cannot really happen - written here with the goal of moving up to Class Beans (beans.php) possibly.
		if( $form_type == "invoice" )
		{	
			if( $account->account_type->code != "income" )
				throw new Exception("Invalid ".$form_type." line account ID: account must be income.");
		}
		else if( $form_type == "expense" ) {
			if( $account->account_type->code == "income" )
				throw new Exception("Invalid ".$form_type." line account ID: account cannot be income.");
		}
		else if( $form_type == "purchase" )
		{
			if( $account->account_type->code == "accountsreceivable" OR
				$account->account_type->code == "bankaccount" OR
				$account->account_type->code == "longtermdebt" OR
				$account->account_type->code == "shorttermdebt" OR
				$account->account_type->code == "accountspayable" OR
				$account->account_type->code == "equity" OR
				$account->account_type->code == "income" )
				throw new Exception("Invalid ".$form_type." line account ID: must be fixed asset, expense, or cost of goods sold.");
		}
		else
		{
			throw new Exception("Unexpected error: invalid form type.");
		}

		if( $form_line->adjustment AND 
			! $account->writeoff )
			throw new Exception("Invalid account for adjustment entry: must be a writeoff account.");

		if( ! $form_line->description OR
			! strlen($form_line->description) )
			throw new Exception("Invalid ".$form_type." line description: none provided.");

		if( strlen($form_line->description) > 128 )
			throw new Exception("Invalid ".$form_type." line description: maximum of 128 characters.");

		if( ! strlen($form_line->amount) )
			throw new Exception("Invalid ".$form_type." line amount: none provided.");

		if( ! strlen($form_line->quantity) )
			throw new Exception("Invalid ".$form_type." line quantity: none provided.");

		if( $form_line->quantity <= 0 )
			throw new Exception("Invalid ".$form_type." line quantity: must be greater than zero.");
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

		if( isset($this->_return_account_element[$account->id]) )
			return $this->_return_account_element[$account->id];

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
		
		$this->_return_account_element[$account->id] = $return_object;
		return $this->_return_account_element[$account->id];
	}


	// *** DUPLICATE FUNCTION *** 
	private $_return_transaction_element_cache = array();
	protected function _return_transaction_element($transaction)
	{
		$return_object = new stdClass;
		
		if( ! $transaction->loaded() OR
			get_class($transaction) != "Model_Transaction" )
			throw new Exception("Invalid Transaction.");

		if( isset($this->_return_transaction_element_cache[$transaction->id]) )
			return $this->_return_transaction_element_cache[$transaction->id];

		$return_object->id = $transaction->id;
		$return_object->code = $transaction->code;
		$return_object->description = $transaction->description;
		$return_object->date = $transaction->date;
		$return_object->amount = $transaction->amount;
		$return_object->payment = ( $transaction->payment )
								? $transaction->payment
								: FALSE;

		$return_object->account_transactions = $this->_return_account_transactions_array($transaction->account_transactions->find_all());

		$return_object->reconciled = FALSE;
		
		// V2Item
		// Might want to replace this with a reconciled flag on the transaction model.
		$i = 0;
		while( 	! $return_object->reconciled AND $i < count($return_object->account_transactions) )
			if( $return_object->account_transactions[$i++]->reconciled )
				$return_object->reconciled = TRUE;

		$this->_return_transaction_element_cache[$transaction->id] = $return_object;
		return $this->_return_transaction_element_cache[$transaction->id];
	}

	// *** DUPLICATE FUNCTION ***
	protected function _load_transaction($id)
	{
		return ORM::Factory('transaction',$id);
	}

	// *** DUPLICATE FUNCTION ***
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

	protected function _return_vendor_payment_purchases_array($account_transactions)
	{
		$return_array = array();

		foreach( $account_transactions as $account_transaction ) 
			if( ! $account_transaction->transfer AND 
				! $account_transaction->writeoff )
				foreach( $account_transaction->account_transaction_forms->find_all() as $account_transaction_form ) 
					$return_array[$account_transaction_form->form_id] = $this->_return_vendor_payment_purchase_element($account_transaction_form);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Vendor_Payment_Purchase
	@description A payment on a vendor purchase.
	@attribute id INTEGER 
	@attribute purchase OBJECT The #Beans_Vendor_Purchase# this credit/debit applies to.
	@attribute amount DECIMAL The amount actually paid to the vendor.
	@attribute writeoff_amount DECIMAL The writeoff amount ( if any ).
	---BEANSENDSPEC---
	 */

	private $_return_vendor_payment_purchase_element_cache = array();
	protected function _return_vendor_payment_purchase_element($account_transaction_form)
	{
		$return_object = new stdClass;

		if( get_class($account_transaction_form) != "Model_Account_Transaction_Form" )
			throw new Exception("Invalid Transaction Invoice.");

		if( isset($this->_return_vendor_payment_purchase_element_cache[$account_transaction_form->id]) )
			return $this->_return_vendor_payment_purchase_element_cache[$account_transaction_form->id];

		$return_object->id = $account_transaction_form->id;
		$return_object->purchase = $this->_return_vendor_purchase_element($account_transaction_form->form);
		$return_object->amount = ( -1 * $account_transaction_form->amount) - ( -1 * $account_transaction_form->writeoff_amount );
		$return_object->writeoff_amount = ( -1 * $account_transaction_form->writeoff_amount );

		$this->_return_vendor_payment_purchase_element_cache[$account_transaction_form->id] = $return_object;
		return $this->_return_vendor_payment_purchase_element_cache[$account_transaction_form->id];
	}

	// *** DUPLICATE FUNCTIONS ***
	protected function _load_account($id)
	{
		return ORM::Factory('account',$id);
	}

	protected function _load_account_type($id)
	{
		return ORM::Factory('account_type',$id);
	}

	// *** DUPLICATE FUNCTION ***
	private $_return_account_type_element_cache = array();
	protected function _return_account_type_element($account_type)
	{
		$return_object = new stdClass;

		if( ! $account_type->loaded() )
			return $return_object;

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
	
}