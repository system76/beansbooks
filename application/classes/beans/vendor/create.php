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
@action Beans_Vendor_Create
@description Create a new vendor.
@required auth_uid 
@required auth_key 
@required auth_expiration
@optional first_name STRING
@optional last_name STRING 
@required company_name STRING 
@optional email STRING 
@optional phone_number STRING 
@optional fax_number STRING
@optional default_account_id INTEGER The ID of the AP #Beans_Account# to default to for purchases.
@returns vendor OBJECT The resulting #Beans_Vendor#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Create extends Beans_Vendor {

	protected $_auth_role_perm = "vendor_write";

	protected $_vendor;
	protected $_data;
	protected $_validate_only;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		$this->_vendor = $this->_default_vendor();
		$this->_validate_only = ( 	isset($this->_data->validate_only) AND 
							 		$this->_data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( isset($this->_data->first_name) )
			$this->_vendor->first_name = $this->_data->first_name;

		if( isset($this->_data->last_name) )
			$this->_vendor->last_name = $this->_data->last_name;

		if( isset($this->_data->company_name) )
			$this->_vendor->company_name = $this->_data->company_name;

		if( isset($this->_data->email) )
			$this->_vendor->email = $this->_data->email;

		if( isset($this->_data->phone_number) )
			$this->_vendor->phone_number = $this->_data->phone_number;

		if( isset($this->_data->fax_number) )
			$this->_vendor->fax_number = $this->_data->fax_number;

		if( isset($this->_data->default_remit_address_id) )
			$this->_vendor->default_remit_address_id = $this->_data->default_remit_address_id;

		if( isset($this->_data->default_account_id) )
			$this->_vendor->default_account_id = $this->_data->default_account_id;

		$this->_validate_vendor($this->_vendor);

		if( $this->_validate_only )
			return (object)array();

		$this->_vendor->save();

		return (object)array(
			"vendor" => $this->_return_vendor_element($this->_vendor),
		);
	}
}