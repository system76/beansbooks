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
@action Beans_Vendor_Address_Shipping_Create
@description Create a new vendor shipping address.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required first_name STRING 
@required last_name STRING 
@optional company_name STRING 
@required address1 STRING 
@optional address2 STRING 
@required city STRING 
@optional state STRING 
@required zip STRING 
@required country STRING 
@returns address OBJECT The resulting #Beans_Vendor_Address_Shipping#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Address_Shipping_Create extends Beans_Vendor_Address {

	protected $_auth_role_perm = "vendor_write";

	protected $_address;
	protected $_data;
	protected $_validate_only;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		$this->_address = $this->_default_vendor_address();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		$this->_address->entity_id = $this->_VENDOR_ADDRESS_SHIPPING_ENTITY_ID;

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

		$this->_validate_vendor_address($this->_address,$this->_validate_only);

		if( $this->_validate_only ) 
			return (object)array();

		$this->_address->save();

		return (object)array(
			"address" => $this->_return_vendor_address_element($this->_address),
		);
	}
}