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
@action Beans_Setup_Company_List
@description Return all company settings.
@required auth_uid
@required auth_key
@required auth_expiration
@returns settings OBJECT An object with a key => value for each company setting that has been saved or created.
---BEANSENDSPEC---
*/
class Beans_Setup_Company_List extends Beans_Setup_Company {

	protected $_auth_role_perm = "login";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _execute()
	{
		return (object)array(
			"settings" => $this->_return_company_settings($this->_beans_settings_dump()),
		);
	}
}