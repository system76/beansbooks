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


class View_Partials_Customers_Customer_Customer extends Kostache {
	// Receives $this->customer
	
	public function id()
	{
		return $this->customer->id;
	}

	public function first_name()
	{
		return $this->customer->first_name;
	}

	public function last_name()
	{
		return $this->customer->last_name;
	}

	public function email()
	{
		return $this->customer->email;
	}

	public function phone_number()
	{
		return $this->customer->phone_number;
	}

	/**
	 * Get the customer's company name for the template.
	 *
	 * @return string
	 */
	public function company_name()
	{
		return $this->customer->company_name;
	}

	public function balance_current_formatted() {
		return (( $this->customer->balance_pastdue + $this->customer->balance_pending ) < 0 ? '-' : '').$this->_company_currency().number_format(abs(( $this->customer->balance_pastdue + $this->customer->balance_pending )),2,'.',',');
	}

	public function balance_pastdue_formatted() {
		$beans_settings = $this->beans_settings();

		return ($this->customer->balance_pastdue < 0 ? '-' : '').$this->_company_currency().number_format(abs($this->customer->balance_pastdue),2,'.',',');
	}

}
