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

class Beans_Auth_Reset extends Beans_Auth {

	protected $_auth_role_perm = FALSE;		// Make sure this is available to any user ( not logged in ).

	protected $_data;

	/**
	 * Attempt to login and establish session authentication.
	 */
	public function __construct($data = NULL)
	{
		parent::__construct($data);

		$this->_data = $data;
	}

	// This is a bit non-standard but it fits two sides of the same coin.
	protected function _execute()
	{
		if( ! isset($this->_data->email) )
			throw new Exception("Please provide a valid email address.");

		$user = ORM::Factory('user')->where('email','LIKE',$this->_data->email)->find();

		if( ! $user->loaded() )
			throw new Exception("Login error: that email address was not found.");

		if( isset($this->_data->resetkey) &&
			strlen($this->_data->resetkey) )
		{
			if( ! isset($this->_data->password) OR
				! strlen($this->_data->password) )
				throw new Exception("Please provide a valid password.");

			if( $user->reset != $this->_data->resetkey )
				throw new Exception("Invalid reset key.  Please try sending the email again.");

			if( $user->reset_expiration < time() )
				throw new Exception("Reset key expired.  Please try sending the email again.");

			$user->reset = NULL;
			$user->reset_expiration = NULL;
			$user->password_change = FALSE;
			$user->password = $this->_beans_auth_password($user->id,$this->_data->password);
			
			// And auto-login...
			$expiration = ( $user->role->auth_expiration_length != 0 )
						? ( time() + $user->role->auth_expiration_length )
						: rand(11111,99999);						// Generate a random for salt.

			$user->auth_expiration = $expiration;
			$user->save();

			return (object)array(
				"auth" => $this->_return_auth_element($user,$expiration),
			);
		}
		else
		{
			// Generate Key
			$user->reset = $this->_generate_reset($user->id);
			$user->reset_expiration = time() + ( 10 * 60 );
			$user->save();

			// This is the one email we send from within the app for security.
			$auth_print_reset = new View_Auth_Print_Reset;
			$auth_print_reset->user = $user;

			$message = Swift_Message::newInstance();
			
			$message->setSubject('BeansBooks Password Reset')->
				setFrom(array(( $this->_beans_setting_get('company_email') ? $this->_beans_setting_get('company_email') : 'no-reply@beansbooks.com' )))->
				setTo(array($user->email));
			
			$auth_print_reset->swift_email_message = $message;

			$message = $auth_print_reset->render();
			
			try
			{
				if( ! Email::connect() ) 
					throw new Exception ("Could not send email. Does your config have correct email settings?");

				if( ! Email::sendMessage($message) )
					throw new Exception ("Could not send email. Does your config have correct email settings?");
			}
			catch( Exception $e )
			{
				throw new Exception ("An error occurred when sending the email: have you setup email properly in config.php?");
			}
		}

		return (object)array();
	}
}