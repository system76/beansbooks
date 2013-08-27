<?php defined('SYSPATH') or die('No direct access allowed.');
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


class View_Dash_Ledger extends View_Template {
	
	public function report_date_start()
	{
		if( ! isset($this->report_ledger_result) )
			return date("Y-m-d",strtotime("-30 Days"));

		return $this->report_ledger_result->data->date_start;
	}

	public function report_date_end()
	{
		if( ! isset($this->report_ledger_result) )
			return date("Y-m-d");

		return $this->report_ledger_result->data->date_end;
	}

	public function account()
	{
		if( ! isset($this->report_ledger_result) )
			return FALSE;

		return $this->_account_array($this->report_ledger_result->data->account);
	}

	public function account_transactions()
	{
		if( ! isset($this->report_ledger_result) )
			return FALSE;

		$return_array = array();

		foreach( $this->report_ledger_result->data->account_transactions as $account_transaction )
			$return_array[] = $this->_account_transaction_array($account_transaction,$this->report_ledger_result->data->account->type->table_sign);
		
		foreach( $return_array as $i => $a )
			$return_array[$i]['odd'] = ( $i % 2 ) ? TRUE : FALSE;

		return $return_array;
	}

	protected function _account_transaction_array($account_transaction,$table_sign)
	{
		return array(
			'date' => $account_transaction->date,
			'check_number' => $account_transaction->check_number,
			'number' => $account_transaction->number,
			'description' => $account_transaction->description,
			'debit' => $account_transaction->debit,
			'debit_formatted' => ( $account_transaction->debit ? number_format(abs($account_transaction->debit),2,'.',',') : FALSE ),
			'credit' => $account_transaction->credit,
			'credit_formatted' => ( $account_transaction->credit ? number_format(abs($account_transaction->credit),2,'.',',') : FALSE ),
			'balance' => $account_transaction->balance,
			'balance_formatted' => number_format($table_sign * $account_transaction->balance,2,'.',','),
		);
	}

	public function all_accounts_chart_flat()
	{
		$all_accounts_flat = parent::all_accounts_chart_flat();

		foreach( $all_accounts_flat as $index => $account )
			$all_accounts_flat[$index]['selected'] = ( isset($this->report_ledger_result) AND 
													   $account['id'] == $this->report_ledger_result->data->account->id ) 
													? TRUE
													: FALSE;
		
		return $all_accounts_flat;
	}
}