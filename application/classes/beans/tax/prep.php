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

// TODO_V_1_3 - Increase doc to handle nested attributes?

/*
---BEANSAPISPEC---
@action Beans_Tax_Prep
@description Prepares information to be able to create a tax payment.
@required auth_uid 
@required auth_key 
@required auth_expiration
@required id INTEGER The ID of the #Beans_Tax# being paid.
@required date_start STRING Inclusive YYYY-MM-DD date ( i.e. >= date_start )
@required date_end STRING Inclusive YYYY-MM-DD date ( i.e. <= date_end )
@optional payment_id INTEGER The ID of a #Beans_Tax_Payment# to ignore when tabulating totals.  Useful if updating a previous payment.
@returns tax OBJECT The applicable #Beans_Tax#.
@returns taxes OBJECT The applicable taxes for the given period.
@returns @attribute taxes paid OBJECT The taxes that have already been paid during this period.  Includes invoiced and refunded - objects - each with line_amount, line_taxable_amount, amount and exemptions.
@returns @attribute taxes due OBJECT The taxes that have already been paid during this period.  Includes invoiced and refunded - objects - each with line_amount, line_taxable_amount, amount and exemptions.
---BEANSENDSPEC---
*/
class Beans_Tax_Prep extends Beans_Tax {
	protected $_auth_role_perm = "customer_sale_read";

	protected $_id;
	protected $_tax;
	protected $_date_start;
	protected $_data_end;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_payment_id = ( isset($data->payment_id) AND
							   $data->payment_id )
						   ? $data->payment_id
						   : -1;	// a value that will never match a real record ( BIGINT UNSIGNED )

		$this->_tax = $this->_load_tax($this->_id);

		$this->_date_start = ( isset($data->date_start) )
						   ? $data->date_start
						   : NULL;

		$this->_date_end = ( isset($data->date_end) )
						   ? $data->date_end
						   : NULL;
	}

	protected function _execute()
	{
		if( ! $this->_tax->loaded() )
			throw new Exception("Tax could not be found.");

		if( ! $this->_date_start ) 
			throw new Exception("Invalid date start: none provided.");

		if( $this->_date_start != date("Y-m-d",strtotime($this->_date_start)) )
			throw new Exception("Invalid date start: must be in YYYY-MM-DD format.");

		if( ! $this->_date_end ) 
			throw new Exception("Invalid date start: none provided.");

		if( $this->_date_end != date("Y-m-d",strtotime($this->_date_end)) )
			throw new Exception("Invalid date start: must be in YYYY-MM-DD format.");

		$periods = array(
			'paid' => 'tax_payment_id IS NOT NULL AND date <= DATE("'.$this->_date_end.'") AND date >= DATE("'.$this->_date_start.'")', 
			'due' => 'tax_payment_id IS NULL AND date <= DATE("'.$this->_date_end.'")',
		);

		$categories = array(
			'invoiced' => 'type = "invoice"',
			'refunded' => 'type = "refund"',
		);

		$taxes = (object)array();

		$taxes->net = (object)array(
			'form_line_amount' => 0.00,
			'form_line_taxable_amount' => 0.00,
			'amount' => 0.00,
		);

		foreach( $periods as $period => $period_query )
		{
			$taxes->{$period} = (object)array();

			$taxes->{$period}->net = (object)array(
				'form_line_amount' => 0.00,
				'form_line_taxable_amount' => 0.00,
				'amount' => 0.00,
			);

			foreach( $categories as $category => $category_query )
			{
				$taxes->{$period}->{$category} = (object)array(
					'form_line_amount' => 0.00,
					'form_line_taxable_amount' => 0.00,
					'amount' => 0.00,
					'liabilities' => array(),
				);

				$tax_item_summaries_query = 
					'SELECT '.
					'form_id as form_id, '.
					'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
					'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
					'IFNULL(SUM(total),0.00) as amount '.
					'FROM tax_items WHERE '.
					'tax_id = '.$this->_tax->id.' AND '.
					$period_query.' AND '.
					$category_query.' '.
					'GROUP BY form_id ';

				$tax_item_summaries = DB::Query(Database::SELECT, $tax_item_summaries_query)->execute()->as_array();

				foreach( $tax_item_summaries as $tax_item_summary )
				{
					$taxes->net->form_line_amount = $this->_beans_round(
						$taxes->net->form_line_amount +
						$tax_item_summary['form_line_amount']
					);
					$taxes->net->form_line_taxable_amount = $this->_beans_round(
						$taxes->net->form_line_taxable_amount +
						$tax_item_summary['form_line_taxable_amount']
					);
					$taxes->net->amount = $this->_beans_round(
						$taxes->net->amount +
						$tax_item_summary['amount']
					);
					
					$taxes->{$period}->net->form_line_amount = $this->_beans_round(
						$taxes->{$period}->net->form_line_amount +
						$tax_item_summary['form_line_amount']
					);
					$taxes->{$period}->net->form_line_taxable_amount = $this->_beans_round(
						$taxes->{$period}->net->form_line_taxable_amount +
						$tax_item_summary['form_line_taxable_amount']
					);
					$taxes->{$period}->net->amount = $this->_beans_round(
						$taxes->{$period}->net->amount +
						$tax_item_summary['amount']
					);
					
					$taxes->{$period}->{$category}->form_line_amount = $this->_beans_round(
						$taxes->{$period}->{$category}->form_line_amount +
						$tax_item_summary['form_line_amount']
					);
					$taxes->{$period}->{$category}->form_line_taxable_amount = $this->_beans_round(
						$taxes->{$period}->{$category}->form_line_taxable_amount +
						$tax_item_summary['form_line_taxable_amount']
					);
					$taxes->{$period}->{$category}->amount = $this->_beans_round(
						$taxes->{$period}->{$category}->amount +
						$tax_item_summary['amount']
					);
					
					$taxes->{$period}->{$category}->liabilities[] = $this->_return_tax_liability_element(
						$tax_item_summary['form_id'],
						$tax_item_summary['form_line_amount'], 
						$tax_item_summary['form_line_taxable_amount'], 
						$tax_item_summary['amount']
					);
				}
			}
		}

		return (object)array(
			'tax' => $this->_return_tax_element($this->_tax),
			'taxes' => $taxes,
		);


		// TODO_V_1_3 - REMOVE ALL OF THIS WHEN READY

		// // // // // // // 
		// OLD BELOW HERE // 
		// // // // // // // 
		/*
		$taxes_due = (object)array(
			'invoice_line_amount' => 0.00,
			'invoice_line_taxable_amount' => 0.00,
			'invoice_amount' => 0.00,
			'refund_line_amount' => 0.00,
			'refund_line_taxable_amount' => 0.00,
			'refund_amount' => 0.00,
			'net_line_amount' => 0.00,
			'net_line_taxable_amount' => 0.00,
			'net_amount' => 0.00,
			'tax_exemptions' => array(),
		);

		$taxes_due_form_totals = array();

		$taxes_due_invoice_totals_query = 
			'SELECT '.
			'form_id as form_id, '.
			'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
			'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
			'IFNULL(SUM(total),0.00) as total, '.
			'IFNULL(SUM(balance),0.00) as balance '.
			'FROM tax_items WHERE '.
			'tax_id = '.$this->_tax->id.' AND '.
			'tax_payment_id IS NULL AND '.
			'date <= DATE("'.$this->_date_end.'") AND '.
			'type = "invoice" '.
			'GROUP BY form_id ';

		$taxes_due_invoice_totals = DB::Query(Database::SELECT, $taxes_due_invoice_totals_query)->execute()->as_array();

		// $taxes_due->invoice_line_amount = $taxes_due_invoice_totals[0]['form_line_amount'];
		// $taxes_due->invoice_line_taxable_amount = $taxes_due_invoice_totals[0]['form_line_taxable_amount'];
		// $taxes_due->invoice_amount = $taxes_due_invoice_totals[0]['total'];
		foreach( $taxes_due_invoice_totals as $taxes_due_invoice_total )
		{
			$taxes_due->invoice_line_amount = $this->_beans_round(
				$taxes_due->invoice_line_amount + 
				$taxes_due_invoice_total['invoice_line_amount']
			);
			$taxes_due->invoice_line_taxable_amount = $this->_beans_round(
				$taxes_due->invoice_line_taxable_amount + 
				$taxes_due_invoice_total['invoice_line_taxable_amount']
			);
			$taxes_due->invoice_amount = $this->_beans_round(
				$taxes_due->invoice_amount + 
				$taxes_due_invoice_total['total']
			);
			
			$taxes_due_form_totals[$taxes_due_form_total['tax_id']] = (object)$taxes_due_form_total;
		}

		$taxes_due_refund_totals_query = 
			'SELECT '.
			'form_id as form_id, '.
			'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
			'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
			'IFNULL(SUM(total),0.00) as total, '.
			'IFNULL(SUM(balance),0.00) as balance '.
			'FROM tax_items WHERE '.
			'tax_id = '.$this->_tax->id.' AND '.
			'tax_payment_id IS NULL AND '.
			'date <= DATE("'.$this->_date_end.'") AND '.
			'type = "refund" '.
			'GROUP BY form_id';

		$taxes_due_refund_totals = DB::Query(Database::SELECT, $taxes_due_refund_totals_query)->execute()->as_array();

		$taxes_due->refund_line_amount = $taxes_due_refund_totals[0]['form_line_amount'];
		$taxes_due->refund_line_taxable_amount = $taxes_due_refund_totals[0]['form_line_taxable_amount'];
		$taxes_due->refund_amount = $taxes_due_refund_totals[0]['total'];
		
		$taxes_due->net_line_amount = $this->_beans_round(
			$taxes_due->invoice_line_amount +
			$taxes_due->refund_line_amount
		);
		$taxes_due->net_line_taxable_amount = $this->_beans_round(
			$taxes_due->invoice_line_taxable_amount +
			$taxes_due->refund_line_taxable_amount
		);
		$taxes_due->net_amount = $this->_beans_round(
			$taxes_due->invoice_amount +
			$taxes_due->refund_amount
		);

		$taxes_paid = (object)array(
			'invoice_line_amount' => 0.00,
			'invoice_line_taxable_amount' => 0.00,
			'invoice_amount' => 0.00,
			'refund_line_amount' => 0.00,
			'refund_line_taxable_amount' => 0.00,
			'refund_amount' => 0.00,
			'net_line_amount' => 0.00,
			'net_line_taxable_amount' => 0.00,
			'net_amount' => 0.00,
			'tax_exemptions' => array(),
		);

		$taxes_paid_invoice_totals_query = 
			'SELECT '.
			'form_id as form_id, '.
			'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
			'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
			'IFNULL(SUM(total),0.00) as total, '.
			'IFNULL(SUM(balance),0.00) as balance '.
			'FROM tax_items WHERE '.
			'tax_id = '.$this->_tax->id.' AND '.
			'tax_payment_id IS NOT NULL AND '.
			'date <= DATE("'.$this->_date_end.'") AND '.
			'date >= DATE("'.$this->_date_start.'") AND '.
			'type = "invoice" '.
			'GROUP BY form_id ';

		$taxes_paid_invoice_totals = DB::Query(Database::SELECT, $taxes_paid_invoice_totals_query)->execute()->as_array();

		$taxes_paid->invoice_line_amount = $taxes_paid_invoice_totals[0]['form_line_amount'];
		$taxes_paid->invoice_line_taxable_amount = $taxes_paid_invoice_totals[0]['form_line_taxable_amount'];
		$taxes_paid->invoice_amount = $taxes_paid_invoice_totals[0]['total'];
		
		$taxes_paid_refund_totals_query = 
			'SELECT '.
			'form_id as form_id, '.
			'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
			'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
			'IFNULL(SUM(total),0.00) as total, '.
			'IFNULL(SUM(balance),0.00) as balance '.
			'FROM tax_items WHERE '.
			'tax_id = '.$this->_tax->id.' AND '.
			'tax_payment_id IS NOT NULL AND '.
			'date <= DATE("'.$this->_date_end.'") AND '.
			'date >= DATE("'.$this->_date_start.'") AND '.
			'type = "refund" '.
			'GROUP BY form_id ';

		$taxes_paid_refund_totals = DB::Query(Database::SELECT, $taxes_paid_refund_totals_query)->execute()->as_array();

		$taxes_paid->refund_line_amount = $taxes_paid_refund_totals[0]['form_line_amount'];
		$taxes_paid->refund_line_taxable_amount = $taxes_paid_refund_totals[0]['form_line_taxable_amount'];
		$taxes_paid->refund_amount = $taxes_paid_refund_totals[0]['total'];
		
		$taxes_paid->net_line_amount = $this->_beans_round(
			$taxes_paid->invoice_line_amount +
			$taxes_paid->refund_line_amount
		);
		$taxes_paid->net_line_taxable_amount = $this->_beans_round(
			$taxes_paid->invoice_line_taxable_amount +
			$taxes_paid->refund_line_taxable_amount
		);
		$taxes_paid->net_amount = $this->_beans_round(
			$taxes_paid->invoice_amount +
			$taxes_paid->refund_amount
		);
		
		return (object)array(
			'tax' => $this->_return_tax_element($this->_tax),
			"taxes_due" => $taxes_due,
			"taxes_paid" => $taxes_paid,
		);
		*/
	}

	// Consider moving this up in the hierarchy.
	
	/*
	---BEANSOBJSPEC---
	@object Beans_Tax_Liability
	@description A customer sale that has been exempted from paying sales tax.
	@attribute form_id INTEGER 
	@attribute form_line_amount DECIMAL The applicable amount of non-taxable revenue for this period.
	@attribute form_line_amount_taxable DECIMAL The applicable amount of taxable revenue for this period.
	@attribute total DECIMAL The applicable amount of taxes due for this sale.
	@attribute customer OBJECT A #Beans_Tax_Liability_Customer# object - which represents an abbreviated #Beans_Customer# object.
	@attribute account_name STRING The name of the receivable #Beans_Account# tied to this sale.
	@attribute sale_number STRING
	@attribute order_number STRING
	@attribute po_number STRING
	@attribute quote_number STRING
	@attribute tax_exempt BOOLEAN Whether or not this entire sale is tax exempt.
	@attribute tax_exempt_reason BOOLEAN Explanation for exemption.
	@attribute lines ARRAY An array of #Beans_Tax_Liability_Line# - a simplified representation of #Beans_Customer_Sale_Line#.
	@attribute title STRING A short description of the sale.
	---BEANSENDSPEC---
	 */
	protected function _return_tax_liability_element($form_id, $form_line_amount, $form_line_taxable_amount, $amount)
	{
		$sale = ORM::Factory('form', $form_id);

		if( ! $sale->loaded() )
			throw new Exception("Invalid Form ID.");

		$return_object = new stdClass;

		$return_object->form_id = $sale->id;
		$return_object->form_line_amount = $form_line_amount;
		$return_object->form_line_taxable_amount = $form_line_taxable_amount;
		$return_object->amount = $amount;
		$return_object->account_name = $this->_get_account_name_by_id($sale->account_id);
		$return_object->sale_number = $sale->code;
		$return_object->order_number = $sale->reference;
		$return_object->po_number = $sale->alt_reference;
		$return_object->quote_number = $sale->aux_reference;
		$return_object->tax_exempt = $sale->tax_exempt ? TRUE : FALSE;
		$return_object->tax_exempt_reason = $sale->tax_exempt_reason;
		$return_object->title = ( $sale->date_billed )
							  ? "Sales Invoice ".$sale->code
							  : "Sales Order ".$sale->code;

		$return_object->customer = $this->_return_tax_liability_customer_element($sale->entity);
		$return_object->lines = $this->_return_tax_liability_lines_array($sale->form_lines->find_all());

		return $return_object;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Tax_Liability_Customer
	@description An abbreviated representation of a customer in the system - primarily contact information.
	@attribute id INTEGER 
	@attribute first_name STRING
	@attribute last_name STRING
	@attribute company_name STRING
	@attribute display_name STRING Company Name if it exists, or else First Last.
	@attribute email STRING
	@attribute phone_number STRING
	@attribute fax_number STRING
	---BEANSENDSPEC---
	 */
	private $_return_tax_liability_customer_element_cache = array();
	protected function _return_tax_liability_customer_element($customer)
	{
		if( isset($this->_return_tax_liability_customer_element_cache[$customer->id]) )
			return $this->_return_tax_liability_customer_element_cache[$customer->id];

		if( get_class($customer) != "Model_Entity" ||
			$customer->type != "customer" )
			throw new Exception("Invalid Customer.");

		$return_object = new stdClass;

		$return_object->id = $customer->id;
		$return_object->first_name = $customer->first_name;
		$return_object->last_name = $customer->last_name;
		$return_object->company_name = $customer->company_name;
		$return_object->display_name = $return_object->company_name
									 ? $return_object->company_name
									 : $return_object->first_name.' '.$return_object->last_name;
		$return_object->email = $customer->email;
		$return_object->phone_number = $customer->phone_number;
		$return_object->fax_number = $customer->fax_number;

		$this->_return_tax_liability_customer_element_cache[$customer->id] = $return_object;
		return $this->_return_tax_liability_customer_element_cache[$customer->id];
	}

	protected function _return_tax_liability_lines_array($form_lines)
	{
		$return_array = array();
		
		foreach( $form_lines as $form_line )
			$return_array[] = $this->_return_tax_liability_line_element($form_line);
		
		return $return_array;
	}

	/*
	---BEANSOBJSPEC---
	@object Beans_Tax_Liability_Line
	@description A line on an exempted customer sale.
	@attribute id INTEGER 
	@attribute account_name STRING The name of the #Beans_Account# tied to this line.
	@attribute tax_exempt BOOLEAN Whether or not this particular line is tax exempt.
	@attribute description STRING
	@attribute amount DECIMAL
	@attribute quantity INTEGER
	@attribute total DECIMAL
	---BEANSENDSPEC---
	 */
	private $_return_tax_liability_line_element_cache = array();
	protected function _return_tax_liability_line_element($form_line)
	{
		if( isset($this->_return_tax_liability_line_element_cache[$form_line->id]) )
			return $this->_return_tax_liability_line_element_cache[$form_line->id];

		if( get_class($form_line) != "Model_Form_Line" )
			throw new Exception("Invalid Form Line.");

		$return_object = (object)array();

		$return_object->id = $form_line->id;
		$return_object->account_name = $this->_get_account_name_by_id($form_line->account_id);
		$return_object->tax_exempt = $form_line->tax_exempt ? TRUE : FALSE;
		$return_object->description = $form_line->description;
		$return_object->amount = $form_line->amount;
		$return_object->quantity = $form_line->quantity;
		$return_object->total = $form_line->total;

		$this->_return_tax_liability_line_element_cache[$form_line->id] = $return_object;
		return $this->_return_tax_liability_line_element_cache[$form_line->id];
	}
}