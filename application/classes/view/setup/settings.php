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

class View_Setup_Settings extends View_Template {
	// Receives $this->beans_company_update_result

	private $_settings = FALSE;
	public function settings()
	{
		if( $this->_settings )
			return $this->_settings;

		$this->_settings = array();
		foreach( $this->beans_company_update_result->data->settings as $key => $value )
			$this->_settings[$key] = $value;

		return $this->_settings;
	}

	// Called only once - no cache.
	public function countries()
	{
		$countries = parent::countries();

		foreach( $countries as $index => $country )
		{
			if( isset($this->beans_company_update_result->data->settings->company_address_country) AND 
				$country['code'] == $this->beans_company_update_result->data->settings->company_address_country )
				$countries[$index]['selected'] = TRUE;
			else
				$countries[$index]['selected'] = FALSE;
		}

		return $countries;
	}

	public function company_logo_src()
	{
		if( ! isset($this->beans_company_update_result->data->settings->company_logo_data) OR 
			! isset($this->beans_company_update_result->data->settings->company_logo_type) OR 
			! $this->beans_company_update_result->data->settings->company_logo_data OR 
			! $this->beans_company_update_result->data->settings->company_logo_type )
			return FALSE;

		return array(
			'type' => $this->beans_company_update_result->data->settings->company_logo_type,
			'data' => $this->beans_company_update_result->data->settings->company_logo_data,
		);

		// return "data:".$this->beans_company_update_result->data->settings->company_logo_type.";base64,".$this->beans_company_update_result->data->settings->company_logo_data;
	}

	private function chart_flat_account_selected($key)
	{
		$accounts = parent::all_accounts_chart_flat();

		foreach( $accounts as $index => $account )
			if( isset($this->beans_company_update_result->data->settings->{$key}) AND 
				$this->beans_company_update_result->data->settings->{$key} == $account['id'] )
				$accounts[$index]['selected'] = TRUE;
			else
				$accounts[$index]['selected'] = FALSE;

		return $accounts;
	}

	public function account_default_deposit_options()
	{
		return $this->chart_flat_account_selected('account_default_deposit');
	}

	public function account_default_receivable_options()
	{
		return $this->chart_flat_account_selected('account_default_receivable');
	}

	public function account_default_income_options()
	{
		return $this->chart_flat_account_selected('account_default_income');
	}

	public function account_default_returns_options()
	{
		return $this->chart_flat_account_selected('account_default_returns');
	}

	public function account_default_expense_options()
	{
		return $this->chart_flat_account_selected('account_default_expense');
	}

	public function account_default_order_options()
	{
		return $this->chart_flat_account_selected('account_default_order');
	}

	public function account_default_costofgoods_options()
	{
		return $this->chart_flat_account_selected('account_default_costofgoods');
	}

	public function account_default_payable_options()
	{
		return $this->chart_flat_account_selected('account_default_payable');
	}

	public function writeoff_accounts_options_dropdowns()
	{
		$accounts = parent::all_accounts_chart_flat();

		$dropdowns = array();

		foreach( $accounts as $index => $account )
		{
			if( $account['writeoff'] )
			{
				$new_dropdown_options = parent::all_accounts_chart_flat();

				foreach( $new_dropdown_options as $index => $option_account )
					if( $account['id'] == $option_account['id'] )
						$new_dropdown_options[$index]['selected'] = TRUE;
					else
						$new_dropdown_options[$index]['selected'] = FALSE;

				$dropdowns[] = array(
					'options' => $new_dropdown_options
				);
			}
		}

		return $dropdowns;
	}

	public function currencies()
	{
		$settings = $this->settings();

		$currencies = array(
			array(
				'name' => "Dollar",
				'symbol' => '$',
			),
			array(
				'name' => "Pound",
				'symbol' => '£',
			),
			array(
				'name' => "Euro",
				'symbol' => '€',
			),
			array(
				'name' => "Yen",
				'symbol' => '¥',
			),
			array(
				'name' => "Renminbi",
				'symbol' => '¥',
			),
			array(
				'name' => "Romanian Leu",
				'symbol' => 'RON',
			),
			array(
				'name' => "Hungarian Forint",
				'symbol' => 'Ft',
			),
			array(
				'name' => "Indian rupee",
				'symbol' => 'INR',
			),
			array(
				'name' => "Egyptian pound",
				'symbol' => '£',
			),
			array(
				'name' => "Russian ruble",
				'symbol' => 'R',
			),
			array(
				'name' => "Swiss Franc",
				'symbol' => 'Fr',
			),
		);

		foreach( $currencies as $index => $currency )
			$currencies[$index]['default'] = ( isset($settings['company_currency']) AND $currency['symbol'] == $settings['company_currency'] ) ? TRUE : FALSE;

		return $currencies;
	}

}
