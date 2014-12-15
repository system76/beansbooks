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
@action Beans_Tax_Show
@description Set a tax to be visible.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax# being requested.
@returns tax OBJECT The resulting #Beans_Tax#.
---BEANSENDSPEC---
*/
class Beans_Tax_Show extends Beans_Tax {
	
	protected $_auth_role_perm = "setup";

	protected $_id;
	protected $_tax;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_tax = $this->_load_tax($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_tax->loaded() )
			throw new Exception("Tax could not be found.");

		$this->_tax->visible = TRUE;
		$this->_tax->save();

		return (object)array(
			"tax" => $this->_return_tax_element($this->_tax),
		);
	}
}