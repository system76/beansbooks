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

class Controller_Setup_Json extends Controller_Json {

	public function action_taxcreate()
	{
		$tax_create = new Beans_Tax_Create($this->_beans_data_auth((object)array(
			'name' => $this->request->post('name'),
			'account_id' => $this->request->post('account_id'),
			'percent' => ( $this->request->post('percent') / 100 ),
			'license' => $this->request->post('license'),
			'authority' => $this->request->post('authority'),
			'address1' => $this->request->post('address1'),
			'address2' => $this->request->post('address2'),
			'city' => $this->request->post('city'),
			'state' => $this->request->post('state'),
			'zip' => $this->request->post('zip'),
			'country' => $this->request->post('country'),
			'date_due' => $this->request->post('date_due'),
			'date_due_months_increment' => $this->request->post('date_due_months_increment'),
		)));
		$tax_create_result = $tax_create->execute();

		if( ! $tax_create_result->success )
			return $this->_return_error($this->_beans_result_get_error($tax_create_result));

		$html = new View_Partials_Taxes_Taxes_Tax;
		$html->tax = $tax_create_result->data->tax;

		$tax_create_result->data->tax->html = $html->render();

		$this->_return_object->data->tax = $tax_create_result->data->tax;
	}

	public function action_taxupdate()
	{
		$tax_update = new Beans_Tax_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('tax_id'),
			'name' => $this->request->post('name'),
			'account_id' => $this->request->post('account_id'),
			'percent' => ( $this->request->post('percent') / 100 ),
			'license' => $this->request->post('license'),
			'authority' => $this->request->post('authority'),
			'address1' => $this->request->post('address1'),
			'address2' => $this->request->post('address2'),
			'city' => $this->request->post('city'),
			'state' => $this->request->post('state'),
			'zip' => $this->request->post('zip'),
			'country' => $this->request->post('country'),
			'date_due' => $this->request->post('date_due'),
			'date_due_months_increment' => $this->request->post('date_due_months_increment'),
		)));
		$tax_update_result = $tax_update->execute();

		if( ! $tax_update_result->success )
			return $this->_return_error($this->_beans_result_get_error($tax_update_result));

		$html = new View_Partials_Taxes_Taxes_Tax;
		$html->tax = $tax_update_result->data->tax;

		$tax_update_result->data->tax->html = $html->render();

		$this->_return_object->data->tax = $tax_update_result->data->tax;
	}

	public function action_taxsearch()
	{
		$term = $this->request->post('term');

		$tax_search = new Beans_Tax_Search($this->_beans_data_auth((object)array(
			'search_and' => FALSE,
			'search_code' => $term,
			'search_name' => $term,
		)));
		$tax_search_result = $tax_search->execute();

		if( ! $tax_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($tax_search_result));

		foreach( $tax_search_result->data->taxes as $index => $tax ) 
		{
			$html = new View_Partials_Taxes_Taxes_Tax;
			$html->tax = $tax;

			$tax_search_result->data->taxes[$index]->html = $html->render();
		}

		$this->_return_object->data->taxes = $tax_search_result->data->taxes;
	}

	public function action_taxload()
	{
		$tax_lookup = new Beans_Tax_Lookup($this->_beans_data_auth((object)array(
			'id' => $this->request->post('tax_id'),
		)));
		$tax_lookup_result = $tax_lookup->execute();

		if( ! $tax_lookup_result->success )
			return $this->_return_error($this->_beans_result_get_error($tax_lookup_result));

		$this->_return_object->data->tax = $tax_lookup_result->data->tax;
	}

	public function action_usercreate()
	{
		$auth_user_create = new Beans_Auth_User_Create($this->_beans_data_auth((object)array(
			'name' => $this->request->post('name'),
			'email' => $this->request->post('email'),
			'role_id' => $this->request->post('role_id'),
			'password' => $this->request->post('password'),
		)));
		$auth_user_create_result = $auth_user_create->execute();

		if( ! $auth_user_create_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_user_create_result));

		$html = new View_Partials_Setup_Users_User;
		$html->user = $auth_user_create_result->data->user;

		$auth_user_create_result->data->user->html = $html->render();

		$this->_return_object->data->user = $auth_user_create_result->data->user;
	}

	public function action_userupdate()
	{
		$auth_user_update = new Beans_Auth_User_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('user_id'),
			'name' => $this->request->post('name'),
			'email' => $this->request->post('email'),
			'role_id' => $this->request->post('role_id'),
			( $this->request->post('password') ? 'password' : 'nopassupdate' ) => $this->request->post('password'),
		)));
		$auth_user_update_result = $auth_user_update->execute();

		if( ! $auth_user_update_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_user_update_result));

		$html = new View_Partials_Setup_Users_User;
		$html->user = $auth_user_update_result->data->user;

		$auth_user_update_result->data->user->html = $html->render();

		$this->_return_object->data->user = $auth_user_update_result->data->user;
	}

	public function action_userdelete()
	{
		$auth_user_delete = new Beans_Auth_User_Delete($this->_beans_data_auth((object)array(
			'id' => $this->request->post('user_id'),
		)));
		$auth_user_delete_result = $auth_user_delete->execute();

		if( ! $auth_user_delete_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_user_delete_result));
	}

	public function action_apibuild()
	{
		$random_password = $this->_generate_random_string();
		$api_email = "api.access@beans.instance";

		// Create User
		$auth_user_create = new Beans_Auth_User_Create($this->_beans_data_auth((object)array(
			'name' => "API Access",
			'email' => $api_email,
			'role_code' => "api",
			'password' => $random_password,
		)));
		$auth_user_create_result = $auth_user_create->execute();

		if( ! $auth_user_create_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_user_create_result));

		$auth_login = new Beans_Auth_Login((object)array(
			'email' => $api_email,
			'password' => $random_password,
		));
		$auth_login_result = $auth_login->execute();

		if( ! $auth_login_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_login_result));

		$html = new View_Partials_Setup_Users_Api;
		$html->auth = $auth_login_result->data->auth;

		$auth_login_result->data->auth->html = $html->render();

		$this->_return_object->data->auth = $auth_login_result->data->auth;
	}

	public function action_apiregen()
	{
		$user_id = $this->request->post('auth_uid');
		$random_password = $this->_generate_random_string();

		// Create User
		$auth_user_update = new Beans_Auth_User_Update($this->_beans_data_auth((object)array(
			'id' => $user_id,
			'password' => $random_password,
		)));
		$auth_user_update_result = $auth_user_update->execute();

		if( ! $auth_user_update_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_user_update_result));

		$auth_login = new Beans_Auth_Login((object)array(
			'email' => $auth_user_update_result->data->user->email,
			'password' => $random_password,
		));
		$auth_login_result = $auth_login->execute();

		if( ! $auth_login_result->success )
			return $this->_return_error($this->_beans_result_get_error($auth_login_result));

		$html = new View_Partials_Setup_Users_Api;
		$html->auth = $auth_login_result->data->auth;

		$auth_login_result->data->auth->html = $html->render();

		$this->_return_object->data->auth = $auth_login_result->data->auth;
	}

	public function action_calibratestartdate()
	{
		set_time_limit(60 * 10);
		ini_set('memory_limit', '256M');

		// Get First Transaction and Last Transaction
		// then simple bisection...
		$date_start = NULL;
		$date_end = NULL;

		$account_transaction_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'sort_by' => 'oldest',
			'page_size' => 1,
		)));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( ! $account_transaction_search_result->success ||
			! count($account_transaction_search_result->data->transactions) )
			return $this->_return_error('Error getting starting transaction: '.$account_transaction_search_result->error);

		$date_start = $account_transaction_search_result->data->transactions[0]->date;

		$account_transaction_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'sort_by' => 'newest',
			'page_size' => 1,
		)));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( ! $account_transaction_search_result->success ||
			! count($account_transaction_search_result->data->transactions) )
			return $this->_return_error('Error getting ending transaction: '.$account_transaction_search_result->error);

		$date_end = $account_transaction_search_result->data->transactions[0]->date;


		while( $date_start != $date_end &&
			   ( strtotime($date_end) - strtotime($date_start) ) > ( 60 * 60 * 24 * 2 ) ) 
		{
			$report_balancecheck = new Beans_Report_Balancecheck($this->_beans_data_auth((object)array(
				'date' => date("Y-m-d", strtotime($date_start) + floor( ( strtotime($date_end) - strtotime($date_start) ) / 2) ),
			)));
			$report_balancecheck_result = $report_balancecheck->execute();

			if( ! $report_balancecheck_result->success )
				return $this->_return_error('Error finding first good date: '.$report_balancecheck_result->error);

			if( $report_balancecheck_result->data->balanced )
				$date_start = $report_balancecheck_result->data->date;
			else
				$date_end = $report_balancecheck_result->data->date;
		}

		$this->_return_object->data->date = $date_start;

		$setup_company_list = new Beans_Setup_Company_List($this->_beans_data_auth());
		$setup_company_list_result = $setup_company_list->execute();

		if( isset($setup_company_list_result->data->settings) &&
			isset($setup_company_list_result->data->settings->calibrate_date_next) &&
			$setup_company_list_result->data->settings->calibrate_date_next && 
			strtotime($setup_company_list_result->data->settings->calibrate_date_next) < strtotime($this->_return_object->data->date) )
			$this->_return_object->data->date = $setup_company_list_result->data->settings->calibrate_date_next;
	}

	public function action_calibratedate()
	{
		$date = $this->request->post('date');

		if( ! $date OR 
			$date != date("Y-m-d",strtotime($date)) )
			return $this->_return_error('Invalid date provided: '.$date.' expected YYYY-MM-DD');

		// This can take a while.
		set_time_limit(60 * 10);
		ini_set('memory_limit', '256M');

		$customer_invoice_updatebatch = new Beans_Customer_Sale_Invoice_Updatebatch($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$customer_invoice_updatebatch_result = $customer_invoice_updatebatch->execute();

		if( ! $customer_invoice_updatebatch_result->success )
			return $this->_return_error('Error updating customer invoices: '.$customer_invoice_updatebatch_result->error);

		$customer_cancel_updatebatch = new Beans_Customer_Sale_Cancel_Updatebatch($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$customer_cancel_updatebatch_result = $customer_cancel_updatebatch->execute();

		if( ! $customer_cancel_updatebatch_result->success )
			return $this->_return_error('Error updating cancelled customer sales: '.$customer_cancel_updatebatch_result->error);

		$customer_payment_calibratebatch = new Beans_Customer_Payment_Calibratebatch($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$customer_payment_calibratebatch_result = $customer_payment_calibratebatch->execute();

		if( ! $customer_payment_calibratebatch_result->success )
			return $this->_return_error('Error updating customer payments: '.$customer_payment_calibratebatch_result->error);

		$this->_return_object->data->date_next = date("Y-m-d",strtotime($date." +1 Day"));		

		$account_transaction_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'sort_by' => 'newest',
			'page_size' => 1,
		)));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( ! $account_transaction_search_result->success ||
			! count($account_transaction_search_result->data->transactions) )
			return $this->_return_error('Error getting ending transaction: '.$account_transaction_search_result->error);

		$date_end = $account_transaction_search_result->data->transactions[0]->date;

		if( strtotime($date_end) < strtotime($this->_return_object->data->date_next) )
		{
			$this->_return_object->data->date_next = FALSE;

			// Calibrate Account Balances
			$account_search = new Beans_Account_Search($this->_beans_data_auth());
			$account_search_result = $account_search->execute();

			if( ! $account_search_result->success )
				return $this->_return_error('Could not look up accounts for final calibration.');

			$success = '';

			foreach( $account_search_result->data->accounts as $account )
			{
				$account_calibrate = new Beans_Account_Calibrate($this->_beans_data_auth((object)array('id' => $account->id)));
				$account_calibrate_result = $account_calibrate->execute();

				if( ! $account_calibrate_result->success )
					return $this->_return_error('Error calibrating account balance for '.$account->name.'.');
			}
		}

		// Update our latest date in case user pauses and comes back later.
		$setup_company_update = new Beans_Setup_Company_Update($this->_beans_data_auth((object)array(
			'settings' => array(
				'calibrate_date_next' => $this->_return_object->data->date_next,
			)
		)));
		$setup_company_update_result = $setup_company_update->execute();
	}

	private function _generate_random_string()
	{
		$string = '';
		for( $i = 0; $i <= rand(1000,2000); $i++ ) {
			$string .= substr(md5(rand()*time()*rand()),rand(0,31),1);
		}

		return md5($string).md5(strrev($string));
	}

}