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


class View_Partials_Customers_Payments_Payment extends KOstache {
	// Receives $this->payment
	
	public function id()
	{
		return $this->payment->id;
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

	protected $_deposit_account = FALSE;
	public function deposit_account()
	{
		if( $this->_deposit_account AND
			count($this->_deposit_account) )
			return $this->_deposit_account;

		$this->_deposit_account = $this->_customer_payment_deposit_account_array($this->payment);

		return $this->_deposit_account;
	}

	protected $_amount = FALSe;
	public function amount()
	{
		if( $this->_amount )
			return $this->_amount;

		$deposit_account = $this->_customer_payment_deposit_account_array($this->payment);

		return $deposit_account['amount'];
	}

	public function amount_formatted()
	{
		$amount = $this->amount();

		return ( $amount > 0 ? '-' : '' ).$this->_company_currency().number_format(abs($amount),2,'.',',');
	}
	
	protected function _customer_payment_deposit_account_array($payment) {
		if( ! isset($payment->deposit_transaction) OR 
			! $payment->deposit_transaction )
			return FALSE;

		return array(
			'id' => $payment->deposit_transaction->account->id,
			'name' => $payment->deposit_transaction->account->name,
			'amount' => $payment->deposit_transaction->amount,
		);
	}
}