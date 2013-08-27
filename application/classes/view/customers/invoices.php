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


class View_Customers_Invoices extends View_Template {
	// Receives $this->requested_sale_id
	
	private $_sale_receivable_accounts;
	function sale_receivable_accounts()
	{
		if( isset($this->_sale_receivable_accounts) )
			return $this->_sale_receivable_accounts;

		$settings = parent::beans_settings();

		$this->_sale_receivable_accounts = parent::all_accounts_chart_flat();

		if( isset($settings->account_default_receivable) AND 
			$settings->account_default_receivable )
		{
			foreach( $this->_sale_receivable_accounts as $index => $account )
			{
				if( $account['id'] == $settings->account_default_receivable )
					$this->_sale_receivable_accounts[$index]['selected'] = TRUE;
				else
					$this->_sale_receivable_accounts[$index]['selected'] = FALSE;
			}
		}
		else
		{
			foreach( $this->_sale_receivable_accounts as $index => $account )
				$this->_sale_receivable_accounts[$index]['selected'] = FALSE;
		}

		return $this->_sale_receivable_accounts;
	}

	function sale_receivable_account_default()
	{
		foreach( $this->sale_receivable_accounts() as $account )
			if( $account['selected'] )
				return $account;

		return FALSE;
	}

	private $_sale_income_accounts = FALSE;
	function sale_income_accounts()
	{
		if( $this->_sale_income_accounts )
			return $this->_sale_income_accounts;

		$settings = parent::beans_settings();

		$this->_sale_income_accounts = parent::all_accounts_chart_flat();

		if( isset($settings->account_default_income) AND 
			$settings->account_default_income )
		{
			foreach( $this->_sale_income_accounts as $index => $account )
			{
				if( $account['id'] == $settings->account_default_income )
					$this->_sale_income_accounts[$index]['selected'] = TRUE;
				else
					$this->_sale_income_accounts[$index]['selected'] = FALSE;
			}
		}
		else
		{
			foreach( $this->_sale_income_accounts as $index => $account )
				$this->_sale_income_accounts[$index]['selected'] = FALSE;
		}

		return $this->_sale_income_accounts;

	}

	function default_refund_account_id()
	{
		$settings = parent::beans_settings();

		if( isset($settings->account_default_returns) AND 
			strlen($settings->account_default_returns) ) {
			return $settings->account_default_returns;
		}

		return FALSE;
	}

	private $_customer_receivable_accounts;
	function customer_receivable_accounts()
	{
		if( isset($this->_customer_receivable_accounts) )
			return $this->_customer_receivable_accounts;

		$settings = parent::beans_settings();

		$this->_customer_receivable_accounts = parent::all_accounts_chart_flat();

		if( isset($settings->account_default_receivable) AND 
			$settings->account_default_receivable )
		{
			foreach( $this->_customer_receivable_accounts as $index => $account )
			{
				if( $account['id'] == $settings->account_default_receivable )
					$this->_customer_receivable_accounts[$index]['selected'] = TRUE;
				else
					$this->_customer_receivable_accounts[$index]['selected'] = FALSE;
			}
		}
		else
		{
			foreach( $this->_customer_receivable_accounts as $index => $account )
				$this->_customer_receivable_accounts[$index]['selected'] = FALSE;
		}

		return $this->_customer_receivable_accounts;
	}

	function customer_receivable_account_default()
	{
		foreach( $this->customer_receivable_accounts() as $account )
			if( $account['selected'] )
				return $account;

		return FALSE;
	}

	public function countries()
	{
		$default_country = "US";

		$settings = parent::beans_settings();
		
		$return_array = parent::countries();

		if( $settings AND 
			isset($settings->company_address_country) AND 
			strlen($settings->company_address_country) )
			$default_country = $settings->company_address_country;
		
		foreach( $return_array as $index => $country )
			$return_array[$index]['default'] = ( $country['code'] == $default_country ) ? TRUE : FALSE;
		
		return $return_array;
	}

	public function default_country()
	{
		$countries = $this->countries();

		foreach( $countries as $country )
			if( $country['default'] )
				return $country;
		
		return FALSE;
	}
	
}