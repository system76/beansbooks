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


class View_Customers_Customer extends View_Template {
	// Receives $this->customer_lookup_result
	// Receives $this->customer_address_search_result
	// Receives $this->customer_invoice_search_result

	protected $_customer = FALSE;
	public function customer()
	{
		if( ! isset($this->customer_lookup_result) )
			return FALSE;

		if( $this->_customer )
			return $this->_customer;
		
		$this->_customer = $this->_customer_array($this->customer_lookup_result->data->customer);

		return $this->_customer;
	}

	protected $_customer_addresses = FALSE;
	public function customer_addresses()
	{
		if( ! isset($this->customer_address_search_result) )
			return FALSE;

		if( $this->_customer_addresses )
			return $this->_customer_addresses;

		$this->_customer_addresses = $this->_addresses_array($this->customer_address_search_result->data->addresses);

		if( ! isset($this->customer_lookup_result) )
			return $this->_customer_addresses;

		foreach( $this->_customer_addresses as $index => $address ) 
		{
			$this->_customer_addresses[$index]['default_billing'] = ( $address['id'] == $this->customer_lookup_result->data->customer->default_billing_address_id )
																  ? TRUE
																  : FALSE;

			$this->_customer_addresses[$index]['default_shipping'] = ( $address['id'] == $this->customer_lookup_result->data->customer->default_shipping_address_id )
																  ? TRUE
																  : FALSE;
		}
		
		return $this->_customer_addresses;
	}

	public function all_accounts_chart_flat()
	{
		$return_array = parent::all_accounts_chart_flat();
		
		if( ! isset($this->customer_lookup_result) )
			return $return_array;

		foreach( $return_array as $index => $account )
			if( isset($this->customer_lookup_result->data->customer->default_account->id) AND 
				$account['id'] == $this->customer_lookup_result->data->customer->default_account->id )
				$return_array[$index]['customer_current'] = TRUE;
			else
				$return_array[$index]['customer_current'] = FALSE;

		return $return_array;
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