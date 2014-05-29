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

// This is a proxy function to both 
// Beans_Vendor_Purchase_Calibrate_Invoice 
// Beans_Vendor_Purchase_Calibrate_Cancel 
class Beans_Vendor_Purchase_Calibrate extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}	

	protected function _execute()
	{
		$vendor_purchase_calibrate_create = new Beans_Vendor_Purchase_Calibrate_Create($this->_beans_data_auth((object)array(
			'ids' => isset($this->_data->ids) ? $this->_data->ids : NULL,
			'date_after' => isset($this->_data->date_after) ? $this->_data->date_after : NULL,
			'date_before' => isset($this->_data->date_before) ? $this->_data->date_before : NULL,
		)));
		$vendor_purchase_calibrate_create_result = $vendor_purchase_calibrate_create->execute();

		if( ! $vendor_purchase_calibrate_create_result->success )
			throw new Exception("Unexpected error - could not calibrate purchases: ".$vendor_purchase_calibrate_create_result->error);

		$vendor_purchase_calibrate_invoice = new Beans_Vendor_Purchase_Calibrate_Invoice($this->_beans_data_auth((object)array(
			'ids' => isset($this->_data->ids) ? $this->_data->ids : NULL,
			'date_after' => isset($this->_data->date_after) ? $this->_data->date_after : NULL,
			'date_before' => isset($this->_data->date_before) ? $this->_data->date_before : NULL,
		)));
		$vendor_purchase_calibrate_invoice_result = $vendor_purchase_calibrate_invoice->execute();

		if( ! $vendor_purchase_calibrate_invoice_result->success )
			throw new Exception("Unexpected error - could not calibrate invoiced purchases: ".$vendor_purchase_calibrate_invoice_result->error);

		$vendor_purchase_calibrate_cancel = new Beans_Vendor_Purchase_Calibrate_Cancel($this->_beans_data_auth((object)array(
			'ids' => isset($this->_data->ids) ? $this->_data->ids : NULL,
			'date_after' => isset($this->_data->date_after) ? $this->_data->date_after : NULL,
			'date_before' => isset($this->_data->date_before) ? $this->_data->date_before : NULL,
		)));
		$vendor_purchase_calibrate_cancel_result = $vendor_purchase_calibrate_cancel->execute();

		if( ! $vendor_purchase_calibrate_cancel_result->success )
			throw new Exception("Unexpected error - could not calibrate cancelled purchases: ".$vendor_purchase_calibrate_cancel_result->error);
		
		return (object)array();
	}
}