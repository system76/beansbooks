<?php defined('SYSPATH') or die('No direct script access.');

/**
* Customer Kohana Exception Handler
* Will enable pretty errors for non-dev environments and clean
* exits when a user does not have access to a function.
*/

class Kohana_Exception extends Kohana_Kohana_Exception {

	public static function handler(Exception $e)
	{
		if( Kohana::$environment == Kohana::DEVELOPMENT )
			return parent::handler($e);

		try
		{
			$params = array(
				'action' => rawurlencode('thrown'),
				'code' => base64_encode(( $e instanceof HTTP_Exception ) ? $e->getCode() : 500),
				'message' => base64_encode($e->getMessage()),
			);

			die(Request::factory( Route::get('exception')->uri($params) )->
				execute()->
				send_headers()->
				body()
			);
		}
		// If - by some chance - an error occurs while rendering the error view.
		catch( Exception $e2 )
		{
			ob_clean();
			die("A fatal error has occurred: ".parent::text($e2));
		}
	}
}