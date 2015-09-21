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

// V2Item - Create a clear dump view layout.
class Controller_Docs extends Controller {

	private $_API_START = "---BEANSAPISPEC---";
	private $_OBJ_START = "---BEANSOBJSPEC---";
	private $_DELIMITER_END = "---BEANSENDSPEC---";

	private $_CONSTANT_API_PARAMETERS = array(
		'auth_uid' => array(
			'name' => 'auth_uid',
			'required' => TRUE,
			'description' => "INTEGER The ID for your API key.",
		),
		'auth_key' => array(
			'name' => 'auth_key',
			'required' => TRUE,
			'description' => "STRING The API key.",
		),
		'auth_expiration' => array(
			'name' => 'auth_expiration',
			'required' => TRUE,
			'description' => "INTEGER Unique ID tied to your key; changes if you reset your key.",
		),
	);

	protected $_actions = array();
	protected $_objects = array();

	public function action_index()
	{
		// V2Item - Return navigable tree based on JSON responses.
	}

	public function action_json()
	{
		$this->_build();

		$api = new stdClass;
		$api->actions = $this->_actions;
		$api->objects = $this->_objects;
		$api->actions_tree = array();
		$api->objects_tree = array();

		$done = FALSE;

		// Create keys / end-points for actions
		foreach( $this->_actions as $action )
		{
			$ref = &$api->actions_tree;

			foreach( explode('_',$action['name']) as $key )
			{
				if( ! isset($ref[$key]) )
					$ref[$key] = array(
						'part' => $key,
						'name' => FALSE,
						'children' => array(),
					);

				$ref = &$ref[$key]['children'];
			}
		}

		$api->actions_tree = $this->_build_tree_names($api->actions_tree);
		$api->actions_tree = $this->_remove_array_keys($api->actions_tree);
		
		// Create keys / end-points for objects
		foreach( $this->_objects as $object )
		{
			$ref = &$api->objects_tree;

			foreach( explode('_',$object['name']) as $key )
			{
				if( ! isset($ref[$key]) )
					$ref[$key] = array(
						'part' => $key,
						'name' => FALSE,
						'children' => array(),
					);

				$ref = &$ref[$key]['children'];
			}
		}

		$api->objects_tree = $this->_build_tree_names($api->objects_tree);
		$api->objects_tree = $this->_remove_array_keys($api->objects_tree);


		$this->response->body(json_encode($api));
		$this->response->headers('Content-Type', 'application/json');
		return $this->after();
	}

	private function _build()
	{
		$files = Kohana::list_files('classes/beans');
		$files[] = APPPATH.'classes/beans.php';

		$this->_search_files($files);
	}

	private function _search_files($files)
	{
		foreach( $files as $index => $item )
		{
			if( is_array($item) )
			{
				$this->_search_files($item);
			}
			else
			{
				$this->_handle_file($item);
			}
		}
	}

	private function _handle_file($file)
	{
		$lines = explode("\n", file_get_contents($file));
		$i = 0;

		while( $i < count($lines) )
		{
			$definitions = array();
			if( strpos($lines[$i], $this->_API_START) !== FALSE )
			{
				$i++;
				
				while( strpos($lines[$i], $this->_DELIMITER_END) === FALSE )
					$definitions[] = $lines[$i++];
				
				$api_spec = $this->_handle_api_spec($definitions);
				$this->_actions[$api_spec['name']] = $api_spec;
			}

			if( strpos($lines[$i], $this->_OBJ_START) !== FALSE )
			{
				$i++;
				
				while( strpos($lines[$i], $this->_DELIMITER_END) === FALSE )
					$definitions[] = $lines[$i++];

				$obj_spec = $this->_handle_obj_spec($definitions);
				$this->_objects[$obj_spec['name']] = $obj_spec;
			}

			$i++;
		}
	}

	private function _handle_api_spec($definitions)
	{
		$api_spec = array();
		$api_spec['name'] = FALSE;
		$api_spec['description'] = FALSE;
		$api_spec['deprecated'] = FALSE;
		$api_spec['parameters'] = array();
		$api_spec['returns'] = array();

		foreach( $definitions as $definitions_line )
		{
			$definition = trim($definitions_line);

			$this->_parse_references($definition);
			
			if( strpos($definition, '@action ') === 0 )
				$api_spec['name'] = str_replace('@action ', '', $definition);

			if( strpos($definition, '@deprecated ') === 0 )
				$api_spec['deprecated'] = str_replace('@deprecated ', '', $definition);

			if( strpos($definition, '@description ') === 0 )
				$api_spec['description'] = str_replace('@description ', '', $definition);

			if( strpos($definition, '@required ') === 0 )
			{
				$parameter = explode(' ',str_replace('@required ', '', $definition));

				if( strpos(implode(' ',$parameter),'@attribute ') === 0 )
				{
					$attribute = explode(' ',str_replace('@attribute ', '', implode(' ',$parameter)));

					$parameter_name = $attribute[0];

					if( ! isset($attribute[1]) )
						$attribute[1] = '';

					if( ! isset($attribute[2]) )
						$attribute[2] = '';

					$attribute_name = $attribute[1];

					$attribute_description = implode(' ',array_slice($attribute,2));

					$attribute_required = TRUE;
					
					if( ! isset($api_spec['parameters'][$parameter_name]) )
						$api_spec['parameters'][$parameter_name] = array(
							'name' => FALSE,
							'required' => FALSE,
							'description' => FALSE,
							'attributes' => array(),
						);

					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name] = array();
					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name]['name'] = $attribute_name;
					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name]['required'] = $attribute_required;
					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name]['description'] = $attribute_description;
				}
				else
				{
					$parameter_name = $parameter[0];
					
					if( ! isset($parameter[1]) )
						$parameter[1] = '';

					$parameter_description = implode(' ',array_slice($parameter,1));

					$parameter_required = TRUE;

					if( ! isset($api_spec['parameters'][$parameter_name]) )
						$api_spec['parameters'][$parameter_name] = array(
							'name' => FALSE,
							'required' => FALSE,
							'description' => FALSE,
							'attributes' => array(),
						);

					$api_spec['parameters'][$parameter_name]['name'] = $parameter_name;
					$api_spec['parameters'][$parameter_name]['required'] = $parameter_required;
					$api_spec['parameters'][$parameter_name]['description'] = $parameter_description;
				}
			}

			if( strpos($definition, '@optional ') === 0 )
			{
				$parameter = explode(' ',str_replace('@optional ', '', $definition));

				if( strpos(implode(' ',$parameter),'@attribute ') === 0 )
				{
					$attribute = explode(' ',str_replace('@attribute ', '', implode(' ',$parameter)));

					$parameter_name = $attribute[0];

					if( ! isset($attribute[1]) )
						$attribute[1] = '';

					if( ! isset($attribute[2]) )
						$attribute[2] = '';

					$attribute_name = $attribute[1];

					$attribute_description = implode(' ',array_slice($attribute,2));

					$attribute_required = FALSE;
					
					if( ! isset($api_spec['parameters'][$parameter_name]) )
						$api_spec['parameters'][$parameter_name] = array(
							'name' => FALSE,
							'required' => FALSE,
							'description' => FALSE,
							'attributes' => array(),
						);

					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name] = array();
					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name]['name'] = $attribute_name;
					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name]['required'] = $attribute_required;
					$api_spec['parameters'][$parameter_name]['attributes'][$attribute_name]['description'] = $attribute_description;
				}
				else
				{
					$parameter_name = $parameter[0];
					
					if( ! isset($parameter[1]) )
						$parameter[1] = '';

					$parameter_description = implode(' ',array_slice($parameter,1));

					$parameter_required = FALSE;

					if( ! isset($api_spec['parameters'][$parameter_name]) )
						$api_spec['parameters'][$parameter_name] = array(
							'name' => FALSE,
							'required' => FALSE,
							'description' => FALSE,
							'attributes' => array(),
						);

					$api_spec['parameters'][$parameter_name]['name'] = $parameter_name;
					$api_spec['parameters'][$parameter_name]['required'] = $parameter_required;
					$api_spec['parameters'][$parameter_name]['description'] = $parameter_description;
				}
			}

			if( strpos($definition, '@returns ') === 0 )
			{
				$returns = explode(' ',str_replace('@returns ', '', $definition));

				if( strpos(implode(' ',$returns),'@attribute ') === 0 )
				{
					$attribute = explode(' ',str_replace('@attribute ', '', implode(' ',$returns)));

					$return_name = $attribute[0];

					if( ! isset($attribute[1]) )
						$attribute[1] = '';

					if( ! isset($attribute[2]) )
						$attribute[2] = '';

					$attribute_name = $attribute[1];

					$attribute_description = implode(' ',array_slice($attribute,2));

					if( ! isset($api_spec['returns'][$return_name]) )
						$api_spec['returns'][$return_name] = array(
							'name' => FALSE,
							'description' => FALSE,
							'attributes' => array(),
						);

					$api_spec['returns'][$return_name]['attributes'][$attribute_name] = array();
					$api_spec['returns'][$return_name]['attributes'][$attribute_name]['name'] = $attribute_name;
					$api_spec['returns'][$return_name]['attributes'][$attribute_name]['description'] = $attribute_description;
				}
				else
				{
					$return_name = $returns[0];
					
					if( ! isset($returns[1]) )
						$returns[1] = '';

					$parameter_description = implode(' ',array_slice($returns,1));


					if( ! isset($api_spec['returns'][$return_name]) )
						$api_spec['returns'][$return_name] = array(
							'name' => FALSE,
							'description' => FALSE,
							'attributes' => array(),
						);

					$api_spec['returns'][$return_name]['name'] = $return_name;
					$api_spec['returns'][$return_name]['description'] = $parameter_description;
				}
			}
		}
		
		foreach( $api_spec['parameters'] as $key => $parameter )
		{
			if( isset($this->_CONSTANT_API_PARAMETERS[$key]) )
			{
				$api_spec['parameters'][$key] = $this->_CONSTANT_API_PARAMETERS[$key];
			}
		}

		$api_spec['parameters'] = $this->_remove_array_keys($api_spec['parameters']);
		$api_spec['returns'] = $this->_remove_array_keys($api_spec['returns']);

		return $api_spec;
	}

	private function _handle_obj_spec($definitions)
	{
		$obj_spec = array();
		$obj_spec['name'] = FALSE;
		$obj_spec['description'] = FALSE;
		$obj_spec['attributes'] = array();

		foreach( $definitions as $definitions_line )
		{
			$definition = trim($definitions_line);

			$this->_parse_references($definition);
			
			if( strpos($definition, '@object ') === 0 )
				$obj_spec['name'] = str_replace('@object ', '', $definition);

			if( strpos($definition, '@description ') === 0 )
				$obj_spec['description'] = str_replace('@description ', '', $definition);

			if( strpos($definition, '@attribute ') === 0 )
			{
				$attribute = explode(' ',str_replace('@attribute ', '', $definition));

				$attribute_name = $attribute[0];

				if( ! isset($attribute[1]) )
					$attribute[1] = '';

				$attribute_description = implode(' ',array_slice($attribute,1));

				$obj_spec['attributes'][$attribute_name] = array();
				$obj_spec['attributes'][$attribute_name]['name'] = $attribute_name;
				$obj_spec['attributes'][$attribute_name]['description'] = $attribute_description;
			}
		}

		$obj_spec['attributes'] = $this->_remove_array_keys($obj_spec['attributes']);

		return $obj_spec;
	}

	private function _parse_references($line)
	{
		$this->_parse_action_names($line);
		$this->_parse_object_names($line);
	}

	// Return Array of Actions
	private function _parse_action_names($line)
	{
		$actions = $this->_parse_delimited_string($line,'!');
		foreach( $actions as $action )
		{
			if( ! isset($this->_actions[$action]) )
				$this->_actions[$action] = FALSE;
		}
	}

	// Return Array of Objects
	private function _parse_object_names($line)
	{
		$objects = $this->_parse_delimited_string($line,'#');
		foreach( $objects as $object )
		{
			if( ! isset($this->_objects[$object]) )
				$this->_objects[$object] = FALSE;
		}
	}

	private function _parse_delimited_string($line, $delimiter)
	{
		$return_array = array();
		$i = 0;
		while( $i < strlen($line) )
		{
			if( $line[$i] == $delimiter )
			{
				$buffer = '';
				$i++;
				while( isset($line[$i]) AND $line[$i] != $delimiter )
					$buffer .= $line[$i++];

				if( ! isset($line[$i]) )
					die("MISSING END DELIMITER: <br>\n".$lines);

				$return_array[] = $buffer;
			}

			$i++;
		}

		return $return_array;
	}

	private function _build_tree_names($children,$parent_name = "")
	{
		foreach( $children as $index => $child )
		{
			$children[$index]['name'] = $parent_name.$index;
			
			if( $child['children'] )
			{
				$children[$index]['children'] = $this->_build_tree_names($child['children'],$children[$index]['name'].'_');
			}
		}

		return $children;
	}

	private function _remove_array_keys($items)
	{
		$return_array = array();

		foreach( $items as $item )
		{
			if( isset($item['children']) )
			{
				$item['children'] = $this->_remove_array_keys($item['children']);
			}

			foreach( $item as $key => $value ) 
			{
				if( is_array($value) )
				{
					$item[$key] = $this->_remove_array_keys($value);
				}
			}

			$return_array[] = $item;
		}

		return $return_array;
	}

}