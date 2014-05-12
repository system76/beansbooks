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

class Controller_Setup extends Controller_View {
	
	protected $_required_role_permissions = array(
		'default' => 'setup',
	);

	function before()
	{
		parent::before();

		// Check for tabs and set if necessary.
		if( ! Session::instance()->get('tab_section') )
		{
			Session::instance()->set('tab_section',$this->request->controller());
			
			$tab_links = array();

			$tab_links[] = array(
				'url' => '/setup/settings',
				'text' => 'Company Info',
				'removable' => FALSE,
				'text_short' => "Info",
			);

			$tab_links[] = array(
				'url' => '/setup/taxes',
				'text' => 'Sales Tax',
				'removable' => FALSE,
				'text_short' => 'Tax',
			);

			$tab_links[] = array(
				'url' => '/setup/users',
				'text' => 'Users',
				'removable' => FALSE,
				'text_short' => "Users",
			);

			Session::instance()->set('tab_links',$tab_links);
		}

	}

	function action_index()
	{
		$this->request->redirect('/setup/settings');
	}

	function action_settings()
	{
		// Handle $_POST
		$data = new stdClass;
		$data->settings = new stdClass;
		
		if( count($this->request->post()) )
		{
			// Update every account - THREE LAWS.
			$accounts_search = new Beans_Account_Search($this->_beans_data_auth());
			$accounts_search_result = $accounts_search->execute();
			
			if( $this->_beans_result_check($accounts_search_result) )
			{
				foreach( $accounts_search_result->data->accounts as $account )
				{
					if( $account->parent_account_id AND 
						! $account->reserved )
					{
						$account_update_result = FALSE;
						if( in_array($account->id, $this->request->post('writeoff_account_ids')) )
						{
							$account_update = new Beans_Account_Update($this->_beans_data_auth((object)array(
								'id' => $account->id,
								'writeoff' => TRUE,
							)));
							$account_update_result = $account_update->execute();
						} else {
							$account_update = new Beans_Account_Update($this->_beans_data_auth((object)array(
								'id' => $account->id,
								'writeoff' => FALSE,
							)));
							$account_update_result = $account_update->execute();
						}

						if( ! $account_update_result )
							$this->_view->send_error_message('An unexpected error occurred.');

						// We can ignore the return value - this will post the necessary error.
						$this->_beans_result_check($account_update_result);
					}
				}

				$data->settings->company_name = $this->request->post('company_name');
				$data->settings->company_email = $this->request->post('company_email');
				$data->settings->company_phone = $this->request->post('company_phone');
				$data->settings->company_fax = $this->request->post('company_fax');
				$data->settings->company_address_address1 = $this->request->post('company_address_address1');
				$data->settings->company_address_address2 = $this->request->post('company_address_address2');
				$data->settings->company_address_city = $this->request->post('company_address_city');
				$data->settings->company_address_state = $this->request->post('company_address_state');
				$data->settings->company_address_zip = $this->request->post('company_address_zip');
				$data->settings->company_address_country = $this->request->post('company_address_country');
				$data->settings->company_fye = $this->request->post('company_fye');
				$data->settings->company_currency = $this->request->post('company_currency');
				$data->settings->account_default_deposit = $this->request->post('account_default_deposit');
				$data->settings->account_default_receivable = $this->request->post('account_default_receivable');
				$data->settings->account_default_income = $this->request->post('account_default_income');
				$data->settings->account_default_returns = $this->request->post('account_default_returns');
				$data->settings->account_default_expense = $this->request->post('account_default_expense');
				$data->settings->account_default_order = $this->request->post('account_default_order');
				$data->settings->account_default_costofgoods = $this->request->post('account_default_costofgoods');
				$data->settings->account_default_payable = $this->request->post('account_default_payable');

				if( isset($_FILES['logo']) AND 
					$_FILES['logo']['error'] == UPLOAD_ERR_OK )
				{
					// Do some magic.
					$type = strtolower(substr($_FILES['logo']['type'],(1+strpos($_FILES['logo']['type'],'/'))));
					if( substr($_FILES['logo']['type'],0,strpos($_FILES['logo']['type'],'/')) == "image" AND
						(
							$type == "gif" OR 
							$type == "png" OR 
							$type == "jpeg" OR 
							$type == "jpg"
						) )
					{
						$image = FALSE;

						if( $type == "jpeg" OR 
							$type == "jpg" )
							$image = imagecreatefromjpeg($_FILES['logo']['tmp_name']);
						else if( $type == "gif" )
							$image = imagecreatefromgif($_FILES['logo']['tmp_name']);
						else if( $type == "png" )
						{
							$image = imagecreatefrompng($_FILES['logo']['tmp_name']);
							imagealphablending($image,TRUE);
							imagesavealpha($image, TRUE);
						}
							
						if( ! $image )
						{
							$this->_view->send_error_message("An error occurred when reading your image.  Must be JPG/JPEG, PNG, or GIF.");
						}
						else
						{
							$width = imagesx($image);
							$height = imagesy($image);
							if( $width > 150 OR
								$height > 50 )
							{
								// Resize
								$new_width = 150;
								$new_height = 50;

								if( ( $width / $height ) > ( $new_width / $new_height ) )
									$new_height = ( $new_width * ( $height / $width ) );
								else
									$new_width = ( $new_height * ( $width / $height ) );

								$new_image = imagecreatetruecolor($new_width, $new_height);

								if( strtolower($type) == "png" )
								{
									imagealphablending($new_image, false);
									imagesavealpha($new_image,true);
									$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
									imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
								}

								imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
								$image = $new_image;
							}

							// Fancy trick to avoid writing to the filesystem.
							$logo_filename = "logo.";
							ob_start();
							if( $type == "jpeg" OR 
								$type == "jpg" )
							{
								$logo_filename .= "jpg";	
								imagejpeg($image,NULL,95);
							}
							else if( $type == "gif" )
							{
								$logo_filename .= "gif";
								imagegif($image);
							}
							else if( $type == "png" )
							{
								$logo_filename .= "png";
								imagepng($image,NULL,9);
							}
							$image_string =  ob_get_contents();
							ob_end_clean();

							// Ensure "image/jpeg" if it's JPEG.
							$type = ( $type == "jpg" ) ? "jpeg" : $type;

							$data->settings->company_logo_data = base64_encode($image_string);
							$data->settings->company_logo_type = 'image/'.$type;

							// Not really necessary.
							$data->settings->company_logo_filename = $logo_filename;
						}
					}
					else
					{
						$this->_view->send_error_message("That logo was not an acceptable image format.  Must be JPG/JPEG, PNG, or GIF.");
					}
				}
				else if( isset($_FILES['logo']) AND 
						 $_FILES['logo']['error'] != UPLOAD_ERR_NO_FILE )
				{
					$this->_view->send_error_message("An error occurred when receiving that logo.  Please try again with a smaller file.".$_FILES['logo']['error']);
				}
			}
		}

		$beans_company_update = new Beans_Setup_Company_Update($this->_beans_data_auth($data));
		$beans_company_update_result = $beans_company_update->execute();

		if( $this->_beans_result_check($beans_company_update_result) )
			$this->_view->beans_company_update_result = $beans_company_update_result;

	}

	public function action_taxes()
	{
		$tax_search = new Beans_Tax_Search($this->_beans_data_auth());
		$tax_search_result = $tax_search->execute();

		if( $this->_beans_result_check($tax_search_result) )
			$this->_view->tax_search_result = $tax_search_result;
		
	}

	public function action_users()
	{
		$auth_role_search = new Beans_Auth_Role_Search($this->_beans_data_auth());
		$auth_role_search_result = $auth_role_search->execute();

		if( $this->_beans_result_check($auth_role_search_result) )
			$this->_view->auth_role_search_result = $auth_role_search_result;

		$auth_user_search = new Beans_Auth_User_Search($this->_beans_data_auth());
		$auth_user_search_result = $auth_user_search->execute();

		if( $this->_beans_result_check($auth_user_search_result) )
			$this->_view->auth_user_search_result = $auth_user_search_result;

		$api_role_lookup = new Beans_Auth_Role_Lookup($this->_beans_data_auth((object)array(
			'role_code' => "api",
		)));
		$api_role_lookup_result = $api_role_lookup->execute();

		if( $this->_beans_result_check($api_role_lookup_result) )
			$this->_view->api_role_lookup_result = $api_role_lookup_result;
	}

	public function action_calibrate()
	{
		$report_balancecheck = new Beans_Report_Balancecheck($this->_beans_data_auth((object)array(
			'date' => date("Y-m-d"),
		)));
		$report_balancecheck_result = $report_balancecheck->execute();

		if( $this->_beans_result_check($report_balancecheck_result) )
			$this->_view->report_balancecheck_result = $report_balancecheck_result;

		$setup_company_list = new Beans_Setup_Company_List($this->_beans_data_auth());
		$setup_company_list_result = $setup_company_list->execute();

		if( $this->_beans_result_check($setup_company_list_result) )
			$this->_view->setup_company_list_result = $setup_company_list_result;
	}

}