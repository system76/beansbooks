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

class Controller_Accounts extends Controller_View {

	protected $_required_role_permissions = array(
		'default' => 'account_read',
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
				'url' => '/accounts',
				'text' => 'Chart',
				'removable' => FALSE,
				'text_short' => "Chart",
			);

			Session::instance()->set('tab_links',$tab_links);
		}

	}

	function after()
	{
		if( Session::instance()->get('account_error_message') ) {
			$this->_view->send_error_message(Session::instance()->get('account_error_message'));
			Session::instance()->delete('account_error_message');
		}
		
		if( Session::instance()->get('account_success_message') ) {
			$this->_view->send_success_message(Session::instance()->get('account_success_message'));
			Session::instance()->delete('account_success_message');
		}

		parent::after();
	}

	/**
	 * Chart of Accounts
	 */
	public function action_index()
	{
		
	}

	public function action_setup()
	{
		// Update chart_setup
		$beans_company_update = new Beans_Setup_Company_Update($this->_beans_data_auth((object)array(
			'settings' => (object)array(
				'chart_setup' => TRUE,
			),
		)));
		$beans_company_update_result = $beans_company_update->execute();

		if( $this->_beans_result_check($beans_company_update_result) )
			$this->_view->beans_company_update_result = $beans_company_update_result;

		$this->request->redirect('/accounts');
	}

	public function action_startingbalance()
	{
		$account_transaction_search = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
			'search_code' => "STARTINGBAL",
		)));
		$account_transaction_search_result = $account_transaction_search->execute();

		if( ! $account_transaction_search_result->success ) 
		{
			Session::instance()->set('account_error_message',$account_transaction_search_result->auth_error.$account_transaction_search_result->error);
			$this->request->redirect('/accounts/');
		}

		if( $account_transaction_search_result->data->total_results > 0 )
		{
			Session::instance()->set('account_error_message',"An opening balance has already been created and cannot be done again.<br>To make adjustments to your accuunts please add the appropriate transactions to your account journals.");
			$this->request->redirect('/accounts/');
		}

		if( count($this->request->post()) )
		{
			$create_transaction_data = new stdClass;
			$create_transaction_data->account_transactions = array();
			$create_transaction_data->code = "STARTINGBAL";
			/* $create_transaction_data->reference = "STARTINGBAL"; */
			$create_transaction_data->description = "Starting Balance";

			$create_transaction_data->date = date("Y-m-d");
			if( $this->request->post('date') AND 
				strlen($this->request->post('date')) )
				$create_transaction_data->date = date("Y-m-d",strtotime($this->request->post('date')));

			foreach( $this->request->post() as $key => $value ) 
			{
				if( strpos($key, 'account_debit_') !== FALSE AND
					strlen($value) AND 
					floatval($value) != 0 ) 
				{
					$create_transaction_data->account_transactions[] = (object)array(
						'account_id' => str_replace('account_debit_', '', $key),
						'amount' => ( -1 * $value ),
					);
				}
				else if( 	strpos($key, 'account_credit_') !== FALSE AND
							strlen($value) AND 
							floatval($value) != 0 )
				{
					$create_transaction_data->account_transactions[] = (object)array(
						'account_id' => str_replace('account_credit_', '', $key),
						'amount' => ( $value ),
					);
				}
			}

			$create_transaction = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction_data));
			$create_transaction_result = $create_transaction->execute();

			if( ! $create_transaction_result->success ) 
				Session::instance()->set('account_error_message',$create_transaction_result->auth_error.$create_transaction_result->error);
			else
			{
				// Add reconciles
				foreach( $create_transaction_result->data->transaction->account_transactions as $account_transaction )
				{
					if( $account_transaction->account->reconcilable )
					{
						// Create an account reconcile.
						$account_reconcile_create = new Beans_Account_Reconcile_Create($this->_beans_data_auth((object)array(
							'account_id' => $account_transaction->account->id,
							'date' => $create_transaction_result->data->transaction->date,
							'balance_start' => 0.00,
							'balance_end' => $account_transaction->amount * $account_transaction->account->type->table_sign,
							'account_transaction_ids' => array(
								$account_transaction->id,
							),
						)));
						$account_reconcile_create_result = $account_reconcile_create->execute();

						if( ! $account_reconcile_create_result->success )
							Session::instance()->set('account_error_message',"One or more transactions could not be automatically reconciled.");
					}
				}

				Session::instance()->set('account_success_message',"Opening balance has been added to your chart of accounts.");
			}
			
			$this->request->redirect('/accounts/');
		}
	}

	public function action_calibrate()
	{
		$account_id = $this->request->param('id');

		$account_calibrate = new Beans_Account_Calibrate($this->_beans_data_auth((object)array('id' => $account_id)));
		$account_calibrate_result = $account_calibrate->execute();

		if( $this->_beans_result_check($account_calibrate_result) )
		{
			$this->_view->account_calibrate_result = $account_calibrate_result;
		}

	}

	public function action_calibrateall()
	{
		set_time_limit(60 * 10);
		
		$account_search = new Beans_Account_Search($this->_beans_data_auth());
		$account_search_result = $account_search->execute();

		if( ! $account_search_result->success )
		{
			Session::instance()->set('global_error_message',$account_search_result->error.$account_search_result->auth_error.$account_search_result->config_error);
			$this->request->redirect('/accounts/');
		}

		$success = '';

		foreach( $account_search_result->data->accounts as $account )
		{
			$account_calibrate = new Beans_Account_Calibrate($this->_beans_data_auth((object)array('id' => $account->id)));
			$account_calibrate_result = $account_calibrate->execute();

			if( ! $account_calibrate_result->success )
			{
				Session::instance()->set('global_error_message',$account_calibrate_result->error.$account_calibrate_result->auth_error.$account_calibrate_result->config_error);
				$this->request->redirect('/accounts/');
			}

			if( $account_calibrate_result->data->calibrated_balance_shift != 0.00 )
				$success .= 'Calibrated '.$account->name.' by '.$account_calibrate_result->data->calibrated_balance_shift.'<br>';
		}

		if( strlen($success) == 0 )
			$success .= 'Calibration successful: no changes made.';
		
		Session::instance()->set('global_success_message',$success);
		$this->request->redirect('/accounts/');
	}

	public function action_view()
	{
		$account_id = $this->request->param('id');

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array('id' => $account_id)));
		$account_lookup_result = $account_lookup->execute();

		if( $this->_beans_result_check($account_lookup_result) )
		{
			$this->_view->account_lookup_result = $account_lookup_result;

			$account_transactions = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
				'account_id' => $account_lookup_result->data->account->id,
				'page_size' => 50,
				'page' => 0,
				'search_include_cancelled' => TRUE,
				'sort_by' => 'newest',
			)));
			$account_transactions_result = $account_transactions->execute();

			if( $this->_beans_result_check($account_transactions_result) )
				$this->_view->account_transactions_result = $account_transactions_result;
		
			$this->_action_tab_name = $account_lookup_result->data->account->name;
			$this->_action_tab_uri = '/'.$this->request->uri();
		}

		
		
	}

	public function action_import()
	{
		$account_id = $this->request->param('id');

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array('id' => $account_id)));
		$account_lookup_result = $account_lookup->execute();

		if( $this->_beans_result_check($account_lookup_result) )
		{
			$this->_view->account_lookup_result = $account_lookup_result;
			
			$this->_action_tab_name = $account_lookup_result->data->account->name;
			$this->_view->force_current_uri = '/accounts/view/'.$account_id;

			$account_transactions = array();

			if( isset($_FILES['transactionsfile']) AND 
				$_FILES['transactionsfile']['error'] == UPLOAD_ERR_OK )
			{
				$extension = strtolower(substr($_FILES['transactionsfile']['name'],( 1 + strrpos($_FILES['transactionsfile']['name'],'.') )));
				if( $extension == "csv" )
				{
					$this->_view->transactionsfilestring = file_get_contents($_FILES['transactionsfile']['tmp_name']);
					return;
				}
				else if( $extension == "qbo" OR 
						 $extension == "qfx" OR 
						 $extension == "ofx" )
				{
					try
					{
						$account_table_sign = $account_lookup_result->data->account->type->table_sign;
						$ofx = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?>\n".$this->_fix_ofx(file_get_contents($_FILES['transactionsfile']['tmp_name'])));
						$i = 0;
						foreach( $ofx->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $transaction )
						{
							$account_transactions[] = (object)array(
								'account_id' => $account_id,
								'hash' => $i++,
								'amount' => ( $transaction->TRNAMT * $account_table_sign ),
								'description' => $transaction->NAME,
								'date' => substr($transaction->DTPOSTED,0,4).'-'.substr($transaction->DTPOSTED,4,2).'-'.substr($transaction->DTPOSTED,6,2),
								'number' =>  isset($transaction->CHECKNUM) ? $transaction->CHECKNUM : $transaction->TRNTYPE ,
							);
						}

						$account_transactions_match = new Beans_Account_Transaction_Match($this->_beans_data_auth((object)array(
							'date_range_days' => 3,
							'account_transactions' => $account_transactions,
						)));
						$account_transactions_match_result = $account_transactions_match->execute();

						if( $this->_beans_result_check($account_transactions_match_result) )
							$this->_view->account_transactions = $account_transactions_match_result->data->account_transactions;
						
						$this->_view->account_table_sign = $account_table_sign;
						return;
					}
					catch( Exception $e )
					{
						return $this->_view->send_error_message("An error occurred when trying to read that file. ".$e->getMessage());
					}
				}
				else
				{
					// Error - Unsupported format.
					return $this->_view->send_error_message("Error - that file type is not supported.  Please upload a QBO, QFX, or CSV.");
				}
			}

			if( $this->request->post('transactionsfilestring') )
			{
				$date_index = $this->request->post('date_index');
				$description_index = $this->request->post('description_index');
				$number_index = $this->request->post('number_index');
				$amount_index = $this->request->post('amount_index');
				$account_table_sign = $this->request->post('account_table_sign');

				if( $date_index === NULL OR 
					$description_index === NULL OR 
					$number_index === NULL OR 
					$amount_index === NULL OR
					$account_table_sign === NULL )
				{
					return $this->_view->send_error_message("Error - missing required values.");
				} 
				else
				{
					// Create a bunch of transactions - search for matches - etc.
					$transactionsfilelines = explode("\n",$this->request->post('transactionsfilestring'));
					
					foreach( $transactionsfilelines as $i => $transactionfileline )
					{
						if( strlen($transactionfileline) > 5 ) 
						{
							$line = explode(",",$transactionfileline);
							if( substr($line[$description_index],0,1) == '"' AND 
								substr($line[$description_index],-1) == '"' )
								$line = str_getcsv($transactionfileline,',','"');
							
							$account_transactions[] = (object)array(
								'account_id' => $account_id,
								'hash' => $i,
								'amount' => ( $line[$amount_index] * $account_table_sign ),
								'description' => $line[$description_index],
								'date' => date("Y-m-d",strtotime($line[$date_index])),
								'number' =>  $line[$number_index],
							);
						}
					}
					
					$account_transactions_match = new Beans_Account_Transaction_Match($this->_beans_data_auth((object)array(
						'date_range_days' => 3,
						'account_transactions' => $account_transactions,
					)));
					$account_transactions_match_result = $account_transactions_match->execute();

					if( $this->_beans_result_check($account_transactions_match_result) )
						$this->_view->account_transactions = $account_transactions_match_result->data->account_transactions;
					
					$this->_view->account_table_sign = $account_table_sign;
				}
			}

			$this->_action_tab_name = $account_lookup_result->data->account->name;
			$this->_action_tab_uri = '/accounts/view/'.$account_lookup_result->data->account->id;
		}

		
	}

	public function action_importtransactions()
	{
		$account_id = $this->request->post('account_id');
		$account_table_sign = $this->request->post('account_table_sign');

		$importdata = $this->request->post('importdata');

		if( ! $importdata ) 
			return $this->_return_error("Missing required import data.");

		$importobject = json_decode($importdata);
		$importarray = array();

		foreach( $importobject as $key => $value )
			$importarray[$key] = $value;

		if( ! $account_id OR 
			! $account_table_sign )
			return $this->_return_error("Missing required values account ID and table sign.");

		$transaction_keys = array();
		foreach( $importarray as $key => $value )
			if( $value == "TRANSACTIONKEY" ) 
				$transaction_keys[] = $key;

		foreach( $transaction_keys as $hash )
		{
			if( Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer') != "duplicate" AND 
				Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer') != "ignore" )
			{
				$create_transaction = new stdClass;
				
				$create_transaction->date = ( Arr::get($importarray,'import-transaction-'.$hash.'-date') )
									   ? date("Y-m-d",strtotime(Arr::get($importarray,'import-transaction-'.$hash.'-date')))
									   : NULL; // THIS WILL NOT ALLOW TRANSACTION LINES WITHOUT A DATE date("Y-m-d");
				$create_transaction->code = Arr::get($importarray,'import-transaction-'.$hash.'-number');
				$create_transaction->description = Arr::get($importarray,'import-transaction-'.$hash.'-description');
				$create_transaction->account_transactions = array();

				$create_transaction->account_transactions[] = (object)array(
					'account_id' => $account_id,
					'amount' => ( Arr::get($importarray,'import-transaction-'.$hash.'-amount') * $account_table_sign ),
				);

				if( Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer') )
				{
					$create_transaction->account_transactions[] = (object)array(
						'account_id' => Arr::get($importarray,'import-transaction-'.$hash.'-transaction-transfer'),
						'amount' => ( $create_transaction->account_transactions[0]->amount * -1 )
					);
				}
				else
				{
					$split_keys = array();
					foreach( $importarray as $key => $value )
						if( $value == 'import-transaction-'.$hash.'-split-key' )
							$split_keys[] = str_replace('split-key-','',$key);
					
					foreach( $split_keys as $split_key )
					{
						if( Arr::get($importarray,'import-transaction-'.$hash.'-split-transaction-transfer-'.$split_key) )
							$create_transaction->account_transactions[] = (object)array(
								'account_id' => Arr::get($importarray,'import-transaction-'.$hash.'-split-transaction-transfer-'.$split_key),
								'amount' => ( Arr::get($importarray,'import-transaction-'.$hash.'-split-credit-'.$split_key) )
										 ? ( Arr::get($importarray,'import-transaction-'.$hash.'-split-credit-'.$split_key) * $account_table_sign )
										 : ( Arr::get($importarray,'import-transaction-'.$hash.'-split-debit-'.$split_key) * -1 * $account_table_sign ),
							);
					}
				}
				
				$account_transaction_create = new Beans_Account_Transaction_Create($this->_beans_data_auth($create_transaction));
				$account_transaction_create_result = $account_transaction_create->execute();

				$this->_beans_result_check($account_transaction_create_result);
					
			}
		}
		$this->request->redirect('/accounts/view/'.$account_id);
	}

	public function action_reconcile()
	{
		$account_id = $this->request->param('id');

		$account_lookup = new Beans_Account_Lookup($this->_beans_data_auth((object)array('id' => $account_id)));
		$account_lookup_result = $account_lookup->execute();

		if( $this->_beans_result_check($account_lookup_result) )
		{
			$this->_action_tab_name = $account_lookup_result->data->account->name;
			$this->_view->force_current_uri = '/accounts/view/'.$account_id;

			if( ! $account_lookup_result->data->account->reconcilable )
				$this->_view->send_error_message("This account cannot be reconciled.");
			else
			{
				$this->_view->account_lookup_result = $account_lookup_result;

				// Look up last reconcile...
				$account_reconcile = new Beans_Account_Reconcile_Search($this->_beans_data_auth((object)array(
					'account_id' => $account_lookup_result->data->account->id,
					'page' => 0,
					'page_size' => 1,
					'sort_by' => 'newest'
				)));
				$account_reconcile_result = $account_reconcile->execute();

				if( $this->_beans_result_check($account_reconcile_result) )
					$this->_view->account_reconcile_result = $account_reconcile_result;

				$account_transactions = new Beans_Account_Transaction_Search($this->_beans_data_auth((object)array(
					'account_id' => $account_lookup_result->data->account->id,
					'page_size' => 50000,
					'page' => 0,
					'not_reconciled' => TRUE,
					'sort_by' => 'oldest',
				)));
				$account_transactions_result = $account_transactions->execute();

				if( $this->_beans_result_check($account_transactions_result) )
					$this->_view->account_transactions_result = $account_transactions_result;
			}

			$this->_action_tab_name = $account_lookup_result->data->account->name;
			$this->_action_tab_uri = '/accounts/view/'.$account_lookup_result->data->account->id;
		}
	}

	public function action_reconcilecreate()
	{
		$account_reconcile_create_data = new stdClass;
		$account_reconcile_create_data->account_id = $this->request->post('account_id');
		$account_reconcile_create_data->date = date("Y-m-d",strtotime($this->request->post('date')));
		$account_reconcile_create_data->balance_start = preg_replace("/([^0-9\\.\\-])/i", "", $this->request->post('balance_start') );
		$account_reconcile_create_data->balance_end = preg_replace("/([^0-9\\.\\-])/i", "", $this->request->post('balance_end') );
		$account_reconcile_create_data->account_transaction_ids = array();

		foreach( $this->request->post() as $key => $value ) 
			if( strpos($key, 'include-transaction-') !== FALSE )
				$account_reconcile_create_data->account_transaction_ids[] = str_replace('include-transaction-', '', $key);

		$account_reconcile_create = new Beans_Account_Reconcile_Create($this->_beans_data_auth($account_reconcile_create_data));
		$account_reconcile_create_result = $account_reconcile_create->execute();

		if( ! $account_reconcile_create_result->success )
			Session::instance()->set('account_error_message',"An error occurred when recording that statement:<br>".$account_reconcile_create_result->auth_error.$account_reconcile_create_result->error);
		else
			Session::instance()->set('account_success_message',"Your statement was successfully reconciled.");

		$this->request->redirect('/accounts/view/'.$account_reconcile_create_data->account_id);
	}

	private function _fix_ofx($string)
	{
		$string = substr($string,stripos($string,'<OFX>'));
		$lines = preg_split("/\r\n|\n|\r/", $string);

		$lines_fixed = array();

		$elements = array();

		while( count($lines) )
		{
			$line = array_shift($lines);
			$element = (object)array(
				'tag' => substr($line,(strpos($line,'<') + 1),(strpos($line,'>') - (strpos($line,'<') + 1))),
				'value' => substr($line,(strpos($line,'>') + 1)),
			);

			if( strlen($element->value) )
				$lines_fixed[] = '<'.$element->tag.'>'.htmlentities($element->value).'</'.$element->tag.'>';
			else if( $element->tag )
				$lines_fixed[] = '<'.$element->tag.'>';
		}

		return implode($lines_fixed,"\n");
	}

}