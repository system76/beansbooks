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

class View_Vendors_Print_Expense extends View_Print {

	// Receives $this->expense

	public function vendor()
	{
		return array(
			'name' => $this->expense->vendor->display_name,
			'email' => $this->expense->vendor->email,
			'phone' => $this->expense->vendor->phone_number,
		);
	}

	public function expense_number()
	{
		return $this->expense->expense_number;
	}

	public function expense_date_formatted()
	{
		return date("F j, Y",strtotime($this->expense->date_created));
	}

	public function invoice_number()
	{
		return $this->expense->invoice_number;
	}

	public function so_number()
	{
		return $this->expense->so_number;
	}

	public function check_number()
	{
		return $this->expense->check_number;
	}

	public function total_formatted()
	{
		$beans_settings = parent::beans_settings();

		return ( $this->expense->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->expense->total),2,'.',',');
	}

	protected $_expense_lines = FALSE;
	public function expense_lines()
	{
		if( $this->_expense_lines )
			return $this->_expense_lines;

		$beans_settings = parent::beans_settings();

		$this->_expense_lines = array();

		$i = 0;
		foreach( $this->expense->lines as $expense_line )
			$this->_expense_lines[] = array(
				'odd' => ( $i++ % 2 == 0 ? TRUE : FALSE ),
				'description' => $expense_line->description,
				'qty_formatted' => number_format($expense_line->quantity,2,'.',','),
				'price_formatted' => $beans_settings->company_currency.number_format($expense_line->amount,2,'.',','),
				'total_formatted' => ( $expense_line->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($expense_line->total),2,'.',','),
			);

		return $this->_expense_lines;
	}

}