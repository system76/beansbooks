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

class Controller extends Kohana_Controller {

	protected $_required_role_permissions = array();

	function before()
	{
		parent::before();

		// Auth redirects.
		if( $this->request->controller() != "api" AND 
			$this->request->controller() != "auth" AND
			$this->request->controller() != "update" AND
			$this->request->controller() != "install" AND
			$this->request->controller() != "exception" AND
			(
				! strlen(Session::instance()->get('auth_uid')) OR
				! strlen(Session::instance()->get('auth_expiration')) OR
				! strlen(Session::instance()->get('auth_key')) OR 
				! Session::instance()->get('auth_role')
			) )
			$this->request->redirect('/');
		// If logged in we redirect to /dash rather than auth
		else if(	$this->request->controller() == "auth" AND
					$this->request->action() != "logout" AND
					(
						strlen(Session::instance()->get('auth_uid')) AND
						strlen(Session::instance()->get('auth_expiration')) AND
						strlen(Session::instance()->get('auth_key')) AND 
						Session::instance()->get('auth_role')
					) )
			$this->request->redirect('/dash');

		// Avoid a nested exception thrown.
		if( $this->request->controller() != "api" AND 
			$this->request->controller() != "auth" AND
			$this->request->controller() != "update" AND 
			$this->request->controller() != "install" AND 
			$this->request->controller() != "exception" AND 
			count($this->_required_role_permissions) )
		{
			$auth_role = Session::instance()->get('auth_role');

			if( ! isset($this->_required_role_permissions['default']) )
				throw new HTTP_Exception_401("Developer Error! No default permission set!");

			if( isset($this->_required_role_permissions[$this->request->action()]) AND
				(
					! isset($auth_role->{$this->_required_role_permissions[$this->request->action()]}) OR 
					! $auth_role->{$this->_required_role_permissions[$this->request->action()]} 
				) )
				throw new HTTP_Exception_401("Your account does not have access to this feature.");

			if( ! isset($this->_required_role_permissions[$this->request->action()]) AND
				(
					! isset($auth_role->{$this->_required_role_permissions['default']}) OR 
					! $auth_role->{$this->_required_role_permissions['default']} 
				) )
				throw new HTTP_Exception_401("Your account does not have access to this feature.");
		}
	}

	// Beans Authentication
	protected function _beans_data_auth($data = NULL)
	{
		if( $data === NULL )
			$data = new stdClass;

		if( is_array($data) )
			$data = (object)$data;

		if( ! is_object($data) OR
			get_class($data) != "stdClass" )
			$data = new stdClass;

		// Set our auth keys.
		$data->auth_uid = Session::instance()->get('auth_uid');
		$data->auth_expiration = Session::instance()->get('auth_expiration');
		$data->auth_key = Session::instance()->get('auth_key');

		return $data;
	}

	protected function _get_numeric_value($value)
	{
		return preg_replace('/[^0-9.]*/','', $value);
	}

}