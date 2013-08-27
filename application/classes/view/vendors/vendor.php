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

class View_Vendors_Vendor extends View_Template {
	// Receives $this->vendor_lookup_result
	// Receives $this->vendor_addresses_search_result
	// Receives $this->vendor_payment_search_result

	protected $_vendor = FALSE;
	public function vendor()
	{
		if( ! isset($this->vendor_lookup_result) )
			return FALSE;

		if( $this->_vendor )
			return $this->_vendor;
		
		$this->_vendor = $this->_vendor_array($this->vendor_lookup_result->data->vendor);

		return $this->_vendor;
	}

	protected $_vendor_addresses = FALSE;
	public function vendor_addresses()
	{
		if( ! isset($this->vendor_address_search_result) )
			return FALSE;

		if( $this->_vendor_addresses )
			return $this->_vendor_addresses;

		$this->_vendor_addresses = $this->_addresses_array($this->vendor_address_search_result->data->addresses);

		if( ! isset($this->vendor_lookup_result) )
			return $this->_vendor_addresses;

		foreach( $this->_vendor_addresses as $index => $address ) 
			$this->_vendor_addresses[$index]['default_remit'] = ( $address['id'] == $this->vendor_lookup_result->data->vendor->default_remit_address_id )
																  ? TRUE
																  : FALSE;
		
		return $this->_vendor_addresses;
	}

	public function all_accounts_chart_flat()
	{
		$return_array = parent::all_accounts_chart_flat();

		if( ! isset($this->vendor_lookup_result) )
			return $return_array;

		foreach( $return_array as $index => $account )
			if( isset($this->vendor_lookup_result->data->vendor->default_account->id) AND
				$account['id'] == $this->vendor_lookup_result->data->vendor->default_account->id )
				$return_array[$index]['vendor_current'] = TRUE;
			else
				$return_array[$index]['vendor_current'] = FALSE;

		return $return_array;
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
			'typeformatted' => "Payment",
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
			'typeformatted' => "Tax Payment",
			'amount' => $taxpayment->amount,
			'amount_formatted' => ( $taxpayment->amount < 0 ? '-' : '' ).number_format(abs($taxpayment->amount), 2, '.', ','),
		);
	}


	// Copied from View_Partials_Vendors_Checks_Check
	protected function _parse_data_expense($expense)
	{
		$return_array = array();

		$return_array['type'] = "expense";
		$return_array['typeformatted'] = "Expense";
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
		$return_array['typeformatted'] = "Payment";
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
		$return_array['typeformatted'] = "Tax Payment";
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