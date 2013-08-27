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
@action Beans_Account_Reconcile_Lookup
@description Look up an account reconciliation.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Account_Reconcile# to lookup.
@returns account_reconcile The resulting #Beans_Account_Reconcile#.
---BEANSENDSPEC---
*/
class Beans_Account_Reconcile_Lookup extends Beans_Account_Reconcile {

	protected $_id;
	protected $_account_reconcile;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_account_reconcile = $this->_load_account_reconcile($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_account_reconcile->loaded() )
			throw new Exception("Account reconcile could not be found.");

		return (object)array(
			"account_reconcile" => $this->_return_account_reconcile_element($this->_account_reconcile),
		);
	}
}