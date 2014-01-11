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

class Beans_Report_Balance extends Beans_Report {

	protected $_date;

	/**
	 * Create a new account
	 * @param array $data fields => values to create an account.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_date = ( isset($data->date) )
					 ? $data->date
					 : NULL;
	}

	protected function _execute()
	{
		if( ! $this->_date ) 
			throw new Exception("Invalid report date: none provided.");

		if( $this->_date != date("Y-m-d",strtotime($this->_date)) )
			throw new Exception("Invalid report date: must be in format YYYY-MM-DD.");
		
		// T2 Accounts ( just below top level )
		$t2_accounts = array();

		foreach( ORM::Factory('account')->where('parent_account_id','IS',NULL)->find_all() as $top_level_account )
			foreach( $top_level_account->child_accounts->find_all() as $t2_account )
				$t2_accounts[] = $t2_account;
		
		//
		// Query for our accounts - we're interested in 
		// 
		$account_types = array();

		$account_types['asset'] = new stdClass;
		$account_types['asset']->name = "Assets";
		$account_types['asset']->balance = 0.00;
		$account_types['asset']->accounts = array();
		
		
		$account_types['liability'] = new stdClass;
		$account_types['liability']->name = "Liabilities";
		$account_types['liability']->balance = 0.00;
		$account_types['liability']->accounts = array();

		$account_types['equity'] = new stdClass;
		$account_types['equity']->name = "Equity";
		$account_types['equity']->balance = 0.00;
		$account_types['equity']->accounts = array();
		
		// $array is blank - fill it in.
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
				$account_types[$type_key]->accounts[$key] = $this->_generate_account_balance($account,$this->_date,FALSE,($type_key == 'equity' ? TRUE : FALSE));
			}
		}

		foreach( $account_types as $type_key => $type )
		{
			$bal = $this->_generate_account_balance_total($type->accounts);
			$account_types[$type_key]->balance = $bal;
		}

		// Net Income Year = Last un-closed year.
		$fye_transaction = ORM::Factory('transaction')->
							where('close_books','IS NOT',NULL)->
							order_by('close_books','DESC')->
							find();

		$income_year = date("Y",1);

		if( $fye_transaction->loaded() )
			$income_year = intval(substr($fye_transaction->close_books,0,4))+1;

		// Get Net Income
		$income_report = new Beans_Report_Income($this->_beans_data_auth((object)array(
			'date_start' => $income_year.'-01-01',
			'date_end' => $this->_date
		)));
		$income_report_result = $income_report->execute();

		if( ! $income_report_result->success )
			throw new Exception(
				"Unexpected error: could not get net income: ".
				$income_report_result->error.
				$income_report_result->auth_error.
				$income_report_result->config_error
			);

		$net_income = $income_report_result->data->account_types['net']->balance;

		$account_types['equity']->balance = $this->_beans_round( $account_types['equity']->balance + $net_income );

		$account_types['equity']->accounts[] = (object)array(
			'name' => "Net Income",
			'id' => NULL,
			'type' => 'equity',
			'table_sign' => 1,
			'balance' => $net_income,
		);

		$total_liabilities_equity = $this->_beans_round( $account_types['liability']->balance + $account_types['equity']->balance );

		$account_types['net'] = new stdClass;
		$account_types['net']->name = "Total Liabilities and Equity";
		$account_types['net']->balance = $total_liabilities_equity;
		$account_types['net']->accounts = array();

		$return_array[] = array(
			'name' => '',
			'balance_formatted' => 
				( $total_liabilities_equity < 0 ? '<span class="text-red">-' : '' ).
				number_format(abs($total_liabilities_equity),2,'.',',').
				( $total_liabilities_equity < 0 ? '</span>' : '' ),
		);

		return (object)array(
			'date' => $this->_date,
			'account_types' => $account_types,
		);
	}

	
}