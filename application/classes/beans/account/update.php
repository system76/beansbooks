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
@action Beans_Account_Update
@description Update an account.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The id of the #Beans_Account# to update.
@optional parent_account_id The #Beans_Account# that is a direct parent of this one in the Chart of Accounts.
@optional account_type_id The #Beans_Account#_Type of this account.
@optional name The plain-text name to assign to this account.
@optional code A hash or word to represent the account by.
@optional writeoff A boolean representing whether or not this account can record writeoffs.
@optional terms The terms ( in days ) to assign to invoices tied to this account.
@returns account The #Beans_Account# that was updated.
---BEANSENDSPEC---
*/
class Beans_Account_Update extends Beans_Account {

	protected $_auth_role_perm = "account_write";
	
	protected $_id;
	protected $_data;
	protected $_account;

	/**
	 * Update an account
	 * @param array $data fields => values to update account.
	 *                    Must include id to reference account.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_data = $data;
		$this->_account = $this->_load_account($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_account->loaded() )
			throw new Exception("That account could not be found.");

		if( ! isset($this->_account->account_type->id) )
			throw new Exception("Top level accounts cannot be changed.");

		if( isset($this->_data->parent_account_id) )
			$this->_account->parent_account_id = $this->_data->parent_account_id;

		if( isset($this->_data->account_type_id) )
		{
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
		}
			
		if( isset($this->_data->name) )
			$this->_account->name = $this->_data->name;

		if( isset($this->_data->code) )
			$this->_account->code = $this->_data->code;

		if( isset($this->_data->writeoff) )
			$this->_account->writeoff = $this->_data->writeoff 
										  ? TRUE 
										  : FALSE;

		if( isset($this->_data->reserved) )
			$this->_account->reserved = $this->_data->reserved;

		if( isset($this->_data->terms) )
			$this->_account->terms = ( strlen($this->_data->terms) ) ? $this->_data->terms : NULL;
		
		$this->_validate_account($this->_account);

		$this->_account->save();

		return (object)array(
			"account" => $this->_return_account_element($this->_account),
		);
	}
}