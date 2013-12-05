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
 * Defined Web Routes for Delivery
 * Standard path is:
 * 		controller / action / id / code 
 */

Route::set('api','api(/<api_path_1>(/<api_path_2>(/<api_path_3>(/<api_path_4>(/<api_path_5>(/<api_path_6>))))))')
	->defaults(array(
		'controller'	=> 'api',
		'action'		=> 'execute',
	));

Route::set('install','install(/<action>(/<id>))')
	->defaults(array(
		'controller'	=> 'install',
		'action'		=> 'index'
	));

Route::set('update','update(/<action>(/<id>))')
	->defaults(array(
		'controller'	=> 'update',
		'action'		=> 'index'
	));

Route::set('interface-tab','interface/tab(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'interface_tab',
		'action'		=> 'index',
	));

Route::set('interface','interface(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'interface',
		'action'		=> 'index',
	));

// Print
Route::set('print','print(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'print',
		'action'		=> 'index',
	));

// Dash
Route::set('dash-json','dash/json(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'dash_json',
		'action'		=> 'index',
	));

Route::set('dash','dash(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'dash',
		'action'		=> 'index',
	));

// Accounts
Route::set('accounts-json','accounts/json(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'accounts_json',
		'action'		=> 'index',
	));

Route::set('accounts','accounts(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'accounts',
		'action'		=> 'index',
	));

// Customers
Route::set('customers-json','customers/json(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'customers_json',
		'action'		=> 'index',
	));

Route::set('customers','customers(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'customers',
		'action'		=> 'index',
	));

// Vendors
Route::set('vendors-json','vendors/json(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'vendors_json',
		'action'		=> 'index',
	));

Route::set('vendors','vendors(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'vendors',
		'action'		=> 'index',
	));

// Setup
Route::set('setup-json','setup/json(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'setup_json',
		'action'		=> 'index',
	));

Route::set('setup','setup(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'setup',
		'action'		=> 'index',
	));

// My Account
Route::set('myaccount','myaccount(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'myaccount',
		'action'		=> 'index',
	));

// Exception
Route::set('exception', 'exception(/<action>(/<code>(/<message>)))')
	->defaults(array(
		'controller' => 'exception',
		'action'	 => 'thrown',
	));

// Auth
Route::set('auth','auth(/<action>(/<id>(/<code>)))')
	->defaults(array(
		'controller'	=> 'auth',
		'action'		=> 'index',
	));

// Any specific routes should go above this - but this should catch most 
// use cases for testing purposes, etc.
Route::set('basicroute', '(<controller>(/<action>(/<id>(/<code>))))')
	->defaults(array(
		'controller'	=> 'auth',
		'action'		=> 'index',
	));
