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
@action Beans_Vendor_Payment_Update_Check
@description Update a check number on a vendor payment.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Payment# to update.
@optional check_number STRING A transaction or check number.
@returns payment OBJECT The updated #Beans_Vendor_Payment#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Payment_Update_Check extends Beans_Vendor_Payment {

	protected $_auth_role_perm = "vendor_payment_write";
	
	protected $_id;
	protected $_data;
	protected $_payment;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;
		
		$this->_data = $data;
		
		$this->_payment = $this->_load_vendor_payment($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_payment->loaded() )
			throw new Exception("Payment could not be found.");

		if( $this->_payment->account_transactions->where('account_reconcile_id','IS NOT',NULL)->count_all() )
			throw new Exception("Payment cannot be changed after it has been reconciled.");

		if( isset($this->_data->check_number) AND 
			strlen($this->_data->check_number) > 16 )
			throw new Exception("Invalid payment check number: can be no more than 16 characters.");

		if( isset($this->_data->check_number) )
			$this->_payment->reference = $this->_data->check_number;
		
		$this->_payment->save();

		return (object)array(
			"payment" => $this->_return_vendor_payment_element($this->_payment),
		);
	}
}