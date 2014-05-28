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
@action Beans_Account_Lookup
@description Look up an account.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The id of the #Beans_Account# to retrieve.
@returns account The #Beans_Account# that was requested.
---BEANSENDSPEC---
*/
class Beans_Account_Lookup extends Beans_Account {
	
	private $_id;
	private $_account;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_account = $this->_load_account($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_account->loaded() )
			throw new Exception("Account could not be found.");

		return (object)array(
			"account" => $this->_return_account_element($this->_account),
		);
	}
}