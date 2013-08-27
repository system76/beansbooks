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


class View_Accounts_Import extends View_Template {
	
	// Received $this->account_lookup_result
	
	// If stage 2:
	// Received $this->transactionsfilestring
	
	// If stage 3:
	// Received $this->account_table_sign // Cuts down on lookups - should be able to trim up later.
	// Received $this->account_transactions
	// 			This is an array of:
	// 			account_id
	// 			amount
	// 			hash - To keep everything straight
	// 			date
	// 			description
	// 			number
	// 			duplicate - TRUE / FALSE
	// 			transaction - Matched transaction

	public function account()
	{
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
		return $account;
	}

	public function transactionsfile() {
		return ( isset($this->transactionsfilestring) )
			? $this->transactionsfilestring
			: FALSE;
	}

	// Stage 2
	public function samplecolumns()
	{
		$samplecolumns = array();
		
		$line = substr($this->transactionsfilestring,0,strpos($this->transactionsfilestring,"\n"));
		
		$index = 0;
		foreach( explode(',',$line) as $term )
			$samplecolumns[] = array(
				'index' => $index++,
				'value' => $term,
				'coldate' => FALSE,
				'colnum' => FALSE,
				'coldesc' => FALSE,
				'colamount' => FALSE,
			);

		foreach( $samplecolumns as $i => $column ) 
		{
			if( $column['value'] == date("Y-m-d", strtotime($column['value'])) OR 
				$column['value'] == date("m/d/Y", strtotime($column['value'])) )
				$samplecolumns[$i]['coldate'] = TRUE;
			else if ( ''.( intval( (float)$column['value'] * 100 ) / 100 ) == ''.$column['value'] )
				$samplecolumns[$i]['colamount'] = TRUE;
			else if ( strlen($column['value']) > 12 )
				$samplecolumns[$i]['coldesc'] = TRUE;
			else
				$samplecolumns[$i]['colnum'] = TRUE;
		}

		return $samplecolumns;
	}

	// Stage 3
	public function transactions()
	{
		if( ! isset($this->account_transactions) OR
			! count($this->account_transactions) ) 
			return FALSE;
		return TRUE;
	}

	public function account_transactions()
	{
		if( ! isset($this->account_transactions) OR
			! count($this->account_transactions) ) 
			return FALSE;

		$formatted_account_transactions = array();
		
		foreach( $this->account_transactions as $transaction )
		{
			$element = array();
			$element['hash'] = $transaction->hash;
			$element['number'] = $transaction->number;
			$element['date'] = $transaction->date;
			$element['description'] = $transaction->description;
			$element['amount'] = ( $transaction->amount * $this->account_table_sign );
			$element['amount_credit'] = ( $element['amount'] > 0 )
									  ? $element['amount']
									  : FALSE;
			$element['amount_debit'] = ( $element['amount'] < 0 )
									  ? abs($element['amount'])
									  : FALSE;

			$element['amount_credit_formatted'] = ( $element['amount_credit'] )
												? $this->_company_currency().number_format($element['amount_credit'],2,'.',',')
												: FALSE;

			$element['amount_debit_formatted'] = ( $element['amount_debit'] )
												? $this->_company_currency().number_format($element['amount_debit'],2,'.',',')
												: FALSE;

			$element['duplicate'] = ( $transaction->duplicate )
								  ? TRUE
								  : FALSE;

			$element['transfer_account'] = FALSE;

			// Simplified for now - splits are done manually.
			if( isset($transaction->transaction) AND 
				isset($transaction->transaction->account_transactions) AND 
				count($transaction->transaction->account_transactions) )
				if( count($transaction->transaction->account_transactions) == 2 )
					foreach( $transaction->transaction->account_transactions as $account_transaction )
						if( $account_transaction->account->id != $this->account_lookup_result->data->account->id )
							$element['transfer_account'] = $account_transaction->account->id;
			
			$formatted_account_transactions[] = $element;
		}

		return $formatted_account_transactions;
	}
}