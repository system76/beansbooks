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

		$form_ids_query = ' SELECT DISTINCT(account_transaction_forms.form_id) as form_id FROM account_transaction_forms '.
						  ' INNER JOIN account_transactions ON account_transaction_forms.account_transaction_id = account_transactions.id WHERE '.
						  ' account_transactions.transaction_id = '.$this->_payment->id;
		$form_ids = DB::Query(Database::SELECT, $form_ids_query)->execute()->as_array();

		$handled_sales_ids = array();
		foreach( $form_ids as $form_id )
			$handled_sales_ids[] = $form_id['form_id'];

		$payment_date = $this->_payment->date;
		$payment_id = $this->_payment->id;

		// Try to cancel payment.
		$account_transaction_delete = new Beans_Account_Transaction_Delete($this->_beans_data_auth((object)array(
			'id' => $this->_payment->id,
			'payment_type_handled' => 'customer',
		)));
		$account_transaction_delete_result = $account_transaction_delete->execute();

		if( ! $account_transaction_delete_result->success )
			throw new Exception("Error cancelling payment: ".$account_transaction_delete_result->error);

		// Recalibrate Customer Invoices / Cancellations
		$customer_sale_calibrate_invoice = new Beans_Customer_Sale_Calibrate_Invoice($this->_beans_data_auth((object)array(
			'ids' => $handled_sales_ids,
		)));
		$customer_sale_calibrate_invoice_result = $customer_sale_calibrate_invoice->execute();

		if( ! $customer_sale_calibrate_invoice_result->success )
			throw new Exception("UNEXPECTED ERROR: COULD NOT CALIBRATE CUSTOMER SALES: ".$customer_sale_calibrate_invoice_result->error);

		// Recalibrate Customer Invoices / Cancellations
		$customer_sale_calibrate_cancel = new Beans_Customer_Sale_Calibrate_Cancel($this->_beans_data_auth((object)array(
			'ids' => $handled_sales_ids,
		)));
		$customer_sale_calibrate_cancel_result = $customer_sale_calibrate_cancel->execute();

		if( ! $customer_sale_calibrate_cancel_result->success )
			throw new Exception("UNEXPECTED ERROR: COULD NOT CALIBRATE CUSTOMER SALES: ".$customer_sale_calibrate_cancel_result->error);

		// Recalibrate any payments tied to these sales AFTER this transaction date.
		$customer_payment_calibrate = new Beans_Customer_Payment_Calibrate($this->_beans_data_auth((object)array(
			'form_ids' => $handled_sales_ids,
			// TODO - ADD A MEANS TO SEND AFTER PAYMENT ID AND AFTER PAYMENT DATE
		)));
		$customer_payment_calibrate_result = $customer_payment_calibrate->execute();

		return (object)array(
			"success" => TRUE,
			"auth_error" => "",
			"error" => "",
			"data" => (object)array(),
		);
	}
}