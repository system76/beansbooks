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

class Beans_Setup_Update_V_1_1_1 extends Beans_Setup_Update_V {

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _execute()
	{
		throw new Exception("TODO - ENABLE v1.1.1 Update");
		
		try
		{
			$form_type_exist_check = DB::Query(Database::SELECT, 'SELECT COUNT(COLUMN_NAME) as exist_check FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = "transactions" AND COLUMN_NAME = "form_type"')->execute()->as_array();

			if( $form_type_exist_check[0]['exist_check'] == '0' )
				DB::Query(NULL, "ALTER TABLE  `transactions` ADD  `form_type` ENUM(  'sale' , 'purchase' , 'expense' , 'tax_payment' ) NULL DEFAULT NULL AFTER  `entity_id` ")->execute();
			
			$form_id_exist_check = DB::Query(Database::SELECT, 'SELECT COUNT(COLUMN_NAME) as exist_check FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = "transactions" AND COLUMN_NAME = "form_id"')->execute()->as_array();

			if( $form_type_exist_check[0]['exist_check'] == '0' )
				DB::Query(NULL, "ALTER TABLE  `transactions` ADD  `form_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER  `form_type` ")->execute();
		}
		catch ( Exception $e ) 
		{
			throw new Exception("An error occurred when migrating your database tables: ".$e->getMessage());
		}

		try
		{
			$forms = DB::Query(
				Database::SELECT, 
				'SELECT id, type, create_transaction_id, invoice_transaction_id, cancel_transaction_id FROM forms WHERE '.
				'( create_transaction_id IS NOT NULL OR '.
				' invoice_transaction_id IS NOT NULL OR '.
				' cancel_transaction_id IS NOT NULL )'
			)->execute()->as_array();

			foreach( $forms as $form )
			{
				if( $form['create_transaction_id'] )
					DB::Query(Database::UPDATE,'UPDATE transactions SET form_type = "'.$form['type'].'", form_id = '.$form['id'].' WHERE id = '.$form['create_transaction_id'])->execute();

				if( $form['invoice_transaction_id'] )
					DB::Query(Database::UPDATE,'UPDATE transactions SET form_type = "'.$form['type'].'", form_id = '.$form['id'].' WHERE id = '.$form['invoice_transaction_id'])->execute();

				if( $form['cancel_transaction_id'] )
					DB::Query(Database::UPDATE,'UPDATE transactions SET form_type = "'.$form['type'].'", form_id = '.$form['id'].' WHERE id = '.$form['cancel_transaction_id'])->execute();
			}

			$tax_payments = DB::Query(Database::SELECT, ' SELECT id, transaction_id FROM tax_payments WHERE transaction_id IS NOT NULL')->execute()->as_array();

			foreach( $tax_payments as $tax_payment )
				DB::Query(Database::UPDATE,'UPDATE transactions SET form_type = "tax_payment", form_id = '.$tax_payment['id'].' WHERE id = '.$form['transaction_id'])->execute();
		}
		catch( Exception $e )
		{
			throw new Exception("Error creating appropriate records in transactions. ".$e->getMessage());
		}

		return (object)array();
	}
}