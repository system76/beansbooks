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


class View_Dash_Index extends View_Template {
	
	public function report_cash_lines()
	{
		if( ! isset($this->report_cash_result) )
			return FALSE;

		$settings = $this->beans_settings();

		$return_array = array();

		foreach( $this->report_cash_result->data->account_types as $account_type )
		{
			$account_type_array = $this->_report_cash_lines_array($account_type->accounts);
			$return_array = array_merge($return_array,$account_type_array);
			
			$level_count = 0;
			foreach( $account_type_array as $item )
				if( $item['indent_level_px'] > 0 )
					$level_count++;

			if( $level_count > 1 )
				$return_array[] = array(
					'name' => "Total",
					'indent_level_px' => 50,
					'total_formatted' => 
						( $account_type->balance_total < 0 ? '<span class="text-red">-' : '' ).
						$settings->company_currency.
						number_format(abs($account_type->balance_total),2,'.',',').
						( $account_type->balance_total < 0 ? '</span>' : '' ),
				);
			$return_array[] = array(
				'name' => ' ',
				'indent_level_px' => 0,
			);
		}

		return $return_array;
	}

	public function report_cash_total()
	{
		if( ! isset($this->report_cash_result) )
			return FALSE;

		$settings = $this->beans_settings();

		$return_array = array();

		$return_array['name'] = "Grand Total";
		$return_array['total_formatted'] = 
			( $this->report_cash_result->data->net < 0 ? '<span class="text-red">-' : '' ).
			$settings->company_currency.
			number_format(abs($this->report_cash_result->data->net),2,'.',',').
			( $this->report_cash_result->data->net < 0 ? '</span>' : '' );

		return $return_array;
	}

	private function _report_cash_lines_array($accounts,$level = 0)
	{
		$settings = $this->beans_settings();

		$return_array = array();

		foreach( $accounts as $account )
		{
			if( $level == 0 OR 
				$account->balance != NULL )
				$return_array[] = array(
					'name' => $account->name,
					'showzero' => ( strpos($account->code,'pending_') === FALSE && $level == 0 ? FALSE : TRUE ),
					'indent_level_px' => ( ( strpos($account->code,'pending_') !== FALSE ? ( $level + 1 ) : $level ) * 15),
					'amount_formatted' => ( $level == 0 AND $account->balance == NULL )
									   ? FALSE
									   : ( $account->balance < 0 ? '<span class="text-red">-' : '' ).
										 $settings->company_currency.
										 number_format(abs($account->balance),2,'.',',').
										 ( $account->balance < 0 ? '</span>' : '' ),
				);

			if( isset($account->accounts) )
				$return_array = array_merge($return_array,$this->_report_cash_lines_array($account->accounts,($level+1)));
		}

		return $return_array;
	}

	public function incomeexpense_date_start() {
		if( Session::instance()->get('dash_incomeexpense_date_start') )
			return Session::instance()->get('dash_incomeexpense_date_start');

		return date("Y-m",strtotime("-5 Months")).'-01';
	}

	public function incomeexpense_date_end() {
		if( Session::instance()->get('dash_incomeexpense_date_end') )
			return Session::instance()->get('dash_incomeexpense_date_end');

		return date("Y-m-d");
	}

	public function income_date_start() {
		if( Session::instance()->get('dash_income_date_start') )
			return Session::instance()->get('dash_income_date_start');

		return date("Y-m",strtotime("-11 Months")).'-01';
	}

	public function income_date_end() {
		if( Session::instance()->get('dash_income_date_end') )
			return Session::instance()->get('dash_income_date_end');

		return date("Y-m-d");
	}

	public function expenses_months() {
		$return_array = array();

		$date = date("Y-m-d");

		if( Session::instance()->get('dash_expense_date') )
			$date = Session::instance()->get('dash_expense_date');

		for( $i = 0; $i <= 36; $i++ )
			$return_array[] = array(
				'value' => date("Y-m-d",strtotime("-".$i." Months")),
				'label' => date("F Y",strtotime("-".$i." Months")),
				'default' => ( $date == date("Y-m-d",strtotime("-".$i." Months")) ) ? TRUE : FALSE,
			);

		return $return_array;
	}

	private $_sales_past_due;
	public function sales_past_due()
	{
		if( isset($this->_sales_past_due) )
			return $this->_sales_past_due;

		if( ! isset($this->sales_past_due_result) )
			return FALSE;

		$return_array = $this->_generate_form_lines($this->sales_past_due_result->data->sales);

		$j = 1;
		foreach( $return_array as $index => $array )
		{
			$return_array[$index]['odd'] = ( $j++ % 2 ) ? TRUE : FALSE;
			$return_array[$index]['hidden'] = ( $index >= 10 ) ? TRUE : FALSE;
		}

		$this->_sales_past_due = $return_array;

		return $this->_sales_past_due;
	}

	public function sales_past_due_more()
	{
		if( count($this->sales_past_due()) > 10 )
			return array(
				'count' => ( count($this->sales_past_due()) - 10 ),
			);

		return FALSE;
	}

	private $_sales_not_invoiced;
	public function sales_not_invoiced()
	{
		if( isset($this->_sales_not_invoiced) )
			return $this->_sales_not_invoiced;

		if( ! isset($this->sales_not_invoiced_result) )
			return FALSE;

		$return_array = $this->_generate_form_lines($this->sales_not_invoiced_result->data->sales, 'date_created');

		$j = 1;
		foreach( $return_array as $index => $array )
		{
			$return_array[$index]['odd'] = ( $j++ % 2 ) ? TRUE : FALSE;
			$return_array[$index]['hidden'] = ( $index >= 10 ) ? TRUE : FALSE;
		}

		$this->_sales_not_invoiced = $return_array;

		return $this->_sales_not_invoiced;
	}

	public function sales_not_invoiced_more()
	{
		if( count($this->sales_not_invoiced()) > 10 )
			return array(
				'count' => ( count($this->sales_not_invoiced()) - 10 ),
			);

		return FALSE;
	}

	private $_purchases_not_invoiced;
	public function purchases_not_invoiced()
	{
		if( isset($this->_purchases_not_invoiced) )
			return $this->_purchases_not_invoiced;

		if( ! isset($this->purchases_not_invoiced_result) )
			return FALSE;

		$return_array = $this->_generate_form_lines($this->purchases_not_invoiced_result->data->purchases, 'date_created');

		$j = 1;
		foreach( $return_array as $index => $array )
		{
			$return_array[$index]['odd'] = ( $j++ % 2 ) ? TRUE : FALSE;
			$return_array[$index]['hidden'] = ( $index >= 10 ) ? TRUE : FALSE;
		}

		$this->_purchases_not_invoiced = $return_array;

		return $this->_purchases_not_invoiced;
	}

	public function purchases_not_invoiced_more()
	{
		if( count($this->purchases_not_invoiced()) > 10 )
			return array(
				'count' => ( count($this->purchases_not_invoiced()) - 10 ),
			);

		return FALSE;
	}

	private $_purchases_past_due;
	public function purchases_past_due()
	{
		if( isset($this->_purchases_past_due) )
			return $this->_purchases_past_due;

		if( ! isset($this->purchases_past_due_result) )
			return FALSE;

		$return_array = $this->_generate_form_lines($this->purchases_past_due_result->data->purchases);

		$j = 1;
		foreach( $return_array as $index => $array )
		{
			$return_array[$index]['odd'] = ( $j++ % 2 ) ? TRUE : FALSE;
			$return_array[$index]['hidden'] = ( $index >= 10 ) ? TRUE : FALSE;
		}

		$this->_purchases_past_due = $return_array;

		return $this->_purchases_past_due;
	}

	public function purchases_past_due_more()
	{
		if( count($this->purchases_past_due()) > 10 )
			return array(
				'count' => ( count($this->purchases_past_due()) - 10 ),
			);

		return FALSE;
	}

	private function _generate_form_lines($forms, $date_field = "date_due")
	{
		$settings = $this->beans_settings();

		$return_array = array();

		foreach( $forms as $form )
			$return_array[] = array(
				'id' => $form->id,
				'number' => ( isset($form->sale_number) )
						 ? $form->sale_number
						 : $form->purchase_number,
				'url' => ( isset($form->customer) )
					  ? ( $form->date_billed 
					  		? '/customers/invoices/'.$form->id 
					  		: '/customers/sales/'.$form->id
					  	)
					  : '/vendors/purchases/'.$form->id,
				'name' => ( isset($form->customer) )
					   ? $form->customer->display_name
					   : $form->vendor->display_name,
				'amount_formatted' => ( ( ( isset($form->customer) AND $form->balance > 0 ) OR ( ! isset($form->customer) AND $form->balance < 0 ) ) ? '<span class="text-red">-' : '' ).
										$settings->company_currency.
										number_format(abs($form->balance),2,'.',',').
										( ( ( isset($form->customer) AND $form->balance > 0 ) OR ( ! isset($form->customer) AND $form->balance < 0 ) ) ? '</span>' : '' ),
				'date' => $form->{$date_field}
			);

		return $return_array;
	}

	public function dash_messages()
	{
		$return_array = array();

		if( ! isset($this->messages) OR 
			! is_array($this->messages) OR 
			! count($this->messages) )
			return FALSE;

		foreach( $this->messages as $message ) 
		{
			$return_array[] = $this->_dash_message_array($message);
		}

		return $return_array;
	}

	private function _dash_message_array($message)
	{
		$return_array = array(
			'title' => $message->title,
			'text' => $message->text,
		);

		if( ! isset($message->actions) OR
			! $message->actions OR
			! count($message->actions) )
			return FALSE;

		$return_array['actions'] = array();

		foreach( $message->actions as $action )
		{
			$return_array['actions'][] = $this->_dash_message_action_array($action);
		}

		return $return_array;
	}

	private function _dash_message_action_array($action)
	{
		$return_array = array(
			'text' => $action->text,
			'url' => ( isset($action->url) ) ? $action->url : FALSE,
			'id' => ( isset($action->id) ) ? $action->id : FALSE,
		);

		return $return_array;
	}

}