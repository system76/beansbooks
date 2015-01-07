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
@action Beans_Account_Reconcile_Delete
@description Delete an account reconciliation.  This only allows deleting the most recent reconciliation for a specific account.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Account_Reconcile# to lookup.
@returns account_reconcile The resulting #Beans_Account_Reconcile#.
---BEANSENDSPEC---
*/
class Beans_Account_Reconcile_Delete extends Beans_Account_Reconcile {

	protected $_id;
	protected $_account_reconcile;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_account_reconcile = $this->_load_account_reconcile($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_account_reconcile->loaded() )
			throw new Exception("Account reconciliation could not be found.");

		$latest_reconciliation = ORM::Factory('account_reconcile')
			->where('account_id','=',$this->_account_reconcile->account_id)
			->order_by('date','desc')
			->find();

		if( $latest_reconciliation->id != $this->_account_reconcile->id )
			throw new Exception("Can only delete the most recent reconciliation for an account.");

		DB::Query(
			NULL,
			' UPDATE '.
			' account_transactions '.
			' SET '.
			' account_reconcile_id = NULL '.
			' WHERE '.
			' account_reconcile_id = '.$this->_account_reconcile->id.' '
		)->execute();

		$this->_account_reconcile->delete();

		return (object)array();
	}
}