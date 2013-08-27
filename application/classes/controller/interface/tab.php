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

class Controller_Interface_Tab extends Controller_Interface {

	public function action_remove()
	{
		$tab_url = $this->request->post('url');

		if( ! $tab_url )
			$this->_return_error("No tab URL was provided.");

		$tab_links = Session::instance()->get('tab_links');

		$remove_index = FALSE;

		foreach( $tab_links as $index => $tab_link )
			if( $tab_link['url'] == $tab_url )
				$remove_index = $index;

		if( $remove_index === FALSE )
			$this->_return_error("That tab URL was not found.");

		if( ! $tab_links[$remove_index]['removable'] )
			$this->_return_error("That tab cannot be removed.");

		$new_tab_links = Helper_Mustache::RemoveArrayIndex($tab_links,$remove_index);
		
		Session::instance()->set('tab_links',$new_tab_links);
	}

}