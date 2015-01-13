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
@action Beans_Customer_Sale_Calibrate_Check
@description Determine if any sales in the current fiscal year require calibration due to one or more errors.
@required auth_uid
@required auth_key
@required auth_expiration
@returns ids ARRAY An array of sale IDs that require calibration.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Calibrate_Check extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";

	protected $_data;

	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}	

	protected function _execute()
	{
		$fye_date = $this->_get_books_closed_date();
		
		$sale_ids = DB::Query(
			Database::SELECT,
			' SELECT '.
			' id '.
			' FROM forms '.
			' WHERE '.
			' type = "sale" AND '.
			' date_created > DATE("'.$fye_date.'") AND '.
			' ( '.
			'   ( create_transaction_id IS NULL ) OR '.
			'   ( date_billed IS NOT NULL AND invoice_transaction_id IS NULL ) OR '.
			'   ( date_cancelled IS NOT NULL AND cancel_transaction_id IS NULL ) '.
			' ) '
		)->execute()->as_array();

		$ids = array();

		foreach( $sale_ids as $sale_id )
			$ids[] = $sale_id['id'];

		return (object)array(
			'ids' => $ids,
		);
	}
}