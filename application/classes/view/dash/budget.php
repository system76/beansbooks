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


class View_Dash_Budget extends View_Template {
	
	public function date_months()
	{
		$months = parent::months_backward_36();

		if( ! isset($this->report_budget_result) )
			return $months;

		foreach( $months as $index => $month )
		{
			if( $month['YYYY-MM'] == substr($this->report_budget_result->data->date_start,0,7) )
				$months[$index]['selected'] = TRUE;
			else
				$months[$index]['selected'] = FALSE;
		}

		return $months;
	}

	public function months()
	{
		$return_array = array();

		for( $i = 0; $i <= 12; $i++ )
		{
			$return_array[] = array(
				'value' => $i,
				'selected' => ( isset($this->months) AND 
								$i == $this->months )
						   ? TRUE
						   : FALSE,
			);
		}

		return $return_array;
	}

	public function report_date_start()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		return $this->report_budget_result->data->date_start;
	}

	public function report_date_end()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		return $this->report_budget_result->data->date_end;
	}

	public function report_months()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		$report_months = array();

		for( $i = 0; $i < 12; $i++ )
		{
			$report_months[$i] = array();

			if( ! isset($this->report_budget_result->data->date_ranges[$i]) )
			{
				$report_months[$i] = array();
				$report_months[$i]['label'] = FALSE;
			}
			else
			{
				$report_months[$i] = array();
				$report_months[$i]['label'] = date("F",strtotime($this->report_budget_result->data->date_ranges[$i]->date_start));
			}
		}

		return $report_months;
	}

	public function income_accounts()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		$return_array = array();

		$j = 0;
		foreach( $this->report_budget_result->data->account_types['income'] as $account )
		{
			$return_array[] = $this->_summarize_account($account,$j++);
		}

		return $return_array;
	}

	public function income_total()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		return $this->_summarize_account($this->report_budget_result->data->account_types['income_total']);
	}

	public function expense_accounts()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		$return_array = array();

		$j = 0;
		foreach( $this->report_budget_result->data->account_types['expense'] as $account )
		{
			$return_array[] = $this->_summarize_account($account,$j++);
		}

		return $return_array;
	}

	public function expense_total()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		return $this->_summarize_account($this->report_budget_result->data->account_types['expense_total']);
	}

	public function net()
	{
		if( ! isset($this->report_budget_result) )
			return FALSE;

		return $this->_summarize_account($this->report_budget_result->data->account_types['net']);
	}

	private function _summarize_account($account,$j = 0)
	{
		$return_array = array();

		$return_array['name'] = $account->name;
		$return_array['total'] = 0.00;
		$return_array['date_values'] = array();
		$return_array['odd'] = ( $j % 2 ) ? TRUE : FALSE;

		for( $i = 0; $i < 12; $i++ )
		{
			if( ! isset($account->date_ranges[$i]) )
			{
				$return_array['date_values'][$i] = array(
					'amount_formatted' => '&nbsp;',
					'odd' => ( $j % 2 ) ? TRUE : FALSE,
				);
			}
			else
			{
				$return_array['date_values'][$i] = array(
					'amount' => $account->date_ranges[$i],
					'amount_formatted' => 
						( $account->date_ranges[$i] < 0 ? '<span class="text-red">-' : '' ).
						number_format(abs($account->date_ranges[$i]),2,'.',',').
						( $account->date_ranges[$i] < 0 ? '</span>' : '' ),
					'odd' => ( $j % 2 ) ? TRUE : FALSE,
				);
				$return_array['total'] = round( $return_array['total'] + $account->date_ranges[$i],2,PHP_ROUND_HALF_UP);
			}
		}

		$return_array['zero'] = ( $return_array['total'] == 0.00 ) ? TRUE : FALSE;
		$return_array['total_formatted'] = 
			( $return_array['total'] < 0 ? '<span class="text-red">-' : '' ).
			number_format(abs($return_array['total']),2,'.',',').
			( $return_array['total'] < 0 ? '</span>' : '' );

		return $return_array;
	}



	// 

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

		return $return_array;
	}

	private function _account_type_lines($accounts,$level = 0)
	{
		$return_array = array();

		foreach( $accounts as $account )
		{
			$return_array[] = array(
				'name' => $account->name,
				'indent_level_px' => ($level * 25),
				'balance_formatted' => 
					( $account->balance < 0 ? '<span class="text-red">-' : '' ).
					number_format(abs($account->balance),2,'.',',').
					( $account->balance < 0 ? '</span>' : '' ),
				'total_formatted' => FALSE,
				'zero' => ( $level != 0 AND $account->balance == 0 ),
			);

			if( isset($account->accounts) )
				$return_array = array_merge($return_array,$this->_account_type_lines($account->accounts,($level+1)));

			if( $level == 0 )
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