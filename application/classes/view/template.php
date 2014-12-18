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


class View_Template extends Kostache_Layout {

	// The following are passed from Controller.php:
	// 		$this->request = Request instance.

	// Define ALL partials used in any view here.
	// Path should match name, replacing / with _ and ignoring partials directory.
	// i.e. partials/path/to/template.mustache would be defined as
	// 		'path_to_template' => 'partials/path/to/template'
	protected $_partials = array(
		'layout_header' => 'partials/layout/header',
		'accounts_chart_account' => 'partials/accounts/chart/account',
		'accounts_view_transaction' => 'partials/accounts/view/transaction',
		'accounts_view_transactionsplit' => 'partials/accounts/view/transactionsplit',
		'accounts_reconcile_transaction' => 'partials/accounts/reconcile/transaction',
		'customers_customer_address' => 'partials/customers/customer/address',
		'customers_sales_sale' => 'partials/customers/sales/sale',
		'customers_customer_customer' => 'partials/customers/customer/customer',
		'customers_payments_payment' => 'partials/customers/payments/payment',
		'customers_payments_batchpaymentform' => 'partials/customers/payments/batchpaymentform',
		'vendors_vendor_vendor' => 'partials/vendors/vendor/vendor',
		'vendors_vendor_address' => 'partials/vendors/vendor/address',
		'vendors_payments_payment' => 'partials/vendors/payments/payment',
		'vendors_expenses_expense' => 'partials/vendors/expenses/expense',
		'vendors_purchases_purchase' => 'partials/vendors/purchases/purchase',
		'vendors_payments_paymentpoform' => 'partials/vendors/payments/paymentpoform',
		'vendors_payments_paymentpo' => 'partials/vendors/payments/paymentpo',
		'vendors_checks_check' => 'partials/vendors/checks/check',
		'vendors_checks_newcheck' => 'partials/vendors/checks/newcheck',
		'taxes_payments_payment' => 'partials/taxes/payments/payment',
		'taxes_taxes_tax' => 'partials/taxes/taxes/tax',
		'dash_print_balance' => 'dash/print/balance',
		'dash_print_income' => 'dash/print/income',
		'dash_print_ledger' => 'dash/print/ledger',
		'dash_print_sales' => 'dash/print/sales',
		'dash_print_budget' => 'dash/print/budget',
		'dash_print_payables' => 'dash/print/payables',
		'dash_print_receivables' => 'dash/print/receivables',
		'dash_print_purchaseorders' => 'dash/print/purchaseorders',
		'dash_print_salesorders' => 'dash/print/salesorders',
		'dash_print_customer' => 'dash/print/customer',
		'dash_print_vendor' => 'dash/print/vendor',
		'dash_print_taxes' => 'dash/print/taxes',
		'dash_print_trial' => 'dash/print/trial',
		'dash_message' => 'partials/dash/message',
		'setup_users_user' => 'partials/setup/users/user',
		'setup_users_api' => 'partials/setup/users/api',
		'help_navigation' => 'partials/help/navigation',
	);
	
	// V2Item - Override this to use cache or something similar.
	// Cascade _company_currency() throughout view classes.
	protected function _company_currency()
	{
		$beans_settings = $this->beans_settings();

		if( ! $beans_settings ) 
			return '$';

		return $beans_settings->company_currency;
	}

	public function currency_symbol()
	{
		return $this->_company_currency();
	}
	
	// Cache Settings
	protected $_beans_settings = FALSE;
	protected function beans_settings()
	{
		if( ! isset($this->setup_company_list_result) )
			return FALSE;

		if( $this->_beans_settings )
			return $this->_beans_settings;

		$this->_beans_settings = $this->setup_company_list_result->data->settings;

		// Default
		if( ! isset($this->_beans_settings->company_currency) OR 
			! strlen($this->_beans_settings->company_currency) )
			$this->_beans_settings->company_currency = "$";
		
		return $this->_beans_settings;
	}

	public function logged_in()
	{
		return ( 	strlen(Session::instance()->get('auth_uid')) AND 
					strlen(Session::instance()->get('auth_key')) AND 
					strlen(Session::instance()->get('auth_expiration')) )
			? TRUE
			: FALSE;
	}

	public function logged_in_admin()
	{
		// V2Item - When adding roles, insert checks here for admin role.
		return $this->logged_in();
	}

	protected $_system_messages = FALSE;

	public function send_error_message($text) {
		return $this->_add_system_message('error',$text);
	}

	public function send_success_message($text) {
		return $this->_add_system_message('success',$text);
	}

	public function send_warning_message($text) {
		return $this->_add_system_message('warning',$text);
	}

	private function _add_system_message($type = NULL,$text = NULL) {
		if( ! $type OR 
			! $text )
			return FALSE;

		if( $this->_system_messages === FALSE )
			$this->_system_messages = array();

		// Don't add duplicates.
		if( ! in_array(array(
				'type' => $type,
				'text' => $text,
			), $this->_system_messages) )
			$this->_system_messages[] = array(
				'type' => $type,
				'text' => $text,
			);

		return TRUE;
	}

	public function system_messages() {
		return $this->_system_messages;
	}

	/**
	 * Return an array of masthead_links, including the current one.
	 * @return Array 
	 */
	public function masthead_links() {
		$masthead_links = array();

		$masthead_links[] = array(
			'url' => '/dash/',
			'text' => 'Dash',
			'class' => 'dash',
		);
		$masthead_links[] = array(
			'url' => '/customers/',
			'text' => 'Customers',
			'class' => 'customers',
		);
		$masthead_links[] = array(
			'url' => '/vendors/',
			'text' => 'Vendors',
			'class' => 'vendors',
		);
		$masthead_links[] = array(
			'url' => '/accounts/',
			'text' => 'Accounts',
			'class' => 'accounts',
		);
		
		$masthead_links[] = array(
			'url' => '/setup/',
			'text' => 'Setup',
			'class' => 'setup',
		);
		
		foreach( $masthead_links as $index => $masthead_link )
			$masthead_links[$index]['current'] = ( strpos(strtolower($this->request->uri()), strtolower(str_replace('/','',$masthead_link['url']))) === 0 )
									  ? TRUE
									  : FALSE;
		
		return $masthead_links;
	}

	public function site_section()
	{
		foreach( $this->masthead_links() as $link ) 
			if( $link['current'] )
				return $link['class'];

		return FALSE;
	}

	/**
	 * Return an array of tab_links, including the current one.
	 * @return Array 
	 */
	public function tab_links()
	{
		$tab_links = Session::instance()->get('tab_links');
		if( ! $tab_links )
			$tab_links = array();
		
		foreach( $tab_links as $index => $tab_link )
		{
			$tab_links[$index]['current'] = ( '/'.$this->request->uri() == $tab_link['url'] OR 
												(
												 	isset($this->force_current_uri) AND
												  	$this->force_current_uri == $tab_link['url']
												)
											)
										  ? TRUE
										  : FALSE;
			$tab_links[$index]['text_short'] = ( ! isset($tab_link['text_short']) OR 
												 ! strlen($tab_link['text_short']) )
											 ? substr($tab_link['text'],0,3)
											 : $tab_link['text_short'];
		}
		return $tab_links;
	}

	public function tab_links_defined()
	{
		return ( count($this->tab_links()) ) ? TRUE : FALSE;
	}

	public function shrink()
	{
		return ( count($this->tab_links()) > 7 ) ? TRUE : FALSE;
	}

	public function current_tab_link()
	{
		$tabs = $this->tab_links();

		foreach( $tabs as $tab )
		{
			if( $tab['current'] )
				return $tab;
		}
		
		return FALSE;
	}

	/**
	 * Layout Variables and Helpers
	 * Date Builders, Etc.
	 */
	
	// List of integers, etc.
	public function integers0to90()
	{
		$integers = array();
		for( $i = 0; $i <= 90; $i++ )
			$integers[] = $i;

		return $integers;
	}

	public function integers1to12()
	{
		$integers = array();
		for( $i = 1; $i <= 12; $i++ )
			$integers[] = $i;

		return $integers;
	}
	
	// Generate a list of dates by month.
	private function _months_backward($count = 12)
	{
		$months = array();
		for( $i = 0; $i < $count; $i++ )
			$months[] = array(
				'YYYY-MM' => date("Y-m",strtotime("-".$i." Months")),
				'text' => date("F, Y",strtotime("-".$i." Months")),
			);

		return $months;
	}

	public function months_backward_36()
	{
		return $this->_months_backward(36);
	}

	public function months_backward_24()
	{
		return $this->_months_backward(24);
	}

	// Generate the current date.
	public function dateYYYYMMDD() {
		return date("Y-m-d");
	}

	protected $_taxes = FALSE;
	public function taxes()
	{
		if( ! isset($this->tax_search_result) )
			return array();

		if( $this->_taxes )
			return $this->_taxes;

		$this->_taxes = array();

		foreach( $this->tax_search_result->data->taxes as $tax )
			$this->_taxes[] = array(
				'id' => $tax->id,
				'name' => $tax->name,
				'code' => $tax->code,
				'percent' => $tax->percent,
				'visible' => $tax->visible ? TRUE : FALSE,
			);

		return $this->_taxes;
	}

	protected $_all_accounts_chart = FALSE;
	public function all_accounts_chart()
	{
		if( ! isset($this->account_chart_result) )
			return array();

		if( $this->_all_accounts_chart )
			return $this->_all_accounts_chart;

		$this->_all_accounts_chart = array();

		$this->_all_accounts_chart = $this->_accounts_array($this->account_chart_result->data->accounts);
		
		return $this->_all_accounts_chart;
	}

	protected $_all_accounts_chart_flat = FALSE;
	public function all_accounts_chart_flat() {
		if( $this->_all_accounts_chart_flat )
			return $this->_all_accounts_chart_flat;

		$this->_all_accounts_chart_flat = $this->_accounts_array_flat($this->all_accounts_chart());
		
		$this->_all_accounts_chart_flat[0]['first'] = TRUE;
		$this->_all_accounts_chart_flat[( count($this->_all_accounts_chart_flat) - 1)]['last'] = TRUE;

		return $this->_all_accounts_chart_flat;
	}

	protected $_account_types = FALSE;
	public function account_types()
	{
		if( ! isset($this->account_type_search_result) )
			return array();

		if( $this->_account_types AND 
			count($this->_account_types) )
			return $this->_account_types;

		$this->_account_types = array();

		foreach( $this->account_type_search_result->data->account_types as $account_type )
			$this->_account_types[] = array(
				'id' => $account_type->id,
				'name' => $account_type->name,
				'code' => $account_type->code,
				'table_sign' => $account_type->table_sign,
			);

		return $this->_account_types;
	}

	protected $_countries = FALSE;
	public function countries() {
		if( $this->_countries )
			return $this->_countries;

		$countries = Helper_Address::Countries();

		$this->_countries = array();

		foreach( $countries as $code => $name )
			$this->_countries[] = array(
				'code' => $code,
				'name' => $name,
			);

		return $this->_countries;
	}

	
	protected function _accounts_array($accounts,$level = 0)
	{
		$return_array = array();

		foreach( $accounts as $account )
		{
			$return_array[] = array(
				'id' => $account->id,
				'reserved' => $account->reserved,
				'name' => $account->name,
				'level' => $level,
				'name_print' => str_repeat('&nbsp;',($level*4)).$account->name,
				'code' => $account->code,
				'parent_account_id' => $account->parent_account_id,
				'account_type_id' => ( isset($account->type->id) )
								  ? $account->type->id
								  : FALSE,
				'negative' => ( 
								(
									! isset($account->type->table_sign) AND 
									(
										(
											stripos($account->name,'asset') !== FALSE OR
											stripos($account->name,'expense') !== FALSE
										) AND
										$account->balance > 0
									)
								) OR
								(
									! isset($account->type->table_sign) AND 
									(
										(
											stripos($account->name,'asset') === FALSE AND
											stripos($account->name,'expense') === FALSE
										) AND
										$account->balance < 0
									)
								) OR
								(
									isset($account->type->table_sign) AND 
									$account->type->table_sign > 0 AND 
									$account->balance < 0 
								) OR
								(
									isset($account->type->table_sign) AND 
									$account->type->table_sign < 0 AND 
									$account->balance > 0 
								)
							  )
							? TRUE 
							: FALSE,
				'table_sign' => ( isset($account->type) AND isset($account->type->table_sign) )
							 ? $account->type->table_sign
							 : 0,
				'balance' => number_format(abs($account->balance),2,'.',','),
				'top_level' => ( isset($account->type->id) )
							 ? FALSE
							 : TRUE,
				'has_accounts' => ( isset($account->accounts) AND 
									count($account->accounts) )
								? TRUE
								: FALSE,
				'accounts' => ( isset($account->accounts) AND 
								count($account->accounts) )
							? $this->_accounts_array($account->accounts,($level+1))
							: FALSE,
				'reconcilable' => ( $account->reconcilable ) ? TRUE : FALSE,
				'deposit' => $account->deposit ? TRUE : FALSE,
				'payment' => $account->payment ? TRUE : FALSE,
				'receivable' => $account->receivable ? TRUE : FALSE,
				'payable' => $account->payable ? TRUE : FALSE,
				'reserved' => $account->reserved ? TRUE : FALSE,
				'income' => ( isset($account->type->code) AND $account->type->code == "income" ) ? TRUE : FALSE,
				'costofgoods' => ( isset($account->type->code) AND $account->type->code == "costofgoods" ) ? TRUE : FALSE,
				'writeoff' => $account->writeoff ? TRUE : FALSE,
				'type_'.( isset($account->type->code) ? $account->type->code : '') => TRUE,
				'terms' => $account->terms,
			);
		}

		return $return_array;
	}

	private function _accounts_array_flat($accounts) {
		$return_array = array();

		foreach( $accounts as $account )
		{
			$temp_array = array();
			foreach( $account as $key => $value )
				if( ! is_array($value) )
					$temp_array[$key] = $value;
			$return_array[] = $temp_array;
			
			if( isset($account['accounts']) AND
				$account['accounts'] AND
				count($account['accounts']) ) 
				$return_array = array_merge($return_array,$this->_accounts_array_flat($account['accounts']));
		}

		return $return_array;
	}

	/**
	 * Returns an array of countries and country codes.
	 * @return Array Array of Arrays
	 *                     code => Two letter country code.
	 *                     name => Proper country name.
	 */	
	protected function _country_name($code) {
		return Helper_Address::CountryName($code);
	}

	protected function _customer_array($customer)
	{
		return array(
			"id" => $customer->id,
			"display_name" => $customer->display_name,
			'first_name' => $customer->first_name,
			'last_name' => $customer->last_name,
			'company_name' => $customer->company_name,
			"email" => $customer->email,
			"phone_number" => $customer->phone_number,
			"fax_number" => $customer->fax_number,
			"default_billing_address_id" => $customer->default_billing_address_id,
			"default_shipping_address_id" => $customer->default_shipping_address_id,
			"default_account" => (array)($customer->default_account),
			"sales_count" => $customer->sales_count,
			"sales_total" => $customer->sales_total,
			"balance_current" => ( $customer->balance_pastdue + $customer->balance_pending ),
			"balance_pastdue" => $customer->balance_pastdue,
			"sales_total_formatted" => ($customer->sales_total < 0 ? '-' : '').$this->_company_currency().number_format(abs($customer->sales_total),2,'.',','),
			"balance_current_formatted" => (( $customer->balance_pastdue + $customer->balance_pending ) < 0 ? '-' : '').$this->_company_currency().number_format(abs(( $customer->balance_pastdue + $customer->balance_pending )),2,'.',','),
			"balance_pastdue_formatted" => ($customer->balance_pastdue < 0 ? '-' : '').$this->_company_currency().number_format(abs($customer->balance_pastdue),2,'.',','),
		);
	}

	protected $_sales = FALSE;
	public function sales()
	{
		if( ! isset($this->customer_sale_search_result) )
			return array();

		if( $this->_sales )
			return $this->_sales;

		$this->_sales = array();

		foreach( $this->customer_sale_search_result->data->sales as $sale )
			$this->_sales[] = $this->_sale_array($sale);

		return $this->_sales;
	}

	protected function _sale_array($sale)
	{
		return array(
			'id' => $sale->id,
			'customer' => array(
				'id' => $sale->customer->id,
				'display_name' => $sale->customer->display_name,
				'name' => $sale->customer->display_name,
			),
			'invoiced' => ( $sale->date_billed ? TRUE : FALSE ),
			'cancelled' => ( $sale->date_cancelled ? TRUE : FALSE ),
			'date_created' => $sale->date_created,
			'date_due' => $sale->date_due,
			'sale_number' => $sale->sale_number,
			'order_number' => $sale->order_number,
			'po_number' => $sale->po_number,
			'total' => $sale->total,
			'total_formatted' => ( $sale->total < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($sale->total),2,'.',','),
			'balance' => $sale->balance,
			'balance_flipped' => ( $sale->balance * -1 ),
			'balance_flipped_formatted' => ( $sale->balance > 0 ? '-' : '' ).$this->_company_currency().number_format(abs($sale->balance),2,'.',','),
			'status' => $sale->status,
			'paid' => ( $sale->balance == 0 AND 
						$sale->date_billed )
				   ? TRUE 
				   : FALSE,
			'can_cancel' => (   ! $sale->date_billed AND 
								! $sale->date_cancelled )
						 ? TRUE
						 : FALSE,
			'can_refund' => ( ! $sale->refund_sale_id AND
							  ! $sale->date_cancelled AND 
							  $sale->date_billed )
						 ? TRUE
						 : FALSE,
		);
	}

	protected function _addresses_array($addresses)
	{
		$return_array = array();

		foreach( $addresses as $address )
			$return_array[] = $this->_addresses_address_array($address);

		return $return_array;
	}

	protected function _addresses_address_array($address)
	{
		$return_array = array(
			'id' => $address->id,
			'first_name' => ( $address->first_name )
						 ? $address->first_name
						 : FALSE,
			'last_name' => ( $address->last_name )
						? $address->last_name
						: FALSE,
			'address1' => ( $address->address1 )
					   ? $address->address1
					   : FALSE,
			'address2' => ( $address->address2 )
					   ? $address->address2
					   : FALSE,
			'city' => ( $address->city )
				   ? $address->city
				   : FALSE,
			'state' => ( $address->state )
					? $address->state
					: FALSE,
			'zip' => ( $address->zip )
				  ? $address->zip
				  : FALSE,
			'country' => ( $address->country )
					  ? $address->country
					  : FALSE,
			'country_full' => $this->_country_name($address->country),
		);

		return $return_array;
	}

	protected $_customer_payments = FALSE; // Cache for customer_payments()
	public function customer_payments()
	{
		if( ! isset($this->customer_payment_search_result) )
			return array();

		if( $this->_customer_payments )
			return $this->_customer_payments;

		$this->_customer_payments = array();

		foreach( $this->customer_payment_search_result->data->payments as $payment )
			$this->_customer_payments[] = $this->_customer_payment_array($payment);

		return $this->_customer_payments;
	}

	protected function _customer_payment_array($payment)
	{
		$return_array = array();
		$return_array['id'] = $payment->id;
		$return_array['date'] = $payment->date;
		$return_array['description'] = $payment->description;
		$return_array['number'] = $payment->number;
		$return_array['deposit_account'] = $this->_customer_payment_deposit_account_array($payment);
		$return_array['amount'] = $return_array['deposit_account']['amount'];
		$return_array['amount_formatted'] = ($return_array['amount'] > 0 ? '-' : '').$this->_company_currency().number_format(abs($return_array['amount']),2,'.',',');

		return $return_array;
	}

	protected function _customer_payment_deposit_account_array($payment) {
		if( ! isset($payment->deposit_transaction) OR 
			! $payment->deposit_transaction )
			return FALSE;

		return array(
			'id' => $payment->deposit_transaction->account->id,
			'name' => $payment->deposit_transaction->account->name,
			'amount' => $payment->deposit_transaction->amount,
		);
	}

	protected function _vendor_array($vendor)
	{
		return array(
			"id" => $vendor->id,
			"display_name" => $vendor->display_name,
			'first_name' => $vendor->first_name,
			'last_name' => $vendor->last_name,
			'company_name' => $vendor->company_name,
			"email" => $vendor->email,
			"phone_number" => $vendor->phone_number,
			"fax_number" => $vendor->fax_number,
			"default_remit_address_id" => $vendor->default_remit_address_id,
			"default_account" => (array)($vendor->default_account),
			"purchases_total" => $vendor->purchases_total,
			"expenses_total" => $vendor->expenses_total,
			"total_sales" => $vendor->total_total,
			"balance_current" => ( $vendor->balance_pastdue + $vendor->balance_pending ),
			"balance_pastdue" => $vendor->balance_pastdue,
			"total_sales_formatted" => ($vendor->total_total < 0 ? '-' : '').$this->_company_currency().number_format(abs($vendor->total_total),2,'.',','),
			"balance_current_formatted" => (( $vendor->balance_pastdue + $vendor->balance_pending ) < 0 ? '-' : '').$this->_company_currency().number_format(abs(( $vendor->balance_pastdue + $vendor->balance_pending )),2,'.',','),
			"balance_pastdue_formatted" => ($vendor->balance_pastdue < 0 ? '-' : '').$this->_company_currency().number_format(abs($vendor->balance_pastdue),2,'.',','),
		);
	}

	protected $_vendor_payments = FALSE;
	public function vendor_payments()
	{
		if( ! isset($this->vendor_payment_search_result) )
			return FALSE;

		if( $this->_vendor_payments )
			return $this->_vendor_payments;

		$this->_vendor_payments = array();

		foreach( $this->vendor_payment_search_result->data->payments as $payment )
			$this->_vendor_payments[] = $this->_vendor_payment_array($payment);

		return $this->_vendor_payments;
	}

	protected function _vendor_payment_array($payment)
	{
		$return_array = array();
		$return_array['id'] = $payment->id;
		$return_array['date'] = $payment->date;
		$return_array['description'] = $payment->description;
		$return_array['number'] = $payment->number;
		$return_array['payment_account'] = $this->_vendor_payment_payment_account_array($payment);
		$return_array['amount'] = ( $return_array['payment_account']['amount'] );
		$return_array['amount_formatted'] = ($return_array['amount'] < 0 ? '-' : '').$this->_company_currency().number_format(abs($return_array['amount']),2,'.',',');
		$return_array['check_number'] = ( 	isset($payment->check_number) AND 
											strlen($payment->check_number) )
									  ? $payment->check_number
									  : FALSE;

		$return_array['vendor'] = array(
			'id' => $payment->vendor->id,
			'display_name' => $payment->vendor->display_name,
		);

		return $return_array;
	}

	protected function _vendor_payment_payment_account_array($payment) {
		if( ! isset($payment->payment_transaction) )
			return FALSE;

		return array(
			'id' => $payment->payment_transaction->account->id,
			'name' => $payment->payment_transaction->account->name,
			'amount' => $payment->payment_transaction->amount,
		);
	}

	protected $_expenses = FALSE; // Cache for expenses()
	public function expenses()
	{
		if( ! isset($this->vendor_expense_search_result) )
			return array();

		if( $this->_expenses )
			return $this->_expenses;

		$this->_expenses = array();

		foreach( $this->vendor_expense_search_result->data->expenses as $expense )
			$this->_expenses[] = $this->_expense_array($expense);

		return $this->_expenses;
	}

	protected function _expense_array($expense)
	{
		return array( 
			'id' => $expense->id,
			'vendor' => array(
				'id' => $expense->vendor->id,
				'display_name' => $expense->vendor->display_name,
			),
			'date_created' => $expense->date_created,
			'invoice_number' => $expense->invoice_number,
			'so_number' => $expense->so_number,
			'check_number' => $expense->check_number,
			'total' => $expense->total,
			'total_formatted' => ( $expense->total < 0 ? '-' : '' ).$this->_company_currency().number_format($expense->total,2,'.',','),
			'balance' => ( $expense->balance * -1 ),
			'balance_flipped' => ( $expense->balance ),
			'balance_flipped_formatted' => ( $expense->balance < 0 ? '-' : '' ).$this->_company_currency().number_format(abs($expense->balance),2,'.',','),
			'can_cancel' => ( ! $expense->refund_expense_id OR 
								$expense->refund_expense_id < $expense->id )
						 ? TRUE
						 : FALSE,
			'can_refund' => ( ! $expense->refund_expense_id )
						 ? TRUE
						 : FALSE,
		);
	}

	protected $_purchases = FALSE;
	public function purchases()
	{
		if( ! isset($this->vendor_purchase_search_result) )
			return array();

		if( $this->_purchases )
			return $this->_purchases;

		$this->_purchases = array();

		foreach( $this->vendor_purchase_search_result->data->purchases as $purchase )
			$this->_purchases[] = $this->_purchase_array($purchase);

		return $this->_purchases;
	}

	protected function _purchase_array($purchase)
	{
		return array(
			'id' => $purchase->id,
			'vendor' => array(
				'id' => $purchase->vendor->id,
				'display_name' => $purchase->vendor->display_name,
				'email' => $purchase->vendor->email,
			),
			'cancelled' => $purchase->date_cancelled ? TRUE : FALSE,
			'invoiced' => $purchase->date_billed ? TRUE : FALSE,
			'date_created' => $purchase->date_created,
			'date_billed' => $purchase->date_billed,
			'date_due' => $purchase->date_due,
			'purchase_number' => $purchase->purchase_number,
			'so_number' => $purchase->so_number,
			'quote_number' => $purchase->quote_number,
			'invoice_number' => $purchase->invoice_number,
			'total' => $purchase->total,
			'total_formatted' => ( $purchase->total > 0 ? '' : '-' ).$this->_company_currency().number_format(abs($purchase->total),2,'.',','),
			'balance' => $purchase->balance,
			'balance_flipped' => ( $purchase->balance * -1 ),
			'balance_flipped_formatted' => ( $purchase->balance > 0 ? '-' : '' ).$this->_company_currency().number_format(abs($purchase->balance),2,'.',','),
			'status' => $purchase->status,
			'can_cancel' => ( ! $purchase->date_cancelled )
						 ? TRUE
						 : FALSE,
			'can_refund' => ( ! $purchase->date_cancelled AND 
							  ! $purchase->refund_purchase_id AND 
							  $purchase->date_billed )
						 ? TRUE
						 : FALSE,
		);
	}

	protected $_swift_logo_embed = FALSE;
	public function companylogo()
	{
		$beans_settings = $this->beans_settings();

		if( ! $beans_settings )
			return FALSE;

		if( ! isset($beans_settings->LOCAL) OR 
			! isset($beans_settings->company_logo_filename) )
			return FALSE;

		if( isset($this->swift_email_message) AND
			$this->swift_email_message )
		{
			if( ! $this->_swift_logo_embed )
				$this->_swift_logo_embed = $this->swift_email_message->embed(new Swift_Image(base64_decode($beans_settings->company_logo_data),$beans_settings->company_logo_filename,$beans_settings->company_logo_type));
			
			return '<img alt="'.$beans_settings->company_name.'" src="'.$this->_swift_logo_embed.'" style="max-height: 50px; max-width: 150px;">';
		}
		else
		{
			return '<img alt="'.$beans_settings->company_name.'" src="data:'.$beans_settings->company_logo_type.';base64,'.$beans_settings->company_logo_data.'" style="max-height: 50px; max-width: 150px;">';
		}
	}

	public function companyname()
	{
		$beans_settings = $this->beans_settings();

		if( ! $beans_settings )
			return FALSE;

		if( ! isset($beans_settings->company_name) OR 
			! $beans_settings->company_name )
			return FALSE;

		return $beans_settings->company_name;
	}

	protected $_companyinfo = FALSE;
	public function companyinfo()
	{
		if( $this->_companyinfo )
			return $this->_companyinfo;

		$beans_settings = $this->beans_settings();
		
		$this->_companyinfo = array(
			'address1' => $beans_settings->company_address_address1,
			'address2' => $beans_settings->company_address_address2,
			'city' => $beans_settings->company_address_city,
			'state' => $beans_settings->company_address_state,
			'zip' => $beans_settings->company_address_zip,
			'phone' => $beans_settings->company_phone,
			'email' => $beans_settings->company_email,
		);

		return $this->_companyinfo;
	}
	
	public function fontsizenormal()
	{
		return 'font-size: 12px; line-height: 15px;';
	}

	public function fontsizelarge()
	{
		return 'font-size: 20px; line-height: 30px;';
	}

	public function tableopen()
	{
		return '<table cellpadding="0" cellspacing="0" border="0" style="width: 735px;margin:0px auto; table-layout:fixed; overflow: hidden;">';
	}

	public function tableopenlandscape()
	{
		return '<table cellpadding="0" cellspacing="0" border="0" style="width: 936px;margin:0px auto; table-layout:fixed; overflow: hidden;">';
	}

	public function tableclose()
	{
		return '</table>';
	}

	public function tablecloselandscape()
	{
		return '</table>';
	}

	public function borderdarkgreen()
	{
		return '#b6d2b6';
	}

	public function backgroundlightgreen()
	{
		return '#e8f1e8';
	}

	public function backgrounddarkgreen()
	{
		return '#589b5c';
	}

	public function contentclass()
	{
		return FALSE;
	}

}