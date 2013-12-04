<?php defined('SYSPATH') or die('No direct script access.');
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

/*
---BEANSAPISPEC---
@action Beans_Tax_Prep
@description Prepares information to be able to create a tax payment.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax# being paid.
@required date_start STRING Inclusive YYYY-MM-DD date ( i.e. >= date_start )
@required date_end STRING Inclusive YYYY-MM-DD date ( i.e. <= date_end )
@optional payment_id INTEGER The ID of a #Beans_Tax_Payment# to ignore when tabulating totals.  Useful if updating a previous payment.
@returns tax_prep OBJECT An object with information regarding the tax payment for that time period.
@returns @attribute tax_prep tax_collected DECIMAL Total amount of tax collected.
@returns @attribute tax_prep total_sales DECIMAL Total sales for that time period on applied taxes.
@returns @attribute tax_prep taxable_sales DECIMAL The amount of taxable sales for that time period on that tax.
@returns @attribute tax_prep tax_returned DECIMAL Amount of taxes returned for refunds.
@returns @attribute tax_prep total_returns DECIMAL Total returned.
@returns @attribute tax_prep taxable_returns DECIMAL The taxable total returned.
@returns @attribute tax_prep tax_payments ARRAY An array of #Beans_Tax_Payment# that occurred for periods within that time period.
@returns @attribute tax_prep tax_payments_total DECIMAL Total tax remitted for periods within that time period.
@returns @attribute tax_prep tax OBJECT The applicable #Beans_Tax#.
---BEANSENDSPEC---
*/
class Beans_Tax_Prep extends Beans_Tax {
	protected $_auth_role_perm = "customer_sale_read";

	protected $_id;
	protected $_tax;
	protected $_date_start;
	protected $_data_end;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_payment_id = ( isset($data->payment_id) AND
								$data->payment_id )
						   ? $data->payment_id
						   : -1;	// a value that will never match a real record ( BIGINT UNSIGNED )

		$this->_tax = $this->_load_tax($this->_id);

		$this->_date_start = ( isset($data->date_start) )
						   ? $data->date_start
						   : NULL;

		$this->_date_end = ( isset($data->date_end) )
						   ? $data->date_end
						   : NULL;
	}

	protected function _execute()
	{
		if( ! $this->_tax->loaded() )
			throw new Exception("Tax could not be found.");

		if( ! $this->_date_start ) 
			throw new Exception("Invalid date start: none provided.");

		if( $this->_date_start != date("Y-m-d",strtotime($this->_date_start)) )
			throw new Exception("Invalid date start: must be in YYYY-MM-DD format.");

		if( ! $this->_date_end ) 
			throw new Exception("Invalid date start: none provided.");

		if( $this->_date_end != date("Y-m-d",strtotime($this->_date_end)) )
			throw new Exception("Invalid date start: must be in YYYY-MM-DD format.");

		// Query all sales with that tax in between the dates.
		$sale_taxes = ORM::Factory('form_tax')->distinct(TRUE)->
			join('forms','RIGHT')->on('form_tax.form_id','=','forms.id')->
			where('forms.date_created','>=',$this->_date_start)->
			where('forms.date_created','<=',$this->_date_end)->
			where('forms.type','=','sale')->
			where('form_tax.tax_id','=',$this->_tax->id)->
			find_all();

		$tax_collected = 0.00;
		$total_sales = 0.00;
		$taxable_sales = 0.00;
		$tax_returned = 0.00;
		$total_returns = 0.00;
		$taxable_returns = 0.00;
		
		// V2Item
		// Consider adding form.amount to speed this ( and other ) processes up.
		$test_total_sales = 0.00;
		$test_total_returns = 0.00;
		foreach( $sale_taxes as $sale_tax )
		{
			if( $sale_tax->total > 0 )
			{
				$tax_collected = $this->_beans_round( $tax_collected + $sale_tax->total );
				$taxable_sales = $this->_beans_round( $taxable_sales + $sale_tax->amount );
				$test_total_sales = $this->_beans_round( $test_total_sales + $sale_tax->form->amount );
				foreach( $sale_tax->form->form_lines->find_all() as $sale_line )
					$total_sales = $this->_beans_round( $total_sales + $sale_line->total );
			}
			else
			{
				$tax_returned = $this->_beans_round( $tax_returned + $sale_tax->total );
				$taxable_returns = $this->_beans_round( $taxable_returns + $sale_tax->amount );
				$test_total_returns = $this->_beans_round( $test_total_returns + $sale_tax->form->amount );
				foreach( $sale_tax->form->form_lines->find_all() as $sale_line )
					$total_returns = $this->_beans_round( $total_returns + $sale_line->total );
			}
		}

		if( $test_total_sales != $total_sales )
			throw new Exception("MISMATCH TOTAL SALES: ".$test_total_sales." ? ".$total_sales);

		if( $test_total_returns != $total_returns )
			throw new Exception("MISMATCH TOTAL RETURNS: ".$test_total_returns." ? ".$total_returns);

		// Find tax payments in same date range.
		$tax_payments = ORM::Factory('tax_payment')->
			where('date_start','>=',$this->_date_start)->
			where('date_end','<=',$this->_date_end)->
			where('tax_id','=',$this->_tax->id)->
			where('id','!=',$this->_payment_id)->
			find_all();

		$tax_payments_total = 0.00;

		if( count($tax_payments) )
			foreach( $tax_payments as $tax_payment )
				$tax_payments_total = $this->_beans_round( $tax_payments_total + $tax_payment->amount );

		return (object)array(
			"tax_prep" => (object)array(
				'tax_collected' => $tax_collected,
				'total_sales' => $total_sales,
				'taxable_sales' => $taxable_sales,
				'tax_returned' => $tax_returned,
				'total_returns' => $total_returns,
				'taxable_returns' => $taxable_returns,
				'tax_payments' => $this->_return_tax_payments_array($tax_payments),
				'tax_payments_total' => $tax_payments_total,
				'tax' => $this->_return_tax_element($this->_tax),
			),
		);
	}
}