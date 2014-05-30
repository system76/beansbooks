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


class View_Partials_Vendors_Invoices_Purchase extends KOstache {
	// Receives $this->purchase
	
	public function cancelled()
	{
		return ( $this->purchase->date_cancelled )
			? TRUE 
			: FALSE;
	}

	public function invoiced()
	{
		return ( $this->purchase->date_billed )
			? TRUE
			: FALSE;
	}

	public function id()
	{
		return $this->purchase->id;
	}

	public function vendor_name()
	{
		return $this->purchase->vendor->display_name;
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
	
	public function total()
	{
		return number_format($this->purchase->total,2,'.','');
	}

	public function total_formatted()
	{
		return ( $this->purchase->total < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->purchase->total),2,'.',',');
	}

	public function dateYYYYMMDD() {
		return date("Y-m-d");
	}
	
}
