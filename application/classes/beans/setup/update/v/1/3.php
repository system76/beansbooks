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

class Beans_Setup_Update_V_1_3 extends Beans_Setup_Update_V {

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _execute()
	{
		$this->_db_remove_table_column(	'taxes', 'fee');
		$this->_db_add_table_column(	'taxes', 'visible', 'BOOLEAN NOT NULL DEFAULT TRUE');
		
		// We will remove form_line_taxes after the form_lines have been updated to be exempt or not.

		$this->_db_remove_table_column(	'form_taxes', 'quantity');
		$this->_db_remove_table_column(	'form_taxes', 'fee');
		$this->_db_update_table_column(	'form_taxes', 'percent', '`tax_percent` decimal( 6, 6 ) NULL DEFAULT NULL');
		$this->_db_update_table_column(	'form_taxes', 'amount', '`form_line_taxable_amount` decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'form_taxes', 'form_line_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL AFTER `tax_id`');		

		$this->_db_add_table(			'tax_items');
		$this->_db_add_table_column(	'tax_items', 'tax_id', 'bigint(20) unsigned DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'form_id', 'bigint(20) unsigned DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'tax_payment_id', 'bigint(20) unsigned DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'date', 'date DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'type', 'enum("invoice","refund") DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'form_line_amount', 'decimal(15,2) DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'form_line_taxable_amount', 'decimal(15,2) DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'tax_percent', 'decimal(6,6) DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'total', 'decimal(15,2) DEFAULT NULL');
		$this->_db_add_table_column(	'tax_items', 'balance', 'decimal(15,2) DEFAULT NULL');

		$this->_db_add_table_column(	'tax_payments', 'writeoff_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL AFTER `amount`');
		$this->_db_add_table_column(	'tax_payments', 'invoiced_line_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'invoiced_line_taxable_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'invoiced_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'refunded_line_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'refunded_line_taxable_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'refunded_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'net_line_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'net_line_taxable_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		$this->_db_add_table_column(	'tax_payments', 'net_amount', 'decimal( 15, 2 ) NULL DEFAULT NULL');
		
		$this->_db_add_table_column(	'forms', 'tax_exempt', 'boolean NOT NULL DEFAULT FALSE AFTER `type`');
		$this->_db_add_table_column(	'forms', 'tax_exempt_reason' ,'varchar(255) NULL DEFAULT NULL AFTER `tax_exempt`');

		$this->_db_add_table_column(	'form_lines', 'tax_exempt', 'boolean NOT NULL DEFAULT FALSE AFTER `adjustment`');
		
		return (object)array();
	}


}