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


class View_Customers_Customers extends View_Template {
	
	protected $_customers = FALSE;
	public function customers()
	{
		if( ! isset($this->customer_search_result) )
			return FALSE;

		if( $this->_customers )
			return $this->_customers;

		$this->_customers = array();
		foreach( $this->customer_search_result->data->customers as $customer )
			$this->_customers[] = $this->_customer_array($customer);

		return $this->_customers;
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

}