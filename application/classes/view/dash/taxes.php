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


class View_Dash_Taxes extends View_Template {

	public function report_date_start()
	{
		if( ! isset($this->report_taxes_result) )
			return $this->default_date_start;

		return $this->report_taxes_result->data->date_start;
	}

	public function report_date_end()
	{
		if( ! isset($this->report_taxes_result) )
			return $this->default_date_end;

		return $this->report_taxes_result->data->date_end;
	}

	public function report_tax_id()
	{
		if( ! isset($this->report_taxes_result) )
			return FALSE;

		return $this->report_taxes_result->data->tax_id;
	}

	public function report_tax_name()
	{
		if( ! isset($this->report_taxes_result) )
			return FALSE;

		return $this->report_taxes_result->data->tax_name;
	}

	public function report_run()
	{
		return ( isset($this->report_taxes_result) );
	}

	public function sales()
	{
		$return_array = array();

		if( ! isset($this->report_taxes_result) )
			return FALSE;

		foreach( $this->report_taxes_result->data->sales as $sale )
			$return_array[] = $this->_sale_array($sale);

		return $return_array;
	}

	public function sale_totals()
	{
		if( ! isset($this->report_taxes_result) )
			return FALSE;

		return $this->_sale_array($this->report_taxes_result->data->sale_totals);
	}

	protected function _sale_array($sale)
	{
		$settings = $this->beans_settings();

		$return_array = array();

		$return_array['id'] = ( isset($sale->id) ) ? $sale->id : FALSE;
		$return_array['date'] = ( isset($sale->date_created) ) ? $sale->date_created : FALSE;
		$return_array['sale_number'] = ( isset($sale->sale_number) ) ? $sale->sale_number : FALSE;
		
		$return_array['subtotal_formatted'] = 
			( $sale->subtotal < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($sale->subtotal),2,'.',',').
			( $sale->subtotal < 0 ? '</span>' : '' );

		$return_array['taxes_formatted'] =
			( $sale->taxes < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($sale->taxes),2,'.',',').
			( $sale->taxes < 0 ? '</span>' : '' );

		$return_array['total_formatted'] =
			( $sale->total < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($sale->total),2,'.',',').
			( $sale->total < 0 ? '</span>' : '' );

		return $return_array;
	}
}