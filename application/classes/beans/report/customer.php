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

class Beans_Report_Customer extends Beans_Report {

	protected $_date_start;
	protected $_date_end;
	protected $_customer_id;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_date_start = ( isset($data->date_start) )
					 ? $data->date_start
					 : NULL;

		$this->_date_end = ( isset($data->date_end) )
					 ? $data->date_end
					 : NULL;

		// Either customer ID or t### to represent top ### customers
		$this->_customer_id = ( isset($data->customer_id) AND strlen($data->customer_id) )
							? $data->customer_id
							: 't20';						// Default = top 20

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

		if( ! $this->_customer_id )
			throw new Exception("Invalid report customer: must be an ID or t### where ### represents the cut off.");

		$report_lines = array();
		$report_title = FALSE;
		$report_line_range_label = FALSE;
		$report_customer_name = FALSE;

		if( substr($this->_customer_id,0,1) == "t" )
		{
			$top_count = intval(substr($this->_customer_id,1));

			if( $top_count <= 0 )
				throw new Exception("Invalid report customer: t#### must be greater than 0.");

			$report_customer_name = "Top ".$top_count;
			$report_title = 'Top '.$top_count.' Customers '.$this->_date_start.' to '.$this->_date_end;
			$report_line_range_label = "Customer";

			$customer_ids_query = 	'SELECT forms.entity_id as customer_id, SUM(forms.total) as customer_total FROM forms WHERE ';
			$customer_ids_query .=	'forms.type = "sale" AND ';
			$customer_ids_query .= 	'forms.date_billed IS NOT NULL AND ';
			$customer_ids_query .= 	'forms.date_billed >= "'.$this->_date_start.'" AND ';
			$customer_ids_query .=	'forms.date_billed <= "'.$this->_date_end.'" ';
			$customer_ids_query .= 	'GROUP BY forms.entity_id ';
			$customer_ids_query .= 	'ORDER BY customer_total DESC ';
			$customer_ids_query .=	'LIMIT '.$top_count;

			$customer_ids_result = DB::Query(Database::SELECT,$customer_ids_query)->execute()->as_array();

			foreach( $customer_ids_result as $customer_id_row )
			{
				$customer = $this->_load_customer($customer_id_row['customer_id']);
				if( ! $customer->loaded() )
					throw new Exception("Unexpected error: could not load top customer.");

				$report_lines[] = (object)array(
					'date_start' => $this->_date_start,
					'date_end' => $this->_date_end,
					'customer_id' => $customer_id_row['customer_id'],
					'label' => $customer->first_name.' '.$customer->last_name,
					'orders' => 0,
					'items' => 0,
					'subtotal' => 0.00,
					'taxes' => 0.00,
					'total' => 0.00,
				);
			}
		}
		else
		{
			$customer = $this->_load_customer($this->_customer_id);

			if( ! $customer->loaded() )
				throw new Exception("Invalid report customer: not found.");

			$report_customer_name = $customer->first_name.' '.$customer->last_name;
			$report_title = $customer->first_name.' '.$customer->last_name.' '.$this->_date_start.' to '.$this->_date_end;
			$report_line_range_label = "Month";

			$date_ranges = $this->_generate_date_ranges($this->_date_start,$this->_date_end,'month',FALSE);

			foreach( $date_ranges as $date_range )
				$report_lines[] = (object)array(
					'date_start' => $date_range->date_start,
					'date_end' => $date_range->date_end,
					'customer_id' => $customer->id,
					'label' => date("F",strtotime($date_range->date_start)),
					'orders' => 0,
					'items' => 0,
					'subtotal' => 0.00,
					'taxes' => 0.00,
					'total' => 0.00,
				);

		}

		$line_totals = (object)array(
			'orders' => 0,
			'items' => 0,
			'subtotal' => 0.00,
			'taxes' => 0.00,
			'total' => 0.00,
		);

		// Run each report line.
		foreach( $report_lines as $index => $report )
		{

			$date_range_query = 	'SELECT 1 as sign, SUM(form_lines.quantity) as items FROM ';
			$date_range_query .= 	'form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id WHERE ';
			$date_range_query .= 	'form_lines.amount >= 0 AND ';
			$date_range_query .= 	'forms.type = "sale" AND ';
			$date_range_query .=	'forms.entity_id = "'.$report->customer_id.'" AND '; 
			$date_range_query .= 	'forms.date_billed IS NOT NULL AND ';
			$date_range_query .= 	'forms.date_billed >= DATE("'.$report->date_start.'") AND ';
			$date_range_query .= 	'forms.date_billed <= DATE("'.$report->date_end.'") ';
			$date_range_query .= 	'GROUP BY sign';
			
			$date_range_result = DB::Query(Database::SELECT,$date_range_query)->execute()->as_array();

			$date_range_query = 	'SELECT -1 as sign, SUM(form_lines.quantity) as items FROM ';
			$date_range_query .= 	'form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id WHERE ';
			$date_range_query .= 	'form_lines.amount < 0 AND ';
			$date_range_query .= 	'forms.type = "sale" AND ';
			$date_range_query .=	'forms.entity_id = "'.$report->customer_id.'" AND '; 
			$date_range_query .= 	'forms.date_billed IS NOT NULL AND ';
			$date_range_query .= 	'forms.date_billed >= DATE("'.$report->date_start.'") AND ';
			$date_range_query .= 	'forms.date_billed <= DATE("'.$report->date_end.'") ';
			$date_range_query .= 	'GROUP BY sign';

			$date_range_result = array_merge($date_range_result, DB::Query(Database::SELECT,$date_range_query)->execute()->as_array());

			foreach( $date_range_result as $row )
				$report_lines[$index]->items = $this->_beans_round( $report_lines[$index]->items + ( $row['sign'] * $row['items'] ) );
			
			$date_range_orders_query = 	'SELECT SUM(forms.total) as total, SUM(forms.amount) as subtotal, COUNT(forms.id) as orders FROM forms WHERE ';
			$date_range_orders_query .= 'forms.type = "sale" AND ';
			$date_range_orders_query .=	'forms.entity_id = "'.$report->customer_id.'" AND '; 
			$date_range_orders_query .= 'forms.date_billed IS NOT NULL AND ';
			$date_range_orders_query .= 'forms.date_billed >= DATE("'.$report->date_start.'") AND ';
			$date_range_orders_query .= 'forms.date_billed <= DATE("'.$report->date_end.'") ';

			$date_range_orders_result = DB::Query(Database::SELECT,$date_range_orders_query)->execute()->as_array();

			$report_lines[$index]->orders = $date_range_orders_result[0]['orders'];
			$report_lines[$index]->total = $date_range_orders_result[0]['total'];
			$report_lines[$index]->subtotal = $date_range_orders_result[0]['subtotal'];
			$report_lines[$index]->taxes = $this->_beans_round( $report_lines[$index]->total - $report_lines[$index]->subtotal );

			$line_totals->orders = $line_totals->orders + $report_lines[$index]->orders;
			$line_totals->items = $line_totals->items + $report_lines[$index]->items;
			$line_totals->subtotal = $this->_beans_round( $line_totals->subtotal + $report_lines[$index]->subtotal );
			$line_totals->taxes = $this->_beans_round( $line_totals->taxes + $report_lines[$index]->taxes );
			$line_totals->total = $this->_beans_round( $line_totals->total + $report_lines[$index]->total );

		}

		return (object)array(
			'title' => $report_title,
			'customer_name' => $report_customer_name,
			'customer_id' => $this->_customer_id,
			'report_line_range_label' => $report_line_range_label,
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'report_lines' => $report_lines,
			'line_totals' => $line_totals,
		);
	}


}