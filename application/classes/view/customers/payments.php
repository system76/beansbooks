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


class View_Customers_Payments extends View_Template {
	// Receives $this->customer_payment_search_result
	// Receives $this->customer_invoice_search_result // Overdue invoices.
	
	private $_payment_deposit_accounts;
	function payment_deposit_accounts()
	{
		if( isset($this->_payment_deposit_accounts) )
			return $this->_payment_deposit_accounts;

		$settings = parent::beans_settings();

		$this->_payment_deposit_accounts = parent::all_accounts_chart_flat();

		if( isset($settings->account_default_deposit) AND 
			$settings->account_default_deposit )
		{
			foreach( $this->_payment_deposit_accounts as $index => $account )
			{
				if( $account['id'] == $settings->account_default_deposit )
					$this->_payment_deposit_accounts[$index]['selected'] = TRUE;
				else
					$this->_payment_deposit_accounts[$index]['selected'] = FALSE;
			}
		}
		else
		{
			foreach( $this->_payment_deposit_accounts as $index => $account )
				$this->_payment_deposit_accounts[$index]['selected'] = FALSE;
		}

		return $this->_payment_deposit_accounts;
	}

	function payment_deposit_account_default()
	{
		foreach( $this->payment_deposit_accounts() as $account )
			if( $account['selected'] )
				return $account;

		return FALSE;
	}

	function customer_payments_more()
	{
		return ( $this->customer_payment_search_result->data->pages > 1 )
			? TRUE
			: FALSE;
	}

}