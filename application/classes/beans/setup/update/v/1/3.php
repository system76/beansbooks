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

		/*
		Required Database Updates:

		ALTER TABLE `taxes` DROP `fee` ;

		DROP TABLE `form_line_taxes`;

		ALTER TABLE `form_taxes` CHANGE `percent` `tax_percent` DECIMAL( 6, 6 ) NULL DEFAULT NULL ;
		ALTER TABLE `form_taxes` CHANGE `amount` `form_line_taxable_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL ;
		ALTER TABLE `form_taxes` ADD `form_line_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL AFTER `tax_id` ;
		ALTER TABLE `form_taxes` DROP `quantity`;
		ALTER TABLE `form_taxes` DROP `fee` ;

		CREATE TABLE IF NOT EXISTS `tax_items` (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `tax_id` bigint(20) unsigned DEFAULT NULL,
		  `form_id` bigint(20) unsigned DEFAULT NULL,
		  `tax_payment_id` bigint(20) unsigned DEFAULT NULL,
		  `date` date DEFAULT NULL,
		  `type` enum('invoice','refund') DEFAULT NULL,
		  `form_line_amount` decimal(15,2) DEFAULT NULL,
		  `form_line_taxable_amount` decimal(15,2) DEFAULT NULL,
		  `tax_percent` decimal(6,6) DEFAULT NULL,
		  `total` decimal(15,2) DEFAULT NULL,
		  `balance` decimal(15,2) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

		ALTER TABLE `tax_payments` ADD `writeoff_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL AFTER `amount` ;
		ALTER TABLE `tax_payments` ADD `invoiced_line_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `invoiced_line_taxable_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `invoiced_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `refunded_line_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `refunded_line_taxable_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `refunded_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `net_line_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL;
		ALTER TABLE `tax_payments` ADD `net_line_taxable_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL ;
		ALTER TABLE `tax_payments` ADD `net_amount` DECIMAL( 15, 2 ) NULL DEFAULT NULL ;

		ALTER TABLE `forms` ADD `tax_exempt` BOOLEAN NOT NULL DEFAULT FALSE AFTER `type` ;

		ALTER TABLE `form_lines` ADD `tax_exempt` BOOLEAN NOT NULL DEFAULT FALSE AFTER `adjustment` ;

		
		 */

		
		return (object)array();
	}
}