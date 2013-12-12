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

class Beans_Setup_Update extends Beans_Setup {

	protected $_auth_role_perm = "UPDATE";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _get_next_update($current_version)
	{
		$updates = $this->_find_all_updates(Kohana::list_files('classes/beans/setup/update/v'));

		if( ! in_array($current_version, $updates) )
			$updates[] = $current_version;

		// PHP 5.4+ Required for this...  using an ugly hack instead.
		// sort($updates,'SORT_NATURAL');
		natsort($updates);

		$new_updates = array();
		foreach( $updates as $update )
				$new_updates[] = $update;

		$updates = $new_updates;

		$i = 0;
		$target_version = FALSE;
		while( ! $target_version && 
			   $i < count($updates) )
		{
			if( $updates[$i] == $current_version &&
				isset($updates[$i+1]) )
				$target_version = $updates[$i+1];

			$i++;
		}

		return $target_version;
	}

	protected function _find_all_updates($files)
	{
		$return_array = array();

		foreach( $files as $file )
		{
			if( is_array($file) )
			{
				$return_array = array_merge($return_array,$this->_find_all_updates($file));
			}
			else
			{
				$return_array[] = str_replace('.php','',str_replace('/','.',substr($file,strpos($file,'/beans/setup/update/v/')+strlen('/beans/setup/update/v/'))));
			}
		}

		return $return_array;
	}

}