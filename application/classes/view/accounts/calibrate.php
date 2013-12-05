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


class View_Accounts_Calibrate extends View_Template {
	
	// Received $this->account_lookup_result
	// Received $this->account_transactions_result
	
	public function account()
	{
		if( ! isset($this->account_calibrate_result) )
			return FALSE;

		$account = array();

		$account['id'] = $this->account_lookup_result->data->account->id;
		$account['name'] = $this->account_lookup_result->data->account->name;
		$account['code'] = $this->account_lookup_result->data->account->code;
		$account['balance'] = $this->account_lookup_result->data->account->balance;
		$account['table_sign'] = (  isset($this->account_lookup_result->data->account->type) AND 
									isset($this->account_lookup_result->data->account->type->table_sign) )
							   ? $this->account_lookup_result->data->account->type->table_sign
							   : 0;
		$account['reconcilable'] = ( $this->account_lookup_result->data->account->reconcilable )
								 ? TRUE
								 : FALSE;
		$account['top_level'] = ( isset($this->account_lookup_result->data->account->type->id) AND
								  $this->account_lookup_result->data->account->type->id )
							  ? FALSE
							  : TRUE;
		$account['deposit'] = $this->account_lookup_result->data->account->reconcilable ? TRUE : FALSE;
		$account['payment'] = $this->account_lookup_result->data->account->reconcilable ? TRUE : FALSE;
		$account['receivable'] = $this->account_lookup_result->data->account->reconcilable ? TRUE : FALSE;
		$account['payable'] = $this->account_lookup_result->data->account->reconcilable ? TRUE : FALSE;
		$account['writeoff'] = $this->account_lookup_result->data->account->reconcilable ? TRUE : FALSE;

		return $account;
	}

	public function calibrated_transactions()
	{
		if( ! isset($this->account_calibrate_result) )
			return FALSE;

		$account_transactions = array();

		foreach( $this->account_calibrate_result->data->calibrated_transactions as $calibrated_transaction )
		{
			$account_transactions[] = array(
				'id' => $calibrated_transaction->id,
				'date' => $calibrated_transaction->date,
				'transaction_id' => $calibrated_transaction->transaction_id,
				'amount' => $calibrated_transaction->amount,
				'balance' => $calibrated_transaction->balance,
				'calibrated_balance' => $calibrated_transaction->calibrated_balance,
				'balance_changed' => ( $calibrated_transaction->balance != $calibrated_transaction->calibrated_balance ) ? TRUE : FALSE,
			);
		}

		return $account_transactions;
	}

	public function calibrated_balance_shift()
	{
		if( ! isset($this->account_calibrate_result) )
			return FALSE;

		return $this->account_calibrate_result->data->calibrated_balance_shift;
	}

}