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
@action Beans_Customer_Sale_Refund
@description Create a refund for a sale.  This acts just like !Beans_Customer_Sale_Create! in terms of parameters, but expects an ID of a #Beans_Customer_Sale# to refund.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID for the #Beans_Customer_Sale# that will be refunded with this sale.
@required date_created STRING The date of the sale in YYYY-MM-DD format.
@optional date_billed STRING The bill date in YYYY-MM-DD for the sale; adding this will automatically convert it to an invoice.
@optional date_due STRING The due date in YYYY-MM-DD for the sale; adding this will automatically convert it to an invoice.
@optional account_id INTEGER The ID for the Accounts Receivable #Beans_Account# this sale will be tied to.
@optional sent STRING STRING The sent status for the sale: "email", "phone", or "both".
@optional sale_number STRING A customer sale number to reference this sale.  If none is created, it will auto-generate.
@optional order_number STRING An order number to help identify this sale.
@optional po_number STRING A purchase order number to help identify this sale.
@optional quote_number STRING A quote number to help identify this sale.
@optional tax_exempt BOOLEAN If set to true, all lines will be marked as tax_exempt.
@optional billing_address_id INTEGER The ID of the #Beans_Customer_Address# for billing this sale.
@optional shipping_address_id INTEGER The ID of the #Beans_Customer_Address# for shipping this sale.
@required lines ARRAY An array of objects representing line items for the sale.
@required @attribute lines description STRING The text for the line item.
@required @attribute lines amount DECIMAL The amount per unit.
@required @attribute lines quantity INTEGER The number of units.
@optional @attribute lines account_id INTEGER The ID of the #Beans_Account# to count the sale towards ( in terms of revenue ).
@optional @attribute lines tax_exempt BOOLEAN
@optional taxes ARRAY An array of objects representing taxes applicable to the sale.
@required @attribute taxes tax_id The ID of the #Beans_Tax# that should be applied.
@returns sale OBJECT The resulting #Beans_Customer_Sale#.
---BEANSENDSPEC---
*/
class Beans_Customer_Sale_Refund extends Beans_Customer_Sale {

	protected $_auth_role_perm = "customer_sale_write";
	
	protected $_id;
	protected $_data;		// Will be passed along.
	protected $_sale;		// Sale that is being refunded.
	
	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_id = ( isset($data->id) ) 
				   ? (int)$data->id
				   : 0;

		$this->_data = $data;
		$this->_sale = $this->_load_customer_sale($this->_id);
	}

	protected function _execute()
	{
		if( ! $this->_sale->loaded() )
			throw new Exception("That sale could not be found.");

		if( $this->_sale->refund_form->loaded() )
			throw new Exception("That sale already belongs to a refund-set.");

		$this->_data->customer_id = $this->_sale->entity_id;
		$this->_data->refund_sale_id = $this->_sale->id;
		$this->_data->code = "R".$this->_sale->code;
		$this->_data->reference = ( $this->_sale->reference )
								? 'R'.$this->_sale->reference 
								: NULL;
		$this->_data->alt_reference = ( $this->_sale->alt_reference )
									? 'R'.$this->_sale->alt_reference 
									: NULL;
		
		// Add default return account
		if( ! isset($this->_data->account_id) OR 
			! $this->_data->account_id )
		{
			$this->_data->account_id = $this->_beans_setting_get('account_default_returns');
		}

		$create_sale = new Beans_Customer_Sale_Create($this->_beans_data_auth($this->_data));
		$create_sale_result = $create_sale->execute();

		if( ! $create_sale_result->success )
			throw new Exception($create_sale_result->error);

		$this->_sale->refund_form_id = $create_sale_result->data->sale->id;
		$this->_sale->save();
		
		return (object)array(
			"sale" => $create_sale_result->data->sale,
		);
	}
}