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

class View_Vendors_Printchecks extends View_Template {
	
	public function next_check_number()
	{
		if( ! isset($this->account_transaction_search_result) OR 
			! count($this->account_transaction_search_result->data->transactions) OR 
			! isset($this->account_transaction_search_result->data->transactions[0]) )
			return FALSE;

		return ( 1 + $this->account_transaction_search_result->data->transactions[0]->check_number );
	}

	public function vendor_checks()
	{
		if( ! isset($this->account_transaction_search_result) OR 
			! count($this->account_transaction_search_result->data->transactions) )
			return FALSE;

		$return_array = array();

		// Not really a result from that beans request, but has additional information.
		foreach( $this->account_transaction_search_result->data->transactions as $transaction )
		{
			if( isset($transaction->expense) AND 
				$transaction->expense )
				$return_array[] = $this->_parse_data_expense($transaction->expense);
			else if ( isset($transaction->payment) AND 
					  $transaction->payment )
				$return_array[] = $this->_parse_data_payment($transaction->payment);
			else if ( isset($transaction->taxpayment) )
				$return_array[] = $this->_parse_data_taxpayment($transaction->taxpayment);
			else
				$return_array[] = $this->_parse_data_transaction($transaction);
		}

		return $return_array;
	}

	public function vendor_checks_more()
	{
		if( ! isset($this->account_transaction_search_result) OR 
			! count($this->account_transaction_search_result->data->transactions) )
			return FALSE;

		return ( $this->account_transaction_search_result->data->pages > 1 )
			? TRUE
			: FALSE;
	}


	public function vendor_newchecks()
	{
		// Loop expenses, payments, taxpayments
		$return_array = array();

		if( $this->expenses )
			foreach( $this->expenses as $expense )
				$return_array[] = $this->_expense_check_array($expense);
		
		if( $this->payments )
			foreach( $this->payments as $payment )
				$return_array[] = $this->_payment_check_array($payment);
		
		if( $this->taxpayments )
			foreach( $this->taxpayments as $taxpayment )
				$return_array[] = $this->_taxpayment_check_array($taxpayment);
		
		if( count($return_array) )
			return $return_array;

		return FALSE;
	}

	protected function _expense_check_array($expense)
	{
		return array(
			'id' => $expense->id,
			'vendor' => ( $expense->vendor->company_name )
				? $expense->vendor->company_name
				: $expense->vendor->first_name.' '.$expense->vendor->last_name,
			'date' => $expense->date_created,
			'type' => "expense",
			'amount' => $expense->total,
			'amount_formatted' => ( $expense->total < 0 ? '-' : '' ).number_format(abs($expense->total), 2, '.', ','),
		);
	}

	protected function _payment_check_array($payment)
	{
		return array(
			'id' => $payment->id,
			'vendor' => ( $payment->vendor->company_name )
				? $payment->vendor->company_name
				: $payment->vendor->first_name.' '.$payment->vendor->last_name,
			'date' => $payment->date,
			'type' => "payment",
			'amount' => $payment->amount,
			'amount_formatted' => ( $payment->amount < 0 ? '-' : '' ).number_format(abs($payment->amount), 2, '.', ','),
		);
	}

	protected function _taxpayment_check_array($taxpayment)
	{
		return array(
			'id' => $taxpayment->id,
			'vendor' => $taxpayment->tax->authority,
			'date' => $taxpayment->date,
			'type' => "taxpayment",
			'amount' => $taxpayment->amount,
			'amount_formatted' => ( $taxpayment->amount < 0 ? '-' : '' ).number_format(abs($taxpayment->amount), 2, '.', ','),
		);
	}


	// Copied from View_Partials_Vendors_Checks_Check
	protected function _parse_data_expense($expense)
	{
		$return_array = array();

		$return_array['type'] = "expense";
		$return_array['id'] = $expense->id;

		$return_array['date'] = $expense->date_created;
		$return_array['vendor'] = ( $expense->vendor->company_name )
			? $expense->vendor->company_name
			: $expense->vendor->first_name.' '.$expense->vendor->last_name;
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
		$return_array = array();

		$return_array['type'] = "payment";
		$return_array['id'] = $payment->id;

		$return_array['date'] = $payment->date;
		$return_array['vendor'] = ( $payment->vendor->company_name )
			? $payment->vendor->company_name
			: $payment->vendor->first_name.' '.$payment->vendor->last_name;
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
		$return_array = array();

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
		$return_array = array();

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