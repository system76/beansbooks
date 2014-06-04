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
@action Beans_Account_Calibrate
@description Calibrate the balances of an account in the case of a fatal error.
@required auth_uid
@required auth_key
@required auth_expiration
@optional id The id of the #Beans_Account# to calibrate.
@returns calibrated_account_ids ARRAY Accounts that had journal-entry balance corrections.
@returns calibrated_form_ids ARRAY Forms that were calibrated by removing extraneous transactions.
---BEANSENDSPEC---
*/
class Beans_Account_Calibrate extends Beans_Account {
	
	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}

	protected function _execute()
	{
		$calibrated_form_ids = $this->_calibrate_forms();
		
		$calibrated_account_ids = array();

		$accounts = ORM::Factory('account');

		if( isset($this->_data->id) )
			$accounts = $accounts->where('id','=',$this->_data->id);

		$accounts = $accounts->find_all();

		if( isset($this->_data->id) &&
			! count($accounts) )
			throw new Exception("Account could not be found.");

		foreach( $accounts as $account )
		{
			if( $this->_calibrate_account($account) != 0.00 )
				$calibrated_account_ids[] = $account->id;
		}

		return (object)array(
			'calibrated_account_ids' => $calibrated_account_ids,
			'calibrated_form_ids' => $calibrated_form_ids,
		);
	}

	private function _calibrate_account($account)
	{
		$previous_balance = 0.00;
		$calibrated_balance = 0.00;
		$calibrated_balance_shift = 0.00;

		// Look up all transactions in order.
		// TODO - Can probably add a check to be newer than the most recent close_books.
		$account_transactions = DB::Query(Database::SELECT,'SELECT id,date,transaction_id,amount,balance FROM account_transactions WHERE account_id = "'.$account->id.'" ORDER BY date ASC, close_books DESC, transaction_id ASC')->execute()->as_array();

		foreach( $account_transactions as $account_transaction )
		{
			$calibrated_balance = $account_transaction['balance'];

			// V2Item
			// There should be a faster way to do this that involves tracking changes in calibrated_balance and then applying that amount going forward.
			// Update if necessary.
			if( round(( $account_transaction['amount'] + $previous_balance ),2) != $calibrated_balance )
			{
				$calibrated_balance = round(( $account_transaction['amount'] + $previous_balance ),2);
				$calibrated_balance_shift = round( $calibrated_balance_shift + abs( $account_transaction['balance'] - $calibrated_balance ), 2);
				DB::Query(Database::UPDATE,'UPDATE account_transactions SET balance = '.$calibrated_balance.' WHERE id = "'.$account_transaction['id'].'"')->execute();
			}

			$previous_balance = $calibrated_balance;
		}

		$this->_account_balance_calibrate($account->id);

		return $calibrated_balance_shift;
	}

	// Remove any extra transactions hanging on to forms.
	// This could happen if a request is sent twice ... somehow... while being... updated?
	private function _calibrate_forms()
	{
		$calibrated_form_ids = array();

		$calibrate_transaction_ids_query = 
			'SELECT forms_transactions.* FROM (  '.
			'    SELECT transactions.id as transaction_id,  '.
			'        forms.id as form_id, '.
			'        forms.type as form_type, '.
			'        forms.create_transaction_id as create_transaction_id, '.
			'        forms.invoice_transaction_id as invoice_transaction_id, '.
			'        forms.cancel_transaction_id as cancel_transaction_id '.
			'    FROM  '.
			'    transactions INNER JOIN forms '.
			'    ON transactions.form_id = forms.id  '.
			') forms_transactions  '.
			'WHERE '.
			'NOT(forms_transactions.transaction_id <=> forms_transactions.create_transaction_id) AND '.
			'NOT(forms_transactions.transaction_id <=> forms_transactions.invoice_transaction_id) AND '.
			'NOT(forms_transactions.transaction_id <=> forms_transactions.cancel_transaction_id) ';

		$calibrate_transaction_ids = DB::Query(Database::SELECT, $calibrate_transaction_ids_query)->execute()->as_array();

		if( $calibrate_transaction_ids &&
			count($calibrate_transaction_ids) )
		{
			foreach( $calibrate_transaction_ids as $calibrate_transaction_id )
			{
				// Delete any transactions returned.
				$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
					'id' => $calibrate_transaction_id['transaction_id'],
					'form_type_handled' => $calibrate_transaction_id['form_type'],
				)));
				$account_transaction_delete_result = $account_transaction_delete->execute();

				if( ! $account_transaction_delete_result->success )
					throw new Exception("Unexpected error! Could not delete extra form transaction: ".$account_transaction_delete_result->error);

				if( ! in_array($calibrate_transaction_id['form_id'], $calibrated_form_ids))
					$calibrated_form_ids[] = $calibrate_transaction_id['form_id'];
			}
		}

		return $calibrated_form_ids;
	}
}