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
@action Beans_Customer_Update
@description Update a customer.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer# to update.
@optional first_name STRING
@optional last_name STRING 
@optional company_name STRING 
@optional email STRING 
@optional phone_number STRING 
@optional fax_number STRING
@optional default_account_id INTEGER The ID of the AR #Beans_Account# to default to for sales.
@optional default_shipping_address_id INTEGER The ID of the #Beans_Customer_Address# to use for shipping by default.
@optional default_billing_address_id INTEGER The ID of the #Beans_Customer_Address# to use for billing by default.
@returns customer OBJECT The updated #Beans_Customer#.
---BEANSENDSPEC---
*/
class Beans_Customer_Update extends Beans_Customer {

	protected $_auth_role_perm = "customer_write";
	
	protected $_customer;
	protected $_data;
	protected $_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_customer = $this->_load_customer($this->_id);

		$this->_data = $data;
	}

	protected function _execute()
	{
		if( ! $this->_customer->loaded() )
			throw new Exception("Customer could not be found.");
		
		$customer_address_first_name = $this->_customer->first_name;
		$customer_address_last_name = $this->_customer->last_name;
		$customer_address_company_name = $this->_customer->company_name;
		
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

		if( isset($this->_data->default_shipping_address_id) )
			$this->_customer->default_shipping_address_id = (int)$this->_data->default_shipping_address_id;

		if( isset($this->_data->default_billing_address_id) )
			$this->_customer->default_billing_address_id = (int)$this->_data->default_billing_address_id;

		if( isset($this->_data->default_account_id) )
			$this->_customer->default_account_id = $this->_data->default_account_id;

		$this->_validate_customer($this->_customer);

		$this->_customer->save();

		// Cascade FIRST / LAST / COMPANY to Addresses.
		foreach( $this->_customer->entity_addresses->find_all() as $address )
		{
			// We can assume the information is valid as it matches a valid customer.
			// We only replace it if it was matching the original customer name ( easy roll ).
			$address->first_name = ( $customer_address_first_name == $address->first_name )
								 ? $this->_customer->first_name
								 : $address->first_name;
			$address->last_name = ( $customer_address_last_name == $address->last_name )
								? $this->_customer->last_name
								: $address->last_name;
			$address->company_name = ( $customer_address_company_name == $address->company_name )
								   ? $this->_customer->company_name
								   : $address->company_name;
			$address->save();
		}

		return (object)array(
			"customer" => $this->_return_customer_element($this->_customer),
		);
	}
}