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

class Beans_Tax extends Beans {

	/**
	 * Empty constructor to pull in Beans data.
	 * @param stdClass $data
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	/**
	 * Return an array of Tax Elements.
	 * @param  Array $taxes Iterative array of Model_Tax
	 * @return Array        Array of Tax Elements.
	 * @throws Exception If Invalid Tax or Tax Account
	 */
	protected function _return_taxes_array($taxes)
	{
		$return_array = array();

		foreach( $taxes as $tax )
			$return_array[] = $this->_return_tax_element($tax);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Tax
	@description Represents a tax jurisdiction in the system.
	@attribute id INTEGER
	@attribute code STRING
	@attribute name STRING
	@attribute percent DECIMAL In decimal form, i.e. 1.5% = 0.0015
	@attribute percent_formatted DECIMAL In print form, i.e. 1.5% = "1.5"
	@attribute account OBJECT The #Beans_Account# tied to this tax.
	@attribute total DECIMAL The total amount of this tax that has been collected.
	@attribute balance DECIMAL
	@attribute date_due STRING The next payment YYYY-MM-DD due date.
	@attribute date_due_months_increment 
	@attribute license STRING
	@attribute authority STRING
	@attribute address1 STRING The address that payments are remitted to.
	@attribute address2 STRING
	@attribute city STRING 
	@attribute state STRING 
	@attribute zip STRING 
	@attribute country STRING 
	@attribute visible BOOLEAN 
	---BEANSENDSPEC---
	 */

	/**
	 * Return a Tax Element representing the tax attributes.
	 * @param  Model_Tax $tax 
	 * @return stdClass
	 * @throws Exception If Invalid Tax or Tax Account.
	 */
	protected function _return_tax_element($tax)
	{
		$return_object = new stdClass;

		if( get_class($tax) != "Model_Tax" OR 
			! $tax->loaded() )
			throw new Exception("Invalid Tax Object.");

		$return_object->id = $tax->id;
		$return_object->code = $tax->code;
		$return_object->name = $tax->name;
		$return_object->percent = $tax->percent;
		$return_object->percent_formatted = $tax->percent * 100 ;
		$return_object->account = $this->_return_account_element($tax->account);
		$return_object->total = $tax->total;
		$return_object->balance = $tax->balance;
		$return_object->date_due = $tax->date_due;
		$return_object->date_due_months_increment = $tax->date_due_months_increment;
		$return_object->license = $tax->license;
		$return_object->authority = $tax->authority;
		$return_object->address1 = $tax->address1;
		$return_object->address2 = $tax->address2;
		$return_object->city = $tax->city;
		$return_object->state = $tax->state;
		$return_object->zip = $tax->zip;
		$return_object->country = $tax->country;
		$return_object->visible = $tax->visible ? TRUE : FALSE;

		return $return_object;
	}

	/**
	 * Attempts to load and return a tax by ID.
	 * @param  int $id 
	 * @return Model_Tax 
	 */ 
	protected function _load_tax($id)
	{
		return ORM::Factory('tax',$id);
	}

	/**
	 * Returns a default tax object.  Most values will not pass validation.
	 * @return Model_Tax 
	 */
	protected function _default_tax()
	{
		$tax = ORM::Factory('tax');

		$tax->code = NULL;
		$tax->name = NULL;
		$tax->percent = NULL;
		$tax->account_id = NULL;
		$tax->total = 0.00;
		$tax->balance = 0.00;
		$tax->date_due = NULL;
		$tax->date_due_months_increment = NULL;
		$tax->license = NULL;
		$tax->authority = NULL;
		$tax->address1 = NULL;
		$tax->address2 = NULL;
		$tax->city = NULL;
		$tax->state = NULL;
		$tax->zip = NULL;
		$tax->country = NULL;

		return $tax;
	}

	/**
	 * Validates a Model_Tax 
	 * @param  Model_Tax $tax
	 * @return void     
	 * @throws Exception If Invalid Tax Attribute 
	 */	
	protected function _validate_tax($tax)
	{
		if( get_class($tax) != "Model_Tax" )
			throw new Exception("Invalid Tax.");

		if( ! $tax->code OR 
			! strlen($tax->code) )
			throw new Exception("Invalid tax code: none provided.");

		if( strlen($tax->code) > 16 )
			throw new Exception("Invalid tax code: maximum length of 16 characters.");

		if( ! $tax->name OR 
			! strlen($tax->name) )
			throw new Exception("Invalid tax name: none provided.");

		if( strlen($tax->name) > 64 )
			throw new Exception("Invalid tax name: maximum length of 64 characters.");

		if( ! $tax->percent )
			throw new Exception("Invalid tax percent: none provided.");

		if( $tax->percent AND 
			$tax->percent > 1 )
			throw new Exception("Invalid tax percent: must be less than or equal to 1.");

		if( $tax->percent AND
			$tax->percent < 0 )
			throw new Exception("Invalid tax percent: must be greater than 0.");

		if( ! $tax->account_id )
			throw new Exception("Invalid tax account: none provided.");

		$account = $this->_load_account($tax->account_id);

		if( ! $account->loaded() )
			throw new Exception("Invalid tax account: account not found.");

		if( $account->account_type->code != "shorttermdebt" AND 
			$account->account_type->code != "longtermdebt"  )
			throw new Exception("Invalid tax account: must be account type liability ( short or long term debt ).");

		if( ! $tax->date_due )
			throw new Exception("Invalid tax due date: none provided.");

		if( $tax->date_due != date("Y-m-d",strtotime($tax->date_due)) )
			throw new Exception("Invalid tax due date: must be in format YYYY-MM-DD.");

		if( ! $tax->date_due_months_increment ) 
			throw new Exception("Invalid tax due date incremement: none provided.");

		if( $tax->date_due_months_increment > 12 OR 
			$tax->date_due_months_increment < 1 )
			throw new Exception("Invalid tax due date incremement: must be between 1 and 12.");

		if( $tax->license AND 
			strlen($tax->license) > 100 )
			throw new Exception("Invalid tax license number: cannot exceed 100 characters.");
		
		if( ! $tax->authority )
			throw new Exception("Invalid tax authority name: none provided.");

		if( strlen($tax->authority) > 100 )
			throw new Exception("Invalid tax authority name: cannot exceed 100 characters.");

		if( $tax->address1 AND
			strlen($tax->address1) > 128 )
			throw new Exception("Invalid address primary street: maximum of 128 characters.");

		if( $tax->address2 AND
			strlen($tax->address2) > 128 )
			throw new Exception("Invalid address secondary street: maximum of 128 characters.");

		if( $tax->city AND 
			strlen($tax->city) > 64 )
			throw new Exception("Invalid address city: maximum of 64 characters.");

		if( $tax->state AND 
			strlen($tax->state) > 64 )
			throw new Exception("Invalid address state: maximum of 64 characters.");

		if( $tax->zip AND 
			strlen($tax->zip) > 32 )
			throw new Exception("Invalid address postal code: maximum of 32 characters.");

		if( $tax->country AND 
			strlen($tax->country) != 2 )
			throw new Exception("Invalid address country: must be 2 characters.");
	}

	protected function _return_tax_payments_array($tax_payments)
	{
		$return_array = array();

		foreach( $tax_payments as $tax_payment ) 
			$return_array[] = $this->_return_tax_payment_element($tax_payment);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Tax_Payment
	@description Represents a tax payment.
	@attribute id INTEGER
	@attribute tax OBJECT The #Beans_Tax# this payment belongs t.
	@attribute amount DECIMAL The payment amount.
	@attribute writeoff_amount DECIMAL
	@attribute date STRING The payment date.
	@attribute date_start STRING The beginning of the remitted date range.
	@attribute date_end STRING The end of the remitted date range.
	@attribute check_number STRING
	@attribute payment_account OBJECT The #Beans_Account# that payment was remitted from.
	@attribute payment_transaction OBJECT The #Beans_Account_Transaction# representing the transfer from the payment account.
	@attribute writeoff_transaction OBJECT The #Beans_Account_Transaction# represetnging the writeoff transfer.
	@attribute transaction OBJECT The #Beans_Transaction# representing the transfer.
	@attribute invoiced_line_amount DECIMAL The total invoiced sales.
	@attribute invoiced_line_taxable_amount DECIMAL The total taxable invoiced sales.
	@attribute invoiced_amount DECIMAL The total taxes for sales.
	@attribute refunded_line_amount DECIMAL The total invoiced refunds.
	@attribute refunded_line_taxable_amount DECIMAL The total taxable invoiced refunds.
	@attribute refunded_amount DECIMAL The total taxes for sales.
	@attribute net_line_amount DECIMAL The total sales.
	@attribute net_line_taxable_amount DECIMAL The total taxable sales.
	@attribute net_amount DECIMAL The total taxes.
	---BEANSENDSPEC---
	 */

	protected function _return_tax_payment_element($tax_payment)
	{
		$return_object = new stdClass;

		if( get_class($tax_payment) != "Model_Tax_Payment" )
			throw new Exception("Invalid Tax Payment Object.");

		$return_object->id = $tax_payment->id;
		$return_object->tax = $this->_return_tax_element($tax_payment->tax);
		$return_object->amount = $tax_payment->amount;
		$return_object->writeoff_amount = $tax_payment->writeoff_amount;
		$return_object->date = $tax_payment->date;
		$return_object->date_start = $tax_payment->date_start;
		$return_object->date_end = $tax_payment->date_end;
		$return_object->check_number = $tax_payment->transaction->reference;
		
		$return_object->invoiced_line_amount = $tax_payment->invoiced_line_amount;
		$return_object->invoiced_line_taxable_amount = $tax_payment->invoiced_line_taxable_amount;
		$return_object->invoiced_amount = $tax_payment->invoiced_amount;
		$return_object->refunded_line_amount = $tax_payment->refunded_line_amount;
		$return_object->refunded_line_taxable_amount = $tax_payment->refunded_line_taxable_amount;
		$return_object->refunded_amount = $tax_payment->refunded_amount;
		$return_object->net_line_amount = $tax_payment->net_line_amount;
		$return_object->net_line_taxable_amount = $tax_payment->net_line_taxable_amount;
		$return_object->net_amount = $tax_payment->net_amount;
		
		$return_object->payment_account = FALSE;
		$return_object->payment_transaction = FALSE;
		$return_object->writeoff_transaction = FALSE;
		
		if( $return_object->amount == 0 )
		{
			$return_object->transaction = FALSE;
			return $return_object;
		}

		$return_object->transaction = $this->_return_transaction_element($tax_payment->transaction);
		
		foreach( $tax_payment->transaction->account_transactions->find_all() as $account_transaction ) 
			if( isset($account_transaction->transfer) AND 
				$account_transaction->transfer ) 
				$return_object->payment_account = $this->_return_account_element($account_transaction->account);

		if( ! $return_object->payment_account )
			throw new Exception("Invalid payment - no payment account found.");

		foreach( $return_object->transaction->account_transactions as $account_transaction )
		{
			if( $account_transaction->transfer )
				$return_object->payment_transaction = $account_transaction;
			if( $account_transaction->writeoff )
				$return_object->writeoff_transaction = $account_transaction;
		}

		if( ! $return_object->payment_transaction )
			throw new Exception("Invalid payment - no payment transaction account found.");

		$return_object->amount = $return_object->payment_transaction->amount;

		return $return_object;
	}

	protected function _load_tax_payment($id)
	{
		return ORM::Factory('tax_payment',$id);
	}

	
	protected function _default_tax_payment()
	{
		$tax_payment = ORM::Factory('tax_payment');

		$tax_payment->tax_id = NULL;
		$tax_payment->amount = NULL;
		$tax_payment->date = NULL;
		$tax_payment->date_start = NULL;
		$tax_payment->date_end = NULL;
		$tax_payment->transaction_id = NULL;

		return $tax_payment;
	}

	protected function _validate_tax_payment($payment)
	{
		if( get_class($payment) != "Model_Tax_Payment" )
			throw new Exception("Invalid Tax Payment.");

		if( ! $payment->date )
			throw new Exception("Invalid tax payment date: none provided.");

		if( $payment->date != date("Y-m-d",strtotime($payment->date)) )
			throw new Exception("Invalid tax payment date: must be in format YYYY-MM-DD.");

		if( ! $payment->date_start )
			throw new Exception("Invalid tax payment date: none provided.");

		if( $payment->date_start != date("Y-m-d",strtotime($payment->date_start)) )
			throw new Exception("Invalid tax payment date range start: must be in format YYYY-MM-DD.");

		if( ! $payment->date_end )
			throw new Exception("Invalid tax payment date: none provided.");

		if( $payment->date_end != date("Y-m-d",strtotime($payment->date_end)) )
			throw new Exception("Invalid tax payment date range end: must be in format YYYY-MM-DD.");

		// V2Item
		// Consider validating date_end individually

		if( $this->_check_books_closed($payment->date) OR 
			$this->_check_books_closed($payment->date_start) OR	// Assuming you remit taxes before closing books.
			$this->_check_books_closed($payment->date_end) )	
			throw new Exception("Invalid tax payment date: that financial year is already closed.");

		if( ! $payment->tax_id )
			throw new Exception("Invalid tax payment tax ID: none provided.");

		$tax = $this->_load_tax($payment->tax_id);

		if( ! $tax->loaded() )
			throw new Exception("Invalid tax payment tax ID: not found.");
	}
	
	protected function _tax_payment_update_balance($tax_id)
	{

		DB::query(NULL,'UPDATE taxes SET balance = ( SELECT SUM(balance) FROM tax_items WHERE id = "'.$tax_id.'" ) WHERE id = "'.$tax_id.'"')->execute();
	}

	protected function _tax_update_due_date($tax_id)
	{
		$tax = $this->_load_tax($tax_id);

		$last_tax_payment = $tax->tax_payments->order_by('date','DESC')->find();

		if( ! $last_tax_payment->loaded() )
			return;

		if( strtotime($last_tax_payment->date) > strtotime($tax->date_due.' -'.$tax->date_due_months_increment.' Months') AND 
			strtotime($last_tax_payment->date) < strtotime($tax->date_due.' +'.$tax->date_due_months_increment.' Months') )
		{
			$tax->date_due = $this->_date_add_months($tax->date_due,$tax->date_due_months_increment);
			$tax->save();
		}
	}

	// V2Item - Is this a general enough function to roll up to beans.php ?
	private function _date_add_months($date,$months)
	{
		$current_YM = substr($date,0,8).'01';
		$next_YM = date("Y-m",strtotime($current_YM.' +'.$months.' Months'));
		$next_YMD = $next_YM.substr($date,7);

		$days_in_month = date("t",strtotime($next_YM.'-01'));
		if( intval(substr($date,8)) > $days_in_month ) {
			$next_YMD = $next_YM.'-'.$days_in_month;
		}

		return $next_YMD;
	}

	// *** DUPLICATE FUNCTION ***
	/**
	 * Attempts to load an account with the specified ID.
	 * @param  Integer $id
	 * @return Model_Account     The loaded account.
	 */
	protected function _load_account($id)
	{
		return ORM::Factory('account',$id);
	}

	// *** DUPLICATE FUNCTION ***
	/**
	 * Returns an object of the properties for the given Model_Account (ORM)
	 * @param  Model_Account $account Model_Account ORM Object
	 * @return stdClass          stdClass of properties for given Model_Account.
	 * @throws Exception If Model_Account object is not valid.
	 */
	protected function _return_account_element($account)
	{
		$return_object = new stdClass;

		// Verify this model.
		if( ! $account->loaded() OR
			get_class($account) != "Model_Account" )
			throw new Exception("Invalid Account.");

		// Account Details
		$return_object->id = $account->id;
		$return_object->name = $account->name;
		$return_object->code = $account->code;
		$return_object->reconcilable = $account->reconcilable ? TRUE : FALSE;
		$return_object->terms = (int)$account->terms;
		$return_object->balance = (float)$account->balance;
		
		// Account Type
		$return_object->type = $this->_return_account_type_element($account->account_type);
		
		return $return_object;
	}

	// *** DUPLICATE FUNCTION ***
	/**
	 * Returns an object of the properties for the given Model_Account_Type (ORM)
	 * @param  Model_Account_Type $account_type Model_Account_Type ORM Object
	 * @return stdClass               stdClass of the properties for a given Model_Account_Type
	 * @throws Exception If Account Type is not valid.
	 */
	protected function _return_account_type_element($account_type)
	{
		$return_object = new stdClass;

		if( ! $account_type->loaded() )
			return $return_object;

		if( get_class($account_type) != "Model_Account_Type" )
			throw new Exception("Invalid Account Type.");

		$return_object->id = $account_type->id;
		$return_object->name = $account_type->name;
		$return_object->code = $account_type->code;
		$return_object->table_sign = $account_type->table_sign;

		return $return_object;
	}

	// DUPES ! ! !
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

	/**
	 * Attempts to load a transaction with the specific ID.
	 * @param  Integer $id 
	 * @return Model_Transaction The loaded transaction.
	 */
	protected function _load_transaction($id)
	{
		return ORM::Factory('transaction',$id);
	}
	
}