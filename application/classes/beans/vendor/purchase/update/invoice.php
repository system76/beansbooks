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
	protected $_id;
	protected $_purchase;
	protected $_invoice_number;
	protected $_validate_only;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_validate_only = ( isset($data->validate_only) AND 
								  $data->validate_only )
							  ? TRUE
							  : FALSE;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);

		$this->_invoice_number = ( isset($data->invoice_number) )
							   ? $data->invoice_number
							   : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("Purchase could not be found.");

		if( $this->_invoice_number AND 
			strlen($this->_invoice_number) > 16 )
			throw new Exception("Invalid invoice number: maximum of 16 characters.");

		if( $this->_invoice_number )
			$this->_purchase->aux_reference = $this->_invoice_number;

		$this->_validate_vendor_purchase($this->_purchase);

		if( $this->_validate_only )
			return (object)array();
		
		$this->_purchase->save();

		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
		);
	}
}