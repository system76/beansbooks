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


class View_Partials_Customers_Payments_Batchpaymentline extends KOstache {
	// Receives $this->sale_payment
	// 					->id
	// 					->amount ( paid )
	// 					->sale ( object )

	public function id()
	{
		return $this->sale_payment->sale->id;
	}

	public function customer()
	{
		return array(
			'id' => $this->sale_payment->sale->customer->id,
			'name' => $this->sale_payment->sale->customer->display_name,
		);
	}

	public function sale_number()
	{
		return $this->sale_payment->sale->sale_number;
	}
	
	public function date_due()
	{
		return $this->sale_payment->sale->date_due;
	}

	public function balance_flipped()
	{
		return ( ( isset($this->sale_payment->writeoff_amount) ? $this->sale_payment->writeoff_amount : 0 ) + $this->sale_payment->amount + ($this->sale_payment->sale->balance * -1) );
	}

	public function payment_amount() {
		return $this->sale_payment->amount;
	}

	public function writeoff_balance() {
		if( ! isset($this->sale_payment->writeoff_amount) OR 
			! $this->sale_payment->writeoff_amount )
			return FALSE;

		return array(
			'amount' => $this->sale_payment->writeoff_amount,
		);
	}

}