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

class Beans_Setup_Update_V_1_0_1 extends Beans_Setup_Update_V {

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _execute()
	{
		try
		{
			$exist_check = DB::Query(Database::SELECT, 'SELECT COUNT(COLUMN_NAME) as exist_check FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = "account_transactions" AND COLUMN_NAME = "close_books"')->execute()->as_array();

			if( $exist_check[0]['exist_check'] == '0' )
			{
				// Add the close_books column to account_transactions
				DB::Query(NULL, 'ALTER TABLE  `account_transactions` ADD `close_books` BOOLEAN NOT NULL DEFAULT FALSE')->execute();

				// Update account_transactions to properly reflect the change.
				DB::Query(Database::UPDATE, 'UPDATE account_transactions SET close_books = 1 WHERE transaction_id IN ( SELECT id FROM transactions WHERE close_books IS NOT NULL )')->execute();
			}
		}
		catch ( Exception $e ) 
		{
			throw new Exception("An error occurred when migrating your database tables: ".$e->getMessage());
		}

		try
		{
			$accounts = DB::Query(DATABASE::SELECT, 'SELECT id FROM accounts WHERE parent_account_id IS NOT NULL')->execute()->as_array();

			foreach( $accounts as $account )
			{
				$previous_balance = 0.00;
				$calibrated_balance = 0.00;
				
				// Look up all transactions in order.
				$account_transactions = DB::Query(Database::SELECT,'SELECT id,date,transaction_id,amount,balance FROM account_transactions WHERE account_id = "'.$account['id'].'" ORDER BY date ASC, close_books DESC, transaction_id ASC')->execute()->as_array();

				foreach( $account_transactions as $account_transaction )
				{
					$calibrated_balance = $account_transaction['balance'];

					if( round(( $account_transaction['amount'] + $previous_balance ),2) != $calibrated_balance )
						$calibrated_balance = round(( $account_transaction['amount'] + $previous_balance ),2);

					
					// Update if necessary.
					if( $account_transaction['balance'] != $calibrated_balance )
						DB::Query(Database::UPDATE,'UPDATE account_transactions SET balance = '.$calibrated_balance.' WHERE id = "'.$account_transaction['id'].'"')->execute();

					$previous_balance = $calibrated_balance;
				}
			}
		}
		catch( Exception $e )
		{
			// This is OK - we just need to warn them that the accounts need calibration.
		}

		return (object)array();
	}
}

