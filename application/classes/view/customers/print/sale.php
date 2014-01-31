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


class View_Customers_Print_Sale extends View_Print {

	/** @var bool|array Cache of line items on this sale */
	protected $_sale_lines = FALSE;
	/** @var bool|array Cache of taxes on this sale */
	protected $_sale_taxes = FALSE;
	/** @var bool|array Cache of payments on this sale */
	protected $_payment_lines = FALSE;

	// Receives $this->sale

	public function invoiced()
	{
		return ( $this->sale->date_billed ? TRUE : FALSE );
	}

	public function sale_title()
	{
		return $this->sale->title;
	}

	public function customer_company_name()
	{
		return ( $this->sale->customer->company_name ? $this->sale->customer->company_name : FALSE );
	}

	public function customer_name()
	{
		return $this->sale->customer->first_name.' '.$this->sale->customer->last_name;
	}

	public function billing()
	{
		if( ! $this->sale->billing_address )
			return FALSE;

		return array(
			'company_name' => ( $this->sale->billing_address->company_name ? $this->sale->billing_address->company_name : FALSE ),
			'first_name' => $this->sale->billing_address->first_name,
			'last_name' => $this->sale->billing_address->last_name,
			'address1' => $this->sale->billing_address->address1,
			'address2' => $this->sale->billing_address->address2,
			'city' => $this->sale->billing_address->city,
			'state' => $this->sale->billing_address->state,
			'zip' => $this->sale->billing_address->zip,
			'phone' => $this->sale->customer->phone_number,
			'email' => $this->sale->customer->email,
		);
	}

	public function shipping()
	{
		if( ! $this->sale->shipping_address )
			return FALSE;
		
		return array(
			'company_name' => ( $this->sale->shipping_address->company_name ? $this->sale->shipping_address->company_name : FALSE ),
			'first_name' => $this->sale->shipping_address->first_name,
			'last_name' => $this->sale->shipping_address->last_name,
			'address1' => $this->sale->shipping_address->address1,
			'address2' => $this->sale->shipping_address->address2,
			'city' => $this->sale->shipping_address->city,
			'state' => $this->sale->shipping_address->state,
			'zip' => $this->sale->shipping_address->zip,
			'phone' => $this->sale->customer->phone_number,
			'email' => $this->sale->customer->email,
		);
	}

	public function sale_number()
	{
		return $this->sale->sale_number;
	}

	public function order_number()
	{
		return $this->sale->order_number;
	}

	public function po_number()
	{
		return $this->sale->po_number;
	}

	public function sale_date_formatted()
	{
		return date("F j, Y",strtotime($this->sale->date_created));
	}
	
	public function sale_total_formatted()
	{
		$beans_settings = parent::beans_settings();

		return ( $this->sale->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->sale->total),2,'.',',');
	}

	/**
	 * Return the balance left on this sale formatted with the correct currency settings.
	 *
	 * Used directly in the template
	 * @see application/templates/customers/print/sale.mustache
	 *
	 * @return string
	 */
	public function sale_total_balance_formatted()
	{
		$beans_settings = parent::beans_settings();

		// Balance is going to be inverse;
		// if it's $-13.50, that means that the customer still owes $13.50.
		// Since this is used on a form to be used by their perspective, it'll be positive.
		$balance = 0 - $this->sale->balance;

		return ( $balance < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($balance),2,'.',',');
	}
	
	public function sale_date_due_formatted()
	{
		return date("F j, Y",strtotime($this->sale->date_due));
	}

	public function sale_date_billed_formatted()
	{
		return date("F j, Y",strtotime($this->sale->date_billed));
	}


	/**
	 * Return an array of the sale lines on this sale or false if it doesn't have any.
	 *
	 * @return array|bool
	 */
	public function sale_lines()
	{
		if( $this->_sale_lines ) 
			return $this->_sale_lines;

		$this->_sale_lines = array();

		$beans_settings = parent::beans_settings();

		$i = 0;
		foreach( $this->sale->lines as $sale_line )
			$this->_sale_lines[] = array(
				'odd' => ( $i++ % 2 == 0 ? TRUE : FALSE ),
				'description' => $sale_line->description,
				'qty_formatted' => number_format($sale_line->quantity,2,'.',','),
				'price_formatted' => ( $sale_line->amount < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($sale_line->amount),2,'.',','),
				'total_formatted' => ( $sale_line->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($sale_line->total),2,'.',','),
			);
		
		return $this->_sale_lines;
	}

	public function payment_lines()
	{
		// Already been cached, I can simply return that cache instead of running through the full process.
		if($this->_payment_lines) return $this->_payment_lines;

		$this->_payment_lines = array();

		$beans_settings = parent::beans_settings();

		$i = 0;
		foreach( $this->sale->payments as $payment )
		{
			$this->_payment_lines[] = array(
				'odd'            => ( $i++ % 2 == 0 ? TRUE : FALSE ),
				'date_formatted' => date("F j, Y",strtotime($payment->date)),
				'type'           => ucwords($payment->type),
				'reference'      => $payment->reference,
				'price_formatted' => ( $payment->amount < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($payment->amount),2,'.',','),
			);
		}

		return $this->_payment_lines;
	}

	public function has_payments()
	{
		return sizeof($this->payment_lines()) > 0;
	}

	
	public function total_formatted()
	{
		$beans_settings = parent::beans_settings();

		return ( $this->sale->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->sale->total),2,'.',',');
	}

	public function subtotal()
	{
		return $this->sale->subtotal;
	}

	public function subtotal_formatted()
	{
		$beans_settings = parent::beans_settings();

		return ( $this->sale->subtotal < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($this->sale->subtotal),2,'.',',');
	}


	public function sale_taxes()
	{
		if( $this->_sale_taxes )
			return $this->_sale_taxes;

		if( ! count($this->sale->taxes) ) {
			return FALSE;
		}

		$beans_settings = parent::beans_settings();
		
		$this->_sale_taxes = array();

		foreach( $this->sale->taxes as $sale_tax )
			$this->_sale_taxes[] = array(
				'name' => $sale_tax->tax->name,
				'total' => $sale_tax->total,
				'total_formatted' => ( $sale_tax->total < 0 ? '-' : '' ).$beans_settings->company_currency.number_format(abs($sale_tax->total),2,'.',','),
			);

		return $this->_sale_taxes;
	}

}