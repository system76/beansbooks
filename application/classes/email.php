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

class Email extends Email_Core {

	/**
	 * Send a Swift_Message that is already formatted.
	 * @param  Swift_Message $message 
	 * @return BOOLEAN
	 */
	public static function sendMessage(Swift_Message $message)
	{
		// Connect to SwiftMailer
		(self::$_mail === NULL) AND self::connect();

		try
		{
			return self::$_mail -> send($message);
		}
		catch (Swift_SwiftException $e)
		{
			// Throw Kohana Http Exception
			throw new Http_Exception_408('Connecting to mailserver timed out: :message', array(':message' => $e -> getMessage()));
		}
	}
}