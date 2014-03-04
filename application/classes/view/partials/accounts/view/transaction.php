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


class View_Partials_Accounts_View_Transaction extends Kostache {

	protected $_partials = array(
		'accounts_view_transactionsplit' => 'partials/accounts/view/transactionsplit',
	);

	// Received $this->transaction
	// Received $this->account_id
	
	public $id;
	public $cancelled;
	public $date;
	public $month;
	public $number;
	public $description;
	public $transfer_account;
	public $reconciled;
	public $amount_credit;
	public $amount_debit;
	public $balance;
	public $transaction_splits;
	public $edit;

	public function render()
	{
		$this->_parse_data();

		return parent::render();
	}

	private function _parse_data()
	{
		if( ! isset($this->account_lookup_result) )
			return FALSE;

		$current_table_sign = $this->account_lookup_result->data->account->type->table_sign;

		$this->id = $this->transaction->id;

		$this->date = $this->transaction->date;

		$this->month = substr($this->date,0,7);

		$this->number = $this->transaction->code;

		$this->check_number = $this->transaction->check_number;

		$this->description = $this->transaction->description;

		$this->edit = FALSE;
		if( $this->transaction->payment )
		{
			$this->edit = array();

			if( $this->transaction->payment == "customer" )
			{
				$this->edit['url'] = "/customers/payments/".$this->transaction->id;
				$element['type'] = "customer payment";
			}
			else if( $this->transaction->payment == "expense" )
			{
				$this->edit['url'] = "/vendors/expenses/".$this->transaction->form->id;
				$element['type'] = "vendor expense";
			}
			else if( $this->transaction->payment = "vendor" )
			{
				$this->edit['url'] = "/vendors/payments/".$this->transaction->id;
				$element['type'] = "vendor payment";
			}

		}
		else if( $this->transaction->form )
		{
			$this->edit = array();

			if( $this->transaction->form->type == "sale" )
			{
				$this->edit['url'] = "/customers/sales/".$this->transaction->form->id;
				$element['type'] = "customer sale";
			}
			else if( $this->transaction->form->type = "purchase" )
			{
				$this->edit['url'] = "/vendors/purchases/".$this->transaction->form->id;
				$element['type'] = "vendor purchase order";
			}
			else if( $this->transaction->form->type = "expense" )
			{
				$this->edit['url'] = "/vendors/expenses/".$this->transaction->form->id;
				$element['type'] = "vendor expense";
			}
			// Probably won't hit last one.
		}
		else if( $this->transaction->tax_payment )
		{
			$this->edit['url'] = "/vendors/taxpayments/".$this->transaction->tax_payment->id;
			$element['type'] = "tax payment";
		}

		// One of these will be replaced with FALSE.
		// Same logic as View_Accounts_View.php -> transactions()
		$this->transfer_account = array();
		$this->transaction_splits = array();
		
		foreach( $this->transaction->account_transactions as $account_transaction )
		{
			$amount_credit = (
								(
									$current_table_sign == $account_transaction->account->type->table_sign AND 
									$account_transaction->amount * $account_transaction->account->type->table_sign > 0
								) OR
								(
									$current_table_sign != $account_transaction->account->type->table_sign AND 
									$account_transaction->amount * $account_transaction->account->type->table_sign < 0
								)
							) 
						   ? $this->_company_currency().number_format(abs($account_transaction->amount),2,'.',',')
						   : FALSE;

			$amount_debit = $amount_credit
						   ? FALSE
						   : $this->_company_currency().number_format(abs($account_transaction->amount),2,'.',',');

			if( $account_transaction->account->id == $this->account_id )
			{
				$this->amount_credit = $amount_credit;
				$this->amount_debit = $amount_debit;
				$this->balance = number_format(($account_transaction->balance * $account_transaction->account->type->table_sign),2,'.',',');
				$this->reconciled = $account_transaction->reconciled;
			}
			else
			{
				$this->transaction_splits[] = array(
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

		$this->transfer_account = $this->transaction_splits[0];

		if( count($this->transaction_splits) == 1 )
		{
			$this->transfer_account['name'] = $this->transaction_splits[0]['name'];
			$this->transfer_account['id'] = $this->transaction_splits[0]['id'];
			$this->transaction_splits = FALSE;
		}
		else
		{
			$this->transfer_account = FALSE;
		}
	}

}