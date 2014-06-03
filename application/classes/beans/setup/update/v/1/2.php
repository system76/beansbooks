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

class Beans_Setup_Update_V_1_2 extends Beans_Setup_Update_V {

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _execute()
	{
		try
		{
			// Check if account exists - it shouldn't, but always better safe than sorry.
			$prepaid_account_exists = FALSE;
			$prepaid_account_setting = FALSE;
			foreach( ORM::Factory('setting')->find_all() as $setting )
			{
				if( $setting->key == 'purchase_prepaid_purchase_account_id' )
				{	
					$prepaid_account_setting = $setting;

					if( ORM::Factory('account')->where('id','=',$setting->value)->count_all() )
						$prepaid_account_exists = TRUE;
				}
			}

			// If not, we create the account and save it into the appropriate setting.
			if( ! $prepaid_account_exists )
			{
				$account_type_id = FALSE;
				foreach( ORM::Factory('account_type')->find_all() as $account_type )
				{
					if( $account_type->code == "bankaccount" )
						$account_type_id = $account_type->id;
				}

				if( ! $account_type_id )
					throw new Exception("Could not locate account type code: bankaccount");

				$parent_account_id = FALSE;				// Assign this to "Current Assets" if we can find it.
				$fallback_parent_account_id = FALSE;	// Assign this to the Internal Accounts parent...

				foreach( ORM::Factory('account')->find_all() as $account )
				{
					// Find "Current Assets"
					if( $account->name == "Current Assets" )
						$parent_account_id = $account->id;
					// Fallback to "BeansBooks Tracking Accounts"
					else if( ! $account->account_type_id &&
							 ! $account->parent_account_id &&
							 $account->reserved )
						$fallback_parent_account_id = $account->id;
				}

				if( ! $parent_account_id &&
					! $fallback_parent_account_id )
					throw new Exception("Could not locate any valid parent account!");

				// Create the account
				$account = ORM::Factory('account');
				$account->account_type_id = $account_type_id;
				$account->parent_account_id = $parent_account_id
											? $parent_account_id
											: $fallback_parent_account_id;
				$account->reserved = TRUE;
				$account->name = "Prepaid Purchase Orders";
				$account->code = substr(strtolower(str_replace(' ','',$account->name)),0,16);
				$account->terms = NULL;
				$account->writeoff = FALSE;
				$account->save();

				// Save it as the account for setting key purchase_prepaid_purchase_account_id
				if( ! $prepaid_account_setting )
				{
					$prepaid_account_setting = ORM::Factory('setting');
					$prepaid_account_setting->key = 'purchase_prepaid_purchase_account_id';
				}

				$prepaid_account_setting->value = $account->id;
				$prepaid_account_setting->save();
			}
		}
		catch( Exception $e )
		{
			// ERROR CREATING NEW ACCOUNT!
			throw new Exception("Unexpected error when trying to create new prepaid asset account: ".$e->getMessage());
		}
				
		// Calibrate all account balances ( for good measure ).
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

				$update_sql = 'UPDATE accounts SET '.
							  'balance = ( SELECT IFNULL(SUM(bbalance),0.00) FROM ('.
							  '		SELECT IFNULL(balance,0.00) as bbalance FROM '.
							  '		account_transactions as aaccount_transactions WHERE '.
							  '		account_id = "'.$account['id'].'" '.
							  '		ORDER BY date DESC, close_books ASC, transaction_id DESC LIMIT 1 FOR UPDATE '.
							  ') as baccount_transactions ) '.
							  'WHERE id = "'.$account['id'].'"';

				$update_result = DB::Query(Database::UPDATE,$update_sql)->execute();
			}
		}
		catch( Exception $e )
		{
			// This is OK - we just need to warn them that the accounts need calibration.
		}

		return (object)array();
	}
}