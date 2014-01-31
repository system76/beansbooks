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

class Controller_Dash extends Controller_View {

	function before()
	{

		parent::before();

		// Check for tabs and set if necessary.
		if( ! Session::instance()->get('tab_section') )
		{
			Session::instance()->set('tab_section',$this->request->controller());
			
			$tab_links = array();

			$tab_links[] = array(
				'url' => '/dash',
				'text' => 'Overview',
				'removable' => FALSE,
				'text_short' => 'Over',
			);

			Session::instance()->set('tab_links',$tab_links);
		}

	}

	public function action_index()
	{
		
		$report_cash = new Beans_Report_Cash($this->_beans_data_auth((object)array(
			'date' => date("Y-m-d"),
		)));
		$report_cash_result = $report_cash->execute();

		if( $this->_beans_result_check($report_cash_result) )
			$this->_view->report_cash_result = $report_cash_result;
		
		// Past Due Sales
		$sales_past_due = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
			'past_due' => TRUE,
			'sort_by' => 'duelatest',
			'page_size' => 100,
		)));
		$sales_past_due_result = $sales_past_due->execute();

		if( $this->_beans_result_check($sales_past_due_result) )
			$this->_view->sales_past_due_result = $sales_past_due_result;

		// Not Sent Sales
		$sales_not_invoiced = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
			'invoiced' => FALSE,
			'has_balance' => TRUE,
			'sort_by' => 'oldest',
			'page_size' => 100,
			'date_created_before' => date("Y-m-d",strtotime("-30 Days")),
		)));
		$sales_not_invoiced_result = $sales_not_invoiced->execute();

		if( $this->_beans_result_check($sales_not_invoiced_result) )
			$this->_view->sales_not_invoiced_result = $sales_not_invoiced_result;

		// Past Due Purchases
		$purchases_past_due = new Beans_Vendor_Purchase_Search($this->_beans_data_auth((object)array(
			'past_due' => TRUE,
			'sort_by' => 'duelatest',
			'page_size' => 100,
		)));
		$purchases_past_due_result = $purchases_past_due->execute();

		if( $this->_beans_result_check($purchases_past_due_result) )
			$this->_view->purchases_past_due_result = $purchases_past_due_result;

		// Not Sent Sales
		$purchases_not_invoiced = new Beans_Vendor_Purchase_Search($this->_beans_data_auth((object)array(
			'invoiced' => FALSE,
			'has_balance' => TRUE,
			'sort_by' => 'oldest',
			'page_size' => 100,
			'date_created_before' => date("Y-m-d",strtotime("-30 Days")),
		)));
		$purchases_not_invoiced_result = $purchases_not_invoiced->execute();

		if( $this->_beans_result_check($purchases_not_invoiced_result) )
			$this->_view->purchases_not_invoiced_result = $purchases_not_invoiced_result;
		
		// Determine any messages
		$this->_view->messages = $this->_dash_index_messages();

	}

	public function action_balance()
	{
		$date = date("Y-m-d");
		if( $this->request->post('date') )
			$date =$this->request->post('date');

		$report_balance = new Beans_Report_Balance($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$report_balance_result = $report_balance->execute();

		if( $this->_beans_result_check($report_balance_result) )
			$this->_view->report_balance_result = $report_balance_result;

		if( $this->request->post('report-balance-zero-toggle') )
			$this->_view->show_zero = TRUE;

		$this->_action_tab_name = "Balance Sheet";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_income()
	{
		$date_start = date("Y")."-01-01";
		$date_end = date("Y-m-d");

		if( $this->request->post('date_start') )
			$date_start =$this->request->post('date_start');

		if( $this->request->post('date_end') )
			$date_end =$this->request->post('date_end');

		$report_income = new Beans_Report_Income($this->_beans_data_auth((object)array(
			'date_start' => $date_start,
			'date_end' => $date_end,
		)));
		$report_income_result = $report_income->execute();

		if( $this->_beans_result_check($report_income_result) )
			$this->_view->report_income_result = $report_income_result;

		if( $this->request->post('report-balance-zero-toggle') )
			$this->_view->show_zero = TRUE;

		$this->_action_tab_name = "Income Statement";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_ledger()
	{
		$date_start = date("Y-m-d",strtotime("-30 Days"));
		$date_end = date("Y-m-d");
		$account_id = $this->request->post('account_id');

		if( $this->request->post('date_start') )
			$date_start = $this->request->post('date_start');

		if( $this->request->post('date_end') )
			$date_end = $this->request->post('date_end');

		if( $account_id )
		{
			$report_ledger = new Beans_Report_Ledger($this->_beans_data_auth((object)array(
				'account_id' => $account_id,
				'date_start' => $date_start,
				'date_end' => $date_end,
			)));
			$report_ledger_result = $report_ledger->execute();

			if( $this->_beans_result_check($report_ledger_result) )
				$this->_view->report_ledger_result = $report_ledger_result;
		}

		$this->_action_tab_name = "General Ledger";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_sales()
	{
		$date_start = date("Y")."-01-01";
		$date_end = date("Y-m-d");
		$interval = "month";
		
		if( $this->request->post('date_start') )
			$date_start = $this->request->post('date_start');

		if( $this->request->post('date_end') )
			$date_end = $this->request->post('date_end');

		if( $this->request->post('interval') ) 
			$interval = $this->request->post('interval');

		$report_sales = new Beans_Report_Sales($this->_beans_data_auth((object)array(
			'date_start' => $date_start,
			'date_end' => $date_end,
			'interval' => $interval,
		)));
		$report_sales_result = $report_sales->execute();

		if( $this->_beans_result_check($report_sales_result) )
			$this->_view->report_sales_result = $report_sales_result;

		$this->_action_tab_name = "Sales Report";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_budget()
	{
		$date_start = date("Y")."-01-01";
		$months = 12;

		if( $this->request->post('date_start') )
			$date_start = date("Y-m",strtotime($this->request->post('date_start')))."-01";

		if( $this->request->post('months') AND 
			$this->request->post('months') <= 12 AND
			$this->request->post('months') >= 1 )
			$months = $this->request->post('months');
		
		// Last day in month = t
		$date_end = date("Y-m-t",strtotime($date_start." +".( $months - 1 )." Months"));

		$report_budget = new Beans_Report_Budget($this->_beans_data_auth((object)array(
			'date_start' => $date_start,
			'date_end' => $date_end,
		)));
		$report_budget_result = $report_budget->execute();

		if( $this->_beans_result_check($report_budget_result) )
			$this->_view->report_budget_result = $report_budget_result;
		
		$this->_view->months = $months;
		
		if( $this->request->post('report-budget-zero-toggle') )
			$this->_view->show_zero = TRUE;

		$this->_action_tab_name = "Budget Detail";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_payables()
	{
		$vendor_id = FALSE;

		if( $this->request->post('vendor_id') AND 
			$this->request->post('vendor_id') != "false" )
			$vendor_id = $this->request->post('vendor_id');

		$days_late_minimum = FALSE;

		if( $this->request->post('days_late_minimum') )
			$days_late_minimum = $this->request->post('days_late_minimum');

		$report_payables = new Beans_Report_Payables($this->_beans_data_auth((object)array(
			'vendor_id' => $vendor_id,
			'days_late_minimum' => $days_late_minimum,
			'date' => $this->request->post('date'),
		)));
		$report_payables_result = $report_payables->execute();

		if( $this->_beans_result_check($report_payables_result) )
			$this->_view->report_payables_result = $report_payables_result;

		$this->_view->vendor_id = $vendor_id;

		$this->_action_tab_name = "Aged Payables";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_receivables()
	{
		$customer_id = FALSE;

		if( $this->request->post('customer_id') AND 
			$this->request->post('customer_id') != "false" )
			$customer_id = $this->request->post('customer_id');

		$days_late_minimum = FALSE;

		if( $this->request->post('days_late_minimum') )
			$days_late_minimum = $this->request->post('days_late_minimum');

		$report_receivables = new Beans_Report_Receivables($this->_beans_data_auth((object)array(
			'customer_id' => $customer_id,
			'days_late_minimum' => $days_late_minimum,
			'date' => $this->request->post('date'),
		)));
		$report_receivables_result = $report_receivables->execute();

		if( $this->_beans_result_check($report_receivables_result) )
			$this->_view->report_receivables_result = $report_receivables_result;

		$this->_view->customer_id = $customer_id;

		$this->_action_tab_name = "Aged Receivables";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_purchaseorders()
	{
		$vendor_id = FALSE;

		if( $this->request->post('vendor_id') AND 
			$this->request->post('vendor_id') != "false" )
			$vendor_id = $this->request->post('vendor_id');

		$days_late_minimum = FALSE;

		if( $this->request->post('days_late_minimum') )
			$days_late_minimum = $this->request->post('days_late_minimum');

		$report_purchaseorders = new Beans_Report_Purchaseorders($this->_beans_data_auth((object)array(
			'vendor_id' => $vendor_id,
			'days_late_minimum' => $days_late_minimum,
		)));
		$report_purchaseorders_result = $report_purchaseorders->execute();

		if( $this->_beans_result_check($report_purchaseorders_result) )
			$this->_view->report_purchaseorders_result = $report_purchaseorders_result;

		$this->_view->vendor_id = $vendor_id;

		$this->_action_tab_name = "Outstanding Purchase Orders";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_salesorders()
	{
		$customer_id = FALSE;

		if( $this->request->post('customer_id') AND 
			$this->request->post('customer_id') != "false" )
			$customer_id = $this->request->post('customer_id');

		$days_old_minimum = FALSE;

		if( $this->request->post('days_old_minimum') )
			$days_old_minimum = $this->request->post('days_old_minimum');

		$report_salesorders = new Beans_Report_Salesorders($this->_beans_data_auth((object)array(
			'customer_id' => $customer_id,
			'days_old_minimum' => $days_old_minimum,
		)));
		$report_salesorders_result = $report_salesorders->execute();

		if( $this->_beans_result_check($report_salesorders_result) )
			$this->_view->report_salesorders_result = $report_salesorders_result;

		$this->_view->customer_id = $customer_id;

		$this->_action_tab_name = "Outstanding Sales Orders";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_customer()
	{
		$customer_id = FALSE;
		$date_start = date("Y")."-01-01";
		$date_end = date("Y-m-d");
		
		if( $this->request->post('date_start') )
			$date_start = $this->request->post('date_start');

		if( $this->request->post('date_end') )
			$date_end = $this->request->post('date_end');

		if( $this->request->post('customer_id') AND 
			$this->request->post('customer_id') != "false" )
			$customer_id = $this->request->post('customer_id');

		$report_customer = new Beans_Report_Customer($this->_beans_data_auth((object)array(
			'customer_id' => $customer_id,
			'date_start' => $date_start,
			'date_end' => $date_end,
		)));
		$report_customer_result = $report_customer->execute();

		if( $this->_beans_result_check($report_customer_result) )
			$this->_view->report_customer_result = $report_customer_result;

		$this->_view->customer_id = $customer_id;

		$this->_action_tab_name = "Income by Customer";
		$this->_action_tab_uri = '/'.$this->request->uri();

	}

	public function action_vendor()
	{
		$vendor_id = FALSE;
		$date_start = date("Y")."-01-01";
		$date_end = date("Y-m-d");
		
		if( $this->request->post('date_start') )
			$date_start = $this->request->post('date_start');

		if( $this->request->post('date_end') )
			$date_end = $this->request->post('date_end');

		if( $this->request->post('vendor_id') AND 
			$this->request->post('vendor_id') != "false" )
			$vendor_id = $this->request->post('vendor_id');

		$report_vendor = new Beans_Report_Vendor($this->_beans_data_auth((object)array(
			'vendor_id' => $vendor_id,
			'date_start' => $date_start,
			'date_end' => $date_end,
		)));
		$report_vendor_result = $report_vendor->execute();

		if( $this->_beans_result_check($report_vendor_result) )
			$this->_view->report_vendor_result = $report_vendor_result;

		$this->_view->vendor_id = $vendor_id;

		$this->_action_tab_name = "Cost by Vendor";
		$this->_action_tab_uri = '/'.$this->request->uri();

	}

	public function action_taxes()
	{
		$tax_id = FALSE;
		$date_start = date("Y-m")."-01";
		$date_end = date("Y-m-d");

		if( $this->request->post('date_start') )
			$date_start = $this->request->post('date_start');

		if( $this->request->post('date_end') )
			$date_end = $this->request->post('date_end');

		if( $this->request->post('tax_id') )
			$tax_id = $this->request->post('tax_id');

		if( $tax_id )
		{
			$report_taxes = new Beans_Report_Taxes($this->_beans_data_auth((object)array(
				'tax_id' => $tax_id,
				'date_start' => $date_start,
				'date_end' => $date_end,
			)));
			$report_taxes_result = $report_taxes->execute();

			if( $this->_beans_result_check($report_taxes_result) )
				$this->_view->report_taxes_result = $report_taxes_result;
		}
		else
		{
			$this->_view->default_date_start = $date_start;
			$this->_view->default_date_end = $date_end;
		}

		$this->_action_tab_name = "Sales Tax";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	public function action_trial()
	{
		$date = date("Y-m-d");

		if( $this->request->post('date') )
			$date = $this->request->post('date');

		$report_trial = new Beans_Report_Trial($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$report_trial_result = $report_trial->execute();

		if( $this->_beans_result_check($report_trial_result) )
			$this->_view->report_trial_result = $report_trial_result;

		$this->_action_tab_name = "Trial Balance";
		$this->_action_tab_uri = '/'.$this->request->uri();
	}

	private function _dash_index_messages()
	{
		// V2Item - Queue these messages up more globally ( or with a database check ).
		$messages = array();
		
		$company_settings = new Beans_Setup_Company_List($this->_beans_data_auth());
		$company_settings_result = $company_settings->execute();

		if( ! $company_settings_result->success )
			return $messages;

		// Two Checks right now
		$messages = array_merge($messages,$this->_dash_index_messages_setup($company_settings_result));
		$messages = array_merge($messages,$this->_dash_index_messages_chart($company_settings_result));
		$messages = array_merge($messages,$this->_dash_index_messages_startingbalance());
		$messages = array_merge($messages,$this->_dash_index_messages_taxes());
		$messages = array_merge($messages,$this->_dash_index_messages_closebooks($company_settings_result));
		
		if( count($messages) == 0 ) 
			return FALSE;

		return $messages;
	}

	private function _dash_index_messages_setup($company_settings_result)
	{
		if( ! $company_settings_result->success )
			return array();

		if( ! isset($company_settings_result->data->settings->company_name) OR 
			! $company_settings_result->data->settings->company_name )
		{
			return array(
				(object)array(
					'title' => "Setup",
					'text' =>  "Welcome to BeansBooks! To get started, 
								add your company information and sales tax jurisdictions.",
					'actions' => array(
						(object)array(
							'text' => "Go to Setup",
							'url' => "/setup",
						)
					)
				),
			);
		}

		return array();
	}

	private function _dash_index_messages_chart($company_settings_result)
	{
		if( ! $company_settings_result->success )
			return array();

		if( ! isset($company_settings_result->data->settings->chart_setup) OR 
			! $company_settings_result->data->settings->chart_setup )
		{
			return array(
				(object)array(
					'title' => "Chart of Accounts",
					'text' =>  "Next, adjust your Chart of Accounts. BeansBooks already 
								includes common accounts but you might want to add, 
								remove or rename accounts to fit your business.",
					'actions' => array(
						(object)array(
							'text' => "Setup Chart of Accounts",
							'url' => "/accounts/setup/",
						)
					)
				),
			);
		}

		return array();
	}

	private function _dash_index_messages_startingbalance()
	{
		$account_transaction_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'search_code' => "STARTINGBAL",
		)));
		$account_transaction_search_result = $account_transaction_search->execute();
		
		// V2Item - Consider error'ing this.
		if( ! $account_transaction_search_result->success ) 
			return array();

		if( $account_transaction_search_result->data->total_results == 0 )
		{
			return array(
				(object)array(
					'title' => "Starting Balance",
					'text' => "Finally, add starting balances to your accounts. 
								Starting balances are copied from an existing accounting 
								program or entered for the first time if you're starting 
								a new business.",
					'actions' => array(
						(object)array(
							'text' => "Enter Starting Balance",
							'url' => "/accounts/startingbalance",
						),
					),
				)
			);
		}

		return array();
	}

	private function _dash_index_messages_closebooks($company_settings_result)
	{
		$account_closebooks_check = new Beans_Account_Closebooks_Check($this->_beans_data_auth());
		$account_closebooks_check_result = $account_closebooks_check->execute();

		if( ! $account_closebooks_check_result->success ||
			! $account_closebooks_check_result->data->ready ||
			! $account_closebooks_check_result->data->fye_date )
			return array();

		$fye_date = $account_closebooks_check_result->data->fye_date;

		// This is a really ugly / special use case.

		$text = '';
		$text .= "You have now passed your fiscal year end. You close books
					after completing tax preparation and entering end of year
					adjustments. Click Close Books when you're ready.
				";

		/*
		$text .= '<div class="text-bold bump-down-more">Closing Date</div>';
		$text .= '<div class="bump-down"><div class="select" style="width: 200px;"><select name="date">';

		for( $i = 1; $i < 13; $i++ ) {
			$d = date("Y-m-t",strtotime($fye_date_next_day.' -'.$i.' Months'));
			$text .= '<option value='.$d.'>'.$d.'</option>';
		}
		$text .= '</select></div></div>';
		*/
		
		$account_search = new Beans_Account_Search($this->_beans_data_auth());
		$account_search_result = $account_search->execute();


		$text .= '<div class="clear"></div>';

		$text .= '<div class="text-bold bump-down-more float-left" style="width: 180px;">Fiscal Year End:</div>';
		$text .= '<div class="bump-down-more float-left" style="width: 215px;">'.$fye_date.'</div>';
		$text .= '<div class="clear"></div>';
		
		$text .= '<div class="text-bold bump-down-more float-left" style="width: 180px;">Closing Transfer Account:</div>';
		$text .= '<div class="bump-down float-left" style="width: 215px;"><div class="select" style="width: 200px;"><select name="transfer_account_id">';
		foreach( $account_search_result->data->accounts as $account ) {
			if( isset($account->type) AND 
				isset($account->type->type) AND 
				strtolower($account->type->type) == "equity" )
				$text .= '<option value="'.$account->id.'" '.( stripos($account->name,'retained') !== FALSE ? 'selected="selected"' : '' ).' >'.$account->name.'</option>';
		}
		$text .= '</select></div></div>';
		$text .= '<div class="clear"></div>';



		$text .= '<input type="hidden" name="date" value="'.$fye_date.'">';

		return array(
			(object)array(
				'title' => 'Year End - Close Books',
				'text' => $text,
				'actions' => array(
					(object)array(
						'text' => "Close Books",
						'id' => "dash-index-close-books-submit",
					),
				),
			),
		);
	}
	
	private function _dash_index_messages_taxes()
	{
		$taxes_search = new Beans_Tax_Search($this->_beans_data_auth());
		$taxes_search_result = $taxes_search->execute();

		// V2Item - Consider error'ing
		if( ! $taxes_search_result->success )
			return array();

		$messages = array();
		foreach( $taxes_search_result->data->taxes as $tax )
		{
			if( strtotime($tax->date_due.' -10 Days') <= strtotime(date("Y-m-d")) )
			{
				$messages[] = (object)array(
					'title' => $tax->name." Due on ".$tax->date_due,
					'text' => $tax->name." payable to the ".$tax->authority." is due ".$tax->date_due.".",
					'actions' => array(
						(object)array(
							'text' => "Make a Payment",
							'url' => "/vendors/taxpayments/new/".$tax->id,
						),
					),
				);
			}
		}

		return $messages;
	}

}