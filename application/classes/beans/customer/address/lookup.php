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
@action Beans_Customer_Address_Lookup
@description Look up a customer address.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The id of the #Beans_Customer_Address# to retrieve.
@returns address OBJECT The #Beans_Customer_Address# that was requested.
---BEANSENDSPEC---
*/
class Beans_Customer_Address_Lookup extends Beans_Customer_Address {

	private $_id;
	private $_address;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_address = $this->_load_customer_address($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_address->loaded() )
			throw new Exception("Address could not be found.");

		return (object)array(
			"address" => $this->_return_customer_address_element($this->_address),
		);
	}
}