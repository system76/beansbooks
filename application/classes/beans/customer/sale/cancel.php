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
@action Beans_Customer_Sale_Cancel
@description Cancel a customer sale. This should be called if you cannot delete a sale because it has payments tied to it.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer_Sale# to cancel.
@optional date_cancelled STRING The cancel date in YYYY-MM-DD for the sale.  If not provided, will default to today.
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Cancel extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_id;
	protected $_sale;
	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("Sale could not be found.");

		if( $this->_sale->date_cancelled || 
			$this->_sale->cancel_transaction_id )
			throw new Exception("Sale has already been cancelled.");

		if( $this->_check_books_closed($this->_sale->date_created) )
			throw new Exception("Sale could not be cancelled.  The financial year has been closed already.");

		if( $this->_sale->refund_form_id AND 
			$this->_sale->refund_form_id > $this->_sale->id )
			throw new Exception("Sale could not be cancelled - it has a refund attached to it.");

		$date_cancelled = date("Y-m-d");

		if( isset($this->_data->date_cancelled) )
			$date_cancelled = $this->_data->date_cancelled;

		if( $date_cancelled != date("Y-m-d",strtotime($date_cancelled)) )
			throw new Exception("Invalid cancellation date: must be in YYYY-MM-DD format.");

		if( strtotime($date_cancelled) < strtotime($this->_sale->date_created) )
			throw new Exception("Invalid cancellation date: must be on or after the creation date of ".$this->_sale->date_created.".");

		if( $this->_sale->date_billed AND 
			strtotime($this->_sale->date_billed) > strtotime($date_cancelled) )
			throw new Exception("Invalid cancellation date: must be after the invoice date of ".$this->_sale->date_billed.".");

		$this->_sale->date_cancelled = $date_cancelled;
		$this->_sale->save();

		$sale_calibrate = new Beans_Customer_Sale_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_sale->id),
		)));
		$sale_calibrate_result = $sale_calibrate->execute();

		if( ! $sale_calibrate_result->success )
		{
			$this->_sale->date_cancelled = NULL;
			$this->_sale->save();

			throw new Exception("Error trying to cancel sale: ".$sale_calibrate_result->error);
		}

		// Reload Sale
		$this->_sale = $this->_load_customer_sale($this->_sale->id);

		// If we're successful - reverse taxes.
		if( $this->_sale->date_billed )
		{
			foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
				$this->_tax_adjust_balance($sale_tax->tax_id,( -1 * $sale_tax->total) );
		}

		// Remove the refund form from the corresponding form.
		if( $this->_sale->refund_form->loaded() )
		{
			$this->_sale->refund_form->refund_form_id = NULL;
			$this->_sale->refund_form->save();
		}
		
		// Recalibrate Payments
		$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
			'form_ids' => array($this->_sale->id),
		)));
		$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

		if( ! $customer_payment_calibrate_result->success )
			throw new Exception("Error encountered when calibrating payments: ".$customer_payment_calibrate_result->error);

		// Reload Sale per Payment Calibration.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}