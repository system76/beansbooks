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

class Beans_Setup_Update_Run extends Beans_Setup_Update {

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}

	protected function _execute()
	{
		$current_version = $this->_get_current_beans_version();

		// Find the next available update.
		$target_version = $this->_get_next_update($current_version);
		
		if( ! $target_version )
			throw new Exception("The currently installed version is ".$current_version." and the upgrade path target is ".$this->_BEANS_VERSION.", however there is no next version upgrade to complete.");
		
		if( ! isset($this->_data->target_version) ||
			! $this->_data->target_version ||
			$this->_data->target_version != $target_version )
			throw new Exception("Invalid update target provided. Expected: ".$target_version);

		// Check if update script exists.
		$update_script = 'Beans_Setup_Update_V_'.implode('_',explode('.',$target_version));

		if( ! class_exists($update_script) )
			throw new Exception("Fatal error: update script not found.  Looking for: ".$update_script);
		
		$update = new $update_script($this->_beans_data_auth());
		$update_result = $update->execute();

		if( ! $update_result->success )
			throw new Exception("Error running update: ".$update_result->error.$update_result->auth_error.$update_result->config_error);

		// Update beans current version.
		$this->_beans_setting_set('BEANS_VERSION',$target_version);

		$this->_beans_settings_save();

		$new_target_version = $this->_get_next_update($target_version);
		
		if( ! $new_target_version &&
			$this->_beans_setting_get('BEANS_VERSION') == $this->_BEANS_VERSION )
			$new_target_version = $this->_BEANS_VERSION;

		return (object)array(
			'current_version' => $this->_beans_setting_get('BEANS_VERSION'),
			'target_version' => $new_target_version,
		);
	}
	
	

}