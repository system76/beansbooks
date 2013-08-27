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


/**
 * Extension of Kohana ORM class to enabled per-column encryption and compression.
 * 		protected $_encrypted_compressed_columns; Array of columns that are compressed and encrypted.
 * 		protected $_encrypted_columns; Array of columns that are encrypted.
 * Requires KOHANA modules/encrypt
 */
class ORMEncrypted extends ORM {

	protected $_encrypted_columns = array();
	protected $_encrypted_compressed_columns = array();

	/**
	 * Necesarry override to enable per-column encryption.
	 * @param String $column
	 * @param mixed $value 
	 */
	public function set($column, $value)
	{
		if( in_array($column,$this->_encrypted_compressed_columns) )
			return parent::set($column,Encrypt::instance()->encode(gzcompress($value,1)));

		if( in_array($column,$this->_encrypted_columns) )
			return parent::set($column,Encrypt::instance()->encode($value));

		return parent::set($column,$value);
	}
	
	/**
	 * Necessary override to enable per-column encryption.
	 * @param  String $column 
	 * @return mixed
	 */
	public function __get($column)
	{
		if( in_array($column,$this->_encrypted_compressed_columns) )
			return gzuncompress(Encrypt::instance()->decode(parent::__get($column)));

		if( in_array($column,$this->_encrypted_columns) )
			return Encrypt::instance()->decode(parent::__get($column));

		return parent::__get($column);
	}

}