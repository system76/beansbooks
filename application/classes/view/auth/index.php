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


class View_Auth_Index extends View_Template {
	
	public function contentclass()
	{
		return 'form';
	}

	public function masthead_links()
	{
		$masthead_links = array();

		$masthead_links[] = array(
			'url' => '/auth/',
			'text' => 'Login',
			'class' => 'login',
			'current' => FALSE,
		);
		

		return $masthead_links;
	}
}