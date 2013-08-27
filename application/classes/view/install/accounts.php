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


class View_Install_Accounts extends View_Install {

	// Receives $this->accounts_options
	
	public function accounts_options()
	{
		$return_array = array();

		foreach( $this->accounts_options as $key => $val )
		{
			$return_array[] = array(
				'key' => $key,
				'name' => $val['name'],
				'description' => $val['description'],
			);
		}
		//die(print_r($return_array,TRUE));
		return $return_array;
	}

}