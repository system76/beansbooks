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
@action Beans_Customer_Sale_Lookup
@description Look up a customer sale.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer_Sale# to retrieve.
@returns sale OBJECT The #Beans_Customer_Sale# that was requested.
@returns transactions ARRAY The #Beans_Transaction# objects tied to the sale.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Lookup extends Beans_Customer_Sale {

	protected $_id;
	protected $_sale;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("Sale could not be found.");

		$account_transaction_form_search = new Beans_Account_Transaction_Search_Form($this->_beans_data_auth((object)array(
			'form_id' => $this->_sale->id,
		)));
		$account_transaction_form_search_result = $account_transaction_form_search->execute();

		if( ! $account_transaction_form_search_result->success )
			throw new Exception("An error occurred in looking up the transactions for this sale: ".$account_transaction_form_search_result->error);

		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
			"transactions" => $account_transaction_form_search_result->data->transactions,
		);
	}
}