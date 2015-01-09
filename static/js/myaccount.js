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

	$('.myaccount-index-save').click(function(e) {
		e.preventDefault();
		showPleaseWait();
		$(this).closest('form').submit();
	});

	$('.myaccount-index-cancel').click(function(e) {
		e.preventDefault();
		window.location.reload();
	});

});