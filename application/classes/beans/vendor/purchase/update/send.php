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
@action Beans_Vendor_Purchase_Update_Send
@description Send the purchase order by email.
@required auth_uid
@required auth_key
@required auth_expiration
@required id INTEGER The ID of the #Beans_Vendor_Purchase# to update.
@optional email STRING The email we should send the PO to.
@returns purchase OBJECT The updated #Beans_Vendor_Purchase#.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Update_Send extends Beans_Vendor_Purchase {

  protected $_auth_role_perm = "vendor_purchase_write";

  protected $_data;
  protected $_id;
  protected $_purchase;

  public function __construct($data = NULL)
  {
    parent::__construct($data);

    $this->_data = $data;
    $this->_id = (isset($data->id)) ? (int) $data->id : 0;

    $this->_purchase = $this->_load_vendor_purchase($this->_id);

    $vendor_purchase_lookup = new Beans_Vendor_Purchase_Lookup($this->_beans_data_auth((object)array(
      'id' => $this->_id,
    )));
    $this->_lookup = $vendor_purchase_lookup->execute();
  }

  protected function _execute()
  {
    if (!$this->_purchase->loaded()) {
      throw new Exception("Purchase could not be found.");
    }

    if (!$this->_data->email || !filter_var($this->_data->email, FILTER_VALIDATE_EMAIL)) {
      return $this->_return_error("Please provide a valid email address.");
    }

    $company_settings = new Beans_Setup_Company_List($this->_beans_data_auth());
    $company_settings_result = $company_settings->execute();

    if (!$company_settings_result->success) {
      return $this->_return_error($this->_beans_result_get_error($company_settings_result));
    }

    // Shorten for sanity's sake...
    $settings = $company_settings_result->data->settings;

    if (!isset($settings->company_email) || !strlen($settings->company_email)) {
      return $this->_return_error("Email cannot be sent until you set an email address for your company within 'Setup'.");
    }

    $message = Swift_Message::newInstance();
    $message
      ->setSubject($settings->company_name.' - Purchase '.$this->_lookup->data->purchase->purchase_number)
      ->setFrom(array($settings->company_email))
      ->setTo(array($this->_data->email));

    $vendors_print_purchase = new View_Vendors_Print_Purchase();
    $vendors_print_purchase->setup_company_list_result = $company_settings_result;
    $vendors_print_purchase->purchase = $this->_lookup->data->purchase;
    $vendors_print_purchase->swift_email_message = $message;
    $vendors_print_purchase->updated_purchase = (boolean) $this->_data->updated;

    $message = $vendors_print_purchase->render();

    if (!Email::connect()) {
      return $this->_return_error("Could not send email. Does your config have correct email settings?");
    }

    if (!Email::sendMessage($message)) {
      return $this->_return_error("Could not send email. Does your config have correct email settings?");
    }

    $vendor_purchase_update_sent_data = new stdClass;
    $vendor_purchase_update_sent_data->id = $this->_id;
    $vendor_purchase_update_sent_data->sent = 'email';

    $vendor_purchase_update_sent = new Beans_Vendor_Purchase_Update_Sent($this->_beans_data_auth($vendor_purchase_update_sent_data));
    $vendor_purchase_update_sent_result = $vendor_purchase_update_sent->execute();

    if (!$vendor_purchase_update_sent_result->success) {
      return $this->_return_error("An error occurred when updating that purchase:<br>".$this->_beans_result_get_error($vendor_purchase_update_sent_result));
    }

    $this->_purchase->save();

    return (object) array(
      "purchase" => $this->_return_vendor_purchase_element($this->_purchase),
    );
  }
}
