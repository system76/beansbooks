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
@action Beans_Vendor_Payment_Cancel
@description Cancel a payment by deleting its transaction.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Vendor_Payment# to cancel.
---BEANSENDSPEC---
*/
class Beans_Vendor_Payment_Cancel extends Beans_Vendor_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

	protected $_id;
	protected $_date;
	protected $_payment;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_date = ( isset($data->date) )
					 ? $data->date
					 : FALSE;

		$this->_payment = $this->_load_vendor_payment($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_payment->loaded() )
			throw new Exception("Payment could not be found.");

		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_payment->id,
			'payment_type_handled' => 'vendor',
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("Error cancelling payment: ".$account_transaction_delete_result->error);
		
		return (object)array();
	}
}