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


class View_Dash_Income extends View_Template {
	
	public function report_date_start()
	{
		if( ! isset($this->report_income_result) )
			return FALSE;

		return $this->report_income_result->data->date_start;
	}

	public function report_date_end()
	{
		if( ! isset($this->report_income_result) )
			return FALSE;

		return $this->report_income_result->data->date_end;
	}

	public function account_types()
	{
		if( ! isset($this->report_income_result) )
			return FALSE;
		
		$return_array = array();

		foreach( $this->report_income_result->data->account_types as $account_type )
			$return_array[] = $this->_account_type_array($account_type);

		/*
		$total_liabilities_equity = $this->report_income_result->data->account_types['liability']->balance + $this->report_income_result->data->account_types['equity']->balance;

		$return_array[] = array(
			'name' => 'Total Liabilities and Equity',
			'balance_formatted' => 
				( $total_liabilities_equity < 0 ? '<span class="text-red">-' : '' ).
				number_format(abs($total_liabilities_equity),2,'.',',').
				( $total_liabilities_equity < 0 ? '</span>' : '' ),
		);
		*/
		return $return_array;
	}

	private function _account_type_array($account_type)
	{
		$return_array = array();

		$return_array['name'] = $account_type->name;
		$return_array['balance_formatted'] = 
			( $account_type->balance < 0 ? '<span class="text-red">-' : '' ).
			number_format(abs($account_type->balance),2,'.',',').
			( $account_type->balance < 0 ? '</span>' : '' );

		$return_array['account_type_lines'] = $this->_account_type_lines($account_type->accounts);

		$return_array['show_type_total'] = ( count($return_array['account_type_lines']) ) ? TRUE : FALSE;

		return $return_array;
	}

	private function _account_type_lines($accounts,$level = 0)
	{
		$return_array = array();

		foreach( $accounts as $account )
		{
			$account_array = array(
				'name' => $account->name,
				'indent_level_px' => ($level * 25),
				'balance_formatted' => 
					( $account->balance < 0 ? '<span class="text-red">-' : '' ).
					number_format(abs($account->balance),2,'.',',').
					( $account->balance < 0 ? '</span>' : '' ),
				'total_formatted' => FALSE,
				'zero' => ( $account->balance == 0 ),
			);

			$child_account_lines = array();

			if( isset($account->accounts) )
				$child_account_lines = $this->_account_type_lines($account->accounts,($level+1));
			
			$child_accounts_nonzero = FALSE;
			foreach( $child_account_lines as $child_account_line )
				$child_accounts_nonzero = ( $child_accounts_nonzero OR ! $child_account_line['zero'] );

			if( $child_accounts_nonzero )
				$account_array['zero'] = FALSE;

			$return_array[] = $account_array;
			$return_array = array_merge($return_array,$child_account_lines);

			if( $level == 0 AND
				$child_accounts_nonzero )
			{
				$account_total = $this->_account_total($account);
				$return_array[] = array(
					'name' => 'Total',
					'indent_level_px' => (($level + 1) * 25),
					'balance_formatted' => FALSE,
					'total_formatted' => 
						( $account_total < 0 ? '<span class="text-red">-' : '' ).
						number_format(abs($account_total),2,'.',',').
						( $account_total < 0 ? '</span>' : '' ),
					'zero' => ( $account_total == 0 ),
				);
			}
		}

		return $return_array;
	}

	private function _account_total($account)
	{
		$total = 0.00;

		if( $account->balance )
			$total = round($total + $account->balance,2);

		if( isset($account->accounts) )
			foreach( $account->accounts as $child_account )
				$total = round( ( $total + $this->_account_total($child_account) ),2);

		return $total;
	}
}