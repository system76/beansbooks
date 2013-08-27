$(function() {
	/**
	 * Install Process JS
	 */
	
	 $('a.process-step').live('click',function(e) {
	 	e.preventDefault();
	 	showPleaseWait();
	 	$(this).closest('form').submit();
	 });

});