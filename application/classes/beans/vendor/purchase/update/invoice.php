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

class Beans_Vendor_Purchase_Update_Invoice extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";
	
	protected $_data;
	protected $_validate_only;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_validate_only = ( isset($data->validate_only) AND 
								  $data->validate_only )
							  ? TRUE
							  : FALSE;
	}

	protected function _execute()
	{
		if( ! isset($this->_data->id) )
			throw new Exception("Purchase could not be found: no ID provided.");

		$purchase = $this->_load_vendor_purchase($this->_data->id);

		if( ! $purchase->loaded() )
			throw new Exception("Purchase could not be found.");

		if( ! $purchase->date_billed )
			throw new Exception("Purchase invoices cannot be updated before first creating the invoice.");

		$requires_calibration = FALSE;

		if( $this->_data->invoice_number AND 
			strlen($this->_data->invoice_number) > 16 )
			throw new Exception("Invalid invoice number: maximum of 16 characters.");

		if( $this->_data->invoice_number )
			$purchase->aux_reference = $this->_data->invoice_number;

		if( $this->_data->date_billed AND 
			$this->_data->date_billed != date("Y-m-d",strtotime($this->_data->date_billed)) )
			throw new Exception("Invalid invoice date: must be in YYYY-MM-DD format.");

		if( $this->_data->date_billed != $purchase->date_billed )
		{
			$requires_calibration = TRUE;
			$purchase->date_billed = $this->_data->date_billed;
		}

		$this->_validate_vendor_purchase($purchase);

		if( $this->_validate_only )
			return (object)array();
		
		$purchase->save();

		if( $requires_calibration )
		{
			$vendor_purchase_calibrate = new Beans_Vendor_Purchase_Calibrate($this->_beans_data_auth((object)array(
				'ids' => array($purchase->id),
			)));
			$vendor_purchase_calibrate_result = $vendor_purchase_calibrate->execute();

			if( ! $vendor_purchase_calibrate_result->success )
				throw new Exception("UNEXPECTED ERROR: COULD NOT CALIBRATE VENDOR PURCHASE: ".$vendor_purchase_calibrate_result->error);

			// Reload Purchase
			$purchase = $this->_load_vendor_purchase($this->_data->id);
		}

		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($purchase),
		);
	}
}