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

class Controller_Myaccount extends Controller_View {

	function before()
	{

		parent::before();

		Session::instance()->set('tab_section','myaccount');
		Session::instance()->delete('tab_links');
	}

	public function action_index()
	{
		$auth_user_lookup_result = FALSE;

		// Handle Post and show success message
		if( count($this->request->post()) )
		{
			$valid = TRUE;
			$data = new stdClass;
			$data->id = Session::instance()->get('auth_uid');
			$data->name = $this->request->post('name');
			$data->email = $this->request->post('email');

			if( $this->request->post('current_password') OR 
				$this->request->post('new_password') OR 
				$this->request->post('repeat_password') )
			{
				if( ! strlen($this->request->post('current_password')) OR 
					! strlen($this->request->post('new_password')) OR 
					! strlen($this->request->post('repeat_password')) )
				{
					$this->_view->send_error_message('Please include all password fields to update your password.');
					$valid = FALSE;
				}
				else if ( $this->request->post('new_password') != $this->request->post('repeat_password') )
				{
					$this->_view->send_error_message('Those new passwords did not match.');
					$valid = FALSE;
				}
				else
				{
					$data->current_password = $this->request->post('current_password');
					$data->password = $this->request->post('new_password');
				}
			}

			if( $valid )
			{
				$auth_user_update = new Beans_Auth_User_Update($this->_beans_data_auth($data));
				$auth_user_update_result = $auth_user_update->execute();

				if( ! $auth_user_update_result->success )
				{
					$this->_view->send_error_message($auth_user_update_result->error.$auth_user_update_result->auth_error);
				}
				else
				{
					$this->_view->send_success_message('Your account was updated.');
					$auth_user_lookup_result = $auth_user_update_result;
				}
			}
		}

		if( ! $auth_user_lookup_result )
		{
			$auth_user_lookup = new Beans_Auth_User_Lookup($this->_beans_data_auth((object)array(
				'id' => Session::instance()->get('auth_uid'),
			)));
			$auth_user_lookup_result = $auth_user_lookup->execute();
		}

		$this->_view->auth_user_lookup_result = $auth_user_lookup_result;
	}
}