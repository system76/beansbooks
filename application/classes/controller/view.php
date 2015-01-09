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

/**
 * KOstache view builder.
 * Thanks to IRC/Kohana zombor for the idea @ https://github.com/vendo/core/blob/develop/classes/controller.php
 */
class Controller_View extends Controller {

	// We inflate the view here.
	protected $_view;
	protected $_view_class;

	// These must be defined within each action that you want to be added to the tab.
	protected $_action_tab_name = FALSE;
	protected $_action_tab_uri = FALSE;

	public function before()
	{
		parent::before();

		// Check for stdClass in after() in case we don't find view class.
		// This enables actions to just check data into the _view and assume it will be there.
		// Rather than if( $this->_view ) then $this->_view = whatever;
		$this->_view = new stdClass;

		$this->_view_class = "view_".$this->request->controller().'_'.$this->request->action();

		if( Kohana::find_file('classes', strtolower(str_replace('_','/',$this->_view_class))) )
			$this->_view = new $this->_view_class;

		// Used for controlling what tabs are shown.
		if( Session::instance()->get('tab_section') !== $this->request->controller() )
			Session::instance()->delete('tab_section');

		// Assign some basic variables that are used by the template class.
		$this->_view->request = $this->request;

	}

	public function after()
	{
		// Make sure we've queried the default data.
		$this->_beans_default_calls();

		if( $this->_action_tab_name AND 
			$this->_action_tab_uri )
		{
			// Make sure that the uri starts with a slash.
			$this->_action_tab_uri = ( substr($this->_action_tab_uri,0,1) == "/" )
								   ? $this->_action_tab_uri
								   : '/'.$this->_action_tab_uri;
			$tab_links = Session::instance()->get('tab_links');
			$new_tab = TRUE;
			foreach( $tab_links as $tab_link )
				if( $tab_link['url'] == $this->_action_tab_uri )
					$new_tab = FALSE;

			if( $new_tab )
			{
				$tab_links[] = array(
					'url' => $this->_action_tab_uri,
					'text' => $this->_action_tab_name,
					'removable' => TRUE,
				);

				Session::instance()->set('tab_links',$tab_links);
			}
		}

		if( Session::instance()->get('global_error_message') ) {
			$this->_view->send_error_message(Session::instance()->get('global_error_message'));
			Session::instance()->delete('global_error_message');
		}
		
		if( Session::instance()->get('global_success_message') ) {
			$this->_view->send_success_message(Session::instance()->get('global_success_message'));
			Session::instance()->delete('global_success_message');
		}
		
		if( get_class($this->_view) != "stdClass" )
			$this->response->body($this->_view->render());
		else
			$this->response->body('Error! Could not find view class: '.$this->_view_class);

		// Append debug data in case we're in development mode.
		if( Kohana::$environment == Kohana::DEVELOPMENT )
			$this->response->body($this->response->body().'<div class="wrapper"><br><br><br><br><br><br><hr><hr><hr><br><br><br><br><br><br><h1>Debug Output:</h1><br>'.View::factory('profiler/stats')->render().'<br><br><br><br><br><br></div>');
	}

	/**
	 * Check a result for an error - either auth or data related, and handle appropriately.
	 * @param  stdClass $result 
	 * @return BOOLEAN
	 */
	protected function _beans_result_check($result)
	{
		if( ! $result->success )
		{
			// As of right now we're checking what sort of auth error based on expected
			// error messages.
			// If the message contains "credentials" then the user key is incorrect or invalid.
			// However - if it contains "permission" then it's simply a lack of access.
			if( isset($result->auth_error) AND
				strlen($result->auth_error) )
			{
				if( strpos($result->auth_error,"credential") !== FALSE )
				{
					Session::instance()->destroy();
					$this->request->redirect('/');
				}
				else
					$this->_view->send_error_message($result->auth_error);
			}
			else if( isset($result->config_error) AND
				strlen($result->config_error) )
			{
				// In the case of a config_error - the user must be logged out.
				Session::instance()->destroy();
				//Session::instance()->set('global_error_message',$result->config_error);
				$this->request->redirect('/');
			}
			else 
			{
				$this->_view->send_error_message($result->error);
			}
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Adds a few Beans_ calls to each view ( such as company settings ).
	 */
	protected function _beans_default_calls()
	{
		// Only if logged in.
		if( ! strlen(Session::instance()->get('auth_uid')) OR 
			! strlen(Session::instance()->get('auth_key')) OR 
			! strlen(Session::instance()->get('auth_expiration')) )
			return FALSE;
		
		$setup_company_list = new Beans_Setup_Company_List($this->_beans_data_auth());
		$setup_company_list_result = $setup_company_list->execute();

		if( $this->_beans_result_check($setup_company_list_result) )
			$this->_view->setup_company_list_result = $setup_company_list_result;
		
		$account_chart = new Beans_Account_Chart($this->_beans_data_auth());
		$account_chart_result = $account_chart->execute();
		
		if( $this->_beans_result_check($account_chart_result) )
			$this->_view->account_chart_result = $account_chart_result;

		$account_type_search = new Beans_Account_Type_Search($this->_beans_data_auth());
		$account_type_search_result = $account_type_search->execute();

		if( $this->_beans_result_check($account_type_search_result) )
			$this->_view->account_type_search_result = $account_type_search_result;

		// We don't want to override something set elsewhere - like in Controller_View_Setup -> action_taxes()
		if( ! isset($this->_view->tax_search_result) )
		{
			$tax_search = new Beans_Tax_Search($this->_beans_data_auth((object)array(
				'search_include_hidden' => TRUE,
			)));
			$tax_search_result = $tax_search->execute();

			if( $this->_beans_result_check($tax_search_result) )
				$this->_view->tax_search_result = $tax_search_result;
		}
	}

}
