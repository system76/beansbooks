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
@action Beans_Customer_Payment_Cancel
@description Cancel a payment by deleting its transaction.
@required auth_uid
@required auth_key
@required auth_expiration
@required id The ID of the #Beans_Customer_Payment# to cancel.
---BEANSENDSPEC---
*/
class Beans_Customer_Payment_Cancel extends Beans_Customer_Payment {

	protected $_auth_role_perm = "customer_payment_write";

	protected $_id;
	protected $_payment;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_payment = $this->_load_customer_payment($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_payment->loaded() )
			throw new Exception("Payment could not be found.");

		// Array of IDs for sales to have their invoices updated.
		$sales_invoice_update = array();

		foreach( $this->_payment->account_transactions->find_all() as $account_transaction )
		{
			if( ! $account_transaction->writeoff )
			{
				foreach( $account_transaction->account_transaction_forms->find_all() as $account_transaction_form )
				{
					if( $account_transaction_form->form->account_id != $account_transaction->account_id AND 
						( 
							$account_transaction_form->form->date_billed AND 
							strtotime($account_transaction_form->form->date_billed) >= strtotime($this->_payment->date)
						) )
						$sales_invoice_update[] = $account_transaction_form->form_id;
				}
			}
		}

		// Try to cancel payment.
		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_payment->id,
			'payment_type_handled' => 'customer',
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("Error cancelling payment: ".$account_transaction_delete_result->error);

		// Update invoices if necessary
		foreach( $sales_invoice_update as $sale_id ) 
		{
			$customer_sale_invoice_update = new Beans_Customer_Sale_Invoice_Update($this->_beans_data_auth((object)array(
				'id' => $sale_id,
			)));
			$customer_sale_invoice_update_result = $customer_sale_invoice_update->execute();

			if( ! $customer_sale_invoice_update_result->success ) 
				throw new Exception("UNEXPECTED ERROR: Error updating customer sale invoice transaction. ".$customer_sale_invoice_update_result->error);
		}

		return (object)array(
			"success" => TRUE,
			"auth_error" => "",
			"error" => "",
			"data" => (object)array(),
		);
	}
}