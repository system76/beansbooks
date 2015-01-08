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
@action Beans_Customer_Sale_Delete
@description Delete a customer sale.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Customer_Sale# to cancel.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Delete extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_id;
	protected $_sale;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_sale = $this->_load_customer_sale($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("Sale could not be found.");

		if( $this->_sale->date_cancelled || 
			$this->_sale->cancel_transaction_id )
			throw new Exception("Sale could not be deleted - it has already been cancelled.");

		// There's a unique use-case that's hard to replicate, but it produces a form that
		// has no create_transaction - closing the FYE with this form can be frustrating to deal with otherwise.
		if( $this->_check_books_closed($this->_sale->date_created) &&
			$this->_sale->create_transaction_id &&
			$this->_sale->invoice_transaction_id &&
			$this->_sale->cancel_transaction_id )
			throw new Exception("Sale could not be deleted.  The financial year has been closed already.");

		if( $this->_sale->refund_form_id AND 
			$this->_sale->refund_form_id > $this->_sale->id )
			throw new Exception("Sale could not be deleted - it has a refund attached to it.");

		if( $this->_sale->date_billed ||
			$this->_sale->invoice_transaction_id )
			throw new Exception("A sale cannot be deleted after it has been converted to an invoice.");

		$transfer_transactions = FALSE;
		foreach( $this->_sale->account_transaction_forms->find_all() as $account_transaction_form )
			if( $account_transaction_form->account_transaction->transaction->payment ) 
				$transfer_transactions = TRUE;

		if( $transfer_transactions )
			throw new Exception("A sale cannot be deleted after it has had a payment recorded to it.  You can, however, cancel it.");

		// Delete the Create Transaction.
		if( $this->_sale->create_transaction->loaded() )
		{
			$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_sale->create_transaction_id,
				'form_type_handled' => 'sale',
			)));
			$account_transaction_delete_result = $account_transaction_delete->execute();

			if( ! $account_transaction_delete_result->success )
				throw new Exception("Error deleting account transaction: ".$account_transaction_delete_result->error);
		}

		foreach( $this->_sale->form_lines->find_all() as $sale_line )
			$sale_line->delete();

		foreach( $this->_sale->form_taxes->find_all() as $sale_tax )
			$sale_tax->delete();
		
		// If this is a refund for another SO, then we make sure to remove the reference to this form.
		if( $this->_sale->refund_form->loaded() )
		{
			$this->_sale->refund_form->refund_form_id = NULL;
			$this->_sale->refund_form->save();
		}

		$this->_sale->delete();
		
		return (object)array();
	}
}