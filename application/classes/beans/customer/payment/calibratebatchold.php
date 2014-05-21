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
class Beans_Customer_Payment_Calibratebatchold extends Beans_Customer_Payment {

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
		$calibrated_payment_ids = array();

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

		$payments = ORM::Factory('transaction')->
			where('payment','=','customer')->
			where('date','>=',$date_start)->
			where('date','<=',$date_end)->
			order_by('date','asc')->
			order_by('id','asc')->
			find_all();

		foreach( $payments as $payment )
		{
			$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
				'id' => $payment->id,
			)));
			$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

			if( ! $customer_payment_calibrate_result->success )
				throw new Exception(
					"Error calibrating payment ".$payment->id.": ".
					$customer_payment_calibrate_result->error.
					$customer_payment_calibrate_result->auth_error.
					$customer_payment_calibrate_result->config_error
				);

			$calibrated_payment_ids[] = $payment->id;
		}
		
		return (object)array(
			'date_start' => $date_start,
			'date_end' => $date_end,
			'calibrated_payment_ids' => $calibrated_payment_ids,
		);
	}
}