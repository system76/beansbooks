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

class View_Vendors_Payments extends View_Template {
	
	private $_payment_payment_accounts;
	function payment_payment_accounts()
	{
		if( isset($this->_payment_payment_accounts) )
			return $this->_payment_payment_accounts;

		$settings = parent::beans_settings();

		$this->_payment_payment_accounts = parent::all_accounts_chart_flat();

		if( isset($settings->account_default_order) AND 
			$settings->account_default_order )
		{
			foreach( $this->_payment_payment_accounts as $index => $account )
			{
				if( $account['id'] == $settings->account_default_order )
					$this->_payment_payment_accounts[$index]['selected'] = TRUE;
				else
					$this->_payment_payment_accounts[$index]['selected'] = FALSE;
			}
		}
		else
		{
			foreach( $this->_payment_payment_accounts as $index => $account )
				$this->_payment_payment_accounts[$index]['selected'] = FALSE;
		}

		return $this->_payment_payment_accounts;
	}

	function payment_payment_account_default()
	{
		foreach( $this->payment_payment_accounts() as $account )
			if( $account['selected'] )
				return $account;

		return FALSE;
	}

	function vendor_payments_more()
	{
		return ( $this->vendor_payment_search_result->data->pages > 1 )
			? TRUE
			: FALSE;
	}

}