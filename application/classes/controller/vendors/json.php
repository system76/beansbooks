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

class Controller_Vendors_Json extends Controller_Json {

	public function after() {
		$this->_return_object->print_check_queue = new stdClass;
		$this->_return_object->print_check_queue->count = $this->_print_check_queue_count();
		$this->_return_object->print_check_queue->text = "Check Print Queue".
			( $this->_return_object->print_check_queue->count ? " (".$this->_return_object->print_check_queue->count.")" : "" );
		parent::after();
	}

	public function action_vendorcreate()
	{
		$vendor_validate_data = array(
			'validate_only' => TRUE,
			'first_name' => $this->request->post('first_name'),
			'last_name' => $this->request->post('last_name'),
			'company_name' => $this->request->post('company_name'),
			'email' => $this->request->post('email'),
			'default_account_id' => $this->request->post('default_account_id'),
			'phone_number' => $this->request->post('phone_number'),
			'fax_number' => $this->request->post('fax_number'),
		);
		
		$vendor_validate = new Beans_Vendor_Create($this->_beans_data_auth((object)$vendor_validate_data));
		$vendor_validate_result = $vendor_validate->execute();

		if( ! $vendor_validate_result->success )
			return $this->_return_error($this->_beans_result_get_error($vendor_validate_result));

		// Check for addresses.
		$address_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "ADDRESSKEY" )
				$address_keys[] = str_replace('address-key-', '', $key);

		foreach( $address_keys as $address_key )
		{
			$vendor_address_validate_data = array(
				'validate_only' => TRUE,
				'company_name' => $vendor_validate_data['company_name'],
				'address1' => $this->request->post('address1-'.$address_key),
				'address2' => $this->request->post('address2-'.$address_key),
				'city' => $this->request->post('city-'.$address_key),
				'state' => $this->request->post('state-'.$address_key),
				'zip' => $this->request->post('zip-'.$address_key),
				'country' => $this->request->post('country-'.$address_key),
			);
			$vendor_address_validate = new Beans_Vendor_Address_Create($this->_beans_data_auth((object)$vendor_address_validate_data));
			$vendor_address_validate_result = $vendor_address_validate->execute();

			if( ! $vendor_address_validate_result->success )
				return $this->_return_error("There was a problem with an address ( ".$this->request->post('address1-'.$address_key)." ).<br>".$this->_beans_result_get_error($vendor_address_validate_result));
		}

		// Now we create!
		$vendor_create_data = array(
			'first_name' => $this->request->post('first_name'),
			'last_name' => $this->request->post('last_name'),
			'company_name' => $this->request->post('company_name'),
			'email' => $this->request->post('email'),
			'default_account_id' => $this->request->post('default_account_id'),
			'phone_number' => $this->request->post('phone_number'),
			'fax_number' => $this->request->post('fax_number'),
		);
		
		$vendor_create = new Beans_Vendor_Create($this->_beans_data_auth((object)$vendor_create_data));
		$vendor_create_result = $vendor_create->execute();

		if( ! $vendor_create_result->success )
			return $this->_return_error($this->_beans_result_get_error($vendor_create_result));

		// Check for addresses.
		$address_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "ADDRESSKEY" )
				$address_keys[] = str_replace('address-key-', '', $key);

		$default_remit_address_id = FALSE;
		
		foreach( $address_keys as $address_key )
		{
			$vendor_address_create_data = array(
				'vendor_id' => $vendor_create_result->data->vendor->id,
				'company_name' => $vendor_create_data['company_name'],
				'address1' => $this->request->post('address1-'.$address_key),
				'address2' => $this->request->post('address2-'.$address_key),
				'city' => $this->request->post('city-'.$address_key),
				'state' => $this->request->post('state-'.$address_key),
				'zip' => $this->request->post('zip-'.$address_key),
				'country' => $this->request->post('country-'.$address_key),
			);
			$vendor_address_create = new Beans_Vendor_Address_Create($this->_beans_data_auth((object)$vendor_address_create_data));
			$vendor_address_create_result = $vendor_address_create->execute();

			if( ! $vendor_address_create_result->success )
				$this->_return_error("An unexpected error occurred while creating the vendor addresses: <br>".$this->_beans_result_get_error($vendor_address_create_result)."<br>Please refresh the page to ensure no data duplication.");
			
			if( $this->request->post('default-remit-'.$address_key) )
				$default_remit_address_id = $vendor_address_create_result->data->address->id;

		}

		if( $default_remit_address_id )
		{
			$vendor_update = new Beans_Vendor_Update($this->_beans_data_auth((object)array(
				'id' => $vendor_create_result->data->vendor->id,
				'default_remit_address_id' => $default_remit_address_id,
			)));
			$vendor_update_result = $vendor_update->execute();

			if( ! $vendor_update_result->success )
				return $this->_return_error("An unexpected error occurred when setting the default remit address:<br>".$this->_beans_result_get_error($vendor_update_result));
		}

		$html = new View_Partials_Vendors_Vendor_Vendor;
		$html->vendor = $vendor_create_result->data->vendor;

		$vendor_create_result->data->vendor->html = $html->render();

		$this->_return_object->data->vendor = $vendor_create_result->data->vendor;
	}

	public function action_vendorupdate()
	{
		$vendor_update = new Beans_Vendor_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('vendor_id'),
			'first_name' => $this->request->post('first_name'),
			'last_name' => $this->request->post('last_name'),
			'company_name' => $this->request->post('company_name'),
			'default_account_id' => $this->request->post('default_account_id'),
			'phone_number' => $this->request->post('phone_number'),
			'fax_number' => $this->request->post('fax_number'),
			'email' => $this->request->post('email'),
		)));
		$vendor_update_result = $vendor_update->execute();

		if( ! $vendor_update_result->success )
			return $this->_return_error("An error occurred updating that customer information:<br>".$this->_beans_result_get_error($vendor_update_result));
	}

	public function action_vendoraddresscreate()
	{
		$vendor_address_create = new Beans_Vendor_Address_Create($this->_beans_data_auth((object)array(
			'vendor_id' => $this->request->post('vendor_id'),
			'address1' => $this->request->post('address1'),
			'address2' => $this->request->post('address2'),
			'city' => $this->request->post('city'),
			'state' => $this->request->post('state'),
			'zip' => $this->request->post('zip'),
			'country' => $this->request->post('country'),
		)));

		$vendor_address_create_result = $vendor_address_create->execute();

		if( ! $vendor_address_create_result->success )
			return $this->_return_error("An error occurred in creating your address:<br>".$this->_beans_result_get_error($vendor_address_create_result));

		$vendor_result = FALSE;
		if( $this->request->post('default-remit') )
		{
			$vendor_update_data = new stdClass;
			$vendor_update_data->id = $this->request->post('vendor_id');
			if( $this->request->post('default-remit') )
				$vendor_update_data->default_remit_address_id = $vendor_address_create_result->data->address->id;
			
			$vendor_update = new Beans_Vendor_Update($this->_beans_data_auth($vendor_update_data));
			$vendor_result = $vendor_update->execute();
		}
		else
		{
			$vendor_lookup = new Beans_Vendor_Lookup($this->_beans_data_auth((object)array(
				'id' => $this->request->post('vendor_id'),
			)));
			$vendor_result = $vendor_lookup->execute();
		}

		if( ! $vendor_result )
			return $this->_return_error("An unknown error has occurred.");

		if( ! $vendor_result->success )
			return $this->_return_error("An error has occurred:<br>".$this->_beans_result_get_error($vendor_result));

		$address = $vendor_address_create_result->data->address;

		$html = new View_Partials_Vendors_Vendor_Address();
		$html->address = $vendor_address_create_result->data->address;
		$html->vendor = $vendor_result->data->vendor;

		$address->html = $html->render();
		$address->default_remit = ( $this->request->post('default-remit') )
								  ? TRUE
								  : FALSE;
		
		$this->_return_object->data->address = $address;
	}

	public function action_vendoraddressupdate()
	{
		$vendor_address_update = new Beans_Vendor_Address_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('address_id'),
			'address1' => $this->request->post('address1'),
			'address2' => $this->request->post('address2'),
			'city' => $this->request->post('city'),
			'state' => $this->request->post('state'),
			'zip' => $this->request->post('zip'),
			'country' => $this->request->post('country'),
		)));

		$vendor_address_update_result = $vendor_address_update->execute();

		if( ! $vendor_address_update_result->success )
			return $this->_return_error("An error occurred in creating your address:<br>".$this->_beans_result_get_error($vendor_address_update_result));

		$vendor_result = FALSE;
		if( $this->request->post('default-remit') )
		{
			$vendor_update_data = new stdClass;
			$vendor_update_data->id = $this->request->post('vendor_id');
			if( $this->request->post('default-remit') )
				$vendor_update_data->default_remit_address_id = $vendor_address_update_result->data->address->id;
			
			$vendor_update = new Beans_Vendor_Update($this->_beans_data_auth($vendor_update_data));
			$vendor_result = $vendor_update->execute();
		}
		else
		{
			$vendor_lookup = new Beans_Vendor_Lookup($this->_beans_data_auth((object)array(
				'id' => $this->request->post('vendor_id'),
			)));
			$vendor_result = $vendor_lookup->execute();
		}

		if( ! $vendor_result )
			return $this->_return_error("An unknown error has occurred.");
		
		if( ! $vendor_result->success )
			return $this->_return_error("An error has occurred:<br>".$this->_beans_result_get_error($vendor_result));

		$address = $vendor_address_update_result->data->address;

		$html = new View_Partials_Vendors_Vendor_Address();
		$html->address = $vendor_address_update_result->data->address;
		
		$html->vendor = $vendor_result->data->vendor;

		$address->html = $html->render();
		$address->default_remit = ( $this->request->post('default-remit') )
								  ? TRUE
								  : FALSE;
		
		$this->_return_object->data->address = $address;
	}

	public function action_shippingaddresscreate()
	{
		$vendor_address_shipping_create = new Beans_Vendor_Address_Shipping_Create($this->_beans_data_auth((object)array(
			'first_name' 	=> $this->request->post('first_name'),
			'last_name' 	=> $this->request->post('last_name'),
			'company_name'	=> $this->request->post('company_name'),
			'address1' 		=> $this->request->post('address1'),
			'address2' 		=> $this->request->post('address2'),
			'city' 			=> $this->request->post('city'),
			'state'			=> $this->request->post('state'),
			'zip' 			=> $this->request->post('zip'),
			'country'		=> $this->request->post('country'),
		)));
		$vendor_address_shipping_create_result = $vendor_address_shipping_create->execute();
		if( ! $vendor_address_shipping_create_result->success )
			return $this->_return_error("Error creating that address: ".$this->_beans_result_get_error($vendor_address_shipping_create_result));

		$this->_return_object->data->address = $vendor_address_shipping_create_result->data->address;
	}

	public function action_shippingaddressupdate()
	{
		return $this->_return_error("Not built yet.");
	}

	public function action_shippingaddresssearch()
	{
		$vendor_address_shipping_search = new Beans_Vendor_Address_Shipping_Search($this->_beans_data_auth((object)array(
			'search_keywords' => $this->request->post('keywords'),
		)));
		$vendor_address_shipping_search_result = $vendor_address_shipping_search->execute();

		if( ! $vendor_address_shipping_search_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($vendor_address_shipping_search_result));

		// Consider moving this to Beans_Vendor_Address_Shipping_Search
		$this->_sort_addresses_keywords = $this->request->post('keywords');
		
		$addresses = $vendor_address_shipping_search_result->data->addresses;

		usort($addresses, array($this,'_sort_addresses'));
		
		$this->_return_object->data->addresses = $addresses;
	}

	protected $_sort_addresses_keywords = NULL;
	protected function _sort_addresses($a,$b)
	{
		if( $a->standard == $b->standard )
				return 0;

		if( ! $this->_sort_addresses_keywords )
			return ( strcmp($a->standard,$b->standard) <= 0 ? -1 : 1 );

		if( strtolower($a->standard) == substr(strtolower($this->_sort_addresses_keywords),0,strlen($a->standard)) AND 
			strtolower($b->standard) != substr(strtolower($this->_sort_addresses_keywords),0,strlen($b->standard)) )
			return -1;
		
		if( strtolower($a->standard) != substr(strtolower($this->_sort_addresses_keywords),0,strlen($a->standard)) AND 
			strtolower($b->standard) == substr(strtolower($this->_sort_addresses_keywords),0,strlen($b->standard)) )
			return 1;
		
		if( strtolower($a->standard) == substr(strtolower($this->_sort_addresses_keywords),0,strlen($a->standard)) AND 
			strtolower($b->standard) == substr(strtolower($this->_sort_addresses_keywords),0,strlen($b->standard)) )
			return 0;
		
		return ( levenshtein(strtolower($a->standard), $this->_sort_addresses_keywords) < levenshtein(strtolower($b->standard), $this->_sort_addresses_keywords) ? -1 : 1 );
	}

	public function action_paymentsloadmore()
	{
		$last_payment_id = $this->request->post('last_payment_id');
		$last_payment_date = $this->request->post('last_payment_date');
		$search_terms = $this->request->post('search_terms');
		$search_vendor_id = $this->request->post('search_vendor_id');
		$count = $this->request->post('count');

		if( ! $count )
			$count = 20;

		$this->_return_object->data->payments = array();

		$page = 0;

		$search_parameters = new stdClass;
		$search_parameters->sort_by = 'newest';
		$search_parameters->page_size = ($count * 2);
		$search_parameters->search_date_before = ( $last_payment_date )
											   ? date("Y-m-d",strtotime($last_payment_date." +1 Day"))
											   : NULL;		// ALL

		// NULLs itself from $this->request->post();
		if( $search_vendor_id AND
			strlen(trim($search_vendor_id)) )
		{
			$search_parameters->include_invoices = TRUE;
			$search_parameters->search_vendor_id = trim($search_vendor_id);
		}

		if( $search_terms )
		{
			$search_terms_array = explode(' ',$search_terms);
			$search_parameters->search_purchase_number = implode(' ',$search_terms_array);
			$search_parameters->search_check_number = implode(' ',$search_terms_array);
			$search_parameters->search_invoice_name = implode(' ',$search_terms_array);
			$search_parameters->search_so_number = implode(' ',$search_terms_array);
			$search_parameters->search_vendor_id = $search_vendor_id;
			foreach( $search_terms_array as $search_term )
				if( trim($search_term) AND
					$search_term == date("Y-m-d",strtotime($search_term)) )
						$search_parameters->search_date = $search_term;
		}
		
		do
		{
			$search_parameters->page = $page;
			$vendor_payments = new Beans_Vendor_Payment_Search($this->_beans_data_auth($search_parameters));
			$vendor_payments_result = $vendor_payments->execute();
			
			if( ! $vendor_payments_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($vendor_payments_result));

			foreach( $vendor_payments_result->data->payments as $payment ) 
			{
				if( (
						strtotime($payment->date) <= strtotime($last_payment_date) AND
						$payment->id < $last_payment_id 
					) OR 
					strtotime($payment->date) < strtotime($last_payment_date) OR 
					! $last_payment_id )
				{
					$html = new View_Partials_Vendors_Payments_Payment;
					$html->payment = $payment;

					$payment->html = $html->render();

					$this->_return_object->data->payments[] = $payment;
				}
				if( count($this->_return_object->data->payments) >= $count )
					return;
			}
			$page++;
		}
		while( 	$page < $vendor_payments_result->data->pages AND 
				count($this->_return_object->data->payments) < $count );
	}

	public function action_vendorsformsearch()
	{
		$search_terms = $this->request->post('search_terms');

		$search_parameters = new stdClass;
		$search_parameters->sort_by = 'newest';
		$search_parameters->search_name = $search_terms;
		
		$vendor_search = new Beans_Vendor_Search($this->_beans_data_auth($search_parameters));
		$vendor_search_result = $vendor_search->execute();

		if( ! $vendor_search_result->success )
			return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($vendor_search_result));

		foreach( $vendor_search_result->data->vendors as $vendor ) 
			$this->_return_object->data->vendors[] = $vendor;
		
	}

	public function action_vendorsloadmore()
	{
		$last_vendor_id = $this->request->post('last_vendor_id');
		$last_page = $this->request->post('last_page');
		$search_terms = $this->request->post('search_terms');
		$count = $this->request->post('count');

		if( ! $last_page ) 
			$last_page = 0;

		if( ! $count )
			$count = 20;

		$this->_return_object->data->vendors = array();
		
		$search_parameters = new stdClass;
		$search_parameters->sort_by = 'newest';
		$search_parameters->page = $last_page;

		if( $search_terms ) {
			$search_parameters->search_email = $search_terms;
			$search_parameters->search_name = $search_terms;
			$search_parameters->search_number = $search_terms;
		}

		do
		{
			$vendor_search = new Beans_Vendor_Search($this->_beans_data_auth($search_parameters));
			$vendor_search_result = $vendor_search->execute();

			if( ! $vendor_search_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($vendor_search_result));

			$this->_return_object->data->last_page = $vendor_search_result->data->page;

			foreach( $vendor_search_result->data->vendors as $vendor ) 
			{
				if( $vendor->id < $last_vendor_id OR
					! $last_vendor_id )
				{
					$html = new View_Partials_Vendors_Vendor_Vendor;
					$html->vendor = $vendor;

					$vendor->html = $html->render();

					$this->_return_object->data->vendors[] = $vendor;
				}
				if( count($this->_return_object->data->vendors) >= $count )
					return;
			}
			$search_parameters->page++;
		}
		while( 	$search_parameters->page < $vendor_search_result->data->pages AND 
				count($this->_return_object->data->vendors) < $count );
	}
	
	public function action_vendoraddresses()
	{
		$vendor_id = $this->request->post('vendor_id');

		$vendor_address_search = new Beans_Vendor_Address_Search($this->_beans_data_auth((object)array(
			'search_vendor_id' => $vendor_id,
		)));
		$vendor_address_search_result = $vendor_address_search->execute();

		if( ! $vendor_address_search_result->success )
			return $this->_return_error("An error occurred: ".$this->_beans_result_get_error($vendor_address_search_result));

		$vendor_lookup = new Beans_Vendor_Lookup($this->_beans_data_auth((object)array(
			'id' => $vendor_id,
		)));
		$vendor_lookup_result = $vendor_lookup->execute();

		if( ! $vendor_lookup_result->success )
			return $this->_return_error($vendor_lookup_result->auth_error.$vendor_lookup_result->error);

		$this->_return_object->data->vendor = $vendor_lookup_result->data->vendor;
		$this->_return_object->data->addresses = $vendor_address_search_result->data->addresses;
	}

	/**
	 * Expense Actions
	 */

	public function action_expensecreate()
	{
		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "EXPENSELINEKEY" )
				$line_keys[] = str_replace('expense-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$vendor_info = explode('#',$this->request->post('vendor'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this expense.");

		if( ! isset($vendor_info[0]) OR 
			! strlen($vendor_info[0]) )
			return $this->_return_error("Please select a valid vendor for this expense.");

		$create_expense_data = new stdClass;
		$create_expense_data->vendor_id = $vendor_info[0];
		$create_expense_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		$create_expense_data->remit_address_id = $this->request->post('remit_address_id');
		$create_expense_data->account_id = $account_info[0];
		$create_expense_data->invoice_number = $this->request->post('invoice_number'); 
		$create_expense_data->so_number = $this->request->post('so_number');
		$create_expense_data->check_number = $this->request->post('check_number');

		$create_expense_data->lines = array();

		foreach( $line_keys as $line_key )
		{
			if( (
					/* $this->request->post('line-account_id-'.$line_key) OR */ // Removed per a default line item account.
					$this->request->post('line-description-'.$line_key) OR
					floatval($this->request->post('line-price-'.$line_key)) OR
					floatval($this->request->post('line-quantity-'.$line_key))
				) AND
				(
					! $this->request->post('line-account_id-'.$line_key) OR
					! $this->request->post('line-description-'.$line_key) OR
					! strlen($this->request->post('line-price-'.$line_key)) OR
					! strlen($this->request->post('line-quantity-'.$line_key))
				) ) {
				return $this->_return_error("One of those line items is missing a value.");
			}
			else if( 	$this->request->post('line-account_id-'.$line_key) AND
						$this->request->post('line-description-'.$line_key) AND
						strlen($this->request->post('line-price-'.$line_key)) AND
						strlen($this->request->post('line-quantity-'.$line_key)) ) 
			{
				$expense_line = new stdClass;
				$expense_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$expense_line->description = $this->request->post('line-description-'.$line_key);
				$expense_line->amount = $this->request->post('line-price-'.$line_key);
				$expense_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$create_expense_data->lines[] = $expense_line;
			}
		}
		
		$create_expense = new Beans_Vendor_Expense_Create($this->_beans_data_auth($create_expense_data));
		$create_expense_result = $create_expense->execute();

		if( ! $create_expense_result->success )
			return $this->_return_error("An error occurred when creating that expense:<br>".$this->_beans_result_get_error($create_expense_result));

		if( $this->request->post('print_check') AND 
			$this->request->post('print_check') == "1" )
			$this->_print_check_queue_expense_add($create_expense_result->data->expense->id);
		
		$html = new View_Partials_Vendors_Expenses_Expense;
		$html->expense = $create_expense_result->data->expense;

		$this->_return_object->data->expense = $create_expense_result->data->expense;
		$this->_return_object->data->expense->html = $html->render();
	}

	public function action_expenseload()
	{
		$expense_id = $this->request->post('expense_id');

		$vendor_expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
			'id' => $expense_id,
		)));
		$vendor_expense_lookup_result = $vendor_expense_lookup->execute();

		if( ! $vendor_expense_lookup_result->success )
			return $this->_return_error("An error occurred when looking up that expense:<br>".$this->_beans_result_get_error($vendor_expense_lookup_result));

		$this->_return_object->data->expense = $vendor_expense_lookup_result->data->expense;
	}

	public function action_expenseupdate()
	{
		$expense_id = $this->request->post('expense_id');

		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "EXPENSELINEKEY" )
				$line_keys[] = str_replace('expense-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$vendor_info = explode('#',$this->request->post('vendor'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this expense.");

		if( ! isset($vendor_info[0]) OR 
			! strlen($vendor_info[0]) )
			return $this->_return_error("Please select a valid vendor for this expense.");

		$update_expense_data = new stdClass;
		$update_expense_data->id = $expense_id;
		$update_expense_data->vendor_id = $vendor_info[0];
		$update_expense_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		$update_expense_data->date_due = ( $this->request->post('date_due') )
									   ? date("Y-m-d",strtotime($this->request->post('date_due')))
									   : date("Y-m-d",strtotime($update_expense_data->date_created.' +'.$account_info[1].' Days'));
		$update_expense_data->remit_address_id = $this->request->post('remit_address_id');
		$update_expense_data->account_id = $account_info[0];
		$update_expense_data->invoice_number = $this->request->post('invoice_number'); 
		$update_expense_data->so_number = $this->request->post('so_number');
		$update_expense_data->check_number = $this->request->post('check_number');

		$update_expense_data->lines = array();

		foreach( $line_keys as $line_key )
		{
			if( (
					/* $this->request->post('line-account_id-'.$line_key) OR */ // Removed per a default line item account.
					$this->request->post('line-description-'.$line_key) OR
					floatval($this->request->post('line-price-'.$line_key)) OR
					floatval($this->request->post('line-quantity-'.$line_key))
				) AND
				(
					! $this->request->post('line-account_id-'.$line_key) OR
					! $this->request->post('line-description-'.$line_key) OR
					! strlen($this->request->post('line-price-'.$line_key)) OR
					! strlen($this->request->post('line-quantity-'.$line_key))
				) ) {
				return $this->_return_error("One of those line items is missing a value.");
			}
			else if( 	$this->request->post('line-account_id-'.$line_key) AND
						$this->request->post('line-description-'.$line_key) AND
						strlen($this->request->post('line-price-'.$line_key)) AND
						strlen($this->request->post('line-quantity-'.$line_key)) ) 
			{
				$expense_line = new stdClass;
				$expense_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$expense_line->description = $this->request->post('line-description-'.$line_key);
				$expense_line->amount = $this->request->post('line-price-'.$line_key);
				$expense_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$update_expense_data->lines[] = $expense_line;
			}
		}

		$update_expense = new Beans_Vendor_Expense_Update($this->_beans_data_auth($update_expense_data));
		$update_expense_result = $update_expense->execute();

		if( ! $update_expense_result->success )
			return $this->_return_error("An error occurred when updating that expense:<br>".$this->_beans_result_get_error($update_expense_result));

		if( $this->request->post('print_check') AND 
			$this->request->post('print_check') == "1" )
			$this->_print_check_queue_expense_add($update_expense_result->data->expense->id);
		
		$html = new View_Partials_Vendors_Expenses_Expense;
		$html->expense = $update_expense_result->data->expense;

		$this->_return_object->data->expense = $update_expense_result->data->expense;
		$this->_return_object->data->expense->html = $html->render();
	}

	public function action_expenserefund()
	{
		$expense_id = $this->request->post('refund_expense_id');

		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "EXPENSELINEKEY" )
				$line_keys[] = str_replace('expense-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$vendor_info = explode('#',$this->request->post('vendor'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this expense.");

		if( ! isset($vendor_info[0]) OR 
			! strlen($vendor_info[0]) )
			return $this->_return_error("Please select a valid vendor for this expense.");

		$refund_expense_data = new stdClass;
		$refund_expense_data->id = $expense_id;
		$refund_expense_data->vendor_id = $vendor_info[0];
		$refund_expense_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		$refund_expense_data->date_due = ( $this->request->post('date_due') )
									   ? date("Y-m-d",strtotime($this->request->post('date_due')))
									   : date("Y-m-d",strtotime($refund_expense_data->date_created.' +'.$account_info[1].' Days'));
		$refund_expense_data->remit_address_id = $this->request->post('remit_address_id');
		$refund_expense_data->account_id = $account_info[0];
		$refund_expense_data->invoice_number = $this->request->post('invoice_number'); 
		$refund_expense_data->so_number = $this->request->post('so_number');
		$refund_expense_data->check_number = $this->request->post('check_number');

		$refund_expense_data->lines = array();

		foreach( $line_keys as $line_key )
		{
			if( (
					/* $this->request->post('line-account_id-'.$line_key) OR */ // Removed per a default line item account.
					$this->request->post('line-description-'.$line_key) OR
					floatval($this->request->post('line-price-'.$line_key)) OR
					floatval($this->request->post('line-quantity-'.$line_key))
				) AND
				(
					! $this->request->post('line-account_id-'.$line_key) OR
					! $this->request->post('line-description-'.$line_key) OR
					! strlen($this->request->post('line-price-'.$line_key)) OR
					! strlen($this->request->post('line-quantity-'.$line_key))
				) ) {
				return $this->_return_error("One of those line items is missing a value.");
			}
			else if( 	$this->request->post('line-account_id-'.$line_key) AND
						$this->request->post('line-description-'.$line_key) AND
						strlen($this->request->post('line-price-'.$line_key)) AND
						strlen($this->request->post('line-quantity-'.$line_key)) ) 
			{
				$expense_line = new stdClass;
				$expense_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$expense_line->description = $this->request->post('line-description-'.$line_key);
				$expense_line->amount = $this->request->post('line-price-'.$line_key);
				$expense_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$refund_expense_data->lines[] = $expense_line;
			}
		}

		$refund_expense = new Beans_Vendor_Expense_Refund($this->_beans_data_auth($refund_expense_data));
		$refund_expense_result = $refund_expense->execute();

		if( ! $refund_expense_result->success )
			return $this->_return_error("An error occurred when updating that expense:<br>".$this->_beans_result_get_error($refund_expense_result));

		$html = new View_Partials_Vendors_Expenses_Expense;
		$html->expense = $refund_expense_result->data->expense;

		$this->_return_object->data->expense = $refund_expense_result->data->expense;
		$this->_return_object->data->expense->html = $html->render();
	}

	public function action_expensecancel()
	{
		$expense_id = $this->request->post('expense_id');

		$expense_delete = new Beans_Vendor_Expense_Delete($this->_beans_data_auth((object)array(
			'id' => $expense_id,
		)));
		$expense_delete_result = $expense_delete->execute();

		if( ! $expense_delete_result->success )
			return $this->_return_error("An error occurred when trying to delete that expense:<br>".$this->_beans_result_get_error($expense_delete_result));

		$this->_print_check_queue_expense_remove($expense_id);
	}

	public function action_expensesloadmore()
	{
		$last_expense_id = $this->request->post('last_expense_id');
		$last_expense_date = $this->request->post('last_expense_date');
		$search_terms = $this->request->post('search_terms');
		$search_vendor_id = $this->request->post('search_vendor_id');
		
		$count = $this->request->post('count');

		if( ! $count )
			$count = 20;

		$this->_return_object->data->expenses = array();

		$page = 0;

		$search_parameters = new stdClass;
		$search_parameters->sort_by = 'newest';
		$search_parameters->page_size = ($count * 2);
		$search_parameters->search_date_before = ( $last_expense_date )
											   ? date("Y-m-d",strtotime($last_expense_date." +1 Day"))
											   : NULL;		// ALL

		// NULLs itself from $this->request->post();
		if( $search_vendor_id AND
			strlen(trim($search_vendor_id)) )
			$search_parameters->search_vendor_id = $search_vendor_id;

		$search_parameters->keywords = $search_terms;

		do
		{
			$search_parameters->page = $page;
			$vendor_expenses = new Beans_Vendor_Expense_Search($this->_beans_data_auth($search_parameters));
			$vendor_expenses_result = $vendor_expenses->execute();
			
			if( ! $vendor_expenses_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($vendor_expenses_result));

			foreach( $vendor_expenses_result->data->expenses as $expense ) 
			{
				if( (
						strtotime($expense->date_created) <= strtotime($last_expense_date) AND 
						$expense->id < $last_expense_id 
					) OR
					strtotime($expense->date_created) < strtotime($last_expense_date) OR
					! $last_expense_id )
				{
					$html = new View_Partials_Vendors_Expenses_Expense;
					$html->expense = $expense;

					$expense->html = $html->render();

					$this->_return_object->data->expenses[] = $expense;
				}
				if( count($this->_return_object->data->expenses) >= $count )
					return;
			}
			$page++;
		}
		while( 	$page < $vendor_expenses_result->data->pages AND 
				count($this->_return_object->data->expenses) < $count );

	}

	/**
	 * Purchase Purchase Actions
	 */
	
	public function action_purchasecreate()
	{
		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "PURCHASELINEKEY" )
				$line_keys[] = str_replace('purchase-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$vendor_info = explode('#',$this->request->post('vendor'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this expense.");

		if( ! isset($vendor_info[0]) OR 
			! strlen($vendor_info[0]) )
			return $this->_return_error("Please select a valid vendor for this expense.");

		$create_purchase_data = new stdClass;
		$create_purchase_data->vendor_id = $vendor_info[0];
		$create_purchase_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		$create_purchase_data->date_due = ( $this->request->post('date_due') )
									   ? date("Y-m-d",strtotime($this->request->post('date_due')))
									   : date("Y-m-d",strtotime($create_purchase_data->date_created.' +'.$account_info[1].' Days'));
		$create_purchase_data->remit_address_id = $this->request->post('remit_address_id');
		$create_purchase_data->shipping_address_id = $this->request->post('shipping_address_id');
		$create_purchase_data->account_id = $account_info[0];
		$create_purchase_data->purchase_number = $this->request->post('purchase_number'); 
		$create_purchase_data->so_number = $this->request->post('so_number');
		$create_purchase_data->quote_number = $this->request->post('quote_number');

		// Invoice Data
		if( $this->request->post('date_billed') )
			$create_purchase_data->date_billed = $this->request->post('date_billed');

		if( $this->request->post('invoice_number') )
			$create_purchase_data->invoice_number = $this->request->post('invoice_number');

		$create_purchase_data->lines = array();

		foreach( $line_keys as $line_key )
		{
			if( (
					/* $this->request->post('line-account_id-'.$line_key) OR */ // Removed per a default line item account.
					$this->request->post('line-description-'.$line_key) OR
					floatval($this->request->post('line-price-'.$line_key)) OR
					floatval($this->request->post('line-quantity-'.$line_key))
				) AND
				(
					! $this->request->post('line-account_id-'.$line_key) OR
					! $this->request->post('line-description-'.$line_key) OR
					! strlen($this->request->post('line-price-'.$line_key)) OR
					! strlen($this->request->post('line-quantity-'.$line_key))
				) ) {
				return $this->_return_error("One of those line items is missing a value.");
			}
			else if( 	$this->request->post('line-account_id-'.$line_key) AND
						$this->request->post('line-description-'.$line_key) AND
						strlen($this->request->post('line-price-'.$line_key)) AND
						strlen($this->request->post('line-quantity-'.$line_key)) ) 
			{
				$purchase_line = new stdClass;
				$purchase_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$purchase_line->description = $this->request->post('line-description-'.$line_key);
				$purchase_line->amount = $this->request->post('line-price-'.$line_key);
				$purchase_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$create_purchase_data->lines[] = $purchase_line;
			}
		}
		
		$create_purchase = new Beans_Vendor_Purchase_Create($this->_beans_data_auth($create_purchase_data));
		$create_purchase_result = $create_purchase->execute();

		if( ! $create_purchase_result->success )
			return $this->_return_error("An error occurred when creating that purchase purchase:<br>".$this->_beans_result_get_error($create_purchase_result));

		$html = new View_Partials_Vendors_Purchases_Purchase;
		$html->purchase = $create_purchase_result->data->purchase;

		$this->_return_object->data->purchase = $create_purchase_result->data->purchase;
		$this->_return_object->data->purchase->html = $html->render();
	}

	public function action_purchasesendvalidate()
	{
		$purchase_id = $this->request->post('purchase_id');
		$send_email = ( $this->request->post('send-email') ? TRUE : FALSE );
		$email = $this->request->post('email');
		$send_mail = ( $this->request->post('send-mail') ? TRUE : FALSE );
		$send_done = ( $this->request->post('send-done') ? TRUE : FALSE );

		if( ! $send_email AND
			! $send_mail AND 
			! $send_done )
			return $this->_return_error("ERROR: Please select at least one option.");

		if( $send_email )
		{
			if( ! $email OR 
				! filter_var($email,FILTER_VALIDATE_EMAIL) )
				return $this->_return_error("Please provide a valid email address.");

			$company_settings = new Beans_Setup_Company_List($this->_beans_data_auth());
			$company_settings_result = $company_settings->execute();

			if( ! $company_settings_result->success )
				return $this->_return_error($this->_beans_result_get_error($company_settings_result));

			$settings = $company_settings_result->data->settings;

			if( ! isset($settings->company_email) OR 
				! strlen($settings->company_email) )
				return $this->_return_error("Email cannot be sent until you set an email address for your company within 'Setup'.");
		}
	}

	public function action_purchasesend()
	{
		$purchase_id = $this->request->post('purchase_id');
		$send_email = ( $this->request->post('send-email') ? TRUE : FALSE );
		$email = $this->request->post('email');
		$send_mail = ( $this->request->post('send-mail') ? TRUE : FALSE );
		$send_done = ( $this->request->post('send-done') ? TRUE : FALSE );

		if( ! $purchase_id )
			return $this->_return_error("ERROR: No purchase ID provided.");

		if( ! $send_email AND
			! $send_mail AND 
			! $send_done )
			return $this->_return_error("ERROR: Please select at least one option.");

		$vendor_purchase_lookup = new Beans_Vendor_Purchase_Lookup($this->_beans_data_auth((object)array(
			'id' => $purchase_id,
		)));
		$vendor_purchase_lookup_result = $vendor_purchase_lookup->execute();

		if( ! $vendor_purchase_lookup_result->success )
			return $this->_return_error("An error occurred retrieving that purchase:<br>".$this->_beans_result_get_error($vendor_purchase_lookup_result));
		
		if( $send_email )
		{
			if( ! $email OR 
				! filter_var($email,FILTER_VALIDATE_EMAIL) )
				return $this->_return_error("Please provide a valid email address.");

			$company_settings = new Beans_Setup_Company_List($this->_beans_data_auth());
			$company_settings_result = $company_settings->execute();

			if( ! $company_settings_result->success )
				return $this->_return_error($this->_beans_result_get_error($company_settings_result));

			// Shorten for sanity's sake...
			$settings = $company_settings_result->data->settings;

			if( ! isset($settings->company_email) OR 
				! strlen($settings->company_email) )
				return $this->_return_error("Email cannot be sent until you set an email address for your company within 'Setup'.");

			$message = Swift_Message::newInstance();
			
			$message
			->setSubject($settings->company_name.' - Purchase '.$vendor_purchase_lookup_result->data->purchase->purchase_number)
			->setFrom(array($settings->company_email))
			->setTo(array($email));
			
			$vendors_print_purchase = new View_Vendors_Print_Purchase();
			$vendors_print_purchase->setup_company_list_result = $company_settings_result;
			$vendors_print_purchase->purchase = $vendor_purchase_lookup_result->data->purchase;
			$vendors_print_purchase->swift_email_message = $message;

			$message = $vendors_print_purchase->render();
			
			try
			{
				if( ! Email::connect() ) 
					return $this->_return_error("Could not send email. Does your config have correct email settings?");

				if( ! Email::sendMessage($message) )
					return $this->_return_error("Could not send email. Does your config have correct email settings?");
			}
			catch( Exception $e )
			{
				return $this->_return_error("An error occurred when sending the email: ".$e->getMessage()."<br><br>Have you setup email properly in config.php?");
			}
		}

		$vendor_purchase_update_sent_data = new stdClass;
		$vendor_purchase_update_sent_data->id = $purchase_id;
		if( $send_done OR
			(
				$send_email AND
				$send_mail
			) OR (
				$send_email AND 
				$vendor_purchase_lookup_result->data->purchase->sent == "print" 
			) OR (
				$send_mail AND
				$vendor_purchase_lookup_result->data->purchase->sent == "email"
			) )
			$vendor_purchase_update_sent_data->sent = 'both';
		else if( $send_email )
			$vendor_purchase_update_sent_data->sent = 'email';
		else if( $send_mail )
			$vendor_purchase_update_sent_data->sent = 'print';

		$vendor_purchase_update_sent = new Beans_Vendor_Purchase_Update_Sent($this->_beans_data_auth($vendor_purchase_update_sent_data));
		$vendor_purchase_update_sent_result = $vendor_purchase_update_sent->execute();

		if( ! $vendor_purchase_update_sent_result->success )
			return $this->_return_error("An error occurred when updating that purchase:<br>".$this->_beans_result_get_error($vendor_purchase_update_sent_result));

		$html = new View_Partials_Vendors_Purchases_Purchase;
		$html->purchase = $vendor_purchase_update_sent_result->data->purchase;

		$this->_return_object->data->purchase = $vendor_purchase_update_sent_result->data->purchase;
		$this->_return_object->data->purchase->html = $html->render();

	}

	public function action_purchaseload()
	{
		$purchase_id = $this->request->post('purchase_id');

		$vendor_purchase_lookup = new Beans_Vendor_Purchase_Lookup($this->_beans_data_auth((object)array(
			'id' => $purchase_id,
			'page_size' => 10000,
		)));
		$vendor_purchase_lookup_result = $vendor_purchase_lookup->execute();

		if( ! $vendor_purchase_lookup_result->success )
			return $this->_return_error("An error occurred when looking up that purchase purchase:<br>".$this->_beans_result_get_error($vendor_purchase_lookup_result));

		$this->_return_object->data->purchase = $vendor_purchase_lookup_result->data->purchase;
	}

	public function action_purchaseupdate()
	{
		$purchase_id = $this->request->post('purchase_id');

		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "PURCHASELINEKEY" )
				$line_keys[] = str_replace('purchase-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$vendor_info = explode('#',$this->request->post('vendor'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this expense.");

		if( ! isset($vendor_info[0]) OR 
			! strlen($vendor_info[0]) )
			return $this->_return_error("Please select a valid vendor for this expense.");

		$update_purchase_data = new stdClass;
		$update_purchase_data->id = $purchase_id;
		$update_purchase_data->vendor_id = $vendor_info[0];
		$update_purchase_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		$update_purchase_data->date_due = ( $this->request->post('date_due') )
									   ? date("Y-m-d",strtotime($this->request->post('date_due')))
									   : date("Y-m-d",strtotime($update_purchase_data->date_created.' +'.$account_info[1].' Days'));
		$update_purchase_data->remit_address_id = $this->request->post('remit_address_id');
		$update_purchase_data->shipping_address_id = $this->request->post('shipping_address_id');
		$update_purchase_data->account_id = $account_info[0];
		$update_purchase_data->purchase_number = $this->request->post('purchase_number'); 
		$update_purchase_data->so_number = $this->request->post('so_number');
		$update_purchase_data->quote_number = $this->request->post('quote_number');

		// Invoice Data
		if( $this->request->post('date_billed') )
			$update_purchase_data->date_billed = $this->request->post('date_billed');

		if( $this->request->post('invoice_number') )
			$update_purchase_data->invoice_number = $this->request->post('invoice_number');

		$update_purchase_data->lines = array();

		foreach( $line_keys as $line_key )
		{
			if( (
					$this->request->post('line-description-'.$line_key) OR
					floatval($this->request->post('line-price-'.$line_key)) OR
					floatval($this->request->post('line-quantity-'.$line_key))
				) AND
				(
					! $this->request->post('line-account_id-'.$line_key) OR
					! $this->request->post('line-description-'.$line_key) OR
					! strlen($this->request->post('line-price-'.$line_key)) OR
					! strlen($this->request->post('line-quantity-'.$line_key))
				) ) {
				return $this->_return_error("One of those line items is missing a value.");
			}
			else if( 	$this->request->post('line-account_id-'.$line_key) AND
						$this->request->post('line-description-'.$line_key) AND
						strlen($this->request->post('line-price-'.$line_key)) AND
						strlen($this->request->post('line-quantity-'.$line_key)) ) 
			{
				$purchase_line = new stdClass;
				$purchase_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$purchase_line->description = $this->request->post('line-description-'.$line_key);
				$purchase_line->amount = $this->request->post('line-price-'.$line_key);
				$purchase_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$update_purchase_data->lines[] = $purchase_line;
			}
		}
		
		$update_purchase = new Beans_Vendor_Purchase_Update($this->_beans_data_auth($update_purchase_data));
		$update_purchase_result = $update_purchase->execute();

		if( ! $update_purchase_result->success )
			return $this->_return_error("An error occurred when updating that purchase purchase:<br>".$this->_beans_result_get_error($update_purchase_result));

		$html = new View_Partials_Vendors_Purchases_Purchase;
		$html->purchase = $update_purchase_result->data->purchase;

		$this->_return_object->data->purchase = $update_purchase_result->data->purchase;
		$this->_return_object->data->purchase->html = $html->render();
	}

	public function action_purchaserefund()
	{
		$purchase_id = $this->request->post('refund_purchase_id');

		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "PURCHASELINEKEY" )
				$line_keys[] = str_replace('purchase-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$vendor_info = explode('#',$this->request->post('vendor'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this expense.");

		if( ! isset($vendor_info[0]) OR 
			! strlen($vendor_info[0]) )
			return $this->_return_error("Please select a valid vendor for this expense.");

		$refund_purchase_data = new stdClass;
		$refund_purchase_data->id = $purchase_id;
		$refund_purchase_data->vendor_id = $vendor_info[0];
		$refund_purchase_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		$refund_purchase_data->date_due = ( $this->request->post('date_due') )
									   ? date("Y-m-d",strtotime($this->request->post('date_due')))
									   : date("Y-m-d",strtotime($refund_purchase_data->date_created.' +'.$account_info[1].' Days'));
		$refund_purchase_data->remit_address_id = $this->request->post('remit_address_id');
		$refund_purchase_data->account_id = $account_info[0];
		$refund_purchase_data->purchase_number = $this->request->post('purchase_number'); 
		$refund_purchase_data->so_number = $this->request->post('so_number');
		$refund_purchase_data->quote_number = $this->request->post('quote_number');

		$refund_purchase_data->lines = array();

		foreach( $line_keys as $line_key )
		{
			if( (
					/* $this->request->post('line-account_id-'.$line_key) OR */ // Removed per a default line item account.
					$this->request->post('line-description-'.$line_key) OR
					floatval($this->request->post('line-price-'.$line_key)) OR
					floatval($this->request->post('line-quantity-'.$line_key))
				) AND
				(
					! $this->request->post('line-account_id-'.$line_key) OR
					! $this->request->post('line-description-'.$line_key) OR
					! strlen($this->request->post('line-price-'.$line_key)) OR
					! strlen($this->request->post('line-quantity-'.$line_key))
				) ) {
				return $this->_return_error("One of those line items is missing a value.");
			}
			else if( 	$this->request->post('line-account_id-'.$line_key) AND
						$this->request->post('line-description-'.$line_key) AND
						strlen($this->request->post('line-price-'.$line_key)) AND
						strlen($this->request->post('line-quantity-'.$line_key)) ) 
			{
				$purchase_line = new stdClass;
				$purchase_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$purchase_line->description = $this->request->post('line-description-'.$line_key);
				$purchase_line->amount = $this->request->post('line-price-'.$line_key);
				$purchase_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$refund_purchase_data->lines[] = $purchase_line;
			}
		}
		
		$refund_purchase = new Beans_Vendor_Purchase_Refund($this->_beans_data_auth($refund_purchase_data));
		$refund_purchase_result = $refund_purchase->execute();

		if( ! $refund_purchase_result->success )
			return $this->_return_error("An error occurred when refunding that purchase purchase:<br>".$this->_beans_result_get_error($refund_purchase_result));

		$html = new View_Partials_Vendors_Purchases_Purchase;
		$html->purchase = $refund_purchase_result->data->purchase;

		$this->_return_object->data->purchase = $refund_purchase_result->data->purchase;
		$this->_return_object->data->purchase->html = $html->render();
	}

	public function action_purchasecancel()
	{
		$purchase_id = $this->request->post('purchase_id');

		$purchase_delete = new Beans_Vendor_Purchase_Delete($this->_beans_data_auth((object)array(
			'id' => $purchase_id,
		)));
		$purchase_delete_result = $purchase_delete->execute();

		if( ! $purchase_delete_result->success )
			return $this->_return_error("An error occurred when trying to delete that purchase purchase:<br>".$this->_beans_result_get_error($purchase_delete_result));
	}

	public function action_purchasesloadmore()
	{
		$last_purchase_id = $this->request->post('last_purchase_id');
		$last_purchase_date = $this->request->post('last_purchase_date');
		$search_terms = $this->request->post('search_terms');
		$search_vendor_id = $this->request->post('search_vendor_id');
		// N/A ?
		$search_past_due = $this->request->post('search_past_due');
		$count = $this->request->post('count');

		if( ! $count )
			$count = 20;

		$this->_return_object->data->purchases = array();

		$page = 0;

		$search_parameters = new stdClass;
		$search_parameters->sort_by = 'newest';
		$search_parameters->page_size = ($count * 2);
		$search_parameters->search_date_before = ( $last_purchase_date )
											   ? date("Y-m-d",strtotime($last_purchase_date." +1 Day"))
											   : NULL;		// ALL

		// NULLs itself from $this->request->post();
		if( $search_vendor_id AND
			strlen(trim($search_vendor_id)) )
			$search_parameters->search_vendor_id = $search_vendor_id;

		// N/A ?
		if( $search_past_due )
			$search_parameters->search_past_due = TRUE;

		$search_parameters->keywords = $search_terms;

		do
		{
			$search_parameters->page = $page;
			$vendor_purchases = new Beans_Vendor_Purchase_Search($this->_beans_data_auth($search_parameters));
			$vendor_purchases_result = $vendor_purchases->execute();
			
			if( ! $vendor_purchases_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($vendor_purchases_result));

			foreach( $vendor_purchases_result->data->purchases as $purchase ) 
			{
				if( (
						strtotime($purchase->date_created) <= strtotime($last_purchase_date) AND 
						$purchase->id < $last_purchase_id
					) OR
					strtotime($purchase->date_created) < strtotime($last_purchase_date) OR
					! $last_purchase_id )
				{
					$html = new View_Partials_Vendors_Purchases_Purchase;
					$html->purchase = $purchase;

					$purchase->html = $html->render();

					$this->_return_object->data->purchases[] = $purchase;
				}
				if( count($this->_return_object->data->purchases) >= $count )
					return;
			}
			$page++;
		}
		while( 	$page < $vendor_purchases_result->data->pages AND 
				count($this->_return_object->data->purchases) < $count );

	}
	
	public function action_paymentpurchases()
	{
		$vendor_id = $this->request->post('vendor_id');
		$has_balance = $this->request->post('has_balance');
		$search_terms = $this->request->post('search_terms');

		$search_parameters = new stdClass;

		$search_parameters->search_vendor_id = $vendor_id;
		$search_parameters->has_balance = TRUE;
		$search_parameters->sort_by = "duesoonest";
		$search_parameters->page_size = "20";
		
		$search_parameters->keywords = $search_terms;

		$vendor_purchases_search = new Beans_Vendor_Purchase_Search($this->_beans_data_auth($search_parameters));
		$vendor_purchases_search_result = $vendor_purchases_search->execute();

		if( ! $vendor_purchases_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($vendor_purchases_search_result));

		foreach( $vendor_purchases_search_result->data->purchases as $index => $purchase )
		{
			$html = new View_Partials_Vendors_Payments_Paymentpoform;
			$html->purchase = $purchase;

			$vendor_purchases_search_result->data->purchases[$index]->html = $html->render();
		}

		$this->_return_object->data->purchases = $vendor_purchases_search_result->data->purchases;

	}

	public function action_invoicepurchases()
	{
		$search_terms = $this->request->post('search_terms');

		$search_parameters = new stdClass;

		$search_parameters->sort_by = "oldest";
		$search_parameters->page_size = "20";
		$search_parameters->keywords = $search_terms;

		$vendor_purchases_search = new Beans_Vendor_Purchase_Search($this->_beans_data_auth($search_parameters));
		$vendor_purchases_search_result = $vendor_purchases_search->execute();

		if( ! $vendor_purchases_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($vendor_purchases_search_result));

		foreach( $vendor_purchases_search_result->data->purchases as $index => $purchase )
		{
			$html = new View_Partials_Vendors_Invoices_Purchase;
			$html->purchase = $purchase;

			$vendor_purchases_search_result->data->purchases[$index]->html = $html->render();
		}

		$this->_return_object->data->purchases = $vendor_purchases_search_result->data->purchases;
	}

	public function action_invoiceprocess() {
		$purchases = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "purchase-key" )
				$purchases[] = (object)array(
					'id' => $key,
					'so_number' => $this->request->post('purchase-so_number-'.$key),
					'date_billed' => ( $this->request->post('purchase-date_billed-'.$key) )
								  ? $this->request->post('purchase-date_billed-'.$key) 
								  : date("Y-m-d"),
					'invoice_number' => $this->request->post('purchase-invoice_number-'.$key),
					'invoice_amount' => $this->request->post('purchase-invoice_amount-'.$key),
					'adjustment_description' => $this->request->post('purchase-adjustment_description-'.$key),
					'adjustment_account_id' => $this->request->post('purchase-adjustment_account_id-'.$key),
				);

		if( ! count($purchases) )
			return $this->_return_error("Please include at least one purchase to invoice.");

		// Validate
		foreach( $purchases as $purchase )
		{
			$vendor_purchase_invoice_validate = new Beans_VEndor_Purchase_Invoice($this->_beans_data_auth((object)array(
				'id' => $purchase->id,
				'so_number' => $purchase->so_number,
				'date_billed' => $purchase->date_billed,
				'invoice_number' => $purchase->invoice_number,
				'invoice_amount' => $purchase->invoice_amount,
				'adjustment_description' => $purchase->adjustment_description,
				'adjustment_account_id' => $purchase->adjustment_account_id,
				'validate_only' => TRUE,
			)));
			$vendor_purchase_invoice_validate_result = $vendor_purchase_invoice_validate->execute();

			if( ! $vendor_purchase_invoice_validate_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_purchase_invoice_validate_result));
		}

		$this->_return_object->data->purchases = array();

		// Go
		foreach( $purchases as $purchase )
		{
			$vendor_purchase_invoice_validate = new Beans_VEndor_Purchase_Invoice($this->_beans_data_auth((object)array(
				'id' => $purchase->id,
				'so_number' => $purchase->so_number,
				'date_billed' => $purchase->date_billed,
				'invoice_number' => $purchase->invoice_number,
				'invoice_amount' => $purchase->invoice_amount,
				'adjustment_description' => $purchase->adjustment_description,
				'adjustment_account_id' => $purchase->adjustment_account_id,
			)));
			$vendor_purchase_invoice_validate_result = $vendor_purchase_invoice_validate->execute();

			// Unexpected
			if( ! $vendor_purchase_invoice_validate_result->success )
				return $this->_return_error(
					"An unexpected error has occurred:<br>".
					$this->_beans_result_get_error($vendor_purchase_invoice_validate_result)
				);

			$html = new View_Partials_Vendors_Invoices_Purchase;
			$html->purchase = $vendor_purchase_invoice_validate_result->data->purchase;

			$vendor_purchase_invoice_validate_result->data->purchase->html = $html->render();

			$this->_return_object->data->purchases[] = $vendor_purchase_invoice_validate_result->data->purchase;
		}

	}

	public function action_paymentcreate()
	{
		$purchases = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "purchase-key" )
				$purchases[] = (object)array(
					'purchase_id' => $key,
					'amount' => $this->request->post('purchase-amount-'.$key),
					'writeoff_balance' => ( $this->request->post('purchase-balance-writeoff-'.$key) ) ? TRUE : FALSE,
					'writeoff_amount' => $this->request->post('purchase-balance-writeoff-'.$key),
					'date_billed' => ( $this->request->post('purchase-date_billed-'.$key) ? $this->request->post('purchase-date_billed-'.$key) : date("Y-m-d") ),
					'invoice_number' => $this->request->post('purchase-invoice_number-'.$key),
					// Adding 'so_number' here would also be updated upon invoicing if applicable
				);

		$payment = FALSE;

		if( $this->request->post('replace_transaction_id') AND 
			$this->request->post('replace_transaction_id') != "new" )
		{
			$vendor_payment_replace_data = (object)array(
				'transaction_id' => $this->request->post('replace_transaction_id'),
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'payment_account_id' => $this->request->post('payment_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'code' => $this->request->post('check_number'),
				'check_number' => $this->request->post('check_number'),
				'purchases' => $purchases,
			);

			$vendor_payment_replace_data->validate_only = TRUE;
			$vendor_payment_replace_validate = new Beans_Vendor_Payment_Replace($this->_beans_data_auth($vendor_payment_replace_data));
			$vendor_payment_replace_validate_result = $vendor_payment_replace_validate->execute();

			if( ! $vendor_payment_replace_validate_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_payment_replace_validate_result));

			$vendor_payment_replace_data->validate_only = FALSE;
			$vendor_payment_replace = new Beans_Vendor_Payment_Replace($this->_beans_data_auth($vendor_payment_replace_data));
			$vendor_payment_replace_result = $vendor_payment_replace->execute();

			// This would be quite unexpected.
			if( ! $vendor_payment_replace_result->success )
				return $this->_return_error(
					"An unexpected error has occurred:<br>".
					$this->_beans_result_get_error($vendor_payment_replace_result)
				);

			$payment = $vendor_payment_replace_result->data->payment;
		}
		else
		{
			// Check for duplicates
			if( $this->request->post('replace_transaction_id') != "new" )
			{
				if( ! $this->request->post('payment_account_id') )
					return $this->_return_error("Please select a payment account.");

				$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
					'id' => $this->request->post('payment_account_id'),
				)));
				$account_lookup_result = $account_lookup->execute();

				if( ! $account_lookup_result->success )
					return $this->_return_error("An unexpected error has occurred:<br>".$this->_beans_result_get_error($account_lookup_result));

				// Check for duplicate transactions.
				$account_transaction_match = new Beans_Account_Transaction_Match($this->_beans_data_auth((object)array(
					'date_range_days' => 7,
					'ignore_payments' => TRUE,
					'account_transactions' => array(
						(object)array(
							'account_id' => $this->request->post('payment_account_id'),
							'hash' => 'vendorpayment',
							'amount' => ( $this->request->post('amount') * -1 * $account_lookup_result->data->account->type->table_sign ),
							'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
						),
					),
				)));

				$account_transaction_match_result = $account_transaction_match->execute();

				if( ! $account_transaction_match_result->success )
					return $this->_return_error("An error occurred when checking for duplicate transactions:<br>".$this->_beans_result_get_error($account_transaction_match_result));

				// There should only be one result to this - and it should match, etc.
				if( count($account_transaction_match_result->data->account_transactions) AND 
					$account_transaction_match_result->data->account_transactions[0]->duplicate )
				{
					// Duplicate transaction detected.
					$this->_return_object->data->duplicate_transaction = $account_transaction_match_result->data->account_transactions[0]->transaction;
					$error_message = "It looks like that might be a duplicate transaction that is already recorded in the system.<br><br>";
					$error_message .= "Is this transaction the same as the payment you are trying to record?<br><br>";
					$error_message .= "Transaction ID: ".$account_transaction_match_result->data->account_transactions[0]->transaction->id."<br>";
					$error_message .= "Description: ".$account_transaction_match_result->data->account_transactions[0]->transaction->description."<br>";
					$error_message .= "Date: ".$account_transaction_match_result->data->account_transactions[0]->transaction->date."<br>";
					$error_message .= "Amount: $".number_format($account_transaction_match_result->data->account_transactions[0]->transaction->amount,2,'.',',')."<br>";
					$error_message .= "<br>You can either add your payment info to this transaction if this is the same, or create a brand new transaction record in the journal.";
					return $this->_return_error($error_message);
				}

			}

			$vendor_payment_create_data = (object)array(
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'payment_account_id' => $this->request->post('payment_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'code' => $this->request->post('check_number'),
				'check_number' => $this->request->post('check_number'),
				'purchases' => $purchases,
			);

			$vendor_payment_create_data->validate_only = TRUE;
			$vendor_payment_create_validate = new Beans_Vendor_Payment_Create($this->_beans_data_auth($vendor_payment_create_data));
			$vendor_payment_create_validate_result = $vendor_payment_create_validate->execute();

			if( ! $vendor_payment_create_validate_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_payment_create_validate_result));

			$vendor_payment_create_data->validate_only = FALSE;
			$vendor_payment_create = new Beans_Vendor_Payment_Create($this->_beans_data_auth($vendor_payment_create_data));
			$vendor_payment_create_result = $vendor_payment_create->execute();

			// This would be quite unexpected.
			if( ! $vendor_payment_create_result->success )
				return $this->_return_error(
					"An unexpected error has occurred:<br>".
					$this->_beans_result_get_error($vendor_payment_create_result)
				);

			$payment = $vendor_payment_create_result->data->payment;
		}

		if( $this->request->post('remit_address_id') AND 
			$this->request->post('remit_address_id') != "skip" )
		{
			foreach( $purchases as $purchase )
			{
				$vendor_purchase_update_address = new Beans_Vendor_Purchase_Update_Address($this->_beans_data_auth((object)array(
					'id' => $purchase->purchase_id,
					'remit_address_id' => $this->request->post('remit_address_id'),
				)));
				$vendor_purchase_update_address_result = $vendor_purchase_update_address->execute();

				if( ! $vendor_purchase_update_address_result->success )
					$this->_return_object->error = ( $this->_return_object->error ) ? $this->_return_object->error.'<br>'.$this->_beans_result_get_error($vendor_purchase_update_address_result) : 'Payment was successfully added, but some errors were encountered:<br>'.$this->_beans_result_get_error($vendor_purchase_update_address_result);
			}
		}

		if( ! $payment )
			return $this->_return_error(
				"An unexpected error has occurred and the payment could not be recorded. ".
				"Please try reloading the page and trying again."
			);

		if( $this->request->post('print_check') AND 
			$this->request->post('print_check') == "1" )
			$this->_print_check_queue_payment_add($payment->id);
		
		$html = new View_Partials_Vendors_Payments_Payment;
		$html->payment = $payment;

		$payment->html = $html->render();

		$this->_return_object->data->payment = $payment;
	}
	
	public function action_paymentload()
	{
		$payment_id = $this->request->post('payment_id');

		$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

		if( ! $vendor_payment_lookup_result->success )
			return $this->_return_error($this->_beans_result_get_error($vendor_payment_lookup_result));

		$payment = $vendor_payment_lookup_result->data->payment;

		foreach( $payment->purchase_payments as $index => $purchase_payment )
		{
			$html = new View_Partials_Vendors_Payments_Paymentpo;
			$html->purchase_payment = $purchase_payment;

			$payment->purchase_payments[$index]->html = $html->render();
		}

		$this->_return_object->data->payment = $payment;
	}

	public function action_paymentdelete()
	{
		$payment_id = $this->request->post('payment_id');

		$vendor_payment_cancel = new Beans_Vendor_Payment_Cancel($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$vendor_payment_cancel_result = $vendor_payment_cancel->execute();

		if( ! $vendor_payment_cancel_result->success )
			$this->_return_error($this->_beans_result_get_error($vendor_payment_cancel_result));

		$this->_print_check_queue_payment_remove($payment_id);

	}

	public function action_paymentupdate()
	{
		$payment_id = $this->request->post('payment_id');

		$purchases = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "purchase-key" )
				$purchases[] = (object)array(
					'purchase_id' => $key,
					'amount' => $this->request->post('purchase-amount-'.$key),
					'writeoff_balance' => ( $this->request->post('purchase-balance-writeoff-'.$key) ) ? TRUE : FALSE,
					'writeoff_amount' => $this->request->post('purchase-balance-writeoff-'.$key),
					'so_number' => $this->request->post('purchase-so_number-'.$key),
					'invoice_number' => $this->request->post('purchase-invoice_number-'.$key),
				);

		// This is the payment we end up creating.
		$payment = FALSE;

		if( $this->request->post('replace_transaction_id') AND 
			$this->request->post('replace_transaction_id') != "new" )
		{
			$vendor_payment_replace_data = (object)array(
				'transaction_id' => $this->request->post('replace_transaction_id'),
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'payment_account_id' => $this->request->post('payment_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'code' => $this->request->post('check_number'),
				'check_number' => $this->request->post('check_number'),
				'purchases' => $purchases,
			);

			// REPLACE
			$vendor_payment_replace_data->validate_only = FALSE;
			$vendor_payment_replace = new Beans_Vendor_Payment_Replace($this->_beans_data_auth($vendor_payment_replace_data));

			$vendor_payment_replace_result = $vendor_payment_replace->execute();

			if( ! $vendor_payment_replace_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_payment_replace_result));

			$payment = $vendor_payment_replace_result->data->payment;
		}
		else
		{
			// Check for duplicates
			if( $this->request->post('replace_transaction_id') != "new" )
			{
				if( ! $this->request->post('payment_account_id') )
					return $this->_return_error("Please select a payment account.");

				$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
					'id' => $this->request->post('payment_account_id'),
				)));
				$account_lookup_result = $account_lookup->execute();

				if( ! $account_lookup_result->success )
					return $this->_return_error("An unexpected error has occurred:<br>".$this->_beans_result_get_error($account_lookup_result));

				// Check for duplicate transactions.
				$account_transaction_match = new Beans_Account_Transaction_Match($this->_beans_data_auth((object)array(
					'date_range_days' => 7,
					'ignore_payments' => TRUE,
					'account_transactions' => array(
						(object)array(
							'account_id' => $this->request->post('payment_account_id'),
							'hash' => 'vendorpayment',
							'amount' => ( $this->request->post('amount') * -1 * $account_lookup_result->data->account->type->table_sign ),
							'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
						),
					),
				)));

				$account_transaction_match_result = $account_transaction_match->execute();

				if( ! $account_transaction_match_result->success )
					return $this->_return_error("An error occurred when checking for duplicate transactions:<br>".$this->_beans_result_get_error($account_transaction_match_result));

				// There should only be one result to this - and it should match, etc.
				if( $account_transaction_match_result->data->account_transactions[0]->duplicate AND
					$account_transaction_match_result->data->account_transactions[0]->transaction->id != $payment_id )
				{
					// Duplicate transaction detected.
					$this->_return_object->data->duplicate_transaction = $account_transaction_match_result->data->account_transactions[0]->transaction;
					$error_message = "It looks like that might be a duplicate transaction that is already recorded in the system.<br><br>";
					$error_message .= "Is this transaction the same as the payment you are trying to record?<br><br>";
					$error_message .= "Transaction ID: ".$account_transaction_match_result->data->account_transactions[0]->transaction->id."<br>";
					$error_message .= "Description: ".$account_transaction_match_result->data->account_transactions[0]->transaction->description."<br>";
					$error_message .= "Date: ".$account_transaction_match_result->data->account_transactions[0]->transaction->date."<br>";
					$error_message .= "Amount: $".number_format($account_transaction_match_result->data->account_transactions[0]->transaction->amount,2,'.',',')."<br>";
					$error_message .= "<br>You can either add your payment info to this transaction if this is the same, or create a brand new transaction record in the journal.";
					return $this->_return_error($error_message);
				}

			}

			$vendor_payment_update = new Beans_Vendor_Payment_Update($this->_beans_data_auth((object)array(
				'id' => $payment_id,
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'payment_account_id' => $this->request->post('payment_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'check_number' => $this->request->post('check_number'),
				'purchases' => $purchases,
			)));

			$vendor_payment_update_result = $vendor_payment_update->execute();

			if( ! $vendor_payment_update_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_payment_update_result));

			$payment = $vendor_payment_update_result->data->payment;
		}

		// V2Item
		// Remove the attributes_only request from here and Beans_Vendor_Purchase_Update
		// Update purchases with SO/Invoice numbers.
		foreach( $purchases as $purchase )
		{
			$vendor_purchase_update = new Beans_Vendor_Purchase_Update($this->_beans_data_auth((object)array(
				'id' => $purchase->purchase_id,
				'attributes_only' => TRUE,
				'so_number' => ( strlen($purchase->so_number) ) ? $purchase->so_number : NULL,
				'invoice_number' => ( strlen($purchase->invoice_number) ) ? $purchase->invoice_number : NULL,
			)));
			$vendor_purchase_update_result = $vendor_purchase_update->execute();

			if( ! $vendor_purchase_update_result->success )
				$this->_return_object->error = ( $this->_return_object->error ) ? $this->_return_object->error.'<br>'.$this->_beans_result_get_error($vendor_purchase_update_result) : 'Payment was successfully added, but some errors were encountered:<br>'.$this->_beans_result_get_error($vendor_purchase_update_result);
		}

		// Update remit address ID
		if( $this->request->post('remit_address_id') AND 
			$this->request->post('remit_address_id') != "skip" )
		{
			foreach( $purchases as $purchase )
			{
				$vendor_purchase_update_address = new Beans_Vendor_Purchase_Update_Address($this->_beans_data_auth((object)array(
					'id' => $purchase->purchase_id,
					'remit_address_id' => $this->request->post('remit_address_id'),
				)));
				$vendor_purchase_update_address_result = $vendor_purchase_update_address->execute();

				if( ! $vendor_purchase_update_address_result->success )
					$this->_return_object->error = ( $this->_return_object->error ) ? $this->_return_object->error.'<br>'.$this->_beans_result_get_error($vendor_purchase_update_address_result) : 'Payment was successfully added, but some errors were encountered:<br>'.$this->_beans_result_get_error($vendor_purchase_update_address_result);
			}
		}

		$this->_print_check_queue_payment_remove($payment_id);

		if( $this->request->post('print_check') AND 
			$this->request->post('print_check') == "1" )
			$this->_print_check_queue_payment_add($payment->id);
		
		$html = new View_Partials_Vendors_Payments_Payment;
		$html->payment = $payment;

		$payment->html = $html->render();

		$this->_return_object->data->payment = $payment;
	}

	public function action_taxsearch()
	{
		$search_terms = $this->request->post('search_terms');

		$tax_search = new Beans_Tax_Search($this->_beans_data_auth((object)array(
			'search_name' => $search_terms,
		)));
		$tax_search_result = $tax_search->execute();

		$this->_return_object->data->taxes = $tax_search_result->data->taxes;
	}

	public function action_taxpaymentprep()
	{
		$tax_id = $this->request->post('tax_id');
		$payment_id = $this->request->post('payment_id');
		$date_start = $this->request->post('date_start');
		$date_end = $this->request->post('date_end');

		$tax_prep = new Beans_Tax_Prep($this->_beans_data_auth((object)array(
			'id' => $tax_id,
			'payment_id' => $payment_id,
			'date_start' => $date_start,
			'date_end' => $date_end,
		)));
		$tax_prep_result = $tax_prep->execute();

		$this->_return_object->data->tax_prep = $tax_prep_result->data->tax_prep;
	}

	public function action_taxpaymentcreate()
	{
		$tax_id = $this->request->post('tax_id');
		$payment_account_id = $this->request->post('payment_account_id');
		$writeoff_account_id = $this->request->post('writeoff_account_id');
		$date = ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d");
		$date_start = $this->request->post('date_start');
		$date_end = $this->request->post('date_end');
		$amount = $this->request->post('amount');
		$writeoff_amount = $this->request->post('writeoff_amount');
		$check_number = $this->request->post('check_number');

		$payment = FALSE;

		if( $this->request->post('replace_transaction_id') AND 
			$this->request->post('replace_transaction_id') != "new" )
		{
			// REPLACE
			$tax_payment_replace = new Beans_Tax_Payment_Replace($this->_beans_data_auth((object)array(
				'transaction_id' => $this->request->post('replace_transaction_id'),
				'tax_id' => $tax_id,
				'date' => $date,
				'date_start' => $date_start,
				'date_end' => $date_end,
				'amount' => $amount,
				'check_number' => $check_number,
				'payment_account_id' => $payment_account_id,
				'writeoff_account_id' => $writeoff_account_id,
				'writeoff_amount' => $writeoff_amount,
			)));

			$tax_payment_replace_result = $tax_payment_replace->execute();

			if( ! $tax_payment_replace_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_replace_result));

			$payment = $tax_payment_replace_result->data->payment;
		}
		else
		{
			// Check for duplicates
			if( $this->request->post('replace_transaction_id') != "new" )
			{
				if( ! $payment_account_id )
					return $this->_return_error("Please select a payment account.");

				$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
					'id' => $payment_account_id,
				)));
				$account_lookup_result = $account_lookup->execute();

				if( ! $account_lookup_result->success )
					return $this->_return_error("An unexpected error has occurred.<br>".$this->_beans_result_get_error($account_lookup_result));

				// Check for duplicate transactions.
				$account_transaction_match = new Beans_Account_Transaction_Match($this->_beans_data_auth((object)array(
					'date_range_days' => 7,
					'ignore_payments' => TRUE,
					'account_transactions' => array(
						(object)array(
							'account_id' => $payment_account_id,
							'hash' => 'taxpayment',
							'amount' => ($amount * -1 * $account_lookup_result->data->account->type->table_sign),
							'date' => $date,
						),
					),
				)));

				$account_transaction_match_result = $account_transaction_match->execute();

				if( ! $account_transaction_match_result->success )
					return $this->_return_error("An error occurred when checking for duplicate transactions:<br>".$this->_beans_result_get_error($account_transaction_match_result));

				// There should only be one result to this - and it should match, etc.
				if( $account_transaction_match_result->data->account_transactions[0]->duplicate )
				{
					// Duplicate transaction detected.
					$this->_return_object->data->duplicate_transaction = $account_transaction_match_result->data->account_transactions[0]->transaction;
					$error_message = "It looks like that might be a duplicate transaction that is already recorded in the system.<br><br>";
					$error_message .= "Is this transaction the same as the payment you are trying to record?<br><br>";
					$error_message .= "Transaction ID: ".$account_transaction_match_result->data->account_transactions[0]->transaction->id."<br>";
					$error_message .= "Description: ".$account_transaction_match_result->data->account_transactions[0]->transaction->description."<br>";
					$error_message .= "Date: ".$account_transaction_match_result->data->account_transactions[0]->transaction->date."<br>";
					$error_message .= "Amount: $".number_format($account_transaction_match_result->data->account_transactions[0]->transaction->amount,2,'.',',')."<br>";
					$error_message .= "<br>You can either add your payment info to this transaction if this is the same, or create a brand new transaction record in the journal.";
					return $this->_return_error($error_message);
				}

			}

			$tax_payment_create = new Beans_Tax_Payment_Create($this->_beans_data_auth((object)array(
				'tax_id' => $tax_id,
				'date' => $date,
				'check_number' => $check_number,
				'date_start' => $date_start,
				'date_end' => $date_end,
				'amount' => $amount,
				'payment_account_id' => $payment_account_id,
				'writeoff_account_id' => $writeoff_account_id,
				'writeoff_amount' => $writeoff_amount,
			)));

			$tax_payment_create_result = $tax_payment_create->execute();

			if( ! $tax_payment_create_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_create_result));

			$payment = $tax_payment_create_result->data->payment;
		}
		
		if( $this->request->post("print_check") AND 
			$this->request->post("print_check") == "1" )
			$this->_print_check_queue_taxpayment_add($payment->id);

		$html = new View_Partials_Taxes_Payments_Payment;
		$html->payment = $payment;
		
		$payment->html = $html->render();
		
		$this->_return_object->data->payment = $payment;
	}

	public function action_taxpaymentupdate()
	{

		$tax_id = $this->request->post('tax_id');
		$payment_account_id = $this->request->post('payment_account_id');
		$writeoff_account_id = $this->request->post('writeoff_account_id');
		$date = ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d");
		$date_start = $this->request->post('date_start');
		$date_end = $this->request->post('date_end');
		$amount = $this->request->post('amount');
		$writeoff_amount = $this->request->post('writeoff_amount');
		$check_number = $this->request->post('check_number');

		$payment_id = $this->request->post('payment_id');

		// This is the payment we end up creating.
		$payment = FALSE;

		if( $this->request->post('replace_transaction_id') AND 
			$this->request->post('replace_transaction_id') != "new" )
		{
			$tax_payment_replace_data = (object)array(
				'transaction_id' => $this->request->post('replace_transaction_id'),
				'tax_id' => $tax_id,
				'date' => $date,
				'check_number' => $check_number,
				'date_start' => $date_start,
				'date_end' => $date_end,
				'amount' => $amount,
				'payment_account_id' => $payment_account_id,
				'writeoff_account_id' => $writeoff_account_id,
				'writeoff_amount' => $writeoff_amount,
			);

			$tax_payment_replace_data->validate_only = TRUE;
			$tax_payment_validate = new Beans_Tax_Payment_Replace($this->_beans_data_auth($tax_payment_replace_data));
			$tax_payment_validate_result = $tax_payment_validate->execute();
			
			if( ! $tax_payment_validate_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_validate_result));

			// DELETE
			$tax_payment_cancel = new Beans_Tax_Payment_Cancel($this->_beans_data_auth((object)array(
				'id' => $payment_id,
			)));
			$tax_payment_cancel_result = $tax_payment_cancel->execute();

			if( ! $tax_payment_cancel_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_cancel_result));

			// REPLACE
			$tax_payment_replace_data->validate_only = FALSE;
			$tax_payment_replace = new Beans_Tax_Payment_Replace($this->_beans_data_auth($tax_payment_replace_data));

			$tax_payment_replace_result = $tax_payment_replace->execute();

			if( ! $tax_payment_replace_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_replace_result));

			$payment = $tax_payment_replace_result->data->payment;
		}
		else
		{
			// Check for duplicates
			if( $this->request->post('replace_transaction_id') != "new" )
			{
				if( ! $payment_account_id )
					return $this->_return_error("Please select a payment account.");

				$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
					'id' => $payment_account_id,
				)));
				$account_lookup_result = $account_lookup->execute();

				if( ! $account_lookup_result->success )
					return $this->_return_error("An unexpected error has occurred.<br>".$this->_beans_result_get_error($account_lookup_result));

				// Check for duplicate transactions.
				$account_transaction_match = new Beans_Account_Transaction_Match($this->_beans_data_auth((object)array(
					'date_range_days' => 7,
					'ignore_payments' => TRUE,
					'account_transactions' => array(
						(object)array(
							'account_id' => $payment_account_id,
							'hash' => 'taxpayment',
							'amount' => ($amount * $account_lookup_result->data->account->type->table_sign),
							'date' => $date,
						),
					),
				)));

				$account_transaction_match_result = $account_transaction_match->execute();

				if( ! $account_transaction_match_result->success )
					return $this->_return_error("An error occurred when checking for duplicate transactions:<br>".$this->_beans_result_get_error($account_transaction_match_result));

				// There should only be one result to this - and it should match, etc.
				if( $account_transaction_match_result->data->account_transactions[0]->duplicate )
				{
					// Duplicate transaction detected.
					$this->_return_object->data->duplicate_transaction = $account_transaction_match_result->data->account_transactions[0]->transaction;
					$error_message = "It looks like that might be a duplicate transaction that is already recorded in the system.<br><br>";
					$error_message .= "Is this transaction the same as the payment you are trying to record?<br><br>";
					$error_message .= "Transaction ID: ".$account_transaction_match_result->data->account_transactions[0]->transaction->id."<br>";
					$error_message .= "Description: ".$account_transaction_match_result->data->account_transactions[0]->transaction->description."<br>";
					$error_message .= "Date: ".$account_transaction_match_result->data->account_transactions[0]->transaction->date."<br>";
					$error_message .= "Amount: $".number_format($account_transaction_match_result->data->account_transactions[0]->transaction->amount,2,'.',',')."<br>";
					$error_message .= "<br>You can either add your payment info to this transaction if this is the same, or create a brand new transaction record in the journal.";
					return $this->_return_error($error_message);
				}

			}

			$tax_payment_update = new Beans_Tax_Payment_Update($this->_beans_data_auth((object)array(
				'id' => $payment_id,
				'tax_id' => $tax_id,
				'date' => $date,
				'date_start' => $date_start,
				'date_end' => $date_end,
				'amount' => $amount,
				'check_number' => $check_number,
				'payment_account_id' => $payment_account_id,
				'writeoff_account_id' => $writeoff_account_id,
				'writeoff_amount' => $writeoff_amount,
			)));

			$tax_payment_update_result = $tax_payment_update->execute();

			if( ! $tax_payment_update_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_update_result));

			$payment = $tax_payment_update_result->data->payment;
		}

		$this->_print_check_queue_taxpayment_remove($payment_id);

		if( $this->request->post("print_check") AND 
			$this->request->post("print_check") == "1" )
			$this->_print_check_queue_taxpayment_add($payment->id);

		$html = new View_Partials_Taxes_Payments_Payment;
		$html->payment = $payment;

		$payment->html = $html->render();

		$this->_return_object->data->payment = $payment;
	}

	public function action_taxpaymentload()
	{
		$payment_id = $this->request->post('payment_id');

		$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$tax_payment_lookup_result = $tax_payment_lookup->execute();

		if( ! $tax_payment_lookup_result->success )
			return $this->_return_error($this->_beans_result_get_error($tax_payment_lookup_result));

		$payment = $tax_payment_lookup_result->data->payment;

		$this->_return_object->data->payment = $payment;
	}

	public function action_taxpaymentcancel()
	{
		$payment_id = $this->request->post('payment_id');

		$tax_payment_cancel = new Beans_Tax_Payment_Cancel($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$tax_payment_cancel_result = $tax_payment_cancel->execute();

		if( ! $tax_payment_cancel_result->success )
			return $this->_return_error($this->_beans_result_get_error($tax_payment_cancel_result));

		$this->_print_check_queue_taxpayment_remove($payment_id);
	}

	public function action_taxpaymentsearch() 
	{
		$search_terms = $this->request->post('search_terms');
		$count = $this->request->post('count');

		if( ! $count )
			$count = 5;

		$tax_payment_search_data = new stdClass;
		$tax_payment_search_data->sort_by = 'newest';
		$tax_payment_search_data->page_size = $count;
		$tax_payment_search_data->search_tax_name = $search_terms;
		
		$tax_payment_search = new Beans_Tax_Payment_Search($this->_beans_data_auth($tax_payment_search_data));

		$tax_payment_search_result = $tax_payment_search->execute();

		if( ! $tax_payment_search_result->success )
			return $this->_return_error($tax_payment_search_result->error);

		foreach( $tax_payment_search_result->data->payments as $index => $payment )
		{
			$html = new View_Partials_Taxes_Payments_Payment;
			$html->payment = $payment;

			$tax_payment_search_result->data->payments[$index]->html = $html->render();
		}

		$this->_return_object->data->payments = $tax_payment_search_result->data->payments;
	}

	public function action_expenselines()
	{
		$search_term = $this->request->post('search_term');

		$vendor_expense_line_search = new Beans_Vendor_Expense_Line_Search($this->_beans_data_auth((object)array(
			'search_description' => $search_term,
		)));
		$vendor_expense_line_search_result = $vendor_expense_line_search->execute();

		if( ! $vendor_expense_line_search_result->success ) 
			return $this->_return_error($this->_beans_result_get_error($vendor_expense_line_search_result));

		$this->_return_object->data->lines = $vendor_expense_line_search_result->data->expense_lines;
	}

	public function action_purchaselines()
	{
		$search_term = $this->request->post('search_term');

		$vendor_purchase_line_search = new Beans_Vendor_Purchase_Line_Search($this->_beans_data_auth((object)array(
			'search_description' => $search_term,
		)));
		$vendor_purchase_line_search_result = $vendor_purchase_line_search->execute();

		if( ! $vendor_purchase_line_search_result->success ) 
			return $this->_return_error($this->_beans_result_get_error($vendor_purchase_line_search_result));

		$this->_return_object->data->lines = $vendor_purchase_line_search_result->data->purchase_lines;
	}

	public function action_paymentsearch()
	{
		$search_terms = $this->request->post('search_terms');
		$count = $this->request->post('count');
		$page = $this->request->post('page');

		if( ! $count )
			$count = 5;

		if( ! $page ) 
			$page = 0;

		$vendor_payment_search_data = new stdClass;
		$vendor_payment_search_data->sort_by = 'newest';
		$vendor_payment_search_data->page_size = $count;
		$vendor_payment_search_data->page = $page;
		$vendor_payment_search_data->keywords = $search_terms;

		// Include this as a checkbox?
		$vendor_payment_search_data->include_invoices = TRUE;

		$vendor_payment_search = new Beans_Vendor_Payment_Search($this->_beans_data_auth($vendor_payment_search_data));

		$vendor_payment_search_result = $vendor_payment_search->execute();

		if( ! $vendor_payment_search_result->success )
			return $this->_return_error($vendor_payment_search_result->error);

		foreach( $vendor_payment_search_result->data->payments as $index => $payment )
		{
			$html = new View_Partials_Vendors_Payments_Payment;
			$html->payment = $payment;

			$vendor_payment_search_result->data->payments[$index]->html = $html->render();
		}

		$this->_return_object->data = $vendor_payment_search_result->data;
	}

	public function action_clearchecks()
	{
		Session::instance()->delete('check_print_queue');
	}

	public function action_printchecks()
	{

		$check_number_start = $this->request->post('check_number_start');

		if( ! $check_number_start OR 
			! is_numeric($check_number_start) OR
			intval($check_number_start) != $check_number_start )
			return $this->_return_error("Please provide a valid starting check number.");

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
					return $this->_return_error($this->_beans_result_get_error($expense_lookup_result));

				$print_vendor_checks->expenses[] = $expense_lookup_result->data->expense;

			}
			
			foreach( $payment_ids as $payment_id )
			{
				$payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $payment_id,
				)));
				$payment_lookup_result = $payment_lookup->execute();

				if( ! $payment_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($payment_lookup_result));

				$print_vendor_checks->payments[] = $payment_lookup_result->data->payment;
			}
			
			foreach( $taxpayment_ids as $taxpayment_id )
			{
				$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $taxpayment_id,
				)));
				$tax_payment_lookup_result = $tax_payment_lookup->execute();

				if( ! $tax_payment_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($tax_payment_lookup_result));

				$print_vendor_checks->taxpayments[] = $tax_payment_lookup_result->data->payment;
			}
		}
		else
		{
			return $this->_return_error("Please include at least one check to print.");
		}

		$this->_return_object->data->checks = array();

		// Update then show and create checks as we go...
		foreach( $print_vendor_checks->expenses as $expense )
		{
			$vendor_expense_update_check = new Beans_Vendor_Expense_Update_Check($this->_beans_data_auth((object)array(
				'id' => $expense->id,
				'check_number' => $check_number_start++,
			)));
			$vendor_expense_update_check_result = $vendor_expense_update_check->execute();

			$this->_print_check_queue_expense_remove($expense->id);

			if( ! $vendor_expense_update_check_result->success )
				return $this->_return_error("Unexpected error has occurred: ".$this->_beans_result_get_error($vendor_expense_update_check_result));

			$vendor_checks_newcheck = new View_Partials_Vendors_Checks_Check;
			$vendor_checks_newcheck->expense = $vendor_expense_update_check_result->data->expense;

			$this->_return_object->data->checks[] = $vendor_checks_newcheck->render();
		}

		foreach( $print_vendor_checks->payments as $payment )
		{
			$vendor_payment_update_check = new Beans_Vendor_Payment_Update_Check($this->_beans_data_auth((object)array(
				'id' => $payment->id,
				'check_number' => $check_number_start++,
			)));
			$vendor_payment_update_check_result = $vendor_payment_update_check->execute();

			$this->_print_check_queue_payment_remove($payment->id);

			if( ! $vendor_payment_update_check_result->success )
				return $this->_return_error("Unexpected error has occurred: ".$this->_beans_result_get_error($vendor_payment_update_check_result));

			$vendor_checks_newcheck = new View_Partials_Vendors_Checks_Check;
			$vendor_checks_newcheck->payment = $vendor_payment_update_check_result->data->payment;

			$this->_return_object->data->checks[] = $vendor_checks_newcheck->render();
		}

		foreach( $print_vendor_checks->taxpayments as $taxpayment )
		{
			$tax_payment_update_check = new Beans_Tax_Payment_Update_Check($this->_beans_data_auth((object)array(
				'id' => $taxpayment->id,
				'check_number' => $check_number_start++,
			)));
			$tax_payment_update_check_result = $tax_payment_update_check->execute();

			$this->_print_check_queue_taxpayment_remove($taxpayment->id);

			if( ! $tax_payment_update_check_result->success )
				return $this->_return_error("Unexpected error has occurred: ".$this->_beans_result_get_error($tax_payment_update_check_result));

			$vendor_checks_newcheck = new View_Partials_Vendors_Checks_Check;
			$vendor_checks_newcheck->taxpayment = $tax_payment_update_check_result->data->payment;

			$this->_return_object->data->checks[] = $vendor_checks_newcheck->render();
		}

		$this->_return_object->data->next_check_number = $check_number_start;
		
		// $this->_return_object->data->printhtml = $print_vendor_checks->render();
	}

	public function action_checkadd()
	{
		$key = $this->request->post('key');

		if( ! $key )
			return $this->_return_error("Invalid key provided.");

		$key_array = explode('-', $key);

		if( $key_array[0] == "expense" )
		{
			if( $this->_print_check_queue_expense_exists($key_array[1]) )
				return $this->_return_error("That expense is already in the queue to be printed.");

			$vendor_expense_lookup = new Beans_Vendor_Expense_Lookup($this->_beans_data_auth((object)array(
				'id' => $key_array[1],
			)));
			$vendor_expense_lookup_result = $vendor_expense_lookup->execute();

			if( ! $vendor_expense_lookup_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_expense_lookup_result));

			$this->_print_check_queue_expense_add($key_array[1]);

			$vendor_checks_newcheck = new View_Partials_Vendors_Checks_Newcheck;
			$vendor_checks_newcheck->expense = $vendor_expense_lookup_result->data->expense;
			$this->_return_object->data->newcheck = $vendor_checks_newcheck->render();
		}
		else if ( $key_array[0] == "payment" )
		{
			if( $this->_print_check_queue_payment_exists($key_array[1]) )
				return $this->_return_error("That payment is already in the queue to be printed.");

			$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
				'id' => $key_array[1],
			)));
			$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

			if( ! $vendor_payment_lookup_result->success )
				return $this->_return_error($this->_beans_result_get_error($vendor_payment_lookup_result));

			$this->_print_check_queue_payment_add($key_array[1]);

			$vendor_checks_newcheck = new View_Partials_Vendors_Checks_Newcheck;
			$vendor_checks_newcheck->payment = $vendor_payment_lookup_result->data->payment;
			$this->_return_object->data->newcheck = $vendor_checks_newcheck->render();
		}
		else if ( $key_array[0] == "taxpayment" )
		{
			if( $this->_print_check_queue_taxpayment_exists($key_array[1]) )
				return $this->_return_error("That tax payment is already in the queue to be printed.");

			$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
				'id' => $key_array[1],
			)));
			$tax_payment_lookup_result = $tax_payment_lookup->execute();

			if( ! $tax_payment_lookup_result->success )
				return $this->_return_error($this->_beans_result_get_error($tax_payment_lookup_result));

			$this->_print_check_queue_taxpayment_add($key_array[1]);

			$vendor_checks_newcheck = new View_Partials_Vendors_Checks_Newcheck;
			$vendor_checks_newcheck->taxpayment = $tax_payment_lookup_result->data->payment;
			$this->_return_object->data->newcheck = $vendor_checks_newcheck->render();
		}
		else
		{
			return $this->_return_error("Invalid key provided.");
		}
	}

	public function action_checksearch()
	{
		$search_terms = $this->request->post('search_terms');
		$count = $this->request->post('count');
		$page = $this->request->post('page');

		if( ! $count )
			$count = 5;

		if( ! $page ) 
			$page = 0;

		$search_parameters = (object)array(
			'page' => $page,
			'page_size' => $count,
			'sort_by' => 'checknewest',
			'vendor_keywords' => ' ',
		);

		foreach( explode(' ',$search_terms) as $search_term )
		{
			$term = trim($search_term);

			if( $term AND 
				is_numeric($search_term) )
				$search_parameters->check_number = $term;
			else if( $term AND 
				date('Y-m-d',strtotime($term)) == $term )
				$search_parameters->date = $term;
			else 
				$search_parameters->vendor_keywords .= $term.' ';
		}

		$account_transaction_search = new Beans_Account_Transaction_Search_Check($this->_beans_data_auth($search_parameters));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( ! $account_transaction_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($account_transaction_search_result));

		$this->_return_object->data = $account_transaction_search_result->data;

		// $this->_return_object->data->checks = array();

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

				if( ! $vendor_expense_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($vendor_expense_lookup_result));

				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->expense = $vendor_expense_lookup_result->data->expense;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();
			}
			else if( $transaction->payment AND 
					 $transaction->payment == "vendor" )
			{
				$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $transaction->id,
				)));
				$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

				if( ! $vendor_payment_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($vendor_payment_lookup_result));

				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->payment = $vendor_payment_lookup_result->data->payment;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();

			}
			else if( $transaction->tax_payment AND 
					 isset($transaction->tax_payment->id) )
			{
				$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $transaction->tax_payment->id,
				)));
				$tax_payment_lookup_result = $tax_payment_lookup->execute();

				if( ! $tax_payment_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($tax_payment_lookup_result));

				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->taxpayment = $tax_payment_lookup_result->data->payment;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();
			}
			else
			{
				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->transaction = $transaction;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();
			}
		}
	}

	public function action_vendorpaymentsearch()
	{
		$search_terms = $this->request->post('search_terms');
		$search_vendor_id = $this->request->post('search_vendor_id');
		$count = $this->request->post('count');
		$page = $this->request->post('page');

		if( ! $count )
			$count = 5;

		if( ! $page ) 
			$page = 0;

		$search_parameters = (object)array(
			'page' => $page,
			'page_size' => $count,
			'sort_by' => 'newest',
			'vendor_id' => $search_vendor_id,
			'keywords' => '',
			'search_and' => FALSE,
		);

		foreach( explode(' ',$search_terms) as $search_term )
		{
			$term = trim($search_term);

			if( $term AND 
				is_numeric($search_term) )
				$search_parameters->check_number = $term;
			else if( $term AND 
				date('Y-m-d',strtotime($term)) == $term )
				$search_parameters->date = $term;
			
			$search_parameters->keywords .= $term.' ';
		}

		$account_transaction_search = new Beans_Account_Transaction_Search_Vendor($this->_beans_data_auth($search_parameters));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( ! $account_transaction_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($account_transaction_search_result));

		$this->_return_object->data = $account_transaction_search_result->data;

		// $this->_return_object->data->checks = array();

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

				if( ! $vendor_expense_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($vendor_expense_lookup_result));

				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->noprintchecks = TRUE;
				$vendor_checks_check->expense = $vendor_expense_lookup_result->data->expense;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();
			}
			else if( $transaction->payment AND 
					 $transaction->payment == "vendor" )
			{
				$vendor_payment_lookup = new Beans_Vendor_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $transaction->id,
				)));
				$vendor_payment_lookup_result = $vendor_payment_lookup->execute();

				if( ! $vendor_payment_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($vendor_payment_lookup_result));

				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->noprintchecks = TRUE;
				$vendor_checks_check->payment = $vendor_payment_lookup_result->data->payment;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();

			}
			else if( $transaction->tax_payment AND 
					 isset($transaction->tax_payment->id) )
			{
				$tax_payment_lookup = new Beans_Tax_Payment_Lookup($this->_beans_data_auth((object)array(
					'id' => $transaction->tax_payment->id,
				)));
				$tax_payment_lookup_result = $tax_payment_lookup->execute();

				if( ! $tax_payment_lookup_result->success )
					return $this->_return_error($this->_beans_result_get_error($tax_payment_lookup_result));

				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->noprintchecks = TRUE;
				$vendor_checks_check->taxpayment = $tax_payment_lookup_result->data->payment;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();
			}
			else
			{
				$vendor_checks_check = new View_Partials_Vendors_Checks_Check;
				$vendor_checks_check->noprintchecks = TRUE;
				$vendor_checks_check->transaction = $transaction;
				$this->_return_object->data->transactions[$index]->html = $vendor_checks_check->render();
			}
		}
	}

	/**
	 * Check Printing Queue
	 */
	
	protected function _print_check_queue_expense_add($expense_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			$check_print_queue = (object)array(
				'expense_ids' => array(),
				'payment_ids' => array(),
				'taxpayment_ids' => array(),
			);

		$check_print_queue->expense_ids[$expense_id] = TRUE;

		Session::instance()->set('check_print_queue',$check_print_queue);
	}

	protected function _print_check_queue_expense_exists($expense_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			return FALSE;

		if( isset($check_print_queue->expense_ids[$expense_id]) )
			return TRUE;

		return FALSE;
	}

	protected function _print_check_queue_expense_remove($expense_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			$check_print_queue = (object)array(
				'expense_ids' => array(),
				'payment_ids' => array(),
				'taxpayment_ids' => array(),
			);

		if( isset($check_print_queue->expense_ids[$expense_id]) )
			unset($check_print_queue->expense_ids[$expense_id]);

		Session::instance()->set('check_print_queue',$check_print_queue);
	}

	protected function _print_check_queue_payment_add($payment_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			$check_print_queue = (object)array(
				'expense_ids' => array(),
				'payment_ids' => array(),
				'taxpayment_ids' => array(),
			);

		$check_print_queue->payment_ids[$payment_id] = TRUE;

		Session::instance()->set('check_print_queue',$check_print_queue);
	}

	protected function _print_check_queue_payment_exists($payment_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			return FALSE;

		if( isset($check_print_queue->payment_ids[$payment_id]) )
			return TRUE;

		return FALSE;
	}

	protected function _print_check_queue_payment_remove($payment_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			$check_print_queue = (object)array(
				'expense_ids' => array(),
				'payment_ids' => array(),
				'taxpayment_ids' => array(),
			);

		if( isset($check_print_queue->payment_ids[$payment_id]) )
			unset($check_print_queue->payment_ids[$payment_id]);

		Session::instance()->set('check_print_queue',$check_print_queue);
	}

	protected function _print_check_queue_taxpayment_add($taxpayment_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			$check_print_queue = (object)array(
				'expense_ids' => array(),
				'payment_ids' => array(),
				'taxpayment_ids' => array(),
			);

		$check_print_queue->taxpayment_ids[$taxpayment_id] = TRUE;

		Session::instance()->set('check_print_queue',$check_print_queue);
	}

	protected function _print_check_queue_taxpayment_exists($taxpayment_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			return FALSE;

		if( isset($check_print_queue->taxpayment_ids[$taxpayment_id]) )
			return TRUE;

		return FALSE;
	}

	protected function _print_check_queue_taxpayment_remove($taxpayment_id)
	{
		$check_print_queue = Session::instance()->get('check_print_queue');
		if( ! $check_print_queue )
			$check_print_queue = (object)array(
				'expense_ids' => array(),
				'payment_ids' => array(),
				'taxpayment_ids' => array(),
			);

		if( isset($check_print_queue->taxpayment_ids[$taxpayment_id]) )
			unset($check_print_queue->taxpayment_ids[$taxpayment_id]);

		Session::instance()->set('check_print_queue',$check_print_queue);
	}

	protected function _print_check_queue_count()
	{
		$check_print_queue = Session::instance()->get('check_print_queue');

		if( ! $check_print_queue )
			return 0;

		return 
			count($check_print_queue->expense_ids) + 
			count($check_print_queue->payment_ids) +
			count($check_print_queue->taxpayment_ids);
	}

}