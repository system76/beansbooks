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

/*
---BEANSAPISPEC---
@action Beans_Account_Closebooks
@description Close the books on a fiscal period.
@required auth_uid
@required auth_key
@required auth_expiration
@required transfer_account_id The account to transfer retained earning to.
@required date The last day of transactions to include in the fiscal time period.
@returns transaction A #Beans_Account_Transaction# representing the transfer of retained earnings.
---BEANSENDSPEC---
*/
class Beans_Account_Closebooks extends Beans_Account {

	protected $_auth_role_perm = "account_write";

	protected $_transfer_account_id;
	protected $_transfer_account;
	protected $_date;
	protected $_data;
	protected $_closing_transaction;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_transfer_account_id = ( isset($data->transfer_account_id) ) 
				   ? (int)$data->transfer_account_id
				   : 0;

		$this->_transfer_account = $this->_load_account($this->_transfer_account_id);

		$this->_date = ( isset($data->date) )
					 ? $data->date
					 : NULL;

	}

	protected function _execute()
	{
		if( ! $this->_transfer_account->loaded() )
			throw new Exception("That closing transfer account could not be found.");

		if( $this->_transfer_account->account_type->code != "equity" )
			throw new Exception("You must choose an Equity account to distribute the closing transfer to.");

		if( ! $this->_date )
			throw new Exception("Invalid date: none provided.");

		if( date("Y-m-d",strtotime($this->_date)) != $this->_date ) 
			throw new Exception("Invalid date: must be in YYYY-MM-DD format.");

		// In case we change our mind on the format.
		$balance_report_date = $this->_date;

		if( strtotime($balance_report_date) > time() )
			throw new Exception("Invalid date: must be a date in the past.");

		// Make sure we haven't closed after this already.
		if( $this->_check_books_closed($balance_report_date) )
			throw new Exception("The books have already been closed in that date range.");

		$this->_closing_transaction = new stdClass;

		// The first day of the next month.
		$this->_closing_transaction->date = date("Y-m-d",strtotime($this->_date.' +1 Day'));
		$this->_closing_transaction->close_books = $this->_date;
		$this->_closing_transaction->code = "CLOSEBOOKS";
		$this->_closing_transaction->description = "Close Books for ".$this->_date;

		$this->_closing_transaction->account_transactions = array();

		$transfer_account_total = 0.00;

		// Look up all accounts and generate their inverse transaction.
		$accounts = ORM::Factory('account')->where('account_type_id','IS NOT',NULL)->where('reserved','=',FALSE)->find_all();

		foreach( $accounts as $account )
		{
			if( (
					strtolower($account->account_type->type) == "income" OR 
					strtolower($account->account_type->type) == "cost of goods sold" OR 
					strtolower($account->account_type->type) == "expense" 
				) AND
				(
					strpos($account->account_type->code, 'pending_') === FALSE
				) )
			{
				$balance = $this->_generate_simple_account_balance($account->id,$balance_report_date);
				if( $balance != 0.00 )
				{
					$transfer_account_total = $this->_beans_round( $transfer_account_total + $balance );
					$this->_closing_transaction->account_transactions[] = (object)array(
						'account_id' => $account->id,
						'amount' => ( $balance * -1 ),
					);
				}
			}
		}
		
		array_unshift($this->_closing_transaction->account_transactions, (object)array(
			'account_id' => $this->_transfer_account_id,
			'amount' => $transfer_account_total,
		));

		$account_transaction_create = new Beans_Account_Transaction_Create($this->_beans_data_auth($this->_closing_transaction));
		
		return $this->_beans_return_internal_result($account_transaction_create->execute());
	}

	// DUPLICATE FROM class Beans_Report ( beans/report.php )
	protected function _generate_simple_account_balance($account_id,$date_end,$date_start = FALSE)
	{
		$table_sign = 1;

		$balance_query = ' SELECT IFNULL(SUM(amount),0.00) as bal FROM account_transactions WHERE '.
						 ' account_id = "'.$account_id.'" AND '.
						 ' close_books = 0 '.
						 ( $date_end ? ' AND date <= DATE("'.$date_end.'") ' : '' ).
						 ( $date_start ? ' AND date >= DATE("'.$date_start.'") ' : '' );

		$balance_rows = DB::Query(Database::SELECT, $balance_query)->execute()->as_array();

		$balance = $balance_rows[0]['bal'] * $table_sign;

		return $balance;
	}


}