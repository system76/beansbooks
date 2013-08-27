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


class View_Install extends Kostache_Layout {

	protected $_layout = 'install';

	protected $_system_messages = FALSE;

	public function send_error_message($text) {
		return $this->_add_system_message('error',$text);
	}

	public function send_success_message($text) {
		return $this->_add_system_message('success',$text);
	}

	public function send_warning_message($text) {
		return $this->_add_system_message('warning',$text);
	}

	private function _add_system_message($type = NULL,$text = NULL) {
		if( ! $type OR 
			! $text )
			return FALSE;

		if( strpos($text, "Missing configuration file") !== FALSE )
			return FALSE;

		if( $this->_system_messages === FALSE )
			$this->_system_messages = array();

		$this->_system_messages[] = array(
			'type' => $type,
			'text' => $text,
		);

		return TRUE;
	}

	public function system_messages() {
		return $this->_system_messages;
	}

}