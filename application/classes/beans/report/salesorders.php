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

class Beans_Report_Salesorders extends Beans_Report {

	protected $_date;
	protected $_customer_id;
	protected $_days_late_minimum;

	/**
	 * Create a new account
	 * @param array $data fields => values to create an account.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_customer_id = ( isset($data->customer_id) )
							? $data->customer_id
							: NULL;

		$this->_days_old_minimum = ( isset($data->days_old_minimum) )
								  ? $data->days_old_minimum
								  : NULL;
	}

	protected function _execute()
	{
		if( $this->_customer_id AND
			! $this->_load_customer($this->_customer_id)->loaded() )
			throw new Exception("Invalid report customer ID: customer not found.");

		// Look up all sales that are unpaid - if specific customer, limit.

		$sales = ORM::Factory('form')->
			where('type','=','sale')->
			where('balance','!=',0.00);

		if( $this->_customer_id )
			$sales = $sales->where('entity_id','=',$this->_customer_id);

		if( $this->_days_old_minimum )
			$sales = $sales->where('date_created','<=',date("Y-m-d",strtotime("-".$this->_days_old_minimum." Days")));
		
		$sales = $sales->where('date_due','IS',NULL)->order_by('date_created','ASC')->find_all();

		$customers = array();

		$timestamp_today = strtotime(date("Y-m-d"));

		$balance_total = 0.00;
		$balances = array(
			'90' => 0.00,
			'60' => 0.00,
			'30' => 0.00,
			'0' => 0.00,
			'current' => 0.00,
		);

		foreach( $sales as $sale )
		{
			if( ! isset($customers[$sale->entity_id]) )
			{
				$customers[$sale->entity_id] = new stdClass;
				$customers[$sale->entity_id]->customer_name = $sale->entity->first_name.' '.$sale->entity->last_name;
				$customers[$sale->entity_id]->customer_company_name = $sale->entity->company_name;
				$customers[$sale->entity_id]->customer_phone_number = $sale->entity->phone_number;
				$customers[$sale->entity_id]->sales = array();
				$customers[$sale->entity_id]->balance_total = 0.00;
				$customers[$sale->entity_id]->balances = array(
					'90' => 0.00,
					'60' => 0.00,
					'30' => 0.00,
					'0' => 0.00,
					'current' => 0.00,
				);
			}

			$report_sale = (object)array(
				'id' => $sale->id,
				'date_created' => $sale->date_created,
				'date_due' => $sale->date_due,
				'sale_number' => $sale->code,
				'balance' => ( $sale->balance * -1),
				'days_late' => round(($timestamp_today - strtotime($sale->date_created)) / 86400),
			);
			
			$days_range = 'current';
			if( $report_sale->days_late >= 90 )
				$days_range = '90';
			else if( $report_sale->days_late >= 60 )
				$days_range = '60';
			else if( $report_sale->days_late >= 30 )
				$days_range = '30';
			else if( $report_sale->days_late > 0 )
				$days_range = '0';

			$customers[$sale->entity_id]->balances[$days_range] = $this->_beans_round( $customers[$sale->entity_id]->balances[$days_range] + $report_sale->balance );
			$balances[$days_range] = $this->_beans_round( $balances[$days_range] + $report_sale->balance );
			
			$customers[$sale->entity_id]->balance_total = $this->_beans_round( $customers[$sale->entity_id]->balance_total + $report_sale->balance );
			$balance_total = $this->_beans_round( $balance_total + $report_sale->balance );

			$customers[$sale->entity_id]->sales[] = $report_sale;
		}
		
		return (object)array(
			'date' => date("Y-m-d"),
			'customer_id' => $this->_customer_id,
			'days_old_minimum' => $this->_days_old_minimum,
			'customers' => $customers,
			'total_customers' => count($customers),
			'balance_total'	=> $balance_total,
			'balances' => $balances,
		);
	}

}