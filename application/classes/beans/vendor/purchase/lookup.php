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
@action Beans_Vendor_Purchase_Lookup
@description Look up a vendor purchase.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Purchase# to retrieve.
@returns purchase OBJECT The #Beans_Vendor_Purchase# that was requested.
@returns transactions ARRAY An array of #Beans_Transaction# objects tied to the purchase.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Lookup extends Beans_Vendor_Purchase {

	protected $_id;
	protected $_purchase;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("Purchase purchase could not be found.");

		$account_transaction_form_search = new Beans_Account_Transaction_Search_Form($this->_beans_data_auth((object)array(
			'form_id' => $this->_purchase->id,
		)));
		$account_transaction_form_search_result = $account_transaction_form_search->execute();

		if( ! $account_transaction_form_search_result->success )
			throw new Exception("An error occurred in looking up the transactions for this invoice: ".$account_transaction_form_search_result->error);

		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
			"transactions" => $account_transaction_form_search_result->data->transactions,
		);
	}
}