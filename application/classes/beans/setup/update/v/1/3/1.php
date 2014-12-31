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

class Beans_Setup_Update_V_1_3_1 extends Beans_Setup_Update_V {

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _execute()
	{
		$fye_transaction = ORM::Factory('transaction')
			->where('close_books','IS NOT',NULL)
			->order_by('close_books','desc')
			->find();

		$fye_date = date("Y-m-d",0);

		if( $fye_transaction->loaded() )
			$fye_date = date("Y-m-t",strtotime(substr($fye_transaction->close_books,0,7).'-01'));

		// Update form_taxes
		// 		- Add form_line_amount
		$form_taxes = ORM::Factory('form_tax')
			->find_all();

		foreach( $form_taxes as $form_tax )
		{
			$form_tax->form_line_amount = $form_tax->form->amount;
			$form_tax->save();
		}

		unset($form_taxes);

		// Update form_lines
		// 		- Add tax_exempt to forms that had tax
		$forms = ORM::Factory('form')
			->where('type','=','sale')
			->where('amount','<',DB::expr('total'))
			->where('date_created','>',$fye_date)
			->find_all();

		foreach( $forms as $form )
		{
			$form_lines = $form->form_lines->find_all();
			
			foreach( $form_lines as $form_line )
			{
				$form_line_taxes_exist = DB::Query(
					Database::SELECT, 
					' SELECT COUNT(id) as exists '.
					' FROM form_line_taxes '.
					' WHERE form_line_id = '.$form_line->id.' '
				)->execute()->as_array();

				if( $form_line_taxes_exist[0]['exists'] == "0" )
				{
					$form_line->tax_exempt = TRUE;
					$form_line->save();
				}
			}

			unset($form_lines);
		}

		unset($forms);

		// Create tax_items for sales.
		// 		- Any order that has been invoiced = invoice
		// 		- Any order that has been invoiced and cancelled = invoice
		// 		- Any order with a date_created in a tax payment date range ( two entries - for a 0 balance )
		
		$forms = ORM::Factory('form')
			->where('type','=','sale')
			->where('amount','<',DB::expr('total'))
			->where('date_created','>',$fye_date)
			->find_all();

		foreach( $forms as $form )
		{
			// If the form has either been billed or a payment was remitted during it's date_created period
			if( $form->date_billed ||
				ORM::Factory('tax_payment')->where('date_end','>',$form->date_created)->count_all() )
			{
				// Couple of use cases:
				// a) Form was not billed, but taxes were remitted.
				if( ! $form->date_billed )
				{
					$form->date_billed = $form->date_created;
					$date_cancelled = $form->date_cancelled;
					$form->date_cancelled = NULL;
					$this->_update_form_tax_items($form, 'invoice');

					$form->date_cancelled = $date_cancelled;
					if( ! $form->date_cancelled )
					{
						$form->date_cancelled = $form->date_billed;
						$this->_update_form_tax_items($form, 'invoice');
					}
					else
					{
						$this->_update_form_tax_items($form, 'refund');
					}
				}
				// b) Form was billed
				else
				{
					$date_cancelled = $form->date_cancelled;
					$form->date_cancelled = NULL;
					$form->date_billed = $form->date_created;
					$this->_update_form_tax_items($form, 'invoice');

					if( $date_cancelled )
					{
						$form->date_cancelled = $date_cancelled;
						$this->_update_form_tax_items($form, 'refund');
					}
				}
			}
		}

		unset($forms);

		// Update tax_payments for tax_items and include new fields:
		// 		- writeoff_amount
		// 		- invoiced_line_amount
		// 		- invoiced_line_taxable_amount
		// 		- invoiced_amount
		// 		- refunded_line_amount
		// 		- refunded_line_taxable_amount
		// 		- refunded_amount
		// 		- net_line_amount
		// 		- net_line_taxable_amount
		// 		- net_amount
		

		$tax_payments = ORM::Factory('tax_payment')
			->order_by(array(
				'tax_id' => 'asc',
				'date' => 'asc',
			))
			->find_all();

		// Not the most efficient loop, but it's easy to understand.
		foreach( $tax_payments as $tax_payment )
		{
			$unpaid_tax_items = ORM::Factory('tax_item')
				->where('tax_id','=',$tax_payment->tax_id)
				->where('tax_payment_id','IS',NULL)
				->where('date','<=',$tax_payment->date_end)
				->order_by('date','asc')
				->find_all();

			$included_tax_items = array();
			$included_tax_total = 0.00;
			$locked = FALSE;

			foreach( $unpaid_tax_items as $unpaid_tax_item )
			{
				if( ! $locked &&
					$this->_beans_round( $included_tax_total + $unpaid_tax_item->total ) <= $tax_payment->amount )
				{
					$included_tax_items[] = $unpaid_tax_item;
					$included_tax_total = $this->_beans_round( $included_tax_total + $unpaid_tax_item->total );
				}
				else
				{
					$locked = TRUE;
				}
			}

			$tax_payment->invoiced_line_amount = 0.00;
			$tax_payment->invoiced_line_taxable_amount = 0.00;
			$tax_payment->invoiced_amount = 0.00;
			$tax_payment->refunded_line_amount = 0.00;
			$tax_payment->refunded_line_taxable_amount = 0.00;
			$tax_payment->refunded_amount = 0.00;
			$tax_payment->net_line_amount = 0.00;
			$tax_payment->net_line_taxable_amount = 0.00;
			$tax_payment->net_amount = 0.00;

			foreach( $included_tax_items as $included_tax_item )
			{
				if( $included_tax_item->type == "invoice" )
				{
					$tax_payment->invoiced_line_amount = $this->_beans_round(
						$tax_payment->invoiced_line_amount +
						$included_tax_item->form_line_amount
					);
					$tax_payment->invoiced_line_taxable_amount = $this->_beans_round(
						$tax_payment->invoiced_line_taxable_amount +
						$included_tax_item->form_line_taxable_amount
					);
					$tax_payment->invoiced_amount = $this->_beans_round(
						$tax_payment->invoiced_amount +
						$included_tax_item->total
					);
				}
				else
				{
					$tax_payment->refunded_line_amount = $this->_beans_round(
						$tax_payment->refunded_line_amount +
						$included_tax_item->form_line_amount
					);
					$tax_payment->refunded_line_taxable_amount = $this->_beans_round(
						$tax_payment->refunded_line_taxable_amount +
						$included_tax_item->form_line_taxable_amount
					);
					$tax_payment->refunded_amount = $this->_beans_round(
						$tax_payment->refunded_amount +
						$included_tax_item->total
					);
				}
				$tax_payment->net_line_amount = $this->_beans_round(
					$tax_payment->net_line_amount +
					$included_tax_item->form_line_amount
				);
				$tax_payment->net_line_taxable_amount = $this->_beans_round(
					$tax_payment->net_line_taxable_amount +
					$included_tax_item->form_line_taxable_amount
				);
				$tax_payment->net_amount = $this->_beans_round(
					$tax_payment->net_amount +
					$included_tax_item->total
				);
				
				$included_tax_item->tax_payment_id = $tax_payment->id;
				$included_tax_item->save();
			}

			$writeoff_transaction = NULL;
			foreach( $tax_payment->transaction->account_transactions->find_all() as $account_transaction )
			{
				if( $account_transaction->writeoff )
					$writeoff_transaction = $account_transaction;
			}
			
			if( $writeoff_transaction )
			{
				$tax_payment->writeoff_amount = $writeoff_transaction->amount;
			}

			$tax_payment->save();
		}

		unset($tax_payments);

		// Update total and balance on all taxes.
		
		$taxes = ORM::Factory('tax')
			->find_all();

		foreach( $taxes as $tax )
		{
			$tax_amounts = DB::Query(
				Database::SELECT,
				' SELECT '.
				' IFNULL(SUM(total),0.00) as total, '.
				' IFNULL(SUM(balance),0.00) as balance '.
				' FROM tax_items '.
				' WHERE '.
				' tax_id = '.$tax->id.' '
			)->execute()->as_array();

			$tax->balance = $tax_amounts[0]['balance'];
			$tax->total = $tax_amounts[0]['total'];

			$tax->save();
		}

		// When we're all done - we can remove the form_line_taxes table.
		$this->_db_remove_table('form_line_taxes');

		return (object)array();
	}

	// Copied from Beans_Customer 
	protected function _update_form_tax_items($form_id, $action = NULL)
	{
		if( ! $action ||
			! in_array($action, array('invoice','refund')) )
			throw new Exception("Invalid or missing action provided: must be invoice or refund.");

		// Get a list of all taxes that have ever affected this form
		$tax_ids = array();

		// Taxes currently on the form
		$form_taxes_tax_ids = DB::query(
			Database::SELECT, 
			'SELECT DISTINCT(tax_id) AS tax_id'.
			' FROM form_taxes WHERE'.
			' form_id = '.$form_id
		)->execute()->as_array();

		foreach( $form_taxes_tax_ids as $form_taxes_tax_id )
		{
			if( ! in_array($form_taxes_tax_id['tax_id'], $tax_ids) )
				$tax_ids[] = $form_taxes_tax_id['tax_id'];
		}

		$tax_items_tax_ids = DB::query(
			Database::SELECT,
			'SELECT DISTINCT(tax_id) AS tax_id '.
			'FROM tax_items WHERE '.
			'form_id = '.$form_id.' '
		)->execute()->as_array();

		foreach( $tax_items_tax_ids as $tax_items_tax_id )
		{
			if( ! in_array($tax_items_tax_id['tax_id'], $tax_ids) )
				$tax_ids[] = $tax_items_tax_id['tax_id'];
		}

		foreach( $tax_ids as $tax_id )
		{
			$tax_item = $this->_create_tax_item($form_id, $tax_id, $action);

			if( $tax_item )
			{
				DB::Query(
					NULL,
					'UPDATE taxes SET '.
					'total = total + '.$tax_item->total.' '.
					', balance = balance + '.$tax_item->balance.' '.
					'WHERE id = '.$tax_item->tax_id.' '
				)->execute();
			}
		}
		
		return;
	}

	private function _create_tax_item($form_id, $tax_id, $action)
	{
		$form = ORM::Factory('form', $form_id);
		$tax = ORM::Factory('tax', $tax_id);

		// If this form isn't cancelled, we want to update every tax_item
		// associated to it to reflect the current date_billed as date
		if( ! $form->date_cancelled )
		{
			DB::Query(
				NULL,
				'UPDATE tax_items SET '.
				'date = DATE("'.$form->date_billed.'") '.
				'WHERE '.
				'form_id = '.$form->id.' '
			)->execute();
		}

		// Our general form for generating a tax_item value is to take the difference of
		// the current value and the sum of values stored in tax_items currently.
		// So first we get the current value for each field -
		// Note - if the form is cancelled we force a value of 0.00

		$current_form_line_taxable_amount = 0.00;
		$current_form_line_amount = 0.00;
		$current_total = 0.00;

		if( ! $form->date_cancelled )
		{
			$current_values = DB::Query(
				Database::SELECT,
				'SELECT '.
				'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
				'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
				'IFNULL(SUM(total),0.00) as total '.
				'FROM form_taxes '.
				'WHERE '.
				'form_id = '.$form_id.' AND '.
				'tax_id = '.$tax_id.' '
			)->execute()->as_array();

			$current_form_line_taxable_amount = $current_values[0]['form_line_taxable_amount'];
			$current_form_line_amount = $current_values[0]['form_line_amount'];
			$current_total = $current_values[0]['total'];
		}

		$sum_values = DB::Query(
			Database::SELECT,
			'SELECT '.
			'IFNULL(SUM(form_line_taxable_amount),0.00) as form_line_taxable_amount, '.
			'IFNULL(SUM(form_line_amount),0.00) as form_line_amount, '.
			'IFNULL(SUM(total),0.00) as total '.
			'FROM tax_items '.
			'WHERE '.
			'form_id = '.$form_id.' AND '.
			'tax_id = '.$tax_id.' '
		)->execute()->as_array();

		$sum_form_line_taxable_amount = $sum_values[0]['form_line_taxable_amount'];
		$sum_form_line_amount = $sum_values[0]['form_line_amount'];
		$sum_total = $sum_values[0]['total'];

		$tax_item = ORM::Factory('tax_item');

		$tax_item->tax_id = $tax_id;
		$tax_item->form_id = $form_id;
		$tax_item->tax_payment_id = NULL;
		$tax_item->tax_percent = $tax->percent;

		$tax_item->form_line_amount = $this->_beans_round(
			$current_form_line_amount -
			$sum_form_line_amount
		);
		$tax_item->form_line_taxable_amount = $this->_beans_round(
			$current_form_line_taxable_amount -
			$sum_form_line_taxable_amount
		);
		$tax_item->total = $this->_beans_round(
			$current_total -
			$sum_total
		);

		// If no taxable amount changes have been made - we can just exit.
		//if( $tax_item->form_line_taxable_amount === 0.00 )
		//	return NULL;

		$tax_item->balance = ( -1 * $tax_item->total );
		$tax_item->type = $action;
		
		if( $form->date_cancelled )
			$tax_item->date = $form->date_cancelled;
		else
			$tax_item->date = $form->date_billed;
		
		$tax_item->save();
		
		return $tax_item;
	}
}

