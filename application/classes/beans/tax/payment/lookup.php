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
@action Beans_Tax_Payment_Lookup
@description Remove a tax payment.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax_Payment# being requested.
@returns payment OBJECT The requested #Beans_Tax_Payment#.
---BEANSENDSPEC---
*/
class Beans_Tax_Payment_Lookup extends Beans_Tax_Payment {

	protected $_auth_role_perm = "vendor_payment_read";

	protected $_id;
	protected $_payment;

	/**
	 * Look up a payment by ID.
	 * @param stdClass $data key => value
	 *                       'id' => ID to look up.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_payment = $this->_load_tax_payment($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_payment->loaded() )
			throw new Exception("Payment could not be found.");

		return (object)array(
			"payment" => $this->_return_tax_payment_element($this->_payment, TRUE),
		);
	}
}