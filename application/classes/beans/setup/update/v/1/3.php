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
		throw new Exception("PENDING: v1.3 Update Script");
		
		// Required DB Changes:
		// - Add taxes.visible BOOLEAN
		// 		ALTER TABLE `taxes` ADD `visible` BOOLEAN NOT NULL DEFAULT TRUE ;

		try
		{
			$tax_visible_exist_check = DB::Query(
				Database::SELECT, 
				'SELECT COUNT(COLUMN_NAME) as exist_check '.
				'FROM INFORMATION_SCHEMA.COLUMNS WHERE '.
				'TABLE_NAME = "taxes" '.
				'AND COLUMN_NAME = "visible"'
			)->execute()->as_array();

			if( $tax_visible_exist_check[0]['exist_check'] == '0' )
			{
				DB::Query(
					NULL,
					'ALTER TABLE `taxes` ADD `visible` BOOLEAN NOT NULL DEFAULT TRUE'
				);
			}
		}
		catch( Exception $e )
		{
			throw new Exception("An error occurred when migrating your database tables: ".$e->getMessage());
		}

		
		return (object)array();
	}
}