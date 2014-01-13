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

class View_Vendors_Print_Payment extends View_Print {

	public function vendor()
	{
		return array(
			'name' => $this->payment->vendor->company_name,
			'email' => $this->payment->vendor->email,
			'phone' => $this->payment->vendor->phone_number,
			'address' => ( isset($this->vendor_address) AND 
						   isset($this->vendor_address->address1) )
				? array(
					'address1' => $this->vendor_address->address1,
					'address2' => $this->vendor_address->address2,
					'city' => $this->vendor_address->city,
					'state' => $this->vendor_address->state,
					'zip' => $this->vendor_address->zip,
					'country' => $this->vendor_address->country,
				)
				: FALSE,
		);
	}

	public function payment_account()
	{
		if( ! isset($this->payment->payment_transaction) OR 
			! $this->payment->payment_transaction )
			return FALSE;

		return array(
			'name' => $this->payment->payment_transaction->account->name,
		);
	}

	public function writeoff_account()
	{
		if( ! isset($this->payment->writeoff_transaction) OR 
			! $this->payment->writeoff_transaction )
			return FALSE;

		return array(
			'name' => $this->payment->writeoff_transaction->account->name,
		);
	}

	public function payment_number()
	{
		return $this->payment->number;
	}

	public function payment_date_formatted()
	{
		return date("F j, Y",strtotime($this->payment->date));
	}
	
	public function writeoff_total_formatted()
	{
		$beans_settings = parent::beans_settings();

		if( ! isset($this->payment->writeoff_transaction) OR 
			! $this->payment->writeoff_transaction )
			return $beans_settings->company_currency.number_format(0,2,'.',',');

		return ( $this->payment->writeoff_transaction->amount < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->payment->writeoff_transaction->amount),2,'.',',');
	}

	public function check_number()
	{
		return $this->payment->check_number;
	}

	public function total_formatted()
	{
		$beans_settings = parent::beans_settings();

		return ( $this->payment->payment_transaction->amount < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->payment->payment_transaction->amount),2,'.',',');
	}

	protected $_payment_lines = FALSE;
	public function payment_lines()
	{
		if( $this->_payment_lines )
			return $this->_payment_lines;

		$beans_settings = parent::beans_settings();

		$this->_payment_lines = array();

		$i = 0;
		foreach( $this->payment->purchase_payments as $purchase_payment )
			$this->_payment_lines[] = array(
				'odd' => ( $i++ % 2 == 0 ? TRUE : FALSE ),
				'po_number' => $purchase_payment->purchase->purchase_number,
				'po_date' => $purchase_payment->purchase->date_created,
				'so_number' => $purchase_payment->purchase->so_number,
				'invoice_number' => $purchase_payment->purchase->invoice_number,
				'invoice_date' => $purchase_payment->purchase->date_billed,
				'date_due' => $purchase_payment->purchase->date_due,
				'amount_formatted' => ( $purchase_payment->amount < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($purchase_payment->amount),2,'.',','),
			);

		return $this->_payment_lines;
	}

}