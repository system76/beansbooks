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

class Beans_Report_Vendor extends Beans_Report {

	protected $_date_start;
	protected $_date_end;
	protected $_vendor_id;

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
		$this->_vendor_id = ( isset($data->vendor_id) AND strlen($data->vendor_id) )
							? $data->vendor_id
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

		if( ! $this->_vendor_id )
			throw new Exception("Invalid report vendor: must be an ID or t### where ### represents the cut off.");

		$report_lines = array();
		$report_title = FALSE;
		$report_line_range_label = FALSE;
		$report_vendor_name = FALSE;

		if( substr($this->_vendor_id,0,1) == "t" )
		{
			$top_count = intval(substr($this->_vendor_id,1));

			if( $top_count <= 0 )
				throw new Exception("Invalid report vendor: t#### must be greater than 0.");

			$report_vendor_name = "Top ".$top_count;
			$report_title = 'Top '.$top_count.' Vendors '.$this->_date_start.' to '.$this->_date_end;
			$report_line_range_label = "Vendor";

			$vendor_ids_query = 	'SELECT forms.entity_id as vendor_id, SUM(forms.total) as vendor_total FROM forms WHERE ';
			$vendor_ids_query .=	'( forms.type = "purchase" OR forms.type = "expense" ) AND ';
			$vendor_ids_query .= 	'forms.date_billed IS NOT NULL AND ';
			$vendor_ids_query .= 	'forms.date_billed >= "'.$this->_date_start.'" AND ';
			$vendor_ids_query .=	'forms.date_billed <= "'.$this->_date_end.'" ';
			$vendor_ids_query .= 	'GROUP BY forms.entity_id ';
			$vendor_ids_query .= 	'ORDER BY vendor_total DESC ';
			$vendor_ids_query .=	'LIMIT '.$top_count;

			$vendor_ids_result = DB::Query(Database::SELECT,$vendor_ids_query)->execute()->as_array();

			foreach( $vendor_ids_result as $vendor_id_row )
			{
				$vendor = $this->_load_vendor($vendor_id_row['vendor_id']);
				if( ! $vendor->loaded() )
					throw new Exception("Unexpected error: could not load top vendor.");

				$report_lines[] = (object)array(
					'date_start' => $this->_date_start,
					'date_end' => $this->_date_end,
					'vendor_id' => $vendor_id_row['vendor_id'],
					'label' => $vendor->company_name,
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
			$vendor = $this->_load_vendor($this->_vendor_id);

			if( ! $vendor->loaded() )
				throw new Exception("Invalid report vendor: not found.");

			$report_vendor_name = $vendor->company_name;
			$report_title = $vendor->company_name.' '.$this->_date_start.' to '.$this->_date_end;
			$report_line_range_label = "Month";

			$date_ranges = $this->_generate_date_ranges($this->_date_start,$this->_date_end,'month',FALSE);

			foreach( $date_ranges as $date_range )
				$report_lines[] = (object)array(
					'date_start' => $date_range->date_start,
					'date_end' => $date_range->date_end,
					'vendor_id' => $vendor->id,
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
			$date_range_query .= 	'( forms.type = "purchase" OR forms.type = "expense" ) AND ';
			$date_range_query .=	'forms.entity_id = "'.$report->vendor_id.'" AND '; 
			$date_range_query .= 	'forms.date_billed IS NOT NULL AND ';
			$date_range_query .= 	'forms.date_billed >= DATE("'.$report->date_start.'") AND ';
			$date_range_query .= 	'forms.date_billed <= DATE("'.$report->date_end.'") ';
			$date_range_query .= 	'GROUP BY sign';
			
			$date_range_result = DB::Query(Database::SELECT,$date_range_query)->execute()->as_array();

			$date_range_query = 	'SELECT -1 as sign, SUM(form_lines.quantity) as items FROM ';
			$date_range_query .= 	'form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id WHERE ';
			$date_range_query .= 	'form_lines.amount < 0 AND ';
			$date_range_query .= 	'( forms.type = "purchase" OR forms.type = "expense" ) AND ';
			$date_range_query .=	'forms.entity_id = "'.$report->vendor_id.'" AND '; 
			$date_range_query .= 	'forms.date_billed IS NOT NULL AND ';
			$date_range_query .= 	'forms.date_billed >= DATE("'.$report->date_start.'") AND ';
			$date_range_query .= 	'forms.date_billed <= DATE("'.$report->date_end.'") ';
			$date_range_query .= 	'GROUP BY sign';

			$date_range_result = array_merge($date_range_result, DB::Query(Database::SELECT,$date_range_query)->execute()->as_array());

			foreach( $date_range_result as $row )
				$report_lines[$index]->items = $this->_beans_round( $report_lines[$index]->items + ( $row['sign'] * $row['items'] ) );
			
			$date_range_orders_query = 	'SELECT SUM(forms.total) as total, SUM(forms.amount) as subtotal, COUNT(forms.id) as orders FROM forms WHERE ';
			$date_range_orders_query .= '( forms.type = "purchase" OR forms.type = "expense" ) AND ';
			$date_range_orders_query .=	'forms.entity_id = "'.$report->vendor_id.'" AND '; 
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
			'vendor_name' => $report_vendor_name,
			'vendor_id' => $this->_vendor_id,
			'report_line_range_label' => $report_line_range_label,
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'report_lines' => $report_lines,
			'line_totals' => $line_totals,
		);
	}


}