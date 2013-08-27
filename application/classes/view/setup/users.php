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

class View_Setup_Users extends View_Template {
	// Receives $this->auth_role_search_result
	// Receives $this->auth_user_search_result

	public function domain()
	{
		return URL::base(TRUE,TRUE);
	}

	public function user_roles()
	{
		$return_array = array();

		if( ! isset($this->auth_role_search_result) )
			return FALSE;

		foreach( $this->auth_role_search_result->data->roles as $role )
		{
			if( $role->code != "api" )
				$return_array[] = $this->_role_array($role);
		}

		return $return_array;
	}

	private function _role_array($role) 
	{
		return array(
			'id' => $role->id,
			'name' => $role->name,
			'description' => $role->description,
		);
	}

	public function users()
	{
		$return_array = array();

		if( ! isset($this->auth_user_search_result) )
			return FALSE;

		foreach( $this->auth_user_search_result->data->users as $user )
		{
			if( $user->role->code != "api" )
				$return_array[] = $this->_user_array($user);
		}

		return $return_array;
	}

	private function _user_array($user)
	{
		return array(
			'primary' => ( $user->id == 1 ? TRUE : FALSE ),
			'active' => ( $user->id == Session::instance()->get('auth_uid') ? TRUE : FALSE ),
			'id' => $user->id,
			'name' => $user->name,
			'email' => $user->email,
			'role' => $user->role->name,
			'role_id' => $user->role->id,
		);
	}

	public function api_user()
	{
		if( ! isset($this->api_role_lookup_result) OR 
			! count($this->api_role_lookup_result->data->users) )
			return FALSE;

		return array(
			'auth_uid' => $this->api_role_lookup_result->data->users[0]->id,
			'auth_expiration' => $this->api_role_lookup_result->data->users[0]->current_auth_expiration,
			'auth_key' => FALSE,
		);
	}
}