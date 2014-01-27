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

class Beans_Report_Trial extends Beans_Report {

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
		
		$account_chart = new Beans_Account_Chart($this->_beans_data_auth());
		$account_chart_result = $account_chart->execute();

		if( ! $account_chart_result->success )
			throw new Exception("Error loading chart of accounts: ".$account_chart_result->auth_error.$account_chart_result->error);

		$accounts = $this->_generate_simple_accounts($account_chart_result->data->accounts,$this->_date);
		
		$credit_total = $this->_generate_credit_total($accounts);
		$debit_total = $this->_generate_debit_total($accounts);

		return (object)array(
			'date' => $this->_date,
			'accounts' => $accounts,
			'debit_total' => $debit_total,
			'credit_total' => $credit_total,
		);
	}

	private function _generate_simple_accounts($accounts,$date)
	{
		$return_array = array();

		foreach( $accounts as $account )
		{
			if( ! isset($account->type->code) ||
				strpos($account->type->code,'pending_') === FALSE )
			{
				$return_account = new stdClass;
				$return_account->id = $account->id;
				$return_account->name = $account->name;
				
				if( isset($account->type->table_sign) )
				{
					$return_account->table_sign = $account->type->table_sign;
					$return_account->balance = $this->_generate_simple_account_balance($account->id,$account->type->table_sign,$date,FALSE,TRUE);  // Trial balance always includes FYE transactions.
				}
				else
				{
					$return_account->table_sign = NULL;
					$return_account->balance = NULL;
				}

				if( isset($account->accounts) AND 
					count($account->accounts) )
					$return_account->accounts = $this->_generate_simple_accounts($account->accounts,$date);
				
				if( (
						isset($return_account->accounts) &&
						count($return_account->accounts) 
					) ||
					$return_account->table_sign !== NULL )
					$return_array[] = $return_account;
			}
		}

		return $return_array;
	}

	private function _generate_credit_total($accounts)
	{
		$total = 0.00;

		foreach( $accounts as $account )
		{
			if( ( $account->table_sign * $account->balance ) > 0 )
				$total = $this->_beans_round( $total + abs($account->balance) );

			if( isset($account->accounts) AND
				count($account->accounts) )
				$total = $this->_beans_round( $total + $this->_generate_credit_total($account->accounts) );
		}

		return $total;
	}

	private function _generate_debit_total($accounts)
	{
		$total = 0.00;

		foreach( $accounts as $account )
		{
			if( ( $account->table_sign * $account->balance ) < 0 )
				$total = $this->_beans_round( $total + abs($account->balance) );

			if( isset($account->accounts) AND
				count($account->accounts) )
				$total = $this->_beans_round( $total + $this->_generate_debit_total($account->accounts) );
		}

		return $total;
	}

	
}