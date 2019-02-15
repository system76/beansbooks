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

class View_Vendors_Print_Purchase extends View_Print {

	public function vendor()
	{
		return array(
			'id' => $this->purchase->vendor->id,
			'display_name' => $this->purchase->vendor->display_name,
		);
	}

	public function remit()
	{
		if( ! $this->purchase->remit_address )
			return FALSE;

		return array(
			'company_name' => ( $this->purchase->remit_address->company_name ? $this->purchase->remit_address->company_name : FALSE ),
			'first_name' => $this->purchase->remit_address->first_name,
			'last_name' => $this->purchase->remit_address->last_name,
			'address1' => $this->purchase->remit_address->address1,
			'address2' => $this->purchase->remit_address->address2,
			'city' => $this->purchase->remit_address->city,
			'state' => $this->purchase->remit_address->state,
			'zip' => $this->purchase->remit_address->zip,
			'phone' => $this->purchase->vendor->phone_number,
			'email' => $this->purchase->vendor->email,
		);
	}

	public function shipping()
	{
		if( ! $this->purchase->shipping_address )
			return FALSE;

		return array(
			'company_name' => ( $this->purchase->shipping_address->company_name ? $this->purchase->shipping_address->company_name : FALSE ),
			'first_name' => $this->purchase->shipping_address->first_name,
			'last_name' => $this->purchase->shipping_address->last_name,
			'address1' => $this->purchase->shipping_address->address1,
			'address2' => $this->purchase->shipping_address->address2,
			'city' => $this->purchase->shipping_address->city,
			'state' => $this->purchase->shipping_address->state,
			'zip' => $this->purchase->shipping_address->zip,
			'phone' => $this->purchase->vendor->phone_number,
			'email' => $this->purchase->vendor->email,
		);
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

	public function updated_purchase()
	{
		return ($this->updated_purchase === true);
	}

	public function purchase_date_formatted()
	{
		return date("F j, Y",strtotime($this->purchase->date_created));
	}

	public function purchase_total_formatted()
	{
		$beans_settings = parent::beans_settings();
		if( ! isset($beans_settings->company_currency) )
			return "NO CURRENCY SET";

		return ( $this->purchase->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->purchase->total),2,'.',',');
	}

	public function purchase_date_due_formatted()
	{
		return date("F j, Y",strtotime($this->purchase->date_due));
	}

	public function total_formatted()
	{
		$beans_settings = parent::beans_settings();

		return ( $this->purchase->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->purchase->total),2,'.',',');
	}

	protected $_purchase_lines = FALSE;
	public function purchase_lines()
	{
		if( $this->_purchase_lines )
			return $this->_purchase_lines;

		$beans_settings = parent::beans_settings();

		$this->_purchase_lines = array();

		$i = 0;
		foreach( $this->purchase->lines as $purchase_line )
			$this->_purchase_lines[] = array(
				'odd' => ( $i++ % 2 == 0 ? TRUE : FALSE ),
				'description' => $purchase_line->description,
				'qty_formatted' => number_format($purchase_line->quantity,2,'.',','),
				'price_formatted' => $beans_settings->company_currency.number_format($purchase_line->amount,2,'.',','),
				'total_formatted' => ( $purchase_line->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($purchase_line->total),2,'.',','),
			);

		return $this->_purchase_lines;
	}

}
