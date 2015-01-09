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

// TODO_V_1_3 - Increase doc to handle nested attributes?

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
@returns tax OBJECT The applicable #Beans_Tax#.
@returns date_start STRING 
@returns date_end STRING
@returns taxes OBJECT The applicable taxes for the given period.
@returns @attribute taxes paid OBJECT The taxes that have already been paid during this period.  Includes invoiced and refunded - objects - each with line_amount, line_taxable_amount, amount and exemptions.
@returns @attribute taxes due OBJECT The taxes that have already been paid during this period.  Includes invoiced and refunded - objects - each with line_amount, line_taxable_amount, amount and exemptions.
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

		$periods = array(
			'paid' => 'tax_payment_id IS NOT NULL AND date <= DATE("'.$this->_date_end.'") AND date >= DATE("'.$this->_date_start.'")', 
			'due' => 'tax_payment_id IS NULL AND date <= DATE("'.$this->_date_end.'")',
		);

		$categories = array(
			'invoiced' => 'type = "invoice"',
			'refunded' => 'type = "refund"',
		);

		$taxes = (object)array();

		$taxes->net = (object)array(
			'form_line_amount' => 0.00,
			'form_line_taxable_amount' => 0.00,
			'amount' => 0.00,
		);

		foreach( $periods as $period => $period_query )
		{
			$taxes->{$period} = (object)array();

			$taxes->{$period}->net = (object)array(
				'form_line_amount' => 0.00,
				'form_line_taxable_amount' => 0.00,
				'amount' => 0.00,
			);

			foreach( $categories as $category => $category_query )
			{
				$taxes->{$period}->{$category} = (object)array(
					'form_line_amount' => 0.00,
					'form_line_taxable_amount' => 0.00,
					'amount' => 0.00,
					'liabilities' => array(),
				);

				$tax_item_summaries_query = 
					'SELECT '.
					'form_id as form_id, '.
					'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
					'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
					'IFNULL(SUM(total),0.00) as amount, '.
					'type as type '.
					'FROM tax_items WHERE '.
					'tax_id = '.$this->_tax->id.' AND '.
					$period_query.' AND '.
					$category_query.' '.
					'GROUP BY form_id ';

				$tax_item_summaries = DB::Query(Database::SELECT, $tax_item_summaries_query)->execute()->as_array();

				foreach( $tax_item_summaries as $tax_item_summary )
				{
					$taxes->net->form_line_amount = $this->_beans_round(
						$taxes->net->form_line_amount +
						$tax_item_summary['form_line_amount']
					);
					$taxes->net->form_line_taxable_amount = $this->_beans_round(
						$taxes->net->form_line_taxable_amount +
						$tax_item_summary['form_line_taxable_amount']
					);
					$taxes->net->amount = $this->_beans_round(
						$taxes->net->amount +
						$tax_item_summary['amount']
					);
					
					$taxes->{$period}->net->form_line_amount = $this->_beans_round(
						$taxes->{$period}->net->form_line_amount +
						$tax_item_summary['form_line_amount']
					);
					$taxes->{$period}->net->form_line_taxable_amount = $this->_beans_round(
						$taxes->{$period}->net->form_line_taxable_amount +
						$tax_item_summary['form_line_taxable_amount']
					);
					$taxes->{$period}->net->amount = $this->_beans_round(
						$taxes->{$period}->net->amount +
						$tax_item_summary['amount']
					);
					
					$taxes->{$period}->{$category}->form_line_amount = $this->_beans_round(
						$taxes->{$period}->{$category}->form_line_amount +
						$tax_item_summary['form_line_amount']
					);
					$taxes->{$period}->{$category}->form_line_taxable_amount = $this->_beans_round(
						$taxes->{$period}->{$category}->form_line_taxable_amount +
						$tax_item_summary['form_line_taxable_amount']
					);
					$taxes->{$period}->{$category}->amount = $this->_beans_round(
						$taxes->{$period}->{$category}->amount +
						$tax_item_summary['amount']
					);
					
					$taxes->{$period}->{$category}->liabilities[] = $this->_return_tax_liability_element(
						$tax_item_summary['form_id'],
						$tax_item_summary['form_line_amount'], 
						$tax_item_summary['form_line_taxable_amount'], 
						$tax_item_summary['amount'],
						$tax_item_summary['type']
					);
				}
			}
		}

		return (object)array(
			'tax' => $this->_return_tax_element($this->_tax),
			'taxes' => $taxes,
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
		);
	}
}