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


class View_Myaccount_Index extends View_Template {

	public function user()
	{
		if( ! isset($this->auth_user_lookup_result) )
			return FALSE;

		$return_array = $this->_user_array($this->auth_user_lookup_result->data->user);
		
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

	private function _user_array($user)
	{
		return array(
			'primary' => ( $user->id == 1 ? TRUE : FALSE ),
			'id' => $user->id,
			'name' => $user->name,
			'email' => $user->email,
			'role' => $user->role->name,
			'role_id' => $user->role->id,
		);
	}

}