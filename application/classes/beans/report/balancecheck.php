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

class Beans_Report_Balancecheck extends Beans_Report {

	protected $_date;	

	/**
	 * Internal function to check if the balance sheet is squared up on a specific date.
	 * This is primarilly used to verify the integrity of accounts.
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
		
		$account_types = array();

		$account_types['asset'] = new stdClass;
		$account_types['asset']->name = "Assets";
		$account_types['asset']->balance = 0.00;
		$account_types['asset']->date_start = FALSE;
		$account_types['asset']->date_end = $this->_date;
		$account_types['asset']->accounts = $this->_build_type_array('asset');
		
		$account_types['liability'] = new stdClass;
		$account_types['liability']->name = "Liabilities";
		$account_types['liability']->balance = 0.00;
		$account_types['liability']->date_start = FALSE;
		$account_types['liability']->date_end = $this->_date;
		$account_types['liability']->accounts = $this->_build_type_array('liability');

		$account_types['equity'] = new stdClass;
		$account_types['equity']->name = "Equity";
		$account_types['equity']->balance = 0.00;
		$account_types['equity']->date_start = FALSE;
		$account_types['equity']->date_end = $this->_date;
		$account_types['equity']->accounts = $this->_build_type_array('equity');

		// Income = Last FYE through $this->_date
		$fye_transaction = ORM::Factory('transaction')->
							where('close_books','IS NOT',NULL)->
							where('date','<',$this->_date)->
							order_by('close_books','DESC')->
							find();

		$income_year = date("Y",1);

		if( $fye_transaction->loaded() )
			$income_year = intval(substr($fye_transaction->close_books,0,4))+1;
		
		$income_date_start = $income_year.'-01-01';

		$account_types['income'] = new stdClass;
		$account_types['income']->name = "Income";
		$account_types['income']->balance = 0.00;
		$account_types['income']->date_start = $income_date_start;
		$account_types['income']->date_end = $this->_date;
		$account_types['income']->accounts = $this->_build_type_array('income');
		
		$account_types['cost of goods sold'] = new stdClass;
		$account_types['cost of goods sold']->name = "Cost of Goods Sold";
		$account_types['cost of goods sold']->balance = 0.00;
		$account_types['cost of goods sold']->date_start = $income_date_start;
		$account_types['cost of goods sold']->date_end = $this->_date;
		$account_types['cost of goods sold']->accounts = $this->_build_type_array('cost of goods sold');

		$account_types['expense'] = new stdClass;
		$account_types['expense']->name = "Expenses";
		$account_types['expense']->balance = 0.00;
		$account_types['expense']->date_start = $income_date_start;
		$account_types['expense']->date_end = $this->_date;
		$account_types['expense']->accounts = $this->_build_type_array('expense');

		foreach( $account_types as $type_key => $type )
		{
			foreach( $type->accounts as $key => $account )
			{
				$account_types[$type_key]->accounts[$key] = $this->_generate_account_balance($account,$type->date_end,$type->date_start,($type_key == 'equity' ? TRUE : FALSE));
			}
		}

		foreach( $account_types as $type_key => $type )
			$account_types[$type_key]->balance = $this->_generate_account_balance_total($type->accounts);
		
		// Final Check = 
		// Asset + Liability =  Equity + Income - COGS - Expense
		$result = $this->_beans_round( 
			( $account_types['asset']->balance - $account_types['liability']->balance ) - 
			( $account_types['equity']->balance + $account_types['income']->balance + $account_types['cost of goods sold']->balance - $account_types['expense']->balance )
		);
		
		return (object)array(
			'date' => $this->_date,
			'balanced' => ( $result == 0.00 ? 1 : 0 ),
		);
	}
}