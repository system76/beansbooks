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

class Beans_Setup_Update_Pending extends Beans_Setup_Update {

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _execute()
	{
		$current_version = $this->_get_current_beans_version();

		if( $current_version == $this->_BEANS_VERSION )
		{
			return (object)array(
				'current_version' => $current_version,
				'target_version' => $current_version,
			);
		}
		
		// Find the next available update.
		$target_version = $this->_get_next_update($current_version);
		
		if( ! $target_version )
			throw new Exception("The currently installed version is ".$current_version." and the upgrade path target is ".$this->_BEANS_VERSION.", however there is no next version upgrade to complete.");

		return (object)array(
			'current_version' => $current_version,
			'target_version' => $target_version,
		);
	}
	
}