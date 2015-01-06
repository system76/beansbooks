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

class View_Vendors_Print_Taxpayment extends View_Print {

	public function noheader()
	{
		return TRUE;
	}

	public function tax()
	{
		if( ! isset($this->payment) ||
			! $this->payment )
			return FALSE;

		$return_array = array();

		$return_array['name'] = $this->payment->tax->name;
		$return_array['authority'] = $this->payment->tax->authority;
		
		$return_array['address'] = array();
		$return_array['address']['address1'] = $this->payment->tax->address1;
		$return_array['address']['address2'] = $this->payment->tax->address2;
		
		if( $this->payment->tax->city || 
			$this->payment->tax->state || 
			$this->payment->tax->zip )
			$return_array['address']['citystatezip'] = TRUE;

		$return_array['address']['city'] = $this->payment->tax->city;
		$return_array['address']['state'] = $this->payment->tax->state;
		$return_array['address']['zip'] = $this->payment->tax->zip;

		$return_array['address']['country'] = $this->_country_name($this->payment->tax->country);

		return $return_array;
	}

	public function payment()
	{
		if( ! isset($this->payment) ||
			! $this->payment )
			return FALSE;

		$return_array = array();

		$return_array['invoiced_line_amount_formatted'] = $this->_format_beans_number($this->payment->invoiced_line_amount);
		$return_array['invoiced_line_taxable_amount_formatted'] = $this->_format_beans_number($this->payment->invoiced_line_taxable_amount);
		$return_array['invoiced_amount_formatted'] = $this->_format_beans_number($this->payment->invoiced_amount);
		$return_array['refunded_line_amount_formatted'] = $this->_format_beans_number($this->payment->refunded_line_amount);
		$return_array['refunded_line_taxable_amount_formatted'] = $this->_format_beans_number($this->payment->refunded_line_taxable_amount);
		$return_array['refunded_amount_formatted'] = $this->_format_beans_number($this->payment->refunded_amount);
		$return_array['net_line_amount_formatted'] = $this->_format_beans_number($this->payment->net_line_amount);
		$return_array['net_line_taxable_amount_formatted'] = $this->_format_beans_number($this->payment->net_line_taxable_amount);
		$return_array['net_amount_formatted'] = $this->_format_beans_number($this->payment->net_amount);
		
		$return_array['date_start_formatted'] = date("m/d/Y", strtotime($this->payment->date_start));
		$return_array['date_end_formatted'] = date("m/d/Y", strtotime($this->payment->date_end));

		$return_array['check_number'] = $this->payment->check_number;

		$return_array['paid_amount_formatted'] = FALSE;
		$return_array['writeoff_amount_formatted'] = FALSE;

		if( $this->payment->amount )
			$return_array['paid_amount_formatted'] = $this->_format_beans_number($this->payment->amount - $this->payment->writeoff_amount);

		if( $this->payment->writeoff_amount )
			$return_array['writeoff_amount_formatted'] = $this->_format_beans_number($this->payment->writeoff_amount);

		return $return_array;
	}

	public function invoiced_liabilities()
	{
		if( ! isset($this->payment) || 
			! $this->payment )
			return FALSE;

		$return_array = array();

		foreach( $this->payment->liabilities as $liability )
		{
			if( $liability->type == "invoice" )
				$return_array[] = $this->_liability_array($liability);
		}

		return $return_array;
	}

	public function refunded_liabilities()
	{
		if( ! isset($this->payment) ||
			! $this->payment )
			return FALSE;

		$return_array = array();

		foreach( $this->payment->liabilities as $liability )
		{
			if( $liability->type == "refund" )
				$return_array[] = $this->_liability_array($liability);
		}

		return $return_array;
	}

	private function _liability_array($liability)
	{
		$return_array = $this->_convert_object_to_array($liability);

		$return_array['includes_exemptions'] = ( $return_array['form_line_amount'] != $return_array['form_line_taxable_amount'] ) 
											 ? TRUE 
											 : FALSE;

		$return_array['form_line_amount_formatted'] = $this->_format_beans_number($return_array['form_line_amount']);
		$return_array['form_line_taxable_amount_formatted'] = $this->_format_beans_number($return_array['form_line_taxable_amount']);
		$return_array['amount_formatted'] = $this->_format_beans_number($return_array['amount']);

		$return_array['exempted_lines'] = array();

		foreach( $return_array['lines'] as $index => $line )
		{
			$return_array['lines'][$index]['total_formatted'] = $this->_format_beans_number($line['total']);

			if( $line['tax_exempt'] )
				$return_array['exempted_lines'][] = $return_array['lines'][$index];
		}

		$return_array['has_exempted_lines'] = ( count($return_array['exempted_lines']) )
											? TRUE 
											: FALSE;

		return $return_array;
	}

}