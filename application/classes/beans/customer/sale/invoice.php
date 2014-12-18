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
@action Beans_Customer_Sale_Invoice
@description Convert a customer sale into an invoice.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Customer_Sale#.
@optional date_billed STRING The bill date in YYYY-MM-DD for the sale.  Default is today.
@optional date_due STRING The due date in YYYY-MM-DD for the sale.  If not provided, will default to the date_billed + the terms days on the AR #Beans_Account#.
@returns sale OBJECT The updated #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Invoice extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_id;
	protected $_sale;
	protected $_date_billed;
	protected $_date_due;		// Override.

	protected $_validate_only;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_validate_only = ( isset($data->validate_only) AND 
								  $data->validate_only )
							  ? TRUE
							  : FALSE;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);

		// Date Billed defaults to today, or the SO date if it is in the future.
		$this->_date_billed = ( isset($data->date_billed) )
							? $data->date_billed
							: ( 
								strtotime(date("Y-m-d")) < strtotime($this->_sale->date_created) 
								? $this->_sale->date_created
								: date("Y-m-d") 
							);

		$this->_date_due = ( isset($data->date_due) )
						 ? $data->date_due
						 : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("That sale could not be found.");

		if( $this->_sale->date_cancelled )
			throw new Exception("A sale cannot be converted to an invoice after it has been cancelled.");

		if( $this->_sale->date_billed )
			throw new Exception("That sale has already been converted to an invoice.");

		if( $this->_date_billed != date("Y-m-d",strtotime($this->_date_billed)) )
			throw new Exception("Invalid invoice date: must be in YYYY-MM-DD format.");

		if( strtotime($this->_date_billed) < strtotime($this->_sale->date_created) )
			throw new Exception("Invalid invoice date: must be on or after the creation date of ".$this->_sale->date_created.".");

		if( $this->_date_due AND 
			$this->_date_due != date("Y-m-d",strtotime($this->_date_due)) )
			throw new Exception("Invalid due date: must be in YYYY-MM-DD format.");

		if( $this->_date_due AND 
			strtotime($this->_date_due) < strtotime($this->_date_billed) )
			throw new Exception("Invalid due date: must be on or after the bill date.");

		if( $this->_sale->total == 0.00 )
			throw new Exception("Cannot invoice a sale for $0.00 - but it can be cancelled.");

		$this->_sale->date_billed = $this->_date_billed;
		$this->_sale->date_due = ( $this->_date_due )
							   ? $this->_date_due
							   : date("Y-m-d",strtotime($this->_sale->date_billed.' +'.$this->_sale->account->terms.' Days'));
		$this->_sale->save();

		$sale_calibrate = new Beans_Customer_Sale_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_sale->id),
		)));
		$sale_calibrate_result = $sale_calibrate->execute();

		if( ! $sale_calibrate_result->success )
		{
			$this->_sale->date_billed = NULL;
			$this->_sale->date_due = NULL;
			$this->_sale->save();

			throw new Exception("Error trying to invoice sale: ".$sale_calibrate_result->error);
		}

		// Reload the sale.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);

		// Recalibrate Payments 
		$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
			'form_ids' => array($this->_sale->id),
		)));
		$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

		if( ! $customer_payment_calibrate_result->success )
			throw new Exception("Error encountered when calibrating payments: ".$customer_payment_calibrate_result->error);
		
		// Update tax items only if we're successful.
		$tax_item_action = 'invoice';
		if( $this->_sale->refund_form_id && 
			$this->_sale->refund_form_id < $this->_sale->id )
			$tax_item_action = 'refund';
		
		$this->_update_form_tax_items($this->_sale->id, $tax_item_action);
		
		$this->_sale->save();

		// Reload Sale per Payment Calibration.
		$this->_sale = $this->_load_customer_sale($this->_sale->id);
		
		return (object)array(
			"sale" => $this->_return_customer_sale_element($this->_sale),
		);
	}
}