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

class Controller_Accounts_Json extends Controller_Json {

	public function action_accountcreate()
	{	
		$account_create = new Beans_Account_Create($this->_beans_data_auth((object)array(
			'parent_account_id' => $this->request->post('parent_account_id'),
			'account_type_id' => $this->request->post('account_type_id'),
			'name' => $this->request->post('name'),
			'code' => substr(strtolower(str_replace(' ','',$this->request->post('name'))),0,16),
			'terms' => strlen($this->request->post('terms')) ? $this->request->post('terms') : NULL,
		)));

		$account_create_result = $account_create->execute();

		if( ! $account_create_result->success )
			return $this->_return_error($this->_beans_result_get_error($account_create_result));
		
		// Success!
		$this->_return_object->data->account = $account_create_result->data->account;
	}

	public function action_accountupdate()
	{
		$account_update = new Beans_Account_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('account_id'),
			'parent_account_id' => $this->request->post('parent_account_id'),
			'account_type_id' => $this->request->post('account_type_id'),
			'name' => $this->request->post('name'),
			'code' => substr(strtolower(str_replace(' ','',$this->request->post('name'))),0,16),
			'terms' => strlen($this->request->post('terms')) ? $this->request->post('terms') : NULL,
		)));

		$account_update_result = $account_update->execute();

		if( ! $account_update_result->success )
			return $this->_return_error($this->_beans_result_get_error($account_update_result));
		
		// Success!
		$this->_return_object->data->account = $account_update_result->data->account;
	}

	public function action_accountdelete()
	{
		$account_delete = new Beans_Account_Delete($this->_beans_data_auth((object)array(
			'id' => $this->request->post('account_id'),
			'transfer_account_id' => $this->request->post('transfer_account_id'),
		)));

		$account_delete_result = $account_delete->execute();

		if( ! $account_delete_result->success )
			return $this->_return_error($this->_beans_result_get_error($account_delete_result));

	}

	public function action_transactionsjumptomonth()
	{
		$account_id = $this->request->post('account_id');
		$last_transaction_id = $this->request->post('last_transaction_id');
		$last_transaction_date = $this->request->post('last_transaction_date');
		$month = $this->request->post('month');

		if( ! $account_id )
			return $this->_return_error("An error occurred: no account ID was provided.");

		if( ! $last_transaction_id )
			return $this->_return_error("An error occurred: no last transaction ID was provided.");

		if( ! $month )
			return $this->_return_error("An error occurred: no month was provided.");

		$page_size = 250;
		$page = 0;

		$this->_return_object->data->transactions = array();

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
			'id' => $account_id,
		)));
		$account_lookup_result = $account_lookup->execute();
		
		$account_transactions_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'account_id' => $account_id,
			'sort_by' => 'newest',
			'before_transaction_id' => $last_transaction_id,
			'date_after' => date("Y-m-d",strtotime($month."-01 -1 Day")),
			'page_size' => $page_size,
			'page' => $page,
		)));
		$account_transactions_search_result = $account_transactions_search->execute();

		if( ! $account_transactions_search_result->success )
			return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($account_transactions_search_result));

		foreach( $account_transactions_search_result->data->transactions as $transaction )
		{
			$html = new View_Partials_Accounts_View_Transaction;
			$html->account_lookup_result = $account_lookup_result;
			$html->transaction = $transaction;
			$html->account_id = $account_id;

			$transaction->html = $html->render();

			$this->_return_object->data->transactions[] = $transaction;
		}
	}

	public function action_transactionsjumptomonthOLD()
	{
		$account_id = $this->request->post('account_id');
		$last_transaction_id = $this->request->post('last_transaction_id');
		$last_transaction_date = $this->request->post('last_transaction_date');
		$month = $this->request->post('month');

		if( ! $account_id )
			return $this->_return_error("An error occurred: no account ID was provided.");

		if( ! $last_transaction_id )
			return $this->_return_error("An error occurred: no last transaction ID was provided.");

		if( ! $month )
			return $this->_return_error("An error occurred: no month was provided.");

		$page_size = 50;
		$page = 0;

		$this->_return_object->data->transactions = array();

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
			'id' => $account_id,
		)));
		$account_lookup_result = $account_lookup->execute();
		do
		{
			$account_transactions_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
				'account_id' => $account_id,
				'sort_by' => 'newest',
				'before_transaction_id' => $last_transaction_id,
				'date_after' => date("Y-m-d",strtotime($month."-01 -1 Day")),
				'page_size' => $page_size,
				'page' => $page,
			)));
			$account_transactions_search_result = $account_transactions_search->execute();

			if( ! $account_transactions_search_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($account_transactions_search_result));

			foreach( $account_transactions_search_result->data->transactions as $transaction )
			{
				$html = new View_Partials_Accounts_View_Transaction;
				$html->account_lookup_result = $account_lookup_result;
				$html->transaction = $transaction;
				$html->account_id = $account_id;

				$transaction->html = $html->render();

				$this->_return_object->data->transactions[] = $transaction;
			}

			$page++;
		}
		while( $page < $account_transactions_search_result->data->pages );

	}

	public function action_transactionsloadmore()
	{
		$account_id = $this->request->post('account_id');
		$last_transaction_id = $this->request->post('last_transaction_id');
		$last_transaction_date = $this->request->post('last_transaction_date');
		$count = $this->request->post('count');
		
		if( ! $account_id )
			return $this->_return_error("An error occurred: no account ID was provided.");

		if( ! $last_transaction_id )
			return $this->_return_error("An error occurred: no last transaction ID was provided.");

		if( ! $count )
			$count = 50;

		$this->_return_object->data->transactions = array();

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
			'id' => $account_id,
		)));
		$account_lookup_result = $account_lookup->execute();
		
		$account_transactions_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'account_id' => $account_id,
			'sort_by' => 'newest',
			'before_transaction_id' => $last_transaction_id,
			'page_size' => $count,
		)));
		$account_transactions_search_result = $account_transactions_search->execute();

		if( ! $account_transactions_search_result->success )
			return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($account_transactions_search_result));


		foreach( $account_transactions_search_result->data->transactions as $transaction )
		{
			$html = new View_Partials_Accounts_View_Transaction;
			$html->account_lookup_result = $account_lookup_result;
			$html->transaction = $transaction;
			$html->account_id = $account_id;

			$transaction->html = $html->render();

			$this->_return_object->data->transactions[] = $transaction;
		}

	}

	public function action_importvalidatetransactions()
	{
		$account_id = $this->request->post('account_id');
		$account_table_sign = $this->request->post('account_table_sign');

		$importdata = $this->request->post('importdata');

		if( ! $importdata ) 
			return $this->_return_error("Missing required import data.");

		$importobject = json_decode($importdata);
		$importarray = array();

		foreach( $importobject as $key => $value )
			$importarray[$key] = $value;

		if( ! $account_id OR 
			! $account_table_sign )
			return $this->_return_error("Missing required values account ID and table sign.");

		$transaction_keys = array();
		foreach( $importarray as $key => $value )
			if( $value == "TRANSACTIONKEY" ) 
				$transaction_keys[] = $key;

		foreach( $transaction_keys as $hash )
		{
			if( Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer') != "duplicate" AND 
				Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer') != "ignore" )
			{
				$validate_transaction = new stdClass;
				$validate_transaction->validate_only = TRUE;

				$validate_transaction->date = ( Arr::get($importarray,'import-transaction-'.$hash.'-date') )
									   ? date("Y-m-d",strtotime(Arr::get($importarray,'import-transaction-'.$hash.'-date')))
									   : NULL; // THIS WILL NOT ALLOW TRANSACTION LINES WITHOUT A DATE date("Y-m-d");
				$validate_transaction->code = Arr::get($importarray,'import-transaction-'.$hash.'-number');
				$validate_transaction->description = Arr::get($importarray,'import-transaction-'.$hash.'-description');
				$validate_transaction->account_transactions = array();

				$validate_transaction->account_transactions[] = (object)array(
					'account_id' => $account_id,
					'amount' => ( Arr::get($importarray,'import-transaction-'.$hash.'-amount') * $account_table_sign ),
				);

				if( Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer') )
				{
					$validate_transaction->account_transactions[] = (object)array(
						'account_id' => Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer'),
						'amount' => ( $validate_transaction->account_transactions[0]->amount * -1 )
					);
				}
				else
				{
					$split_keys = array();
					foreach( $importarray as $key => $value )
						if( $value == 'import-transaction-'.$hash.'-split-key' )
							$split_keys[] = str_replace('split-key-','',$key);
					
					foreach( $split_keys as $split_key )
					{
						if( Arr::get($importarray,'import-transaction-'.$hash.'-split-transaction-transfer-'.$split_key) )
							$validate_transaction->account_transactions[] = (object)array(
								'account_id' => Arr::get($importarray,'import-transaction-'.$hash.'-split-transaction-transfer-'.$split_key),
								'amount' => ( Arr::get($importarray,'import-transaction-'.$hash.'-split-credit-'.$split_key) )
										 ? ( Arr::get($importarray,'import-transaction-'.$hash.'-split-credit-'.$split_key) * $account_table_sign )
										 : ( Arr::get($importarray,'import-transaction-'.$hash.'-split-debit-'.$split_key) * -1 * $account_table_sign ),
							);
					}
				}
				
				$account_transaction_create = new Beans_Account_Transaction_Create($this->_beans_data_auth($validate_transaction));
				$account_transaction_create_result = $account_transaction_create->execute();

				if( ! $account_transaction_create_result->success )
				{
					$this->_return_object->data->split_keys = ( isset($split_keys) ? $split_keys : array());
					$this->_return_object->data->validate_transaction_failure = $validate_transaction;
					return $this->_return_error("There is an error with one of your transactions:".$validate_transaction->date.': '.$validate_transaction->description.' ($'.Arr::get($importarray,'import-transaction-'.$hash.'-amount').')');
				}
					
			}
		}

	}


	public function action_transactioncreate()
	{
		$split_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( strpos($key, 'transaction-split-transfer') !== FALSE AND
				$value )
				$split_keys[] = str_replace('transaction-split-transfer-','',$key);

		$new_transaction = new stdClass;
		$new_transaction->date = ( $this->request->post('transaction-date') )
							   ? date("Y-m-d",strtotime($this->request->post('transaction-date')))
							   : date("Y-m-d");
		$new_transaction->code = $this->request->post('transaction-number');
		$new_transaction->description = $this->request->post('transaction-description');
		$new_transaction->account_transactions = array();

		$new_transaction->account_transactions[] = (object)array(
			'account_id' => $this->request->post('transaction-account-id'),
			'amount' => ( $this->request->post('transaction-credit') )
					 ? ( $this->request->post('transaction-credit') * $this->request->post('transaction-account-table_sign') )
					 : ( $this->request->post('transaction-debit') * -1 * $this->request->post('transaction-account-table_sign') ),
		);

		if( $this->request->post('transaction-transfer') )
		{
			$new_transaction->account_transactions[] = (object)array(
				'account_id' => $this->request->post('transaction-transfer'),
				'amount' => ( $new_transaction->account_transactions[0]->amount * -1 )
			);
		}
		else
		{
			// Loop all split transactions and go for it.
			foreach( $split_keys as $split_key )
			{
				if( $this->request->post('transaction-split-transfer-'.$split_key) AND
					(
						$this->request->post('transaction-split-credit-'.$split_key) OR
						$this->request->post('transaction-split-debit-'.$split_key)
					) )
					$new_transaction->account_transactions[] = (object)array(
						'account_id' => $this->request->post('transaction-split-transfer-'.$split_key),
						'amount' => ( $this->request->post('transaction-split-credit-'.$split_key) )
								 ? ( $this->request->post('transaction-split-credit-'.$split_key) * $this->request->post('transaction-account-table_sign') )
								 : ( $this->request->post('transaction-split-debit-'.$split_key) * -1 * $this->request->post('transaction-account-table_sign') ),
					);
			}
		}

		if( count($new_transaction->account_transactions) == 1 )
			return $this->_return_error("Please either select a transfer account or create a split.");

		$account_transaction_create = new Beans_Account_Transaction_Create($this->_beans_data_auth($new_transaction));
		$account_transaction_create_result = $account_transaction_create->execute();

		if( ! $account_transaction_create_result->success )
			return $this->_return_error("An error occurred:<br>".$this->_beans_result_get_error($account_transaction_create_result));

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
			'id' => $this->request->post('transaction-account-id'),
		)));
		$account_lookup_result = $account_lookup->execute();

		$html = new View_Partials_Accounts_View_Transaction;
		$html->account_lookup_result = $account_lookup_result;
		$html->transaction = $account_transaction_create_result->data->transaction;
		$html->account_id = $this->request->post('transaction-account-id');

		
		$this->_return_object->data->transaction = $account_transaction_create_result->data->transaction;
		$this->_return_object->data->transaction->html = $html->render();

	}

	public function action_transactionupdate()
	{
		$transaction_id = $this->request->post("transaction-id");

		$split_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( strpos($key, 'transaction-split-transfer') !== FALSE AND
				$value )
				$split_keys[] = str_replace('transaction-split-transfer-','',$key);

		$new_transaction = new stdClass;
		$new_transaction->date = ( $this->request->post('transaction-date') )
							   ? date("Y-m-d",strtotime($this->request->post('transaction-date')))
							   : date("Y-m-d");
		$new_transaction->code = $this->request->post('transaction-number');
		$new_transaction->description = $this->request->post('transaction-description');
		$new_transaction->account_transactions = array();

		$new_transaction->account_transactions[] = (object)array(
			'account_id' => $this->request->post('transaction-account-id'),
			'amount' => ( $this->request->post('transaction-credit') )
					 ? ( $this->request->post('transaction-credit') * $this->request->post('transaction-account-table_sign') )
					 : ( $this->request->post('transaction-debit') * -1 * $this->request->post('transaction-account-table_sign') ),
		);

		if( $this->request->post('transaction-transfer') )
		{
			$new_transaction->account_transactions[] = (object)array(
				'account_id' => $this->request->post('transaction-transfer'),
				'amount' => ( $new_transaction->account_transactions[0]->amount * -1 )
			);
		}
		else
		{
			// Loop all split transactions and go for it.
			foreach( $split_keys as $split_key )
			{
				if( $this->request->post('transaction-split-transfer-'.$split_key) AND
					(
						$this->request->post('transaction-split-credit-'.$split_key) OR
						$this->request->post('transaction-split-debit-'.$split_key)
					) )
					$new_transaction->account_transactions[] = (object)array(
						'account_id' => $this->request->post('transaction-split-transfer-'.$split_key),
						'amount' => ( $this->request->post('transaction-split-credit-'.$split_key) )
								 ? ( $this->request->post('transaction-split-credit-'.$split_key) * $this->request->post('transaction-account-table_sign') )
								 : ( $this->request->post('transaction-split-debit-'.$split_key) * -1 * $this->request->post('transaction-account-table_sign') ),
					);
			}
		}

		if( count($new_transaction->account_transactions) == 1 )
			return $this->_return_error("Please either select a transfer account or create a split.");

		$new_transaction->id = $transaction_id;
		
		$account_transaction_update = new Beans_Account_Transaction_Update($this->_beans_data_auth($new_transaction));
		$account_transaction_update_result = $account_transaction_update->execute();

		if( ! $account_transaction_update_result->success )
			return $this->_return_error($this->_beans_result_get_error($account_transaction_update_result));

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
			'id' => $this->request->post('transaction-account-id'),
		)));
		$account_lookup_result = $account_lookup->execute();

		$html = new View_Partials_Accounts_View_Transaction;
		$html->account_lookup_result = $account_lookup_result;
		$html->transaction = $account_transaction_update_result->data->transaction;
		$html->account_id = $this->request->post('transaction-account-id');

		$this->_return_object->data->transaction = $account_transaction_update_result->data->transaction;
		$this->_return_object->data->transaction->html = $html->render();
	}

	public function action_transactiondelete()
	{
		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->request->post('transaction_id'),
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			return $this->_return_error("An error occurred when deleting that transaction: ".$this->_beans_result_get_error($account_transaction_delete_result));

		return;
	}

	public function action_reconcilevalidate()
	{
		$account_reconcile_create_data = new stdClass;
		$account_reconcile_create_data->validate_only = TRUE;
		$account_reconcile_create_data->account_id = $this->request->post('account_id');
		$account_reconcile_create_data->date = date("Y-m-d",strtotime($this->request->post('date')));
		$account_reconcile_create_data->balance_start = preg_replace("/([^0-9\\.\\-])/i", "", $this->request->post('balance_start') );
		$account_reconcile_create_data->balance_end = preg_replace("/([^0-9\\.\\-])/i", "", $this->request->post('balance_end') );
		$account_reconcile_create_data->account_transaction_ids = array();

		foreach( $this->request->post() as $key => $value ) 
			if( strpos($key, 'include-transaction-') !== FALSE )
				$account_reconcile_create_data->account_transaction_ids[] = str_replace('include-transaction-', '', $key);

		$account_reconcile_create = new Beans_Account_Reconcile_Create($this->_beans_data_auth($account_reconcile_create_data));
		$account_reconcile_create_result = $account_reconcile_create->execute();

		if( ! $account_reconcile_create_result->success )
			return $this->_return_error("An error occurred when recording that statement:<br>".$this->_beans_result_get_error($account_reconcile_create_result));

		return;
		
		$this->_return_object->data->account_reconcile = $account_reconcile_create_result->data->account_reconcile;
	}

	public function action_startingbalancevalidate()
	{
		$create_transaction_data = new stdClass;
		$create_transaction_data->validate_only = TRUE;
		$create_transaction_data->account_transactions = array();

		$create_transaction_data->date = date("Y-m-d");
		if( $this->request->post('date') AND 
			strlen($this->request->post('date')) )
			$create_transaction_data->date = date("Y-m-d",strtotime($this->request->post('date')));

		foreach( $this->request->post() as $key => $value ) 
		{
			if( strpos($key, 'account_debit_') !== FALSE AND
				strlen($value) AND 
				floatval($value) != 0 ) 
			{
				$create_transaction_data->account_transactions[] = (object)array(
					'account_id' => str_replace('account_debit_', '', $key),
					'amount' => ( -1 * $value ),
				);
			}
			else if( 	strpos($key, 'account_credit_') !== FALSE AND
						strlen($value) AND 
						floatval($value) != 0 )
			{
				$create_transaction_data->account_transactions[] = (object)array(
					'account_id' => str_replace('account_credit_', '', $key),
					'amount' => ( $value ),
				);
			}
		}

		$create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction_data));
		$create_transaction_result = $create_transaction->execute();

		if( ! $create_transaction_result->success )
			return $this->_return_error($this->_beans_result_get_error($create_transaction_result));

	}

}