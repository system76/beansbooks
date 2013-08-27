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

class Model_Entity extends ORM {
	
	protected $_table_name = 'entities';
	protected $_has_many = array(
		'entity_addresses' => array(
			'foreign_key' => 'entity_id',
		),
		'forms' => array(
			'foreign_key' => 'entity_id',
		),
	);
	protected $_belongs_to = array(
		'default_shipping_address' => array(
			'model' => 'entity_address',
		),
		'default_billing_address' => array(
			'model' => 'entity_address',
		),
		'default_remit_address' => array(
			'model' => 'entity_address',
		),
		'default_account' => array(
			'model' => 'account',
		),
	);

	// Represents the value that will be assigned to $this->type
	// Should be overwritten in any class that extends Model_Entity.
	protected $_entity_type = NULL;

	/**
	 * Default type for a Model_Entity.
	 * @return String type
	 */
	protected function getType()
	{
		return $this->_entity_type;
	}

	protected function _initialize()
	{
		parent::_initialize();
		// We have the ternary because loading a form via Model_Form could set the type to NULL
		$this->type = ( $this->getType() ) ? $this->getType() : $this->type;
	}

	/**
	 * Override ORM->save() to set specific type.
	 * @param  Validation $validation|NULL Validation object
	 * @return ORM
	 */
	public function save(Validation $validation = NULL)
	{
		// We have the ternary because loading a form via Model_Form could set the type to NULL
		$this->type = ( $this->getType() ) ? $this->getType() : $this->type;
		return parent::save();
	}

}
