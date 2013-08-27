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
@action Beans_Vendor_Purchase_Line_Search
@description Search for descriptions of previously entered line items.  Items are returned based on the levenshtein difference to the submitted phrase.
@required auth_uid
@required auth_key
@required auth_expiration
@optional search_description STRING The generic query to search descriptions for.
@returns purchase_lines ARRAY An array of objects representing a #Beans_Vendor_Purchase_Line# with the most recently used information.
@returns @attribute purchase_lines description The description of the line item.
@returns @attribute purchase_lines amount The most recent amount assigned to that line item.
@returns @attribute purchase_lines quantity The most recent quantity assigned to that line item.
@returns @attribute purchase_lines total The total based on the amount and quantity.
@returns @attribute purchase_lines account_id The most recent account attributed to that line item.
---BEANSENDSPEC---
*/
class Beans_Vendor_Purchase_Line_Search extends Beans_Vendor_Purchase_Line {

	private $_search_description;


	public function __construct($data = NULL)
	{
		parent::__construct($data);
		
		$this->_search_description = isset($data->search_description)
								   ? strtolower($data->search_description)
								   : FALSE;

	}

	protected function _execute()
	{
		// SELECT DISTINCT DESCRIPTIONS FIRST.
		$description_query = 'SELECT form_lines.description as description FROM form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id ';
		$description_query .= ' WHERE forms.type = "purchase" ';
		
		if( $this->_search_description )
			foreach( explode(' ',$this->_search_description) as $search_description_term )
				if( trim($search_description_term) )
					$description_query .= ' AND form_lines.description LIKE "%'.$search_description_term.'%" ';

		$description_query .= ' GROUP BY form_lines.description LIMIT 10';

		$description_results = DB::Query(Database::SELECT,$description_query)->execute()->as_array();

		$purchase_lines = array();
		foreach( $description_results as $description_result )
		{
			$line_query =	'SELECT form_lines.account_id as account_id, form_lines.amount as amount, form_lines.quantity as quantity, form_lines.total as total FROM form_lines RIGHT JOIN forms ON form_lines.form_id = forms.id ';
			$line_query .=	' WHERE form_lines.description = "'.$description_result['description'].'" ';
			$line_query .=	' AND forms.type = "purchase" ';
			$line_query .=	' AND form_lines.amount >= 0 ';
			$line_query .=	' ORDER BY forms.date_created DESC LIMIT 1';

			$line_result = DB::Query(Database::SELECT,$line_query)->execute()->as_array();

			$purchase_lines[] = (object)array(
				'description' => $description_result['description'],
				'amount' => $line_result[0]['amount'],
				'quantity' => $line_result[0]['quantity'],
				'total' => $line_result[0]['total'],
				'account_id' => $line_result[0]['account_id'],
			);
		}

		usort($purchase_lines,array(
			$this,
			'_sort_lines_by_description',
		));
		
		return (object)array(
			"purchase_lines" => $purchase_lines,
		);
	}

	private function _sort_lines_by_description($a,$b)
	{
		if( ! $this->_search_description )
		{
			if( $a->description == $b->description )
				return 0;

			return ( strcmp($a->description,$b->description) <= 0 ? -1 : 1 );
		}
		else
		{
			if( $a->description == $b->description )
				return 0;

			if( $a->description == substr($this->_search_description,0,strlen($a->description)) AND 
				$b->description != substr($this->_search_description,0,strlen($b->description)) )
			{
				return -1;
			}
			else if( $a->description != substr($this->_search_description,0,strlen($a->description)) AND 
					 $b->description == substr($this->_search_description,0,strlen($b->description)) )
			{
				return 1;
			}
			else if( $a->description == substr($this->_search_description,0,strlen($a->description)) AND 
					 $b->description == substr($this->_search_description,0,strlen($b->description)) )
			{
				return 0;
			}

			return ( levenshtein(strtolower($a->description), $this->_search_description) < levenshtein(strtolower($b->description), $this->_search_description) ? -1 : 1 );
		}
	}

}