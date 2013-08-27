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


class View_Partials_Setup_Users_Api extends Kostache {
	// Receives $this->user
	// Receives $this->auth
	
	public function auth_uid()
	{
		if( isset($this->auth) )
			return $this->auth->auth_uid;
		else if ( isset($this->user) )
			return $this->user->id;

		return FALSE;
	}

	public function auth_expiration()
	{
		if( isset($this->auth) )
			return $this->auth->auth_expiration;
		else if ( isset($this->user) )
			return $this->user->current_auth_expiration;

		return FALSE;
	}

	public function auth_key()
	{
		if( isset($this->auth) )
			return $this->auth->auth_key;
		
		return FALSE;
	}
}
