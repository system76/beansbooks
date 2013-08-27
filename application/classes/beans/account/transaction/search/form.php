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
class Beans_Account_Transaction_Search_Form extends Beans_Account_Transaction_Search {

	protected $_form_id;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_form_id = ( isset($data->form_id) )
						? $data->form_id
						: FALSE;

		// Re-declare $this->_transactions to use proper joins for a form search.
		$this->_transactions = ORM::Factory('transaction')->DISTINCT(TRUE)->
			join('account_transactions','RIGHT')->on('account_transactions.transaction_id','=','transaction.id')->
			join('account_transaction_forms','RIGHT')->on('account_transaction_forms.account_transaction_id','=','account_transactions.id');
	}

	protected function _execute()
	{
		// Internal Only Right Now
		// Consider making public.
		if( ! $this->_beans_internal_call() )
			throw new Exception("Restricted to internal calls.");

		if( ! $this->_form_id )
			throw new Exception("Invalid form ID: none provided.");

		$this->_transactions = $this->_transactions->
			where('account_transaction_forms.form_id','=',$this->_form_id);

		$result_object = $this->_find_transactions();

		return (object)array(
			"total_results" => $result_object->total_results,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"transactions" => $this->_return_transactions_array($result_object->transactions),
		);
	}
}