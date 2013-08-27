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
@action Beans_Vendor_Purchase_Update_Sent
@description Update the sent status of a purchase.  This is useful if you want to only update a single attribute without having to re-create or update the entire purchase ( i.e. to mark it as sent ).
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Purchase# to update.
@optional sent STRING The sent status of the purchase: 'email', 'print', 'both', or NULL
@returns purchase OBJECT The updated #Beans_Vendor_Purchase#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Update_Sent extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";
	
	protected $_data;
	protected $_id;
	protected $_purchase;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("Purchase could not be found.");

		if( isset($this->_data->sent) )
			$this->_purchase->sent = $this->_data->sent;

		$this->_validate_vendor_purchase($this->_purchase);

		$this->_purchase->save();

		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
		);
	}
}