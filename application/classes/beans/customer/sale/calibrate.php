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
@action Beans_Customer_Sale_Calibrate
@description Re-calibrate the transactions tied to a particular sale.  This is used mostly by internal functions.  Requires at least one parameter to be provided.
@required auth_uid
@required auth_key
@required auth_expiration
@optional ids ARRAY The IDs of the #Beans_Customer_Sale# objects to calibrate.
@optional date_before STRING Filter for all sales before a specific date.
@optional date_after STRING Filter for all sales after a specific date.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Calibrate extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}	

	protected function _execute()
	{
		$customer_sale_calibrate_create = new Beans_Customer_Sale_Calibrate_Create($this->_beans_data_auth((object)array(
			'ids' => isset($this->_data->ids) ? $this->_data->ids : NULL,
			'date_after' => isset($this->_data->date_after) ? $this->_data->date_after : NULL,
			'date_before' => isset($this->_data->date_before) ? $this->_data->date_before : NULL,
		)));
		$customer_sale_calibrate_create_result = $customer_sale_calibrate_create->execute();

		if( ! $customer_sale_calibrate_create_result->success )
			throw new Exception("Unexpected error - could not calibrate created sales: ".$customer_sale_calibrate_create_result->error);

		$customer_sale_calibrate_invoice = new Beans_Customer_Sale_Calibrate_Invoice($this->_beans_data_auth((object)array(
			'ids' => isset($this->_data->ids) ? $this->_data->ids : NULL,
			'date_after' => isset($this->_data->date_after) ? $this->_data->date_after : NULL,
			'date_before' => isset($this->_data->date_before) ? $this->_data->date_before : NULL,
		)));
		$customer_sale_calibrate_invoice_result = $customer_sale_calibrate_invoice->execute();

		if( ! $customer_sale_calibrate_invoice_result->success )
			throw new Exception("Unexpected error - could not calibrate invoiced sales: ".$customer_sale_calibrate_invoice_result->error);

		$customer_sale_calibrate_cancel = new Beans_Customer_Sale_Calibrate_Cancel($this->_beans_data_auth((object)array(
			'ids' => isset($this->_data->ids) ? $this->_data->ids : NULL,
			'date_after' => isset($this->_data->date_after) ? $this->_data->date_after : NULL,
			'date_before' => isset($this->_data->date_before) ? $this->_data->date_before : NULL,
		)));
		$customer_sale_calibrate_cancel_result = $customer_sale_calibrate_cancel->execute();

		if( ! $customer_sale_calibrate_cancel_result->success )
			throw new Exception("Unexpected error - could not calibrate cancelled sales: ".$customer_sale_calibrate_cancel_result->error);

		return (object)array();
	}
}