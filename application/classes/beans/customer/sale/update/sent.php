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
@action Beans_Customer_Sale_Update_Sent
@description Update the sent status of a sale.  This is useful if you want to only update a single attribute without having to re-create or update the entire sale ( i.e. to mark it as sent ).
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer_Sale# to update.
@optional sent STRING The sent status of the sale: 'email', 'print', 'both', or NULL
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Update_Sent extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";
	
	protected $_data;
	protected $_id;
	protected $_sale;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("Sale could not be found.");

		if( isset($this->_data->sent) )
			$this->_sale->sent = $this->_data->sent;

		$this->_validate_customer_sale($this->_sale);

		$this->_sale->save();

		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}