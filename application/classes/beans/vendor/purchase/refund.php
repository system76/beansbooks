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
@description Create a refund for a vendor purchase.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Vendor_Purchase# being refunded.
@required account_id INTEGER The ID for the AP #Beans_Account# this purchase is being added to.
@required date_created STRING The date of the purchase in YYYY-MM-DD format.
@optional date_billed STRING The bill date in YYYY-MM-DD for the sale; adding this will automatically convert it to an invoice.
@optional invoice_number STRING This is required if date_billed is provided.
@optional purchase_number STRING An purchase number to reference this purchase.  If none is created, it will auto-generate.
@optional so_number STRING An SO number to reference this purchase.
@optional quote_number STRING A quote number to reference this purchase.
@optional remit_address_id INTEGER The ID of the #Beans_Vendor_Address# to remit payment to.
@optional shipping_address_id INTEGER The ID of the #Beans_Vendor_Address_Shipping# to ship to.
@required lines ARRAY An array of objects representing line items for the purchase.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity INTEGER The number of units.
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the cost of the purchase towards.
@returns purchase OBJECT The resulting #Beans_Vendor_Purchase#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Refund extends Beans_Vendor_Purchase {

	protected $_auth_role_perm = "vendor_purchase_write";
	
	protected $_id;
	protected $_data;			// Will be passed along.
	protected $_purchase;		// purchase that is being refunded.
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_data = $data;
		$this->_purchase = $this->_load_vendor_purchase($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_purchase->loaded() )
			throw new Exception("That purchase could not be found.");

		if( $this->_purchase->refund_form->loaded() )
			throw new Exception("That purchase already belongs to a refund-set.");

		$this->_data->vendor_id = $this->_purchase->entity_id;
		$this->_data->refund_purchase_id = $this->_purchase->id;
		$this->_data->code = "R".$this->_purchase->code;
		$this->_data->reference = ( $this->_purchase->reference )
								? 'R'.$this->_purchase->reference 
								: NULL;
		$this->_data->alt_reference = ( $this->_purchase->alt_reference )
									? 'R'.$this->_purchase->alt_reference 
									: NULL;
		
		$create_purchase = new Beans_Vendor_Purchase_Create($this->_beans_data_auth($this->_data));
		$create_purchase_result = $create_purchase->execute();

		if( ! $create_purchase_result->success )
			throw new Exception($create_purchase_result->error);

		$this->_purchase->refund_form_id = $create_purchase_result->data->purchase->id;
		$this->_purchase->save();
		
		return (object)array(
			"purchase" => $create_purchase_result->data->purchase,
		);
	}
}