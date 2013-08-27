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


class View_Partials_Taxes_Taxes_Tax extends KOstache {
	// Receives $this->tax
	
	public function id()
	{
		return $this->tax->id;
	}

	public function name()
	{
		return $this->tax->name;
	}

	public function percent()
	{
		return ( $this->tax->percent * 100 ).'%';
	}

	public function balance()
	{
		return ( $this->tax->balance < 0 ? '-' : '' ).$this->_company_currency().number_format(abs(($this->tax->balance)),2,'.',',');
	}

	public function nextduedate()
	{
		return ( $this->tax->date_due ? $this->tax->date_due : "Not Set." );
	}

}
