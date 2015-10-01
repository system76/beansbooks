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

if ( document.body.className.match(new RegExp('(\\s|^)setup(\\s|$)')) !== null ) {

	/**
	 * Javascript for pages related to setup/
	 */

	$(function() {

		$('.setup-settings-save').click(function(e) {
			e.preventDefault();
			showPleaseWait();
			$(this).closest('form').submit();
		});

		$('.setup-settings-cancel').click(function(e) {
			e.preventDefault();
			window.location.reload();
		});

		$('#setup-settings-company input[name="company_fye"]').datepicker({
			dateFormat: "mm-dd"
		});

		$('#setup-settings-grid-company-logo-link').click(function(e) {
			e.preventDefault();
			$(this).closest('div').find('input[type="file"]').click();
		});

		$('#setup-settings-company input[name="logo"]').live('change',function() {
			if( $(this).val().length > 0 ) {
				$('#setup-settings-grid-company-logo-link').hide();
				$('#setup-settings-grid-company-logo-pending').show();
			}
		});

		$('#setup-settings-writeoff-accounts-container select').live('change',function() {
			if( $(this).next().is('.clear') &&
				$(this).val().length > 0 ) {
				// Duplicate, append, and set to ""
				$currentSelect = $(this);
				$newSelect = $currentSelect.clone();
				$newSelect.val('');
				$currentSelect.after($newSelect);
				$newSelect.accountDropdown();
			}
		});

		$('#setup-taxes-create input[name="date_due"]').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('#setup-taxes-create-save').click(function() {
			showPleaseWait();
			if( $('#setup-taxes-create').attr('rel') == "new" ) {
				// New
				$.post(
					'/setup/json/taxcreate',
					$('#setup-taxes-create input, #setup-taxes-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createTaxClearForm();
							$newTax = $(data.data.tax.html);
							$newTax.addClass('hidden');
							$('#setup-taxes-taxes li.setup-tax:first').after($newTax);
							$newTax.slideDown(function() {
								rowElementsColorVisible($('#setup-taxes-taxes'));
							});
						}
					},
					'json'
				);
			} else {
				// Update
				$.post(
					'/setup/json/taxupdate',
					$('#setup-taxes-create input, #setup-taxes-create select').serialize()+'&tax_id='+$('#setup-taxes-create').attr('rel'),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							$tax = $('#setup-taxes-taxes li.setup-tax[rel="'+$('#setup-taxes-create').attr('rel')+'"]');
							createTaxClearForm();
							$newTax = $(data.data.tax.html);
							$newTax.addClass('hidden');
							$tax.after($newTax);
							$tax.slideUp(function() {
								$newTax.slideDown(function() {
									rowElementsColorVisible($('#setup-taxes-taxes'));
								});
							});
						}
					},
					'json'
				);
			}
		});

		$('#setup-taxes-create-cancel').click(function() {
			createTaxClearForm();
			createTaxEnableFields();
		});

		$('#setup-taxes-create-canceledit').click(function() {
			createTaxClearForm();
			createTaxEnableFields();
		});

		$('#setup-taxes-create-edit').click(function() {
			createTaxEnableFields();
			$('.setup-taxes-editcancel').hide();
			$('.setup-taxes-savecancel').show();
		});

		$('.setup-tax-actions .view').live('click',function() {
			createTaxLoadTax($(this).attr('rel'));
		});

		$('#setup-taxes-taxes-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			taxesSearch($(this).val());
	 		}
		});

		/**
		 * USERS
		 */
		if( $('.setup-users-users-user').length > 0 ) {
			setupUserCreateColorRows();
		}

		$('.setup-users-users-user a.edit').live('click', function(e) {
			e.preventDefault();
			$user = $(this).closest('.setup-users-users-user');
			$editUser = $('#setup-users-users-user-edit-template .setup-users-users-user').clone();
			$editUser.addClass('hidden');
			$editUser.find('input[name="name"]').val($user.find('.setup-users-users-user-name').attr('rel'));
			$editUser.find('input[name="email"]').val($user.find('.setup-users-users-user-email').attr('rel'));
			$editUser.find('select[name="role_id"]').val($user.find('.setup-users-users-user-role').attr('rel'));
			$editUser.attr('rel',$user.attr('rel'));
			if( $user.hasClass('active-user') ) {
				$editUser.find('input[name="password"]').remove();
			}
			$user.after($editUser);
			$user.slideUp(function() {
				$editUser.slideDown(function() {
					setupUserCreateColorRows();
				});
			});
		});

		$('.setup-users-users-user a.delete').live('click', function(e) {
			e.preventDefault();
			$user = $(this).closest('.setup-users-users-user');
			$deleteUser = $('#setup-users-user-delete-template .setup-users-users-user').clone();
			$deleteUser.addClass('hidden');
			$deleteUser.find('.setup-users-users-user-message .name').text($user.find('.setup-users-users-user-name').text());
			$deleteUser.attr('rel',$user.attr('rel'));
			$user.after($deleteUser);
			$user.slideUp(function() {
				$deleteUser.slideDown(function() {
					setupUserCreateColorRows();
				});
			});
		});

		$('.setup-users-users-user a.add').click( function(e) {
			e.preventDefault();
			$addUser = $(this).closest('.setup-users-users-user');
			$editUser = $('#setup-users-users-user-edit-template .setup-users-users-user').clone();
			$editUser.attr('rel','new');
			$editUser.addClass('hidden');
			$addUser.before($editUser);
			$addUser.slideUp(function() {
				$editUser.slideDown(function() {
					setupUserCreateColorRows();
				});
			});
		});

		$('.setup-users-users-user a.cancel').live('click', function(e) {
			e.preventDefault();
			$editUser = $(this).closest('.setup-users-users-user');
			if( $editUser.attr('rel') && 
				$editUser.attr('rel') != "new" ) {
				$user = $('.setup-users-users-user:not(.delete, .edit)[rel="'+$editUser.attr('rel')+'"]');
				$editUser.slideUp(function() {
					$editUser.remove();
					$user.slideDown(function() {
						setupUserCreateColorRows();
					});
				});
			} else {
				$addUser = $('.setup-users-users-user.add');
				$editUser.slideUp(function() {
					$editUser.remove();
					$addUser.slideDown(function() {
						setupUserCreateColorRows();
					});
				});
			}
		});

		$('.setup-users-users-user a.remove').live('click', function(e) {
			e.preventDefault();
			$deleteUser = $(this).closest('.setup-users-users-user');
			$user = $('.setup-users-users-user:not(.delete, .edit)[rel="'+$deleteUser.attr('rel')+'"]');
			showPleaseWait();
			$.post(
				'/setup/json/userdelete',
				{
					user_id: $deleteUser.attr('rel')
				},
				function (data) {
					hidePleaseWait();
					if( ! data.success ) {
						showError(data.error);
					} else {
						$deleteUser.slideUp(function() {
							$deleteUser.remove();
							$user.remove();
							setupUserCreateColorRows();
						});
					}
				},
				'json'
			);
		});

		$('.setup-users-users-user a.save').live('click', function(e) {
			e.preventDefault();
			$editUser = $(this).closest('.setup-users-users-user');
			showPleaseWait();
			if( $editUser.attr('rel') == "new" ) {
				$addUser = $('.setup-users-users-user.add');
				$.post(
					'/setup/json/usercreate',
					$editUser.find("input, select").serialize(),
					function (data) {
						hidePleaseWait();
						if( ! data.success ) {
							showError(data.error);
						} else {
							$newUser = $(data.data.user.html);
							$newUser.addClass('hidden');
							$editUser.after($newUser);
							$editUser.slideUp(function() {
								$newUser.slideDown();
								$addUser.slideDown();
								$editUser.remove();
								setupUserCreateColorRows();
							});
						}
					},
					'json'
				);
			} else {
				$user = $('.setup-users-users-user:not(.delete, .edit)[rel="'+$editUser.attr('rel')+'"]');
				$.post(
					'/setup/json/userupdate',
					$editUser.find("input, select").serialize()+'&user_id='+$editUser.attr('rel'),
					function (data) {
						hidePleaseWait();
						if( ! data.success ) {
							showError(data.error);
						} else {
							$newUser = $(data.data.user.html);
							$newUser.addClass('hidden');
							$editUser.after($newUser);
							$editUser.slideUp(function() {
								$newUser.slideDown();
								$user.remove();
								$editUser.remove();
								setupUserCreateColorRows();
							});
						}
					},
					'json'
				);
			}
		});
		
		// API
		$('.setup-users-api-row a.generate-build').live('click', function(e) {
			e.preventDefault();
			$generate = $(this).closest('.setup-users-api-row');
			showPleaseWait();
			$.post(
				'/setup/json/apibuild',
				{},
				function (data) {
					hidePleaseWait();
					if( ! data.success ) {
						showError(data.error);
					} else {
						$apiInfo = $(data.data.auth.html);
						$apiInfo.addClass('hidden');
						$generate.before($apiInfo);
						$generate.slideUp(function() {
							$apiInfo.slideDown();
							$generate.remove();
						});
					}
				},
				'json'
			);
		});

		$('.setup-users-api-row a.generate-new').live('click', function(e) {
			e.preventDefault();
			if( confirm("Are you sure you want to generate a new API key?  This will cause your previous key to no longer work.") ) {
				$apiInfo = $(this).closest('.setup-users-api-row');
				showPleaseWait();
				$.post(
					'/setup/json/apiregen',
					{
						auth_uid: $apiInfo.attr('rel')
					},
					function (data) {
						hidePleaseWait();
						if( ! data.success ) {
							showError(data.error);
						} else {
							$newApiInfo = $(data.data.auth.html);
							$newApiInfo.addClass('hidden');
							$apiInfo.before($newApiInfo);
							$apiInfo.slideUp(function() {
								$newApiInfo.slideDown();
								$apiInfo.remove();
							});
						}
					},
					'json'
				);
			}
		});

		// Calibration
		$('#setup-calibrate-start').live('click', function (e) {
			e.preventDefault();
			if( $('#setup-calibrate-date').attr('type') == "text" ) {
				$('#setup-calibrate-date').hide();
			};
			setupCalibrateNextReady = true;
			setupCalibrateNext();
			$('#setup-calibrate-start').hide();
			$('#setup-calibrate-resume').show();
		});

		$('#setup-calibrate-resume').live('click', function (e) {
			e.preventDefault();
			setupCalibrateNextReady = true;
			setupCalibrateNext();
		});

		if( $('#setup-calibrate-date').attr('type') == "text" ) {
			$('#setup-calibrate-date').datepicker({
				dateFormat: "yy-mm-dd"
			});
		}

	});

	function createTaxClearForm() {
		$('#setup-taxes-create').attr('rel','new');
		$('#setup-taxes-create').slideUp(function() {
			$('#setup-taxes-create input,#setup-taxes-create select').resetFieldValues();
			$('#setup-taxes-create select[name="account_id"]').select2('data',{
				id: $('#setup-taxes-create select[name="account_id"]').attr('data-default'),
				text: $('#setup-taxes-create select[name="account_id"] option[value="'+$('#setup-taxes-create select[name="account_id"]').attr('data-default')+'"]').text()
			});

			$('#setup-taxes-create').attr('rel','new');
			$('.setup-taxes-editcancel').hide();
			$('.setup-taxes-savecancel').show();

			$('#setup-taxes-create').slideDown();
		});
		
		
	}

	function taxesSearch(term) {
		showPleaseWait();
		$.post(
			'/setup/json/taxsearch',
			{
				term: term
			},
			function (data) {
				if( ! data.success ) {
					hidePleaseWait();
					showError(data.error);
				} else {
					$('#setup-taxes-taxes ul li:not(:first)').each(function() {
						$(this).remove();
					});
					for( i in data.data.taxes ) {
						$tax = $(data.data.taxes[i].html);
						$('#setup-taxes-taxes ul').append($tax);
					}
					hidePleaseWait();
				}
			},
			'json'
		);
	}

	function createTaxLoadTax(id) {
		showPleaseWait();
		$.post(
			'/setup/json/taxload',
			{
				tax_id: id,
			},
			function (data) {
				hidePleaseWait();
				if( ! data.success ) {
					showError(data.error);
				} else {
					createTaxDisableFields();
					$('.setup-taxes-savecancel').hide();
					$('.setup-taxes-editcancel').show();
					$('#setup-taxes-create').attr('rel',data.data.tax.id);
					$('#setup-taxes-create input[name="name"]').val(data.data.tax.name);
					$('#setup-taxes-create input[name="authority"]').val(data.data.tax.authority);
					$('#setup-taxes-create input[name="license"]').val(data.data.tax.license);
					$('#setup-taxes-create input[name="percent"]').val(data.data.tax.percent_formatted);
					$('#setup-taxes-create select[name="account_id"]').select2('data',{
						id: data.data.tax.account.id,
						text: data.data.tax.account.name
					});
					$('#setup-taxes-create input[name="date_due"]').val(data.data.tax.date_due);
					$('#setup-taxes-create select[name="date_due_months_increment"]').val(data.data.tax.date_due_months_increment);
					$('#setup-taxes-create input[name="address1"]').val(data.data.tax.address1);
					$('#setup-taxes-create input[name="address2"]').val(data.data.tax.address2);
					$('#setup-taxes-create input[name="city"]').val(data.data.tax.city);
					$('#setup-taxes-create input[name="state"]').val(data.data.tax.state);
					$('#setup-taxes-create input[name="zip"]').val(data.data.tax.zip);
					$('#setup-taxes-create select[name="country"]').val(data.data.tax.country);
					$('#setup-taxes-create select[name="visible"]').val( data.data.tax.visible ? '1' : '0' );
				}
			},
			'json'
		);
	}

	function createTaxDisableFields() {
		$('#setup-taxes-create input, #setup-taxes-create select').each(function() {
			$(this).attr('disabled','disabled');
		});
		$('#setup-taxes-create select[name="account_id"]').select2('disable');
		$('#setup-taxes-create .select').each(function() {
			$(this).addClass('disabled');
		});
	}

	function createTaxEnableFields() {
		$('#setup-taxes-create input, #setup-taxes-create select').each(function() {
			$(this).attr('disabled',false);
		});
		$('#setup-taxes-create select[name="account_id"]').select2('enable');
		$('#setup-taxes-create .select').each(function() {
			$(this).removeClass('disabled');
		});
		if( $('#setup-taxes-create').attr('rel') &&
			$('#setup-taxes-create').attr('rel').length &&
			$('#setup-taxes-create').attr('rel') != "new" ) {
			$('#setup-taxes-create input[name="percent"]').attr('disabled','disabled');
			$('#setup-taxes-create select[name="account_id"]').select2('disable');
		}
	}

	function setupUserCreateColorRows() {
		var flip = true;
		$('.setup-users-users-user:not(.header):visible').each(function () {
			if( flip ) {
				$(this).addClass('dark');
				flip = false;
			} else {
				$(this).removeClass('dark');
				flip = true;
			}
		});
	}

	var setupCalibrateNextReady = true;

	// Calibration Functions
	function setupCalibrateNext() {
		if( ! $('#setup-calibrate-date').val() ||
			$('#setup-calibrate-date').val().length == 0 )
			return setupCalibrateGetStart();

		if( setupCalibrateNextReady ) {
			showPleaseWait(
				'Calibrating '+$('#setup-calibrate-date').val()+'...',
				'Pause Calibration',
				setupCalibratePauseNext
			);
			$.post(
				'/setup/json/calibratedate',
				{
					date: $('#setup-calibrate-date').val(),
					manual: $('#setup-calibrate-manual').val()
				},
				function (data) {
					if( ! data.success ) {
						hidePleaseWait();
						showError(data.error);
						if( $('#setup-calibrate-manual').length && 
								$('#setup-calibrate-manual').val() == "1" ) {
							$('#setup-calibrate-date').show();
						}
						return;
					}
					if( data.data.date_next ) {
						$("#setup-calibrate-date").val(data.data.date_next);
						setupCalibrateNext();
						return;
					}
					// All done.
					showPleaseWait();
					window.location.reload();
				}
			);
			return;
		}

		hidePleaseWait();

	}

	// Add pause button to showPleaseWait() ?

	function setupCalibrateGetStart() {
		showPleaseWait(
			'Determining Start Date.',
			'Pause Calibration',
			setupCalibratePauseNext
		);
		$.post(
			'/setup/json/calibratestartdate',
			{},
			function (data) {
				if( ! data.success ) {
					hidePleaseWait();
					showError(data.error);
					return;
				}
				$("#setup-calibrate-date").val(data.data.date);
				setupCalibrateNext();
			}
		);
	}

	function setupCalibratePauseNext(e) {
		e.preventDefault();
		setupCalibrateNextReady = false;
		showPleaseWait('Finishing Current Step...');
		return false;
	}

}