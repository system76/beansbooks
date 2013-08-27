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


class View_Partials_Vendors_Expenses_Expense extends KOstache {
	// Receives $this->expense
	
	public function id()
	{
		return $this->expense->id;
	}

	public function vendor()
	{
		return array(
			'id' => $this->expense->vendor->id,
			'name' => $this->expense->vendor->first_name.' '.$this->expense->vendor->last_name,
			'company_name' => $this->expense->vendor->company_name,
		);
	}

	public function date_created()
	{
		return $this->expense->date_created;
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

	public function total()
	{
		return $this->expense->total;
	}

	public function total_formatted()
	{
		return ( $this->expense->total < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->expense->total),2,'.',',');
	}

	public function balance()
	{
		return ( $this->expense->balance * -1 );
	}

	public function balance_flipped()
	{
		return $this->expense->balance;
	}

	public function balance_flipped_formatted()
	{
		return ( $this->expense->balance < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->expense->balance),2,'.',',');
	}

	public function can_cancel()
	{
		// Still working how when this should be T/F
		return ( ! $this->expense->refund_expense_id OR $this->expense->refund_expense_id < $this->expense->id ) ? TRUE : FALSE;
	}

	public function can_refund()
	{
		return ( ! $this->expense->refund_expense_id ) ? TRUE : FALSE;
	}
	
}