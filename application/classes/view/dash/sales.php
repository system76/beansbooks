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


class View_Dash_Sales extends View_Template {
	
	public function report_date_start()
	{
		if( ! isset($this->report_sales_result) )
			return FALSE;

		return $this->report_sales_result->data->date_start;
	}

	public function report_date_end()
	{
		if( ! isset($this->report_sales_result) )
			return FALSE;

		return $this->report_sales_result->data->date_end;
	}

	public function interval_options()
	{
		$return_array = array();

		$return_array[] = array(
			'value' => 'day',
			'name' => 'Day',
		);
		$return_array[] = array(
			'value' => 'week',
			'name' => 'Week',
		);
		$return_array[] = array(
			'value' => 'month',
			'name' => 'Month',
		);
		$return_array[] = array(
			'value' => 'year',
			'name' => 'Year',
		);

		if( ! isset($this->report_sales_result) )
			return $return_array;

		foreach( $return_array as $index => $option )
			if( $option['value'] == $this->report_sales_result->data->interval )
				$return_array[$index]['selected'] = TRUE;

		return $return_array;
	}

	public function interval()
	{
		if( ! isset($this->report_sales_result) )
			return FALSE;

		return ucwords($this->report_sales_result->data->interval);
	}

	public function date_ranges()
	{
		$return_array = array();

		if( ! isset($this->report_sales_result) )
			return FALSE;

		foreach( $this->report_sales_result->data->date_ranges as $date_range )
			$return_array[] = $this->_date_range_array($date_range);

		return $return_array;
	}

	public function net()
	{
		if( ! isset($this->report_sales_result) )
			return FALSE;

		return $this->_date_range_array($this->report_sales_result->data->net);
	}

	private function _date_range_array($date_range)
	{
		$settings = $this->beans_settings();

		$return_array = array();

		$return_array['date'] = ( $date_range->date_start == $date_range->date_end )
							  ? date('m/d/Y',strtotime($date_range->date_start))
							  : date('m/d/Y',strtotime($date_range->date_start)).' - '.date('m/d/Y',strtotime($date_range->date_end));
		
		$return_array['orders_formatted'] = number_format($date_range->orders,0,'',',');
		
		$return_array['items_formatted'] = number_format($date_range->items,0,'',',');

		$return_array['subtotal_formatted'] = 
			( $date_range->subtotal < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($date_range->subtotal),2,'.',',').
			( $date_range->subtotal < 0 ? '</span>' : '' );

		$return_array['taxes_formatted'] =
			( $date_range->taxes < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($date_range->taxes),2,'.',',').
			( $date_range->taxes < 0 ? '</span>' : '' );

		$return_array['total_formatted'] =
			( $date_range->total < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($date_range->total),2,'.',',').
			( $date_range->total < 0 ? '</span>' : '' );

		return $return_array;
	}
}