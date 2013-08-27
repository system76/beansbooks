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

class Beans_Report_Taxes extends Beans_Report {

	protected $_date_start;
	protected $_date_end;
	protected $_tax_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_date_start = ( isset($data->date_start) )
					 ? $data->date_start
					 : NULL;

		$this->_date_end = ( isset($data->date_end) )
					 ? $data->date_end
					 : NULL;

		// Either vendor ID or t### to represent top ### vendors
		$this->_tax_id = ( isset($data->tax_id) )
							? $data->tax_id
							: NULL;

	}

	protected function _execute()
	{
		if( ! $this->_date_start ) 
			throw new Exception("Invalid report start date: none provided.");

		if( $this->_date_start != date("Y-m-d",strtotime($this->_date_start)) )
			throw new Exception("Invalid report start date: must be in format YYYY-MM-DD.");

		if( ! $this->_date_end ) 
			throw new Exception("Invalid report end date: none provided.");

		if( $this->_date_end != date("Y-m-d",strtotime($this->_date_end)) )
			throw new Exception("Invalid report end date: must be in format YYYY-MM-DD.");

		if( ! $this->_tax_id )
			throw new Exception("Invalid report tax ID: none provided.");

		$tax = $this->_load_tax($this->_tax_id);

		if( ! $tax->loaded() )
			throw new Exception("Invalid report tax ID: not found.");

		$sales = array();
		$sale_totals = (object)array(
			'subtotal' => 0.00,
			'taxes' => 0.00,
			'total' => 0.00,
		);

		$form_tax_query = 	'SELECT form_taxes.total as taxes, forms.amount as subtotal, forms.total as total, forms.code as sale_number, forms.id as sale_id, forms.date_created as date_created FROM form_taxes ';
		$form_tax_query .= 	'RIGHT JOIN forms ON forms.id = form_taxes.form_id WHERE ';
		$form_tax_query .=	'form_taxes.tax_id = "'.$this->_tax_id.'" AND ';
		$form_tax_query .=	'forms.type = "sale" AND ';
		$form_tax_query .=	'forms.date_created >= "'.$this->_date_start.'" AND ';
		$form_tax_query .=	'forms.date_created <= "'.$this->_date_end.'" ';
		$form_tax_query .=	'ORDER BY date_created ASC';

		$form_tax_results = DB::Query(Database::SELECT,$form_tax_query)->execute()->as_array();

		foreach( $form_tax_results as $form_tax_result )
		{
			$sales[] = (object)array(
				'id' => $form_tax_result['sale_id'],
				'date_created' => $form_tax_result['date_created'],
				'sale_number' => $form_tax_result['sale_number'],
				'subtotal' => $form_tax_result['subtotal'],
				'taxes' => $form_tax_result['taxes'],
				'total' => $form_tax_result['total'],
			);

			$sale_totals->subtotal = $this->_beans_round( $sale_totals->subtotal + $form_tax_result['subtotal'] );
			$sale_totals->taxes = $this->_beans_round( $sale_totals->taxes + $form_tax_result['taxes'] );
			$sale_totals->total = $this->_beans_round( $sale_totals->total + $form_tax_result['total'] );
		}			

		return (object)array(
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'tax_id' => $tax->id,
			'tax_name' => $tax->name,
			'sales' => $sales,
			'sale_totals' => $sale_totals,
		);
	}


}