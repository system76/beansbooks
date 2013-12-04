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

class Controller_Json extends Controller {

	// Will be returned as an encoded JSON object.
	protected $_return_object;
	
	public function before()
	{
		parent::before();

		$this->_return_object = new stdClass;
		$this->_return_object->success = TRUE;
		$this->_return_object->error = "";
		$this->_return_object->data = new stdClass;
	}

	public function after()
	{
		// V2Item - Consider adding in a JSON Security check, i.e. "for(;;);" before each response.
		$this->response->body(json_encode($this->_return_object));
		$this->response->headers('Content-Type', 'application/json');
	}

	protected function _return_error($error)
	{
		$this->_return_object->success = FALSE;
		$this->_return_object->error = ( $error )
									 ? $error
									 : "An unknown error has occurred.";

		$this->after();
	}

	/**
	 * Check a result for an error - either auth or data related, and handle appropriately.
	 * @param  stdClass $result 
	 * @return BOOLEAN
	 */
	protected function _beans_result_check($result)
	{
		if( ! $result->success )
		{
			// As of right now we're checking what sort of auth error based on expected
			// error messages.
			// If the message contains "credentials" then the user key is incorrect or invalid.
			// However - if it contains "permission" then it's simply a lack of access.
			if( isset($result->auth_error) AND
				strlen($result->auth_error) )
			{
				if( strpos($result->auth_error,"credential") !== FALSE )
				{
					Session::instance()->destroy();
					$this->request->redirect('/');
				}
				else
					$this->_view->send_error_message($result->auth_error);
			}
			else
			{
				$this->_view->send_error_message($result->error);
			}
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Checks a beans result object for an error and returns it as a string - otherwise FALSE.
	 * @param  stdClass $result 
	 * @return String         
	 */
	protected function _beans_result_get_error($result)
	{
		if( ! $result->success )
		{
			if( isset($result->auth_error) AND
				strlen($result->auth_error) )
				return $result->auth_error;
			else if( isset($result->config_error) AND
				strlen($result->config_error) )
				return $result->config_error;
			else
				return $result->error;
		}
		return FALSE;
	}

}