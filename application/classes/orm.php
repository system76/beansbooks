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

class ORM extends Kohana_ORM {

	public function count_all($column = NULL)
	{
		$selects = array();

		foreach ($this->_db_pending as $key => $method)
		{
			if ($method['name'] == 'select')
			{
				// Ignore any selected columns for now
				$selects[] = $method;
				unset($this->_db_pending[$key]);
			}
		}

		if ( ! empty($this->_load_with))
		{
			foreach ($this->_load_with as $alias)
			{
				// Bind relationship
				$this->with($alias);
			}
		}

		$this->_build(Database::SELECT);
		
		$records = $this->_db_builder->from(array($this->_table_name, $this->_object_name))
			->select(array('COUNT('.( $this->_db_builder->_distinct ? 'DISTINCT ' : '' ).'"'.( $column ? $column : '*' ).'")', 'records_found'))
			->execute($this->_db)
			->get('records_found');

		// Add back in selected columns
		$this->_db_pending += $selects;

		$this->reset();

		// Return the total number of records in a table
		return $records;
	}

}

