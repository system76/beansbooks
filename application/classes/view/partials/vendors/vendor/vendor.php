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

class View_Partials_Vendors_Vendor_Vendor extends Kostache {
	// Receives $this->vendor
	
	public function id()
	{
		return $this->vendor->id;
	}

	public function company_name()
	{
		return $this->vendor->company_name;
	}

	public function email()
	{
		return $this->vendor->email;
	}

	public function phone_number()
	{
		return $this->vendor->phone_number;
	}

	public function balance_current_formatted() {
		return (( $this->vendor->balance_pastdue + $this->vendor->balance_pending ) < 0 ? '-' : '').$this->_company_currency().number_format(abs(( $this->vendor->balance_pastdue + $this->vendor->balance_pending )),2,'.',',');
	}

	public function balance_pastdue_formatted() {
		return ($this->vendor->balance_pastdue < 0 ? '-' : '').$this->_company_currency().number_format(abs($this->vendor->balance_pastdue),2,'.',',');
	}

}
