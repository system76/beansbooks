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
@action Beans_Vendor_Purchase_Cancel
@description Cancel a vendor purchase. This should be called if you cannot delete a purchase because it has payments tied to it.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Purchase# to cancel.
@returns purchase OBJECT The updated #Beans_Vendor_Purchase#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Cancel extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";

	protected $_id;
	protected $_purchase;
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_purchase = $this->_load_vendor_purchase($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("Purchase purchase could not be found.");

		if( $this->_purchase->date_cancelled || 
			$this->_purchase->cancel_transaction_id )
			throw new Exception("Purchase has already been cancelled.");

		if( $this->_check_books_closed($this->_purchase->date_created) )
			throw new Exception("Purchase purchase could not be deleted.  The financial year has been closed already.");

		if( $this->_purchase->refund_form_id AND 
			$this->_purchase->refund_form_id > $this->_purchase->id )
			throw new Exception("Purchase could not be cancelled - it has a refund attached to it.");

		$date_cancelled = date("Y-m-d");
		
		$this->_purchase->date_cancelled = $date_cancelled;
		$this->_purchase->save();

		$purchase_calibrate = new Beans_Vendor_Purchase_Calibrate($this->_beans_data_auth((object)array(
			'ids' => array($this->_purchase->id),
		)));
		$purchase_calibrate_result = $purchase_calibrate->execute();

		$this->_purchase = $this->_load_vendor_purchase($this->_purchase->id);

		if( ! $purchase_calibrate_result->success )
		{
			$this->_purchase->date_cancelled = NULL;
			$this->_purchase->save();

			throw new Exception("Error trying to cancel purchase: ".$purchase_calibrate_result->error);
		}

		// Remove the refund form from the corresponding form.
		if( $this->_purchase->refund_form->loaded() )
		{
			$this->_purchase->refund_form->refund_form_id = NULL;
			$this->_purchase->refund_form->save();
		}
		
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