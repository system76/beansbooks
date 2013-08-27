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
class Beans_Account_Reconcile extends Beans_Account {

	// All of the actions related to reconciling accounts have the same permission.
	protected $_auth_role_perm = "account_reconcile";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _return_account_reconciles_array($account_reconciles)
	{
		$return_array = array();

		foreach( $account_reconciles as $account_reconcile )
			$return_array[] = $this->_return_account_reconcile_element($account_reconcile);

		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Account_Reconcile
	@description A reconciliation against a single or set of bank statements.
	@attribute id INTEGER 
	@attribute account OBJECT The #Beans_Account# this is tied to.
	@attribute date STRING The statement date in YYYY-MM-DD format.
	@attribute balance_start DECIMAL The statement beginning balance.
	@attribute balance_end DECIMAL The statement ending balance.
	@attribute account_transaction_ids ARRAY An array of IDs linked to the included #Beans_Account_Transaction#.
	---BEANSENDSPEC---
	 */

	protected function _return_account_reconcile_element($account_reconcile)
	{
		$return_object = new stdClass;

		if( get_class($account_reconcile) != "Model_Account_Reconcile" )
			throw new Exception("Invalid account reconcile object.");

		$return_object->id = $account_reconcile->id;
		$return_object->account = $this->_return_account_element($account_reconcile->account);
		$return_object->date = $account_reconcile->date;
		$return_object->balance_start = (float)$account_reconcile->balance_start;
		$return_object->balance_end = (float)$account_reconcile->balance_end;
		
		// V2Item
		// Consider revamping if necessary to return entire transactions.
		$return_object->account_transaction_ids = $this->_return_account_transaction_ids_array($account_reconcile->account_transactions->find_all());

		return $return_object;
	}

	protected function _default_account_reconcile()
	{
		$account_reconcile = ORM::Factory('account_reconcile');

		$account_reconcile->account_id = NULL;
		$account_reconcile->date = NULL;
		$account_reconcile->balance_start = NULL;
		$account_reconcile->balance_end = NULL;

		return $account_reconcile;
	}

	protected function _load_account_reconcile($id)
	{
		return ORM::Factory('account_reconcile',$id);
	}
	
	protected function _validate_account_reconcile($account_reconcile)
	{
		if( get_class($account_reconcile) != "Model_Account_Reconcile" )
			throw new Exception("Invalid account reconcile object.");

		if( ! $account_reconcile->date )
			throw new Exception("Invalid account reconcile statement date: none provided.");

		if( $account_reconcile->date != date("Y-m-d",strtotime($account_reconcile->date)) )
			throw new Exception("Invalid account reconcile statement date: must be in YYYY-MM-DD format.");

		if( ! strlen($account_reconcile->balance_start) )
			throw new Exception("Invalid account reconcile starting balance: none provided.");

		if( ! strlen($account_reconcile->balance_end) )
			throw new Exception("Invalid account reconcile ending balance: none provided.");
	}

	protected function _return_account_transaction_ids_array($account_transactions)
	{
		$return_array = array();

		// V2Item
		// Consider converting to a SELECT DISTINCT(ID) query rather than calling this.
		foreach( $account_transactions as $account_transaction )
			$return_array[] = $account_transaction->id;

		return $return_array;
	}

}