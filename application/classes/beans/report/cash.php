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

class Beans_Report_Cash extends Beans_Report {

	protected $_date;

	protected $_transaction_sale_deferred_income_account_id;
	protected $_transaction_sale_deferred_liability_account_id;

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

		$this->_transaction_sale_deferred_income_account_id = $this->_beans_setting_get('sale_deferred_income_account_id');
		$this->_transaction_sale_deferred_liability_account_id = $this->_beans_setting_get('sale_deferred_liability_account_id');
	}

	protected function _execute()
	{
		if( ! $this->_date ) 
			throw new Exception("Invalid report date: none provided.");

		if( $this->_date != date("Y-m-d",strtotime($this->_date)) )
			throw new Exception("Invalid report date: must be in format YYYY-MM-DD.");

		// We'll exclude these two accounts from our generated chart.
		$excluded_account_ids = array(
			$this->_transaction_sale_deferred_income_account_id,
			$this->_transaction_sale_deferred_liability_account_id,
		);
		
		// T2 Accounts ( just below top level )
		$t2_accounts = array();

		foreach( ORM::Factory('account')->where('parent_account_id','IS',NULL)->find_all() as $top_level_account )
			foreach( $top_level_account->child_accounts->find_all() as $t2_account )
				$t2_accounts[] = $t2_account;
		
		//
		// Query for our accounts - we're interested in 
		// 
		$account_types = array();

		$account_types['cash'] = new stdClass;
		$account_types['cash']->name = "Cash";
		$account_types['cash']->balance = 0.00;
		$account_types['cash']->direction = 1;
		$account_types['cash']->codes = array(
			'cash',
			'bankaccount',
		);
		$account_types['cash']->accounts = array();
		
		$account_types['accountsreceivable'] = new stdClass;
		$account_types['accountsreceivable']->name = "Accounts Receivable";
		$account_types['accountsreceivable']->balance = 0.00;
		$account_types['accountsreceivable']->direction = 1;
		$account_types['accountsreceivable']->codes = array(
			'accountsreceivable',
			'pending_ar',
			// 'pending_income'
		);
		$account_types['accountsreceivable']->accounts = array();

		$account_types['shorttermdebt'] = new stdClass;
		$account_types['shorttermdebt']->name = "Short Term Debt";
		$account_types['shorttermdebt']->balance = 0.00;
		$account_types['shorttermdebt']->direction = -1;
		$account_types['shorttermdebt']->codes = array(
			'shorttermdebt',
			'accountspayable',
			'pending_ap',
			// 'pending_cost'
		);
		$account_types['shorttermdebt']->accounts = array();
		
		// $array is blank - fill it in.
		foreach( $account_types as $code => $account_type )
		{
			foreach( $t2_accounts as $t2_account )
			{
				$t2_result = $this->_build_code_chart($t2_account,$account_type->codes,TRUE,$excluded_account_ids);
				if( $t2_result )
					$account_types[$code]->accounts[] = $t2_result;
			}
		}

		foreach( $account_types as $type => $account_type )
		{
			foreach( $account_type->accounts as $index => $account )
			{
				$account_types[$type]->accounts[$index] = $this->_generate_account_balance($account,$this->_date);
			}
		}
		
		$net = 0.00;

		foreach( $account_types as $type => $account_type )
		{
			$account_types[$type]->balance_total = $this->_generate_account_balance_total($account_type->accounts);
			$net = $this->_beans_round( $net + ( $account_types[$type]->direction * $account_types[$type]->balance_total ) );
		}
		
		return (object)array(
			'date' => $this->_date,
			'account_types' => $account_types,
			'net' => $net,
		);
	}

	
}