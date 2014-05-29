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

abstract class Database extends Kohana_Database {

	/**
	 * Quote a database column name and add the table prefix if needed.
	 *
	 *     $column = $db->quote_column($column);
	 *
	 * You can also use SQL methods within identifiers.
	 *
	 *     // The value of "column" will be quoted
	 *     $column = $db->quote_column('COUNT("column")');
	 *
	 * Objects passed to this function will be converted to strings.
	 * [Database_Expression] objects will be compiled.
	 * [Database_Query] objects will be compiled and converted to a sub-query.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed   $column  column name or array(column, alias)
	 * @return  string
	 * @uses    Database::quote_identifier
	 * @uses    Database::table_prefix
	 */
	public function quote_column($column)
	{
		if (is_array($column))
		{
			list($column, $alias) = $column;
		}

		if ($column instanceof Database_Query)
		{
			// Create a sub-query
			$column = '('.$column->compile($this).')';
		}
		elseif ($column instanceof Database_Expression)
		{
			// Compile the expression
			$column = $column->compile($this);
		}
		else
		{
			// Convert to a string
			$column = (string) $column;

			if ($column === '*')
			{
				return $column;
			}
			elseif (strpos($column, '"') !== FALSE)
			{
				// Quote the column in FUNC("column") identifiers
				$column = @preg_replace('/"(.+?)"/e', '$this->quote_column("$1")', $column);
			}
			elseif (strpos($column, '.') !== FALSE)
			{
				$parts = explode('.', $column);

				if ($prefix = $this->table_prefix())
				{
					// Get the offset of the table name, 2nd-to-last part
					$offset = count($parts) - 2;

					// Add the table prefix to the table name
					$parts[$offset] = $prefix.$parts[$offset];
				}

				foreach ($parts as & $part)
				{
					if ($part !== '*')
					{
						// Quote each of the parts
						$part = $this->_identifier.$part.$this->_identifier;
					}
				}

				$column = implode('.', $parts);
			}
			else
			{
				$column = $this->_identifier.$column.$this->_identifier;
			}
		}

		if (isset($alias))
		{
			$column .= ' AS '.$this->_identifier.$alias.$this->_identifier;
		}

		return $column;
	}
}