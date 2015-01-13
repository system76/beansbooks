<?php defined('SYSPATH') or die('No direct access allowed.');
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

class Controller_Print extends Controller {
	protected $_setup_company_list_result;

	public function before()
	{
		parent::before();
		// Make sure we've queried the default data.
		$this->_beans_default_calls();
	}

	// Customer Sale
	public function action_customersale()
	{
		$sale_id = $this->request->param('id');

		$customer_sale_lookup = new Beans_Customer_Sale_Lookup($this->_beans_data_auth((object)array(
			'id' => $sale_id,
		)));
		$customer_sale_lookup_result = $customer_sale_lookup->execute();

		if( ! $customer_sale_lookup_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$customer_sale_lookup_result->error);
		}

		$customers_print_sale = new View_Customers_Print_Sale();
		$customers_print_sale->sale = $customer_sale_lookup_result->data->sale;
		$customers_print_sale->setup_company_list_result = $this->_setup_company_list_result;

		die($customers_print_sale->render());
	}

	// Customer Payment
	public function action_customerpayment()
	{
		$payment_id = $this->request->param('id');

		$customer_payment_lookup = new Beans_Customer_Payment_Lookup($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$customer_payment_lookup_result = $customer_payment_lookup->execute();

		if( ! $customer_payment_lookup_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$customer_payment_lookup_result->error);
		}

		$customers_print_payment = new View_Customers_Print_Payment();
		$customers_print_payment->payment = $customer_payment_lookup_result->data->payment;
		$customers_print_payment->setup_company_list_result = $this->_setup_company_list_result;
		
		die($customers_print_payment->render());
	}

	// Vendor Expense
	public function action_vendorexpense()
	{
		$expense_id = $this->request->param('id');

		$vendor_expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
			'id' => $expense_id,
		)));
		$vendor_expense_lookup_result = $vendor_expense_lookup->execute();

		if( ! $vendor_expense_lookup_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$vendor_expense_lookup_result->error);
		}

		$vendors_print_expense = new View_Vendors_Print_Expense();
		$vendors_print_expense->expense = $vendor_expense_lookup_result->data->expense;
		$vendors_print_expense->setup_company_list_result = $this->_setup_company_list_result;
		
		die($vendors_print_expense->render());
	}

	// Vendor Purchase
	public function action_vendorpurchase()
	{
		$purchase_id = $this->request->param('id');

		$vendor_purchase_lookup = new Beans_Vendor_Purchase_Lookup($this->_beans_data_auth((object)array(
			'id' => $purchase_id,
		)));
		$vendor_purchase_lookup_result = $vendor_purchase_lookup->execute();

		if( ! $vendor_purchase_lookup_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$vendor_purchase_lookup_result->error);
		}

		$vendors_print_purchase = new View_Vendors_Print_Purchase();
		$vendors_print_purchase->purchase = $vendor_purchase_lookup_result->data->purchase;
		$vendors_print_purchase->setup_company_list_result = $this->_setup_company_list_result;
		
		die($vendors_print_purchase->render());
	}

	// Vendor Payment
	public function action_vendorpayment()
	{
		$payment_id = $this->request->param('id');

		$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

		if( ! $vendor_payment_lookup_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$vendor_payment_lookup_result->auth_error.$vendor_payment_lookup_result->error);
		}

		$default_address = FALSE;

		if( $vendor_payment_lookup_result->data->payment->vendor->default_remit_address_id )
		{
			$vendor_address_lookup = new Beans_Vendor_Address_Lookup($this->_beans_data_auth((object)array(
				'id' => $vendor_payment_lookup_result->data->payment->vendor->default_remit_address_id,
			)));
			$vendor_address_lookup_result = $vendor_address_lookup->execute();
			$default_address = $vendor_address_lookup_result->data->address;
		}
		else
		{
			$vendor_address_search = new Beans_Vendor_Address_Search($this->_beans_data_auth((object)array(
				'search_vendor_id' => $vendor_payment_lookup_result->data->payment->vendor->id,
			)));
			$vendor_address_search_result = $vendor_address_search->execute();
			foreach( $vendor_address_search_result->data->addresses as $address )
				if( ! $default_address )
					$default_address = $address;
		}

		$vendors_print_payment = new View_Vendors_Print_Payment();
		$vendors_print_payment->vendor_address = $default_address;
		$vendors_print_payment->payment = $vendor_payment_lookup_result->data->payment;
		$vendors_print_payment->setup_company_list_result = $this->_setup_company_list_result;
		
		die($vendors_print_payment->render());
	}

	public function action_taxpayment()
	{
		$taxpayment_id = $this->request->param('id');

		$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
			'id' => $taxpayment_id,
		)));
		$tax_payment_lookup_result = $tax_payment_lookup->execute();

		if( ! $tax_payment_lookup_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$tax_payment_lookup_result->auth_error.$tax_payment_lookup_result->error);
		}

		$vendors_print_taxpayment = new View_Vendors_Print_Taxpayment();
		$vendors_print_taxpayment->setup_company_list_result = $this->_setup_company_list_result;
		$vendors_print_taxpayment->payment = $tax_payment_lookup_result->data->payment;

		die($vendors_print_taxpayment->render());
	}

	public function action_taxprep()
	{
		$tax_id = $this->request->param('id');
		// 2014-01-01_2014-01-30
		$date_range = $this->request->param('code');
		$date_start = substr($date_range,0,10);
		$date_end = substr($date_range,11);

		$tax_prep = new Beans_Tax_Prep($this->_beans_data_auth((object)array(
			'id' => $tax_id,
			'date_start' => $date_start,
			'date_end' => $date_end,
		)));
		$tax_prep_result = $tax_prep->execute();

		if( ! $tax_prep_result->success )
		{
			// V2Item - Clean up and output nicely.
			die("An error occurred: ".$tax_prep_result->auth_error.$tax_prep_result->error);
		}

		$vendors_print_taxpayment = new View_Vendors_Print_Taxpayment();
		$vendors_print_taxpayment->setup_company_list_result = $this->_setup_company_list_result;
		$vendors_print_taxpayment->payment = (object)array(
			'id' => NULL,
			'tax' => $tax_prep_result->data->tax,
			'amount' => NULL,
			'writeoff_amount' => NULL,
			'date' => NULL,
			'date_start' => $tax_prep_result->data->date_start,
			'date_end' => $tax_prep_result->data->date_end,
			'check_number' => NULL,
			'invoiced_line_amount' => $tax_prep_result->data->taxes->due->invoiced->form_line_amount,
			'invoiced_line_taxable_amount' => $tax_prep_result->data->taxes->due->invoiced->form_line_taxable_amount,
			'invoiced_amount' => $tax_prep_result->data->taxes->due->invoiced->amount,
			'refunded_line_amount' => $tax_prep_result->data->taxes->due->refunded->form_line_amount,
			'refunded_line_taxable_amount' => $tax_prep_result->data->taxes->due->refunded->form_line_taxable_amount,
			'refunded_amount' => $tax_prep_result->data->taxes->due->refunded->amount,
			'net_line_amount' => $tax_prep_result->data->taxes->due->net->form_line_amount,
			'net_line_taxable_amount' => $tax_prep_result->data->taxes->due->net->form_line_taxable_amount,
			'net_amount' => $tax_prep_result->data->taxes->due->net->amount,
			'liabilities' => array_merge(
				$tax_prep_result->data->taxes->due->invoiced->liabilities,
				$tax_prep_result->data->taxes->due->refunded->liabilities
			),
			'amount' => NULL,
			'writeoff_amount' => NULL,
		);

		die($vendors_print_taxpayment->render());
	}

	/**
	 * Adds a few Beans_ calls to each view ( such as company settings ).
	 */
	protected function _beans_default_calls()
	{
		// Only if logged in.
		if( ! strlen(Session::instance()->get('auth_uid')) OR 
			! strlen(Session::instance()->get('auth_key')) OR 
			! strlen(Session::instance()->get('auth_expiration')) )
			return FALSE;
		
		$setup_company_list = new Beans_Setup_Company_List($this->_beans_data_auth());
		$this->_setup_company_list_result = $setup_company_list->execute();
	}

}