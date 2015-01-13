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
@action Beans_Tax_Payment_Cancel
@deprecated This function has been replaced with the more accurately named Beans_Tax_Payment_Delete
@description Remove a tax payment.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax_Payment# being removed.
---BEANSENDSPEC---
*/
class Beans_Tax_Payment_Cancel extends Beans_Tax_Payment {

	protected $_auth_role_perm = "vendor_payment_write";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _execute()
	{
		throw new Exception("Beans_Tax_Payment_Cancel has been replaced with Beans_Tax_Payment_Delete");
	}
}