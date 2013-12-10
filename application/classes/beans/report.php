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

class Beans_Report extends Beans {
	
	protected $_auth_role_perm = "reports";
	
	/**
	 * Empty constructor to pull in Beans data.
	 * @param stdClass $data
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}

	protected function _build_type_chart($account,$type,$include_pending = FALSE)
	{
		$return_object = new stdClass;

		$return_object->name = $account->name;
		$return_object->id = $account->id;
		$return_object->type = strtolower($account->account_type->type);
		$return_object->table_sign = $account->account_type->table_sign;

		$return_object->accounts = array();

		foreach( $account->child_accounts->find_all() as $child_account )
		{
			$child_account_result = $this->_build_type_chart($child_account,$type,$include_pending);
			if( $child_account_result )
				$return_object->accounts[] = $child_account_result;
		}

		if( count($return_object->accounts) OR
			(
				(
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					is_array($type) AND 
					in_array($return_object->type, $type)
				) OR
				(
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					$return_object->type == $type
				)
			) )
			return $return_object;

		return FALSE;
	}

	protected function _build_code_chart($account,$code,$include_pending = FALSE)
	{
		$return_object = new stdClass;

		$return_object->name = $account->name;
		$return_object->id = $account->id;
		$return_object->code = strtolower($account->account_type->code);
		$return_object->table_sign = $account->account_type->table_sign;

		$return_object->accounts = array();

		foreach( $account->child_accounts->find_all() as $child_account )
		{
			$child_account_result = $this->_build_code_chart($child_account,$code,$include_pending);
			if( $child_account_result )
				$return_object->accounts[] = $child_account_result;
		}

		if( count($return_object->accounts) OR
			(
				(
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					is_array($code) AND 
					in_array($return_object->code, $code)
				) OR
				(
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					$return_object->code == $code
				)
			) )
			return $return_object;

		return FALSE;
	}

	protected function _build_code_array($code,$include_pending = FALSE)
	{
		$return_array = array();

		foreach( ORM::Factory('account')->where('account_type_id','IS NOT',NULL)->find_all() as $account )
		{
			if( (
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					is_array($code) AND 
					in_array($account->account_type->code,$code)
				) OR
				(
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					strtolower($account->account_type->code) == $code 
				) )
				$return_array[] = (object)array(
					'name' => $account->name,
					'id' => $account->id,
					'table_sign' => $account->account_type->table_sign,
				);
		}

		return $return_array;
	}

	protected function _build_type_array($type,$include_pending = FALSE)
	{
		$return_array = array();

		foreach( ORM::Factory('account')->where('account_type_id','IS NOT',NULL)->find_all() as $account )
		{
			if( (
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					is_array($type) AND 
					in_array($account->account_type->type,$type)
				) OR
				(
					(
						strpos($account->account_type->code, "pending_") === FALSE OR 
						$include_pending 
					) AND
					strtolower($account->account_type->type) == $type 
				) )
				$return_array[] = (object)array(
					'name' => $account->name,
					'id' => $account->id,
					'table_sign' => $account->account_type->table_sign,
				);
		}
		
		usort($return_array,array($this,'_sort_accounts_by_name'));
		
		return $return_array;
	}

	private function _sort_accounts_by_name($a,$b)
	{
		if( $a->name == $b->name )
			return 0;

		return ( strcmp($a->name,$b->name) < 0 ? -1 : 1 );
	}

	protected function _generate_account_balance_total($accounts)
	{
		$balance = 0.00;

		foreach( $accounts as $account )
		{
			if( $account->balance )
				$balance = $this->_beans_round( $balance + $account->balance );
			
			$balance = $this->_beans_round( $balance + $this->_generate_account_balance_total($account->accounts) );
		}

		return $balance;
	}

	protected function _generate_simple_account_balance($account_id,$table_sign,$date_end,$date_start = FALSE,$include_close_books = FALSE)
	{
		$balance_query = ' SELECT IFNULL(SUM(amount),0.00) as bal FROM account_transactions WHERE '.
						 ' account_id = "'.$account_id.'" '.
						 ( ! $include_close_books ? ' AND close_books = 0 ' : ' ' ).
						 ( $date_end ? ' AND date <= DATE("'.$date_end.'") ' : ' ' ).
						 ( $date_start ? ' AND date >= DATE("'.$date_start.'") ' : ' ' );

		$balance_rows = DB::Query(Database::SELECT, $balance_query)->execute()->as_array();

		$balance = $balance_rows[0]['bal'] * $table_sign;

		return $balance;
	}

	protected function _generate_account_balance($account,$date_end,$date_start = FALSE,$include_close_books = FALSE)
	{
		$account->balance = NULL;

		$balance_query = ' SELECT IFNULL(SUM(amount),0.00) as bal FROM account_transactions WHERE '.
						 ' account_id = "'.$account->id.'" '.
						 ( ! $include_close_books ? ' AND close_books = 0 ' : ' ' ).
						 ( $date_end ? ' AND date <= DATE("'.$date_end.'") ' : '' ).
						 ( $date_start ? ' AND date >= DATE("'.$date_start.'") ' : '' );

		$balance_rows = DB::Query(Database::SELECT, $balance_query)->execute()->as_array();

		$account->balance = $balance_rows[0]['bal'] * $account->table_sign;
		
		if( isset($account->accounts) AND 
			count($account->accounts) )
			foreach( $account->accounts as $key => $child_account )
				$account->accounts[$key] = $this->_generate_account_balance($child_account,$date_end,$date_start);
		
		return $account;
	}

	protected function _get_week_first_day($date,$format = "Y-m-d")
	{
		return date($format,strtotime("-6 Days",strtotime('+'.date('W',strtotime($date)).' Weeks',strtotime(date('Y',strtotime($date)).'-01-01'))));
	}

	protected function _get_week_last_day($date,$format = "Y-m-d")
	{
		return date($format,strtotime("-0 Days",strtotime('+'.date('W',strtotime($date)).' Weeks',strtotime(date('Y',strtotime($date)).'-01-01'))));
	}

	protected function _generate_date_ranges($date_start,$date_end,$interval,$include_time = TRUE)
	{
		$date_ranges = array();

		if( $date_start != date("Y-m-d",strtotime($date_start)) )
			throw new Exception("Invalid date start: must be in YYYY-MM-DD.");
		
		if( $date_end != date("Y-m-d",strtotime($date_end)) )
			throw new Exception("Invalid date start: must be in YYYY-MM-DD.");
		
		$date_counter = $date_end;

		if( $interval == 'day' )
		{
			while( strtotime($date_counter) >= strtotime($date_start) )
			{	
				$date_ranges[] = (object)array(
					'date_start' => $date_counter.' 00:00:00',
					'date_end' => $date_counter.' 23:59:59',
				);
				
				$date_counter = date('Y-m-d',strtotime($date_counter." -1 Days"));
			}
		}
		else if ( $interval == 'week' )
		{
			while( strtotime($date_counter) >= strtotime($date_start) )
			{
				$date_ranges[] = (object)array(
					'date_start' => ( strtotime($this->_get_week_first_day($date_counter).' 00:00:00') < strtotime($date_start) )
								 ? $date_start.' 00:00:00'
								 : $this->_get_week_first_day($date_counter).' 00:00:00',
					'date_end' => ( count($date_ranges) == 0 AND strtotime($this->_get_week_last_day($date_counter).' 23:59:59') > strtotime($date_end) )
							   ? $date_end.' 23:59:59'
							   : $this->_get_week_last_day($date_counter).' 23:59:59',
				);
				
				$date_counter = date('Y-m-d',strtotime($date_counter." -1 Weeks"));
			}
		}
		else if ( $interval == 'month' )
		{
			while( strtotime($date_counter) >= strtotime($date_start) )
			{
				$date_ranges[] = (object)array(
					'date_start' => ( strtotime(date('Y-m',strtotime($date_counter)).'-01 00:00:00') < strtotime($date_start) )
								 ? $date_start.' 00:00:00'
								 : date('Y-m',strtotime($date_counter)).'-01 00:00:00',
					'date_end' => $date_counter.' 23:59:59',
				);
				
				$date_counter = date('Y-m-d',strtotime(date('Y-m',strtotime($date_counter)).'-01 00:00:00')-1);
			}
		}
		else if ( $interval == 'year' )
		{
			while( strtotime($date_counter) >= strtotime($date_start) )
			{
				$date_ranges[] = (object)array(
					'date_start' => ( ( strtotime(date('Y',strtotime($date_counter)).'-01-01 00:00:00') < strtotime($date_start) ) )
								 ? $date_start.' 00:00:00'
								 : date('Y',strtotime($date_counter)).'-01-01 00:00:00',
					'date_end' => $date_counter.' 23:59:59',
				);
				
				$date_counter = date('Y-m-d',strtotime(date('Y',strtotime($date_counter)).'-01-01 00:00:00')-1);
			}
		}
		else
		{
			throw new Exception("Invalid interval: must be 'day', 'week', 'month', 'year'.");
		}

		if( ! $include_time )
		{
			foreach( $date_ranges as $index => $date_range )
			{
				$date_ranges[$index]->date_start = substr($date_range->date_start,0,10);
				$date_ranges[$index]->date_end = substr($date_range->date_end,0,10);
			}
		}

		return array_reverse($date_ranges);
	}


	/**
	 * DUPLICATE FUNCTIONS
	 */
	protected function _load_vendor($id)
	{
		return ORM::Factory('entity_vendor')->where('type','=','vendor')->where('id','=',$id)->find();
	}

	protected function _load_customer($id)
	{
		return ORM::Factory('entity_customer')->where('type','=','customer')->where('id','=',$id)->find();
	}

	protected function _load_tax($id)
	{
		return ORM::Factory('tax',$id);
	}

	protected function _load_account($id)
	{
		return ORM::Factory('account',$id);
	}


	/**
	 * Returns an object of the properties for the given Model_Account (ORM)
	 * @param  Model_Account $account Model_Account ORM Object
	 * @return stdClass          stdClass of properties for given Model_Account.
	 * @throws Exception If Model_Account object is not valid.
	 */
	private $_return_account_element_cache = array();
	protected function _return_account_element($account)
	{
		$return_object = new stdClass;

		// Verify this model.
		if( ! $account->loaded() OR
			get_class($account) != "Model_Account" )
			throw new Exception("Invalid Account.");

		if( isset($this->_return_account_element_cache[$account->id]) )
			return $this->_return_account_element_cache[$account->id];

		// Account Details
		$return_object->id = $account->id;
		$return_object->parent_account_id = $account->parent_account_id;
		$return_object->reserved = $account->reserved ? TRUE : FALSE;
		$return_object->name = $account->name;
		$return_object->code = $account->code;
		$return_object->reconcilable = $account->reconcilable ? TRUE : FALSE;
		$return_object->terms = (int)$account->terms;
		$return_object->balance = (float)$account->balance;
		$return_object->deposit = $account->deposit ? TRUE : FALSE;
		$return_object->payment = $account->payment ? TRUE : FALSE;
		$return_object->receivable = $account->receivable ? TRUE : FALSE;
		$return_object->payable = $account->payable ? TRUE : FALSE;
		$return_object->writeoff = $account->writeoff ? TRUE : FALSE;
		
		// Account Type
		$return_object->type = $this->_return_account_type_element($account->account_type);
		
		$this->_return_account_element_cache[$account->id] = $return_object;
		return $this->_return_account_element_cache[$account->id];
	}
	
	/**
	 * Returns an object of the properties for the given Model_Account_Type (ORM)
	 * @param  Model_Account_Type $account_type Model_Account_Type ORM Object
	 * @return stdClass               stdClass of the properties for a given Model_Account_Type
	 * @throws Exception If Account Type is not valid.
	 */
	private $_return_account_type_element_cache = array();
	protected function _return_account_type_element($account_type)
	{
		$return_object = new stdClass;

		if( ! $account_type->loaded() )
			return $return_object;

		if( get_class($account_type) != "Model_Account_Type" )
			throw new Exception("Invalid Account Type.");

		if( isset($this->_return_account_type_element_cache[$account_type->id]) )
			return $this->_return_account_type_element_cache[$account_type->id];

		$return_object->id = $account_type->id;
		$return_object->name = $account_type->name;
		$return_object->code = $account_type->code;
		$return_object->type = $account_type->type;
		$return_object->table_sign = $account_type->table_sign;
		$return_object->deposit = $account_type->deposit;
		$return_object->payment = $account_type->payment;
		$return_object->receivable = $account_type->receivable;
		$return_object->payable = $account_type->payable;
		$return_object->reconcilable = $account_type->reconcilable;

		$this->_return_account_type_element_cache[$account_type->id] = $return_object;
		return $this->_return_account_type_element_cache[$account_type->id];
	}

}