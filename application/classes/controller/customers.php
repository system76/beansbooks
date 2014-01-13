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

class Controller_Customers extends Controller_View {

	protected $_required_role_permissions = array(
		'default' => 'customer_read',
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
				'url' => '/customers/sales',
				'text' => 'Sales Orders',
				'text_short' => 'SOs',
				'removable' => FALSE
			);

			$tab_links[] = array(
				'url' => '/customers/invoices',
				'text' => 'Invoices',
				'text_short' => 'Inv',
				'removable' => FALSE
			);

			$tab_links[] = array(
				'url' => '/customers/payments',
				'text' => 'Payments',
				'text_short' => 'Pay',
				'removable' => FALSE
			);

			$tab_links[] = array(
				'url' => '/customers/customers',
				'text' => 'Customers',
				'text_short' => 'Cust',
				'removable' => FALSE
			);

			Session::instance()->set('tab_links',$tab_links);
		}

	}

	public function action_index()
	{
		// Forward to sales.	
		$this->request->redirect("/customers/sales/");
	}

	public function action_sales()
	{
		$sale_id = $this->request->param('id');

		$customer_sale_search = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
			'page_size' => 20,
			'sort_by' => 'newest',
			'invoiced' => FALSE,
		)));
		$customer_sale_search_result = $customer_sale_search->execute();

		if( $this->_beans_result_check($customer_sale_search_result) )
			$this->_view->customer_sale_search_result = $customer_sale_search_result;

		// Pass the sale ID if it's set.
		$this->_view->requested_sale_id = $sale_id;
		$this->_view->force_current_uri = "/customers/sales";
	}

	public function action_invoices()
	{
		$sale_id = $this->request->param('id');

		$customer_sale_search = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
			'page_size' => 20,
			'sort_by' => 'newest',
			'invoiced' => TRUE,
			'has_balance' => TRUE,
		)));
		$customer_sale_search_result = $customer_sale_search->execute();

		if( $this->_beans_result_check($customer_sale_search_result) )
			$this->_view->customer_sale_search_result = $customer_sale_search_result;

		$this->_view->invoice_view = TRUE;

		// Pass the sale ID if it's set.
		$this->_view->requested_sale_id = $sale_id;
		$this->_view->force_current_uri = "/customers/invoices";
	}

	public function action_customers()
	{
		$customer_search = new Beans_Customer_Search($this->_beans_data_auth((object)array(
			'page_size' => 5,
			'sort_by' => 'newest',
		)));
		$customer_search_result = $customer_search->execute();

		if( $this->_beans_result_check($customer_search_result) )
			$this->_view->customer_search_result = $customer_search_result;

	}

	public function action_customer()
	{
		$customer_id = $this->request->param('id');

		$customer_lookup = new Beans_Customer_Lookup($this->_beans_data_auth((object)array(
			'id' => $customer_id
		)));
		$customer_lookup_result = $customer_lookup->execute();
		
		if( $this->_beans_result_check($customer_lookup_result) )
		{
			$this->_view->customer_lookup_result = $customer_lookup_result;
			$this->_action_tab_name = $customer_lookup_result->data->customer->first_name.' '.$customer_lookup_result->data->customer->last_name;
			$this->_action_tab_uri = '/'.$this->request->uri();

			$customer_address_search = new Beans_Customer_Address_Search($this->_beans_data_auth((object)array(
				'search_customer_id' => $customer_id,
			)));
			$customer_address_search_result = $customer_address_search->execute();

			if( $this->_beans_result_check($customer_address_search_result) )
				$this->_view->customer_address_search_result = $customer_address_search_result;

			$customer_sale_search = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
				'search_customer_id' => $customer_id,
				'page_size' => 5,
				'sort_by' => 'newest',
				'search_and' => TRUE,
			)));
			$customer_sale_search_result = $customer_sale_search->execute();
			
			if( $this->_beans_result_check($customer_sale_search_result) )
				$this->_view->customer_sale_search_result = $customer_sale_search_result;
		}
	}

	public function action_payments()
	{
		$payment_id = $this->request->param('id');

		$customer_payment_search = new Beans_Customer_Payment_Search($this->_beans_data_auth((object)array(
			'page_size' => 5,
			'sort_by' => 'newest',
		)));
		$customer_payment_search_result = $customer_payment_search->execute();

		if( $this->_beans_result_check($customer_payment_search_result) )
			$this->_view->customer_payment_search_result = $customer_payment_search_result;

		// Oustanding sales
		$customer_sale_search = new Beans_Customer_Sale_Search($this->_beans_data_auth((object)array(
			'page_size' => 30,
			'invoiced' => TRUE,
			'has_balance' => TRUE,
			'sort_by' => 'duesoonest',
		)));
		$customer_sale_search_result = $customer_sale_search->execute();

		if( $this->_beans_result_check($customer_sale_search_result) )
			$this->_view->customer_sale_search_result = $customer_sale_search_result;

		$this->_view->requested_payment_id = $payment_id;
		$this->_view->force_current_uri = "/customers/payments";
	}

	public function action_paymentcalibrate()
	{
		set_time_limit(60 * 10);
		ini_set('memory_limit', '256M');

		$date = $this->request->param('id');

		$customer_invoice_updatebatch = new Beans_Customer_Sale_Invoice_Updatebatch($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$customer_invoice_updatebatch_result = $customer_invoice_updatebatch->execute();

		if( ! $customer_invoice_updatebatch_result->success )
			die($date.' -> ERROR: '.$customer_invoice_updatebatch_result->error);

		$customer_payment_calibratebatch = new Beans_Customer_Payment_Calibratebatch($this->_beans_data_auth((object)array(
			'date' => $date,
		)));
		$customer_payment_calibratebatch_result = $customer_payment_calibratebatch->execute();

		if( ! $customer_payment_calibratebatch_result->success )
			die($date.' -> ERROR: '.$customer_payment_calibratebatch_result->error);

		$nextscript = '<script type="text/javascript" src="/static/js/libs/jquery-1.7.2.min.js"></script>';
		$nextscript .= '<script type="text/javascript">';
		$nextscript .= '$(function() { ';
		$nextscript .= '  var count = 5; ';
		$nextscript .= '  var nextInterval = setInterval(function() { ';
		$nextscript .= '    if( count < 0 ) { ';
		$nextscript .= '      $("#counter").text("..."); clearInterval(nextInterval); ';
		$nextscript .= '    } else if( count != 0 ) { ';
		$nextscript .= '      $("#counter").text("Next will run in "+count+" seconds...");';
		$nextscript .= '      count--;';
		$nextscript .= '    } else {';
		$nextscript .= '      count--; clearInterval(nextInterval); ';
		$nextscript .= '      document.location = $("#nextcalibrate").attr("href"); ';//$("#nextcalibrate").trigger("click"); ';
		$nextscript .= '      $("#nextcalibrate").hide(); ';
		$nextscript .= '      $("#stopnext").hide(); ';
		$nextscript .= '    } ';
		$nextscript .= '  }, 1000);';
		$nextscript .= '  $("#stopnext").click(function() { console.log("wasss"); count = -1; } );';
		$nextscript .= '});';
		$nextscript .= '</script>';

		die(
			$date." -> Success!  Calibrated ".count($customer_invoice_updatebatch_result->data->updated_invoice_ids)." invoices and ".count($customer_payment_calibratebatch_result->data->calibrated_payment_ids)." payments.<br><br>".
			'<div id="counter">...</div><br><br>'.
			'<a id="stopnext" href="#">CANCEL NEXT</a>&nbsp;&nbsp;&nbsp;&nbsp;'.
			'<a id="nextcalibrate" href="/customers/paymentcalibrate/'.date("Y-m-d",strtotime($date.' +1 Day')).'/">Next</a>'.
			$nextscript.
			'<br><br>'.
			'Invoice IDs: '.implode(', ', $customer_invoice_updatebatch_result->data->updated_invoice_ids)
		);
	}

}