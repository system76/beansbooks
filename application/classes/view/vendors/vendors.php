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

class View_Vendors_Vendors extends View_Template {
	
	protected $_vendors = FALSE;
	public function vendors()
	{
		if( ! isset($this->vendor_search_result) )
			return FALSE;

		if( $this->_vendors )
			return $this->_vendors;

		$this->_vendors = array();
		foreach( $this->vendor_search_result->data->vendors as $vendor )
			$this->_vendors[] = $this->_vendor_array($vendor);

		return $this->_vendors;
	}

	private $_vendor_payable_accounts;
	function vendor_payable_accounts()
	{
		if( isset($this->_vendor_payable_accounts) )
			return $this->_vendor_payable_accounts;

		$settings = parent::beans_settings();

		$this->_vendor_payable_accounts = parent::all_accounts_chart_flat();

		if( isset($settings->account_default_payable) AND 
			$settings->account_default_payable )
		{
			foreach( $this->_vendor_payable_accounts as $index => $account )
			{
				if( $account['id'] == $settings->account_default_payable )
					$this->_vendor_payable_accounts[$index]['selected'] = TRUE;
				else
					$this->_vendor_payable_accounts[$index]['selected'] = FALSE;
			}
		}
		else
		{
			foreach( $this->_vendor_payable_accounts as $index => $account )
				$this->_vendor_payable_accounts[$index]['selected'] = FALSE;
		}

		return $this->_vendor_payable_accounts;
	}

	function vendor_payable_account_default()
	{
		foreach( $this->vendor_payable_accounts() as $account )
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