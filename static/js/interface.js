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

$(function() {

	// Tab [x]
	$('.tabs li a.remove').click(function() {
		var tab = $(this).closest('li');
		$.post(
			'/interface/tab/remove',
			{
				url: $(this).closest('li').find('a.tab').attr('href'),
			},
			function(data) {
				// We want to perform the transition even if there is an error. i.e. Vendor and Customer Detail
				tab.fadeOut(function() {
					if( tab.hasClass('current') ) {
						if( tab.next().is('li') ) {
							window.location.href = tab.next().find('a.tab').attr('href');
						} else {
							window.location.href = tab.prev().find('a.tab').attr('href');
						}
					} else {
						tab.remove();
					}
				});
			},
			'json'
		);
	});

});