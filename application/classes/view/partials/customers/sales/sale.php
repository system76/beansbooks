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


class View_Partials_Customers_Sales_Sale extends Kostache {
	
	public function customer() {
		return array(
			'id' => $this->sale->customer->id,
			'name' => $this->sale->customer->first_name.' '.$this->sale->customer->last_name,
			'email' => $this->sale->customer->email,
		);
	}

	public function invoice_view()
	{
		return ( isset($this->invoice_view) && $this->invoice_view ? TRUE : FALSE );
	}

	public function invoiced()
	{
		return ( $this->sale->date_billed ? TRUE : FALSE );
	}

	public function cancelled()
	{
		return ( $this->sale->date_cancelled ? TRUE : FALSE );
	}

	public function id() {
		return $this->sale->id;
	}

	public function date_created() {
		return $this->sale->date_created;
	}

	public function date_due() {
		return $this->sale->date_due;
	}

	public function sale_number() {
		return $this->sale->sale_number;
	}

	public function order_number() {
		return $this->sale->order_number;
	}

	public function po_number() {
		return $this->sale->po_number;
	}

	public function total() {
		return $this->sale->total;
	}

	public function balance() {
		return $this->sale->balance;
	}

	public function paid() {
		return ( $this->sale->balance == 0 AND $this->sale->date_billed ? TRUE : FALSE );
	}

	public function total_formatted() {
		return ( $this->sale->total < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($this->sale->total),2,'.',',');
	}

	public function status() {
		return $this->sale->status;
	}

	public function can_cancel() {
		return ( ! $this->sale->date_billed AND 
				 ! $this->sale->date_cancelled )
			? TRUE
			: FALSE;
	}

	public function can_refund() {
		return ( ! $this->sale->refund_sale_id AND 
				 ! $this->sale->date_cancelled AND 
				 $this->sale->date_billed )
			? TRUE
			: FALSE;
	}

	private function _sale_status($sale)
	{
		if( $sale->refund_sale_id AND
			$sale->balance != 0 )
			return "Refund Pending";
		if( $sale->refund_sale_id )
			return "Refunded";
		if( $sale->balance == 0 )
			return "Paid";
		if( ! $sale->sent )
			return "Sale Not Sent";
		return "Due ".$sale->date_due;
	}

}