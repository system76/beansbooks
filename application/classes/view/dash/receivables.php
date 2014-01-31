<?php defined('SYSPATH') or die('No direct access allowed.');
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


class View_Dash_Receivables extends View_Template {
	
	public function report_date()
	{
		if( ! isset($this->report_receivables_result) )
			return FALSE;

		if( $this->report_receivables_result->data->date == date("Y-m-d") )
			return FALSE;

		return $this->report_receivables_result->data->date;
	}

	public function report_customer_name()
	{
		if( ! isset($this->report_receivables_result) )
			return FALSE;

		if( ! $this->report_receivables_result->data->customer_id OR 
		 	count($this->report_receivables_result->data->customers) > 1 )
			return FALSE;

		foreach( $this->report_receivables_result->data->customers as $customer_id => $customer )
			return $customer->customer_name;
	}

	public function days_late_minimum_options()
	{
		$return_array = array();

		$return_array[] = array(
			'days' => 15,
		);

		$return_array[] = array(
			'days' => 30,
		);

		$return_array[] = array(
			'days' => 45,
		);

		$return_array[] = array(
			'days' => 60,
		);

		$return_array[] = array(
			'days' => 90,
		);

		if( ! isset($this->report_receivables_result) )
			return $return_array;

		foreach( $return_array as $index => $option )
			$return_array[$index]['selected'] = ( $option['days'] == $this->report_receivables_result->data->days_late_minimum ) ? TRUE : FALSE;

		return $return_array;
	}

	public function report_customer_id()
	{
		if( ! isset($this->report_receivables_result) )
			return FALSE;

		if( ! $this->report_receivables_result->data->customer_id OR 
		 	count($this->report_receivables_result->data->customers) > 1 )
			return FALSE;

		foreach( $this->report_receivables_result->data->customers as $customer_id => $customer )
			return $customer_id;
	}

	public function report_days_late_minimum()
	{
		if( ! isset($this->report_receivables_result) )
			return FALSE;

		return $this->report_receivables_result->data->days_late_minimum;
	}

	public function report_totals()
	{
		$return_array = array();

		$return_array['balance_total_formatted'] = 
			( $this->report_receivables_result->data->balance_total < 0 ? '<span class="text-red">-' : '' ).
			number_format(abs($this->report_receivables_result->data->balance_total),2,'.',',').
			( $this->report_receivables_result->data->balance_total < 0 ? '</span>' : '' );

		foreach( $this->report_receivables_result->data->balances as $days => $balance )
			$return_array['balance_'.$days.'_formatted'] = 
				( $balance < 0 ? '<span class="text-red">-' : '' ).
				number_format(abs($balance),2,'.',',').
				( $balance < 0 ? '</span>' : '' );

		$return_array['customer_count'] = count($this->report_receivables_result->data->customers);

		return $return_array;
	}

	public function customer_reports()
	{
		if( ! isset($this->report_receivables_result) )
			return FALSE;

		$return_array = array();

		foreach( $this->report_receivables_result->data->customers as $customer_report )
		{
			$return_array[] = $this->_customer_report_array($customer_report);
		}

		return $return_array;
	}

	public function _customer_report_array($customer_report)
	{
		$settings = $this->beans_settings();

		$return_array = array();

		$return_array['company_name'] = $customer_report->customer_company_name;
		$return_array['customer_name'] = $customer_report->customer_name;
		$return_array['phone_number'] = $customer_report->customer_phone_number;
		$return_array['balance_total_formatted'] = 
			( $customer_report->balance_total < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($customer_report->balance_total),2,'.',',').
			( $customer_report->balance_total < 0 ? '</span>' : '' );


		$return_array['customer_balance_total_formatted'] = 
			( $customer_report->balance_total < 0 ? '<span class="text-red">-' : '' ).
			number_format(abs($customer_report->balance_total),2,'.',',').
			( $customer_report->balance_total < 0 ? '</span>' : '' );

		foreach( $customer_report->balances as $days => $balance )
			$return_array['customer_balance_'.$days.'_formatted'] = 
				( $balance < 0 ? '<span class="text-red">-' : '' ).
				number_format(abs($balance),2,'.',',').
				( $balance < 0 ? '</span>' : '' );

		$return_array['sales'] = array();

		foreach( $customer_report->sales as $sale )
			$return_array['sales'][] = $this->_customer_report_sale_array($sale);

		return $return_array;
	}

	public function _customer_report_sale_array($sale)
	{
		$return_array = array();

		$return_array['date_created'] = $sale->date_created;
		$return_array['sale_id'] = $sale->id;
		$return_array['sale_number'] = $sale->sale_number;
		$return_array['date_due'] = $sale->date_due;
		$return_array['days_late'] = ( $sale->days_late > 0 ) ? $sale->days_late : FALSE;
		$return_array['balance_formatted'] = 
			( $sale->balance < 0 ? '<span class="text-red">-' : '' ).
			number_format(abs($sale->balance),2,'.',',').
			( $sale->balance < 0 ? '</span>' : '' );

		$balance_range = 'current';
		if( $sale->days_late >= 90 )
			$balance_range = '90';
		else if( $sale->days_late >= 60 )
			$balance_range = '60';
		else if( $sale->days_late >= 30 )
			$balance_range = '30';
		else if( $sale->days_late > 0 )
			$balance_range = '0';

		$return_array['balance_'.$balance_range.'_formatted'] = 
			( $sale->balance < 0 ? '<span class="text-red">-' : '' ).
			number_format(abs($sale->balance),2,'.',',').
			( $sale->balance < 0 ? '</span>' : '' );


		return $return_array;		
	}


}