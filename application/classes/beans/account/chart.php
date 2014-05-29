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
@action Beans_Account_Chart
@description Returns a nested chart of accounts.
@required auth_uid
@required auth_key
@required auth_expiration
@returns accounts An array of #Beans_Account# - each entry can have a nested child of accounts.
---BEANSENDSPEC---
*/
class Beans_Account_Chart extends Beans_Account {

	protected $_accounts;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_accounts = ORM::Factory('account');
	}

	protected function _execute()
	{
		$this->_accounts = $this->_accounts->where('parent_account_id','IS',NULL)->find_all();

		$accounts = array();

		foreach( $this->_accounts as $account )
			$accounts[] = $this->_discover_account_children($account);

		foreach( $accounts as $account_id => $account ) {
			$accounts[$account_id]->balance = $this->_total_account_children($account);
		}
		
		return (object)array(
			"accounts" => $accounts,
		);
	}

	private function _discover_account_children($account)
	{
		$return_object = $this->_return_account_element($account);

		if( $account->child_accounts->count_all() )
		{
			$return_object->accounts = array();
			foreach( $account->child_accounts->find_all() as $child_account )
				$return_object->accounts[] = $this->_discover_account_children($child_account);

			usort($return_object->accounts,array($this,'_sort_accounts_chart'));
		}

		return $return_object;
	}

	private function _total_account_children($account) {
		$total = $this->_beans_round($account->balance);
		if( isset($account->accounts) AND 
			count($account->accounts) )
			foreach( $account->accounts as $account ) 
				$total = $this->_beans_round( $total + $this->_total_account_children($account) );
		
		return $total;
	}

	private function _sort_accounts_chart($a,$b)
	{
		if( $a->reserved == $b->reserved )
		{
			if( $a->name == $b->name )
				return 0;

			return ( strcmp($a->name,$b->name) < 0 ? -1 : 1 );
		}

		return ( $a->reserved < $b->reserved ? -1 : 1 );
	}

}