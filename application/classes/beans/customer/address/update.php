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
@action Beans_Customer_Address_Update
@description Update a customer address.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer_Address# to update.
@optional first_name STRING 
@optional last_name STRING 
@optional company_name STRING 
@optional address1 STRING 
@optional address2 STRING 
@optional city STRING 
@optional state STRING 
@optional zip STRING 
@optional country STRING 
@returns address OBJECT The updated #Beans_Customer_Address#.
---BEANSENDSPEC---
*/
class Beans_Customer_Address_Update extends Beans_Customer_Address {

	protected $_auth_role_perm = "customer_write";
	
	protected $_address;
	protected $_data;
	protected $_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_address = $this->_load_customer_address($this->_id);
		
		$this->_data = $data;

	}

	protected function _execute()
	{
		if( ! $this->_address->loaded() )
			throw new Exception("Address could not be found.");

		if( isset($this->_data->first_name) )
			$this->_address->first_name = $this->_data->first_name;

		if( isset($this->_data->last_name) )
			$this->_address->last_name = $this->_data->last_name;

		if( isset($this->_data->company_name) )
			$this->_address->company_name = $this->_data->company_name;

		if( isset($this->_data->address1) )
			$this->_address->address1 = $this->_data->address1;

		if( isset($this->_data->address2) )
			$this->_address->address2 = $this->_data->address2;

		if( isset($this->_data->city) )
			$this->_address->city = $this->_data->city;

		if( isset($this->_data->state) )
			$this->_address->state = $this->_data->state;

		if( isset($this->_data->zip) )
			$this->_address->zip = $this->_data->zip;

		if( isset($this->_data->country) )
			$this->_address->country = $this->_data->country;

		$this->_validate_customer_address($this->_address);

		$this->_address->save();

		return (object)array(
			"address" => $this->_return_customer_address_element($this->_address),
		);
	}
}