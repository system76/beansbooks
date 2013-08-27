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

class Helper_Mustache {

	/**
	 * Removes an index from an array that preserves Mustache compatibility.
	 * @param Array $array
	 * @param int $index 
	 * @return Array Array with index removed ( indeces shifted appropriatelY )
	 */
	public static function RemoveArrayIndex($array,$index)
	{
		return array_merge(
			array_slice($array,0,$index),
			( 
				count($array) > ( $index + 1 )
				? array_slice($array,($index+1)) 
				: array()
			)
		);
	}
}