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


class View_Partials_Vendors_Checks_Newcheck extends KOstache {

	public function render()
	{
		$this->_parse_data();

		return parent::render();
	}

	protected function _parse_data()
	{
		$arr = FALSE;
		if( isset($this->expense) )
			$arr = $this->_parse_data_expense($this->expense);
		else if( isset($this->payment) )
			$arr = $this->_parse_data_payment($this->payment);
		else if( isset($this->taxpayment) )
			$arr = $this->_parse_data_taxpayment($this->taxpayment);
		else if( isset($this->transaction) )
			$arr = $this->_parse_data_transaction($this->transaction);

		if( ! $arr )
			return FALSE;

		foreach( $arr as $key => $value )
			$this->{$key} = $value;
	}
	
	protected function _parse_data_expense($expense)
	{
		$return_array = array();

		$return_array['type'] = "expense";
		$return_array['id'] = $expense->id;

		$return_array['date'] = $expense->date_created;
		$return_array['vendor'] = $expense->vendor->display_name;
		$return_array['amount'] = $expense->total;
		$return_array['amount_formatted'] = ( $expense->total < 0 ? '-' : '' ).number_format( abs($expense->total), 2, '.', ',');
		$return_array['check_number'] = $expense->check_number;
		$return_array['reconciled'] = $expense->transaction->reconciled 
			? TRUE 
			: FALSE;
		$return_array['can_print'] = TRUE;

		return $return_array;
	}

	protected function _parse_data_payment($payment)
	{
		$return_array['type'] = "payment";
		$return_array['id'] = $payment->id;

		$return_array['date'] = $payment->date;
		$return_array['vendor'] = $payment->vendor->display_name;
		$return_array['amount'] = $payment->amount;
		$return_array['amount_formatted'] = ( $payment->amount < 0 ? '-' : '' ).number_format(abs($payment->amount), 2, '.', ',');
		$return_array['check_number'] = $payment->check_number;
		$return_array['reconciled'] = $payment->reconciled
			? TRUE
			: FALSE;
		$return_array['can_print'] = TRUE;

		return $return_array;
	}

	protected function _parse_data_taxpayment($taxpayment)
	{
		$return_array['type'] = "taxpayment";
		$return_array['id'] = $taxpayment->id;

		$return_array['date'] = $taxpayment->date;
		$return_array['vendor'] = $taxpayment->tax->authority;
		$return_array['amount'] = $taxpayment->amount;
		$return_array['amount_formatted'] = ( $taxpayment->amount < 0 ? '-' : '' ).number_format(abs($taxpayment->amount), 2, '.', ',');
		$return_array['check_number'] = $taxpayment->check_number;
		$return_array['reconciled'] = $taxpayment->transaction->reconciled
			? TRUE
			: FALSE;
		$return_array['can_print'] = TRUE;

		return $return_array;
	}
	
	protected function _parse_data_transaction($transaction)
	{
		$return_array['date'] = $transaction->date;
		$return_array['vendor'] = "Unknown";
		$return_array['amount'] = $transaction->amount;
		$return_array['amount_formatted'] = ( $transaction->amount < 0 ? '-' : '' ).number_format(abs($transaction->amount), 2, '.', ',');
		$return_array['check_number'] = $transaction->check_number;
		$return_array['reconciled'] = $transaction->reconciled
			? TRUE
			: FALSE;
		$return_array['can_print'] = FALSE;

		return $return_array;
	}
}