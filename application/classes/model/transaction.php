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

class Model_Transaction extends ORMEncrypted {
	
	/**
	 * Define the columns in this model that are encrypted.
	 * @var array
	 */
	protected $_encrypted_columns = array(
		// 'description',
		// REMOVED TO ALLOW RULE MATCHING
	);

	protected $_has_many = array(
		'account_transactions' => array(),
	);

	protected $_has_one = array(
		'create_form' => array(
			'model' => 'form',
			'foreign_key' => 'create_transaction_id',
		),
		'invoice_form' => array(
			'model' => 'form',
			'foreign_key' => 'invoice_transaction_id',
		),
		'cancel_form' => array(
			'model' => 'form',
			'foreign_key' => 'cancel_transaction_id',
		),
		'tax_payment' => array(),
	);

}