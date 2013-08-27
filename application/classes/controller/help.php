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

class Controller_Help extends Controller_View {

	function before()
	{

		parent::before();

		Session::instance()->set('tab_section','help');
		Session::instance()->delete('tab_links');
	}

	public function action_index()
	{
		// Nada
	}

	public function action_dash()
	{

	}

	public function action_customers()
	{

	}

	public function action_vendors()
	{

	}

	public function action_accounts()
	{

	}

	public function action_setup()
	{

	}

}