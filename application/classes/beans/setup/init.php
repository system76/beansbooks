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

class Beans_Setup_Init extends Beans_Setup {

	// See below for default type information.
	protected $_default_account_set;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_default_account_set = isset($data->default_account_set) 
									? $data->default_account_set
									: FALSE;
	}

	protected function _execute()
	{
		// ONLY RUN ONCE.  If we have accounts / account_types / roles we quit
		if( ORM::Factory('account')->count_all() != 0 OR 
			ORM::Factory('account_type')->count_all() != 0 OR 
			ORM::Factory('role')->count_all() != 0 ) 
			throw new Exception("Database initialization can only be run once.");

		if( ! $this->_default_account_set )
			throw new Exception("No default account set provided.");

		if( ! isset($this->_accounts_options[$this->_default_account_set]) )
			throw new Exception("Invalid default account set.  Available options are: ".implode(', ',array_keys($this->_accounts_options)));

		// Roles
		foreach( $this->_default_roles as $default_role )
		{
			$role = ORM::Factory('role');

			foreach( $default_role as $key => $value )
				$role->{$key} = $value;

			$role->save();
		}

		// Account Types
		foreach( $this->_default_account_types as $default_account_type )
		{
			$account_type = ORM::Factory('account_type');

			foreach( $default_account_type as $key => $value )
				$account_type->{$key} = $value;

			$account_type->save();
		}

		// Accounts
		foreach( $this->_default_accounts as $default_account )
		{
			$account = ORM::Factory('account');

			foreach( $default_account as $key => $value )
				$account->{$key} = $value;

			$account->save();
		}

		// Settings
		foreach( $this->_default_settings as $default_setting )
		{
			$setting = ORM::Factory('setting');

			foreach( $default_setting as $key => $value )
				$setting->{$key} = $value;

			$setting->save();
		}

		$setting = ORM::Factory('setting')->where('key','=','BEANS_VERSION')->find();
		if( ! $setting->loaded() )
			$setting = ORM::Factory('setting');
		
		$setting->key = 'BEANS_VERSION';
		$setting->value = $this->_BEANS_VERSION;
		$setting->save();
		
		// Three laws - let it go.

		// Default Accounts
		// Grab all accounts.
		$beans_account_search = new Beans_Account_Search($this->_beans_data_auth());
		$beans_account_search_result = $beans_account_search->execute();

		$top_level_accounts = array();

		foreach( $beans_account_search_result->data->accounts as $account )
			if( ! $account->parent_account_id )
				$top_level_accounts[$account->code] = $account->id;
		
		// Grab all account types.
		$beans_account_type_search = new Beans_Account_Type_Search($this->_beans_data_auth());
		$beans_account_type_search_result = $beans_account_type_search->execute();

		$account_types = array();

		foreach( $beans_account_type_search_result->data->account_types as $account_type )
			$account_types[$account_type->code] = $account_type->id;

		foreach( $this->_accounts_options[$this->_default_account_set]['accounts'] as $account_code => $default_accounts )
			$this->_add_sub_accounts((array)$this->_beans_data_auth(),$account_types,$top_level_accounts[$account_code],$default_accounts);

		return (object)array();
	}

	private function _add_sub_accounts($beans_auth_array,$account_types,$parent_account_id,$accounts)
	{
		foreach( $accounts as $account )
		{
			if( ! isset($account['name']) )
				die("Fatal error encountered: corrupt internal data and could not create accounts.");

			$beans_account_create = new Beans_Account_Create((object)array_merge(
				$beans_auth_array,
				array(
					'parent_account_id' => $parent_account_id,
					'account_type_id' => $account_types[$account['type']],
					'reserved' => isset($account['reserved']) ? $account['reserved'] : FALSE,
					'name' => $account['name'],
					'code' => substr(strtolower(str_replace(' ','',$account['name'])),0,16),
					'terms' => isset($account['terms']) ? $account['terms'] : NULL,
					'writeoff' => isset($account['writeoff']) AND $account['writeoff'] ? TRUE : FALSE,
				)
			));
			$beans_account_create_result = $beans_account_create->execute();

			if( ! $beans_account_create_result->success )
			{
				if( Kohana::$is_cli )
					echo "Error occurred creating account ".$account['name']." (".$account['type'].") : ".$beans_account_create_result->error."\n";
			}
			else
			{
				if( Kohana::$is_cli )
					echo "Created account: ".$beans_account_create_result->data->account->name."\n";

				if ( isset($account['default_setting_account']) )
				{
					$settings = new stdClass;
					if( is_array($account['default_setting_account']) )
						foreach( $account['default_setting_account'] as $setting )
							$settings->{$setting} = $beans_account_create_result->data->account->id;
					else
						$settings->{$account['default_setting_account']} = $beans_account_create_result->data->account->id;
					
					// Add as setting.
					$beans_company_update = new Beans_Setup_Company_Update((object)array_merge(
						$beans_auth_array,
						array(
							'settings' => $settings,
						)
					));
					$beans_company_update_result = $beans_company_update->execute();
				}
				if( isset($account['accounts']) AND 
					count($account['accounts']) )
					$this->_add_sub_accounts($beans_auth_array,$account_types,$beans_account_create_result->data->account->id,$account['accounts']);
			}
		}
	}


	private $_default_account_types = array(
		array(
			'name'			=> 'Bank Account',
			'code'			=> 'bankaccount',
			'type'			=> 'Asset',
			'table_sign'	=> -1,
			'deposit'		=> TRUE,
			'payment'		=> TRUE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> TRUE,
		),
		array(
			'name'			=> 'Cash',
			'code'			=> 'cash',
			'type'			=> 'Asset',
			'table_sign'	=> -1,
			'deposit'		=> TRUE,
			'payment'		=> TRUE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Fixed Asset',
			'code'			=> 'fixedasset',
			'type'			=> 'Asset',
			'table_sign'	=> -1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Short Term Debt',
			'code'			=> 'shorttermdebt',
			'type'			=> 'Liability',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> TRUE,
			'receivable'	=> FALSE,
			'payable'		=> TRUE,
			'reconcilable'	=> TRUE,
		),
		array(
			'name'			=> 'Long Term Debt',
			'code'			=> 'longtermdebt',
			'type'			=> 'Liability',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Accounts Receivable',
			'code'			=> 'accountsreceivable',
			'type'			=> 'Asset',
			'table_sign'	=> -1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> TRUE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Accounts Payable',
			'code'			=> 'accountspayable',
			'type'			=> 'Liability',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> TRUE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Equity',
			'code'			=> 'equity',
			'type'			=> 'Equity',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Cost of Goods Sold',
			'code'			=> 'costofgoods',
			'type'			=> 'Cost of Goods Sold',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Expense',
			'code'			=> 'expense',
			'type'			=> 'Expense',
			'table_sign'	=> -1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Income',
			'code'			=> 'income',
			'type'			=> 'Income',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		// Internal Account Types
		array(
			'name'			=> 'Pending Accounts Receivable',
			'code'			=> 'pending_ar',
			'type'			=> 'Asset',
			'table_sign'	=> -1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> TRUE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Pending Accounts Payable',
			'code'			=> 'pending_ap',
			'type'			=> 'Liability',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> TRUE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Pending Income',
			'code'			=> 'pending_income',
			'type'			=> 'Income',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Pending Cost',
			'code'			=> 'pending_cost',
			'type'			=> 'Cost of Goods Sold',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> FALSE,
			'receivable'	=> FALSE,
			'payable'		=> FALSE,
			'reconcilable'	=> FALSE,
		),
		array(
			'name'			=> 'Pending Liability',
			'code'			=> 'pending_liability',
			'type'			=> 'Liability',
			'table_sign'	=> 1,
			'deposit'		=> FALSE,
			'payment'		=> TRUE,
			'receivable'	=> FALSE,
			'payable'		=> TRUE,
			'reconcilable'	=> TRUE,
		),
	);
	
	private $_default_accounts = array(
		array(
			'name' => 'Assets',
			'code' => 'assets',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
		array(
			'name' => 'Liabilities',
			'code' => 'liabilities',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
		array(
			'name' => 'Income',
			'code' => 'income',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
		array(
			'name' => 'Cost of Goods Sold',
			'code' => 'costofgoods',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
		array(
			'name' => 'Expenses',
			'code' => 'expenses',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
		array(
			'name' => 'Equity',
			'code' => 'equity',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
		array(
			'name' => 'BeansBooks Tracking Accounts',
			'code' => 'beans',
			'parent_account_id' => NULL,
			'account_type_id' => NULL,
			'reserved' => TRUE,
			'deposit' => FALSE,
			'payment' => FALSE,
			'receivable' => FALSE,
			'payable' => FALSE,
			'writeoff' => FALSE,
			'reconcilable' => FALSE,
			'terms' => NULL,
			'balance' => NULL,
		),
	);
	
	// V2Item - Add more roles here and build into system.
	private $_default_roles = array(
		// ADMIN
		array(
			'name' => 'Administrator',
			'code' => 'admin',
			'description' => 'Administrator Description... e.g. EVERYTHING.',
			'auth_expiration_length' => 86400,
			'user_limit' => NULL,
			'customer_read' => TRUE,
			'customer_write' => TRUE,
			'customer_sale_read' => TRUE,
			'customer_sale_write' => TRUE,
			'customer_payment_read' => TRUE,
			'customer_payment_write' => TRUE,
			'vendor_read' => TRUE,
			'vendor_write' => TRUE,
			'vendor_expense_read' => TRUE,
			'vendor_expense_write' => TRUE,
			'vendor_purchase_read' => TRUE,
			'vendor_purchase_write' => TRUE,
			'vendor_payment_read' => TRUE,
			'vendor_payment_write' => TRUE,
			'account_read' => TRUE,
			'account_write' => TRUE,
			'account_transaction_read' => TRUE,
			'account_transaction_write' => TRUE,
			'account_reconcile' => TRUE,
			'books' => TRUE,
			'reports' => TRUE,
			'setup' => TRUE,
		),
		// Super User ( All but setup )
		array(
			'name' => 'User',
			'code' => 'user',
			'description' => 'Super User - Access to everything except setup functionality.',
			'auth_expiration_length' => 86400,
			'user_limit' => NULL,
			'customer_read' => TRUE,
			'customer_write' => TRUE,
			'customer_sale_read' => TRUE,
			'customer_sale_write' => TRUE,
			'customer_payment_read' => TRUE,
			'customer_payment_write' => TRUE,
			'vendor_read' => TRUE,
			'vendor_write' => TRUE,
			'vendor_expense_read' => TRUE,
			'vendor_expense_write' => TRUE,
			'vendor_purchase_read' => TRUE,
			'vendor_purchase_write' => TRUE,
			'vendor_payment_read' => TRUE,
			'vendor_payment_write' => TRUE,
			'account_read' => TRUE,
			'account_write' => TRUE,
			'account_transaction_read' => TRUE,
			'account_transaction_write' => TRUE,
			'account_reconcile' => TRUE,
			'books' => TRUE,
			'reports' => TRUE,
			'setup' => FALSE,
		),
		// API Access
		array(
			'name' => 'API Access',
			'code' => 'api',
			'description' => 'API Access - Adminstrator user with unlimited key authorization time.',
			'auth_expiration_length' => 0,
			'user_limit' => 1,
			'customer_read' => TRUE,
			'customer_write' => TRUE,
			'customer_sale_read' => TRUE,
			'customer_sale_write' => TRUE,
			'customer_payment_read' => TRUE,
			'customer_payment_write' => TRUE,
			'vendor_read' => TRUE,
			'vendor_write' => TRUE,
			'vendor_expense_read' => TRUE,
			'vendor_expense_write' => TRUE,
			'vendor_purchase_read' => TRUE,
			'vendor_purchase_write' => TRUE,
			'vendor_payment_read' => TRUE,
			'vendor_payment_write' => TRUE,
			'account_read' => TRUE,
			'account_write' => TRUE,
			'account_transaction_read' => TRUE,
			'account_transaction_write' => TRUE,
			'account_reconcile' => TRUE,
			'books' => TRUE,
			'reports' => TRUE,
			'setup' => FALSE,
		),
	);

	private $_default_settings = array(
 		array(
			'key' => 'company_logo_data',
			'value' => NULL,
		),
		array(
			'key' => 'company_logo_type',
			'value' => NULL,
		),
		array(
			'key' => 'company_name',
			'value' => NULL,
		),
		array(
			'key' => 'company_email',
			'value' => NULL,
		),
		array(
			'key' => 'company_phone',
			'value' => NULL,
		),
		array(
			'key' => 'company_fax',
			'value' => NULL,
		),
		array(
			'key' => 'company_address_address1',
			'value' => NULL,
		),
		array(
			'key' => 'company_address_address2',
			'value' => NULL,
		),
		array(
			'key' => 'company_address_city',
			'value' => NULL,
		),
		array(
			'key' => 'company_address_state',
			'value' => NULL,
		),
		array(
			'key' => 'company_address_zip',
			'value' => NULL,
		),
		array(
			'key' => 'company_address_country',
			'value' => NULL,
		),
		array(
			'key' => 'company_fye',
			'value' => '12-31',
		),
		array(
			'key' => 'company_currency',
			'value' => '$',
		),
		array(
			'key' => 'account_default_deposit',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_receivable',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_income',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_returns',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_expense',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_order',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_costofgoods',
			'value' => NULL,
		),
		array(
			'key' => 'account_default_payable',
			'value' => NULL,
		),
		array(
			'key' => 'sale_default_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'sale_default_line_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'sale_default_tax_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'purchase_default_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'purchase_default_line_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'sale_deferred_income_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'sale_deferred_liability_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		array(
			'key' => 'purchase_prepaid_purchase_account_id',
			'value' => NULL,
			'reserved' => TRUE,
		),
		
	);

	protected $_accounts_options = array(
		'base' => array(
			'name' => "Top Level Only",
			'description' => "Only setup top level accounts such as assets, liabilities, etc.",
			'accounts' => array(
				'beans' => array(
					array(
						'name' => "Pending AR for Sales Orders",
						'type' => "pending_ar",
						'reserved' => TRUE,
						'default_setting_account' => "sale_default_account_id",
					),
					array(
						'name' => "Pending AP for Purchase Orders",
						'type' => "pending_ap",
						'reserved' => TRUE,
						'default_setting_account' => "purchase_default_account_id",
					),
					array(
						'name' => "Pending Income for Sales Orders",
						'type' => "pending_income",
						'reserved' => TRUE,
						'default_setting_account' => "sale_default_line_account_id",
					),
					array(
						'name' => "Pending Cost for Purchase Orders",
						'type' => "pending_cost",
						'reserved' => TRUE,
						'default_setting_account' => "purchase_default_line_account_id",
					),
					array(
						'name' => "Pending Liability for Sales Tax",
						'type' => "pending_liability",
						'reserved' => TRUE,
						'default_setting_account' => "sale_default_tax_account_id",
					),
					array(
						'name' => 'Deferred Income',
						'type' => "shorttermdebt",
						'reserved' => TRUE,
						'default_setting_account' => "sale_deferred_income_account_id",
					),
					array(
						'name' => 'Deferred Tax',
						'type' => "shorttermdebt",
						'reserved' => TRUE,
						'default_setting_account' => "sale_deferred_liability_account_id",
					),
					array(
						'name' => 'Prepaid Purchase Orders',
						'type' => "bankaccount",
						'reserved' => TRUE,
						'default_setting_account' => "purchase_prepaid_purchase_account_id",
					)
				),
			),
		),
		'full' => array(
			'name' => "Full",
			'description' => "A nearly complete chart of accounts, missing only your business-specific accounts.",
			'accounts' => array(
				
				'assets' => array(
					array(
						'name' => "Current Assets",
						'type' => "bankaccount",
						'accounts' => array(
							array(
								'name' => "Checking Account",
								'type' => "bankaccount",
								'default_setting_account' => array("account_default_deposit","account_default_order","account_default_expense"),
							),
							array(
								'name' => "Savings Account",
								'type' => "bankaccount",
							),
							array(
								'name' => 'Prepaid Purchase Orders',
								'type' => "bankaccount",
								'reserved' => TRUE,
								'default_setting_account' => "purchase_prepaid_purchase_account_id",
							),
						),
					),
					array(
						'name' => "Accounts Receivable",
						'type' => "accountsreceivable",
						'terms' => 0,
						'accounts' => array(
							array(
								'name' => "Due Upon Receipt",
								'type' => "accountsreceivable",
								'terms' => 3,
							),
							array(
								'name' => "Net 15",
								'type' => "accountsreceivable",
								'terms' => 15,
							),
							array(
								'name' => "Net 30",
								'type' => "accountsreceivable",
								'terms' => 30,
							),
							array(
								'name' => "Credit Card Receivable",
								'type' => "accountsreceivable",
								'terms' => 7,
								'default_setting_account' => "account_default_receivable",
							),
						),
					),
					array(
						'name' => "Fixed Assets",
						'type' => "fixedasset",
						'accounts' => array(
							array(
								'name' => "Computers and Equipment",
								'type' => "fixedasset",
							),
							array(
								'name' => "Accumulated Depreciation",
								'type' => "fixedasset",
							),
							array(
								'name' => "Furniture and Fixtures",
								'type' => "fixedasset",
							),
							array(
								'name' => "Leasehold Improvements",
								'type' => "fixedasset",
							),
						),
					),
				),

				'liabilities' => array(
					array(
						'name' => "Long Term Debt",
						'type' => "longtermdebt",
						'accounts' => array(
							array(
								'name' => "Loan 1",
								'type' => "longtermdebt",
							),
						),
					),
					array(
						'name' => "Short Term Debt",
						'type' => 'shorttermdebt',
						'accounts' => array(
							array(
								'name' => 'Credit Card 1',
								'type' => "shorttermdebt",
							),
							array(
								'name' => 'Net 15 Accounts Payable',
								'type' => "accountspayable",
								'terms' => 15,
							),
							array(
								'name' => 'Net 30 Accounts Payable',
								'type' => "accountspayable",
								'terms' => 30,
								'default_setting_account' => "account_default_payable",
							),
							array(
								'name' => 'Sales Tax Collected',
								'type' => "shorttermdebt",
							),
							array(
								'name' => 'Deferred Income',
								'type' => "shorttermdebt",
								'reserved' => TRUE,
								'default_setting_account' => "sale_deferred_income_account_id",
							),
							array(
								'name' => 'Deferred Tax',
								'type' => "shorttermdebt",
								'reserved' => TRUE,
								'default_setting_account' => "sale_deferred_liability_account_id",
							)
						),
					),
				),

				'income' => array(
					array(
						'name' => "Product Sales",
						'type' => "income",
						'default_setting_account' => "account_default_income",
						'writeoff' => TRUE,
					),
					array(
						'name' => "Service Sales",
						'type' => "income",
						'writeoff' => TRUE,
					),
					array(
						'name' => "Interest Income",
						'type' => "income",
					),
					array(
						'name' => "Other Income",
						'type' => "income",
						'writeoff' => TRUE,
					),
					array(
						'name' => "Returns and Allowances",
						'type' => "income",
						'default_setting_account' => "account_default_returns",
						'writeoff' => TRUE,
					),
				),

				'costofgoods' => array(
					array(
						'name' => "Products Cost of Goods Sold",
						'type' => "costofgoods",
						'default_setting_account' => "account_default_costofgoods",
						'writeoff' => TRUE,
					),
					array(
						'name' => "Services Cost of Goods Sold",
						'type' => "costofgoods",
						'writeoff' => TRUE,
					),
					array(
						'name' => "Shipping",
						'type' => "costofgoods",
					),
					array(
						'name' => "Credit Card Merchant Fees",
						'type' => "costofgoods",
						'writeoff' => TRUE,
					),
				),

				'expenses' => array(
					array(
						'name' => "Auto",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Fees",
								'type' => "expense",
							),
							array(
								'name' => "Gas",
								'type' => "expense",
							),
							array(
								'name' => "Parking",
								'type' => "expense",
							),
							array(
								'name' => "Repairs and Maintenance",
								'type' => "expense",
							),
						),
					),
					array(
						'name' => "Bank Service Charge",
						'type' => "expense",
					),
					array(
						'name' => "Depreciation",
						'type' => "expense",
					),
					array(
						'name' => "Dining",
						'type' => "expense",
					),
					array(
						'name' => "Insurance",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Disability Insurance",
								'type' => "expense",
							),
							array(
								'name' => "Liability Insurance",
								'type' => "expense",
							),
							array(
								'name' => "Health Insurance",
								'type' => "expense",
							),
							array(
								'name' => "Workers Comp",
								'type' => "expense",
							),
						),
					),
					array(
						'name' => "Licenses and Permits",
						'type' => "expense",
					),
					array(
						'name' => "Payroll",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Payroll Employees",
								'type' => "expense",
							),
							array(
								'name' => "Payroll Taxes",
								'type' => "expense",
							),
						),
					),
					array(
						'name' => "Postage and Delivery",
						'type' => "expense",
					),
					array(
						'name' => "Professional Services",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Accounting",
								'type' => "expense",
							),
							array(
								'name' => "Legal",
								'type' => "expense",
							),
							array(
								'name' => "Payroll",
								'type' => "expense",
							),
							array(
								'name' => "Tax Preparation",
								'type' => "expense",
							),
						),
					),
					array(
						'name' => "Rent",
						'type' => "expense",
					),
					array(
						'name' => "Taxes",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Federal",
								'type' => "expense",
							),
							array(
								'name' => "State",
								'type' => "expense",
							),
							array(
								'name' => "Local",
								'type' => "expense",
							),
						),
					),
					array(
						'name' => "Travel",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Meals, Entertainment, Misc. Travel",
								'type' => "expense",
							),
							array(
								'name' => "Transit and Lodging",
								'type' => "expense",
							),
						),
					),
					array(
						'name' => "Office Expense",
						'type' => "expense",
					),
					array(
						'name' => "Credit Card Interest and Fees",
						'type' => "expense",
					),
					array(
						'name' => "Information Systems",
						'type' => "expense",
					),
					array(
						'name' => "Loan Interest and Fees",
						'type' => "expense",
					),
					array(
						'name' => "Marketing",
						'type' => "expense",
					),
					array(
						'name' => "Meals and Entertainment 100%",
						'type' => "expense",
					),
					array(
						'name' => "Utilities",
						'type' => "expense",
						'accounts' => array(
							array(
								'name' => "Internet",
								'type' => "expense",
							),
							array(
								'name' => "Cell Phone",
								'type' => "expense",
							),
							array(
								'name' => "Phone",
								'type' => "expense",
							),
						),
					),
				),

				'equity' => array(
					array(
						'name' => "Retained Earnings",
						'type' => "equity",
					),
					array(
						'name' => "Owners Distribution",
						'type' => "equity",
					),
				),

				'beans' => array(
					array(
						'name' => "Pending AR for Sales Orders",
						'type' => "pending_ar",
						'reserved' => TRUE,
						'default_setting_account' => "sale_default_account_id",
					),
					array(
						'name' => "Pending AP for Purchase Orders",
						'type' => "pending_ap",
						'reserved' => TRUE,
						'default_setting_account' => "purchase_default_account_id",
					),
					array(
						'name' => "Pending Income for Sales Orders",
						'type' => "pending_income",
						'reserved' => TRUE,
						'default_setting_account' => "sale_default_line_account_id",
					),
					array(
						'name' => "Pending Cost for Purchase Orders",
						'type' => "pending_cost",
						'reserved' => TRUE,
						'default_setting_account' => "purchase_default_line_account_id",
					),
					array(
						'name' => "Pending Liability for Sales Tax",
						'type' => "pending_liability",
						'reserved' => TRUE,
						'default_setting_account' => "sale_default_tax_account_id",
					),	
				),
			),
		),
	);

}