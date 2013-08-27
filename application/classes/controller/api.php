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

class Controller_Api extends Controller {

	protected $_result_object;

	public function after()
	{
		$this->response->body(json_encode($this->_result_object));
		$this->response->headers('Content-Type', 'application/json');
	}

	public function action_execute()
	{
		$api_path = array();
		$api_path[0] = "Beans";

		foreach( $this->request->param() as $key => $value )
			if( strpos($key,'api_path_') !== FALSE AND 
				strlen(trim($value)) > 0 )
				$api_path[intval(str_replace('api_path_','',$key))] = ucwords(strtolower(trim($value)));
		
		$beans_class_name = implode('_', $api_path);
		$beans_class_data = json_decode($this->request->body());

		if( ! class_exists($beans_class_name) ) 
			throw new HTTP_Exception_404("That action was not found.");

		if( $beans_class_data === NULL )
		{
			$this->_result_object = new stdClass;
			$this->_result_object->success = FALSE;
			$this->_result_object->error = "Malformed JSON POST data.";
			$this->_result_object->auth_error = "";
			$this->_result_object->data = (object)array();

			return;
		}

		$beans_class_action = new $beans_class_name($beans_class_data);
		$beans_class_result = $beans_class_action->execute();

		$this->_result_object = $beans_class_result;
	}

}