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
@action Beans_Tax_Update
@description Update a tax.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax# being updated.
@optional name STRING
@optional code STRING Will be auto-generated if not provided.
@optional percent DECIMAL In decimal form, so 1.5% is 0.0015
@optional account_id INTEGER The #Beans_Account# that the taxes will be recorded to.
@optional date_due STRING The next YYYY-MM-DD date that remittance is due.
@optional date_due_months_increment INTEGER The number of months between each payment.
@optional license STRING The license number for your registration in this jurisdiction.
@optional authority STRING The tax authority ( who you write checks to ).
@optional address1 STRING The address where you remit payment to.
@optional address2 STRING 
@optional city STRING
@optional state STRING
@optional zip STRING
@optional country STRING
@returns tax OBJECT The updated #Beans_Tax#.
---BEANSENDSPEC---
*/
class Beans_Tax_Update extends Beans_Tax {

	protected $_auth_role_perm = "setup";
	
	protected $_id;
	protected $_tax;
	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_tax = $this->_load_tax($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_tax->loaded() )
			throw new Exception("Tax could not be found.");

		if( isset($this->_data->name) )
			$this->_tax->name = $this->_data->name;

		if( isset($this->_data->code) )
			$this->_tax->code = $this->_data->code;

		if( isset($this->_data->date_due) )
			$this->_tax->date_due = $this->_data->date_due;

		if( isset($this->_data->date_due_months_increment) )
			$this->_tax->date_due_months_increment = $this->_data->date_due_months_increment;

		if( isset($this->_data->license) )
			$this->_tax->license = $this->_data->license;

		if( isset($this->_data->authority) )
			$this->_tax->authority = $this->_data->authority;

		if( isset($this->_data->address1) )
			$this->_tax->address1 = $this->_data->address1;

		if( isset($this->_data->address2) )
			$this->_tax->address2 = $this->_data->address2;

		if( isset($this->_data->city) )
			$this->_tax->city = $this->_data->city;

		if( isset($this->_data->state) )
			$this->_tax->state = $this->_data->state;

		if( isset($this->_data->zip) )
			$this->_tax->zip = $this->_data->zip;

		if( isset($this->_data->country) )
			$this->_tax->country = $this->_data->country;

		$this->_validate_tax($this->_tax);

		$this->_tax->save();

		return (object)array(
			"tax" => $this->_return_tax_element($this->_tax),
		);
	}
}