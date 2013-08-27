// EZPZ Hint v1.1.1; Copyright (c) 2009 Mike Enriquez, http://theezpzway.com; Released under the MIT License
(function($){
	$.fn.ezpz_hint = function(options){
		if( ! $.browser.msie ) {
			return;
		}
		var defaults = {
			hintClass: 'ezpz-hint',
			hintName: 'ezpz_hint_dummy_input'
		};
		var settings = $.extend(defaults, options);
		
		return this.each(function(i){
			var id = settings.hintName + '_' + i;
			var hint;
			var dummy_input;
			
			// grab the input's placeholder attribute
			text = $(this).attr('placeholder');
			
			// create a dummy input and place it before the input
			$('<input type="text" id="' + id + '" value="" />')
				.insertBefore($(this));
			
			// set the dummy input's attributes
			hint = $(this).prev('input:first');
			hint.attr('class', $(this).attr('class'));
			hint.attr('size', $(this).attr('size'));
			hint.attr('autocomplete', 'off');
			hint.attr('tabIndex', $(this).attr('tabIndex'));
			hint.addClass(settings.hintClass);
			hint.val(text);
			
			// hide the input
			$(this).hide();
			
			// don't allow autocomplete (sorry, no remember password)
			$(this).attr('autocomplete', 'off');
			
			// bind focus event on the dummy input to swap with the real input
			hint.focus(function(){
				dummy_input = $(this);
				$(this).next('input:first').show();
				$(this).next('input:first').focus();
				$(this).next('input:first').unbind('blur').blur(function(){
					if ($(this).val() == '') {
						$(this).hide();
						dummy_input.show();
					}
				});
				$(this).hide();
			});
			
			// swap if there is a default value
			if ($(this).val() != ''){
				hint.focus();
			};
		});
		
	};
})(jQuery);