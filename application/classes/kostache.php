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

/**
 * Ensure that we can get beans_settings when loading partials via AJAX.
 */

class Kostache extends Kohana_Kostache { 

	// V2Item - Override this to use cache or something similar.
	// Cascade _company_currency() throughout view classes.
	protected function _company_currency()
	{
		$beans_settings = $this->beans_settings();

		return $beans_settings->company_currency;
	}

	protected $_beans_settings = FALSE;
	protected function beans_settings()
	{
		if( $this->_beans_settings )
			return $this->_beans_settings;

		$company_settings = new Beans_Setup_Company_List($this->_beans_data_auth());
		$company_settings_result = $company_settings->execute();

		if( ! $company_settings_result->success )
		{
			$this->_beans_settings = new stdClass;
			$this->_beans_settings->company_currency = "$";
			return $this->_beans_settings;
		}

		$this->_beans_settings = $company_settings_result->data->settings;

		// Default
		if( ! isset($this->_beans_settings->company_currency) OR 
			! strlen($this->_beans_settings->company_currency) )
			$this->_beans_settings->company_currency = "$";
		
		return $this->_beans_settings;
	}

	protected function _beans_data_auth($data = NULL)
	{
		if( $data === NULL )
			$data = new stdClass;

		if( is_array($data) )
			$data = (object)$data;

		if( ! is_object($data) OR
			get_class($data) != "stdClass" )
			$data = new stdClass;

		// Set our auth keys.
		$data->auth_uid = Session::instance()->get('auth_uid');
		$data->auth_expiration = Session::instance()->get('auth_expiration');
		$data->auth_key = Session::instance()->get('auth_key');

		return $data;
	}

}
