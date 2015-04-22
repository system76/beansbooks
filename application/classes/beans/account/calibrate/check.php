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
@action Beans_Account_Calibrate_Check
@description Determine if account calibration is necessary.
@required auth_uid
@required auth_key
@required auth_expiration
@returns account_ids ARRAY A list of #Beans_Account# ids that need to be calibrated.
---BEANSENDSPEC---
*/
class Beans_Account_Calibrate_Check extends Beans_Account {

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}

	protected function _execute()
	{
		$account_ids = array();

		// In lieu of a better ( faster ) query, this toggles all accounts as needing calibration.
		if( $this->_check_calibrate_forms() )
		{
			$accounts = ORM::Factory('account')->find_all();

			foreach( $accounts as $account )
				$account_ids[] = $account->id;

			return (object)array(
				'account_ids' => $account_ids,
			);
		}

		foreach( ORM::Factory('account')->find_all() as $account )
		{
			$last_account_transaction_balance_query = 
				' SELECT '.
				' balance '.
				' FROM '.
				' account_transactions '.
				' WHERE '.
				' account_id = '.$account->id.' '.
				' ORDER BY '.
				' date DESC, close_books ASC, transaction_id DESC '.
				' LIMIT 1 ';

			$last_account_transaction_balance = DB::Query(
				Database::SELECT,
				$last_account_transaction_balance_query
			)->execute()->as_array();

			if( count($last_account_transaction_balance) &&
				$last_account_transaction_balance[0]['balance'] != $account->balance )
				$account_ids[] = $account->id;
		}

		return (object)array(
			'account_ids' => $account_ids,
		);
	}

	private function _check_calibrate_forms()
	{
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
			return TRUE;

		return FALSE;
	}
}
