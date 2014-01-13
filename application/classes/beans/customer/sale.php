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

class Beans_Customer_Sale extends Beans_Customer {

	protected $_auth_role_perm = "customer_sale_read";

	public function __construct($data = NULL)
	{
		parent::__construct($data);
	}
	
	protected function _calculate_deferred_invoice($sale_paid, $sale_line_total, $sale_tax_total)
	{

		$income_transfer_amount = 0.00;
		$tax_transfer_amount = 0.00;

		if( $sale_line_total >= 0 && $sale_paid >= 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid > $sale_line_total )
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid > $sale_tax_total )
				$sale_tax_paid = $sale_tax_total;

			$sale_line_paid = $this->_beans_round( $sale_line_paid + ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			$income_transfer_amount = $sale_line_paid;
			$tax_transfer_amount = $sale_tax_paid;

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		if( $sale_line_total >= 0 && $sale_paid < 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid > $sale_line_total )
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid > $sale_tax_total )
				$sale_tax_paid = $sale_tax_total;

			$sale_line_paid = $this->_beans_round( $sale_line_paid + ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			$income_transfer_amount = $sale_line_paid;
			$tax_transfer_amount = $sale_tax_paid;

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		if( $sale_line_total < 0 && $sale_paid <= 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid < $sale_line_total ) // Sign Flip
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid < $sale_tax_total ) // Sign Flip
				$sale_tax_paid = $sale_tax_total;

			// Operation Flip 
			$sale_line_paid = $this->_beans_round( $sale_line_paid - ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			$income_transfer_amount = $sale_line_paid;
			$tax_transfer_amount = $sale_tax_paid;

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		if( $sale_line_total < 0 && $sale_paid > 0 )
		{
			$sale_line_paid = $sale_paid;

			if( $sale_line_paid < $sale_line_total ) // Sign Flip
				$sale_line_paid = $sale_line_total;

			$sale_tax_paid = $this->_beans_round( $sale_paid - $sale_line_paid );

			if( $sale_tax_paid < $sale_tax_total ) // Sign Flip
				$sale_tax_paid = $sale_tax_total;

			// Operation Flip 
			$sale_line_paid = $this->_beans_round( $sale_line_paid - ( $sale_paid - ( $sale_line_paid + $sale_tax_paid ) ) );

			$income_transfer_amount = $sale_line_paid;
			$tax_transfer_amount = $sale_tax_paid;

			return (object)array(
				'income_transfer_amount' => $income_transfer_amount,
				'tax_transfer_amount' => $tax_transfer_amount
			);
		}

		throw new Exception("Invalid invoice information: Uncaught Invoice Combination.");
	}

}