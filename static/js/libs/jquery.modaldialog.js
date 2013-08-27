// jquery-ui.modaldialog.js
/**
 * Creates a jQuery UI Dialog with specific overrides to fit the Beans application.
 * @author David Overcash <david@system76.com>,<funnylookinhat@gmail.com>
 */
(function( $ ){

	$.fn.modaldialog = function(options) {  

		// Create some defaults, extending them with any options that were provided
		var settings = $.extend( {
			modal: true,
			dialogClass: "generated-modal-dialog",
			resizable: false,
			draggable: false,
			position: ['center',200],
			buttons: { "Ok": function() { $(this).dialog("close"); } },
			width: 460
		}, options);

		// Realistically, modal, resizeable, and draggable aren't optional.
		// If you don't want them, then use .dialog()
		settings.modal = true;
		settings.resizable = false;
		settings.draggable = false;

		return this.each(function() { 
			$(this).dialog(settings);
		});

	};
})( jQuery );