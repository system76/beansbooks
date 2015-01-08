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

class View_Setup_Calibrate extends View_Template {
	
	public function calibrate_required()
	{
		if( isset($this->customer_sale_calibrate_check_result) &&
			count($this->customer_sale_calibrate_check_result->data->ids) )
			return TRUE;
		
		if( isset($this->vendor_purchase_calibrate_check_result) &&
			count($this->vendor_purchase_calibrate_check_result->data->ids) )
			return TRUE;
		
		if( isset($this->account_calibrate_check_result->data->account_ids) &&
			count($this->account_calibrate_check_result->data->account_ids) )
			return TRUE;
		
		if( isset($this->setup_company_list_result->data->settings) &&
			isset($this->setup_company_list_result->data->settings->calibrate_date_next) &&
			$this->setup_company_list_result->data->settings->calibrate_date_next )
			return TRUE;

		if( isset($this->report_balancecheck_result) &&
			! $this->report_balancecheck_result->data->balanced )
			return TRUE;

		return FALSE;
	}

}