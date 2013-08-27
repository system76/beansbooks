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
@action Beans_Account_Create
@description Create a new account.
@required auth_uid
@required auth_key
@required auth_expiration
@required account_type_id The #Beans_Account#_Type of this account.
@required parent_account_id The #Beans_Account# that is a direct parent of this one in the Chart of Accounts.
@required name The plain-text name to assign to this account.
@required code A hash or word to represent the account by.
@required writeoff A boolean representing whether or not this account can record writeoffs.
@optional terms The terms ( in days ) to assign to invoices tied to this account.
@returns account The #Beans_Account# that was created.
---BEANSENDSPEC---
*/
class Beans_Account_Create extends Beans_Account {

	protected $_auth_role_perm = "account_write";

	protected $_account;
	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
		$this->_account = $this->_default_account();
	}

	protected function _execute()
	{
		if( isset($this->_data->account_type_id) )
			$this->_account->account_type_id = $this->_data->account_type_id 
											 ? $this->_data->account_type_id 
											 : NULL;

		$account_type = ORM::Factory('account_type',$this->_account->account_type_id);

		if( ! $account_type->loaded() )
			throw new Exception("Invalid account type: not found.");

		// Copy settings over from account type.
		$this->_account->deposit = $account_type->deposit;
		$this->_account->payment = $account_type->payment;
		$this->_account->payable = $account_type->payable;
		$this->_account->receivable = $account_type->receivable;
		$this->_account->reconcilable = $account_type->reconcilable;

		if( isset($this->_data->parent_account_id) )
			$this->_account->parent_account_id = $this->_data->parent_account_id 
											   ? $this->_data->parent_account_id 
											   : NULL;
		
		if( isset($this->_data->name) )
			$this->_account->name = $this->_data->name;

		if( isset($this->_data->code) )
			$this->_account->code = $this->_data->code;

		if( isset($this->_data->writeoff) )
			$this->_account->writeoff = $this->_data->writeoff 
										  ? TRUE 
										  : FALSE;

		$this->_account->reserved = ( isset($this->_data->reserved) AND $this->_data->reserved ) ? TRUE : FALSE;

		if( isset($this->_data->terms) )
			$this->_account->terms = ( strlen($this->_data->terms) > 0 ) ? $this->_data->terms : NULL;
		else
			$this->_account->terms = NULL;

		$this->_validate_account($this->_account);

		$this->_account->save();

		return (object)array(
			"account" => $this->_return_account_element($this->_account),
		);
	}
}