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

class Beans_Report_Budget extends Beans_Report {

	protected $_date_start;
	protected $_date_end;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_date_start = ( isset($data->date_start) )
					 ? $data->date_start
					 : NULL;

		$this->_date_end = ( isset($data->date_end) )
					 ? $data->date_end
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

		$date_ranges = $this->_generate_date_ranges($this->_date_start,$this->_date_end,"month",FALSE);
		
		$account_types = array();

		$account_types['income_total'] = array();
		$account_types['income_subtotal'] = array();
		$account_types['costofgoods_total'] = array();
		
		$account_types['income'] = $this->_build_type_array('income');
		foreach( $account_types['income'] as $index => $account )
		{
			$account_types['income'][$index]->date_ranges = array();
			foreach( $date_ranges as $date_index => $date_range )
			{
				$account_types['income'][$index]->date_ranges[$date_index] = $this->_generate_simple_account_balance($account->id,$account->table_sign,$date_range->date_end,$date_range->date_start);
				if( ! isset($account_types['income_total'][$date_index]) )
					$account_types['income_total'][$date_index] = 0.00;

				$account_types['income_total'][$date_index] = $this->_beans_round( $account_types['income_total'][$date_index] + $account_types['income'][$index]->date_ranges[$date_index] );

				if( ! isset($account_types['income_subtotal'][$date_index]) )
					$account_types['income_subtotal'][$date_index] = 0.00;

				$account_types['income_subtotal'][$date_index] = $this->_beans_round( $account_types['income_subtotal'][$date_index] + $account_types['income'][$index]->date_ranges[$date_index] );
			}
		}

		$account_types['costofgoods'] = $this->_build_type_array('cost of goods sold');
		foreach( $account_types['costofgoods'] as $index => $account )
		{
			$account_types['costofgoods'][$index]->date_ranges = array();
			foreach( $date_ranges as $date_index => $date_range )
			{
				$account_types['costofgoods'][$index]->date_ranges[$date_index] = $this->_generate_simple_account_balance($account->id,$account->table_sign,$date_range->date_end,$date_range->date_start);
				if( ! isset($account_types['income_total'][$date_index]) )
					$account_types['income_total'][$date_index] = 0.00;

				if( ! isset($account_types['costofgoods_subtotal'][$date_index]) )
					$account_types['costofgoods_subtotal'][$date_index] = 0.00;

				$account_types['costofgoods_subtotal'][$date_index] = $this->_beans_round( $account_types['costofgoods_subtotal'][$date_index] + $account_types['costofgoods'][$index]->date_ranges[$date_index] );
				$account_types['income_total'][$date_index] = $this->_beans_round( $account_types['income_total'][$date_index] + $account_types['costofgoods'][$index]->date_ranges[$date_index] );
			}
		}

		$account_types['expense_total'] = array();

		$account_types['expense'] = $this->_build_type_array('expense');
		foreach( $account_types['expense'] as $index => $account )
		{
			$account_types['expense'][$index]->date_ranges = array();
			foreach( $date_ranges as $date_index => $date_range )
			{
				$account_types['expense'][$index]->date_ranges[$date_index] = $this->_generate_simple_account_balance($account->id,$account->table_sign,$date_range->date_end,$date_range->date_start);
				if( ! isset($account_types['expense_total'][$date_index]) )
					$account_types['expense_total'][$date_index] = 0.00;

				$account_types['expense_total'][$date_index] = $this->_beans_round( $account_types['expense_total'][$date_index] + $account_types['expense'][$index]->date_ranges[$date_index] );
			}
		}

		$account_types['net'] = array();
		
		foreach( $account_types['income_total'] as $index => $income )
			$account_types['net'][$index] = $this->_beans_round( $income - $account_types['expense_total'][$index]);
		
		$account_types['income_total'] = (object)array(
			'name' => "Gross Profit",
			'date_ranges' => $account_types['income_total'],
		);

		$account_types['income_subtotal'] = (object)array(
			'name' => "Income Subtotal",
			'date_ranges' => $account_types['income_subtotal'],
		);

		$account_types['expense_total'] = (object)array(
			'name' => "Total Expenses",
			'date_ranges' => $account_types['expense_total'],
		);
		
		$account_types['costofgoods_subtotal'] = (object)array(
			'name' => "Cost of Goods Subtotal",
			'date_ranges' => $account_types['costofgoods_subtotal'],
		);

		$account_types['net'] = (object)array(
			'name' => "Net Profit",
			'date_ranges' => $account_types['net'],
		);

		return (object)array(
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'date_ranges' => $date_ranges,
			'account_types' => $account_types,
		);
	}

	private function _flatten_accounts($accounts)
	{
		$return_array = array();

		foreach( $accounts as $account )
		{
			$return_array[$account->id] = $account;
			if( isset($account->accounts) AND 
				count($account->accounts) )
				$return_array = array_merge($return_array,$account->accounts);
		}

		return $return_array;
	}
}