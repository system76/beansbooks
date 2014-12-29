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
@action Beans_Tax_Payment_Cancel
@description Remove a tax payment.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax_Payment# being removed.
---BEANSENDSPEC---
*/
class Beans_Tax_Payment_Cancel extends Beans_Tax_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

	protected $_id;
	protected $_payment;

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

		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_payment->transaction->id,
			'payment_type_handled' => 'tax',
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("Error cancelling tax payment: ".$account_transaction_delete_result->error);
		
		// Update tax 
		$this->_tax_payment_update_balance($this->_payment->tax_id);

		$paid_tax_items = ORM::Factory('tax_item')
			->where('tax_payment_id','=',$this->_payment->id)
			->find_all();

		foreach( $paid_tax_items as $paid_tax_item )
		{
			$paid_tax_item->tax_payment_id = NULL;
			$paid_tax_item->save();
		}

		$this->_payment->delete();

		return (object)array();
	}
}