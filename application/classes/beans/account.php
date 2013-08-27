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
	@attribute account_transaction_forms ARRAY An array of #Beans_Account_Transaction_Form# delineating how this transaction affects form balances. 
	---BEANSENDSPEC---
	 */

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
		$return_object->account_transaction_forms = $this->_return_account_transaction_forms_array($account_transaction->account_transaction_forms->find_all());
		
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

		if( isset($this->_return_account_transaction_form_element[$account_transaction_form->id]) )
			return $this->_return_account_transaction_form_element[$account_transaction_form->id];

		// Don't link to objects - just reference by ID.
		$return_object->id = $account_transaction_form->id;
		$return_object->account_transaction_id = $account_transaction_form->account_transaction_id;
		$return_object->form_id = $account_transaction_form->form_id;
		$return_object->amount = $account_transaction_form->amount;

		$this->_return_account_transaction_form_element[$account_transaction_form->id] = $return_object;
		return $this->_return_account_transaction_form_element[$account_transaction_form->id];
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
		$return_object->amount = $transaction->amount;
		$return_object->payment = ( $transaction->payment )
								? $transaction->payment
								: FALSE;

		// If this is directly tied to a form.
		$return_object->form = FALSE;
		
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

		if( $account_transaction->amount == 0 )
			throw new Exception("Invalid account transaction amount: must be non-zero.");

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

		if( ! $this->_load_form($account_transaction_form->form_id)->loaded() )
			throw new Exception("Invalid account transaction form form ID: form not found.");

		if( ! $account_transaction_form->amount )
			throw new Exception("Invalid account transaction form amount: none provided.");

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
	 * Adds an account transaction to the account journal ( updates balances ).
	 * This new transaction should already be tied to a parent transaction and have an amount and account id.
	 * @param  Model_Account_Transaction $new_account_transaction 
	 * @return void 
	 */
	protected function _account_balance_new_transaction($new_account_transaction)
	{
		if( ! $new_account_transaction OR 
			! $new_account_transaction->loaded() )
			throw new Exception("Invalid new account transaction - could not be loaded.");

		if( ! $new_account_transaction->account->loaded() OR 
			! $new_account_transaction->transaction->loaded() OR
			! strlen($new_account_transaction->amount) )
			throw new Exception("Invalid new account transaction - missing required attributes. Must be completed less balance.");

		$previous_transaction = $this->_previous_account_transaction($new_account_transaction);
		$next_transaction = $this->_next_account_transaction($new_account_transaction);

		// First transaction for account.
		if( ! $previous_transaction AND 
			! $next_transaction )
			$new_account_transaction->balance = $this->_beans_round($new_account_transaction->amount);

		// Inserted at beginning of journal.
		else if( ! $previous_transaction )
			$new_account_transaction->balance = $this->_beans_round($new_account_transaction->amount);

		// Inserted somewhere in the middle of the journal.
		else
			$new_account_transaction->balance = $this->_beans_round($previous_transaction->balance + $new_account_transaction->amount);
		
		// Update all account_transactions after this one. $uq = Update Query.
		$uq = 	'UPDATE account_transactions LEFT JOIN transactions ON transactions.id = account_transactions.transaction_id ';
		$uq .= 	'SET account_transactions.balance = ( account_transactions.balance + '.$new_account_transaction->amount.' ) ';
		$uq .=	'WHERE account_transactions.account_id = '.$new_account_transaction->account->id.' AND ';
		$uq .= 	' ( ';
		$uq .=	' ( transactions.id > '.$new_account_transaction->transaction->id.' AND transactions.date = "'.$new_account_transaction->transaction->date.'" ) OR ';
		$uq .= 	' ( transactions.date > "'.$new_account_transaction->transaction->date.'" ) ';
		$uq .= 	' ) ';
		DB::query(NULL,$uq)->execute();

		// Update account balance.
		DB::query(NULL,'UPDATE accounts SET balance = balance + '.$new_account_transaction->amount.' WHERE id = "'.$new_account_transaction->account->id.'"')->execute();

		$new_account_transaction->save();
	}

	/**
	 * Removes an account transaction from an account journal.
	 * @param  Model_Account_Transaction $remove_account_transaction 
	 * @return void                             
	 */
	protected function _account_balance_remove_transaction($remove_account_transaction,$preserve_forms = FALSE)
	{
		if( ! $remove_account_transaction OR 
			! $remove_account_transaction->loaded() )
			throw new Exception("Invalid remove account transaction - could not be loaded.");

		if( ! $remove_account_transaction->account->loaded() OR 
			! $remove_account_transaction->transaction->loaded() OR
			! strlen($remove_account_transaction->amount) OR 
			! strlen($remove_account_transaction->balance) )
			throw new Exception("Invalid remove account transaction - missing required attributes. Must be completed and balanced.");

		$uq = 	'UPDATE account_transactions LEFT JOIN transactions ON transactions.id = account_transactions.transaction_id ';
		$uq .= 	'SET account_transactions.balance = ( account_transactions.balance - '.$remove_account_transaction->amount.' ) ';
		$uq .=	'WHERE account_transactions.account_id = '.$remove_account_transaction->account->id.' AND ';
		$uq .= 	' ( ';
		$uq .=	' ( transactions.id > '.$remove_account_transaction->transaction->id.' AND transactions.date = "'.$remove_account_transaction->transaction->date.'" ) OR ';
		$uq .= 	' ( transactions.date > "'.$remove_account_transaction->transaction->date.'" ) ';
		$uq .= 	' ) ';
		DB::query(NULL,$uq)->execute();

		if( $remove_account_transaction->account->account_transactions->count_all() == 1 )
		{
			// Set to 0 - even though the below should be good this aids in testing.
			DB::query(NULL,'UPDATE accounts SET balance = 0.00 WHERE id = "'.$remove_account_transaction->account->id.'"')->execute();
		}
		else
		{
			// Update account balance.
			DB::query(NULL,'UPDATE accounts SET balance = balance - '.$remove_account_transaction->amount.' WHERE id = "'.$remove_account_transaction->account->id.'"')->execute();
		}

		if( ! $preserve_forms )
		{
			// Remove all attached account_transaction_forms
			foreach( $remove_account_transaction->account_transaction_forms->find_all() as $account_transaction_form )
				$account_transaction_form->delete();
		}

		// Delete the transaction.
		$remove_account_transaction->delete();
	}

	/**
	 * This assumes something hit the fan, but that we also know a last good date.
	 * @param  Integer $account_id 
	 * @param  String $date 
	 * @return void 
	 */
	protected function _account_balance_calibrate($account_id,$date)
	{
		throw new Exception("DEPRECATED UNTIL REDESIGN ON ACCOUNT TRANSACTION UPDATES.");
		
		if( ! $account_id )
			throw new Exception("Invalid account ID - none provided.");

		$account = $this->_load_account($account_id);

		if( ! $account->loaded() )
			throw new Exception("Invalid account ID - not found.");

		if( ! $date )
			throw new Exception("Invalid date - none provided.");

		if( $date != date("Y-m-d",strtotime($date)) )
			throw new Exception("Invalid date - must be in YYYY-MM-DD format.");

		// Get to work.
		
		// LOCK TABLE
		// Before throwing any exceptions after this point we should/must run a COMMIT; query.
		//DB::query(NULL, 'START TRANSACTION;')->execute();
		//DB::query(NULL, 'SET autocommit=0;')->execute();
		//DB::query(NULL, 'LOCK TABLES account_transactions WRITE, transactions WRITE, accounts WRITE;')->execute();
		//DB::query(NULL, 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE')->execute();
		DB::query(NULL, 'START TRANSACTION')->execute();


		// V2Item
		// Consider changing to a find_all() and looping saves afterwards.
		
		$account_transaction = $account->account_transactions->
			join('transactions','left')->on('account_transaction.transaction_id','=','transactions.id')->
			where('transactions.date','<',$date)->
			order_by('transactions.date','desc')->
			order_by('transactions.id','desc')->
			find();

		$next_transaction = $this->_next_account_transaction($account_transaction);

		while( $next_transaction )
		{
			$next_transaction->balance = $this->_beans_round($account_transaction->balance + $next_transaction->amount);
			$next_transaction->save();
			$account_transaction = $next_transaction;
			$next_transaction = $this->_next_account_transaction($account_transaction);
		}

		// Update Account
		DB::query(NULL, 'UPDATE accounts SET balance = '.$account_transaction->balance.' WHERE id = "'.$account->id.'"')->execute();

		// UNLOCK TABLE
		DB::query(NULL, 'COMMIT;')->execute();
		//DB::query(NULL, 'UNLOCK TABLES;')->execute();
		//DB::query(NULL, 'SET autocommit=1;')->execute();
	}

	private function _previous_account_transaction($account_transaction)
	{
		if( ! $account_transaction OR 
			! $account_transaction->loaded() )
			throw new Exception("Invalid account transaction provided.");

		$previous_transaction = ORM::Factory('account_transaction')->
			join('transactions','left')->on('account_transaction.transaction_id','=','transactions.id')->
			where('account_transaction.account_id','=',$account_transaction->account_id)->
			and_where_open()->
			or_where_open()->
			where('transactions.date','=',$account_transaction->transaction->date)->
			where('transactions.id','<',$account_transaction->transaction->id)->
			or_where_close()->
			or_where('transactions.date','<',$account_transaction->transaction->date)->
			and_where_close()->
			order_by('transactions.date','desc')->
			order_by('transactions.id','desc')->
			find();

		if( ! $previous_transaction->loaded() )
			return FALSE;

		return $previous_transaction;
	}

	private function _next_account_transaction($account_transaction)
	{
		if( ! $account_transaction OR 
			! $account_transaction->loaded() )
			throw new Exception("Invalid account transaction provided.");

		$next_transaction = ORM::Factory('account_transaction')->
			join('transactions','left')->on('account_transaction.transaction_id','=','transactions.id')->
			where('account_transaction.account_id','=',$account_transaction->account_id)->
			and_where_open()->
			or_where_open()->
			where('transactions.date','=',$account_transaction->transaction->date)->
			where('transactions.id','>',$account_transaction->transaction->id)->
			or_where_close()->
			or_where('transactions.date','>',$account_transaction->transaction->date)->
			and_where_close()->
			order_by('transactions.date','asc')->
			order_by('transactions.id','asc')->
			find();

		if( ! $next_transaction->loaded() )
			return FALSE;

		return $next_transaction;
	}

	protected function _form_balance_calibrate($form_id)
	{
		if( ! $form_id )
			throw new Exception("Invalid form ID - none provided.");

		$form = ORM::Factory('form',$form_id);

		if( ! $form->loaded() )
			throw new Exception("Invalid form ID - not found.");

		// Forms don't require us to track a per-transaction balance - 
		// so we can simply query for the SUM of all related transactions and apply it.
		$form_balance = DB::query(Database::SELECT,'SELECT SUM(amount) as balance FROM account_transaction_forms WHERE form_id = "'.$form->id.'"')->execute()->as_array();

		$form->balance = $form_balance[0]['balance'];
		$form->save();
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

	protected function _load_account_type($id)
	{
		return ORM::Factory('account_type',$id);
	}

	// *** DUPLICATE FUNCTION ***
	protected function _load_form($id)
	{
		return ORM::Factory('form',$id);
	}

}