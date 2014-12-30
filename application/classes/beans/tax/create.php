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
@action Beans_Tax_Create
@description Create a new tax.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required name STRING
@optional code STRING Will be auto-generated if not provided.
@required percent DECIMAL In decimal form, so 1.5% is 0.0015
@required account_id INTEGER The #Beans_Account# that the taxes will be recorded to.
@required date_due STRING The next YYYY-MM-DD date that remittance is due.
@required date_due_months_increment INTEGER The number of months between each payment.
@required authority STRING The tax authority ( who you write checks to ).
@optional license STRING The license number for your registration in this jurisdiction.
@optional address1 STRING The address where you remit payment to.
@optional address2 STRING 
@optional city STRING
@optional state STRING
@required zip STRING
@required country STRING
@returns tax OBJECT The resulting #Beans_Tax#.
---BEANSENDSPEC---
*/
class Beans_Tax_Create extends Beans_Tax {

	protected $_auth_role_perm = "setup";
	
	protected $_id;
	protected $_tax;
	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;

		$this->_tax = $this->_default_tax();
	}

	protected function _execute()
	{
		$this->_tax->name = ( isset($this->_data->name) )
						  ? $this->_data->name
						  : NULL;

		$this->_tax->code = ( isset($this->_data->code) )
						  ? $this->_data->code
						  : "AUTOGENERATE";

		$this->_tax->percent = ( isset($this->_data->percent) )
							 ? $this->_data->percent
							 : NULL;

		$this->_tax->account_id = ( isset($this->_data->account_id) )
								? $this->_data->account_id
								: NULL;

		$this->_tax->date_due = ( isset($this->_data->date_due) )
							  ? $this->_data->date_due
							  : NULL;

		$this->_tax->date_due_months_increment = ( isset($this->_data->date_due_months_increment) )
											   ? $this->_data->date_due_months_increment
											   : NULL;

		$this->_tax->license = ( isset($this->_data->license) )
							 ? $this->_data->license
							 : NULL;

		$this->_tax->authority = ( isset($this->_data->authority) )
							 ? $this->_data->authority
							 : NULL;

		$this->_tax->address1 = ( isset($this->_data->address1) )
							 ? $this->_data->address1
							 : NULL;

		$this->_tax->address2 = ( isset($this->_data->address2) )
							 ? $this->_data->address2
							 : NULL;

		$this->_tax->city = ( isset($this->_data->city) )
							 ? $this->_data->city
							 : NULL;

		$this->_tax->state = ( isset($this->_data->state) )
							 ? $this->_data->state
							 : NULL;

		$this->_tax->zip = ( isset($this->_data->zip) )
							 ? $this->_data->zip
							 : NULL;

		$this->_tax->country = ( isset($this->_data->country) )
							 ? $this->_data->country
							 : NULL;

		$this->_tax->visible = ( isset($this->_data->visible) AND ! $this->_data->visible )
							 ? FALSE
							 : TRUE;
		
		$this->_validate_tax($this->_tax);

		$this->_tax->save();

		if( $this->_tax->code == "AUTOGENERATE" )
		{
			$this->_tax->code = substr(strtolower(str_replace(' ','',$this->_tax->name)),0,10).$this->_tax->id;
			$this->_tax->save();
		}

		return (object)array(
			"tax" => $this->_return_tax_element($this->_tax),
		);
	}
}