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
@action Beans_Vendor_Purchase_Delete
@description Delete a vendor purchase.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Purchase# to remove.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Delete extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";

	protected $_id;
	protected $_purchase;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("Purchase purchase could not be found.");

		if( $this->_check_books_closed($this->_purchase->date_created) )
			throw new Exception("Purchase purchase could not be deleted.  The financial year has been closed already.");

		if( $this->_purchase->date_billed OR 
			$this->_purchase->invoice_transaction_id )
			throw new Exception("Purchase cannot be deleted after it has been converted to an invoice.");

		if( $this->_purchase->refund_form_id AND 
			$this->_purchase->refund_form_id > $this->_purchase->id )
			throw new Exception("Purchase could not be deleted - it has a refund attached to it.");
		
		// Determine if this purchase has a payment attached to it.
		// This is slow - but it's easy to read and understand.
		// A giant query wouldn't solve that much more probably.
		foreach( $this->_purchase->account_transaction_forms->find_all() as $account_transaction_form )
			if( $account_transaction_form->account_transaction->transaction->payment )
				throw new Exception("Purchase purchase could not be deleted. There are payments attached to this purchase. Are you trying to create a refund?");

		// Cancel Transaction.
		if( $this->_purchase->create_transaction_id )
		{
			$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
				'id' => $this->_purchase->create_transaction_id,
				'form_type_handled' => 'purchase',
			)));
			$account_transaction_delete_result = $account_transaction_delete->execute();

			if( ! $account_transaction_delete_result->success )
				throw new Exception("Error cancelling account transaction: ".$account_transaction_delete_result->error);

			$this->_purchase->create_transaction_id = NULL;
		}

		foreach( $this->_purchase->form_lines->find_all() as $purchase_line )
			$purchase_line->delete();
		
		// Remove the refund form from the corresponding form.
		if( $this->_purchase->refund_form->loaded() )
		{
			$this->_purchase->refund_form->refund_form_id = NULL;
			$this->_purchase->refund_form->save();
		}

		$this->_purchase->delete();
		
		return (object)array();
	}
}