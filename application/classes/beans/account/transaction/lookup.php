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
@action Beans_Account_Transaction_Lookup
@description Look up a transaction.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Transaction# being requested.
@returns transaction The resulting #Beans_Transaction#.
---BEANSENDSPEC---
*/
class Beans_Account_Transaction_Lookup extends Beans_Account_Transaction {

	protected $_id;
	protected $_transaction;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_transaction = $this->_load_transaction($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_transaction->loaded() )
			throw new Exception("Transaction could not be found.");

		return (object)array(
			"transaction" => $this->_return_transaction_element($this->_transaction),
		);
	}
}