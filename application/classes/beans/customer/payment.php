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

class Beans_Customer_Payment extends Beans_Customer {

	protected $_auth_role_perm = "customer_payment_read";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _calculate_deferred_payment($sale_payment_amount, $sale_paid, $sale_line_total, $sale_tax_total)
	{
		$income_transfer_amount = 0.00;
		$tax_transfer_amount = 0.00;

		if( $sale_line_total >= 0 && $sale_payment_amount >= 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid > $sale_line_total )
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid > $sale_tax_total )
				$sale_tax_paid = $sale_tax_total;

			$sale_line_paid = $this->_beans_round( $sale_line_paid + ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			// Deferred Income Step 1 ( Per Line Total Unpaid )
			if( $sale_line_paid < $sale_line_total )
				$income_transfer_amount = $this->_beans_round( $sale_line_total - $sale_line_paid );

			if( $income_transfer_amount <= 0 )
				$income_transfer_amount = 0.00;
			else if( $income_transfer_amount >= $sale_payment_amount )
				$income_transfer_amount = $sale_payment_amount;

			// Deferred Tax ( Per Tax Total Unpaid )
			if( $sale_tax_paid < $sale_tax_total )
				$tax_transfer_amount = $this->_beans_round( $sale_tax_total - $sale_tax_paid );

			if( $tax_transfer_amount <= 0 )
				$tax_transfer_amount = 0.00;
			else if( $tax_transfer_amount >= ( $sale_payment_amount - $income_transfer_amount ) )
				$tax_transfer_amount = $this->_beans_round( $sale_payment_amount - $income_transfer_amount );

			// Deferred Income Step 2 ( Remaining Payment Amount )
			if( ( $income_transfer_amount + $tax_transfer_amount ) < $sale_payment_amount )
				$income_transfer_amount = $this->_beans_round( $income_transfer_amount + ( $sale_payment_amount - ( $income_transfer_amount + $tax_transfer_amount ) ) );

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		if( $sale_line_total >= 0 && $sale_payment_amount < 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid > $sale_line_total )
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid > $sale_tax_total )
				$sale_tax_paid = $sale_tax_total;

			$sale_line_paid = $this->_beans_round( $sale_line_paid + ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			// Deferred Tax First
			if( $sale_tax_paid > 0 )
				$tax_transfer_amount = ( -1 * $sale_tax_paid );

			if( $tax_transfer_amount >= 0 ) 
				$tax_transfer_amount = 0.00;
			else if( $tax_transfer_amount <= $sale_payment_amount ) 
				$tax_transfer_amount = $sale_payment_amount;

			// Deferred Income = Remainder
			$income_transfer_amount = $this->_beans_round( $sale_payment_amount - $tax_transfer_amount );

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		if( $sale_line_total < 0 && $sale_payment_amount < 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid < $sale_line_total ) // Sign Flip
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid < $sale_tax_total ) // Sign Flip
				$sale_tax_paid = $sale_tax_total;

			// Operation Flip 
			$sale_line_paid = $this->_beans_round( $sale_line_paid - ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			// Deferred Income Step 1 ( Per Line Total Unpaid )
			if( $sale_line_paid > $sale_line_total ) // Sign Flip
				$income_transfer_amount = $this->_beans_round( $sale_line_total - $sale_line_paid );

			if( $income_transfer_amount >= 0 ) // Sign Flip
				$income_transfer_amount = 0.00;
			else if( $income_transfer_amount <= $sale_payment_amount ) // Sign Flip
				$income_transfer_amount = $sale_payment_amount;

			// Deferred Tax ( Per Tax Total Unpaid )
			if( $sale_tax_paid > $sale_tax_total ) // Sign Flip
				$tax_transfer_amount = $this->_beans_round( $sale_tax_total - $sale_tax_paid );

			if( $tax_transfer_amount >= 0 ) // Sign Flip
				$tax_transfer_amount = 0.00;
			else if( $tax_transfer_amount <= ( $sale_payment_amount - $income_transfer_amount ) ) // Sign Flip
				$tax_transfer_amount = $this->_beans_round( $sale_payment_amount - $income_transfer_amount );

			// Deferred Income Step 2 ( Remaining Payment Amount )
			if( ( $income_transfer_amount + $tax_transfer_amount ) > $sale_payment_amount ) // Sign Flip & Operation Flip Below
				$income_transfer_amount = $this->_beans_round( $income_transfer_amount + ( $sale_payment_amount - ( $income_transfer_amount + $tax_transfer_amount ) ) );

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		if( $sale_line_total < 0 && $sale_payment_amount >= 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid < $sale_line_total ) // Sign Flip
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid < $sale_tax_total ) // Sign Flip
				$sale_tax_paid = $sale_tax_total;

			// Operation Flip 
			$sale_line_paid = $this->_beans_round( $sale_line_paid - ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			// Deferred Tax First
			if( $sale_tax_paid < 0 )
				$tax_transfer_amount = ( -1 * $sale_tax_paid );

			if( $tax_transfer_amount <= 0 ) // Sign Flip
				$tax_transfer_amount = 0.00;
			else if( $tax_transfer_amount >= $sale_payment_amount ) 
				$tax_transfer_amount = $sale_payment_amount;

			// Deferred Income = Remainder 
			$income_transfer_amount = $this->_beans_round( $sale_payment_amount - $tax_transfer_amount );

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		throw new Exception("Invalid payment information: Uncaught Payment Combination.");
	}

}