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

class Controller_Auth extends Controller_View {

	public function before()
	{
		parent::before();

		if( Session::instance()->get('auth_success_message') )
		{
			$this->_view->send_success_message(Session::instance()->get('auth_success_message'));
			Session::instance()->delete('auth_success_message');
		}
	}

	function action_index()
	{
		// Check and make sure Beans is setup.
		$setup_check = new Beans();
		$setup_check_result = $setup_check->execute();

		if( ! $setup_check_result->success )
			$this->request->redirect('/install');

		if( count($this->request->post()) )
		{
			$auth_login = new Beans_Auth_Login((object)array(
				'email' => $this->request->post('email'),
				'password' => $this->request->post('password'),
			));
			$auth_login_result = $auth_login->execute();

			if( $this->_beans_result_check($auth_login_result) )
			{
				if( isset($auth_login_result->data->reset) )
					$this->request->redirect('/auth/reset/'.$auth_login_result->data->reset);
				
				Session::instance()->set('auth_uid',$auth_login_result->data->auth->auth_uid);
				Session::instance()->set('auth_expiration',$auth_login_result->data->auth->auth_expiration);
				Session::instance()->set('auth_key',$auth_login_result->data->auth->auth_key);
				Session::instance()->set('auth_role',$auth_login_result->data->auth->user->role);
				$this->request->redirect('/');
			}
		}
	}

	function action_reset()
	{
		$resetkey = FALSE;

		if( $this->request->param('id') &&
			strlen($this->request->param('id')) )
		{
			$resetkey = $this->request->param('id');
		} 
		if( count($this->request->post()) )
		{
			$data = new stdClass;
			foreach( $this->request->post() as $key => $value ) 
				$data->{$key} = $value;

			$valid = TRUE;

			if( $this->request->post('password') AND 
				$this->request->post('password') != $this->request->post('password_repeat') )
			{
				$this->_view->send_error_message("Those passwords did not match.");
				$valid = FALSE;
			}

			if( ! $resetkey )
				$resetkey = $this->request->post('resetkey');

			if( $valid ) 
			{
				$auth_reset = new Beans_Auth_Reset($data);
				$auth_reset_result = $auth_reset->execute();

				if( $this->_beans_result_check($auth_reset_result) )
				{
					if( isset($auth_reset_result->data->auth) )
					{
						Session::instance()->set('auth_uid',$auth_reset_result->data->auth->auth_uid);
						Session::instance()->set('auth_expiration',$auth_reset_result->data->auth->auth_expiration);
						Session::instance()->set('auth_key',$auth_reset_result->data->auth->auth_key);
						Session::instance()->set('auth_role',$auth_reset_result->data->auth->user->role);
					} else {
						Session::instance()->set('auth_success_message','An email has been sent to you with instructions.');
						$this->request->redirect('/auth');
					}

					$this->request->redirect('/');
				}
				else
				{
					$this->_view->send_error_message($auth_reset_result->error.$auth_reset_result->auth_error);
				}
			}
		}

		$this->_view->resetkey = $resetkey;
	}

	function action_logout()
	{
		Session::instance()->destroy();
		$this->request->redirect('/');
	}

}