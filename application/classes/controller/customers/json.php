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

class Controller_Customers_Json extends Controller_Json {

	/**
	 * Returns a list of customer addresses.
	 */
	public function action_customeraddresses()
	{
		$customer_id = $this->request->post('customer_id');

		$customer_address_search = new Beans_Customer_Address_Search($this->_beans_data_auth((object)array(
			'search_customer_id' => $customer_id,
		)));
		$customer_address_search_result = $customer_address_search->execute();

		// Not efficient, but clean - we'll model this everywhere for now.
		if( ! $customer_address_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_address_search_result));

		$customer_lookup = new Beans_Customer_Lookup($this->_beans_data_auth((object)array(
			'id' => $customer_id,
		)));
		$customer_lookup_result = $customer_lookup->execute();

		if( ! $customer_lookup_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_lookup_result));

		$this->_return_object->data->addresses = $customer_address_search_result->data->addresses;
		$this->_return_object->data->customer = $customer_lookup_result->data->customer;
	}


	// V2Item - Consider renaming to reflect that this is used for payment preparation. 
	public function action_customersales()
	{
		$customer_id = $this->request->post('customer_id');
		$has_balance = $this->request->post('has_balance');
		$search_terms = $this->request->post('search_terms');

		$search_parameters = new stdClass;

		$search_parameters->keywords = $search_terms;
		$search_parameters->search_customer_id = $customer_id;
		// $search_parameters->invoiced = TRUE;
		if( $has_balance == 1 )
		{
			$search_parameters->has_balance = TRUE;
			$search_parameters->invoiced = TRUE;
		}
		// $search_parameters->has_balance = ( $has_balance ) ? TRUE : FALSE;
		$search_parameters->sort_by = "newest";
		
		$customer_sales_search = new Beans_Customer_Sale_Search($this->_beans_data_auth($search_parameters));
		$customer_sales_search_result = $customer_sales_search->execute();

		if( ! $customer_sales_search_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_sales_search_result));

		foreach( $customer_sales_search_result->data->sales as $index => $sale )
		{
			$html = new View_Partials_Customers_Payments_Batchpaymentform;
			$html->sale = $sale;

			$customer_sales_search_result->data->sales[$index]->html = $html->render();
		}

		$this->_return_object->data->sales = $customer_sales_search_result->data->sales;
	}

	/**
	 * Validates a customer and their addresses for creation.
	 */
	public function action_customercreate()
	{
		// FIRST WE VALIDATE

		$customer_validate_data = array(
			'validate_only' => TRUE,
			'first_name' => $this->request->post('first_name'),
			'last_name' => $this->request->post('last_name'),
			'company_name' => $this->request->post('company_name'),
			'email' => $this->request->post('email'),
			'default_account_id' => $this->request->post('default_account_id'),
			'phone_number' => $this->request->post('phone_number'),
			'fax_number' => $this->request->post('fax_number'),
		);
		
		$customer_validate = new Beans_Customer_Create($this->_beans_data_auth((object)$customer_validate_data));
		$customer_validate_result = $customer_validate->execute();

		if( ! $customer_validate_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_validate_result));

		// Check for addresses.
		$address_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "ADDRESSKEY" )
				$address_keys[] = str_replace('address-key-', '', $key);

		foreach( $address_keys as $address_key )
		{
			$customer_address_validate_data = array(
				'validate_only' => TRUE,
				'first_name' => $customer_validate_data['first_name'],
				'last_name' => $customer_validate_data['last_name'],
				'address1' => $this->request->post('address1-'.$address_key),
				'address2' => $this->request->post('address2-'.$address_key),
				'city' => $this->request->post('city-'.$address_key),
				'state' => $this->request->post('state-'.$address_key),
				'zip' => $this->request->post('zip-'.$address_key),
				'country' => $this->request->post('country-'.$address_key),
			);
			$customer_address_validate = new Beans_Customer_Address_Create($this->_beans_data_auth((object)$customer_address_validate_data));
			$customer_address_validate_result = $customer_address_validate->execute();

			if( ! $customer_address_validate_result->success )
				return $this->_return_error("There was a problem with an address ( ".$this->request->post('address1-'.$address_key)." ).<br>".$this->_beans_result_get_error($customer_address_validate_result));
		}

		// Now we create!
		$customer_create_data = array(
			'first_name' => $this->request->post('first_name'),
			'last_name' => $this->request->post('last_name'),
			'company_name' => $this->request->post('company_name'),
			'email' => $this->request->post('email'),
			'default_account_id' => $this->request->post('default_account_id'),
			'phone_number' => $this->request->post('phone_number'),
			'fax_number' => $this->request->post('fax_number'),
		);
		
		$customer_create = new Beans_Customer_Create($this->_beans_data_auth((object)$customer_create_data));
		$customer_create_result = $customer_create->execute();

		if( ! $customer_create_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_create_result));

		// Check for addresses.
		$address_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "ADDRESSKEY" )
				$address_keys[] = str_replace('address-key-', '', $key);

		$default_shipping_address_id = FALSE;
		$default_billing_address_id = FALSE;

		foreach( $address_keys as $address_key )
		{
			$customer_address_create_data = array(
				'customer_id' => $customer_create_result->data->customer->id,
				'first_name' => $customer_create_data['first_name'],
				'last_name' => $customer_create_data['last_name'],
				'address1' => $this->request->post('address1-'.$address_key),
				'address2' => $this->request->post('address2-'.$address_key),
				'city' => $this->request->post('city-'.$address_key),
				'state' => $this->request->post('state-'.$address_key),
				'zip' => $this->request->post('zip-'.$address_key),
				'country' => $this->request->post('country-'.$address_key),
			);
			$customer_address_create = new Beans_Customer_Address_Create($this->_beans_data_auth((object)$customer_address_create_data));
			$customer_address_create_result = $customer_address_create->execute();

			if( ! $customer_address_create_result->success )
				return $this->_return_error($this->_beans_result_get_error($customer_address_create_result));

			if( $this->request->post('default-billing-'.$address_key) )
				$default_billing_address_id = $customer_address_create_result->data->address->id;

			if( $this->request->post('default-shipping-'.$address_key) )
				$default_shipping_address_id = $customer_address_create_result->data->address->id;

		}

		if( $default_billing_address_id OR 
			$default_shipping_address_id )
		{
			$customer_update = new Beans_Customer_Update($this->_beans_data_auth((object)array(
				'id' => $customer_create_result->data->customer->id,
				'default_billing_address_id' => $default_billing_address_id,
				'default_shipping_address_id' => $default_shipping_address_id,
			)));
			$customer_update_result = $customer_update->execute();

			// If it fails... well... we're screwed.
			if( ! $customer_update_result->success )
				return $this->_return_error($this->_beans_result_get_error($customer_update_result));
		}

		$html = new View_Partials_Customers_Customer_Customer;
		$html->customer = $customer_create_result->data->customer;

		$customer_create_result->data->customer->html = $html->render();

		$this->_return_object->data->customer = $customer_create_result->data->customer;
	}

	public function action_customerupdate()
	{
		$customer_update = new Beans_Customer_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('customer_id'),
			'first_name' => $this->request->post('first_name'),
			'last_name' => $this->request->post('last_name'),
			'company_name' => $this->request->post('company_name'),
			'default_account_id' => $this->request->post('default_account_id'),
			'phone_number' => $this->request->post('phone_number'),
			'fax_number' => $this->request->post('fax_number'),
			'email' => $this->request->post('email'),
		)));
		$customer_update_result = $customer_update->execute();

		if( ! $customer_update_result->success )
			return $this->_return_error("An error occurred updating that customer information:<br>".$this->_beans_result_get_error($customer_update_result));

	}

	public function action_customeraddresscreate()
	{
		$customer_address_create = new Beans_Customer_Address_Create($this->_beans_data_auth((object)array(
			'customer_id' => $this->request->post('customer_id'),
			'address1' => $this->request->post('address1'),
			'address2' => $this->request->post('address2'),
			'city' => $this->request->post('city'),
			'state' => $this->request->post('state'),
			'zip' => $this->request->post('zip'),
			'country' => $this->request->post('country'),
		)));

		$customer_address_create_result = $customer_address_create->execute();

		if( ! $customer_address_create_result->success )
			return $this->_return_error("An error occurred in creation your address:<br>".$this->_beans_result_get_error($customer_address_create_result));

		$customer_result = FALSE;
		if( $this->request->post('default-billing') OR 
			$this->request->post('default-shipping') )
		{
			$customer_update_data = new stdClass;
			$customer_update_data->id = $this->request->post('customer_id');
			if( $this->request->post('default-billing') )
				$customer_update_data->default_billing_address_id = $customer_address_create_result->data->address->id;
			if( $this->request->post('default-shipping') )
				$customer_update_data->default_shipping_address_id = $customer_address_create_result->data->address->id;

			$customer_update = new Beans_Customer_Update($this->_beans_data_auth($customer_update_data));
			$customer_result = $customer_update->execute();
		}
		else
		{
			$customer_lookup = new Beans_Customer_Lookup($this->_beans_data_auth((object)array(
				'id' => $this->request->post('customer_id'),
			)));
			$customer_result = $customer_lookup->execute();
		}

		if( ! $customer_result )
			return $this->_return_error("An unknown error has occurred.");

		if( ! $customer_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_result));

		$address = $customer_address_create_result->data->address;

		$html = new View_Partials_Customers_Customer_Address();
		$html->address = $customer_address_create_result->data->address;
		$html->customer = $customer_result->data->customer;

		$address->html = $html->render();
		$address->default_billing = ( $this->request->post('default-billing') )
								  ? TRUE
								  : FALSE;
		$address->default_shipping = ( $this->request->post('default-shipping') )
								   ? TRUE
								   : FALSE;

		$this->_return_object->data->address = $address;
	}

	function action_customeraddressupdate()
	{
		$customer_address_update = new Beans_Customer_Address_Update($this->_beans_data_auth((object)array(
			'id' => $this->request->post('address_id'),
			'address1' => $this->request->post('address1'),
			'address2' => $this->request->post('address2'),
			'city' => $this->request->post('city'),
			'state' => $this->request->post('state'),
			'zip' => $this->request->post('zip'),
			'country' => $this->request->post('country'),
		)));

		$customer_address_update_result = $customer_address_update->execute();

		if( ! $customer_address_update_result->success )
			return $this->_return_error("An error occurred in creation your address:<br>".$this->_beans_result_get_error($customer_address_update_result));

		$customer_result = FALSE;
		if( $this->request->post('default-billing') OR 
			$this->request->post('default-shipping') )
		{
			$customer_update_data = new stdClass;
			$customer_update_data->id = $this->request->post('customer_id');
			if( $this->request->post('default-billing') )
				$customer_update_data->default_billing_address_id = $customer_address_update_result->data->address->id;
			if( $this->request->post('default-shipping') )
				$customer_update_data->default_shipping_address_id = $customer_address_update_result->data->address->id;

			$customer_update = new Beans_Customer_Update($this->_beans_data_auth($customer_update_data));
			$customer_result = $customer_update->execute();
		}
		else
		{
			$customer_lookup = new Beans_Customer_Lookup($this->_beans_data_auth((object)array(
				'id' => $this->request->post('customer_id'),
			)));
			$customer_result = $customer_lookup->execute();
		}

		if( ! $customer_result )
			return $this->_return_error("An unknown error has occurred.");

		if( ! $customer_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_result));

		$address = $customer_address_update_result->data->address;

		$html = new View_Partials_Customers_Customer_Address();
		$html->address = $customer_address_update_result->data->address;
		$html->customer = $customer_result->data->customer;

		$address->html = $html->render();
		$address->default_billing = ( $this->request->post('default-billing') )
								  ? TRUE
								  : FALSE;
		$address->default_shipping = ( $this->request->post('default-shipping') )
								   ? TRUE
								   : FALSE;

		$this->_return_object->data->address = $address;
	}

	public function action_saleload()
	{
		$sale_id = $this->request->post('sale_id');

		$customer_sale_lookup = new Beans_Customer_Sale_Lookup($this->_beans_data_auth((object)array(
			'id' => $sale_id,
		)));
		$customer_sale_lookup_result = $customer_sale_lookup->execute();

		if( ! $customer_sale_lookup_result->success )
			return $this->_return_error("An error occurred when looking up that sale:<br>".$this->_beans_result_get_error($customer_sale_lookup_result));

		$this->_return_object->data->sale = $customer_sale_lookup_result->data->sale;

	}

	public function action_salecreate()
	{
		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "SALELINEKEY" )
				$line_keys[] = str_replace('sale-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$customer_info = explode('#',$this->request->post('customer'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this sale.");

		if( count($customer_info) != 5 )
			return $this->_return_error("Please select a valid customer for this sale.");

		$create_sale_data = new stdClass;
		$create_sale_data->customer_id = $customer_info[0];
		$create_sale_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");

		$create_sale_data->billing_address_id = $this->request->post('billing_address_id');
		$create_sale_data->shipping_address_id = $this->request->post('shipping_address_id');
		$create_sale_data->account_id = $account_info[0];
		$create_sale_data->sale_number = $this->request->post('sale_number');
		$create_sale_data->order_number = $this->request->post('order_number');
		$create_sale_data->quote_number = $this->request->post('quote_number');
		$create_sale_data->po_number = $this->request->post('po_number');
		$create_sale_data->tax_exempt = $this->request->post('form_tax_exempt') ? TRUE : FALSE;

		// IF INVOICE
		if( $this->request->post('date_billed') )
		{
			$create_sale_data->date_created = $this->request->post('date_billed');
			$create_sale_data->date_billed = $this->request->post('date_billed');

			if( $this->request->post('date_due') )
				$create_sale_data->date_due = $this->request->post('date_due');
		} 
		else if ( $this->request->post('invoice_view') )
		{
			$create_sale_data->date_created = date("Y-m-d");
			$create_sale_data->date_billed = date("Y-m-d");

			if( $this->request->post('date_due') )
				$create_sale_data->date_due = $this->request->post('date_due');
		}

		$create_sale_data->taxes = array();

		if( $this->request->post('form-tax_ids') )
		{
			foreach( explode('#', $this->request->post('form-tax_ids')) as $tax_id )
			{
				if( trim($tax_id) )
					$create_sale_data->taxes[] = (object)array(
						'tax_id' => $tax_id,
					);
			}
		}
		
		$create_sale_data->lines = array();

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
				$sale_line = new stdClass;
				$sale_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$sale_line->description = $this->request->post('line-description-'.$line_key);
				$sale_line->amount = $this->request->post('line-price-'.$line_key);
				$sale_line->quantity = $this->request->post('line-quantity-'.$line_key);
				$sale_line->tax_exempt = $this->request->post('line-tax-exempt-'.$line_key) ? TRUE : FALSE;

				$create_sale_data->lines[] = $sale_line;
			}
		}

		$create_sale = new Beans_Customer_Sale_Create($this->_beans_data_auth($create_sale_data));
		$create_sale_result = $create_sale->execute();

		if( ! $create_sale_result->success )
			return $this->_return_error("An error occurred when creating that sale:<br>".$this->_beans_result_get_error($create_sale_result));

		$html = new View_Partials_Customers_Sales_Sale;
		$html->sale = $create_sale_result->data->sale;
		$html->invoice_view = ( $this->request->post('invoice_view') );

		$this->_return_object->data->sale = $create_sale_result->data->sale;
		$this->_return_object->data->sale->html = $html->render();

	}

	public function action_saleupdate()
	{
		$sale_id = $this->request->post('sale_id');

		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "SALELINEKEY" )
				$line_keys[] = str_replace('sale-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$customer_info = explode('#',$this->request->post('customer'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this sale.");

		if( count($customer_info) != 5 )
			return $this->_return_error("Please select a valid customer for this sale.");

		$update_sale_data = new stdClass;
		$update_sale_data->id = $sale_id;
		$update_sale_data->customer_id = $customer_info[0];
		if( $this->request->post('date_created') ) 
			$update_sale_data->date_created = date("Y-m-d",strtotime($this->request->post('date_created')));
		if( $this->request->post('date_billed') )
			$update_sale_data->date_billed = date("Y-m-d",strtotime($this->request->post('date_billed')));
		$update_sale_data->date_due = ( $this->request->post('date_due') )
									   ? date("Y-m-d",strtotime($this->request->post('date_due')))
									   : date("Y-m-d",strtotime($update_sale_data->date_created.' +'.$account_info[1].' Days'));
		$update_sale_data->billing_address_id = $this->request->post('billing_address_id');
		$update_sale_data->shipping_address_id = $this->request->post('shipping_address_id');
		$update_sale_data->account_id = $account_info[0];
		$update_sale_data->sale_number = $this->request->post('sale_number');
		$update_sale_data->order_number = $this->request->post('order_number');
		$update_sale_data->quote_number = $this->request->post('quote_number');
		$update_sale_data->po_number = $this->request->post('po_number');
		$update_sale_data->tax_exempt = $this->request->post('form_tax_exempt') ? TRUE : FALSE;

		$update_sale_data->taxes = array();

		if( $this->request->post('form-tax_ids') )
		{
			foreach( explode('#', $this->request->post('form-tax_ids')) as $tax_id )
			{
				if( trim($tax_id) )
					$update_sale_data->taxes[] = (object)array(
						'tax_id' => $tax_id,
					);
			}
		}

		$update_sale_data->lines = array();

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
				$sale_line = new stdClass;
				$sale_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$sale_line->description = $this->request->post('line-description-'.$line_key);
				$sale_line->amount = $this->request->post('line-price-'.$line_key);
				$sale_line->quantity = $this->request->post('line-quantity-'.$line_key);
				$sale_line->tax_exempt = $this->request->post('line-tax-exempt-'.$line_key) ? TRUE : FALSE;

				$update_sale_data->lines[] = $sale_line;
			}
		}
		
		$update_sale = new Beans_Customer_Sale_Update($this->_beans_data_auth($update_sale_data));
		$update_sale_result = $update_sale->execute();

		if( ! $update_sale_result->success )
			return $this->_return_error("An error occurred when updating that sale:<br>".$this->_beans_result_get_error($update_sale_result));

		$html = new View_Partials_Customers_Sales_Sale;
		$html->sale = $update_sale_result->data->sale;
		$html->invoice_view = ( $this->request->post('invoice_view') );

		$this->_return_object->data->sale = $update_sale_result->data->sale;
		$this->_return_object->data->sale->html = $html->render();
	}

	public function action_salerefund()
	{
		$sale_id = $this->request->post('refund_sale_id');

		$line_keys = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "SALELINEKEY" )
				$line_keys[] = str_replace('sale-line-key-', '', $key);

		$account_info = explode('#',$this->request->post('account'));
		$customer_info = explode('#',$this->request->post('customer'));

		if( count($account_info) != 2 )
			return $this->_return_error("Please select a valid account for this sale.");

		if( count($customer_info) != 5 )
			return $this->_return_error("Please select a valid customer for this sale.");

		$refund_sale_data = new stdClass;
		$refund_sale_data->id = $sale_id;
		$refund_sale_data->customer_id = $customer_info[0];
		$refund_sale_data->date_created = ( $this->request->post('date_created') )
										   ? date("Y-m-d",strtotime($this->request->post('date_created')))
										   : date("Y-m-d");
		
		$refund_sale_data->billing_address_id = $this->request->post('billing_address_id');
		$refund_sale_data->shipping_address_id = $this->request->post('shipping_address_id');
		$refund_sale_data->account_id = $account_info[0];
		$refund_sale_data->sale_number = $this->request->post('sale_number');
		$refund_sale_data->order_number = $this->request->post('order_number');
		$refund_sale_data->po_number = $this->request->post('po_number');

		if( $this->request->post('date_billed') )
		{
			$refund_sale_data->date_created = $this->request->post('date_billed');
			$refund_sale_data->date_billed = $this->request->post('date_billed');
		} 
		else
		{
			$refund_sale_data->date_created = date("Y-m-d");
			$refund_sale_data->date_billed = date("Y-m-d");
		}

		if( $this->request->post('date_due') )
				$refund_sale_data->date_due = $this->request->post('date_due');

		$refund_sale_data->lines = array();

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
				$sale_line = new stdClass;
				$sale_line->account_id = $this->request->post('line-account_id-'.$line_key);
				$sale_line->description = $this->request->post('line-description-'.$line_key);
				$sale_line->amount = $this->request->post('line-price-'.$line_key);
				$sale_line->quantity = $this->request->post('line-quantity-'.$line_key);

				$sale_line->sale_line_taxes = array();

				foreach( explode('#',$this->request->post('line-tax_ids-'.$line_key)) as $tax_id )
					if( $tax_id AND 
						trim($tax_id) )
						$sale_line->sale_line_taxes[] = (object)array(
							'tax_id' => trim($tax_id),
						);

				$refund_sale_data->lines[] = $sale_line;
			}
		}

		$refund_sale = new Beans_Customer_Sale_Refund($this->_beans_data_auth($refund_sale_data));
		$refund_sale_result = $refund_sale->execute();

		if( ! $refund_sale_result->success )
			return $this->_return_error("An error occurred when updating that sale:<br>".$this->_beans_result_get_error($refund_sale_result));

		$html = new View_Partials_Customers_Sales_Sale;
		$html->sale = $refund_sale_result->data->sale;
		$html->invoice_view = TRUE;

		$this->_return_object->data->sale = $refund_sale_result->data->sale;
		$this->_return_object->data->sale->html = $html->render();
	}

	public function action_salesendvalidate()
	{
		$sale_id = $this->request->post('sale_id');
		$send_email = ( $this->request->post('send-email') ? TRUE : FALSE );
		$email = $this->request->post('email');
		$send_mail = ( $this->request->post('send-mail') ? TRUE : FALSE );
		$send_done = ( $this->request->post('send-done') ? TRUE : FALSE );

		/*
		if( ! $sale_id )
			return $this->_return_error("ERROR: No sale ID provided.");
		*/

		if( ! $send_email AND
			! $send_mail AND 
			! $send_done )
			return $this->_return_error("ERROR: Please select at least one option to send this sale.");

		/*
		$customer_sale_lookup = new Beans_Customer_Sale_Lookup($this->_beans_data_auth((object)array(
			'id' => $sale_id,
		)));
		$customer_sale_lookup_result = $customer_sale_lookup->execute();

		if( ! $customer_sale_lookup_result->success )
			return $this->_return_error("An error occurred retrieving that sale:<br>".$this->_beans_result_get_error($customer_sale_lookup_result));
		*/
		
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

	public function action_salesend()
	{
		$sale_id = $this->request->post('sale_id');
		$send_email = ( $this->request->post('send-email') ? TRUE : FALSE );
		$email = $this->request->post('email');
		$send_mail = ( $this->request->post('send-mail') ? TRUE : FALSE );
		$send_done = ( $this->request->post('send-done') ? TRUE : FALSE );

		if( ! $sale_id )
			return $this->_return_error("ERROR: No sale ID provided.");

		if( ! $send_email AND
			! $send_mail AND 
			! $send_done )
			return $this->_return_error("ERROR: Please select at least one option.");

		$customer_sale_lookup = new Beans_Customer_Sale_Lookup($this->_beans_data_auth((object)array(
			'id' => $sale_id,
		)));
		$customer_sale_lookup_result = $customer_sale_lookup->execute();

		if( ! $customer_sale_lookup_result->success )
			return $this->_return_error("An error occurred retrieving that sale:<br>".$this->_beans_result_get_error($customer_sale_lookup_result));
		
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
			->setSubject($settings->company_name.' - '.$customer_sale_lookup_result->data->sale->title)
			->setFrom(array($settings->company_email))
			->setTo(array($email));
			
			$customers_print_sale = new View_Customers_Print_Sale();
			$customers_print_sale->setup_company_list_result = $company_settings_result;
			$customers_print_sale->sale = $customer_sale_lookup_result->data->sale;
			$customers_print_sale->swift_email_message = $message;

			$message = $customers_print_sale->render();
			
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

		// Update sale attributes only.
		$customer_sale_update_sent_data = new stdClass;
		$customer_sale_update_sent_data->id = $sale_id;
		if( $send_done OR
			(
				$send_email AND
				$send_mail
			) OR (
				$send_email AND 
				$customer_sale_lookup_result->data->sale->sent == "print" 
			) OR (
				$send_mail AND
				$customer_sale_lookup_result->data->sale->sent == "email"
			) )
			$customer_sale_update_sent_data->sent = 'both';
		else if( $send_email )
			$customer_sale_update_sent_data->sent = 'email';
		else if( $send_mail )
			$customer_sale_update_sent_data->sent = 'print';

		$customer_sale_update_sent = new Beans_Customer_Sale_Update_Sent($this->_beans_data_auth($customer_sale_update_sent_data));
		$customer_sale_update_sent_result = $customer_sale_update_sent->execute();

		if( ! $customer_sale_update_sent_result->success )
			return $this->_return_error("An error occurred when updating that sale:<br>".$this->_beans_result_get_error($customer_sale_update_sent_result));

		$html = new View_Partials_Customers_Sales_Sale;
		$html->sale = $customer_sale_update_sent_result->data->sale;
		$html->invoice_view = ( $this->request->post('invoice_view') );

		$this->_return_object->data->sale = $customer_sale_update_sent_result->data->sale;
		$this->_return_object->data->sale->html = $html->render();

	}

	public function action_saleinvoice()
	{
		$sale_id = $this->request->post('sale_id');
		$send_none = ( $this->request->post('send-none') ? TRUE : FALSE );
		$send_email = ( $this->request->post('send-email') ? TRUE : FALSE );
		$email = $this->request->post('email');
		$send_mail = ( $this->request->post('send-mail') ? TRUE : FALSE );
		$send_done = ( $this->request->post('send-done') ? TRUE : FALSE );

		if( ! $sale_id )
			return $this->_return_error("ERROR: No sale ID provided.");

		if( ! $send_none AND 
			! $send_email AND
			! $send_mail AND 
			! $send_done )
			return $this->_return_error("ERROR: Please select at least one option.");

		// Invoice
		$customer_sale_invoice = new Beans_Customer_Sale_Invoice($this->_beans_data_auth((object)array(
			'id' => $sale_id,
		)));
		$customer_sale_invoice_result = $customer_sale_invoice->execute();

		if( ! $customer_sale_invoice_result->success )
			return $this->_return_error("An error occurred when converting that sale to an invoice:<br>".$this->_beans_result_get_error($customer_sale_invoice_result));

		if( $send_none )
		{
			$html = new View_Partials_Customers_Sales_Sale;
			$html->sale = $customer_sale_invoice_result->data->sale;
			$html->invoice_view = ( $this->request->post('invoice_view') );

			$this->_return_object->data->sale = $customer_sale_invoice_result->data->sale;
			$this->_return_object->data->sale->html = $html->render();

			return;
		}

		if( $send_done )
		{
			$customer_sale_update_sent = new Beans_Customer_Sale_Update_Sent($this->_beans_data_auth((object)array(
				'id' => $customer_sale_invoice_result->data->sale->id,
				'sent' => 'both',
			)));
			$customer_sale_update_sent_result = $customer_sale_update_sent->execute();

			$html = new View_Partials_Customers_Sales_Sale;
			$html->sale = $customer_sale_update_sent_result->data->sale;
			$html->invoice_view = ( $this->request->post('invoice_view') );

			$this->_return_object->data->sale = $customer_sale_update_sent_result->data->sale;
			$this->_return_object->data->sale->html = $html->render();

			return;
		}

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
			->setSubject($settings->company_name.' - '.$customer_sale_invoice_result->data->sale->title)
			->setFrom(array($settings->company_email))
			->setTo(array($email));
			
			$customers_print_sale = new View_Customers_Print_Sale();
			$customers_print_sale->setup_company_list_result = $company_settings_result;
			$customers_print_sale->sale = $customer_sale_invoice_result->data->sale;
			$customers_print_sale->swift_email_message = $message;

			$message = $customers_print_sale->render();
			
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

		// Update sale attributes only.
		$customer_sale_update_sent_data = new stdClass;
		$customer_sale_update_sent_data->id = $sale_id;
		if( $send_done OR
			(
				$send_email AND
				$send_mail
			) OR (
				$send_email AND 
				$customer_sale_invoice_result->data->sale->sent == "print" 
			) OR (
				$send_mail AND
				$customer_sale_invoice_result->data->sale->sent == "email"
			) )
			$customer_sale_update_sent_data->sent = 'both';
		else if( $send_email )
			$customer_sale_update_sent_data->sent = 'email';
		else if( $send_mail )
			$customer_sale_update_sent_data->sent = 'print';

		$customer_sale_update_sent = new Beans_Customer_Sale_Update_Sent($this->_beans_data_auth($customer_sale_update_sent_data));
		$customer_sale_update_sent_result = $customer_sale_update_sent->execute();

		if( ! $customer_sale_update_sent_result->success )
			return $this->_return_error("An error occurred when updating that sale:<br>".$this->_beans_result_get_error($customer_sale_update_sent_result));

		$html = new View_Partials_Customers_Sales_Sale;
		$html->sale = $customer_sale_update_sent_result->data->sale;
		$html->invoice_view = ( $this->request->post('invoice_view') );

		$this->_return_object->data->sale = $customer_sale_update_sent_result->data->sale;
		$this->_return_object->data->sale->html = $html->render();
	}

	public function action_salesloadmore()
	{
		$last_sale_id = $this->request->post('last_sale_id');
		$last_sale_date = $this->request->post('last_sale_date');
		$search_terms = $this->request->post('search_terms');
		$search_customer_id = $this->request->post('search_customer_id');
		$search_past_due = $this->request->post('search_past_due');
		$search_invoiced = $this->request->post('search_invoiced');
		$count = $this->request->post('count');

		if( ! $count )
			$count = 20;

		$this->_return_object->data->sales = array();

		$page = 0;

		$search_parameters = new stdClass;
		$search_parameters->sort_by = 'newest';
		$search_parameters->page_size = ($count * 2);
		
		if( $search_invoiced == "1" )
			$search_parameters->invoiced = TRUE;
		
		if( $search_customer_id AND
			strlen(trim($search_customer_id)) )
			$search_parameters->search_customer_id = $search_customer_id;

		if( $search_past_due )
			$search_parameters->past_due = TRUE;

		$search_parameters->keywords = $search_terms;
		
		do
		{
			$search_parameters->page = $page;
			$customer_sales = new Beans_Customer_Sale_Search($this->_beans_data_auth($search_parameters));
			$customer_sales_result = $customer_sales->execute();
			
			if( ! $customer_sales_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($customer_sales_result));

			foreach( $customer_sales_result->data->sales as $sale ) 
			{
				if( (
						strtotime($sale->date_created) <= strtotime($last_sale_date) AND 
						$sale->id < $last_sale_id
					) OR 
					strtotime($sale->date_created) < strtotime($last_sale_date) OR
					! $last_sale_id )
				{
					$html = new View_Partials_Customers_Sales_Sale;
					$html->sale = $sale;
					$html->invoice_view = ( $this->request->post('invoice_view') );

					$sale->html = $html->render();

					$this->_return_object->data->sales[] = $sale;
				}
				if( count($this->_return_object->data->sales) >= $count )
					return;
			}
			$page++;
		}
		while( 	$page < $customer_sales_result->data->pages AND 
				count($this->_return_object->data->sales) < $count );

	}

	public function action_salecancel()
	{
		$sale_id = $this->request->post('sale_id');

		$sale_delete = new Beans_Customer_Sale_Delete($this->_beans_data_auth((object)array(
			'id' => $sale_id,
		)));
		$sale_delete_result = $sale_delete->execute();

		if( ! $sale_delete_result->success )
		{
			$sale_cancel = new Beans_Customer_Sale_Cancel($this->_beans_data_auth((object)array(
				'id' => $sale_id,
			)));
			$sale_cancel_result = $sale_cancel->execute();

			if( ! $sale_cancel_result->success )
				return $this->_return_error("An error occurred when trying to cancel that sale:<br>".$this->_beans_result_get_error($sale_cancel_result));

			$html = new View_Partials_Customers_Sales_Sale;
			$html->sale = $sale_cancel_result->data->sale;
			$html->invoice_view = ( $this->request->post('invoice_view') );

			$sale_cancel_result->data->sale->html = $html->render();

			$this->_return_object->data->sale = $sale_cancel_result->data->sale;
		}
	}

	public function action_customersloadmore()
	{
		$last_customer_id = $this->request->post('last_customer_id');
		$last_page = $this->request->post('last_page');
		$search_terms = $this->request->post('search_terms');
		$count = $this->request->post('count');

		if( ! $last_page ) 
			$last_page = 0;

		if( ! $count )
			$count = 20;

		$this->_return_object->data->customers = array();
		
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
			$customer_search = new Beans_Customer_Search($this->_beans_data_auth($search_parameters));
			$customer_search_result = $customer_search->execute();

			if( ! $customer_search_result->success )
				return $this->_return_error("An unexpected error occurred: ".$this->_beans_result_get_error($customer_search_result));

			$this->_return_object->data->last_page = $customer_search_result->data->page;

			foreach( $customer_search_result->data->customers as $customer ) 
			{
				if( $customer->id < $last_customer_id OR
					! $last_customer_id )
				{
					$html = new View_Partials_Customers_Customer_Customer;
					$html->customer = $customer;

					$customer->html = $html->render();

					$this->_return_object->data->customers[] = $customer;
				}
				if( count($this->_return_object->data->customers) >= $count )
					return;
			}
			$search_parameters->page++;
		}
		while( 	$search_parameters->page < $customer_search_result->data->pages AND 
				count($this->_return_object->data->customers) < $count );
	}
	
	public function action_paymentcreate()
	{
		$sales = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "sale-key" )
				$sales[] = (object)array(
					'sale_id' => $key,
					'amount' => $this->request->post('sale-amount-'.$key),
					'writeoff_balance' => ( $this->request->post('sale-balance-writeoff-'.$key) ) ? TRUE : FALSE,
					'writeoff_amount' => $this->request->post('sale-balance-writeoff-'.$key),
				);

		// This is the payment we end up creating.
		$payment = FALSE;

		if( $this->request->post('replace_transaction_id') AND 
			$this->request->post('replace_transaction_id') != "new" )
		{
			// REPLACE
			$customer_payment_replace = new Beans_Customer_Payment_Replace($this->_beans_data_auth((object)array(
				'transaction_id' => $this->request->post('replace_transaction_id'),
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'deposit_account_id' => $this->request->post('deposit_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'sales' => $sales,
			)));

			$customer_payment_replace_result = $customer_payment_replace->execute();

			if( ! $customer_payment_replace_result->success )
				return $this->_return_error($this->_beans_result_get_error($customer_payment_replace_result));

			$payment = $customer_payment_replace_result->data->payment;
		}
		else
		{
			// Check for duplicates
			if( $this->request->post('replace_transaction_id') != "new" )
			{
				if( ! $this->request->post('deposit_account_id') )
					return $this->_return_error("Please select a deposit account.");

				$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
					'id' => $this->request->post('deposit_account_id'),
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
							'account_id' => $this->request->post('deposit_account_id'),
							'hash' => 'checkpayment',
							'amount' => ($this->request->post('amount') * $account_lookup_result->data->account->type->table_sign),
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

			$customer_payment_create = new Beans_Customer_Payment_Create($this->_beans_data_auth((object)array(
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'deposit_account_id' => $this->request->post('deposit_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'sales' => $sales,
			)));

			$customer_payment_create_result = $customer_payment_create->execute();

			if( ! $customer_payment_create_result->success )
				return $this->_return_error($this->_beans_result_get_error($customer_payment_create_result));

			$payment = $customer_payment_create_result->data->payment;
		}

		$html = new View_Partials_Customers_Payments_Payment;
		$html->payment = $payment;

		$payment->html = $html->render();

		$this->_return_object->data->payment = $payment;
	}

	public function action_paymentload()
	{
		$payment_id = $this->request->post('payment_id');

		$customer_payment_lookup = new Beans_Customer_Payment_Lookup($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$customer_payment_lookup_result = $customer_payment_lookup->execute();

		if( ! $customer_payment_lookup_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_payment_lookup_result));

		$payment = $customer_payment_lookup_result->data->payment;

		foreach( $payment->sale_payments as $index => $sale_payment )
		{
			$html = new View_Partials_Customers_Payments_Batchpaymentline;
			$html->sale_payment = $sale_payment;

			$payment->sale_payments[$index]->html = $html->render();
		}

		$this->_return_object->data->payment = $payment;
	}

	public function action_paymentdelete()
	{
		$payment_id = $this->request->post('payment_id');

		$customer_payment_cancel = new Beans_Customer_Payment_Cancel($this->_beans_data_auth((object)array(
			'id' => $payment_id,
		)));
		$customer_payment_cancel_result = $customer_payment_cancel->execute();

		if( ! $customer_payment_cancel_result->success )
			$this->_return_error($this->_beans_result_get_error($customer_payment_cancel_result));

	}

	public function action_paymentupdate()
	{
		$payment_id = $this->request->post('payment_id');

		$sales = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "sale-key" )
				$sales[] = (object)array(
					'sale_id' => $key,
					'amount' => $this->request->post('sale-amount-'.$key),
					'writeoff_balance' => ( $this->request->post('sale-balance-writeoff-'.$key) ) ? TRUE : FALSE,
					'writeoff_amount' => $this->request->post('sale-balance-writeoff-'.$key),
				);

		// This is the payment we end up creating.
		$payment = FALSE;

		if( $this->request->post('replace_transaction_id') AND 
			$this->request->post('replace_transaction_id') != "new" )
		{
			$customer_payment_replace_data = (object)array(
				'transaction_id' => $this->request->post('replace_transaction_id'),
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'deposit_account_id' => $this->request->post('deposit_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'sales' => $sales,
			);

			// REPLACE
			$customer_payment_replace_data->validate_only = FALSE;
			$customer_payment_replace = new Beans_Customer_Payment_Replace($this->_beans_data_auth($customer_payment_replace_data));

			$customer_payment_replace_result = $customer_payment_replace->execute();

			if( ! $customer_payment_replace_result->success )
				return $this->_return_error($this->_beans_result_get_error($customer_payment_replace_result));

			$payment = $customer_payment_replace_result->data->payment;
		}
		else
		{
			// Check for duplicates
			if( $this->request->post('replace_transaction_id') != "new" )
			{
				if( ! $this->request->post('deposit_account_id') )
					return $this->_return_error("Please select a deposit account.");

				$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array(
					'id' => $this->request->post('deposit_account_id'),
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
							'account_id' => $this->request->post('deposit_account_id'),
							'hash' => 'checkpayment',
							'amount' => ($this->request->post('amount') * $account_lookup_result->data->account->type->table_sign),
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

			$customer_payment_update = new Beans_Customer_Payment_Update($this->_beans_data_auth((object)array(
				'id' => $payment_id,
				'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
				'amount' => $this->request->post('amount'),
				'deposit_account_id' => $this->request->post('deposit_account_id'),
				'writeoff_account_id' => $this->request->post('writeoff_account_id'),
				'sales' => $sales,
			)));

			$customer_payment_update_result = $customer_payment_update->execute();

			if( ! $customer_payment_update_result->success )
				return $this->_return_error($this->_beans_result_get_error($customer_payment_update_result));

			$payment = $customer_payment_update_result->data->payment;
		}

		$html = new View_Partials_Customers_Payments_Payment;
		$html->payment = $payment;

		$payment->html = $html->render();

		$this->_return_object->data->payment = $payment;

		/*
		$sales = array();
		foreach( $this->request->post() as $key => $value )
			if( $value == "sale-key" )
				$sales[] = (object)array(
					'sale_id' => $key,
					'amount' => $this->request->post('sale-amount-'.$key),
					'writeoff_amount' => $this->request->post('sale-balance-writeoff-'.$key),
				);

		$customer_payment_update = new Beans_Customer_Payment_Update($this->_beans_data_auth((object)array(
			'id' => $payment_id,
			'date' => ( $this->request->post('date') ) ? $this->request->post('date') : date("Y-m-d"),
			'amount' => $this->request->post('amount'),
			'deposit_account_id' => $this->request->post('deposit_account_id'),
			'writeoff_account_id' => $this->request->post('writeoff_account_id'),
			'sales' => $sales,
		)));

		$customer_payment_update_result = $customer_payment_update->execute();

		if( ! $customer_payment_update_result->success )
			return $this->_return_error($this->_beans_result_get_error($customer_payment_update_result));

		$payment = $customer_payment_update_result->data->payment;

		$html = new View_Partials_Customers_Payments_Payment;
		$html->payment = $payment;

		$payment->html = $html->render();

		$this->_return_object->data->payment = $payment;
		*/
	}

	// V2Item - Check if deprecated.
	public function action_paymentsearch() 
	{
		$search_terms = $this->request->post('search_terms');
		$count = $this->request->post('count');
		$page = $this->request->post('page');

		if( ! $count )
			$count = 5;

		if( ! $page ) 
			$page = 0;

		$customer_payment_search_data = new stdClass;
		$customer_payment_search_data->sort_by = 'newest';
		$customer_payment_search_data->page_size = $count;
		$customer_payment_search_data->page = $page;
		$customer_payment_search_data->keywords = $search_terms;

		// Include this as a checkbox?
		$customer_payment_search_data->include_invoices = TRUE;

		$customer_payment_search = new Beans_Customer_Payment_Search($this->_beans_data_auth($customer_payment_search_data));
		
		$customer_payment_search_result = $customer_payment_search->execute();

		if( ! $customer_payment_search_result->success )
			return $this->_return_error($customer_payment_search_result->error);

		foreach( $customer_payment_search_result->data->payments as $index => $payment )
		{
			$html = new View_Partials_Customers_Payments_Payment;
			$html->payment = $payment;

			$customer_payment_search_result->data->payments[$index]->html = $html->render();
		}

		$this->_return_object->data = $customer_payment_search_result->data;
	}

	public function action_salelines()
	{
		$search_term = $this->request->post('search_term');

		$customer_sale_line_search = new Beans_Customer_Sale_Line_Search($this->_beans_data_auth((object)array(
			'search_description' => $search_term,
		)));
		$customer_sale_line_search_result = $customer_sale_line_search->execute();

		if( ! $customer_sale_line_search_result->success ) 
			return $this->_return_error($this->_beans_result_get_error($customer_sale_line_search_result));

		$this->_return_object->data->lines = $customer_sale_line_search_result->data->sale_lines;	
	}

	
}