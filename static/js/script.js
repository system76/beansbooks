var GLOBAL_EDIT_FORM_ACTIVE = false;

$(function(){
	/**
	 * Links - Don't react to disabled links.
	 */
	$('a').live('click',function(e) {
		if( $(this).attr('disabled') &&
			$(this).attr('disabled').length > 0 ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			e.stopPropagation();
			return false;
		}
		return true;
	});

	/*
	TODO - Add check for links going off of the current page ( i.e. lost form progress )? 
	 */
	$('a').live('click', function (e) {
		if( $(this).attr('href') &&
			$(this).attr('href').length > 0 &&
			$(this).attr('href') != "#" &&
			GLOBAL_EDIT_FORM_ACTIVE ) {
			if( ! confirm("Are you sure?  Your changes will be lost.") ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				e.stopPropagation();
				return false;
			}
		}
		return true;
	});

	/*
	$('a.form-cancel, a.payment-cancel').live('click', function (e) {
		if( ! confirm("Are you sure? You will lose all of the currently entered information.") ) {
			e.preventDefault();
		}
	});
	*/

	$('.green-select select').live('click',function(e) {
		if( (
				$(this).attr('disabled') &&
				$(this).attr('disabled').length > 0 
			) ||
			(
				$(this).closest('.green-select').attr('disabled') &&
				$(this).closest('.green-select').attr('disabled').length > 0 
			) ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			e.stopPropagation();
			return false;
		}
		return true;
	});

	$('.select select').live('focus',function() {
		$(this).closest('div.select').addClass('focus');
	}).live('blur',function() {
		$(this).closest('div.select').removeClass('focus');
	});

	if( $.browser.msie ) {
		$('.green-select select option:selected').each(function() {
			$(this).css('color','#ffffff');
		});

		$('.green-select select').live('change',function() {
			selectOptionUpdate($(this));
			return true;
		});
	}

	$('input[type="text"].search').live('focus',function() {
		$(this).addClass('active');
	}).live('blur',function() {
		if( $(this).val().length == 0 ) {
			$(this).removeClass('active');
		}
	});

	$('input[type="file"]').each(function() {
		filechooserUpdate($(this));
	});

	$('input[type="file"]').live('change',function() {
		filechooserUpdate($(this));
	});

	$('.file .path,.file .button').live('click',function(e) {
		e.preventDefault();
		$(this).closest('.file').find('input[type="file"]').click();
		return false;
	});

	$('.checkbox input[type="checkbox"]').each(function() {
		checkboxUpdate($(this));
	});

	$('.checkbox input[type="checkbox"]').live('focus', function() {
		$(this).closest('.checkbox').addClass('focus');
	}).live('blur' , function() {
		$(this).closest('.checkbox').removeClass('focus');
	}).live('keyup', function (e) {
		var code = (e.keyCode ? e.keyCode : e.which);
		if( code == 32 ) {
			$(this).click();
		}
	});

	$('.checkbox:not(.manual)').live('click',function() {
		var checkbox = $(this).find(':checkbox');
		if( checkbox.is(':disabled') ) {
			return;
		}
		if( checkbox.is(':checked') ) {
			checkbox.prop('checked',false);
		} else {
			checkbox.prop('checked',true);
		}
		checkboxUpdate(checkbox);
	});

	$('.select-checkbox .value').live('click',function() {
		$dropdown =  $(this).closest('.select-checkbox').find('.dropdown');
		if( $dropdown.is(':visible') ) {
			$dropdown.hide();
		} else {
			$dropdown.show();
		}
	});

	$('.select-checkbox .dropdown .option .label').live('click',function() {
		$checkbox = $(this).closest('.option').find('input[type="checkbox"]');
		if( $checkbox.attr('disabled') &&
			$checkbox.attr('disabled').length > 0 ) {
			return;
		}
		$checkbox.prop('checked', !$checkbox.prop('checked'));
		selectCheckboxUpdate($checkbox.closest('.select-checkbox'));
		return true;		
	});

	$('.select-checkbox .dropdown .option input[type="checkbox"]').live('click',function() {
		selectCheckboxUpdate($(this).closest('.select-checkbox'));
		return true;
	});

	// SELECT-CHECKBOX-FOCUS
	$('.select-checkbox .dropdown .option input[type="checkbox"]').live('focus',function() {
		$(this).closest('.select-checkbox').show();
		// $(this).closest('.option').addClass('focused');
	});

	// SELECT-CHECKBOX-FOCUS
	$('.select-checkbox .dropdown .option input[type="checkbox"]').live('blur', function() {
		// $(this).closest('.option').removeClass('focused');
	});

	$('.select-checkbox').each(function() {
		selectCheckboxUpdate($(this));
	});

	$('.row-elements-alternating-colors').each(function() {
		rowElementsColorVisible($(this));
	});

	$('li.list-master > .toggle').bind('click',function(e) {
		e.preventDefault();
		rowElementsToggleMaster($(this).closest('li'));
	});

	// Account Dropdown
	$('select.account-dropdown').accountDropdown();
	
	// Scroll to Top
	$('#scroll-top-footer a').live('click', function (e) {
		e.preventDefault();
		$('body,html').animate({ scrollTop: 0 }, 500);
		return false;
	});

	if( $('#scroll-top-footer').length ) {
		$(window).scroll(function () {
			if( $(this).scrollTop() > 100 &&
				! $('#scroll-top-footer').is(':visible') ) {
				$('#scroll-top-footer').fadeIn();
			} else if(  $(this).scrollTop() < 100 &&
						$('#scroll-top-footer').is(':visible') ) {
				$('#scroll-top-footer').fadeOut();
			}
		});
	}

	// Smooth Scrolling
	var currentUrl = window.location.pathname;
	if( currentUrl.indexOf('#') > 0 ) {
		currentUrl = currentUrl.substring(0,(currentUrl.indexOf('#') - 1));
	}
	$('a.smoothscroll').live('click', function (e) {
		if( $(this).attr('href').indexOf(currentUrl) >= 0 ) {
			$('body,html').animate({ scrollTop: $('a[name="'+$(this).prop('hash').substring(1)+'"]').offset().top }, 1000);
			e.preventDefault();
			return false;
		}
		return true;
	});

});

$.fn.accountDropdown = function() {
	this.select2({
		formatSelection: function(item) {
			return $.trim(item.text);
		},
		placeholder: "Select an Account"
	});
}

/**
 * Serialize an object so we can create JSON.
 * @return {[type]} [description]
 */
$.fn.serializeObject = function()
{
   var o = {};
   var a = this.serializeArray();
   $.each(a, function() {
       if (o[this.name]) {
           if (!o[this.name].push) {
               o[this.name] = [o[this.name]];
           }
           o[this.name].push(this.value || '');
       } else {
           o[this.name] = this.value || '';
       }
   });
   return o;
};

$.fn.childFieldsHaveValues = function() {
	var fieldsHaveValues = false;
	$(this).find('input:not([readonly], [disabled]), select:not([readonly], [disabled])').each(function() {
		if( $(this).val() &&
			$(this).val().length ) {
			fieldsHaveValues = true;
		}
	});
	return fieldsHaveValues;
}

// Default values
$.fn.resetFieldValues = function() {
	this.each(function() {
		if( ! $(this).hasClass('ezpz-hint') ) {
			$(this).val('');
			if( $(this).attr('data-default') &&
				$(this).attr('data-default').length ) {
				$(this).val($(this).attr('data-default'));
			}
			if( ! $(this).hasClass('hasDatepicker') ) {
				$(this).focus().blur();
			}
		}
	});
}

function showError(error) {
	$('#interface-dialog-error').html('<p class="text-medium">'+error+'</p>').modaldialog({
		dialogClass: 'generated-modal-dialog-error'
	});
}

function showPageSuccess(message) {
	$message = $('<div class="system-message success text-medium hidden">'+message+'</div>');
	$('div.tabs').before($message);
	$message.slideDown();
}

function showConfirm(message,yesButtonText,noButtonText,yesButtonCallback) {
	$('#interface-dialog-confirm').html('<p class="text-medium">'+message+'</p>').modaldialog({
		dialogClass: 'generated-modal-dialog-success',
		buttons: [
			{
				text: noButtonText,
				click: function() { $(this).dialog("close"); }
			},
			{
				text: yesButtonText,
				click: function() { $(this).dialog("close"); yesButtonCallback(); }
			}
		]
	});
}

function showPleaseWait(message,buttonText,buttonAction) {
	if( ! message ) message = 'Please wait...';
	$('#please-wait-dialog p.please-wait-dialog-message').text(message);
	$button = false;
	if( buttonText && buttonAction ) {
		$button = $('<a href="#" class="button">'+buttonText+'</a>');
		$('#please-wait-dialog p.please-wait-dialog-button').html('').append($button).show();
		$button.click(buttonAction);
	} else {
		$('#please-wait-dialog p.please-wait-dialog-button').html('&nbsp;').hide();
	}
	
	$('#please-wait-dialog').modaldialog({
		buttons: [],
		width: 300,
		height: ( $button ? 140 : 100 )
	});
	$('#please-wait-dialog .spinme').spin();
}

function hidePleaseWait() {
	$('#please-wait-dialog').dialog("close");
}

/**
 * Green Select Elements - IE Sucks.
 */
function selectOptionUpdate(select) {
	select.find('option').each(function() {
		$(this).css('color','#000000');
	});
	select.find('option:selected').css('color','#ffffff');
	select.blur();
}

/**
 * Row Element - Alternating Colors and Whatnot
 */
function rowElementsColorVisible(container) {
	container.find('li').removeClass('color-me-odd').removeClass('color-me-even');
	container.find('li:not(.list-container):not(.static-row):visible:odd').addClass('color-me-odd');
	container.find('li:not(.list-container):not(.static-row):visible:even').addClass('color-me-even');
}

function rowElementsToggleMaster(master) {
	if( master.hasClass('active') ) {
		master.next('li.list-container').slideUp(function() {
			master.removeClass('active');
			rowElementsColorVisible(master.closest('.row-elements-alternating-colors'));
		});
	} else {
		master.next('li.list-container').slideDown(function() {
			master.addClass('active');
			rowElementsColorVisible(master.closest('.row-elements-alternating-colors'));
		});
	}
}

/**
 * Custom Checkbox Elements
 */
function checkboxUpdate(checkbox) {
	if( checkbox.is(':disabled') &&
		checkbox.is(':checked') ) {
		checkbox.closest('.checkbox').addClass('disabled').addClass('checked');
	} else if( checkbox.is(':disabled') ) {
		checkbox.closest('.checkbox').addClass('disabled').removeClass('checked');
	} else if( checkbox.is(':checked') ) {
		checkbox.closest('.checkbox').removeClass('disabled').addClass('checked');
	} else {
		checkbox.closest('.checkbox').removeClass('disabled').removeClass('checked');
	}
}

function checkboxUpdateOLD(checkbox) {
	if( checkbox.is(':disabled') ) {
		checkbox.closest('.checkbox').addClass('disabled').removeClass('checked');
	} else if( checkbox.is(':checked') ) {
		checkbox.closest('.checkbox').removeClass('disabled').addClass('checked');
	} else {
		checkbox.closest('.checkbox').removeClass('disabled').removeClass('checked');
	}
}

/**
 * Dropdown Checkbox 
 */
function selectCheckboxUpdate(selectCheckbox) {
	$value = selectCheckbox.find('.value');
	$value.text('');
	selectCheckbox.find('input[type="checkbox"]').each(function() {
		if( $(this).is(':checked') ) {
			$value.text($value.text()+' '+$(this).attr('title'));
		}
	});
	if( $value.text().length == 0 ) {
		$value.text($value.attr('title'));
	}
}

/**
 * Custom File Input Elements
 */
function filechooserUpdate(file) {
	if( file.val().length ) {
		var fullPath = file.val();
		var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
		var filename = fullPath.substring(startIndex);
		if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
			filename = filename.substring(1);
		}
		file.closest('.file').find('.path').text(filename);
	}
	else
		file.closest('.file').find('.path').text("Choose a file...");
}

/**
 * Helper Function to round numbers to two decimal places - includes a .5 round up.
 */
function monetaryRound(value) {
	return ( Math.round(value*100) / 100 );
}

function convertCurrencyToNumber(value) {
	// TODO - Add in calculating sums and differences .
	return monetaryRound(Number(value.replace(/[^0-9\.\-]+/g,"")));
}

/**
 * Hacked function to print money values with props to http://stackoverflow.com/a/10899795
 * TODO - UPDATE CURRENCY SYMBOL HERE TO GRAB COMPANY SETTINGS
 */
var currencySymbol = false;
function monetaryPrint(value) {
	if( currencySymbol == false ) {
		currencySymbol = $('input#beans-company-currency-symbol').val();
	}
	if( typeof value == "undefined" ) {
		value = 0;
	}
	var print = currencySymbol;
	if( value < 0 ) {
		value = value * -1;
		print = '-'+print;
	}
	var parts = value.toString().split(".");
    return print+parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",") + (parts[1] ? "." + ( parts[1].length > 1 ? parts[1] : parts[1] + '0' ) : ".00");
}

function popupWindowLoad(url) {
	return window.open(url,"beansPopup","status=0,toolbar=0,location=0,menubar=0,directories=0,resizable=1,scrollbars=1,height=500,width=800");
}

/**
 * Generates search paging.
 */
function generateSearchPaging($container, data, max) {
	$container.html('');

	if( data.pages == 0 ) {
		// nada
	} else if( data.page == 0 ) {
		$container.append('&lt; ');
	} else {
		$container.append('<a href="#" rel="'+( data.page - 1 )+'">&lt;</a> ');
	}

	if( typeof max == "undefined" ||
		! max ) {
		max = 5;
	}

	var offset = Math.floor( max / 2 );

	if( data.pages <= max || 
		data.page <= offset ) {
		for( i = 0; i < ( data.pages <= max ? data.pages : max ); i++ ) {
			if( i == data.page ) {
				$container.append((i+1)+' ');
			} else {
				$container.append('<a href="#" rel="'+i+'">'+(i+1)+'</a> ');
			}
		}
	} else if( data.page >= ( data.pages + offset - max ) ) {
		for( i = ( data.pages - max ); i < data.pages; i++ ) {
			if( i == data.page ) {
				$container.append((i+1)+' ');
			} else {
				$container.append('<a href="#" rel="'+i+'">'+(i+1)+'</a> ');
			}
		}
	} else {
		for( i = ( data.page - offset ); i < ( data.page - offset + max ); i++ ) {
			if( i == data.page ) {
				$container.append((i+1)+' ');
			} else {
				$container.append('<a href="#" rel="'+i+'">'+(i+1)+'</a> ');
			}
		}
	}

	if( data.pages == 0 ) {
		// nada
	} else if( ( data.page + 1 ) == data.pages ) {
		$container.append('&gt; ');
	} else {
		$container.append('<a href="#" rel="'+( data.page + 1 )+'">&gt;</a> ');
	}
}

var dateYYYYMMDDstring = null;

function dateYYYYMMDD() {
	if( dateYYYYMMDDstring ) {
		return dateYYYYMMDDstring;
	}
	var date = new Date();
	var yyyy = date.getFullYear().toString();
	var mm = (date.getMonth()+1).toString();
	var dd  = date.getDate().toString();
	dateYYYYMMDDstring = yyyy+'-'+( mm[1] ? mm : "0"+mm[0] )+'-'+( dd[1] ? dd : "0"+dd[0] );
	return dateYYYYMMDDstring;
};
