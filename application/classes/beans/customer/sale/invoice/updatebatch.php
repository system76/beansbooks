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
class Beans_Customer_Sale_Invoice_Updatebatch extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_payment_write";

	protected $_date;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_date = ( isset($data->date) ) 
					 ? $data->date
					 : FALSE;
	}

	protected function _execute()
	{
		$updated_invoice_ids = array();

		if( $this->_date != date("Y-m-d",strtotime($this->_date)) AND 
			$this->_date != date("Y-m",strtotime($this->_date.'-01')) )
			throw new Exception("Invalid date: must be in YYYY-MM-DD or YYYY-MM format.");


		$date_start = $this->_date;
		$date_end = $this->_date;

		if( strlen($date_start) == 7 )
		{
			$date_start = $date_start.'-01';
			$date_end = date("Y-m-t",strtotime($date_start));
		}

		$invoices = ORM::Factory('form')->
			where('type','=','sale')->
			where('date_billed','>=',$date_start)->
			where('date_billed','<=',$date_end)->
			order_by('date_billed','asc')->
			order_by('id','asc')->
			find_all();

		foreach( $invoices as $invoice )
		{
			$customer_invoice_update = new Beans_Customer_Sale_Invoice_Update($this->_beans_data_auth((object)array(
				'id' => $invoice->id,
			)));
			$customer_invoice_update_result = $customer_invoice_update->execute();

			if( ! $customer_invoice_update_result->success )
				throw new Exception(
					"Error updating invoice ".$invoice->id.": ".
					$customer_invoice_update_result->error.
					$customer_invoice_update_result->auth_error.
					$customer_invoice_update_result->config_error
				);

			$updated_invoice_ids[] = $invoice->id;
		}
		
		return (object)array(
			'date_start' => $date_start,
			'date_end' => $date_end,
			'updated_invoice_ids' => $updated_invoice_ids,
		);
	}
}