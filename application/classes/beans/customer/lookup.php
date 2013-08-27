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
@action Beans_Customer_Lookup
@description Look up a customer.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer# to retrieve.
@returns account OBJECT The #Beans_Customer# that was requested.
@returns sales ARRAY The #Beans_Customer_Sale# objects tied to this customer.
---BEANSENDSPEC---
*/
class Beans_Customer_Lookup extends Beans_Customer {
	
	private $_id;
	private $_customer;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_customer = $this->_load_customer($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_customer->loaded() )
			throw new Exception("Customer could not be found.");

		// Grab customer sales.
		$customer_sale_search = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
			'customer_id' => $this->_customer->id,
		)));
		$customer_sale_search_result = $customer_sale_search->execute();

		if( ! $customer_sale_search_result->success )
			throw new Exception("An error occurred when looking up customer sales.");

		return (object)array(
			"customer" => $this->_return_customer_element($this->_customer),
			"sales" => $customer_sale_search_result->data->sales,
		);
	}
}