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


class View_Accounts_Startingbalance extends View_Template {
	
	// REQUIRES $this->account_chart_result
	
	public function accounts()
	{
		$return_array = array();

		$return_array = $this->_flatten_accounts_array($this->account_chart_result->data->accounts);

		return $return_array;
	}

	private function _flatten_accounts_array($accounts,$level = 0)
	{
		$return_array = array();
		
		foreach( $accounts as $account )
		{
			$return_array[] = array(
				'id' => $account->id,
				'name' => $account->name,
				'bankaccount' => ( isset($account->type) AND 
									isset($account->type->code) AND
									$account->type->code == "bankaccount" ) ? TRUE : FALSE,
				'indent_level_px' => ( $level * 25 ),
				'reserved' => ( isset($account->reserved) AND 
								$account->reserved )
						   ? TRUE
						   : FALSE,
				'top_level' => ( $level == 0 ) ? TRUE : FALSE,
			);

			if( isset($account->accounts) AND 
				count($account->accounts) )
				$return_array = array_merge($return_array,$this->_flatten_accounts_array($account->accounts,($level+1)));
		}

		return $return_array;
	}

}