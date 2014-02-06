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


class View_Accounts_Reconcile extends View_Template {
	
	// Received $this->account_lookup_result
	// Received $this->account_transactions_result
	
	public function last_reconcile()
	{	
		if( ! isset($this->account_reconcile_result) OR 
			count($this->account_reconcile_result->data->account_reconciles) == 0 )
			return FALSE;

		$last_reconcile_date = $this->account_reconcile_result->data->account_reconciles[0]->date;
		$reconcile_day = date('d',strtotime($last_reconcile_date));
		
		$next_year = date("Y",strtotime($last_reconcile_date));
		$next_month = intval(date("m",strtotime($last_reconcile_date)))+1;
		if( $next_month > 12 ) {
			$next_month = 1;
			$next_year++;
		}

		$next_month_formatted = ( $next_month < 10 )
							  ? '0'.$next_month
							  : $next_month;

		$days_in_next_month = intval(date('t',strtotime($next_year.'-'.$next_month_formatted.'-01')));
		
		$date_next = ( intval($reconcile_day) > $days_in_next_month )
				   ? $next_year.'-'.$next_month_formatted.'-'.$days_in_next_month
				   : $next_year.'-'.$next_month_formatted.'-'.$reconcile_day;
		
		return array(
			'date_next' => $date_next,
			'balance_end' => $this->account_reconcile_result->data->account_reconciles[0]->balance_end,
		);
	}

	private $_transactions_in;
	public function transactions_in()
	{
		if( ! isset($this->account_transactions_result) )
			return FALSE;

		if( ! isset($this->account_lookup_result) )
			return FALSE;
		
		if( $this->_transactions_in ) 
			return $this->_transactions_in;

		$beans_settings = $this->beans_settings();

		$this->_transactions_in = array();

		foreach( $this->account_transactions_result->data->transactions as $transaction ) 
		{
			foreach( $transaction->account_transactions as $account_transaction )
				if( $account_transaction->account->id == $this->account_lookup_result->data->account->id AND 
					$account_transaction->amount * $this->account_lookup_result->data->account->type->table_sign > 0 )
					$this->_transactions_in[] = array(
						'transaction_id' => $transaction->id,
						'id' => $account_transaction->id,
						'date' => $transaction->date,
						'code' => $transaction->code,
						'description' => $transaction->description,
						'amount' => $account_transaction->amount * $this->account_lookup_result->data->account->type->table_sign,
						'amount_formatted' => $beans_settings->company_currency.number_format(abs($account_transaction->amount),2,'.',','),
					);
		}

		return $this->_transactions_in;
	}

	private $_transactions_out;
	public function transactions_out()
	{
		if( ! isset($this->account_transactions_result) )
			return FALSE;

		if( ! isset($this->account_lookup_result) )
			return FALSE;
		
		if( $this->_transactions_out ) 
			return $this->_transactions_out;

		$beans_settings = $this->beans_settings();

		$this->_transactions_out = array();

		foreach( $this->account_transactions_result->data->transactions as $transaction ) 
		{
			foreach( $transaction->account_transactions as $account_transaction )
				if( $account_transaction->account->id == $this->account_lookup_result->data->account->id AND 
					$account_transaction->amount * $this->account_lookup_result->data->account->type->table_sign < 0 )
					$this->_transactions_out[] = array(
						'transaction_id' => $transaction->id,
						'id' => $account_transaction->id,
						'date' => $transaction->date,
						'code' => $transaction->check_number ? $transaction->check_number : $transaction->code,
						'description' => $transaction->description,
						'amount' => $account_transaction->amount * $this->account_lookup_result->data->account->type->table_sign,
						'amount_formatted' => $beans_settings->company_currency.number_format(abs($account_transaction->amount),2,'.',','),
					);
		}

		return $this->_transactions_out;
	}

	public function account()
	{
		if( ! isset($this->account_lookup_result) )
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

	public function transactions()
	{
		if( ! isset($this->account_transactions_result) )
			return FALSE;
		
		$transactions = array();

		if( ! count($this->account_transactions_result->data->transactions) )
			return FALSE;

		foreach( $this->account_transactions_result->data->transactions as $transaction )
		{
			$element = array();
			$element['id'] = $transaction->id;
			$element['date'] = $transaction->date;
			$element['month'] = substr($transaction->date,0,7);
			$element['number'] = $transaction->code;
			$element['description'] = $transaction->description;
			$element['transfer_account'] = array();
			$element['transaction_splits'] = array();
			
			foreach( $transaction->account_transactions as $account_transaction )
			{
				$amount_credit = (
									(
										$account_transaction->account->type->table_sign > 0 AND 
										$account_transaction->amount > 0
									) OR
									(
										$account_transaction->account->type->table_sign < 0 AND 
										$account_transaction->amount < 0
									)
								) 
							   ? $this->_company_currency().number_format(abs($account_transaction->amount),2,'.',',')
							   : FALSE;

				$amount_debit = (
									(
										$account_transaction->account->type->table_sign < 0 AND 
										$account_transaction->amount > 0
									) OR
									(
										$account_transaction->account->type->table_sign > 0 AND 
										$account_transaction->amount < 0
									)
								) 
							   ? $this->_company_currency().number_format(abs($account_transaction->amount),2,'.',',')
							   : FALSE;

				if( $account_transaction->account->id == $this->account_lookup_result->data->account->id )
				{
					$element['amount_credit'] = $amount_credit;
					$element['amount_debit'] = $amount_debit;
					$element['balance'] = number_format(($account_transaction->balance * $account_transaction->account->type->table_sign),2,'.',',');
				}
				else
				{
					$element['transaction_splits'][] = array(
						'id' => $account_transaction->account->id,
						'name' => $account_transaction->account->name,
						'code' => $account_transaction->account->code,
						'amount_credit' => $amount_credit,
						'amount_debit' => $amount_debit,
					);
				}
			}

			if( count($element['transaction_splits']) == 1 )
			{
				$element['transfer_account']['name'] = $element['transaction_splits'][0]['name'];
				$element['transfer_account']['id'] = $element['transaction_splits'][0]['id'];
				unset($element['transfer_account']['transaction_splits']);
			}
			else
			{
				$element['transfer_account'] = FALSE;
			}
			
			$transactions[] = $element;
		}
		
		return $transactions;
	}

}