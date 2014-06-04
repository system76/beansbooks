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

class Controller_Update extends Controller_View {

	public function before()
	{
		$setup_check = new Beans();
		$setup_check_result = $setup_check->execute();

		if( ! Kohana::$is_cli &&
			$this->request->action() != "manual" && 
			(
				$setup_check_result->success ||
				! isset($setup_check_result->config_error) ||
				strpos(strtolower($setup_check_result->config_error),'update') === FALSE 
			) )
			$this->request->redirect('/');

		parent::before();

		$this->_view->head_title = "Update BeansBooks";
		$this->_view->page_title = "Updates Ready to Install";
	}

	public function action_index()
	{
		// Update!
		
		$setup_update_pending = new Beans_Setup_Update_Pending((object)array(
			'auth_uid' => "UPDATE",
			'auth_key' => "UPDATE",
			'auth_expiration' => "UPDATE",
		));
		$setup_update_pending_result = $setup_update_pending->execute();

		if( $this->_beans_result_check($setup_update_pending_result) )
			$this->_view->setup_update_pending_result = $setup_update_pending_result;
	}

	public function action_run()
	{
		// Run Update!
		$target_version = $this->request->post('target_version');

		if( ! $target_version )
			$this->request->redirect('/update/');

		$setup_update_run = new Beans_Setup_Update_Run((object)array(
			'auth_uid' => "UPDATE",
			'auth_key' => "UPDATE",
			'auth_expiration' => "UPDATE",
			'target_version' => $target_version,
		));
		$setup_update_run_result = $setup_update_run->execute();

		if( $setup_update_run_result->success )
		{
			Session::instance()->set('global_success_message','Your instance of BeansBooks has been successfully upgraded to version '.$setup_update_run_result->data->current_version);
			$this->request->redirect('/');
		}
		else
		{
			Session::instance()->set('global_error_message',$setup_update_run_result->error);
			$this->request->redirect('/update');
		}
	}

	public function action_manual()
	{
		set_time_limit(60 * 10);
		
		if( ! Kohana::$is_cli )
			$this->request->redirect('/');

		if( ! file_exists(APPPATH.'classes/beans/config.php') OR 
			filesize(APPPATH.'classes/beans/config.php') < 1 )
			die("Error: Missing config.php\n");

		$setup_update_pending = new Beans_Setup_Update_Pending((object)array(
			'auth_uid' => "UPDATE",
			'auth_key' => "UPDATE",
			'auth_expiration' => "UPDATE",
		));
		$setup_update_pending_result = $setup_update_pending->execute();

		if( ! $setup_update_pending_result->success )
		{
			die(
				"Error querying version info: ".
				$setup_update_pending_result->error.
				$setup_update_pending_result->auth_error.
				$setup_update_pending_result->config_error."\n"
			);
		}

		if( $setup_update_pending_result->data->current_version == $setup_update_pending_result->data->target_version )
			die('BeansBooks is already fully updated to the local source version '.$setup_update_pending_result->data->current_version.'.'."\n");

		// Run all possible updates.
		$target_version = $setup_update_pending_result->data->target_version;
		$current_version = $setup_update_pending_result->data->current_version;
		while( $current_version != $target_version )
		{
			$setup_update_run = new Beans_Setup_Update_Run((object)array(
				'auth_uid' => "UPDATE",
				'auth_key' => "UPDATE",
				'auth_expiration' => "UPDATE",
				'target_version' => $target_version,
			));
			$setup_update_run_result = $setup_update_run->execute();

			if( ! $setup_update_run_result->success )
			{
				die(
					'Error running update to version '.
					$target_version.': '.
					$setup_update_run_result->error.
					$setup_update_run_result->auth_error.
					$setup_update_run_result->config_error."\n"
				);
			}

			$current_version = $setup_update_run_result->data->current_version;
			$target_version = $setup_update_run_result->data->target_version;

			echo 'Successfully updated to version '.$current_version.".\n";
		}

		die('BeansBooks has been updated to local source version '.$current_version.'.'."\n");

	}

}