/**
 * Javascript for pages related to auth/
 */

$(function() {

	$('.auth-login-submit').click(function(e) {
		e.preventDefault();
		showPleaseWait();
		$(this).closest('form').submit();
	});

	$('.login-form input').live('keyup',function(e) {
		var code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			e.preventDefault();
			showPleaseWait();
			$(this).closest('form').submit();
		}
	});
});