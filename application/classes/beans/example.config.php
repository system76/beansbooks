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

return array
	(
	'sha_hash' => 'INSERT_STRONG_KEY',
	'sha_salt' => 'INSERT_STRONG_KEY',
	'cookie_salt' => 'INSERT_STRONG_KEY',
	'modules' => array
	(
		/**
		 * Kohana Encryption Module - Used for Database Encryption.
		 * 
		 * The following options must be set:
		 *
		 * string   key     secret passphrase
		 * integer  mode    encryption mode, one of MCRYPT_MODE_*
		 * integer  cipher  encryption cipher, one of the Mcrpyt cipher constants
		 */
		'encrypt' => array
		(
			'default' => array(
				'key'	 => "INSERT_STRONG_KEY",
				'cipher' => MCRYPT_RIJNDAEL_128,
				'mode'   => MCRYPT_MODE_NOFB,
			),
		),
		/**
		 * Kohana Database Module Configuration - 
		 * 
		 * The following options are available for MySQL:
		 *
		 * string   hostname     server hostname, or socket
		 * string   database     database name
		 * string   username     database username
		 * string   password     database password
		 * boolean  persistent   use persistent connections?
		 * array    variables    system variables as "key => value" pairs
		 *
		 * Ports and sockets may be appended to the hostname.
		 *
		 * For additional configuration options please look up the Database Module documentation here:
		 * http://kohanaframework.org/3.2/guide/database/config
		 */
		'database' => array
		(
			'default' => array
			(
				'type'       => 'mysql',
				'connection' => array(
					'hostname'   => 'localhost',
					'database'   => 'beans',
					'username'   => 'beans',
					'password'   => 'beans',
					'persistent' => FALSE,
				),
				'table_prefix' => '',
				'charset'      => 'utf8',
				'caching'      => FALSE,
				'profiling'    => TRUE,
			),
		),
		/**
		 * SwiftMailer driver, used with the email module.
		 *
		 * Valid drivers are: native, sendmail, smtp
		 * 
		 * To use secure connections with SMTP, set "port" to 465 instead of 25.
		 * To enable TLS, set "encryption" to "tls".
		 * 
		 * Note for SMTP, 'auth' key no longer exists as it did in 2.3.x helper
		 * Simply specifying a username and password is enough for all normal auth methods
		 * as they are autodeteccted in Swiftmailer 4
		 * 
		 * PopB4Smtp is not supported in this module as I had no way to test it but 
		 * SwiftMailer 4 does have a PopBeforeSMTP plugin so it shouldn't be hard to implement
		 * 
		 * Encryption can be one of 'ssl' or 'tls' (both require non-default PHP extensions
		 *
		 * Driver options:
		 * @param   null    native: no options
		 * @param   string  sendmail: executable path, with -bs or equivalent attached
		 * @param   array   smtp: hostname, (username), (password), (port), (encryption)
		 */
		'email' => array
		(
			'driver' => 'smtp',
			'options'	=> array(
				'hostname'	=>	'smtp.myemailservice.com',
				'username'	=>	'my_email_username',
				'password'	=>	'my_email_password',
				'port'		=>	'my_email_port',
				'encryption'=>	'ssl'
			)
		),
	),
);