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

class Model_Account extends ORMEncrypted {
	
	/**
	 * Define the columns in this model that are encrypted.
	 * @var array
	 */
	protected $_encrypted_columns = array(
		'name',
		'code',
	);
	
	protected $_belongs_to = array(
		'account_type' => array(),
		'parent_account' => array(
			'model' => 'account',
		),
	);

	protected $_has_many = array(
		'child_accounts' => array(
			'model' => 'account',
			'foreign_key' => 'parent_account_id',
		),
		'account_transactions' => array(),
		'account_reconciles' => array(),
	);
	
}
