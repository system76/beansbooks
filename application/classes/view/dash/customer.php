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


class View_Dash_Customer extends View_Template {

	public function report_date_start()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->report_customer_result->data->date_start;
	}

	public function report_date_end()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->report_customer_result->data->date_end;
	}

	public function report_customer_id()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->report_customer_result->data->customer_id;
	}

	public function report_customer_name()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->report_customer_result->data->customer_name;
	}

	public function report_title()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->report_customer_result->data->title;
	}

	public function report_line_range_label()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->report_customer_result->data->report_line_range_label;
	}

	public function report_lines()
	{
		$return_array = array();

		if( ! isset($this->report_customer_result) )
			return FALSE;

		foreach( $this->report_customer_result->data->report_lines as $report_line )
			$return_array[] = $this->_report_line_array($report_line);

		return $return_array;
	}

	public function line_totals()
	{
		if( ! isset($this->report_customer_result) )
			return FALSE;

		return $this->_report_line_array($this->report_customer_result->data->line_totals);
	}

	private function _report_line_array($report_line)
	{
		$settings = $this->beans_settings();

		$return_array = array();

		$return_array['label'] = ( isset($report_line->label) ) ? $report_line->label : FALSE;
		
		$return_array['orders_formatted'] = number_format($report_line->orders,0,'',',');
		
		$return_array['items_formatted'] = number_format($report_line->items,0,'',',');

		$return_array['subtotal_formatted'] = 
			( $report_line->subtotal < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($report_line->subtotal),2,'.',',').
			( $report_line->subtotal < 0 ? '</span>' : '' );

		$return_array['taxes_formatted'] =
			( $report_line->taxes < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($report_line->taxes),2,'.',',').
			( $report_line->taxes < 0 ? '</span>' : '' );

		$return_array['total_formatted'] =
			( $report_line->total < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($report_line->total),2,'.',',').
			( $report_line->total < 0 ? '</span>' : '' );

		return $return_array;
	}
}