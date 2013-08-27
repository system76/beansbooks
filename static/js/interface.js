/**
 * interface.js
 * Adjusts interface and works with interface controller ( if necessary )
 * @author David Overcash <david@system76.com>,<funnylookinhat@gmail.com>
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
				if( ! data.success )
					showError(data.error);
				else
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