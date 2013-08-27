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

class View_Vendors_Print_Checks extends View_PDF {
	
	protected $_max_report_lines = 17; 

	public function __construct()
	{
		parent::__construct();
	}

	public function render($name = NULL, $dest = NULL)
	{
		// Create Pages
		$this->_render_expenses();
		$this->_render_payments();
		$this->_render_taxpayments();

		return parent::render($name, $dest);
	}

	protected function _render_expenses()
	{
		if( ! isset($this->expenses) OR 
			! count($this->expenses) )
			return;

		foreach( $this->expenses as $expense )
		{
			$this->_render_expense($expense);
		}

		return;
	}

	protected function _render_payments()
	{
		if( ! isset($this->payments) OR 
			! count($this->payments) )
			return;

		foreach( $this->payments as $payment )
		{
			$this->_render_payment($payment);
		}

		return;
	}

	protected function _render_taxpayments()
	{
		if( ! isset($this->taxpayments) OR 
			! count($this->taxpayments) )
			return;

		foreach( $this->taxpayments as $taxpayment )
		{
			$this->_render_taxpayment($taxpayment);
		}

		return;
	}

	// Expense Code
	protected function _render_expense($expense)
	{
		$expense_array = $this->_expense_array($expense);

		$this->_pdf->AddPage();
		$this->_pdf->SetFont('Courier','',10);
		$this->_pdf->setMargins(2,0,0);
		$this->_pdf->setXY(2,3); // Starting Position.

		$this->_render_check($expense_array);

		// Skip 7 Lines
		$this->_skip_lines(7);

		$this->_pdf->SetFont('Courier','',9);
		$this->_render_expense_lines($expense_array);

		$this->_skip_lines(4);

		$this->_render_expense_lines($expense_array);
	}

	// Payment Code
	protected function _render_payment($payment)
	{
		$payment_array = $this->_payment_array($payment);

		$this->_pdf->AddPage();
		$this->_pdf->SetFont('Courier','',10);
		$this->_pdf->setMargins(2,0,0);
		$this->_pdf->setXY(2,3); // Starting Position.

		$this->_render_check($payment_array);

		// Skip 7 Lines
		$this->_skip_lines(7);

		$this->_pdf->SetFont('Courier','',9);
		$this->_render_payment_lines($payment_array);

		$this->_skip_lines(4);

		$this->_render_payment_lines($payment_array);
	}

	// Taxpayment Code
	protected function _render_taxpayment($taxpayment)
	{
		$taxpayment_array = $this->_taxpayment_array($taxpayment);

		$this->_pdf->AddPage();
		$this->_pdf->SetFont('Courier','',10);
		$this->_pdf->setMargins(2,0,0);
		$this->_pdf->setXY(2,3); // Starting Position.

		$this->_render_check($taxpayment_array);
		
		// Skip 7 Lines
		$this->_skip_lines(7);

		$this->_pdf->SetFont('Courier','',9);
		$this->_render_taxpayment_lines($taxpayment_array);

		$this->_skip_lines(4);

		$this->_render_taxpayment_lines($taxpayment_array);
		
	}

	protected function _render_expense_lines($expense)
	{
		$this->_pdf->Cell(2,10,'',0,0,'L');
		$this->_pdf->Cell(100,10,'Description',0,0,'L');
		$this->_pdf->Cell(34,10,'Quantity',0,0,'C');
		$this->_pdf->Cell(34,10,'Price',0,0,'R');
		$this->_pdf->Cell(34,10,'Amount Paid',0,0,'R');
		$this->_pdf->Ln(4);

		foreach( $expense['expense_lines'] as $expense_line )
		{
			$this->_pdf->Cell(2,10,'',0,0,'L');
			$this->_pdf->Cell(100,10,$expense_line['description'],0,0,'L');
			$this->_pdf->Cell(34,10,$expense_line['quantity'],0,0,'C');
			$this->_pdf->Cell(34,10,$expense_line['price_formatted'],0,0,'R');
			$this->_pdf->Cell(34,10,$expense_line['amount_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

		if( $expense['expense_lines_buffer'] )
			$this->_skip_lines(count($expense['expense_lines_buffer']));

		if( $expense['expense_lines_extra'] )
		{
			$this->_pdf->Cell(2,10,'',0,0,'L');
			$this->_pdf->Cell(100,10,$expense['expense_lines_extra']['linecount'].' Not Shown',0,0,'L');
			$this->_pdf->Cell(34,10,'',0,0,'C');
			$this->_pdf->Cell(34,10,'Amount Not Shown',0,0,'C');
			$this->_pdf->Cell(34,10,$expense['expense_lines_extra']['total_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

		if( $expense['expense_lines_total'] )
		{
			$this->_pdf->Cell(2,10,'',0,0,'L');
			$this->_pdf->Cell(100,10,'',0,0,'L');
			$this->_pdf->Cell(34,10,'',0,0,'C');
			$this->_pdf->Cell(34,10,'Total',0,0,'C');
			$this->_pdf->Cell(34,10,$expense['expense_lines_total']['total_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

	}

	protected function _render_payment_lines($payment)
	{
		// Headers
		$this->_pdf->Cell(34,10,'PO Number',0,0,'C');
		$this->_pdf->Cell(34,10,'PO Date',0,0,'C');
		$this->_pdf->Cell(34,10,'SO Number',0,0,'C');
		$this->_pdf->Cell(34,10,'Invoice Number',0,0,'C');
		$this->_pdf->Cell(34,10,'Invoice Date',0,0,'C');
		$this->_pdf->Cell(34,10,'Amount Paid',0,0,'R');
		$this->_pdf->Ln(4);

		foreach( $payment['payment_lines'] as $payment_line )
		{
			$this->_pdf->Cell(34,10,$payment_line['purchase_number'],0,0,'C');
			$this->_pdf->Cell(34,10,$payment_line['purchase_date'],0,0,'C');
			$this->_pdf->Cell(34,10,$payment_line['so_number'],0,0,'C');
			$this->_pdf->Cell(34,10,$payment_line['invoice_number'],0,0,'C');
			$this->_pdf->Cell(34,10,$payment_line['invoice_date'],0,0,'C');
			$this->_pdf->Cell(34,10,$payment_line['amount_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

		if( $payment['payment_lines_buffer'] )
			$this->_skip_lines(count($payment['payment_lines_buffer']));

		if( $payment['payment_lines_extra'] )
		{
			$this->_pdf->Cell(34,10,$payment['payment_lines_extra']['count'].' Not Shown',0,0,'C');
			$this->_pdf->Cell(34,10,'See Details',0,0,'C');
			$this->_pdf->Cell(34,10,'Included With',0,0,'C');
			$this->_pdf->Cell(34,10,'Payment',0,0,'C');
			$this->_pdf->Cell(34,10,'Amount Not Shown',0,0,'C');
			$this->_pdf->Cell(34,10,$payment['payment_lines_extra']['total_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

		if( $payment['payment_lines_total'] )
		{
			$this->_pdf->Cell(34,10,'',0,0,'C');
			$this->_pdf->Cell(34,10,'',0,0,'C');
			$this->_pdf->Cell(34,10,'',0,0,'C');
			$this->_pdf->Cell(34,10,'',0,0,'C');
			$this->_pdf->Cell(34,10,'Total',0,0,'C');
			$this->_pdf->Cell(34,10,$payment['payment_lines_total']['total_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}
		
	}

	protected function _render_taxpayment_lines($taxpayment)
	{
		$this->_pdf->Cell(2,10,'',0,0,'L');
		$this->_pdf->Cell(168,10,'Description',0,0,'L');
		$this->_pdf->Cell(34,10,'Amount Paid',0,0,'R');
		$this->_pdf->Ln(4);

		foreach( $taxpayment['taxpayment_lines'] as $taxpayment_line )
		{
			$this->_pdf->Cell(2,10,'',0,0,'L');
			$this->_pdf->Cell(168,10,$taxpayment_line['description'],0,0,'L');
			$this->_pdf->Cell(34,10,$taxpayment_line['amount_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

		if( $taxpayment['taxpayment_lines_buffer'] )
			$this->_skip_lines(count($taxpayment['taxpayment_lines_buffer']));

		if( $taxpayment['taxpayment_lines_extra'] )
		{
			$this->_pdf->Cell(2,10,'',0,0,'L');
			$this->_pdf->Cell(168,10,$taxpayment['taxpayment_lines_extra']['linecount'].' Not Shown',0,0,'L');
			$this->_pdf->Cell(34,10,$taxpayment['taxpayment_lines_extra']['total_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}

		if( $taxpayment['taxpayment_lines_total'] )
		{
			$this->_pdf->Cell(2,10,'',0,0,'L');
			$this->_pdf->Cell(168,10,'Total',0,0,'R');
			$this->_pdf->Cell(34,10,$taxpayment['taxpayment_lines_total']['total_formatted'],0,0,'R');
			$this->_pdf->Ln(4);
		}
	}

	protected function _render_check($data)
	{
		// Line 1,2,3,4 Empty
		$this->_skip_lines(4);

		// Date
		$this->_pdf->setX(176);
		$this->_pdf->Cell(0,10, $data['date'],0,0);
		$this->_pdf->Ln(4);

		// Skip 6 & 7
		$this->_skip_lines(2);

		// Vendor & Amount
		$this->_pdf->setX(23);
		$this->_pdf->Cell(140, 10, $data['vendor']['name'],0,0);
		$this->_pdf->setX(174);
		$this->_pdf->Cell(0, 10, $data['amount_formatted'],0,0);
		$this->_pdf->Ln(4);

		// Skip 9
		$this->_skip_lines(1);

		// Written Amount
		$this->_pdf->setX(7);
		$this->_pdf->Cell(0, 10, $data['amount_written'].' '.$data['amount_written_filler'],0,0);
		$this->_pdf->Ln(4);

		// Skip 11
		$this->_skip_lines(1);

		// Address
		$this->_pdf->setX(20);
		$this->_pdf->Cell(0,10, $data['vendor']['name'],0,0);
		$this->_pdf->Ln(4);

		$this->_pdf->setX(20);
		$this->_pdf->Cell(0,10, $data['remit_address']['address1'],0,0);
		$this->_pdf->Ln(4);

		if( $data['remit_address']['address2'] )
		{
			$this->_pdf->setX(20);
			$this->_pdf->Cell(0,10, $data['remit_address']['address2'],0,0);
			$this->_pdf->Ln(4);
		}

		$this->_pdf->setX(20);
		$this->_pdf->Cell(0,10, $data['remit_address']['city'].', '.$data['remit_address']['state'].' '.$data['remit_address']['zip'],0,0);
		$this->_pdf->Ln(4);

		$this->_pdf->setX(20);
		$this->_pdf->Cell(0,10, $data['remit_address']['country'],0,0);
		$this->_pdf->Ln(4);

		if( ! $data['remit_address']['address2'] )
		{
			$this->_skip_lines(1);
		}

	}

	// Tax Payment code















	
	public function expenses()
	{
		if( ! isset($this->expenses) OR 
			! count($this->expenses) )
			return FALSE;

		$return_array = array();

		foreach( $this->expenses as $expense )
		{
			$return_array[] = $this->_expense_array($expense);
			if( $this->_first )
			{
				$this->_first = FALSE;
				$return_array[0]['nopagebreak'] = TRUE;
			}
		}

		return $return_array;
	}

	public function payments()
	{
		if( ! isset($this->payments) OR 
			! count($this->payments) )
			return FALSE;

		$return_array = array();

		// for($a = 0; $a < 3; $a++ ) {
		foreach( $this->payments as $payment )
		{
			$return_array[] = $this->_payment_array($payment);
			if( $this->_first )
			{
				$this->_first = FALSE;
				$return_array[0]['nopagebreak'] = TRUE;
			}
		}
		// }
		return $return_array;
	}

	public function taxpayments()
	{
		if( ! isset($this->taxpayments) OR 
			! count($this->taxpayments) )
			return FALSE;

		$return_array = array();

		foreach( $this->taxpayments as $taxpayment )
		{
			$return_array[] = $this->_taxpayment_array($taxpayment);
			if( $this->_first )
			{
				$this->_first = FALSE;
				$return_array[0]['nopagebreak'] = TRUE;
			}
		}

		return $return_array;
	}

	protected function _expense_array($expense)
	{
		$total_written = $this->_convert_number_to_words($expense->total);
		$total_written_filler = '';
		for( $i = strlen($total_written); $i < 88; $i++ ) {
			$total_written_filler .= '-';
		}
		$expense_lines = $this->_expense_lines($expense);

		return array(
			'amount' => $expense->total,
			'amount_formatted' => number_format($expense->total,2,'.',','),
			'amount_written' => $total_written,
			'amount_written_filler' => $total_written_filler,
			'date' => $expense->date_created,
			'vendor' => $this->_vendor_array($expense->vendor),
			'remit_address' => $this->_remit_address_array($expense->remit_address),
			'expense_lines' => $this->_expense_lines_crop($expense_lines),
			'expense_lines_extra' => $this->_expense_lines_extra($expense_lines),
			'expense_lines_buffer' => $this->_expense_lines_buffer($expense_lines),
			'expense_lines_total' => $this->_expense_lines_total($expense_lines),
		);
	}

	protected function _expense_lines($expense)
	{
		$return_array = array();

		foreach( $expense->lines as $line )
			$return_array[] = $this->_expense_line_array($line);

		// return array_splice($return_array, 0, 10);

		return $return_array;
	}

	protected function _expense_line_array($expense_line)
	{
		return array(
			'description' => $expense_line->description,
			'quantity' => $expense_line->quantity,
			'price' => $expense_line->amount,
			'price_formatted' => ( $expense_line->amount < 0 ? '-' : '' ).number_format(abs($expense_line->amount), 2, '.', ','),
			'amount' => $expense_line->total,
			'amount_formatted' => ( $expense_line->total < 0 ? '-' : '' ).number_format(abs($expense_line->total), 2, '.', ','),
		);
	}

	protected function _expense_lines_extra($expense_lines)
	{
		if( count($expense_lines) <= $this->_max_report_lines ) {
			return FALSE;
		}

		$total = 0.00;
		$linecount = 0;
		$quantitytotal = 0;

		foreach( array_splice($expense_lines,( $this->_max_report_lines - 1 )) as $line )
		{
			$total += $line['amount'];
			$linecount++;
			$quantitytotal += $line['quantity'];
		}

		return array(
			'linecount' => $linecount,
			'quantitytotal' => $quantitytotal,
			'total' => $total,
			'total_formatted' => ( $total < 0 ? '-' : '' ).number_format($total,2,'.',','), 
		);
	}

	protected function _expense_lines_buffer($expense_lines)
	{
		if( count($expense_lines) > $this->_max_report_lines ) {
			return FALSE;
		}

		$return_array = array();
		for( $i = 0; $i < ( $this->_max_report_lines - count($expense_lines) ) ; $i++ )
			$return_array[] = $i;
		
		return $return_array;
	}

	protected function _expense_lines_total($expense_lines)
	{
		$total = 0.00;
		$count = 0;

		$total = 0.00;
		$linecount = 0;
		$quantitytotal = 0;

		foreach( $expense_lines as $line )
		{
			$total += $line['amount'];
			$linecount++;
			$quantitytotal += $line['quantity'];
		}

		return array(
			'linecount' => $linecount,
			'quantitytotal' => $quantitytotal,
			'total' => $total,
			'total_formatted' => ( $total < 0 ? '-' : '' ).number_format($total,2,'.',','), 
		);
	}

	protected function _expense_lines_crop($expense_lines)
	{
		if( count($expense_lines) > $this->_max_report_lines ) {
			return array_splice($expense_lines,0,( $this->_max_report_lines - 1 ));
		}

		return $expense_lines;
	}

	protected function _payment_array($payment)
	{
		$amount_written = $this->_convert_number_to_words($payment->amount);
		$amount_written_filler = '';
		for( $i = strlen($amount_written); $i < 88; $i++ ) {
			$amount_written_filler .= '-';
		}
		$payment_lines = $this->_payment_lines($payment);
		return array(
			'amount' => $payment->amount,
			'amount_formatted' => number_format($payment->amount,2,'.',','),
			'amount_written' => $amount_written,
			'amount_written_filler' => $amount_written_filler,
			'date' => $payment->date,
			'vendor' => $this->_vendor_array($payment->vendor),
			'remit_address' => $this->_remit_address_array($payment->remit_address),
			'payment_lines' => $this->_payment_lines_crop($payment_lines),
			'payment_lines_extra' => $this->_payment_lines_extra($payment_lines),
			'payment_lines_buffer' => $this->_payment_lines_buffer($payment_lines),
			'payment_lines_total' => $this->_payment_lines_total($payment_lines),
		);
	}

	protected function _payment_lines($payment)
	{
		$return_array = array();

		foreach( $payment->purchase_payments as $purchase_payment )
			$return_array[] = $this->_payment_line_array($purchase_payment);

		return $return_array;
	}

	protected function _payment_line_array($purchase_payment)
	{
		return array(
			'purchase_number'	=> $purchase_payment->purchase->purchase_number,
			'so_number'			=> $purchase_payment->purchase->so_number,
			'invoice_number'	=> $purchase_payment->purchase->invoice_number,
			'purchase_date'		=> $purchase_payment->purchase->date_created,
			'invoice_date'		=> $purchase_payment->purchase->date_billed,
			'amount'			=> $purchase_payment->amount,
			'amount_formatted' 	=> ( $purchase_payment->amount < 0 ? '-' : '' ).number_format(abs($purchase_payment->amount),2,'.',','), 
		);
	}

	protected function _payment_lines_crop($payment_lines)
	{
		if( count($payment_lines) > $this->_max_report_lines ) {
			return array_splice($payment_lines,0,( $this->_max_report_lines - 1 ));
		}

		return $payment_lines;
	}

	protected function _payment_lines_extra($payment_lines)
	{
		if( count($payment_lines) <= $this->_max_report_lines ) {
			return FALSE;
		}

		$total = 0.00;
		$count = 0;

		foreach( array_splice($payment_lines,( $this->_max_report_lines - 1 )) as $line )
		{
			$total += $line['amount'];
			$count++;
		}

		return array(
			'count' => $count,
			'total' => $total,
			'total_formatted' => ( $total < 0 ? '-' : '' ).number_format(abs($total),2,'.',','), 
		);
	}

	protected function _payment_lines_total($payment_lines)
	{
		$total = 0.00;
		$count = 0;

		foreach( $payment_lines as $line )
		{
			$total += $line['amount'];
			$count++;
		}

		return array(
			'count' => $count,
			'total' => $total,
			'total_formatted' => ( $total < 0 ? '-' : '' ).number_format(abs($total),2,'.',','), 
		);
	}

	protected function _payment_lines_buffer($payment_lines)
	{
		if( count($payment_lines) >= $this->_max_report_lines ) {
			return FALSE;
		}

		$return_array = array();
		for( $i = 0; $i < ( $this->_max_report_lines - count($payment_lines) ) ; $i++ )
			$return_array[] = $i;
		
		return $return_array;
	}

	protected function _vendor_array($vendor)
	{
		return array(
			'name' => ( $vendor->company_name )
				   ? $vendor->company_name
				   : $vendor->first_name.' '.$vendor->last_name,
		);
	}

	protected function _remit_address_array($address)
	{
		if( ! $address )
			return array(
				'first_name' 	=> FALSE,
				'last_name' 	=> FALSE,
				'company_name'	=> FALSE,
				'address1'		=> FALSE,
				'address2'		=> FALSE,
				'city'			=> FALSE,
				'state'			=> FALSE,
				'zip'			=> FALSE,
				'country'		=> FALSE,
			);

		return array(
			'first_name' 	=> ( isset($address->first_name) ? $address->first_name : FALSE ),
			'last_name' 	=> ( isset($address->last_name) ? $address->last_name : FALSE ),
			'company_name' 	=> ( isset($address->company_name) ? $address->company_name : FALSE ),
			'address1'		=> $address->address1,
			'address2'		=> $address->address2,
			'city'			=> $address->city,
			'state'			=> $address->state,
			'zip'			=> $address->zip,
			'country'		=> $this->_country_name($address->country),
		);
	}

	protected function _taxpayment_array($taxpayment)
	{
		$total_written = $this->_convert_number_to_words($taxpayment->amount);
		$total_written_filler = '';
		for( $i = strlen($total_written); $i < 88; $i++ ) {
			$total_written_filler .= '-';
		}
		$taxpayment_lines = $this->_taxpayment_lines($taxpayment);

		return array(
			'amount' => $taxpayment->amount,
			'amount_formatted' => number_format($taxpayment->amount,2,'.',','),
			'amount_written' => $total_written,
			'amount_written_filler' => $total_written_filler,
			'date' => $taxpayment->date,
			'vendor' => $this->_tax_array($taxpayment->tax),
			'remit_address' => $this->_remit_address_array($taxpayment->tax),
			'taxpayment_lines' => $this->_taxpayment_lines_crop($taxpayment_lines),
			'taxpayment_lines_extra' => $this->_taxpayment_lines_extra($taxpayment_lines),
			'taxpayment_lines_buffer' => $this->_taxpayment_lines_buffer($taxpayment_lines),
			'taxpayment_lines_total' => $this->_taxpayment_lines_total($taxpayment_lines),
		);
	}

	protected function _tax_array($tax)
	{
		return array(
			'name' => $tax->authority,
			'authority' => $tax->authority,
			'license' => $tax->license,
			'address1' => $tax->address1,
			'address2' => $tax->address2,
			'city' => $tax->city,
			'state' => $tax->state,
			'zip' => $tax->zip,
			'country' => $tax->country,
		);
	}

	protected function _taxpayment_lines($taxpayment)
	{
		// We return a single line - but this could have multiple lines...
		$return_array = array();

		$return_array[] = array(
			'description' => $taxpayment->tax->name." remittance for ".$taxpayment->date_start.' - '.$taxpayment->date_end,
			'amount' => $taxpayment->amount,
			'amount_formatted' => ( $taxpayment->amount < 0 ? '-' : '' ).number_format($taxpayment->amount, 2, '.', ','),
		);

		return $return_array;
	}

	protected function _taxpayment_lines_crop($taxpayment_lines)
	{
		if( count($taxpayment_lines) > $this->_max_report_lines ) {
			return array_splice($taxpayment_lines,0,( $this->_max_report_lines - 1 ));
		}

		return $taxpayment_lines;
	}

	protected function _taxpayment_lines_extra($taxpayment_lines)
	{
		if( count($taxpayment_lines) <= $this->_max_report_lines ) {
			return FALSE;
		}

		$total = 0.00;
		$count = 0;

		foreach( array_splice($taxpayment_lines,( $this->_max_report_lines - 1 )) as $line )
		{
			$total += $line['amount'];
			$count++;
		}

		return array(
			'count' => $count,
			'total' => $total,
			'total_formatted' => ( $total < 0 ? '-' : '' ).number_format($total,2,'.',','), 
		);
	}
	
	protected function _taxpayment_lines_buffer($taxpayment_lines)
	{
		if( count($taxpayment_lines) >= $this->_max_report_lines ) {
			return FALSE;
		}

		$return_array = array();
		for( $i = 0; $i < ( $this->_max_report_lines - count($taxpayment_lines) ) ; $i++ )
			$return_array[] = $i;
		
		return $return_array;
	}

	protected function _taxpayment_lines_total($taxpayment_lines)
	{
		$total = 0.00;
		$count = 0;

		foreach( $taxpayment_lines as $line )
		{
			$total += $line['amount'];
			$count++;
		}

		return array(
			'count' => $count,
			'total' => $total,
			'total_formatted' => ( $total < 0 ? '-' : '' ).number_format($total,2,'.',','), 
		);
	}

	// Source: http://www.karlrixon.co.uk/writing/convert-numbers-to-words-with-php/
	// Rewrite ?
	protected function _convert_number_to_words($number) {
		$hyphen      = '-';
		$conjunction = ' and ';
		$separator   = ', ';
		$negative    = 'negative ';
		$decimal     = ' point ';
		$dictionary  = array(
		    0                   => 'zero',
		    1                   => 'one',
		    2                   => 'two',
		    3                   => 'three',
		    4                   => 'four',
		    5                   => 'five',
		    6                   => 'six',
		    7                   => 'seven',
		    8                   => 'eight',
		    9                   => 'nine',
		    10                  => 'ten',
		    11                  => 'eleven',
		    12                  => 'twelve',
		    13                  => 'thirteen',
		    14                  => 'fourteen',
		    15                  => 'fifteen',
		    16                  => 'sixteen',
		    17                  => 'seventeen',
		    18                  => 'eighteen',
		    19                  => 'nineteen',
		    20                  => 'twenty',
		    30                  => 'thirty',
		    40                  => 'fourty',
		    50                  => 'fifty',
		    60                  => 'sixty',
		    70                  => 'seventy',
		    80                  => 'eighty',
		    90                  => 'ninety',
		    100                 => 'hundred',
		    1000                => 'thousand',
		    1000000             => 'million',
		    1000000000          => 'billion',
		    1000000000000       => 'trillion',
		    1000000000000000    => 'quadrillion',
		    1000000000000000000 => 'quintillion'
		);

		if (!is_numeric($number)) {
		    return false;
		}

		if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
		    // overflow
		    trigger_error(
		        'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
		        E_USER_WARNING
		    );
		    return false;
		}

		if ($number < 0) {
		    return $negative . $this->_convert_number_to_words(abs($number));
		}

		$string = $fraction = null;

		if (strpos($number, '.') !== false) {
		    list($number, $fraction) = explode('.', $number);
		}

		switch (true) {
		    case $number < 21:
		        $string = $dictionary[$number];
		        break;
		    case $number < 100:
		        $tens   = ((int) ($number / 10)) * 10;
		        $units  = $number % 10;
		        $string = $dictionary[$tens];
		        if ($units) {
		            $string .= $hyphen . $dictionary[$units];
		        }
		        break;
		    case $number < 1000:
		        $hundreds  = $number / 100;
		        $remainder = $number % 100;
		        $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
		        if ($remainder) {
		            $string .= $conjunction . $this->_convert_number_to_words($remainder);
		        }
		        break;
		    default:
		        $baseUnit = pow(1000, floor(log($number, 1000)));
		        $numBaseUnits = (int) ($number / $baseUnit);
		        $remainder = $number % $baseUnit;
		        $string = $this->_convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
		        if ($remainder) {
		            $string .= $remainder < 100 ? $conjunction : $separator;
		            $string .= $this->_convert_number_to_words($remainder);
		        }
		        break;
		}

		if (null !== $fraction && is_numeric($fraction)) {
		    $string .= $decimal;
		    $words = array();
		    foreach (str_split((string) $fraction) as $number) {
		        $words[] = $dictionary[$number];
		    }
		    $string .= implode(' ', $words);
		}

		return $string;
		}


}