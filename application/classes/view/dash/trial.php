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


class View_Dash_Trial extends View_Template {

	public function report_date()
	{
		if( ! isset($this->report_trial_result) )
			return FALSE;

		return $this->report_trial_result->data->date;
	}

	public function debit_total_formatted()
	{
		if( ! isset($this->report_trial_result) )
			return FALSE;

		return number_format(abs($this->report_trial_result->data->debit_total),2,'.',',');
	}

	public function credit_total_formatted()
	{
		if( ! isset($this->report_trial_result) )
			return FALSE;

		return number_format(abs($this->report_trial_result->data->credit_total),2,'.',',');
	}

	public function account_lines()
	{
		$return_array = $this->_flatten_accounts_array($this->report_trial_result->data->accounts);

		$j = 1;
		foreach( $return_array as $index => $account )
			$return_array[$index]['odd'] = ( $j++ % 2 ) ? TRUE : FALSE;

		return $return_array;
	}

	private function _flatten_accounts_array($accounts,$level = 0)
	{
		$return_array = array();

		foreach( $accounts as $account )
		{
			$return_array[] = array(
				'name' => $account->name,
				'indent_level_px' => ( $level * 25 ),
				'credit_formatted' => ( ( $account->table_sign * $account->balance ) > 0 ) 
								   ? number_format(abs($account->balance),2,'.',',')
								   : FALSE,
				'debit_formatted' => ( ( $account->table_sign * $account->balance ) < 0 ) 
								  ? number_format(abs($account->balance),2,'.',',')
								  : FALSE,
			);

			if( isset($account->accounts) AND 
				count($account->accounts) )
				$return_array = array_merge($return_array,$this->_flatten_accounts_array($account->accounts,($level+1)));
		}

		return $return_array;
	}

}
