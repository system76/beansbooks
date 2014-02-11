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

class Controller_Vendors extends Controller_View {

	protected $_required_role_permissions = array(
		'default' => 'vendor_read',
	);

	function before()
	{
		parent::before();

		// Check for tabs and set if necessary.
		if( ! Session::instance()->get('tab_section') )
		{
			Session::instance()->set('tab_section',$this->request->controller());
			
			$tab_links = array();

			$tab_links[] = array(
				'url' => '/vendors/expenses',
				'text' => 'Expenses',
				'removable' => FALSE,
				'text_short' => 'Exp',
			);

			$tab_links[] = array(
				'url' => '/vendors/purchases',
				'text' => 'Purchase Orders',
				'removable' => FALSE,
				'text_short' => 'POs',
			);

			$tab_links[] = array(
				'url' => '/vendors/invoices',
				'text' => 'Age Invoices',
				'removable' => FALSE,
				'text_short' => 'Inv',
			);

			$tab_links[] = array(
				'url' => '/vendors/payments',
				'text' => 'Pay Invoices',
				'removable' => FALSE,
				'text_short' => 'Pay',
			);

			$tab_links[] = array(
				'url' => '/vendors/taxpayments',
				'text' => 'Remit Sales Tax',
				'removable' => FALSE,
				'text_short' => 'Tax',
			);
			
			$tab_links[] = array(
				'url' => '/vendors/printchecks',
				'text' => "Check Print Queue",
				'removable' => FALSE,
				'check_print_queue' => TRUE,
				'text_short' => 'Print',
			);

			$tab_links[] = array(
				'url' => '/vendors/vendors',
				'text' => 'Vendors',
				'removable' => FALSE,
				'text_short' => 'Ven',
			);


			Session::instance()->set('tab_links',$tab_links);
		}

		// Repetitive - but three laws good.
		$this->_update_check_print_queue_tab();

	}

	public function action_index()
	{
		// Forward to invoices.	
		$this->request->redirect("/vendors/expenses/");
	}

	public function action_expenses()
	{
		$expense_id = $this->request->param('id');

		$vendor_expense_search = new Beans_Vendor_Expense_Search($this->_beans_data_auth((object)array(
			'page_size' => 20,
			'sort_by' => 'newest',
		)));
		$vendor_expense_search_result = $vendor_expense_search->execute();

		if( $this->_beans_result_check($vendor_expense_search_result) )
			$this->_view->vendor_expense_search_result = $vendor_expense_search_result;

		$this->_view->requested_expense_id = $expense_id;
		$this->_view->force_current_uri = "/vendors/expenses";
	}

	public function action_purchases()
	{
		$purchase_id = $this->request->param('id');

		$vendor_purchase_search = new Beans_Vendor_Purchase_Search($this->_beans_data_auth((object)array(
			'page_size' => 20,
			'sort_by' => 'newest',
		)));
		$vendor_purchase_search_result = $vendor_purchase_search->execute();

		if( $this->_beans_result_check($vendor_purchase_search_result) )
			$this->_view->vendor_purchase_search_result = $vendor_purchase_search_result;

		$this->_view->requested_purchase_id = $purchase_id;
		$this->_view->force_current_uri = "/vendors/purchases";
	}

	public function action_invoices()
	{
		// Nada.  The wonders of AJAX.
	}

	public function action_payments()
	{
		$payment_id = $this->request->param('id');

		$vendor_payment_search = new Beans_Vendor_Payment_Search($this->_beans_data_auth((object)array(
			'page_size' => 5,
			'sort_by' => 'newest',
		)));
		$vendor_payment_search_result = $vendor_payment_search->execute();

		if( $this->_beans_result_check($vendor_payment_search_result) )
			$this->_view->vendor_payment_search_result = $vendor_payment_search_result;

		$this->_view->requested_payment_id = $payment_id;
		$this->_view->force_current_uri = "/vendors/payments";
	}

	public function action_vendors()
	{
		$vendor_search = new Beans_Vendor_Search($this->_beans_data_auth((object)array(
			'page_size' => 5,
			'sort_by' => 'newest',
		)));
		$vendor_search_result = $vendor_search->execute();

		if( $this->_beans_result_check($vendor_search_result) )
			$this->_view->vendor_search_result = $vendor_search_result;
	}
	
	public function action_vendor()
	{
		$vendor_id = $this->request->param('id');

		$vendor_lookup = new Beans_Vendor_Lookup($this->_beans_data_auth((object)array(
			'id' => $vendor_id
		)));
		$vendor_lookup_result = $vendor_lookup->execute();
		
		if( $this->_beans_result_check($vendor_lookup_result) )
		{
			$this->_view->vendor_lookup_result = $vendor_lookup_result;
			$this->_action_tab_name = $vendor_lookup_result->data->vendor->company_name;
			$this->_action_tab_uri = '/'.$this->request->uri();

			$vendor_address_search = new Beans_Vendor_Address_Search($this->_beans_data_auth((object)array(
				'search_vendor_id' => $vendor_id,
			)));
			$vendor_address_search_result = $vendor_address_search->execute();

			if( $this->_beans_result_check($vendor_address_search_result) )
				$this->_view->vendor_address_search_result = $vendor_address_search_result;

			$account_transaction_search = new Beans_Account_Transaction_Search_Vendor($this->_beans_data_auth((object)array(
				'vendor_id' => $vendor_id,
				'page_size' => 5,
				'sort_by' => 'newest',
			)));
			$account_transaction_search_result = $account_transaction_search->execute();
			
			if( $this->_beans_result_check($account_transaction_search_result) )
			{
				foreach( $account_transaction_search_result->data->transactions as $index => $transaction )
				{
					if( isset($transaction->form) AND
						$transaction->form AND
						isset($transaction->form->id) AND 
						$transaction->form->type == "expense" )
					{
						$vendor_expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
							'id' => $transaction->form->id,
						)));
						$vendor_expense_lookup_result = $vendor_expense_lookup->execute();

						if( $this->_beans_result_check($vendor_expense_lookup_result) )
							$account_transaction_search_result->data->transactions[$index]->expense = $vendor_expense_lookup_result->data->expense;
						
					}
					else if( $transaction->payment AND 
							 $transaction->payment == "vendor" )
					{
						$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
							'id' => $transaction->id,
						)));
						$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

						if( $this->_beans_result_check($vendor_payment_lookup_result) )
							$account_transaction_search_result->data->transactions[$index]->payment = $vendor_payment_lookup_result->data->payment;

					}
					else if( $transaction->tax_payment AND 
							 isset($transaction->tax_payment->id) )
					{
						$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
							'id' => $transaction->tax_payment->id,
						)));
						$tax_payment_lookup_result = $tax_payment_lookup->execute();

						if( $this->_beans_result_check($tax_payment_lookup_result) )
							$account_transaction_search_result->data->transactions[$index]->taxpayment = $tax_payment_lookup_result->data->payment;
					}
				}
			}
			
			$this->_view->noprintchecks = TRUE;

			if( $this->_beans_result_check($account_transaction_search_result) )
				$this->_view->account_transaction_search_result = $account_transaction_search_result;
		}
	}

	public function action_taxpayments()
	{
		$payment_id = $this->request->param('id');
		$tax_id = NULL;

		if( $payment_id == "new" ) {
			$payment_id = NULL;
			$tax_id = $this->request->param('code');
		}

		$tax_payment_search = new Beans_Tax_Payment_Search($this->_beans_data_auth((object)array(
			'page_size' => 5,
			'sort_by' => 'newest',
		)));
		$tax_payment_search_result = $tax_payment_search->execute();

		if( $this->_beans_result_check($tax_payment_search_result) )
			$this->_view->tax_payment_search_result = $tax_payment_search_result;

		if( $tax_id )
		{
			$tax_lookup = new Beans_Tax_Lookup($this->_beans_data_auth((object)array(
				'id' => $tax_id,
			)));
			$tax_lookup_result = $tax_lookup->execute();

			if( $this->_beans_result_check($tax_lookup_result) )
			{
				$this->_view->requested_tax_id = $tax_lookup_result->data->tax->id;
				$this->_view->requested_tax_name = $tax_lookup_result->data->tax->name;
			}
		}

		$this->_view->requested_payment_id = $payment_id;
	}

	public function action_printchecks()
	{
		$check_print_queue = Session::instance()->get('check_print_queue');

		$this->_view->expenses = array();
		$this->_view->payments = array();
		$this->_view->taxpayments = array();

		if( $check_print_queue )
		{
			foreach( $check_print_queue->expense_ids as $expense_id => $include )
			{
				$expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
					'id' => $expense_id,
				)));
				$expense_lookup_result = $expense_lookup->execute();

				if( $this->_beans_result_check($expense_lookup_result) )
					$this->_view->expenses[] = $expense_lookup_result->data->expense;

			}
			
			foreach( $check_print_queue->payment_ids as $payment_id => $include )
			{
				$payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $payment_id,
				)));
				$payment_lookup_result = $payment_lookup->execute();

				if( $this->_beans_result_check($payment_lookup_result) )
					$this->_view->payments[] = $payment_lookup_result->data->payment;
			}
			
			foreach( $check_print_queue->taxpayment_ids as $taxpayment_id => $include )
			{
				$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $taxpayment_id,
				)));
				$tax_payment_lookup_result = $tax_payment_lookup->execute();

				if( $this->_beans_result_check($tax_payment_lookup_result) )
					$this->_view->taxpayments[] = $tax_payment_lookup_result->data->payment;
			}
		}

		// Search Other Checks
		$account_transaction_search = new Beans_Account_Transaction_Search_Check($this->_beans_data_auth((object)array(
			'page_size' => 5,
			'sort_by' => 'checknewest',
		)));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( $this->_beans_result_check($account_transaction_search_result) )
		{
			// We have to parse out the proper form for these transactions...
			foreach( $account_transaction_search_result->data->transactions as $index => $transaction )
			{
				if( isset($transaction->form) AND
					$transaction->form AND
					isset($transaction->form->id) AND 
					$transaction->form->type == "expense" )
				{
					$vendor_expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
						'id' => $transaction->form->id,
					)));
					$vendor_expense_lookup_result = $vendor_expense_lookup->execute();

					if( $this->_beans_result_check($vendor_expense_lookup_result) )
						$account_transaction_search_result->data->transactions[$index]->expense = $vendor_expense_lookup_result->data->expense;
					
				}
				else if( $transaction->payment AND 
						 $transaction->payment == "vendor" )
				{
					$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
						'id' => $transaction->id,
					)));
					$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

					if( $this->_beans_result_check($vendor_payment_lookup_result) )
						$account_transaction_search_result->data->transactions[$index]->payment = $vendor_payment_lookup_result->data->payment;

				}
				else if( $transaction->tax_payment AND 
						 isset($transaction->tax_payment->id) )
				{
					$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
						'id' => $transaction->tax_payment->id,
					)));
					$tax_payment_lookup_result = $tax_payment_lookup->execute();

					if( $this->_beans_result_check($tax_payment_lookup_result) )
						$account_transaction_search_result->data->transactions[$index]->taxpayment = $tax_payment_lookup_result->data->payment;
				}
			}

			$this->_view->account_transaction_search_result = $account_transaction_search_result;

		}
	}

	public function action_renderprintchecks()
	{
		$expense_ids = array();
		$payment_ids = array();
		$taxpayment_ids = array();

		foreach( $this->request->post() as $key => $value )
		{
			if( $value == "print-check" )
			{
				$key_array = explode('-', $key);
				${$key_array[0].'_ids'}[] = $key_array[1];
			}
		}

		$print_vendor_checks = new View_Vendors_Print_Checks;

		$print_vendor_checks->expenses = array();
		$print_vendor_checks->payments = array();
		$print_vendor_checks->taxpayments = array();

		if( count($expense_ids) OR
			count($payment_ids) OR
			count($taxpayment_ids) )
		{
			foreach( $expense_ids as $expense_id )
			{
				$expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
					'id' => $expense_id,
				)));
				$expense_lookup_result = $expense_lookup->execute();

				if( ! $expense_lookup_result->success )
					throw new Exception($this->_beans_result_get_error($expense_lookup_result));

				$print_vendor_checks->expenses[] = $expense_lookup_result->data->expense;

			}
			
			foreach( $payment_ids as $payment_id )
			{
				$payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $payment_id,
				)));
				$payment_lookup_result = $payment_lookup->execute();

				if( ! $payment_lookup_result->success )
					throw new Exception($this->_beans_result_get_error($payment_lookup_result));

				$print_vendor_checks->payments[] = $payment_lookup_result->data->payment;
			}
			
			foreach( $taxpayment_ids as $taxpayment_id )
			{
				$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $taxpayment_id,
				)));
				$tax_payment_lookup_result = $tax_payment_lookup->execute();

				if( ! $tax_payment_lookup_result->success )
					throw new Exception($this->_beans_result_get_error($tax_payment_lookup_result));

				$print_vendor_checks->taxpayments[] = $tax_payment_lookup_result->data->payment;
			}
		}
		else
		{
			throw new Exception("Please include at least one check to print.");
		}

		die($print_vendor_checks->render());
	}

	protected function _update_check_print_queue_tab()
	{
		$check_print_queue = Session::instance()->get('check_print_queue');

		$check_print_queue_total = 0;

		if( $check_print_queue )
			$check_print_queue_total = 
				count($check_print_queue->expense_ids) + 
				count($check_print_queue->payment_ids) +
				count($check_print_queue->taxpayment_ids);
		
		$tab_links = Session::instance()->get('tab_links');
		
		$check_print_queue_index = FALSE;
		$last_sticky_tab_index = FALSE;
		foreach( $tab_links as $index => $link )
		{
			if( isset($link['check_print_queue']) )
				$check_print_queue_index = $index;

			if( ! $link['removable'] )
				$last_sticky_tab_index = $index;
		}

		if( $check_print_queue_index )
		{
			// Update
			$tab_links[$check_print_queue_index]['text'] = "Check Print Queue".( $check_print_queue_total ? " (".$check_print_queue_total.")" : "" );
		}
		else
		{
			// Create
			$tab_links_extra = array_slice($tab_links, ( $last_sticky_tab_index + 1 ));
			$tab_links = array_slice($tab_links, 0, ( $last_sticky_tab_index + 1 ));
			$tab_links[] = array(
				'url' => '/vendors/printchecks/',
				'text' => "Check Print Queue".( $check_print_queue_total ? " (".$check_print_queue_total.")" : "" ),
				'removable' => FALSE,
				'check_print_queue' => TRUE,
			);

			$tab_links = array_merge($tab_links,$tab_links_extra);
		}

		Session::instance()->set('tab_links',$tab_links);
	}

}