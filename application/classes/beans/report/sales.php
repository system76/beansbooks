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

class Beans_Report_Sales extends Beans_Report {

	protected $_date_start;
	protected $_date_end;
	protected $_interval;	// day / week / month / year

	/**
	 * Create a new account
	 * @param array $data fields => values to create an account.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_date_start = ( isset($data->date_start) )
						   ? $data->date_start
						   : NULL;
		$this->_date_end = ( isset($data->date_end) )
						 ? $data->date_end
						 : NULL;
		$this->_interval = ( isset($data->interval) )
						 ? $data->interval
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

		// Check for day / week / month / year
		$date_ranges = $this->_generate_date_ranges($this->_date_start,$this->_date_end,$this->_interval,FALSE);
		
		$subtotal_net = 0.00;
		$taxes_net = 0.00;
		$total_net = 0.00;
		$items_net = 0;
		$orders_net = 0;

		foreach( $date_ranges as $index => $date_range )
		{
			$date_ranges[$index]->total = 0.00;
			$date_ranges[$index]->subtotal = 0.00;
			$date_ranges[$index]->taxes = 0.00;
			$date_ranges[$index]->items = 0;
			$date_ranges[$index]->orders = 0;
			

			$date_range_query = 	'SELECT 1 as sign, SUM(form_lines.quantity) as items FROM ';
			$date_range_query .= 	'form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id WHERE ';
			$date_range_query .= 	'form_lines.amount >= 0 AND ';
			$date_range_query .= 	'forms.type = "sale" AND ';
			$date_range_query .= 	'forms.date_billed IS NOT NULL AND ';
			$date_range_query .= 	'forms.date_billed >= DATE("'.$date_range->date_start.'") AND ';
			$date_range_query .= 	'forms.date_billed <= DATE("'.$date_range->date_end.'") ';
			$date_range_query .= 	'GROUP BY sign';
			
			$date_range_result = DB::Query(Database::SELECT,$date_range_query)->execute()->as_array();

			$date_range_query = 	'SELECT -1 as sign, SUM(form_lines.quantity) as items FROM ';
			$date_range_query .= 	'form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id WHERE ';
			$date_range_query .= 	'form_lines.amount < 0 AND ';
			$date_range_query .= 	'forms.type = "sale" AND ';
			$date_range_query .= 	'forms.date_billed IS NOT NULL AND ';
			$date_range_query .= 	'forms.date_billed >= DATE("'.$date_range->date_start.'") AND ';
			$date_range_query .= 	'forms.date_billed <= DATE("'.$date_range->date_end.'") ';
			$date_range_query .= 	'GROUP BY sign';

			$date_range_result = array_merge($date_range_result, DB::Query(Database::SELECT,$date_range_query)->execute()->as_array());

			foreach( $date_range_result as $row )
			{
				$date_ranges[$index]->items = $this->_beans_round( $date_ranges[$index]->items + ( $row['sign'] * $row['items'] ) );
			}
			
			$date_range_orders_query = 	'SELECT SUM(forms.total) as total, SUM(forms.amount) as subtotal, COUNT(forms.id) as orders FROM forms WHERE ';
			$date_range_orders_query .= 'forms.type = "sale" AND ';
			$date_range_orders_query .= 'forms.date_billed IS NOT NULL AND ';
			$date_range_orders_query .= 'forms.date_billed >= DATE("'.$date_range->date_start.'") AND ';
			$date_range_orders_query .= 'forms.date_billed <= DATE("'.$date_range->date_end.'") ';

			$date_range_orders_result = DB::Query(Database::SELECT,$date_range_orders_query)->execute()->as_array();

			$date_ranges[$index]->orders = $date_range_orders_result[0]['orders'];
			$date_ranges[$index]->total = $date_range_orders_result[0]['total'];
			$date_ranges[$index]->subtotal = $date_range_orders_result[0]['subtotal'];
			$date_ranges[$index]->taxes = $this->_beans_round( $date_ranges[$index]->total - $date_ranges[$index]->subtotal );
			
			$date_ranges[$index]->date_start = date("Y-m-d",strtotime($date_ranges[$index]->date_start));
			$date_ranges[$index]->date_end = date("Y-m-d",strtotime($date_ranges[$index]->date_end));

			$subtotal_net = $this->_beans_round( $subtotal_net + $date_ranges[$index]->subtotal );
			$taxes_net = $this->_beans_round( $taxes_net + $date_ranges[$index]->taxes );
			$total_net = $this->_beans_round( $total_net + $date_ranges[$index]->total );
			$items_net = $this->_beans_round( $items_net + $date_ranges[$index]->items );
			$orders_net = $this->_beans_round( $orders_net + $date_ranges[$index]->orders );
		}
		
		return (object)array(
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'interval' => $this->_interval,
			'date_ranges' => $date_ranges,
			'net' => (object)array(
				'date_start' => $this->_date_start,
				'date_end' => $this->_date_end,
				'total' => $total_net,
				'subtotal' => $subtotal_net,
				'taxes' => $taxes_net,
				'items' => $items_net,
				'orders' => $orders_net,
			),
		);
	}

}