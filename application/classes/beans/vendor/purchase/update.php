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
@action Beans_Vendor_Purchase_Create
@description Create a new vendor purchase.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Vendor_Purchase# to update.
@required account_id INTEGER The ID for the AP #Beans_Account# this purchase is being added to.
@required date_created STRING The date of the purchase in YYYY-MM-DD format.
@optional date_billed STRING The bill date in YYYY-MM-DD for the sale; adding this will automatically convert it to an invoice.
@optional invoice_number STRING An invoice number to be tied to the purchase.
@optional purchase_number STRING An purchase number to reference this purchase.  If none is created, it will auto-generate.
@optional so_number STRING An SO number to reference this purchase.
@optional quote_number STRING A quote number to reference this purchase.
@optional remit_address_id INTEGER The ID of the #Beans_Vendor_Address# to remit payment to.
@optional shipping_address_id INTEGER The ID of the #Beans_Vendor_Address_Shipping# to ship to.
@required lines ARRAY An array of objects representing line items for the purchase.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity DECIMAL The number of units (up to three decimal places).
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the cost of the purchase towards.
@returns purchase OBJECT The resulting #Beans_Vendor_Purchase#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Update extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";
	
	protected $_attributes_only;
	protected $_address_only;
	protected $_data;
	protected $_id;
	protected $_purchase;
	protected $_purchase_lines;
	
	protected $_date_billed;
	protected $_invoice_number;

	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_data = $data;
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_attributes_only = ( isset($this->_data->attributes_only) AND 
							 		$this->_data->attributes_only )
								? TRUE
								: FALSE;

		$this->_address_only = ( isset($this->_data->address_only) AND 
							 		$this->_data->address_only )
								? TRUE
								: FALSE;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);

		$this->_purchase_lines = array();
		$this->_account_transactions = array();

		$this->_transaction_purchase_account_id = $this->_beans_setting_get('purchase_default_account_id');
		$this->_transaction_purchase_line_account_id = $this->_beans_setting_get('purchase_default_line_account_id');
		
		$this->_date_billed = ( isset($this->_data->date_billed) )
							? $this->_data->date_billed
							: FALSE;

		$this->_invoice_number = ( isset($this->_data->invoice_number) )
							   ? $this->_data->invoice_number
							   : FALSE;
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("purchase could not be found.");

		if( ! $this->_transaction_purchase_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO account.");

		if( ! $this->_transaction_purchase_line_account_id )
			throw new Exception("INTERNAL ERROR: Could not find default PO Line account.");

		// Independently validate $this->_date_billed and $this->_invoice_number
		if( $this->_date_billed AND 
			$this->_date_billed != date("Y-m-d",strtotime($this->_date_billed)) )
			throw new Exception("Invalid invoice date: must be in YYYY-MM-DD format.");

		if( $this->_invoice_number AND 
			strlen($this->_invoice_number) > 16 )
			throw new Exception("Invalid invoice number: maxmimum length of 16 characters.");

		if( $this->_invoice_number AND
			! $this->_date_billed )
			throw new Exception("Both an invoice number and date are required.");
		
		// There's a unique use-case that's hard to replicate, but it produces a form that
		// has no create_transaction - closing the FYE with this form can be frustrating to deal with otherwise.
		if( $this->_check_books_closed($this->_purchase->date_created) &&
			$this->_purchase->create_transaction_id &&
			$this->_purchase->invoice_transaction_id &&
			$this->_purchase->cancel_transaction_id )
			throw new Exception("purchase could not be updated.  The financial year has been closed already.");

		if( $this->_purchase->date_cancelled )
			throw new Exception("A purchase cannot be updated after it has been cancelled.");
		
		if( isset($this->_data->vendor_id) )
			$this->_purchase->entity_id = $this->_data->vendor_id;

		if( isset($this->_data->account_id) )
			$this->_purchase->account_id = $this->_data->account_id;

		if( isset($this->_data->sent) )
			$this->_purchase->sent = $this->_data->sent;

		if( isset($this->_data->date_created) )
			$this->_purchase->date_created = $this->_data->date_created;

		if( isset($this->_data->purchase_number) )
			$this->_purchase->code = $this->_data->purchase_number;

		if( isset($this->_data->so_number) )
			$this->_purchase->reference = $this->_data->so_number;

		if( isset($this->_data->quote_number) )
			$this->_purchase->alt_reference = $this->_data->quote_number;

		if( isset($this->_data->invoice_number) )
			$this->_purchase->aux_reference = $this->_data->invoice_number;

		if( isset($this->_data->date_billed) )
		{
			if( strtotime($this->_data->date_billed) < strtotime($this->_purchase->date_created) )
				throw new Exception("Invalid invoice date: must be on or after the creation date of ".$this->_purchase->date_created.".");

			$this->_purchase->date_billed = $this->_data->date_billed;

			$account = $this->_load_account($this->_purchase->account_id);
			$this->_purchase->date_due = date("Y-m-d",strtotime($this->_purchase->date_billed.' +'.$account->terms.' Days'));
		}

		if( isset($this->_data->remit_address_id) )
			$this->_purchase->remit_address_id = $this->_data->remit_address_id;

		if( isset($this->_data->shipping_address_id) )
			$this->_purchase->shipping_address_id = $this->_data->shipping_address_id;
		
		// Make sure we have good purchase information before moving on.
		$this->_validate_vendor_purchase($this->_purchase);

		if( $this->_attributes_only )
		{
			$this->_purchase->save();

			return (object)array(
				"success" => TRUE,
				"auth_error" => "",
				"error" => "",
				"data" => (object)array(
					"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
				),
			);
		}

		$this->_purchase->total = 0.00;
		$this->_purchase->amount = 0.00;
		
		if( ! isset($this->_data->lines) OR 
			! is_array($this->_data->lines) OR
			! count($this->_data->lines) )
			throw new Exception("Invalid purchase purchase lines: none provided.");

		$adjustment_entered = FALSE;

		foreach( $this->_data->lines as $purchase_line )
		{
			$new_purchase_line = $this->_default_form_line();

			$new_purchase_line->account_id = ( isset($purchase_line->account_id) )
										  ? (int)$purchase_line->account_id
										  : NULL;

			$new_purchase_line->description = ( isset($purchase_line->description) )
										   ? $purchase_line->description
										   : NULL;

			$new_purchase_line->amount = ( isset($purchase_line->amount) )
									  ? $this->_beans_round($purchase_line->amount)
									  : NULL;

			$new_purchase_line->quantity = ( isset($purchase_line->quantity) )
										? (float)$purchase_line->quantity
										: NULL;

			$new_purchase_line->adjustment = ( isset($purchase_line->adjustment) )
										   ? $purchase_line->adjustment
										   : FALSE;

			if( $new_purchase_line->adjustment AND 
				$adjustment_entered )
				throw new Exception("Invalid purchase line: can only have one adjustment line.");
			else if( $new_purchase_line->adjustment AND 
					 $new_purchase_line->quantity != 1 )
				throw new Exception("Invalid purchase line: adjustment lines must have a quantity of 1.");
			else if( $new_purchase_line->adjustment )
				$adjustment_entered = TRUE;


			if( $new_purchase_line->account_id === NULL ) {
				$new_purchase_line->account_id = $this->_beans_setting_get('account_default_costofgoods');
			}

			$this->_validate_form_line($new_purchase_line);

			$new_purchase_line->total = $this->_beans_round( $new_purchase_line->amount * $new_purchase_line->quantity );

			$new_purchase_line_total = $this->_beans_round($new_purchase_line->total);

			$this->_purchase->amount = $this->_beans_round( $this->_purchase->amount + $new_purchase_line->total);
			
			$this->_purchase_lines[] = $new_purchase_line;
		}

		$this->_purchase->total = $this->_beans_round( $this->_purchase->total + $this->_purchase->amount );
		
		// Validate Totals
		
		if( $this->_purchase->refund_form_id )
		{
			$refund_form = $this->_load_vendor_purchase($this->_purchase->refund_form_id);

			$original_purchase = $this->_purchase;
			$refund_purchase = $refund_form;

			if( $this->_purchase->refund_form_id < $this->_purchase->id )
			{
				$refund_purchase = $this->_purchase;
				$original_purchase = $refund_form;
			}

			if( ( $original_purchase->total > 0.00 AND $refund_purchase->total > 0.00 ) OR 
				( $original_purchase->total < 0.00 AND $refund_purchase->total < 0.00 ) )
				throw new Exception("Refund and original purchase totals must offset each other ( they cannot both be positive or negative ).");

			if( abs($refund_purchase->total) > abs($original_purchase->total) )
				throw new Exception("The refund total cannot be greater than the original purchase total.");
		}

		// Delete all current purchase children.
		foreach( $this->_purchase->form_lines->find_all() as $purchase_line )
			$purchase_line->delete();
		
		// Save purchase + Children
		$this->_purchase->save();

		foreach( $this->_purchase_lines as $j => $purchase_line )
		{
			$purchase_line->form_id = $this->_purchase->id;
			$purchase_line->save();
		}

		$purchase_calibrate = new Beans_Vendor_Purchase_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_purchase->id),
		)));
		$purchase_calibrate_result = $purchase_calibrate->execute();

		if( ! $purchase_calibrate_result->success )
			throw new Exception("Error encountered when calibrating payments: ".$purchase_calibrate_result->error);

		// Recalibrate Payments 
		$vendor_payment_calibrate = new Beans_Vendor_Payment_Calibrate($this->_beans_data_auth((object)array(
			'form_ids' => array($this->_purchase->id),
		)));
		$vendor_payment_calibrate_result = $vendor_payment_calibrate->execute();

		if( ! $vendor_payment_calibrate_result->success )
			throw new Exception("Error encountered when calibrating payments: ".$vendor_payment_calibrate_result->error);

		$this->_purchase = $this->_load_vendor_purchase($this->_purchase->id);
		
		return (object)array(
			"purchase" => $this->_return_vendor_purchase_element($this->_purchase),
		);
	}
}
