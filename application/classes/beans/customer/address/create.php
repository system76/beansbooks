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
@action Beans_Customer_Address_Create
@description Create a new customer address.  If the customer has no default_billing_address_id or default_shipping_address_id, it will automatically be set to this new address.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required customer_id INTEGER The ID of the #Beans_Customer# this address belongs to.
@required first_name STRING If not provided, will copy from #Beans_Customer#.
@required last_name STRING If not provided, will copy from #Beans_Customer#.
@optional company_name STRING If not provided, will copy from #Beans_Customer# if exists.
@required address1 STRING 
@optional address2 STRING 
@required city STRING 
@optional state STRING 
@required zip STRING 
@required country STRING 
@returns address OBJECT The resulting #Beans_Customer_Address#.
---BEANSENDSPEC---
*/
class Beans_Customer_Address_Create extends Beans_Customer_Address {

	protected $_auth_role_perm = "customer_write";
	
	protected $_address;
	protected $_data;
	protected $_validate_only;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		$this->_address = $this->_default_customer_address();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( isset($this->_data->customer_id) )
			$this->_address->entity_id = $this->_data->customer_id;

		if( isset($this->_data->first_name) )
			$this->_address->first_name = $this->_data->first_name;
		else
			$this->_address->first_name = "AUTOGENERATE";

		if( isset($this->_data->last_name) )
			$this->_address->last_name = $this->_data->last_name;
		else
			$this->_address->last_name = "AUTOGENERATE";

		if( isset($this->_data->company_name) )
			$this->_address->company_name = $this->_data->company_name;
		else
			$this->_address->company_name = "AUTOGENERATE";

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

		$this->_validate_customer_address($this->_address,$this->_validate_only);

		$customer = $this->_load_customer($this->_address->entity_id);

		if( $this->_address->first_name == "AUTOGENERATE" )
			$this->_address->first_name = $customer->first_name;

		if( $this->_address->last_name == "AUTOGENERATE" )
			$this->_address->last_name = $customer->last_name;

		if( $this->_address->company_name == "AUTOGENERATE" )
			$this->_address->company_name = $customer->company_name;
		
		if( $this->_validate_only ) 
			return (object)array();

		$this->_address->save();

		$entity_updated = FALSE;
		if( ! $this->_address->entity->default_shipping_address_id )
		{
			$entity_updated = TRUE;
			$this->_address->entity->default_shipping_address_id = $this->_address->id;
		}
		
		if( ! $this->_address->entity->default_billing_address_id )
		{
			$entity_updated = TRUE;
			$this->_address->entity->default_billing_address_id = $this->_address->id;
		}

		if( $entity_updated )
			$this->_address->entity->save();

		return (object)array(
			"address" => $this->_return_customer_address_element($this->_address),
		);
	}
}