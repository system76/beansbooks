<?php defined('SYSPATH') or die('No direct script access.');
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

class Beans_Setup_Update_V_1_5 extends Beans_Setup_Update_V {

	private $_table_column_add_indexes = array(
		'accounts' => array(
			'parent_account_id',
			'account_type_id',
		),
		'account_reconciles' => array(
			'account_id',
		),
		'account_transactions' => array(
			'transaction_id',
			'account_id',
			'account_reconcile_id',
		),
		'account_transaction_forms' => array(
			'account_transaction_id',
			'form_id',
		),
		'entities' => array(
			'default_shipping_address_id',
			'default_billing_address_id',
			'default_remit_address_id',
			'default_account_id',
		),
		'entity_addresses' => array(
			'entity_id',
		),
		'forms' => array(
			'entity_id',
			'account_id',
			'create_transaction_id',
			'invoice_transaction_id',
			'cancel_transaction_id',
			'refund_form_id',
			'shipping_address_id',
			'billing_address_id',
			'remit_address_id',
		),
		'form_lines' => array(
			'form_id',
			'account_id',
		),
		'form_taxes' => array(
			'form_id',
			'tax_id',
		),
		'taxes' => array(
			'account_id',
		),
		'tax_items' => array(
			'tax_id',
			'form_id',
			'tax_payment_id',
		),
		'tax_payments' => array(
			'tax_id',
			'transaction_id',
		),
		'transactions' => array(
			'entity_id',
			'form_id',
		),
		'users' => array(
			'role_id',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _execute()
	{
		// Clean up id columns to be bigint(20)
		$this->_db_update_table_column( 'accounts', 'id', '`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		$this->_db_update_table_column( 'settings', 'id', '`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		$this->_db_update_table_column( 'taxes', 'id', '`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT');

		// Ensure that all foreign key columns are bigint(20)
		$this->_db_update_table_column( 'accounts', 'parent_account_id', '`parent_account_id` bigint(20) unsigned NULL DEFAULT NULL');
		$this->_db_update_table_column( 'account_reconciles', 'account_id', '`account_id` bigint(20) unsigned NULL DEFAULT NULL');
		$this->_db_update_table_column( 'entities', 'default_account_id', '`default_account_id` bigint(20) unsigned NULL DEFAULT NULL');
		$this->_db_update_table_column( 'users', 'role_id', '`role_id` bigint(20) unsigned NULL DEFAULT NULL');

		// Add indexes
		foreach( $this->_table_column_add_indexes as $table_name => $columns )
		{
			foreach( $columns as $column_name )
			{
				try
				{
					$key_exist_check = DB::Query(
						Database::SELECT, 
						'SELECT COUNT(TABLE_NAME) as exist_check '.
						'FROM INFORMATION_SCHEMA.STATISTICS WHERE '.
						'TABLE_NAME = "'.$table_name.'" '.
						'AND COLUMN_NAME = "'.$column_name.'"'
					)->execute()->as_array();

					if( $key_exist_check[0]['exist_check'] == '0' )
					{
						DB::Query(
							NULL,
							'ALTER TABLE `'.$table_name.'` '.
							'ADD INDEX(`'.$column_name.'`);'
						)->execute();
					}
				}
				catch( Exception $e )
				{
					throw new Exception('An error occurred when adding an index ('.$table_name.'.'.$column_name.') to the database: '.$e->getMessage());
				}
			}
		}

		// Update quantity to three decimal places.
		$this->_db_update_table_column( 'form_lines', 'quantity', '`quantity` decimal( 13, 3 ) NULL DEFAULT NULL');

		return (object)array();
	}


}