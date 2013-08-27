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


class View_Accounts_View extends View_Template {
	
	// Received $this->account_lookup_result
	// Received $this->account_transactions_result
	
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
			$element['check_number'] = $transaction->check_number;
			$element['description'] = $transaction->description;

			$element['edit'] = FALSE;
			if( $transaction->payment )
			{
				$element['edit'] = array();

				if( $transaction->payment == "customer" )
				{
					$element['edit']['url'] = "/customers/payments/".$transaction->id;
					$element['type'] = "customer payment";
				}
				else if( $transaction->payment == "expense" )
				{
					$element['edit']['url'] = "/vendors/expenses/".$transaction->form->id;
					$element['type'] = "vendor expense";
				}
				else if( $transaction->payment = "vendor" )
				{
					$element['edit']['url'] = "/vendors/payments/".$transaction->id;
					$element['type'] = "vendor payment";
				}

			}
			else if( $transaction->form )
			{
				$element['edit'] = array();

				if( $transaction->form->type == "sale" )
				{
					$element['edit']['url'] = "/customers/sales/".$transaction->form->id;
					$element['type'] = "customer sale";
				}
				else if( $transaction->form->type = "purchase" )
				{
					$element['edit']['url'] = "/vendors/purchases/".$transaction->form->id;
					$element['type'] = "vendor purchase order";
				}
				else if( $transaction->form->type = "expense" )
				{
					$element['edit']['url'] = "/vendors/expenses/".$transaction->form->id;
					$element['type'] = "vendor expense";
				}
				// Probably won't hit last one.
			}
			else if( $transaction->tax_payment )
			{
				$element['edit']['url'] = "/vendors/taxpayments/".$transaction->tax_payment->id;
				$element['type'] = "tax payment";
			}

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
					$element['reconciled'] = $account_transaction->reconciled;
				}
				else
				{
					$element['transaction_splits'][] = array(
						'id' => $account_transaction->account->id,
						'name' => $account_transaction->account->name,
						'code' => $account_transaction->account->code,
						'amount_credit' => $amount_credit,
						'amount_debit' => $amount_debit,
						'amount' => $account_transaction->amount,
						'table_sign' => $account_transaction->account->type->table_sign,
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

			/*
			if( strpos($element['description'],ucwords($transaction->payment)." Payment Recorded") !== FALSE AND 
				$transaction->payment )
				$element['description'] = str_replace(ucwords($transaction->payment)." Payment Recorded", '<a href="'.$element['edit']['url'].'">'.ucwords($transaction->payment).' Payment Recorded</a>', strip_tags($element['description']));
			*/
			
			$transactions[] = $element;
		}
		
		return $transactions;
	}

}