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
@action Beans_Vendor_Purchase_Search
@description Search for vendor purchases.
@required auth_uid
@required auth_key
@required auth_expiration
@optional sort_by STRING The sort pattern for the returned results: 'newest', 'oldest'
@optional page INTEGER The page to return.
@optional page_size INTEGER The number of results on each page.
@optional search_vendor_id INTEGER Limit the search to a specific #Beans_Vendor#.
@optional keywords STRING A generic query string that will be compared to both vendors ( name, company name, phone number ) and purchases ( total, Purchase Number, Order Number, PO Number, and Quote Number ).
@optional invoiced BOOLEAN Whether or not the purchase has been invoiced.  Can be true or false.
@optional sent BOOLEAN Whether or not the purchase has been sent in its current form.  Can be true or false.
@optional past_due BOOLEAN Whether or not the purchase is past due.  Can be true or false.
@optional has_balance BOOLEAN Whether or not the purchase has a balance.  Can be true or false.
@optional date_created_before STRING Search purchases created before a YYYY-MM-DD date.
@optional date_created_after STRING Search purchases created after a YYYY-MM-DD date.
@optional date_billed_before STRING Search purchases billed before a YYYY-MM-DD date.
@optional date_billed_after STRING Search purchases billed after a YYYY-MM-DD date.
@returns purchases ARRAY An array of #Beans_Vendor_Purchase#.
@returns total_results INTEGER Total number of results.
@returns sort_by STRING The sort method used in the returned results.
@returns pages INTEGER The total number of pages of results.
@returns page INTEGER The currently returned page index.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Search extends Beans_Vendor_Purchase {

	protected $_purchases;
	private $_page;
	private $_page_size;

	private $_search_vendor_id;
	private $_search_keywords;

	private $_search_flag_invoiced;
	private $_search_flag_sent;
	private $_search_flag_past_due;
	private $_search_flag_has_balance;

	private $_search_date_created_before;
	private $_search_date_created_after;
	private $_search_date_billed_before;
	private $_search_date_billed_after;

	private $_sort_by;
	private $_sort_by_patterns = array(
		'newest' => array(
			'date_created' => 'desc',
			'id' => 'desc',
		),
		'oldest' => array(
			'date_created' => 'asc',
			'id' => 'asc',
		),
		'duesoonest' => array(
			'date_due' => 'desc',
			'id' => 'desc',
		),
		'duelatest' => array(
			'date_due' => 'asc',
			'id' => 'asc',
		),
	);

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_page = ( isset($data->page) AND (int)$data->page >= 0 )
					 ? (int)$data->page 
					 : 0;
		
		$this->_page_size = ( isset($data->page_size) AND (int)$data->page_size > 0 )
						  ? (int)$data->page_size 
						  : 50;

		$this->_sort_by = ( isset($data->sort_by) )
						? strtolower($data->sort_by)
						: "newest";

		$this->_search_vendor_id = isset($data->search_vendor_id)
								   ? (int)$data->search_vendor_id
								   : FALSE;

		$this->_search_keywords = isset($data->keywords)
								? $data->keywords
								: FALSE;

		$this->_search_flag_invoiced = isset($data->invoiced)
									 ? ( $data->invoiced ? TRUE : FALSE )
									 : NULL;

		$this->_search_flag_sent = isset($data->sent)
								 ? ( $data->sent ? TRUE : FALSE )
								 : NULL;

		$this->_search_flag_past_due = isset($data->past_due)
									 ? ( $data->past_due ? TRUE : FALSE )
									 : NULL;

		$this->_search_flag_has_balance = isset($data->has_balance)
										? ( $data->has_balance ? TRUE : FALSE )
										: NULL;

		$this->_search_date_created_before = isset($data->date_created_before)
										   ? $data->date_created_before
										   : NULL;

		$this->_search_date_created_after = isset($data->date_created_after)
										  ? $data->date_created_after
										  : NULL;

		$this->_search_date_billed_before = isset($data->date_billed_before)
										  ? $data->date_billed_before
										  : NULL;

		$this->_search_date_billed_after = isset($data->date_billed_after)
										 ? $data->date_billed_after
										 : NULL;


	}

	protected function _execute()
	{
		$this->_purchases = ORM::Factory('form_purchase')->
			distinct(TRUE)->
			join('entities','LEFT')->
			on('form_purchase.entity_id','=','entities.id');
		
		$this->_purchases = $this->_purchases->
			where('form_purchase.type','=','purchase')->
			and_where_open();

		$this->_purchases = $this->_purchases->
			where('form_purchase.id','IS NOT',NULL);

		if( $this->_search_vendor_id )
			$this->_purchases = $this->_purchases->
				where('entities.id','=',$this->_search_vendor_id);

		if( $this->_search_flag_invoiced !== NULL AND
			$this->_search_flag_invoiced )
			$this->_purchases = $this->_purchases->
				where('form_purchase.date_billed','IS NOT',NULL);
		else if( $this->_search_flag_invoiced !== NULL )
			$this->_purchases = $this->_purchases->
				where('form_purchase.date_billed','IS',NULL);

		if( $this->_search_flag_sent !== NULL AND 
			$this->_search_flag_sent )
			$this->_purchases = $this->_purchases->
				where('form_purchase.sent','IS NOT',NULL);
		else if( $this->_search_flag_sent !== NULL )
			$this->_purchases = $this->_purchases->
				where('form_purchase.sent','IS',NULL);

		if( $this->_search_flag_has_balance !== NULL AND
			$this->_search_flag_has_balance )
			$this->_purchases = $this->_purchases->
				where('form_purchase.balance','!=',0);
		else if ( $this->_search_flag_has_balance !== NULL )
			$this->_purchases = $this->_purchases->
				where('form_purchase.balance','=',0);

		if( $this->_search_flag_past_due !== NULL AND 
			$this->_search_flag_past_due )
			$this->_purchases = $this->_purchases->
				where('form_purchase.balance','!=',0)->
				where('form_purchase.date_due','<',date("Y-m-d"));
		else if( $this->_search_flag_past_due !== NULL )
			$this->_purchases = $this->_purchases->
				and_where_open()->
					or_where('form_purchases.balance','=',0)->
					or_where('form_purchases.date_due','>=',date("Y-m-d"))->
				and_where_close();

		if( $this->_search_date_created_before AND 
			date("Y-m-d",strtotime($this->_search_date_created_before)) != $this->_search_date_created_before )
			throw new Exception("Invalid date created before: must be in YYYY-MM-DD format.");
		else if( $this->_search_date_created_before )
			$this->_purchases = $this->_purchases->
				where('date_created','<',$this->_search_date_created_before);

		if( $this->_search_date_created_after AND 
			date("Y-m-d",strtotime($this->_search_date_created_after)) != $this->_search_date_created_after )
			throw new Exception("Invalid date created before: must be in YYYY-MM-DD format.");
		else if( $this->_search_date_created_after )
			$this->_purchases = $this->_purchases->
				where('date_created','>',$this->_search_date_created_after);

		if( $this->_search_date_billed_before AND 
			date("Y-m-d",strtotime($this->_search_date_billed_before)) != $this->_search_date_billed_before )
			throw new Exception("Invalid date created before: must be in YYYY-MM-DD format.");
		else if( $this->_search_date_billed_before )
			$this->_purchases = $this->_purchases->
				where('date_billed','IS NOT',NULL)->
				where('date_billed','<',$this->_search_date_billed_before);

		if( $this->_search_date_billed_after AND 
			date("Y-m-d",strtotime($this->_search_date_billed_after)) != $this->_search_date_billed_after )
			throw new Exception("Invalid date created before: must be in YYYY-MM-DD format.");
		else if( $this->_search_date_billed_after )
			$this->_purchases = $this->_purchases->
				where('date_billed','IS NOT',NULL)->
				where('date_billed','>',$this->_search_date_billed_after);

		if( $this->_search_keywords )
			foreach( explode(' ',$this->_search_keywords) as $keyword )
				if( trim($keyword) && $keyword = trim($keyword) )
					$this->_purchases = $this->_purchases->
						and_where_open()->
							or_where('entities.first_name','LIKE','%'.$keyword.'%')->
							or_where('entities.last_name','LIKE','%'.$keyword.'%')->
							or_where('entities.company_name','LIKE','%'.$keyword.'%')->
							or_where('entities.phone_number','LIKE','%'.$keyword.'%')->
							or_where('form_purchase.total','=',$this->_beans_round(floatval($keyword)))->
							or_where('form_purchase.code','LIKE','%'.$keyword.'%')->			// Purchase Number
							or_where('form_purchase.reference','LIKE','%'.$keyword.'%')->		// Order Number
							or_where('form_purchase.alt_reference','LIKE','%'.$keyword.'%')->	// PO Number
							or_where('form_purchase.aux_reference','LIKE','%'.$keyword.'%')->	// Quote Number
							or_where('form_purchase.date_created','=',$keyword)->
						and_where_close();

		$this->_purchases = $this->_purchases
			->and_where_close();

		$result_object = $this->_find_purchases();

		return (object)array(
			"total_results" => $result_object->total_results,
			"sort_by" => $this->_sort_by,
			"pages" => $result_object->pages,
			"page" => $result_object->page,
			"purchases" => $this->_return_vendor_purchases_array($result_object->purchases),
		);
	}

	protected function _find_purchases()
	{
		$return_object = new stdClass;

		if( $this->_page < 0 )
			throw new Exception("Invalid page: must be >= 0.");

		if( $this->_page_size < 1 )
			throw new Exception("Invalid page size: must be >= 1.");

		if( $this->_sort_by AND 
			! isset($this->_sort_by_patterns[$this->_sort_by]) )
			throw new Exception("Invalid sort by value.");

		$purchases_count = clone($this->_purchases);
		$return_object->total_results = $purchases_count->count_all('form_purchase.id');
		$return_object->pages = $return_object->total_results / $this->_page_size;
		$return_object->page = $this->_page;

		// Fix pages.
		$return_object->pages = ( (int)$return_object->pages == $return_object->pages )
							  ? $return_object->pages
							  : ((int)$return_object->pages + 1);

		$this->_purchases = $this->_purchases->
			limit($this->_page_size)->
			offset($this->_page * $this->_page_size);

		if( $this->_sort_by AND 
			isset($this->_sort_by_patterns[$this->_sort_by]) ) 
			foreach( $this->_sort_by_patterns[$this->_sort_by] as $column => $direction )
				$this->_purchases = $this->_purchases->
					order_by($column,$direction);
		
		$return_object->purchases = $this->_purchases->find_all();

		return $return_object;
	}

}
