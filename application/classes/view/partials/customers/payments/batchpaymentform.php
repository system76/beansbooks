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

class View_Partials_Customers_Payments_Batchpaymentform extends KOstache {
	// Receives $this->sale

	public function id()
	{
		return $this->sale->id;
	}

	public function customer()
	{
		return array(
			'id' => $this->sale->customer->id,
			'name' => $this->sale->customer->display_name,
		);
	}

	public function sale_number()
	{
		return $this->sale->sale_number;
	}
	
	public function date_due()
	{
		return $this->sale->date_due;
	}

	public function balance_flipped()
	{
		return ($this->sale->balance * -1);
	}

	public function balance_flipped_formatted()
	{
		return ( $this->sale->balance > 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->sale->balance),2,'.',',');
	}

}