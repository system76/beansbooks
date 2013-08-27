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

class Beans_Vendor_Purchase extends Beans_Vendor {

	protected $_auth_role_perm = "vendor_purchase_read";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _validate_form_line($form_line, $form_type = NULL)
	{
		parent::_validate_form_line($form_line,"purchase");
	}

}
