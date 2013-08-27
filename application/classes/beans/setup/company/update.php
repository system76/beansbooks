<?php defined('SYSPATH') or die('No direct script access.');
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

/*
---BEANSAPISPEC---
@action Beans_Setup_Company_Update
@description Return all company settings.
@required auth_uid
@required auth_key
@required auth_expiration
@required settings OBJECT A key => value mapping of settings to update.
@returns settings OBJECT An object with a key => value for each company setting that has been saved or created.
---BEANSENDSPEC---
*/
class Beans_Setup_Company_Update extends Beans_Setup_Company {
	
	protected $_auth_role_perm = "setup";

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}

	protected function _execute()
	{
		if( ! isset($this->_data->settings) )
			throw new Exception("No settings provided to update.");

		foreach( $this->_data->settings as $key => $value )
			$this->_beans_setting_set($key,$value);

		$this->_beans_settings_save();

		return (object)array(
			"settings" => $this->_return_company_settings($this->_beans_settings_dump()),
		);
	}
}