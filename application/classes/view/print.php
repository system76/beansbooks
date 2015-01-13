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

class View_Print extends Kostache_Layout {

	// Receives $this->swift_email_message ( Swift_Message OR FALSE/NOT SET ) to delineate email

	protected $_layout = 'print';

	protected $_partials = array(
		'vendors_print_check_expense' => 'vendors/print/check/expense',
		'vendors_print_check_payment' => 'vendors/print/check/payment',
		'vendors_print_check_taxpayment' => 'vendors/print/check/taxpayment',
	);

	// Cache Settings
	protected $_beans_settings = FALSE;
	protected function beans_settings()
	{
		if( ! isset($this->setup_company_list_result) )
			return FALSE;

		if( $this->_beans_settings )
			return $this->_beans_settings;

		$this->_beans_settings = $this->setup_company_list_result->data->settings;

		// Default
		if( ! isset($this->_beans_settings->company_currency) OR 
			! strlen($this->_beans_settings->company_currency) )
			$this->_beans_settings->company_currency = "$";
		
		return $this->_beans_settings;
	}

	public function render()
	{
		$return_render = parent::render();

		if( isset($this->swift_email_message) AND
			$this->swift_email_message )
		{
			$this->swift_email_message->setBody($return_render,'text/html');
			return $this->swift_email_message;
		}

		return $return_render;
	}

	protected $_swift_logo_embed = FALSE;
	public function companylogo()
	{
		$beans_settings = $this->beans_settings();

		if( ! $beans_settings )
			return FALSE;

		if( ! isset($beans_settings->LOCAL) OR 
			! isset($beans_settings->company_logo_filename) )
			return FALSE;

		if( isset($this->swift_email_message) AND
			$this->swift_email_message )
		{
			if( ! $this->_swift_logo_embed )
				$this->_swift_logo_embed = $this->swift_email_message->embed(new Swift_Image(base64_decode($beans_settings->company_logo_data),$beans_settings->company_logo_filename,$beans_settings->company_logo_type));
			
			return '<img alt="'.$beans_settings->company_name.'" src="'.$this->_swift_logo_embed.'" style="max-height: 50px; max-width: 150px;">';
		}
		else
		{
			return '<img alt="'.$beans_settings->company_name.'" src="data:'.$beans_settings->company_logo_type.';base64,'.$beans_settings->company_logo_data.'" style="max-height: 50px; max-width: 150px;">';
		}
	}

	public function companyname()
	{
		$beans_settings = $this->beans_settings();

		if( ! $beans_settings )
			return FALSE;

		if( ! isset($beans_settings->company_name) OR
			! $beans_settings->company_name )
			return FALSE;

		return $beans_settings->company_name;
	}

	protected $_companyinfo = FALSE;
	public function companyinfo()
	{
		if( $this->_companyinfo )
			return $this->_companyinfo;

		$beans_settings = $this->beans_settings();
		
		// V2Item - Cascade default information if necessary.
		$this->_companyinfo = array(
			'address1' => ( isset($beans_settings->company_address_address1) ? $beans_settings->company_address_address1 : FALSE ),
			'address2' => ( isset($beans_settings->company_address_address2) ? $beans_settings->company_address_address2 : FALSE ),
			'city' => ( isset($beans_settings->company_address_city) ? $beans_settings->company_address_city : FALSE ),
			'state' => ( isset($beans_settings->company_address_state) ? $beans_settings->company_address_state : FALSE ),
			'zip' => ( isset($beans_settings->company_address_zip) ? $beans_settings->company_address_zip : FALSE ),
			'phone' => ( isset($beans_settings->company_phone) ? $beans_settings->company_phone : FALSE ),
			'email' => ( isset($beans_settings->company_email) ? $beans_settings->company_email : FALSE ),
		);

		return $this->_companyinfo;
	}

	// We're storing all of these settings here because we'll need inline styles for the email formatting.
	public function fontsizenormal()
	{
		return 'font-size: 12px; line-height: 15px;';
	}

	public function fontsizelarge()
	{
		return 'font-size: 20px; line-height: 30px;';
	}

	public function tableopen()
	{
		return '<table cellpadding="0" cellspacing="0" border="0" style="width: 735px;margin:0px auto; table-layout:fixed; overflow: hidden;">';
	}

	public function tableclose()
	{
		return '</table>';
	}

	public function borderdarkgreen()
	{
		return '#b6d2b6';
	}

	public function backgroundlightgreen()
	{
		return '#e8f1e8';
	}

	public function domain()
	{
		return URL::base(TRUE,TRUE);
	}
	
	protected function _country_name($code) {
		return Helper_Address::CountryName($code);
	}

	protected function _format_beans_number($number)
	{
		$beans_settings = $this->beans_settings();

		return ( $number >= 0 ? '' : '-' ).$beans_settings->company_currency.number_format(abs($number),2,'.',',');
	}
		
}