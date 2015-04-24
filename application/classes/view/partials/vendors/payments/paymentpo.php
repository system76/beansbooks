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


class View_Partials_Vendors_Payments_Paymentpo extends KOstache {
	
	public function id()
	{
		return $this->purchase_payment->purchase->id;
	}
	
	public function purchase_number()
	{
		return $this->purchase_payment->purchase->purchase_number;
	}

	public function invoice_number()
	{
		return $this->purchase_payment->purchase->invoice_number;
	}
	
	public function so_number()
	{
		return $this->purchase_payment->purchase->so_number;
	}

	public function date_billed()
	{
		return $this->purchase_payment->purchase->date_billed;
	}
	
	public function balance_flipped()
	{
		return ( 
			-1 * 
			( 
				( isset($this->purchase_payment->writeoff_amount) ? ( $this->purchase_payment->writeoff_amount * -1 ) : 0 ) + 
				( $this->purchase_payment->amount * -1 ) + 
				($this->purchase_payment->purchase->balance * -1) 
			) 
		);
	}

	public function balance_flipped_formatted()
	{
		return ( $this->purchase_payment->purchase->balance < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->purchase_payment->purchase->balance),2,'.',',');
	}

	public function payment_amount() {
		return number_format($this->purchase_payment->amount,2,'.','');
	}

	public function writeoff_balance() {
		if( ! isset($this->purchase_payment->writeoff_amount) OR 
			! $this->purchase_payment->writeoff_amount )
			return FALSE;

		return array(
			'amount' => $this->purchase_payment->writeoff_amount,
		);
	}

	public function remit_address_id() 
	{
		if( ! $this->purchase_payment->purchase->remit_address )
			return FALSE;

		return $this->purchase_payment->purchase->remit_address->id;
	}

}
