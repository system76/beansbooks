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

class View_Setup_Taxes extends View_Template {
	

	function taxes() 
	{
		if( ! isset($this->tax_search_result) )
			return FALSE;

		$return_array = array();

		foreach( $this->tax_search_result->data->taxes as $tax )
			$return_array[] = $this->_tax_array($tax);

		return $return_array;
	}

	private function _tax_array($tax) 
	{
		return array(
			'id' => $tax->id,
			'name' => $tax->name,
			'percent' => ( $tax->percent * 100 ).'%',
			'balance' => ( $tax->balance < 0 ? '-' : '' ).$this->_company_currency().number_format(abs(($tax->balance)),2,'.',','),
			'nextduedate' => ( $tax->date_due ? $tax->date_due : "Not Set." ),
			'visible' => ( $tax->visible ? TRUE : FALSE ),
		);
	}

	public function taxes_all_accounts_chart_flat()
	{
		$accounts = $this->all_accounts_chart_flat();

		foreach( $accounts as $index => $account )
			$accounts[$index]['default'] = ( $account['payable'] AND stripos($account['name'], 'tax') !== FALSE ) ? TRUE : FALSE;

		return $accounts;
	}

	public function default_tax_account()
	{
		$accounts = $this->taxes_all_accounts_chart_flat();

		foreach( $accounts as $account )
			if( $account['default'] )
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
		foreach( $this->countries() as $country ) 
			if( $country['default'] ) 
				return $country;

		return FALSE;
	}
}