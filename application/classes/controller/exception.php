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

class Controller_Exception extends Controller_View {

	protected $_message;
	protected $_code;

	public function before()
	{
		parent::before();
		
		if( ! Session::instance()->get('tab_section') )
		{
			Session::instance()->set('tab_section',$this->request->controller());
			
			$tab_links = array();

			Session::instance()->set('tab_links',$tab_links);
		}

		if( Request::current()->is_initial() )
		{
			$this->_code = 404;
			$this->_message = "Page not found.";
		}
		else
		{
			$this->_code = base64_decode($this->request->param('code'));
			$this->_message = base64_decode($this->request->param('message'));
		}
		
		$this->response->status($this->_code);
	}

	public function action_thrown()
	{
		// Auth Error - Otherwise we handle it normally.
		if( $this->_code == 401 )
		{
			$this->_view->send_error_message($this->_message);
		}
		else
		{
			$this->_view->code = $this->_code;
			$this->_view->message = $this->_message;
		}
	}

}