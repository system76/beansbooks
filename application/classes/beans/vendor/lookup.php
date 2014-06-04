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
@action Beans_Vendor_Lookup
@description Look up a vendor.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor# to retrieve.
@returns account OBJECT The #Beans_Vendor# that was requested.
@returns expenses ARRAY The #Beans_Vendor_Expense# objects tied to this vendor.
@returns purchases ARRAY The #Beans_Vendor_Purchase# objects tied to this vendor.
---BEANSENDSPEC---
*/
class Beans_Vendor_Lookup extends Beans_Vendor {
	private $_id;
	private $_vendor;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_vendor = $this->_load_vendor($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_vendor->loaded() )
			throw new Exception("Vendor could not be found.");

		$vendor_purchase_search = new Beans_Vendor_Purchase_Search($this->_beans_data_auth((object)array(
			'vendor_id' => $this->_vendor->id,
		)));
		$vendor_purchase_search_result = $vendor_purchase_search->execute();

		if( ! $vendor_purchase_search_result->success )
			throw new Exception("An error occurred when looking up vendor purchase purchases.");

		$vendor_expense_search = new Beans_Vendor_Expense_Search($this->_beans_data_auth((object)array(
			'vendor_id' => $this->_vendor->id,
		)));
		$vendor_expense_search_result = $vendor_expense_search->execute();

		if( ! $vendor_expense_search_result->success )
			throw new Exception("An error occurred when looking up vendor expenses.");

		return (object)array(
			"vendor" => $this->_return_vendor_element($this->_vendor),
			"purchases" => $vendor_purchase_search_result->data->purchases,
			"expenses" => $vendor_expense_search_result->data->expenses,
		);
	}
}