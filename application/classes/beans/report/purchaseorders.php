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

class Beans_Report_Purchaseorders extends Beans_Report {

	protected $_date;
	protected $_vendor_id;
	protected $_days_old_minimum;
	protected $_balance_filter;

	/**
	 * Create a new account
	 * @param array $data fields => values to create an account.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_vendor_id = ( isset($data->vendor_id) )
						 ? $data->vendor_id
						 : NULL;

		$this->_days_old_minimum = ( isset($data->days_old_minimum) )
								  ? $data->days_old_minimum
								  : NULL;

		$this->_balance_filter = ( isset($data->balance_filter) )
							   ? $data->balance_filter
							   : NULL;
	}

	protected function _execute()
	{
		if( $this->_vendor_id AND
			! $this->_load_vendor($this->_vendor_id)->loaded() )
			throw new Exception("Invalid report vendor ID: vendor not found.");

		// Look up all purchase IDs
		
		$purchase_ids_query = 'SELECT id FROM forms WHERE type = "purchase" AND date_due IS NULL ';

		if( $this->_vendor_id )
			$purchase_ids_query .= ' AND entity_id = "'.$this->_vendor_id.'" ';

		if( $this->_days_old_minimum )
			$purchase_ids_query .= ' AND date_created <= DATE("'.date("Y-m-d",strtotime("-".$this->_days_old_minimum." Days")).'") ';

		if( $this->_balance_filter )
		{
			if( $this->_balance_filter == "unpaid" )
			{
				$purchase_ids_query .= ' AND ( total - balance ) = 0 ';
			}
			else if( $this->_balance_filter == "paid" )
			{
				$purchase_ids_query .= ' AND ( total - balance ) != 0 ';
			}
			else
			{
				throw new Exception("Invalid balance_filter: must be paid or unpaid.");
			}
		}

		$purchase_ids_query .= ' ORDER BY date_created ASC, id ASC ';

		$purchase_ids = DB::Query(Database::SELECT, $purchase_ids_query)->execute()->as_array();

		/*
		$purchases = ORM::Factory('form')->
			where('type','=','purchase')->
			where('balance','!=',0.00);

		if( $this->_vendor_id )
			$purchases = $purchases->where('entity_id','=',$this->_vendor_id);

		if( $this->_days_old_minimum )
			$purchases = $purchases->where('date_created','<=',date("Y-m-d",strtotime("-".$this->_days_old_minimum." Days")));
		
		
		$purchases = $purchases->where('date_due','IS',NULL)->order_by('date_created','ASC')->find_all();
		*/
		
		$vendors = array();

		$timestamp_today = strtotime(date("Y-m-d"));

		$balance_total = 0.00;
		$balances = array(
			'90' => 0.00,
			'60' => 0.00,
			'30' => 0.00,
			'0' => 0.00,
			'current' => 0.00,
		);

		foreach( $purchase_ids as $purchase_id )
		{
			$purchase = ORM::Factory('form_purchase', $purchase_id);

			if( ! isset($vendors[$purchase->entity_id]) )
			{
				$vendors[$purchase->entity_id] = new stdClass;
				$vendors[$purchase->entity_id]->vendor_company_name = $purchase->entity->company_name;
				$vendors[$purchase->entity_id]->vendor_phone_number = $purchase->entity->phone_number;
				$vendors[$purchase->entity_id]->purchases = array();
				$vendors[$purchase->entity_id]->balance_total = 0.00;
				$vendors[$purchase->entity_id]->balances = array(
					'90' => 0.00,
					'60' => 0.00,
					'30' => 0.00,
					'0' => 0.00,
					'current' => 0.00,
				);
			}

			$report_purchase = (object)array(
				'id' => $purchase->id,
				'date_created' => $purchase->date_created,
				'date_due' => $purchase->date_due,
				'purchase_number' => $purchase->code,
				'balance' => $purchase->balance,
				'days_late' => round(($timestamp_today - strtotime($purchase->date_created)) / 86400),
			);
			
			$days_range = 'current';
			if( $report_purchase->days_late >= 90 )
				$days_range = '90';
			else if( $report_purchase->days_late >= 60 )
				$days_range = '60';
			else if( $report_purchase->days_late >= 30 )
				$days_range = '30';
			else if( $report_purchase->days_late > 0 )
				$days_range = '0';
			

			$vendors[$purchase->entity_id]->balances[$days_range] = $this->_beans_round( $vendors[$purchase->entity_id]->balances[$days_range] + $report_purchase->balance );
			$balances[$days_range] = $this->_beans_round( $balances[$days_range] + $report_purchase->balance );
			
			$vendors[$purchase->entity_id]->balance_total = $this->_beans_round( $vendors[$purchase->entity_id]->balance_total + $report_purchase->balance );
			$balance_total = $this->_beans_round( $balance_total + $report_purchase->balance );

			$vendors[$purchase->entity_id]->purchases[] = $report_purchase;
		}
		
		return (object)array(
			'date' => date("Y-m-d"),
			'vendor_id' => $this->_vendor_id,
			'balance_filter' => $this->_balance_filter,
			'vendors' => $vendors,
			'total_vendors' => count($vendors),
			'balance_total'	=> $balance_total,
			'balances' => $balances,
		);
	}

}