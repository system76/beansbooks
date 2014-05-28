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
class Beans_Account extends Beans {

	protected $_auth_role_perm = "account_read";

	/**
	 * Empty constructor to pull in Beans data.
	 * @param stdClass $data
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	/**
	 * Returns an array of Account Elements formatted for a return object.
	 * @param  Array $accounts An iterative array of Model_Account (ORM)
	 * @return Array           Array of Account Elements (Array)
	 * @throws Exception If Account object is not valid.
	 */
	protected function _return_accounts_array($accounts)
	{
		$return_array = array();
		
		foreach( $accounts as $account )
			$return_array[] = $this->_return_account_element($account);
		
		return $return_array;
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

	/**
	 * Attempts to load an account transaction with the specified ID.
	 * This probably should not be used.
	 * @param  Integer $id
	 * @return Model_Account_Transaction     The loaded account transaction.
	 */
	protected function _load_account_transaction($id)
	{
		return ORM::Factory('account_transaction',$id);
	}
	
	/*
	---BEANSOBJSPEC---
	@object Beans_Account
	@description Represents an account from the chart of accounts.
	@attribute id INTEGER 
	@attribute parent_account_id INTEGER representing the ID of the parent #Beans_Account#.
	@attribute reserved BOOLEAN whether or not this is a system-only account.
	@attribute name STRING 
	@attribute code STRING 
	@attribute reconcilable BOOLEAN 
	@attribute terms INTEGER Number of days that forms attached to this account are due on.
	@attribute balance DECIMAL The current account balance.
	@attribute deposit BOOLEAN Payments can be received to this account.
	@attribute payment BOOLEAN Payments on Purchases can 
	@attribute receivable BOOLEAN Customers Sales can be recorded to this account.
	@attribute payable BOOLEAN Vendor Purchases can be recorded to this account.
	@attribute writeoff BOOLEAN Writeoffs can be put into this account.
	@attribute type The #Beans_Account_Type# for this account.
	---BEANSENDSPEC---
	 */

	/**
	 * Returns an object of the properties for the given Model_Account (ORM)
	 * @param  Model_Account $account Model_Account ORM Object
	 * @return stdClass          stdClass of properties for given Model_Account.
	 * @throws Exception If Model_Account object is not valid.
	 */
	private $_return_account_element_cache = array();
	protected function _return_account_element($account)
	{
		$return_object = new stdClass;

		// Verify this model.
		if( ! $account->loaded() OR
			get_class($account) != "Model_Account" )
			throw new Exception("Invalid Account.");

		if( isset($this->_return_account_element_cache[$account->id]) )
			return $this->_return_account_element_cache[$account->id];

		// Account Details
		$return_object->id = $account->id;
		$return_object->parent_account_id = $account->parent_account_id;
		$return_object->reserved = $account->reserved ? TRUE : FALSE;
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

	/**
	 * Returns an array of Account Type Elements.
	 * @param  Array $account_types Iterative array of Model_Account_Type
	 * @return Array                Array of Account Type Elements.
	 * @throws Exception If Account Type is not valid.
	 */
	protected function _return_account_types_array($account_types)
	{
		$return_array = array();
		foreach( $account_types as $account_type )
		{
			$return_array[] = $this->_return_account_type_element($account_type);
		}

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Account_Type
	@description A type of account.
	@attribute id INTEGER 
	@attribute name STRING 
	@attribute code STRING A code for quick lookup and reference.
	@attribute type STRING A short code to denote a broader type.
	@attribute table_sign INTEGER Either +1 or -1 to denote double entry account sign. 
	@attribute deposit BOOLEAN Payments can be received to this account.
	@attribute payment BOOLEAN Payments on Purchases can 
	@attribute receivable BOOLEAN Customers Sales can be recorded to this account.
	@attribute payable BOOLEAN Vendor Purchases can be recorded to this account.
	@attribute reconcilable BOOLEAN Account can be reconciled.
	---BEANSENDSPEC---
	 */

	/**
	 * Returns an object of the properties for the given Model_Account_Type (ORM)
	 * @param  Model_Account_Type $account_type Model_Account_Type ORM Object
	 * @return stdClass               stdClass of the properties for a given Model_Account_Type
	 * @throws Exception If Account Type is not valid.
	 */
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
		$return_object->type = $account_type->type;
		$return_object->table_sign = $account_type->table_sign;
		$return_object->deposit = $account_type->deposit;
		$return_object->payment = $account_type->payment;
		$return_object->receivable = $account_type->receivable;
		$return_object->payable = $account_type->payable;
		$return_object->reconcilable = $account_type->reconcilable;

		$this->_return_account_type_element_cache[$account_type->id] = $return_object;
		return $this->_return_account_type_element_cache[$account_type->id];
	}

	/**
	 * Returns an array of Account Transaction Elements formatted for a return object.
	 * @param  Array $account_transactions An iterative array of Model_Account_Transaction (ORM)
	 * @return Array                       Array of Account Transaction Elements
	 * @throws Exception If Model_Account_Transaction object is not valid.
	 */
	protected function _return_account_transactions_array($account_transactions)
	{
		$return_array = array();
		foreach( $account_transactions as $account_transaction )
		{
			$return_array[] = $this->_return_account_transaction_element($account_transaction);
		}

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Account_Transaction
	@description A single split on a #Beans_Transaction# - or a single line entry for a #Beans_Account# journal.
	@attribute id INTEGER 
	@attribute amount DECIMAL The transaction amount. 
	@attribute balance DECIMAL The account balance after this transaction. 
	@attribute reconciled BOOLEAN 
	@attribute account OBJECT The #Beans_Account# this transaction is tied to. 
	---BEANSENDSPEC---
	 */

	// Removed & Deprecated
	// @attribute account_transaction_forms ARRAY An array of #Beans_Account_Transaction_Form# delineating how this transaction affects form balances. 

	/**
	 * Returns an object of the properties for the given Model_Account_Transaction (ORM)
	 * @param  Model_Account_Transaction $account_transaction Model_Account_Transaction ORM Object
	 * @return stdClass                      stdClass of properties for given ModeL_Account_Transaction
	 * @throws Exception If Model_Account_Transaction object is not valid.
	 */
	private $_return_account_transaction_element_cache = array();
	protected function _return_account_transaction_element($account_transaction)
	{
		$return_object = new stdClass;

		if( ! $account_transaction->loaded() OR
			get_class($account_transaction) != "Model_Account_Transaction" )
			throw new Exception("Invalid Account Transaction.");

		if( isset($this->_return_account_transaction_element_cache[$account_transaction->id]) )
			return $this->_return_account_transaction_element_cache[$account_transaction->id];

		// Account Transaction Details
		$return_object->id = $account_transaction->id;
		$return_object->amount = (float)$account_transaction->amount;
		$return_object->balance = (float)$account_transaction->balance;
		$return_object->reconciled = $account_transaction->account_reconcile_id ? TRUE : FALSE;

		// Reference IDs
		// REMOVED - Greatly improved query time 
		// $return_object->_return_account_transaction_forms_array = $this->_return_account_transaction_forms_array($account_transaction->account_transaction_forms->find_all());
		
		// OBJECT?
		// *** FAT ***
		$return_object->account = $this->_return_account_element($account_transaction->account);
		
		$this->_return_account_transaction_element_cache[$account_transaction->id] = $return_object;
		return $this->_return_account_transaction_element_cache[$account_transaction->id];
	}

	/**
	 * Return an array of Account Transaction Forms
	 * @param  Array $account_transaction_forms Iterative array of Model_Account_Transaction_Form
	 * @return Array                            Array of Objects representing Account Transaction Form Elements.
	 * @throws Exception If Invalid Account Transaction Form Object
	 */
	protected function _return_account_transaction_forms_array($account_transaction_forms)
	{
		$return_array = array();

		foreach( $account_transaction_forms as $account_transaction_form )
			$return_array[] = $this->_return_account_transaction_form_element($account_transaction_form);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Account_Transaction_Form
	@description A balance adjustment on a form from an account transaction.
	@attribute id INTEGER 
	@attribute account_transaction_id INTEGER The ID of the #Beans_Account_Transaction# this is tied to. 
	@attribute form_id INTEGER The ID of the form that his is tied to.  Could be a #Beans_Customer_Sale#, #Beans_Vendor_Expense#, or #Beans_Vendor_Purchase#.
	@attribute amount DECIMAL The amount that is applied to this particular form. 
	---BEANSENDSPEC---
	 */


	/**
	 * Return an Account Transaction Form Element object.
	 * @param  Model_Account_Transaction_Form $account_transaction_form 
	 * @return stdClass                           Object representing the properties for the Model_Account_Transaction_Form
	 * @throws Exception If Invalid Account Transaction Form Object
	 */
	private $_return_account_transaction_form_element_cache = array();
	protected function _return_account_transaction_form_element($account_transaction_form)
	{
		$return_object = new stdClass;

		if( get_class($account_transaction_form) != "Model_Account_Transaction_Form" )
			throw new Exception("Invalid Account Transaction Form.");

		if( isset($this->_return_account_transaction_form_element_cache[$account_transaction_form->id]) )
			return $this->_return_account_transaction_form_element_cache[$account_transaction_form->id];

		// Don't link to objects - just reference by ID.
		$return_object->id = $account_transaction_form->id;
		$return_object->account_transaction_id = $account_transaction_form->account_transaction_id;
		$return_object->form_id = $account_transaction_form->form_id;
		$return_object->amount = $account_transaction_form->amount;

		$this->_return_account_transaction_form_element_cache[$account_transaction_form->id] = $return_object;
		return $this->_return_account_transaction_form_element_cache[$account_transaction_form->id];
	}

	/**
	 * Returns an array of Transaction Elements formatted for a return object.
	 * @param  Array $transactions An iterative array of Model_Transaction (ORM)
	 * @return Array                       Array of Transaction Elements
	 * @throws Exception If Model_Transaction object is not valid.
	 */
	protected function _return_transactions_array($transactions)
	{
		$return_array = array();
		foreach( $transactions as $transaction )
		{
			$return_array[] = $this->_return_transaction_element($transaction);
		}

		return $return_array;
	}

		
	/*
	---BEANSOBJSPEC---
	@object Beans_Transaction
	@description A transaction in the journal.
	@attribute id INTEGER 
	@attribute code STRING
	@attribute check_number STRING
	@attribute description STRING
	@attribute date STRING Date in YYYY-MM-DD format.
	@attribute amount DECIMAL The total one-way transfer amount.
	@attribute payment BOOLEAN Whether or not this transaction is a payment.
	@attribute form OBJECT Either FALSE if not applicable, or an object with an id and type representing the form this is attached to.
	@attribute tax_payment OBJECT Either FALSE if not applicable, or an object an id representing the tax payment.
	@attribute reconciled BOOLEAN Whether or not any of the splits on this transaction have been reconciled.
	@attribute account_transactions ARRAY An array of #Beans_Account_Transaction# representing all splits.
	---BEANSENDSPEC---
	 */

	/**
	 * Returns an object of the properties for the given Model_Transaction (ORM)
	 * @param  Model_Transaction $transaction Model_Transaction ORM Object
	 * @return stdClass                      stdClass of properties for given Model_Transaction
	 * @throws Exception If Model_Transaction object is not valid.
	 */
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
		$return_object->check_number = $transaction->reference;
		$return_object->description = $transaction->description;
		$return_object->date = $transaction->date;
		$return_object->close_books = $transaction->close_books;
		$return_object->amount = $transaction->amount;
		$return_object->payment = ( $transaction->payment )
								? $transaction->payment
								: FALSE;

		// If this is directly tied to a form.
		$return_object->form = FALSE;
		$return_object->tax_payment = FALSE;
		
		if( $transaction->form_type == "tax_payment" )
		{
			$return_object->tax_payment = new stdClass;
			$return_object->tax_payment->id = $transaction->form_id;
		}
		else if ( $transaction->form_type )
		{
			$return_object->form = new stdClass;
			$return_object->form->id = $transaction->form_id;
			$return_object->form->type = $transaction->form_type;
		}

		/*
		// V2Item - See if we can replace this in the views with betterlogic and remove these loads.
		if( $transaction->create_form->loaded() )
		{
			$return_object->form = new stdClass;
			$return_object->form->id = $transaction->create_form->id;
			$return_object->form->type = $transaction->create_form->type;
		}
		else if( $transaction->invoice_form->loaded() )
		{
			$return_object->form = new stdClass;
			$return_object->form->id = $transaction->invoice_form->id;
			$return_object->form->type = $transaction->invoice_form->type;
		}
		else if( $transaction->cancel_form->loaded() )
		{
			$return_object->form = new stdClass;
			$return_object->form->id = $transaction->cancel_form->id;
			$return_object->form->type = $transaction->cancel_form->type;
		}

		// If this is directly tied to a tax payment.
		$return_object->tax_payment = FALSE;

		if( $transaction->tax_payment->loaded() ) {
			$return_object->tax_payment = new stdClass;
			$return_object->tax_payment->id = $transaction->tax_payment->id;
		}
		*/

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
	 * Validates a transaction.
	 * @param  Model_Transaction $transaction 
	 * @return void              
	 * @throws Exception If Invalid transaction attribute.
	 */
	protected function _validate_transaction($transaction)
	{
		if( get_class($transaction) != "Model_Transaction" )
			throw new Exception("Invalid Transaction.");

		if( ! $transaction->code OR 
			! strlen($transaction->code) )
			throw new Exception("Invalid transaction code: none provided.");

		if( strlen($transaction->code) > 16 )
			throw new Exception("Invalid transaction code: maximum of 16 characters.");

		if( ! $transaction->description OR 
			! strlen($transaction->description) )
			throw new Exception("Invalid transaction description: none provided.");

		if( strlen($transaction->description) > 128 )
			throw new Exception("Invalid transaction description: maxiumum of 128 characters.");

		if( strlen($transaction->reference) > 16 )
			throw new Exception("Transaction reference (check number) must be 16 characters of less.");

		if( ! $transaction->date OR 
			! strlen($transaction->date) )
			throw new Exception("Invalid transaction date: none provided.");

		if( date("Y-m-d",strtotime($transaction->date)) != $transaction->date )
			throw new Exception("Invalid transaction date: format should be YYYY-MM-DD.");

		if( $this->_check_books_closed($transaction->date) )
			throw new Exception("Invalid transaction date: that financial year is already closed.");
		
		if( $transaction->close_books AND 
			! $this->_beans_internal_call() )
			throw new Exception("Invalid close books usage. Reserved internal call.");
		else if( $transaction->close_books AND 
				 strtotime($transaction->close_books) >= strtotime($transaction->date) )
			throw new Exception("Invalid close books value: must be before transaction date.");

		if( $transaction->entity_id AND 
			! ORM::Factory('entity',$transaction->entity_id)->loaded() )
			throw new Exception("Internal error: invalid entity referenced on transaction.");

		if( (
				$this->_transaction->form_type OR
				$this->_transaction->form_id 
			) AND
			(
				! $this->_transaction->form_type OR
				! $this->_transaction->form_id 
			) )
			throw new Exception("Invalid transaction form information: must provide both form_type and form_id.");

	}

	/**
	 * Validates an account transaction.
	 * @param  Model_Account_Transaction $account_transaction 
	 * @return void                      
	 * @throws Exception If Invalid account transaction attribute.
	 */
	protected function _validate_account_transaction($account_transaction)
	{
		if( get_class($account_transaction) != "Model_Account_Transaction" )
			throw new Exception("Invalid Account Transaction.");

		if( ! $account_transaction->account_id )
			throw new Exception("Invalid account transaction account ID: none provided.");

		$account = $this->_load_account($account_transaction->account_id);

		if( ! $account->loaded() )
			throw new Exception("Invalid account transaction account ID: could not find account.");

		if( ! $account->parent_account_id OR 
			! $account->account_type->loaded() )
			throw new Exception("Invalid account transaction account: ".$account->name." is a top-level account and cannot hold a transaction.");

		if( $account->reserved AND 
			! $this->_beans_internal_call() )
			throw new Exception("Reserved account journals can only be updated by Beans.");

		// V2Item
		// Consider validating form.
	}

	/**
	 * Returns a new Model_Account with default values - some of these
	 * need to be overwritten in order for it to be valid.
	 * @return Model_Account Model_Account with default values.
	 */
	protected function _default_account()
	{
		$account = ORM::Factory('account');

		$account->parent_account_id = NULL;
		$account->account_type_id = NULL;
		$account->name = NULL;
		$account->code = NULL;
		$account->reconcilable = FALSE;
		$account->terms = 0;
		$account->balance = 0.00;

		return $account;
	}

	protected function _default_account_transaction_form() 
	{
		$account_transaction_form = ORM::Factory('account_transaction_form');

		$account_transaction_form->account_transaction_id = NULL;
		$account_transaction_form->form_id = NULL;
		$account_transaction_form->amount = NULL;

		return $account_transaction_form;
	}

	protected function _validate_account_transaction_form($account_transaction_form)
	{
		if( get_class($account_transaction_form) != "Model_Account_Transaction_Form" )
			throw new Exception("Invalid Account Transaction Form.");

		if( ! $account_transaction_form->form_id )
			throw new Exception("Invalid account transaction form form ID: none provided.");

		if( ! $this->_check_form_id($account_transaction_form->form_id) )
			throw new Exception("Invalid account transaction form form ID: form not found.");

		/*
		if( ! $account_transaction_form->amount )
			throw new Exception("Invalid account transaction form amount: none provided.");
		*/
	}

	/**
	 * Validates a Model_Account
	 * @param  Model_Account $account The Account to validate.
	 * @return void 
	 * @throws Exception If Invalid Account 
	 * @throws Exception If Invalid Account Attributes
	 */
	protected function _validate_account($account)
	{
		if( get_class($account) != "Model_Account" )
			throw new Exception("Invalid Account.");

		if( $account->reserved AND 
			! $this->_beans_internal_call() )
			throw new Exception("Reserved accounts can only be updated by Beans.");

		if( ! $account->account_type_id )
			throw new Exception("Invalid account type: none provided.");

		if( ! $this->_load_account_type($account->account_type_id)->loaded() )
			throw new Exception("Invalid account type: not found.");

		if( ! $account->parent_account_id )
			throw new Exception("Invalid parent account: none provided.");

		if ( ! $this->_load_account($account->parent_account_id)->loaded() )
			throw new Exception("Invalid parent account: not found.");

		if( ! $account->name OR
			! strlen($account->name) )
			throw new Exception("Invalid account name: none provided.");

		if( strlen($account->name) > 64 )
			throw new Exception("Invalid account name: maximum of 64 characters.");

		if( ! $account->code OR
			! strlen($account->code) )
			throw new Exception("Invalid account code: none provided.");

		if( strlen($account->code) > 16 )
			throw new Exception("Invalid account code: maximum of 16 characters.");

	}

	// Account Balancing
	/**
	 * Updates the account balance to match the last transaction in its journal.
	 * @param Integer $account_id The ID of the account to balance.
	 * @return void
	 */
	protected function _account_balance_calibrate($account_id)
	{
		$update_sql = 'UPDATE accounts SET '.
					  'balance = ( SELECT IFNULL(SUM(bbalance),0.00) FROM ('.
					  '		SELECT IFNULL(balance,0.00) as bbalance FROM '.
					  '		account_transactions as aaccount_transactions WHERE '.
					  '		account_id = "'.$account_id.'" '.
					  '		ORDER BY date DESC, close_books ASC, transaction_id DESC LIMIT 1 FOR UPDATE '.
					  ') as baccount_transactions ) '.
					  'WHERE id = "'.$account_id.'"';

		$update_result = DB::Query(Database::UPDATE,$update_sql)->execute();
	}

	/**
	 * Insert a new account transaction into an account's journal.
	 * @param  Model_Account_Transaction $account_transaction The account transaction to insert.
	 * @return Integer The ID of the new account transaction.
	 */
	protected function _account_transaction_insert($account_transaction)
	{
		// Split avoids deadlocks
		$balance_sql = 'SELECT IFNULL(SUM(bbalance),0.00) as new_balance FROM ('.
					   '		SELECT IFNULL(balance,0.00) as bbalance FROM '.
					   '		account_transactions as aaccount_transactions WHERE '.
					   '		account_id = "'.$account_transaction->account_id.'" AND '.
					   '		( '.
					   '			date < DATE("'.$account_transaction->date.'") OR '.
					   '			( '.
					   '				date <= DATE("'.$account_transaction->date.'") AND ( '.
					   ' 				transaction_id < '.$account_transaction->transaction_id.' OR '.
					   ' 				( close_books >= '.( $account_transaction->close_books ? '1' : '0' ).' AND '.
					   ' 				transaction_id < '.$account_transaction->transaction_id.' ) '.
					   '			) '.
					   '		) '.
					   ' 	) ORDER BY date DESC, close_books ASC, transaction_id DESC LIMIT 1 FOR UPDATE '.
					   ') as baccount_transactions';
		$balance_result = DB::Query(Database::SELECT,$balance_sql)->execute();

		$insert_sql = 'INSERT INTO account_transactions '.
					  '(transaction_id, account_id, date, amount, transfer, writeoff, close_books, account_reconcile_id, balance) '.
					  'VALUES ( '.
					  $account_transaction->transaction_id.', '.
					  $account_transaction->account_id.', '.
					  'DATE("'.$account_transaction->date.'"), '.
					  $account_transaction->amount.', '.
					  ( $account_transaction->transfer ? '1' : '0' ).', '.
					  ( $account_transaction->writeoff ? '1' : '0' ).', '.
					  ( $account_transaction->close_books ? '1' : '0' ).', '.
					  ( $account_transaction->account_reconcile_id ? $account_transaction->account_reconcile_id : 'NULL' ).', '.
					  $balance_result[0]['new_balance'].' '.
					  ') ';
		
		$insert_result = DB::Query(Database::INSERT,$insert_sql)->execute();

		$account_transaction_id = $insert_result[0];

		/*
		// Insert new account transaction.
		$insert_sql = 'INSERT INTO account_transactions '.
					  '(transaction_id, account_id, date, amount, transfer, writeoff, close_books, account_reconcile_id, balance) '.
					  'VALUES ( '.
					  $account_transaction->transaction_id.', '.
					  $account_transaction->account_id.', '.
					  'DATE("'.$account_transaction->date.'"), '.
					  $account_transaction->amount.', '.
					  ( $account_transaction->transfer ? '1' : '0' ).', '.
					  ( $account_transaction->writeoff ? '1' : '0' ).', '.
					  ( $account_transaction->close_books ? '1' : '0' ).', '.
					  ( $account_transaction->account_reconcile_id ? $account_transaction->account_reconcile_id : 'NULL' ).', '.
					  '( SELECT IFNULL(SUM(bbalance),0.00) FROM ('.
					  '		SELECT IFNULL(balance,0.00) as bbalance FROM '.
					  '		account_transactions as aaccount_transactions WHERE '.
					  '		account_id = "'.$account_transaction->account_id.'" AND '.
					  '		( '.
					  '			date < DATE("'.$account_transaction->date.'") OR '.
					  '			( '.
					  '				date <= DATE("'.$account_transaction->date.'") AND ( '.
					  ' 				transaction_id < '.$account_transaction->transaction_id.' OR '.
					  ' 				( close_books >= '.( $account_transaction->close_books ? '1' : '0' ).' AND '.
					  ' 				transaction_id < '.$account_transaction->transaction_id.' ) '.
					  '			) '.
					  '		) '.
					  ' 	) ORDER BY date DESC, close_books ASC, transaction_id DESC LIMIT 1 FOR UPDATE '.
					  ') as baccount_transactions ) '.
					  ') ';
		
		$insert_result = DB::Query(Database::INSERT,$insert_sql)->execute();

		$account_transaction_id = $insert_result[0];
		*/
		
		// Update transaction balances.
		$update_sql = 'UPDATE account_transactions '.
					  'SET balance = balance + '.$account_transaction->amount.' WHERE '.
					  'account_id = "'.$account_transaction->account_id.'" AND '.
					  '( '.
					  ' 	( date > DATE("'.$account_transaction->date.'") ) OR '.
					  ' 	( date = DATE("'.$account_transaction->date.'") AND close_books < '.( $account_transaction->close_books ? '1' : '0' ).' ) OR '.
					  ' 	( date = DATE("'.$account_transaction->date.'") AND close_books <= '.( $account_transaction->close_books ? '1' : '0' ).' AND transaction_id >= '.$account_transaction->transaction_id.' ) '.
					  ') ';
		
		$update_result = DB::Query(Database::UPDATE,$update_sql)->execute();

		// Update Account Balance
		$this->_account_balance_calibrate($account_transaction->account_id);

		return $account_transaction_id;
	}

	/**
	 * Remove an account transaction from a journal.
	 * @param  Model_Account_Transaction $account_transaction The account transaction to remove.
	 * @return void
	 */
	protected function _account_transaction_remove($account_transaction)
	{
		if( ! $account_transaction->id ||
			! $account_transaction->account_id ) 
			throw new Exception("Invalid remove account transaction - missing required ID or account ID.");

		// Remove account transaction row.
		$remove_sql = 'DELETE FROM account_transactions '.
					  'WHERE id = "'.$account_transaction->id.'" '.
					  'LIMIT 1';

		$remove_result = DB::Query(Database::DELETE, $remove_sql)->execute();

		// Update transaction balances.
		$update_sql = 'UPDATE account_transactions '.
					  'SET balance = balance - '.$account_transaction->amount.' WHERE '.
					  'account_id = "'.$account_transaction->account_id.'" AND '.
					  '( '.
					  ' 	( date > DATE("'.$account_transaction->date.'") ) OR '.
					  ' 	( date = DATE("'.$account_transaction->date.'") AND close_books < '.( $account_transaction->close_books ? '1' : '0' ).' ) OR '.
					  ' 	( date = DATE("'.$account_transaction->date.'") AND close_books <= '.( $account_transaction->close_books ? '1' : '0' ).' AND transaction_id >= '.$account_transaction->transaction_id.' ) '.
					  ') ';
		
		$update_result = DB::Query(Database::UPDATE,$update_sql)->execute();

		// Update Account Balance
		$this->_account_balance_calibrate($account_transaction->account_id);
	}

	/**
	 * Updates the balance on a form per all transactions tied to that form.
	 * @param  Integer $form_id The ID of the form to update.
	 * @return void
	 */
	protected function _form_balance_calibrate($form_id)
	{
		if( ! $form_id )
			throw new Exception("Invalid form ID - none provided.");

		$balance_sql = 'SELECT IFNULL(balance,0.00) as new_balance FROM ( '.
					   ' 	SELECT SUM(amount) as balance '.
					   '		FROM account_transaction_forms '.
					   '		WHERE form_id = '.$form_id.' '.
					   ') as aforms';
		
		$balance_result = DB::Query(Database::SELECT,$balance_sql)->execute();

		$update_sql = 'UPDATE forms '.
					  'SET balance = '.$balance_result[0]['new_balance'].' '.
					  'WHERE id = "'.$form_id.'"';

		DB::Query(Database::UPDATE, $update_sql)->execute();

		// PENDING FOR v1.1 or future hotfix
		// Requires adding date_paid field and a migration script to update
		// Significantly speeds up payables/receivables reports and allows showing
		// "Paid YYYY-MM-DD" within search results
		/*
		// Update date_paid
		// If Balance == 0 set date_paid to last account_transaction_form date
		$balance_sql = 'SELECT balance FROM forms WHERE id = "'.$form_id.'"';
		$balance = DB::Query(Database::SELECT, $balance_sql)->execute()->as_array();

		if( $balance['balance'] == 0.00 )
		{
			$date_paid_sql = 
				' SELECT account_transaction_forms.id as id, account_transactions.date as date FROM account_transaction_forms '.
				' LEFT JOIN account_transactions ON account_transaction_forms.account_transaction_id = account_transactions.id '.
				' WHERE account_transaction_forms.form_id = '.$form_id.' '.
				' ORDER BY account_transactions.date DESC, account_transactions.id DESC '.
				' LIMIT 1 ';
			$date_paid = DB::Query(Database::SELECT, $date_paid_sql)->execute()->as_array();

			$date_paid_update_sql = 'UPDATE forms SET date_paid = "'.$date_paid[0]['date'].'" WHERE id = "'.$form_id.'"';
			$date_paid_update = DB::Query(Database::UPDATE, $date_paid_update_sql)->execute();
		}
		else
		{
			$date_paid_update_sql = 'UPDATE forms SET date_paid = NULL WHERE id = "'.$form_id.'"';
			$date_paid_update = DB::Query(Database::UPDATE, $date_paid_update_sql)->execute();
		}
		*/
	}

	/**
	 * Loads the default values for a transaction.
	 * @return Model_Transaction 
	 */
	protected function _default_transaction()
	{
		$transaction = ORM::Factory('transaction');

		$transaction->code = NULL;
		$transaction->description = NULL;
		$transaction->date = NULL;

		return $transaction;
	}

	/**
	 * Loads the default values for an account transaction.
	 * @return Model_Account_Transaction 
	 */
	protected function _default_account_transaction()
	{
		$account_transaction = ORM::Factory('account_transaction');

		$account_transaction->transaction_id = NULL;
		$account_transaction->account_id = NULL;
		$account_transaction->amount = NULL;
		$account_transaction->balance = NULL;
		$account_transaction->account_reconcile_id = NULL;

		return $account_transaction;
	}

	/**
	 * Attempts to load an account with the specified ID.
	 * @param  Integer $id
	 * @return Model_Account     The loaded account.
	 */
	protected function _load_account($id)
	{
		return ORM::Factory('account',$id);
	}

	/**
	 * Attempts to load an acount type with the specified ID.
	 * @param  Integer $id 
	 * @return Model_Account_Type     The loaded account type.
	 */
	protected function _load_account_type($id)
	{
		return ORM::Factory('account_type',$id);
	}

	protected function _check_form_id($id)
	{
		$result = DB::Query(Database::SELECT,'SELECT COUNT(1) as id_exists FROM forms WHERE id = '.$id)->execute()->as_array();
		
		return ( isset($result[0]) && $result[0]['id_exists'] ) ? TRUE : FALSE;
	}

	// *** DUPLICATE FUNCTION ***
	protected function _load_form($id)
	{
		return ORM::Factory('form',$id);
	}

}