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
@action Beans_Vendor_Update
@description Update a vendor.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor# to update.
@optional first_name STRING
@optional last_name STRING 
@optional company_name STRING 
@optional email STRING 
@optional phone_number STRING 
@optional fax_number STRING
@optional default_account_id INTEGER The ID of the AP #Beans_Account# to default to for purchases.
@optional default_remit_address_id INTEGER The ID of the #Beans_Vendor_Address# to use for remitting payment by default.
@returns vendor OBJECT The updated #Beans_Vendor#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Update extends Beans_Vendor {

	protected $_auth_role_perm = "vendor_write";
	
	protected $_vendor;
	protected $_data;
	protected $_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_vendor = $this->_load_vendor($this->_id);

		$this->_data = $data;
	}

	protected function _execute()
	{
		if( ! $this->_vendor->loaded() )
			throw new Exception("Vendor could not be found.");
		
		$vendor_address_first_name = $this->_vendor->first_name;
		$vendor_address_last_name = $this->_vendor->last_name;
		$vendor_address_company_name = $this->_vendor->company_name;

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

		$this->_vendor->save();

		// Cascade FIRST / LAST / COMPANY to Addresses.
		foreach( $this->_vendor->entity_addresses->find_all() as $address )
		{
			// We can assume the information is valid as it matches a valid vendor.
			// We only replace it if it was matching the original vendor name ( easy roll ).
			$address->first_name = ( $vendor_address_first_name == $address->first_name )
								 ? $this->_vendor->first_name
								 : $address->first_name;
			$address->last_name = ( $vendor_address_last_name == $address->last_name )
								? $this->_vendor->last_name
								: $address->last_name;
			$address->company_name = ( $vendor_address_company_name == $address->company_name )
								   ? $this->_vendor->company_name
								   : $address->company_name;
			$address->save();
		}

		return (object)array(
			"vendor" => $this->_return_vendor_element($this->_vendor),
		);
	}
}