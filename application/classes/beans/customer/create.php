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
@action Beans_Customer_Create
@description Create a new customer.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required first_name STRING
@required last_name STRING 
@optional company_name STRING 
@optional email STRING 
@optional phone_number STRING 
@optional fax_number STRING
@optional default_account_id INTEGER The ID of the AR #Beans_Account# to default to for sales.
@returns customer OBJECT The resulting #Beans_Customer#.
---BEANSENDSPEC---
*/
class Beans_Customer_Create extends Beans_Customer {

	protected $_auth_role_perm = "customer_write";

	protected $_customer;
	protected $_data;
	protected $_validate_only;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		$this->_customer = $this->_default_customer();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( isset($this->_data->first_name) )
			$this->_customer->first_name = $this->_data->first_name;

		if( isset($this->_data->last_name) )
			$this->_customer->last_name = $this->_data->last_name;

		if( isset($this->_data->company_name) )
			$this->_customer->company_name = $this->_data->company_name;

		if( isset($this->_data->email) )
			$this->_customer->email = $this->_data->email;

		if( isset($this->_data->phone_number) )
			$this->_customer->phone_number = $this->_data->phone_number;

		if( isset($this->_data->fax_number) )
			$this->_customer->fax_number = $this->_data->fax_number;

		if( isset($this->_data->default_account_id) )
			$this->_customer->default_account_id = $this->_data->default_account_id;

		$this->_validate_customer($this->_customer);

		if( $this->_validate_only )
			return (object)array();

		$this->_customer->save();

		return (object)array(
			"customer" => $this->_return_customer_element($this->_customer),
		);
	}
}