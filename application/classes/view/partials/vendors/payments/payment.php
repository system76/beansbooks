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


class View_Partials_Vendors_Payments_Payment extends KOstache {
	// Receives $this->payment
	
	public function id()
	{
		return $this->payment->id;
	}

	public function vendor()
	{
		return array(
			'id' => $this->payment->vendor->id,
			'name' => $this->payment->vendor->first_name.' '.$this->payment->vendor->last_name,
			'company_name' => $this->payment->vendor->company_name,
		);
	}

	public function date()
	{
		return $this->payment->date;
	}

	public function description()
	{
		return $this->payment->description;
	}

	public function number()
	{
		return $this->payment->number;
	}

	protected $_payment_account = FALSE;
	public function payment_account()
	{
		if( $this->_payment_account AND
			count($this->_payment_account) )
			return $this->_payment_account;

		$this->_payment_account = $this->_vendor_payment_payment_account_array($this->payment);

		return $this->_payment_account;
	}

	protected $_amount = FALSe;
	public function amount()
	{
		if( $this->_amount )
			return $this->_amount;

		$payment_account = $this->_vendor_payment_payment_account_array($this->payment);

		$this->_amount = $payment_account['amount'];

		return $this->_amount;
	}

	public function amount_formatted()
	{
		$amount = $this->amount();

		return ( $amount < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($amount),2,'.',',');
	}
	
	protected function _vendor_payment_payment_account_array($payment) {
		if( ! isset($payment->payment_transaction) )
			return FALSE;

		return array(
			'id' => $payment->payment_transaction->account->id,
			'name' => $payment->payment_transaction->account->name,
			'amount' => $payment->payment_transaction->amount,
		);
	}

	public function check_number()
	{
		if( ! isset($this->payment->check_number) OR 
			! strlen($this->payment->check_number) ) 
			return FALSE;

		return $this->payment->check_number;
	}
}