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

class Beans_Report_Income extends Beans_Report {

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

		$t2_accounts = array();

		foreach( ORM::Factory('account')->where('parent_account_id','IS',NULL)->find_all() as $top_level_account )
			foreach( $top_level_account->child_accounts->find_all() as $t2_account )
				$t2_accounts[] = $t2_account;

		$account_types = array();

		$account_types['income'] = new stdClass;
		$account_types['income']->name = "Income";
		$account_types['income']->balance = 0.00;
		$account_types['income']->accounts = array();
		
		$account_types['cost of goods sold'] = new stdClass;
		$account_types['cost of goods sold']->name = "Cost of Goods Sold";
		$account_types['cost of goods sold']->balance = 0.00;
		$account_types['cost of goods sold']->accounts = array();

		$account_types['expense'] = new stdClass;
		$account_types['expense']->name = "Expenses";
		$account_types['expense']->balance = 0.00;
		$account_types['expense']->accounts = array();

		foreach( $account_types as $type => $array )
		{
			foreach( $t2_accounts as $t2_account )
			{
				$t2_result = $this->_build_type_chart($t2_account,$type);
				if( $t2_result )
					$account_types[$type]->accounts[] = $t2_result;
			}
		}

		foreach( $account_types as $type_key => $type )
		{
			foreach( $type->accounts as $key => $account )
			{
				$account_types[$type_key]->accounts[$key] = $this->_generate_account_balance($account,$this->_date_end,$this->_date_start);
			}
		}

		foreach( $account_types as $type_key => $type )
		{
			$bal = $this->_generate_account_balance_total($type->accounts);
			$account_types[$type_key]->balance = $bal;
		}

		$account_types['gross'] = new stdClass;
		$account_types['gross']->name = "Gross Income";
		$account_types['gross']->balance = $this->_beans_round( $account_types['income']->balance + $account_types['cost of goods sold']->balance );
		$account_types['gross']->accounts = array();

		$account_types['net'] = new stdClass;
		$account_types['net']->name = "Net Income";
		$account_types['net']->balance = $this->_beans_round( $account_types['income']->balance + $account_types['cost of goods sold']->balance - $account_types['expense']->balance );
		$account_types['net']->accounts = array();

		$arranged_account_types = array();

		$arranged_account_types['income'] = $account_types['income'];
		$arranged_account_types['costofgoods'] = $account_types['cost of goods sold'];
		$arranged_account_types['gross'] = $account_types['gross'];
		$arranged_account_types['expense'] = $account_types['expense'];
		$arranged_account_types['net'] = $account_types['net'];

		return (object)array(
			'date_start' => $this->_date_start,
			'date_end' => $this->_date_end,
			'account_types' => $arranged_account_types,
		);
	}


}