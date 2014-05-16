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


class View_Partials_Vendors_Purchases_Purchase extends KOstache {
	// Receives $this->purchase
	
	public function id()
	{
		return $this->purchase->id;
	}

	public function vendor()
	{
		return array(
			'id' => $this->purchase->vendor->id,
			'name' => $this->purchase->vendor->first_name.' '.$this->purchase->vendor->last_name,
			'company_name' => $this->purchase->vendor->company_name,
			'email' => $this->purchase->vendor->email,
		);
	}

	public function date_created()
	{
		return $this->purchase->date_created;
	}

	public function purchase_number()
	{
		return $this->purchase->purchase_number;
	}

	public function so_number()
	{
		return $this->purchase->so_number;
	}

	public function quote_number()
	{
		return $this->purchase->quote_number;
	}

	public function total()
	{
		return $this->purchase->total;
	}

	public function total_formatted()
	{
		return ( $this->purchase->total < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->purchase->total),2,'.',',');
	}

	public function balance()
	{
		return ( $this->purchase->balance );
	}

	public function balance_flipped()
	{
		return ( $this->purchase->balance * -1);
	}

	public function balance_flipped_formatted()
	{
		return ( $this->purchase->balance > 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->purchase->balance),2,'.',',');
	}

	public function can_cancel()
	{
		return ( ! $this->purchase->date_cancelled )
			? TRUE
			: FALSE;
	}

	public function can_refund()
	{
		return ( ! $this->purchase->date_cancelled AND
				 ! $this->purchase->refund_purchase_id AND 
				 $this->purchase->date_billed )
			? TRUE
			: FALSE;	
	}


	public function status()
	{
		return $this->purchase->status;
	}
	
}