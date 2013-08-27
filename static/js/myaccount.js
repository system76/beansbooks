/**
 * Javascript for pages related to myaccount/
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