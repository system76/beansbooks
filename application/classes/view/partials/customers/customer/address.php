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


class View_Partials_Customers_Customer_Address extends Kostache {
	// Receives $this->address
	// Receives $this->customer

	public function id()
	{
		return $this->address->id;
	}

	public function address1()
	{
		return $this->address->address1;
	}

	public function address2()
	{
		return $this->address->address2;
	}

	public function city()
	{
		return $this->address->city;
	}

	public function state()
	{
		return $this->address->state;	
	}

	public function zip()
	{
		return $this->address->zip;
	}

	public function country()
	{
		return $this->address->country;
	}

	public function country_full()
	{
		return Helper_Address::CountryName($this->address->country);
	}

	public function default_shipping()
	{
		return ( $this->address->id == $this->customer->default_shipping_address_id )
			? TRUE
			: FALSE;
	}

	public function default_billing()
	{
		return ( $this->address->id == $this->customer->default_billing_address_id )
			? TRUE
			: FALSE;
	}

}