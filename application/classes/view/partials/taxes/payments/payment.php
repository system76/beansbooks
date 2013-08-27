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

class View_Partials_Taxes_Payments_Payment extends KOstache {
	// Receives $this->payment
	
	public function id()
	{
		return $this->payment->id;
	}

	public function tax()
	{
		return array(
			'name' => $this->payment->tax->name,
		);
	}

	public function date()
	{
		return $this->payment->date;
	}

	public function date_start()
	{
		return $this->payment->date_start;
	}

	public function date_end()
	{
		return $this->payment->date_end;
	}

	public function amount_formatted()
	{
		$amount = $this->payment->amount;

		return ( $amount < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($amount),2,'.',',');
	}

	public function payment_account()
	{
		return array(
			'name' => ( isset($this->payment->payment_account->name) ) 
				   ? $this->payment->payment_account->name
				   : "None",
		);
	}

}