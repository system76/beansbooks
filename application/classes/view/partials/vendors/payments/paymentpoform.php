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


class View_Partials_Vendors_Payments_Paymentpoform extends KOstache {
	// Receives $this->purchase

	public function id()
	{
		return $this->purchase->id;
	}

	public function purchase_number()
	{
		return $this->purchase->purchase_number;
	}

	public function invoice_number()
	{
		return $this->purchase->invoice_number;
	}
	
	public function so_number()
	{
		return $this->purchase->so_number;
	}

	public function date_billed()
	{
		return $this->purchase->date_billed;
	}
	
	public function balance_flipped()
	{
		return ( $this->purchase->balance );
	}

	public function balance_flipped_formatted()
	{
		return ( $this->purchase->balance < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->purchase->balance),2,'.',',');
	}

	public function remit_address_id()
	{
		if( ! $this->purchase->remit_address )
			return FALSE;

		return $this->purchase->remit_address->id;
	}

}
