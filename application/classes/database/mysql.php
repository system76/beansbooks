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

// V2Item - TODO - Write a new ORM or remove altogether.
class Database_MySQL extends Kohana_Database_MySQL {

	// This is a temporary override that will eventually be superceded by an upgrade to 
	// the database module.
	public function connect()
	{
		if ($this->_connection)
			return;

		if (Database_MySQL::$_set_names === NULL)
		{
			// Determine if we can use mysql_set_charset(), which is only
			// available on PHP 5.2.3+ when compiled against MySQL 5.0+
			Database_MySQL::$_set_names = ! function_exists('mysql_set_charset');
		}

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'database'   => '',
			'hostname'   => '',
			'username'   => '',
			'password'   => '',
			'persistent' => FALSE,
		));

		// Prevent this information from showing up in traces
		unset($this->_config['connection']['username'], $this->_config['connection']['password']);

		try
		{
			if ($persistent)
			{
				// Create a persistent connection
				$this->_connection = @mysql_pconnect($hostname, $username, $password);
			}
			else
			{
				// Create a connection and force it to be a new link
				$this->_connection = @mysql_connect($hostname, $username, $password, TRUE);
			}
		}
		catch (Exception $e)
		{
			// No connection exists
			$this->_connection = NULL;

			throw new Database_Exception(':error',
				array(':error' => $e->getMessage()),
				$e->getCode());
		}

		// \xFF is a better delimiter, but the PHP driver uses underscore
		$this->_connection_id = sha1($hostname.'_'.$username.'_'.$password);

		$this->_select_db($database);

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}

		if ( ! empty($this->_config['connection']['variables']))
		{
			// Set session variables
			$variables = array();

			foreach ($this->_config['connection']['variables'] as $var => $val)
			{
				$variables[] = 'SESSION '.$var.' = '.$this->quote($val);
			}

			mysql_query('SET '.implode(', ', $variables), $this->_connection);
		}
	}
}
