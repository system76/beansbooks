if ( document.body.className.match(new RegExp('(\\s|^)vendors(\\s|$)')) !== null ) {

	var expenseDescriptionCache = {};
	var expenseDescriptionParams = {
		autoFocus: true,
		minLength: 2,
		select: function( event, ui ) {
			$description = $(event.target);
			$description.change();
			$price = $description.closest('.vendors-expenses-create-form-lines-line').find('input.line-price');
			if( $price.val().length == 0 ) {
				$price.val(ui.item.amount);
			}
			$account_id = $description.closest('.vendors-expenses-create-form-lines-line').find('select.account_id');
			if( $account_id.val().length == 0 ) {
				$account_id.select2('data',{
					id: ui.item.account_id,
					text: $account_id.find('option[value="'+ui.item.account_id+'"]').text()
				});
			}
		},
		source: function(request, response) {
			var search = request.term.toLowerCase();
			if( expenseDescriptionCache[search] != null ) {
				response(expenseDescriptionCache[search]);
				return;
			}
			$.post(
				'/vendors/json/expenselines',
				{
					search_term: search
				},
				function(data) {
					expenseDescriptionCache[search] = [];
					for( i in data.data.lines ) {
						if( parseInt(i) < 5 ) {
							expenseDescriptionCache[search].push({
								label: data.data.lines[i].description,
								value: data.data.lines[i].description,
								amount: data.data.lines[i].amount,
								account_id: data.data.lines[i].account_id
							});
						}
					}
					response(expenseDescriptionCache[search]);
				},
				'json'
			);
		}
	};

	var purchaseDescriptionCache = {};
	var purchaseDescriptionParams = {
		autoFocus: true,
		minLength: 2,
		select: function( event, ui ) {
			$description = $(event.target);
			$description.change();
			$price = $description.closest('.vendors-purchases-create-form-lines-line').find('input.line-price');
			if( $price.val().length == 0 ) {
				$price.val(ui.item.amount);
			}
			$account_id = $description.closest('.vendors-purchases-create-form-lines-line').find('select.account_id');
			if( $account_id.val().length == 0 ) {
				$account_id.select2('data',{
					id: ui.item.account_id,
					text: $account_id.find('option[value="'+ui.item.account_id+'"]').text()
				});
			}
		},
		source: function(request, response) {
			var search = request.term.toLowerCase();
			if( purchaseDescriptionCache[search] != null ) {
				response(purchaseDescriptionCache[search]);
				return;
			}
			$.post(
				'/vendors/json/purchaselines',
				{
					search_term: search
				},
				function(data) {
					purchaseDescriptionCache[search] = [];
					for( i in data.data.lines ) {
						if( parseInt(i) < 5 ) {
							purchaseDescriptionCache[search].push({
								label: data.data.lines[i].description,
								value: data.data.lines[i].description,
								amount: data.data.lines[i].amount,
								account_id: data.data.lines[i].account_id
							});
						}
					}
					response(purchaseDescriptionCache[search]);
				},
				'json'
			);
		}
	};

	$(function() {

		/**
		 * Vendors / Expenses
		 */
		// Add one expense line by default.
		if( $('#vendors-expenses-create-form-lines').length ) {
			$newExpenseLine = $($('#vendors-expenses-create-form-lines-line-template').html());
			$newExpenseLine.addClass('hidden');
			$('#vendors-expenses-create-form-lines').append($newExpenseLine);
			$newExpenseLine.find('input.line-description').autocomplete(expenseDescriptionParams);
			$newExpenseLine.slideDown(function () {
				$newExpenseLine.css('overflow','');
				$newExpenseLine.find('select.account_id').accountDropdown();
			});
		}

		if( $('#vendors-payments-create-requested_payment_id').length > 0 &&
			$('#vendors-payments-create-requested_payment_id').val().length > 0 ) {
			loadVendorPayment($('#vendors-payments-create-requested_payment_id').val());
		}

		$('input.datepicker').each(function() {
			$(this).datepicker({ dateFormat: "yy-mm-dd" });
		});

		/**
		 * Vendors / Expenses
		 */
		if( $('#vendors-expenses-expenses').length > 0 ) {
			/*
			$(window).scroll(function () { 
				if( ( $(window).height() + $(window).scrollTop() ) >= $('#vendors-expenses-expenses').height() ) {
					if( $('#vendors-expenses-loadexpenses').is(':visible') ||
						$('#vendors-expenses-endexpenses').is(':visible') ) {
						// Do nothing - we're already loading...
					} else {
						loadMoreExpenses();
					}
				}
			});
			*/

			// Check for a default expense ID.
			if( $('#vendors-expenses-create-requested_expense_id').length > 0 &&
				$('#vendors-expenses-create-requested_expense_id').val().length > 0 ) {
				loadExpense($('#vendors-expenses-create-requested_expense_id').val());
			}
		}

		$('#vendors-expenses-expenses .vendor-expense .view').live('click',function() {
			// If we're on the expenses page we AJAX - otherwise... return true.
			if( $('#vendors-expenses-create').length == 0 ) {
				return true;
			}
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadExpense($(this).closest('.vendor-expense').attr('rel'));
			return false;
		});

		$('#vendors-expenses-create input,#vendors-expenses-create select').live('change',function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		var cancel_vendor_expense_id = '';
		function cancelVendorExpense() {
			showPleaseWait();
			$expenseLine = $('#vendors-expenses-expenses .vendor-expense[rel="'+cancel_vendor_expense_id+'"]');
			$.post(
				'/vendors/json/expensecancel',
				{
					expense_id: cancel_vendor_expense_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						if( $expenseLine.length > 0 ) {
							$expenseLine.slideUp(function() {
								$expenseLine.remove();
								rowElementsColorVisible($('#vendors-expenses-expenses'));
							});
						}
						if( $('#vendors-expenses-create').attr('rel') &&
							$('#vendors-expenses-create').attr('rel') == cancel_vendor_expense_id ) {
							createExpenseClearForm();
						}
						$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
					}
				},
				'json'
			);
		}

		$('#vendors-expenses-expenses .vendor-expense .cancel').live('click',function(e) {
			e.preventDefault();
			cancel_vendor_expense_id = $(this).closest('.vendor-expense').attr('rel');
			showConfirm("Are you certain you want to delete this expense?","Yes, Delete.","No.",cancelVendorExpense);
			return false;
		});

		$('#vendors-expenses-expenses .vendor-expense .refund').live('click', function(e) {
			e.preventDefault();
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadExpense($(this).closest('.vendor-expense').attr('rel'),true);
			return false;
		});

		$('#vendors-expenses-expenses .vendor-expense .send').live('click',function() {
			// TODO
			showError("Not implemented yet.");
		});

		$('#vendors-expenses-expenses .vendor-expense .print').live('click',function(e) {
			e.preventDefault();
			printVendorExpense($(this).closest('li.vendor-expense').attr('rel'));
		});

		// EXPENSES SEARCH
		$('#vendors-expenses-expenses-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-expenses-expenses li.vendor-expense:not(:first-child)').remove();
	 			$('#vendors-expenses-endexpenses').hide();
	 			$('#vendors-expenses-loadexpenses').show();
	 			loadMoreExpenses();
	 		}
		});

		$('#vendors-expenses-create-form-cancel').click(function (e) {
			e.preventDefault();
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					if( $('#vendors-expenses-create').attr('rel') &&
						$('#vendors-expenses-create').attr('rel').length > 0 ) {
						if( $('#vendors-expenses-create').attr('rel') == "R" ) {
							loadExpense($('#vendors-expenses-create input[name="refund_expense_id"]').val());
						} else {
							loadExpense($('#vendors-expenses-create').attr('rel'));
						}
					} else {
						createExpenseClearForm();
					}
				}
			} else {
				if( $('#vendors-expenses-create').attr('rel') &&
					$('#vendors-expenses-create').attr('rel').length > 0 ) {
					if( $('#vendors-expenses-create').attr('rel') == "R" ) {
						loadExpense($('#vendors-expenses-create input[name="refund_expense_id"]').val());
					} else {
						loadExpense($('#vendors-expenses-create').attr('rel'));
					}
				} else {
					createExpenseClearForm();
				}
			}
			return false;
		});

		$('#vendors-expenses-create-form-delete').click(function (e) {
			e.preventDefault();
			cancel_vendor_expense_id = $('#vendors-expenses-create').attr('rel');
			showConfirm("Are you certain you want to delete this expense?","Yes, Delete.","No.",cancelVendorExpense);
			return false;
		});

		$('#vendors-expenses-create-form-return').click(function (e) {
			e.preventDefault();
			loadExpense($('#vendors-expenses-create').attr('rel'),true);
		});

		$('#vendors-expenses-create-form-print').click(function (e) {
			e.preventDefault();
			printVendorExpense($('#vendors-expenses-create').attr('rel'));
		});

		$('#vendors-expenses-create-form-editcancel').click(function (e) {
			createExpenseClearForm();
			return false;
		});

		$('#vendors-expenses-create-form-edit').click(function() {
			if( ! $('#vendors-expenses-create').attr('rel') ||
				$('#vendors-expenses-create').attr('rel').length == 0 ) {
				showError("An unexpected error has occurred.<br>You should reload the page before going any further.");
				return;
			}

			$('#vendors-expenses-create input:not(.ezpz-hint,.datepicker),#vendors-expenses-create select').each(function() {
				$(this).attr('disabled',false).focus().blur();
			});

			$('input.datepicker').each(function() {
				$(this).attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
			});

			$('#vendors-expenses-create input[name="vendor"]').select2('disable');
			$('#vendors-expenses-create select[name="account"]').select2('enable');
			$('#vendors-expenses-create-form-lines select.account_id').each(function () {
				$(this).select2('enable');
				$(this).closest('span').find('.select2-container').removeClass('select2-container-active');
			});
			
			$('#vendors-expenses-create div.select').removeClass('disabled');

			$('#vendors-expenses-create .vendor-expenses-create-new-buttons').show();
			$('#vendors-expenses-create .vendor-expenses-create-edit-buttons').hide();
			return false;
		});

		$('#vendors-expenses-create-form-save').click(function(e) {
			e.preventDefault();
			showPleaseWait();
			// Serialize and submit.
			createExpenseIndexLines();
			if( $('#vendors-expenses-create').attr('rel') &&
				$('#vendors-expenses-create').attr('rel') == "R" ) {
				// REFUND
				// Re-enable all disabled fields.
				$('#vendors-expenses-create input[name="vendor"]').select2('enable');
				$('#vendors-expenses-create input[disabled],#vendors-expenses-create select[disabled]').each(function() {
					$(this).attr('disabled',false).focus().blur();
				});
				createExpenseUpdateTotals();
				$.post(
					'/vendors/json/expenserefund',
					$('#vendors-expenses-create input,#vendors-expenses-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							$oldExpense = $('#vendors-expenses-expenses .vendor-expense[rel="'+$('#vendors-expenses-create input[name="refund_expense_id"]').val()+'"]:first-child');
							createExpenseClearForm();
							$newExpense = $(data.data.expense.html);
							$newExpense.addClass('hidden');
							$oldExpense.find('a.refund').remove();
							$('#vendors-expenses-expenses .vendor-expense:first').after($newExpense);
							$newExpense.slideDown();
							// TODO - ADD COLOR ANIMATION
							rowElementsColorVisible($('#vendors-expenses-expenses'));
						}
					},
					'json'
				);
			} else if( $('#vendors-expenses-create').attr('rel') &&
				$('#vendors-expenses-create').attr('rel').length ) {
				// UPDATE
				$.post(
					'/vendors/json/expenseupdate',
					$('#vendors-expenses-create input,#vendors-expenses-create select').serialize()+'&expense_id='+$('#vendors-expenses-create').attr('rel'),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createExpenseClearForm();
							
							$newExpense = $(data.data.expense.html);
							$newExpense.addClass('hidden');
							if( $('#vendors-expenses-expenses .vendor-expense[rel="'+data.data.expense.id+'"]').length > 0 ) {
								$oldExpense = $('#vendors-expenses-expenses .vendor-expense[rel="'+data.data.expense.id+'"]:first');
								$oldExpense.before($newExpense);
								$oldExpense.slideUp(function() {
									$oldExpense.remove();
								});
							} else {
								$('#vendors-expenses-expenses .vendor-expense:first').after($newExpense);
							}
							$newExpense.slideDown(function() {
								rowElementsColorVisible($('#vendors-expenses-expenses'));
							});

							$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
							// TODO - ADD COLOR ANIMATION
						}
					},
					'json'
				);
			} else {
				// NEW
				$.post(
					'/vendors/json/expensecreate',
					$('#vendors-expenses-create input,#vendors-expenses-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createExpenseClearForm();
							$newExpense = $(data.data.expense.html);
							$newExpense.addClass('hidden');
							$('#vendors-expenses-expenses .vendor-expense:first').after($newExpense);
							$newExpense.slideDown();
							
							$noExpenses = $('#vendors-expenses-expenses .vendor-expense:last');
							if( $noExpenses.find('span').length == 0 ) {
								$noExpenses.slideUp(function() {
									$noExpenses.remove();
								});
							}

							$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);

							// TODO - ADD COLOR ANIMATION
							rowElementsColorVisible($('#vendors-expenses-expenses'));
						}
					},
					'json'
				);
			}
		});

		$('#vendors-expenses-create input[placeholder]').ezpz_hint();

		

		$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line select.account_id,#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line input.line-description').live('change',function() {
			if( $(this).val() &&
				$(this).val().length &&
				$(this).closest('.vendors-expenses-create-form-lines-line').is(':last-child') ) {
				$newExpenseLine = $($('#vendors-expenses-create-form-lines-line-template').html());
				$newExpenseLine.addClass('hidden');
				$('#vendors-expenses-create-form-lines').append($newExpenseLine);
				$newExpenseLine.find('input.line-description').autocomplete(expenseDescriptionParams);
				$newExpenseLine.slideDown(function () {
					$newExpenseLine.css('overflow','');
					$newExpenseLine.find('select.account_id').accountDropdown();
				});
			}
		});

		$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line input.line-quantity,#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line input.line-price').live('change',function() {
			createExpenseUpdateTotals();
		});

		$('#vendors-expenses-create input[name="vendor"]').select2({
			minimumInputLength: 1,
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/vendors/json/vendorsformsearch",
				type: "POST",
				dataType: 'json',
				data: function (term) {
					return {
						last_vendor_id: '',
						search_terms: term, // search term
						count: 1000,
					};
				},
				results: function (data) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					var results = new Array();
					for( index in data.data.vendors ) {
						var id = data.data.vendors[index].id+'#';
						if( data.data.vendors[index].default_remit_address_id !== undefined &&
							data.data.vendors[index].default_remit_address_id !== null ) {
							id = id+data.data.vendors[index].default_remit_address_id;
						}
						id = id+'#';
						if( data.data.vendors[index].default_account !== undefined &&
							data.data.vendors[index].default_account !== null &&
							data.data.vendors[index].default_account_id !== undefined &&
							data.data.vendors[index].default_account_id !== null ) {
							id = id+data.data.vendors[index].default_account.id+'#'+data.data.vendors[index].default_account.terms;
						}
						results[index] = {
							id: id,
							text: data.data.vendors[index].display_name
						}
					}
					return {results: results};
				}
			}
		});


		$('#vendors-expenses-create input[name="vendor"]').change(function() {
			
			if( ! $(this).val() ||
				! $(this).val().length ) {
				$('#vendors-expenses-create select[name="remit_address_id"] option[value!=""]').remove();
				return true;
			}
			
			var vendor = $(this).val().split('#');
			showPleaseWait();
			
			if( vendor[2].length ) {
				if( $('#vendors-expenses-create select[name="account"] option[value="'+vendor[2]+'#'+vendor[3]+'"]').is(":enabled") ) {
					$('#vendors-expenses-create select[name="account"]').select2('data',{
						id: vendor[2]+'#'+vendor[3],
						text: $('#vendors-expenses-create select[name="account"] option[value="'+vendor[2]+'#'+vendor[3]+'"]').text()
					});
				} else {
					$('#vendors-expenses-create select[name="account"]').select2('data',{
						id: $('#vendors-expenses-create select[name="account"]').attr('data-default'),
						text: $('#vendors-expenses-create select[name="account"] option[value="'+$('#vendors-expenses-create select[name="account"]').attr('data-default')+'"]').text()
					});
				}
			} else {
				$('#vendors-expenses-create select[name="account"]').select2('data',{
					id: $('#vendors-expenses-create select[name="account"]').attr('data-default'),
					text: $('#vendors-expenses-create select[name="account"] option[value="'+$('#vendors-expenses-create select[name="account"]').attr('data-default')+'"]').text()
				});
			}

			$.post(
				'/vendors/json/vendoraddresses',
				{
					vendor_id: vendor[0]
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('#vendors-expenses-create select[name="remit_address_id"] option[value!=""]').remove();
						for( var index in data.data.addresses ) {
							$('#vendors-expenses-create select[name="remit_address_id"]').append('<option value="'+data.data.addresses[index].id+'">'+data.data.addresses[index].address1+'</option>');
						}
						$('#vendors-expenses-create select[name="remit_address_id"]').val(vendor[1]);
						if( (
								! $('#vendors-expenses-create select[name="remit_address_id"]').val() ||
								! $('#vendors-expenses-create select[name="remit_address_id"]').val().length
							) &&
							$('#vendors-expenses-create select[name="remit_address_id"] option[value!=""]').length == 1 ) {
							$('#vendors-expenses-create select[name="remit_address_id"]').val($('#vendors-expenses-create select[name="remit_address_id"] option[value!=""]').val());
						}
						$('#vendors-expenses-create select[name="remit_address_id"]').focus();
					}
				},
				'json'
			);
		});

		var vendorsExpenseCreateNextInput;
		function vendorsExpenseCreateGoNextInput(input) {
			vendorsExpenseCreateNextInput = input;
			setTimeout( function() {
				vendorsExpenseCreateNextInput.focus();
			}, 10);
		}
		
		$('#vendors-expenses-create select[name="account"]').live('change' , function() {
			vendorsExpenseCreateGoNextInput($('#vendors-expenses-create input[name="check_number"]').focus());
		});

		$('#vendors-expenses-create-form-lines select.account_id').live('change' , function() {
			vendorsExpenseCreateGoNextInput($(this).closest('div.row').find('input.line-quantity').focus());
		});

		/**
		 * Create Vendor Dialog
		 */
		$('#vendors-expenses-dialog-vendor-create').modaldialog({
			autoOpen: false,
			width: 500,
			buttons: { 
				"Cancel": function() { 
					$(this).dialog("close");
					$(this).find('input, select').resetFieldValues();
				},
				"Save Vendor": function() { 
					$currentDialog = $(this);
					showPleaseWait();
					$.post(
						'/vendors/json/vendorcreate',
						$currentDialog.find('input,select').serialize(),
						function(data) {
							hidePleaseWait();
							if( data.success != 1 ) {
								showError(data.error);
							} else {
								var vendor = data.data.vendor;
								var select2data = {
									text: vendor.display_name,
									id: vendor.id+'#'
								};
								if( vendor.default_remit_address_id !== undefined &&
									vendor.default_remit_address_id !== null ) {
									select2data.id = select2data.id+vendor.default_remit_address_id;
								}
								select2data.id = select2data.id+'#';
								if( vendor.default_account !== undefined &&
									vendor.default_account !== null &&
									vendor.default_account_id !== undefined &&
									vendor.default_account_id !== null ) {
									select2data.id = select2data.id+vendor.default_account.id+'#'+vendor.default_account.terms;
								}
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
								$('#vendors-expenses-create input[name="vendor"]').select2("data",select2data);
								$('#vendors-expenses-create input[name="vendor"]').change();
							}
						},
						'json'
					);
				}
			}
		});
		
		$('#vendors-expenses-dialog-vendor-create-link').click(function() {
			$('#vendors-expenses-dialog-vendor-create').dialog("open");
		});

		$('#vendors-expenses-dialog-address-create').modaldialog({
			autoOpen: false,
			width: 500,
			buttons: { 
				"Cancel": function() {
					$(this).dialog("close");
					$(this).find('input, select').resetFieldValues();
				},
				"Save Address": function() { 
					$currentDialog = $(this);
					showPleaseWait();
					$.post(
						'/vendors/json/vendoraddresscreate',
						$currentDialog.find('input,select').serialize(),
						function(data){
							hidePleaseWait();
							if( ! data.success ) {
								showError(data.error);
							} else {
								var address = data.data.address;
								$('#vendors-expenses-create select[name="remit_address_id"]').append('<option value="'+address.id+'">'+address.address1+'</option>');
								$('#vendors-expenses-create select[name="remit_address_id"]').val(address.id);
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
							}
						},
						'json'
					);
				}
			}
		});

		$('#vendors-expenses-dialog-address-create-link').click(function() {
			if( ! $('#vendors-expenses-create input[name="vendor"]').val() ||
				! $('#vendors-expenses-create input[name="vendor"]').val().length ) {
				showError("You must select a vendor before creating an address.");
				return;
			}
			var vendor = $('#vendors-expenses-create input[name="vendor"]').val().split('#');;
			$('#vendors-expenses-dialog-address-create input[name="vendor_id"]').val(vendor[0]);
			$('#vendors-expenses-dialog-address-create').dialog("open");
		});

		/**
		 * Purchase Order Dialogs - Vendor and Address
		 */
		$('#vendors-purchases-dialog-vendor-create').modaldialog({
			autoOpen: false,
			width: 500,
			buttons: { 
				"Cancel": function() { 
					$(this).dialog("close");
					$(this).find('input, select').resetFieldValues();
				},
				"Save Vendor": function() { 
					$currentDialog = $(this);
					showPleaseWait();
					$.post(
						'/vendors/json/vendorcreate',
						$currentDialog.find('input,select').serialize(),
						function(data) {
							hidePleaseWait();
							if( data.success != 1 ) {
								showError(data.error);
							} else {
								// KISS
								var vendor = data.data.vendor;
								var select2data = {
									text: vendor.display_name,
									id: vendor.id+'#'
								};
								if( vendor.default_remit_address_id !== undefined &&
									vendor.default_remit_address_id !== null ) {
									select2data.id = select2data.id+vendor.default_remit_address_id;
								}
								select2data.id = select2data.id+'#';
								if( vendor.default_account !== undefined &&
									vendor.default_account !== null &&
									vendor.default_account_id !== undefined &&
									vendor.default_account_id !== null ) {
									select2data.id = select2data.id+vendor.default_account.id+'#'+vendor.default_account.terms;
								}
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
								$('#vendors-purchases-create input[name="vendor"]').select2("data",select2data);
								$('#vendors-purchases-create input[name="vendor"]').change();
							}
						},
						'json'
					);
				}
			}
		});
		
		$('#vendors-purchases-dialog-vendor-create-link').click(function() {
			$('#vendors-purchases-dialog-vendor-create').dialog("open");
		});

		$('#vendors-purchases-dialog-address-create').modaldialog({
			autoOpen: false,
			width: 500,
			buttons: { 
				"Cancel": function() {
					$(this).dialog("close");
					$(this).find('input, select').resetFieldValues();
				},
				"Save Address": function() { 
					$currentDialog = $(this);
					showPleaseWait();
					$.post(
						'/vendors/json/vendoraddresscreate',
						$currentDialog.find('input,select').serialize(),
						function(data){
							hidePleaseWait();
							if( ! data.success ) {
								showError(data.error);
							} else {
								var address = data.data.address;
								$('#vendors-purchases-create select[name="remit_address_id"]').append('<option value="'+address.id+'">'+address.address1+'</option>');
								$('#vendors-purchases-create select[name="remit_address_id"]').val(address.id);
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
							}
						},
						'json'
					);
				}
			}
		});

		$('#vendors-purchases-dialog-address-create-link').click(function() {
			if( ! $('#vendors-purchases-create input[name="vendor"]').val() ||
				! $('#vendors-purchases-create input[name="vendor"]').val().length ) {
				showError("You must select a vendor before creating an address.");
				return;
			} else if ( $('#vendors-purchases-create select[name="remit_address_id"]').is(':disabled') ) {
				return;
			}
			var vendor = $('#vendors-purchases-create input[name="vendor"]').val().split('#');;
			$('#vendors-purchases-dialog-address-create input[name="vendor_id"]').val(vendor[0]);
			$('#vendors-purchases-dialog-address-create').dialog("open");
		});

		$('#vendors-purchases-dialog-shipaddress-create').modaldialog({
			autoOpen: false,
			width: 500,
			buttons: { 
				"Cancel": function() {
					$(this).dialog("close");
					$(this).find('input, select').resetFieldValues();
				},
				"Save Address": function() { 
					$currentDialog = $(this);
					showPleaseWait();
					$.post(
						'/vendors/json/shippingaddresscreate',
						$currentDialog.find('input,select').serialize(),
						function(data){
							hidePleaseWait();
							if( ! data.success ) {
								showError(data.error);
							} else {
								$('#vendors-purchases-create input[name="shipping_address_id"]').select2('data',{
									id: data.data.address.id,
									text: data.data.address.standard
								});
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
							}
						},
						'json'
					);
				}
			}
		});

		$('#vendors-purchases-dialog-shipaddress-create-link').click(function() {
			if ( $('#vendors-purchases-create input[name="shipping_address_id"]').is(':disabled') ) {
				return;
			}
			$('#vendors-purchases-dialog-shipaddress-create').dialog("open");
		});


		/**
		 * Vendors / Purchase Orders
		 */
		// Add one expense line by default.
		if( $('#vendors-purchases-create-form-lines').length ) {
			$newPurchaseLine = $($('#vendors-purchases-create-form-lines-line-template').html());
			$newPurchaseLine.addClass('hidden');
			$('#vendors-purchases-create-form-lines').append($newPurchaseLine);
			$newPurchaseLine.find('input.line-description').autocomplete(purchaseDescriptionParams);
			$newPurchaseLine.slideDown(function () {
				$newPurchaseLine.css('overflow','');
				$newPurchaseLine.find('select.account_id').accountDropdown();
			});
		}
		
		if( $('#vendors-purchases-purchases').length > 0 ) {
			/*
			$(window).scroll(function () { 
				if( ( $(window).height() + $(window).scrollTop() ) >= $('#vendors-purchases-purchases').height() ) {
					if( $('#vendors-purchases-loadpurchases').is(':visible') ||
						$('#vendors-purchases-endpurchases').is(':visible') ) {
						// Do nothing - we're already loading...
					} else {
						loadMorePurchases();
					}
				}
			});
			*/

			// Check for a default expense ID.
			if( $('#vendors-purchases-create-requested_purchase_id').length > 0 &&
				$('#vendors-purchases-create-requested_purchase_id').val().length > 0 ) {
				loadPurchase($('#vendors-purchases-create-requested_purchase_id').val());
			}
		}

		$('#vendors-purchases-purchases .vendor-purchase .view').live('click',function() {
			// If we're on the expenses page we AJAX - otherwise... return true.
			if( $('#vendors-purchases-create').length == 0 ) {
				return true;
			}
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadPurchase($(this).closest('.vendor-purchase').attr('rel'));
			return false;
		});

		$('#vendors-purchases-create input,#vendors-purchases-create select').live('change',function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		var cancel_vendor_purchase_id = '';
		function cancelVendorPurchase() {
			showPleaseWait();
			$purchaseLine = $('#vendors-purchases-purchases .vendor-purchase[rel="'+cancel_vendor_purchase_id+'"]');
			$.post(
				'/vendors/json/purchasecancel',
				{
					purchase_id: cancel_vendor_purchase_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						if( $purchaseLine.length > 0 ) {
							$purchaseLine.slideUp(function() {
								$purchaseLine.remove();
								rowElementsColorVisible($('#vendors-purchases-purchases'));
							});
						}
						if( $('#vendors-purchases-create').attr('rel') &&
							$('#vendors-purchases-create').attr('rel') == cancel_vendor_purchase_id ) {
							createPurchaseClearForm();
						}
					}
				},
				'json'
			);
		}

		$('#vendors-purchases-purchases .vendor-purchase .cancel').live('click',function(e) {
			e.preventDefault();
			cancel_vendor_purchase_id = $(this).closest('.vendor-purchase').attr('rel');
			showConfirm("Are you certain you want to delete this purchase purchase?","Yes, Delete.","No.",cancelVendorPurchase);
			return false;
		});

		$('#vendors-purchases-purchases .vendor-purchase .refund').live('click',function() {
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadPurchase($(this).closest('.vendor-purchase').attr('rel'),true);
			return false;
		});

		$('#vendors-purchases-purchases .vendor-purchase .send').live('click',function(e) {
			e.preventDefault();
			$purchase = $(this).closest('.vendor-purchase');
			// Check if we've already got a send template loaded.
			if( $('#vendors-purchases-purchases .vendor-purchase-send[rel="'+$purchase.attr('rel')+'"]').length > 0 ) {
				$('#vendors-purchases-purchases .vendor-purchase-send[rel="'+$purchase.attr('rel')+'"]').slideUp(function() {
					$(this).remove();
				});
			} else {
				$sendPurchase = $($('#vendors-purchases-send-template').html());
				$sendPurchase.addClass('hidden');
				$sendPurchase.attr('rel',$purchase.attr('rel'));
				$sendPurchase.find('input[name="email"]').val($purchase.find('input.email').val());
				$purchase.after($sendPurchase);
				$sendPurchase.slideDown();
			}
		});

		$('#vendors-purchases-purchases .vendor-purchase-send .vendor-purchase-send-email .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$doneCheckbox = $(this).closest('.vendor-purchase-send').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
			}
		});

		$('#vendors-purchases-purchases .vendor-purchase-send .vendor-purchase-send-mail .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$doneCheckbox = $(this).closest('.vendor-purchase-send').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
			}
		});

		$('#vendors-purchases-purchases .vendor-purchase-send .vendor-purchase-send-done .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$emailCheckbox = $(this).closest('.vendor-purchase-send').find('input[name="send-email"]');
				$mailCheckbox = $(this).closest('.vendor-purchase-send').find('input[name="send-mail"]');
				$emailCheckbox.attr('checked',false);
				$mailCheckbox.attr('checked',false);
				checkboxUpdate($emailCheckbox);
				checkboxUpdate($mailCheckbox);
			}
		});

		$('#vendors-purchases-purchases .vendor-purchase-send .send-cancel').live('click',function(e) {
			e.preventDefault();
			$sendPurchase = $(this).closest('li.vendor-purchase-send');
			$sendPurchase.slideUp(function() {
				$sendPurchase.remove();
			});
		});

		$('#vendors-purchases-purchases .vendor-purchase-send .send-submit').live('click',function(e) {
			e.preventDefault();
			showPleaseWait();
			var print = false;
			if( $(this).closest('.vendor-purchase-send').find('input[name="send-mail"]').is(':checked') ) {
				print = true;
			}
			$.post(
				'/vendors/json/purchasesend',
				$(this).closest('.vendor-purchase-send').find('input').serialize()+'&purchase_id='+$(this).closest('.vendor-purchase-send').attr('rel'),
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$oldPurchase = $('#vendors-purchases-purchases .vendor-purchase:not(.vendor-purchase-send)[rel="'+data.data.purchase.id+'"]');
						$sendPurchase = $('#vendors-purchases-purchases .vendor-purchase.vendor-purchase-send[rel="'+data.data.purchase.id+'"]');
						$newPurchase = $(data.data.purchase.html)
						$newPurchase.addClass('hidden');
						$oldPurchase.after($newPurchase);
						$sendPurchase.slideUp();
						$oldPurchase.slideUp(function() {
							$newPurchase.slideDown(function() {
								rowElementsColorVisible($('#vendors-purchases-purchases'));
							});
							$oldPurchase.remove();
							$sendPurchase.remove();
						});
						
						// TODO FANCY ANIMATION COLORS 
						if( print ) {
							printVendorPurchase(data.data.purchase.id);
						}
					}
				},
				'json'
			);
		});
		
		/**
		 *
		 *
		 * 
		 */

		 $('#vendors-purchases-create-form-send .checkbox').live('click', function (e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				if( $checkbox.attr('name') == "send-done" ) {
					$emailCheckbox = $('#vendors-purchases-create-form-send input[name="send-email"]');
					$mailCheckbox = $('#vendors-purchases-create-form-send input[name="send-mail"]');
					$emailCheckbox.attr('checked',false);
					$mailCheckbox.attr('checked',false);
					checkboxUpdate($emailCheckbox);
					checkboxUpdate($mailCheckbox);
				} else {
					$doneCheckbox = $('#vendors-purchases-create-form-send input[name="send-done"]');
					$doneCheckbox.attr('checked',false);
					checkboxUpdate($doneCheckbox);
				}
			}
		});
		
		/**
		 * STARTER
		 */

		// TODO - Known bug, we're not checking for invoice ID to be valid.
		$('#vendors-purchases-create-form-send-submit').live('click', function (e) {
			e.preventDefault();
			if( $('#vendors-purchases-create-form-send').attr('rel') == "send" ) {
				showPleaseWait();
				$.post(
					'/vendors/json/purchasesend',
					$('#vendors-purchases-create-form-send').find('input').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							$('#vendors-purchases-create-form-send').slideUp();
							
							if( data.data.purchase.payments.length > 0 ) {
								$('#vendors-purchases-create-status').html('<span class="text-bold">'+data.data.purchase.status+' - Payments: </span>');
								var first = true;
								for( i in data.data.purchase.payments ) {
									if( first ) {
										first = false;
									} else {
										$('#vendors-purchases-create-status').append(',');
									}
									$('#vendors-purchases-create-status').append(' <a href="/vendors/payments/'+data.data.purchase.payments[i].id+'">'+data.data.purchase.payments[i].date+'</a>');
								}
							} else {
								$('#vendors-purchases-create-status').html('<span class="text-bold">'+data.data.purchase.status+'</span>');
							}

							// TODO FANCY COLOR ANIMATION
							if( $('#vendors-purchases-create-form-send input[name="send-mail"]').is(':checked') ) {
								printVendorPurchase(data.data.purchase.id);
							}
						}
					},
					'json'
				);
			} else {
				showPleaseWait();
				createPurchaseIndexLines();
				// Validate First
				$.post(
					'/vendors/json/purchasesendvalidate',
					$('#vendors-purchases-create-form-send').find('input').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
					function(datavalid) {
						if( datavalid.success != 1 ) {
							hidePleaseWait();
							showError(datavalid.error);
						} else {
							if( $('#vendors-purchases-create').attr('rel') &&
								$('#vendors-purchases-create').attr('rel') == "R" ) {
								// REFUND
								// Re-enable all disabled fields.
								// TODO - disabled fields should be readonly instead
								$('#vendors-purchases-create input[disabled],#vendors-purchases-create select[disabled]').each(function() {
									$(this).attr('disabled',false).focus().blur();
								});
								createPurchaseUpdateTotals();
								$.post(
									'/vendors/json/purchaserefund',
									$('#vendors-purchases-create input,#vendors-purchases-create select').serialize(),
									function(datacreate) {
										if( datacreate.success != 1 ) {
											hidePleaseWait();
											showError(data.error);
										} else {

											$('#vendors-purchases-create').attr('rel',datacreate.data.purchase.id);

											$.post(
												'/vendors/json/purchasesend',
												$('#vendors-purchases-create-form-send').find('input').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
												function(data) {
													hidePleaseWait();
													if( data.success != 1 ) {
														showError(data.error);
													} else {
														$('#vendors-purchases-create-form-send').slideUp();
														
														$oldPurchase = $('#vendors-purchases-purchases .vendor-purchase[rel="'+$('#vendors-purchases-create input[name="refund_purchase_id"]').val()+'"]:first-child');
														createPurchaseClearForm();
														$newPurchase = $(data.data.purchase.html);
														$newPurchase.addClass('hidden');
														$oldPurchase.find('a.refund').remove();
														$('#vendors-purchases-purchases .vendor-purchase:first').after($newPurchase);
														$newPurchase.slideDown(function() {
															rowElementsColorVisible($('#vendors-purchases-purchases'));
														});
														// TODO - ADD COLOR ANIMATION
														GLOBAL_EDIT_FORM_ACTIVE = false;
														
														if( data.data.purchase.payments.length > 0 ) {
															$('#vendors-purchases-create-status').html('<span class="text-bold">'+data.data.purchase.status+' - Payments: </span>');
															var first = true;
															for( i in data.data.purchase.payments ) {
																if( first ) {
																	first = false;
																} else {
																	$('#vendors-purchases-create-status').append(',');
																}
																$('#vendors-purchases-create-status').append(' <a href="/vendors/payments/'+data.data.purchase.payments[i].id+'">'+data.data.purchase.payments[i].date+'</a>');
															}
														} else {
															$('#vendors-purchases-create-status').html('<span class="text-bold">'+data.data.purchase.status+'</span>');
														}

														// TODO FANCY COLOR ANIMATION
														if( $('#vendors-purchases-create-form-send input[name="send-mail"]').is(':checked') ) {
															printVendorPurchase(data.data.purchase.id);
														}
													}
												},
												'json'
											);
											
										}
									},
									'json'
								);
							} else if( $('#vendors-purchases-create').attr('rel') &&
								$('#vendors-purchases-create').attr('rel').length ) {
								// UPDATE
								$.post(
									'/vendors/json/purchaseupdate',
									$('#vendors-purchases-create input,#vendors-purchases-create select').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
									function(datacreate) {
										hidePleaseWait();
										if( datacreate.success != 1 ) {
											showError(datacreate.error);
										} else {
											
											$('#vendors-purchases-create').attr('rel',datacreate.data.purchase.id);

											$.post(
												'/vendors/json/purchasesend',
												$('#vendors-purchases-create-form-send').find('input').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
												function(data) {
													hidePleaseWait();
													if( data.success != 1 ) {
														showError(data.error);
													} else {
														$('#vendors-purchases-create-form-send').slideUp();
														createPurchaseClearForm();
											
														$newPurchase = $(data.data.purchase.html);
														$newPurchase.addClass('hidden');

														if( $('#vendors-purchases-purchases .vendor-purchase[rel="'+data.data.purchase.id+'"]').length > 0 ) {
															$oldPurchase = $('#vendors-purchases-purchases .vendor-purchase[rel="'+data.data.purchase.id+'"]:first');
															$oldPurchase.before($newPurchase);
															$oldPurchase.slideUp();
														} else {
															$('#vendors-purchases-purchases .vendor-purchase:first').after($newPurchase);
														}

														$newPurchase.slideDown(function() {
															rowElementsColorVisible($('#vendors-purchases-purchases'));
														});
														GLOBAL_EDIT_FORM_ACTIVE = false;
														// TODO - ADD COLOR ANIMATION
														if( $('#vendors-purchases-create-form-send input[name="send-mail"]').is(':checked') ) {
															printVendorPurchase(data.data.purchase.id);
														}
													}
												},
												'json'
											);
										}
									},
									'json'
								);
							} else {
								// NEW
								$.post(
									'/vendors/json/purchasecreate',
									$('#vendors-purchases-create input,#vendors-purchases-create select').serialize(),
									function(datacreate) {
										hidePleaseWait();
										if( datacreate.success != 1 ) {
											showError(datacreate.error);
										} else {
											$('#vendors-purchases-create').attr('rel',datacreate.data.purchase.id);

											$.post(
												'/vendors/json/purchasesend',
												$('#vendors-purchases-create-form-send').find('input').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
												function(data) {
													hidePleaseWait();
													if( data.success != 1 ) {
														showError(data.error);
													} else {
														createPurchaseClearForm();
														$newPurchase = $(data.data.purchase.html);
														$newPurchase.addClass('hidden');
														$('#vendors-purchases-purchases .vendor-purchase:first').after($newPurchase);
														$newPurchase.slideDown(function() {
															$noPurchases = $('#vendors-purchases-purchases .vendor-purchase:last');
															if( $noPurchases.find('span').length == 0 ) {
																$noPurchases.slideUp(function() {
																	$noPurchases.remove();
																});
															}
															rowElementsColorVisible($('#vendors-purchases-purchases'));
														});
														GLOBAL_EDIT_FORM_ACTIVE = false;
														if( $('#vendors-purchases-create-form-send input[name="send-mail"]').is(':checked') ) {
															printVendorPurchase(data.data.purchase.id);
														}
													}
												},
												'json'
											);
										}
									},
									'json'
								);
							}
						}
					},
					'json'
				);
			}
		});
				
		$('#vendors-purchases-create-form-send-cancel').live('click', function (e) {
			e.preventDefault();
			$('#vendors-purchases-create-form-send').slideUp();
		});

		$('#vendors-purchases-create-form-savesend').live('click', function (e) {
			e.preventDefault();
			if( $('#vendors-purchases-create-form-send').is(':visible') ) {
				$('#vendors-purchases-create-form-send').slideUp();
			} else {
				$('#vendors-purchases-create-form-send').attr('rel','save');
				$('#vendors-purchases-create-form-send').slideDown();
			}
		});

		$('#vendors-purchases-create-form-onlysend').live('click', function (e) {
			e.preventDefault();
			if( $('#vendors-purchases-create-form-send').is(':visible') ) {
				$('#vendors-purchases-create-form-send').slideUp();
			} else {
				$('#vendors-purchases-create-form-send').attr('rel','send');
				$('#vendors-purchases-create-form-send').slideDown();
			}
		});

		$('#vendors-purchases-purchases .vendor-purchase .print').live('click',function(e) {
			e.preventDefault();
			printVendorPurchase($(this).closest('li.vendor-purchase').attr('rel'));
		});

		$('#vendors-purchases-purchases-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-purchases-purchases li.vendor-purchase:not(:first-child)').remove();
	 			$('#vendors-purchases-endpurchases').hide();
	 			$('#vendors-purchases-loadpurchases').show();
	 			loadMorePurchases();
	 		}
		});

		
		$('#vendors-purchases-create-form-cancel').click(function (e) {
			e.preventDefault();
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					if( $('#vendors-purchases-create').attr('rel').length > 0 ) {
						if( $('#vendors-purchases-create').attr('rel') &&
							$('#vendors-purchases-create').attr('rel') == "R" ) {
							loadPurchase($('#vendors-purchases-create input[name="refund_purchase_id"]').val());
						} else {
							loadPurchase($('#vendors-purchases-create').attr('rel'));
						}
					} else {
						createPurchaseClearForm();
					}
				}
			} else {
				if( $('#vendors-purchases-create').attr('rel') &&
					$('#vendors-purchases-create').attr('rel').length > 0 ) {
					if( $('#vendors-purchases-create').attr('rel') == "R" ) {
						loadPurchase($('#vendors-purchases-create input[name="refund_purchase_id"]').val());
					} else {
						loadPurchase($('#vendors-purchases-create').attr('rel'));
					}
				} else {
					createPurchaseClearForm();
				}
			}
			return false;
		});

		$('#vendors-purchases-create-form-delete').click(function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') && $(this).attr('disabled').length ) {
				return false;
			}
			cancel_vendor_purchase_id = $('#vendors-purchases-create').attr('rel');
			showConfirm("Are you certain you want to delete this purchase purchase?","Yes, Delete.","No.",cancelVendorPurchase);
			return false;
		});

		$('#vendors-purchases-create-form-return').click(function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') && $(this).attr('disabled').length ) {
				return false;
			}
			loadPurchase($('#vendors-purchases-create').attr('rel'),true);
		});

		$('#vendors-purchases-create-form-print').click(function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') && $(this).attr('disabled').length ) {
				return false;
			}
			printVendorPurchase($('#vendors-purchases-create').attr('rel'));
		});

		$('#vendors-purchases-create-form-editcancel').click(function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') && $(this).attr('disabled').length ) {
				return false;
			}
			createPurchaseClearForm();
			return false;
		});

		$('#vendors-purchases-create-form-edit').click(function(e) {
			e.preventDefault();
			if( $(this).attr('disabled') && $(this).attr('disabled').length ) {
				return false;
			}
			if( ! $('#vendors-purchases-create').attr('rel') ||
				$('#vendors-purchases-create').attr('rel').length == 0 ) {
				showError("An unexpected error has occurred.<br>You should reload the page before going any further.");
				return;
			}
			$('#vendors-purchases-create input:not(.ezpz-hint,.send-form,.datepicker),#vendors-purchases-create select').each(function() {
				$(this).attr('disabled',false).focus().blur();
			});
			$('#vendors-purchases-create input.datepicker').each(function() {
				$(this).attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
			});
			$('#vendors-purchases-create-form-lines select.account_id').each(function () {
				$(this).select2("enable");
			});

			$('#vendors-purchases-create input[name="shipping_address_id"]').select2('enable');
			$('#vendors-purchases-create div.select').removeClass('disabled');
			$('#vendors-purchases-create input[name="vendor"]').select2('disable');
			$('#vendors-purchases-create .vendor-purchases-create-new-buttons').show();
			$('#vendors-purchases-create .vendor-purchases-create-edit-buttons').hide();
			return false;
		});

		$('#vendors-purchases-create-form-save').click(function(e) {
			e.preventDefault();
			showPleaseWait();
			// Serialize and submit.
			createPurchaseIndexLines();
			if( $('#vendors-purchases-create').attr('rel') &&
				$('#vendors-purchases-create').attr('rel') == "R" ) {
				// REFUND
				// Re-enable all disabled fields.
				$('#vendors-purchases-create input[disabled],#vendors-purchases-create select[disabled]').each(function() {
					$(this).attr('disabled',false).focus().blur();
				});
				$('#vendors-purchases-create input[name="vendor"]').select2('enable');
				$('#vendors-purchases-create input[name="shipping_address_id"]').select2('enable');
				createPurchaseUpdateTotals();
				$.post(
					'/vendors/json/purchaserefund',
					$('#vendors-purchases-create input,#vendors-purchases-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							$oldPurchase = $('#vendors-purchases-purchases .vendor-purchase[rel="'+$('#vendors-purchases-create input[name="refund_purchase_id"]').val()+'"]:first-child');
							createPurchaseClearForm();
							$newPurchase = $(data.data.purchase.html);
							$newPurchase.addClass('hidden');
							$oldPurchase.find('a.refund').remove();
							$('#vendors-purchases-purchases .vendor-purchase:first').after($newPurchase);

							// TODO - ADD COLOR ANIMATION
							GLOBAL_EDIT_FORM_ACTIVE = false;
							$newPurchase.slideDown(function () {
								rowElementsColorVisible($('#vendors-purchases-purchases'));
							});
						}
					},
					'json'
				);
			} else if( $('#vendors-purchases-create').attr('rel') &&
				$('#vendors-purchases-create').attr('rel').length ) {
				// UPDATE
				$.post(
					'/vendors/json/purchaseupdate',
					$('#vendors-purchases-create input,#vendors-purchases-create select').serialize()+'&purchase_id='+$('#vendors-purchases-create').attr('rel'),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createPurchaseClearForm();
							
							$newPurchase = $(data.data.purchase.html);
							$newPurchase.addClass('hidden');
							if( $('#vendors-purchases-purchases .vendor-purchase[rel="'+data.data.purchase.id+'"]').length > 0 ) {
								$oldPurchase = $('#vendors-purchases-purchases .vendor-purchase[rel="'+data.data.purchase.id+'"]:first');
								$oldPurchase.before($newPurchase);
								$oldPurchase.slideUp(function() {
									$oldPurchase.remove();
								});
							} else {
								$('#vendors-purchases-purchases .vendor-purchase:first').after($newPurchase);
							}
							
							// TODO - ADD COLOR ANIMATION
							GLOBAL_EDIT_FORM_ACTIVE = false;
							$newPurchase.slideDown(function() {
								rowElementsColorVisible($('#vendors-purchases-purchases'));
							});							
						}
					},
					'json'
				);
			} else {
				// NEW
				$.post(
					'/vendors/json/purchasecreate',
					$('#vendors-purchases-create input,#vendors-purchases-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createPurchaseClearForm();
							$newPurchase = $(data.data.purchase.html);
							$newPurchase.addClass('hidden');
							$('#vendors-purchases-purchases .vendor-purchase:first').after($newPurchase);
							$newPurchase.slideDown();
							
							$noPurchases = $('#vendors-purchases-purchases .vendor-purchase:last');
							if( $noPurchases.find('span').length == 0 ) {
								$noPurchases.slideUp(function() {
									$noPurchases.remove();
								});
							}

							// TODO - ADD COLOR ANIMATION
							GLOBAL_EDIT_FORM_ACTIVE = false;
							rowElementsColorVisible($('#vendors-purchases-purchases'));
						}
					},
					'json'
				);
			}
		});

		$('#vendors-purchases-create input[placeholder]').ezpz_hint();

		$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line select.account_id,#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line input.line-description').live('change',function() {
			if( $(this).val() &&
				$(this).val().length &&
				$(this).closest('.vendors-purchases-create-form-lines-line').is(':last-child') ) {
				$newPurchaseLine = $($('#vendors-purchases-create-form-lines-line-template').html());
				$newPurchaseLine.addClass('hidden');
				$('#vendors-purchases-create-form-lines').append($newPurchaseLine);
				$newPurchaseLine.find('input.line-description').autocomplete(purchaseDescriptionParams);
				$newPurchaseLine.slideDown(function () {
					$newPurchaseLine.css('overflow','');
					$newPurchaseLine.find('select.account_id').accountDropdown();
				});
			}
		});

		$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line input.line-quantity,#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line input.line-price').live('change',function() {
			createPurchaseUpdateTotals();
		});

		$('#vendors-purchases-create input[name="vendor"]').select2({
			minimumInputLength: 1,
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/vendors/json/vendorsformsearch",
				type: "POST",
				dataType: 'json',
				data: function (term) {
					return {
						last_vendor_id: '',
						search_terms: term, // search term
						count: 1000,
					};
				},
				results: function (data) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					var results = new Array();
					for( index in data.data.vendors ) {
						results[index] = {
							text: data.data.vendors[index].display_name,
							id: 
								data.data.vendors[index].id
								+'#'+
								( typeof data.data.vendors[index].default_remit_address_id != "undefined" &&
									data.data.vendors[index].default_remit_address_id != null &&
									data.data.vendors[index].default_remit_address_id.length ? data.data.vendors[index].default_remit_address_id : '' )
								+'#'+
								( typeof data.data.vendors[index].default_account != "undefined" && 
									data.data.vendors[index].default_account != null && 
									typeof data.data.vendors[index].default_account.id != "undefined" &&
									data.data.vendors[index].default_account.id != null &&
									data.data.vendors[index].default_account.id.length ? data.data.vendors[index].default_account.id : '' )
								+'#'+
								( typeof data.data.vendors[index].default_account != "undefined" && 
									data.data.vendors[index].default_account != null && 
									typeof data.data.vendors[index].default_account.id != "undefined" ? data.data.vendors[index].default_account.terms : '' )
						};
					}
					return {results: results};
				}
			}
		});

		$('#vendors-purchases-create input[name="vendor"]').change(function() {
			if( ! $(this).val() ||
				! $(this).val().length ) {
				$('#vendors-purchases-create select[name="remit_address_id"] option[value!=""]').remove();
				return true;
			}
			var vendor = $(this).val().split('#');
			showPleaseWait();
			
			if( vendor[2].length ) {
				if( $('#vendors-purchases-create select[name="account"] option[value="'+vendor[2]+'#'+vendor[3]+'"]').is(":enabled") ) {
					$('#vendors-purchases-create select[name="account"]').select2('data',{
						id: vendor[2]+'#'+vendor[3],
						text: $('#vendors-purchases-create select[name="account"] option[value="'+vendor[2]+'#'+vendor[3]+'"]').text()
					});
				} else {
					$('#vendors-purchases-create select[name="account"]').select2('data',{
						id: $('#vendors-purchases-create select[name="account"]').attr('data-default'),
						text: $('#vendors-purchases-create select[name="account"] option[value="'+$('#vendors-purchases-create select[name="account"]').attr('data-default')+'"]').text()
					});
				}
			} else {
				$('#vendors-purchases-create select[name="account"]').select2('data',{
					id: $('#vendors-purchases-create select[name="account"]').attr('data-default'),
					text: $('#vendors-purchases-create select[name="account"] option[value="'+$('#vendors-purchases-create select[name="account"]').attr('data-default')+'"]').text()
				});
			}


			$.post(
				'/vendors/json/vendoraddresses',
				{
					vendor_id: vendor[0]
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('#vendors-purchases-create select[name="remit_address_id"] option[value!=""]').remove();
						$('#vendors-purchases-create-form-send input[name="email"]').val(data.data.vendor.email);
						for( var index in data.data.addresses ) {
							$('#vendors-purchases-create select[name="remit_address_id"]').append('<option value="'+data.data.addresses[index].id+'">'+data.data.addresses[index].address1+'</option>');
						}
						$('#vendors-purchases-create select[name="remit_address_id"]').val(vendor[1]);
						if( (
								! $('#vendors-purchases-create select[name="remit_address_id"]').val() ||
								! $('#vendors-purchases-create select[name="remit_address_id"]').val().length
							) &&
							$('#vendors-purchases-create select[name="remit_address_id"] option[value!=""]').length == 1 ) {
							$('#vendors-purchases-create select[name="remit_address_id"]').val($('#vendors-purchases-create select[name="remit_address_id"] option[value!=""]').val());
						}
						$('#vendors-purchases-create select[name="remit_address_id"]').focus();
					}
				},
				'json'
			);
		});

		/**
		 * PO Shipping Address
		 */
		$('#vendors-purchases-create input[name="shipping_address_id"]').select2({
			minimumInputLength: 1,
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/vendors/json/shippingaddresssearch",
				type: "POST",
				dataType: 'json',
				data: function (term) {
					return {
						keywords: term,
					};
				},
				results: function (data) {
					if( data.success != 1 ) 
						return {results: []};

					var results = new Array();
					for( index in data.data.addresses ) {
						results[index] = {
							text: data.data.addresses[index].standard,
							id: data.data.addresses[index].id
						};
					}
					return {results: results};
				}
			}
		});


		/**
		 * Vendors / Vendors
		 */

		/*
		if( $('#vendors-vendors-vendors').length > 0 ) {
			$(window).scroll(function () { 
				if( ( $(window).height() + $(window).scrollTop() ) >= $('#vendors-vendors-vendors').height() ) {
					if( $('#vendors-vendors-loadvendors').is(':visible') ||
						$('#vendors-vendors-endvendors').is(':visible') ) {
						// Do nothing - we're already loading...
					} else {
						loadMoreVendors();
					}
				}
			});
		}
		*/
		
		// vendors SEARCH
		$('#vendors-vendors-vendors-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-vendors-vendors').attr('rel','');
	 			$('#vendors-vendors-vendors li.vendor-vendor:not(:first-child)').remove();
	 			$('#vendors-vendors-endvendors').hide();
	 			$('#vendors-vendors-loadvendors').show();
	 			loadMoreVendors();
	 		}
		});
		
		/*
		$('#vendors-vendors-create-button').click(function(e) {
			e.preventDefault();
			$form = $('#vendors-vendors-create');
			if( $form.is(':visible') ) {
				$form.slideUp();
			} else {
				$form.slideDown();
			}
		});
		 */
		
		$('#vendors-vendors-create-form input, #vendors-vendors-create-form select').change( function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('#vendors-vendors-create-form-address-save').click(function(e) {
			e.preventDefault();
			$form = $('#vendors-vendors-create-form');
			var valid = true;
			$form.find('input.address.required,select.address.required').each(function() {
				if( $(this).val().length == 0 ) {
					valid = false;
				}
			});
			if( ! valid ) {
				showError("Please fill in all required fields:<br>Street Address, City, Zip, Country.");
				return;
			}
			$template = $($('#vendors-vendors-create-form-addresses-address-template').html());
			if( $form.attr('rel') &&
				$form.attr('rel').length > 0 ) {
				$template = $('.vendors-vendors-create-form-addresses-address[rel="'+$form.attr('rel')+'"]');
				$template.fadeOut();
			}
			$form.find('input.address,select.address').each(function() {
				if( $(this).attr('type') == "checkbox" ) {
					if( $(this).is(":checked") ) {
						// Hide any others.
						$('input.'+$(this).attr('rel')).val('0');
						$('span.'+$(this).attr('rel')).hide();
						$template.find('input.'+$(this).attr('rel')).val('1');
						$template.find('span.'+$(this).attr('rel')).show();
					} else {
						$template.find('input.'+$(this).attr('rel')).val('0');
						$template.find('span.'+$(this).attr('rel')).hide();
					}
					$(this).attr('checked',false);
					$(this).closest('.checkbox').removeClass('checked');
				} else {
					$template.find('input.'+$(this).attr('rel')).val($(this).val());
					$template.find('span.'+$(this).attr('rel')).text($(this).val());
					$(this).val('');
				}
			});
			$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());

			$('#vendors-vendors-create-form-address-canceledit').attr('disabled','disabled');
			if( $template.is(':visible') ) {
				$template.fadeIn();
			} else {
				$('#vendors-vendors-create-form-addresses .clear').before($template);
			}
			$template.slideDown();
			$form.attr('rel','');
			$form.find('input[rel="address1"]').focus();
			return false;
		});

		$('#vendors-vendors-create-form-cancel').click(function (e) {
			e.preventDefault();
			$form = $('#vendors-vendors-create-form');
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#vendors-vendors-create').slideUp(function() {
						$form.find('input,select').each(function() {
							if( $(this).is(':checkbox') ) {
								$(this).attr('checked','checked');
								checkboxUpdate($(this));
							} else {
								$(this).val('');
							}
						});
						$form.find('select[name="default_account_id"]').select2('data',{});
						if( $form.find('select[name="default_account_id"]').attr('rel') &&
							$form.find('select[name="default_account_id"]').attr('rel').length > 0 ) {
							$form.find('select[name="default_account_id"]').select2('data',{
								id: $form.find('select[name="default_account_id"]').attr('rel'),
								text: $form.find('select[name="default_account_id"] option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
							});
						}
						$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());
						$form.find('.vendors-vendors-create-form-addresses-address').each(function() {
							$(this).remove();
						});
						GLOBAL_EDIT_FORM_ACTIVE = false;
						$('#vendors-vendors-create').slideDown();
					});
				}
			} else {
				$('#vendors-vendors-create').slideUp(function() {
					$form.find('input,select').each(function() {
						if( $(this).is(':checkbox') ) {
							$(this).attr('checked','checked');
							checkboxUpdate($(this));
						} else {
							$(this).val('');
						}
					});
					$form.find('select[name="default_account_id"]').select2('data',{});
					if( $form.find('select[name="default_account_id"]').attr('rel') &&
						$form.find('select[name="default_account_id"]').attr('rel').length > 0 ) {
						$form.find('select[name="default_account_id"]').select2('data',{
							id: $form.find('select[name="default_account_id"]').attr('rel'),
							text: $form.find('select[name="default_account_id"] option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
						});
					}
					$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());
					$form.find('.vendors-vendors-create-form-addresses-address').each(function() {
						$(this).remove();
					});
					GLOBAL_EDIT_FORM_ACTIVE = false;
					$('#vendors-vendors-create').slideDown();
				});
			}
		});

		$('#vendors-vendors-create-form-save').click(function(e) {
			e.preventDefault();
			var valid = true;
			$form = $('#vendors-vendors-create-form');
			
			var address = false;
			$form.find('input.address,select.address').each(function() {
				if( $(this).val() &&
					$(this).val().length &&
					$(this).attr('rel') != "country" &&
					$(this).attr('rel') != "default-remit" ) {
					address = true;
				}
			});
			if( address ) {
				var addressvalid = true;
				$form.find('input.address.required,select.address.required').each(function() {
					if( $(this).val().length == 0 ) {
						addressvalid = false;
					}
				});
				if( ! addressvalid ) {
					showError("Please fill in all required fields:<br>Street Address, City, Zip, Country.");
					return;
				}
				$('#vendors-vendors-create-form-address-save').click();
			}
			
			var addressIndex = createVendorIndexAddresses($form);
			
			showPleaseWait();
			$.post(
				'/vendors/json/vendorcreate',
				$form.find('input,select').serialize(),
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						GLOBAL_EDIT_FORM_ACTIVE = false;
						// Clear Form
						$form.find('input,select').each(function() {
							if( $(this).is(':checkbox') ) {
								$(this).attr('checked','checked');
								checkboxUpdate($(this));
							} else {
								$(this).val('');
							}
						});

						if( $form.find('select[name="default_account_id"]').attr('rel') &&
							$form.find('select[name="default_account_id"]').attr('rel').length > 0 ) {
							$form.find('select[name="default_account_id"]').val($form.find('select[name="default_account_id"]').attr('rel'));
						}

						$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());
						
						$form.find('.vendors-vendors-create-form-addresses-address').each(function() {
							$(this).remove();
						});
						
						// Add Vendor to top.
						$vendor = $(data.data.vendor.html);
						$vendor.addClass('hidden');
						$('#vendors-vendors-vendors li:first').after($vendor);
						$vendor.slideDown();
						
						// Remove "No vendors Found" if exists.
						$noVendor = $('#vendors-vendors-vendors li:last');
						if( $noVendor.find('span').length == 0 ) {
							$noVendor.slideUp(function() {
								$noVendor.remove();
							});
						}

						GLOBAL_EDIT_FORM_ACTIVE = false;
						rowElementsColorVisible($('#vendors-vendors-vendors'));
					}
				},
				'json'
			);

		});
		
		$('.vendors-vendors-create-form-addresses-address a.edit').live('click',function() {
			$address = $(this).closest('.vendors-vendors-create-form-addresses-address');
			$form = $('#vendors-vendors-create-form');
			createVendorIndexAddresses($form);
			$form.find('input.address,select.address').each(function() {
				if( $(this).attr('type') == "checkbox" ) {
					$(this).attr('checked',false);
					$(this).closest('.checkbox').removeClass('checked');
					if( $address.find('input.'+$(this).attr('rel')).val() == "1" ) {
						$(this).attr('checked',true);
						$(this).closest('.checkbox').addClass('checked');
					}
				} else {
					$(this).val($address.find('input.'+$(this).attr('rel')).val());
				}
			});
			$form.attr('rel',$address.attr('rel'));
			$('#vendors-vendors-create-form-address-canceledit').attr('disabled',false);
		});

		$('#vendors-vendors-create-form-address-canceledit').click(function() {
			$(this).attr('disabled','disabled');
			$form = $('#vendors-vendors-create-form');
			$form.attr('rel','');
			$form.find('input.address,select.address').each(function() {
				if( $(this).attr('type') == "checkbox" ) {
					$(this).attr('checked',false);
					$(this).closest('.checkbox').removeClass('checked');
				} else {
					$(this).val('');
				}
			});
			return false;
		});



		/**
		 * Vendors / Vendor
		 */
		
		$('#vendors-vendor-edit input,#vendors-vendor-edit select').focus(function() {
			if( $('.edit-buttons-placeholder:visible').length > 0 ) {
				$('.edit-buttons-placeholder').hide();
				$('.edit-buttons').show();
			}
			GLOBAL_EDIT_FORM_ACTIVE = true;
			return true;
		});

		$('#vendors-vendor-edit-cancel').click(function (e) {
			e.preventDefault();
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#vendors-vendor-edit input[type="text"],#vendors-vendor-edit select').each(function() {
						$(this).val($('#vendors-vendor-edit input[type="hidden"].'+($(this).attr('name'))).val());
					});
					$('#vendors-vendor-edit select[name="default_account_id"]').select2('data',{
						id: $('#vendors-vendor-edit select[name="default_account_id"]').attr('rel'),
						text: $('#vendors-vendor-edit select[name="default_account_id"] option[value="'+$('#vendors-vendor-edit select[name="default_account_id"]').attr('rel')+'"]').text()
					});
					GLOBAL_EDIT_FORM_ACTIVE = false;
					$('.edit-buttons').hide();
					$('.edit-buttons-placeholder').show();
				}
			} else {
				$('#vendors-vendor-edit input[type="text"],#vendors-vendor-edit select').each(function() {
					$(this).val($('#vendors-vendor-edit input[type="hidden"].'+($(this).attr('name'))).val());
				});
				$('#vendors-vendor-edit select[name="default_account_id"]').select2('data',{
					id: $('#vendors-vendor-edit select[name="default_account_id"]').attr('rel'),
					text: $('#vendors-vendor-edit select[name="default_account_id"] option[value="'+$('#vendors-vendor-edit select[name="default_account_id"]').attr('rel')+'"]').text()
				});
				GLOBAL_EDIT_FORM_ACTIVE = false;
				$('.edit-buttons').hide();
				$('.edit-buttons-placeholder').show();
			}
			return false;
		});

		$('#vendors-vendor-edit-save').click(function (e) {
			e.preventDefault();
			showPleaseWait();
			$.post(
				'/vendors/json/vendorupdate',
				$('#vendors-vendor-edit input,#vendors-vendor-edit select').serialize(),
				function(data) {
					if( data.success == 1 ) {
						$('.edit-buttons').hide();
						$('.edit-buttons-placeholder').show();
						GLOBAL_EDIT_FORM_ACTIVE = false;
						window.location.reload();
					} else {
						hidePleaseWait();
						showError(data.error);
					}
				},
				'json'
			);
		});

		$('#vendors-vendor-address-add').click(function (e) {
			e.preventDefault();
			$form = $('#vendors-vendor-address-form');
			if( $form.is(':visible') ) {
				if( $form.attr('rel') && 
					$form.attr('rel').length > 0 ) {
					$form.slideUp(function() {
						$form.attr('rel','');
						$form.find('input[type="text"],select').val('');
						$form.find('input[type="checkbox"]').each(function() {
							$(this).attr('checked',false);
							checkboxUpdate($(this));
						});
						$form.slideDown();
						$('#vendors-vendor-address-form-canceledit').hide();
					});
				} else {
					$form.slideUp(function() {
						$form.find('input[type="text"],select').val('');
						$form.find('input[type="checkbox"]').each(function() {
							$(this).attr('checked',false);
							checkboxUpdate($(this));
						});
					});
				}
			} else {
				$form.find('input[type="text"],select').val('');
				$form.find('input[type="checkbox"]').each(function() {
					$(this).attr('checked',false);
					checkboxUpdate($(this));
				});
				$form.slideDown();
				$('#vendors-vendor-address-form-canceledit').hide();
			}
		});

		$('#vendors-vendor-address-form-canceledit').click(function (e) {
			e.preventDefault();
			$form = $('#vendors-vendor-address-form');
			$form.slideUp(function() {
				$form.attr('rel','');
				$form.find('input[type="text"],select').val('');
				$form.find('input[type="checkbox"]').each(function() {
					$(this).attr('checked',false);
					checkboxUpdate($(this));
				});
			});
		});

		$('#vendors-vendor-addresses-container .vendor-address a.edit').live('click',function (e) {
			e.preventDefault();
			$address = $(this).closest('.vendor-address');
			$form = $('#vendors-vendor-address-form');
			$form.slideUp(function() {
				$form.attr('rel','');
				$form.find('input[type="text"],select').val('');
				$form.find('input[type="checkbox"]').each(function() {
					$(this).attr('checked',false);
					checkboxUpdate($(this));
				});
				// Loop values and insert into form.
				$form.attr('rel',$address.attr('rel'));
				$form.find('input[type="text"],select').each(function() {
					$(this).val($address.find('input.'+$(this).attr('name')).val());
				});
				$form.find('input[type="checkbox"]').each(function() {
					if( $address.find('input.'+$(this).attr('name')).val() &&
						$address.find('input.'+$(this).attr('name')).val().length &&
						$address.find('input.'+$(this).attr('name')).val() == 1 ) {
						$(this).attr('checked',true);
						checkboxUpdate($(this));
					}
				});
				$form.slideDown();
				$('#vendors-vendor-address-form-canceledit').show();
			});
		});

		$('#vendors-vendor-address-form-save').click(function() {
			showPleaseWait();
			$form = $('#vendors-vendor-address-form');
			if( $form.attr('rel') &&
				$form.attr('rel').length > 0 ) {
				// Existing Address
				$.post(
					'/vendors/json/vendoraddressupdate',
					$form.find('input,select').serialize()+'&address_id='+$form.attr('rel'),
					function(data) {
						if( ! data.success ) {
							hidePleaseWait();
							showError(data.error);
						} else {
							if( data.data.address.default_shipping ) {
								$('#vendors-vendor-addresses-container .vendor-address div.default-shipping').hide();
							}
							if( data.data.address.default_billing ) {
								$('#vendors-vendor-addresses-container .vendor-address div.default-billing').hide();
							}
							$oldAddress = $('#vendors-vendor-addresses-container .vendor-address[rel="'+$form.attr('rel')+'"]');
							$newAddress = $(data.data.address.html);
							$newAddress.addClass('hidden');
							$oldAddress.after($newAddress);
							hidePleaseWait();
							$form.slideUp(function() {
								$form.attr('rel','');
								$form.find('input[type="text"],select').val('');
								$form.find('input[type="checkbox"]').each(function() {
									$(this).attr('checked',false);
									checkboxUpdate($(this));
								});
								$oldAddress.fadeOut(function() {
									$newAddress.fadeIn();
								});
							});
						}
					},
					'json'
				);
			} else {
				// New Address
				$.post(
					'/vendors/json/vendoraddresscreate',
					$form.find('input,select').serialize(),
					function(data){
						if( ! data.success ) {
							hidePleaseWait();
							showError(data.error);
						} else {
							if( data.data.address.default_shipping ) {
								$('#vendors-vendor-addresses-container .vendor-address div.default-shipping').hide();
							}
							if( data.data.address.default_billing ) {
								$('#vendors-vendor-addresses-container .vendor-address div.default-billing').hide();
							}
							$newAddress = $(data.data.address.html);
							$newAddress.addClass('hidden');
							if( $('#vendors-vendor-addresses-container div.clear:last').length > 0 ) {
								$('#vendors-vendor-addresses-container div.clear:last').before($newAddress);
							} else {
								$('#vendors-vendor-addresses-container').append($newAddress);
							}
							hidePleaseWait();
							$form.slideUp(function() {
								$form.attr('rel','');
								$form.find('input[type="text"],select').val('');
								$form.find('input[type="checkbox"]').each(function() {
									$(this).attr('checked',false);
									checkboxUpdate($(this));
								});
								$newAddress.fadeIn();
							});
						}
					},
					'json'
				);
			}
		});
		

		/**
		 * Vendors/Payments
		 * Also used on vendors/vendor
		 */
		
		$('.vendor-payment a.print').live('click',function(e) {
			e.preventDefault();
			printVendorPayment($(this).closest('li.vendor-payment').attr('rel'));
		});

		/*
		$('.vendor-payment a.view').live('click',function(e) {
			e.preventDefault();
			loadVendorPayment($(this).closest('.vendor-payment').attr('rel'));
			return false;
		});
		*/
		$('#vendors-payments-payments .vendor-payment .view').live('click',function(e) {
			// If we're on the invoices page we AJAX - otherwise... return true.
			if( $('#vendors-payments-create').length == 0 ) {
				return true;
			}
			e.preventDefault();
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadVendorPayment($(this).closest('.vendor-payment').attr('rel'));
			return false;
		});


		$('#vendors-vendor-payments-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-payments-payments li.vendor-payment:not(:first-child)').remove();
	 			$('#vendors-payments-endpayments').hide();
	 			$('#vendors-payments-loadpayments').show();
	 			vendorPaymentSearch();
	 		}
		});

		// We bind the scroll search for more vendor payments only to the vendor page.
		// Otherwise we're interested in loading only 5 payments at a time.
		if( $('#vendors-vendor-payments-search').length == 0 &&
			$('#vendors-payments-payments').length > 0 &&
			$('#vendors-payments-create').length == 0 ) {
			$(window).scroll(function () { 
				if( ( $(window).height() + $(window).scrollTop() ) >= $('#vendors-payments-payments').height() ) {
					if( $('#vendors-payments-loadpayments').is(':visible') ||
						$('#vendors-payments-endpayments').is(':visible') ||
						$('#vendors-payments-payments li.vendor-payment:last span').length == 0 ) {
						// Do nothing - we're already loading...
					} else {
						loadMoreVendorPayments();
					}
				}
			});
		}

		$('#vendors-payments-create input,#vendors-payments-create select').live('change',function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('#vendors-payments-create select[name="writeoff_account_id"]').change(function() {
			createVendorPaymentUpdateTotals();
		});

		$('#vendors-payments-create input[name="vendor_id"]').select2({
			minimumInputLength: 1,
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/vendors/json/vendorsformsearch",
				type: "POST",
				dataType: 'json',
				data: function (term) {
					return {
						last_vendor_id: '',
						search_terms: term, // search term
						count: 1000,
					};
				},
				results: function (data) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					var results = new Array();
					for( index in data.data.vendors ) {
						results[index] = {
							text: data.data.vendors[index].display_name,
							id: 
								data.data.vendors[index].id
								+'#'+
								( typeof data.data.vendors[index].default_remit_address_id != "undefined" &&
									data.data.vendors[index].default_remit_address_id != null &&
									data.data.vendors[index].default_remit_address_id.length ? data.data.vendors[index].default_remit_address_id : '' )
								+'#'+
								( typeof data.data.vendors[index].default_account != "undefined" && 
									data.data.vendors[index].default_account != null && 
									typeof data.data.vendors[index].default_account.id != "undefined" &&
									data.data.vendors[index].default_account.id != null &&
									data.data.vendors[index].default_account.id.length ? data.data.vendors[index].default_account.id : '' )
								+'#'+
								( typeof data.data.vendors[index].default_account != "undefined" && 
									data.data.vendors[index].default_account != null && 
									typeof data.data.vendors[index].default_account.id != "undefined" ? data.data.vendors[index].default_account.terms : '' )
						};
					}
					return {results: results};
				}
			}
		});

		$('#vendors-payments-create input[name="vendor_id"]').live('change',function() {
			$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first)').remove();
			if( ! $(this).val() || 
				! $(this).val().length ) {
				return true;
			}
			createVendorPaymentFetchAddresses();
			createVendorPaymentSearchPurchases();
		});

		$('#vendors-payments-address-dialog select[name="address_id"]').change( function () {
			if( $(this).val() &&
				$(this).val() == "new" ) {
				$('#vendors-payments-address-dialog input[type="text"]').each( function () {
					$(this).val('').attr('readonly',false);
				});
				$('#vendors-payments-address-dialog select[name="country"]').attr('disabled',false);
				$('#vendors-payments-address-dialog select[name="country"]').closest('div.select').removeClass('disabled');
			} else if ( $(this).val() &&
						$(this).val().length ) {
				var address_id = $(this).val();
				$('#vendors-payments-address-dialog input[type="text"]').each( function () {
					$(this).val( createVendorPaymentAddresses[address_id][$(this).attr('name')] ).attr('readonly','readonly');
				});
				$('#vendors-payments-address-dialog select[name="country"]').val( createVendorPaymentAddresses[address_id]['country'] );
				$('#vendors-payments-address-dialog select[name="country"]').attr('disabled','disabled');
				$('#vendors-payments-address-dialog select[name="country"]').closest('div.select').addClass('disabled');
			}
		});

		$('#vendors-payments-create-save').click( function (e) {
			e.preventDefault();
			showPleaseWait();

			// Check Remit Address
			var blank = true;
			var remit_address_id = false;
			var remit_address_mismatch = false;
			$('#vendors-payments-create-purchases li.vendor-paymentpo.selected').each(function () {
				blank = false;
				if( ! remit_address_id &&
					$(this).find('input.remit_address_id').val() ) {
					remit_address_id = $(this).find('input.remit_address_id').val();
				} else if ( remit_address_id &&
							$(this).find('input.remit_address_id').val() &&
							$(this).find('input.remit_address_id').val() != remit_address_id ) {
					remit_address_mismatch = true;
				}
			});

			if( ! blank &&
				! remit_address_id ) {
				// SHOW FOR "-none"
				hidePleaseWait();
				$('#vendors-payments-address-dialog .address-mismatch').hide();
				$('#vendors-payments-address-dialog .address-none').show();
				$('#vendors-payments-address-dialog').dialog("destroy");
				$('#vendors-payments-address-dialog').modaldialog({
					width: 500,
					dialogClass: 'generated-modal-dialog-success',
					buttons: [
						{
							text: "Save Without Address",
							click: function () {
								$(this).dialog("close");
								$('#vendors-payments-create-purchases li.vendor-paymentpo.selected').each(function () {
									$(this).find('input.remit_address_id').val('skip');
								});
								$('#vendors-payments-create-save').click();
							}
						},
						{
							text: "Cancel",
							click: function () {
								$(this).dialog("close");
							}
						},
						{
							text: "Save and Update Addresses",
							click: function () {
								if( ! $(this).find('select[name="address_id"]').val() ||
									! $(this).find('select[name="address_id"]').val().length ) {
									alert("Please choose an address or create a new one.");
								} else if ( $(this).find('select[name="address_id"]').val() == "new" ) {
									showPleaseWait();
									$.post(
										'/vendors/json/vendoraddresscreate',
										$(this).find('input,select').serialize(),
										function (data) {
											hidePleaseWait();
											if( ! data.success ) {
												showError(data.error);
											} else {
												$select = $('#vendors-payments-address-dialog select[name="address_id"]');
												$option = $('<option value="'+
													data.data.address.id+'">'+
													data.data.address.address1+' '+
													data.data.address.address2+', '+
													data.data.address.city+' '+
													data.data.address.state+', '+
													data.data.address.zip+' '+
													data.data.address.country+
													'</option>');
												createVendorPaymentAddresses[data.data.address.id] = {
													address1: data.data.address.address1,
													address2: data.data.address.address2,
													city: data.data.address.city,
													state: data.data.address.state,
													zip: data.data.address.zip,
													country: data.data.address.country
												}
												$select.append($option);
												$select.val(data.data.address.id);

												$('#vendors-payments-address-dialog').dialog("close");
												$('#vendors-payments-create-purchases li.vendor-paymentpo.selected').each(function () {
													$(this).find('input.remit_address_id').val(data.data.address.id);
												});
												$select.change();
												$('#vendors-payments-create-save').click();
											}
										}
									);
								} else {
									$select = $('#vendors-payments-address-dialog select[name="address_id"]');
									$('#vendors-payments-address-dialog').dialog("close");
									$('#vendors-payments-create-purchases li.vendor-paymentpo.selected').each(function () {
										$(this).find('input.remit_address_id').val($select.val());
									});
									$select.change();
									$('#vendors-payments-create-save').click();
								}
							}
						}
					]
				});
				return;
			} else if ( ! blank &&
						remit_address_mismatch ) {
				// SHOW FOR "-mismatch"
				hidePleaseWait();
				$('#vendors-payments-address-dialog .address-none').hide();
				$('#vendors-payments-address-dialog .address-mismatch').show();
				$('#vendors-payments-address-dialog').dialog("destroy");
				$('#vendors-payments-address-dialog').modaldialog({
					width: 500,
					dialogClass: 'generated-modal-dialog-success',
					buttons: [
						{
							text: "Cancel",
							click: function () {
								$(this).dialog("close");
							}
						},
						{
							text: "Save and Update Addresses",
							click: function () {
								if( ! $(this).find('select[name="address_id"]').val() ||
									! $(this).find('select[name="address_id"]').val().length ) {
									alert("Please choose an address or create a new one.");
								} else if ( $(this).find('select[name="address_id"]').val() == "new" ) {
									showPleaseWait();
									$.post(
										'/vendors/json/vendoraddresscreate',
										$(this).find('input,select').serialize(),
										function (data) {
											hidePleaseWait();
											if( ! data.success ) {
												showError(data.error);
											} else {
												$select = $('#vendors-payments-address-dialog select[name="address_id"]');
												$option = $('<option value="'+
													data.data.address.id+'">'+
													data.data.address.address1+' '+
													data.data.address.address2+', '+
													data.data.address.city+' '+
													data.data.address.state+', '+
													data.data.address.zip+' '+
													data.data.address.country+
													'</option>');
												createVendorPaymentAddresses[data.data.address.id] = {
													address1: data.data.address.address1,
													address2: data.data.address.address2,
													city: data.data.address.city,
													state: data.data.address.state,
													zip: data.data.address.zip,
													country: data.data.address.country
												}
												$select.append($option);
												$select.val(data.data.address.id);

												$('#vendors-payments-address-dialog').dialog("close");
												$('#vendors-payments-create-purchases li.vendor-paymentpo.selected').each(function () {
													$(this).find('input.remit_address_id').val(data.data.address.id);
												});
												$select.change();
												$('#vendors-payments-create-save').click();
											}
										}
									);
								} else {
									$select = $('#vendors-payments-address-dialog select[name="address_id"]');
									$('#vendors-payments-address-dialog').dialog("close");
									$('#vendors-payments-create-purchases li.vendor-paymentpo.selected').each(function () {
										$(this).find('input.remit_address_id').val($select.val());
									});
									$select.change();
									$('#vendors-payments-create-save').click();
								}
							}
						}
					]
				});
				return;
			}

			$('#vendors-payments-create input[name="remit_address_id"]').val($('#vendors-payments-create-purchases li.vendor-paymentpo.selected:first input.remit_address_id').val());

			if( $('#vendors-payments-create').attr('rel') &&
				$('#vendors-payments-create').attr('rel').length ) {
				// Update
				$.post(
					'/vendors/json/paymentupdate',
					$('#vendors-payments-create input, #vendors-payments-create select').serialize()+'&payment_id='+$('#vendors-payments-create').attr('rel'),
					function(data) {
						// Don't let the value sit in there in case of error.
						$('#vendors-payments-create input[name="replace_transaction_id"]').val('');
						hidePleaseWait();
						if( data.success != 1 ) {
							if( data.data.duplicate_transaction ) {
								$('#vendors-payments-duplicate-dialog').html('<p class="text-medium">'+data.error+'</p>').modaldialog({
									width: 700,
									dialogClass: 'generated-modal-dialog-success',
									buttons: {
										'Create New Payment': function() {
											$('#vendors-payments-create input[name="replace_transaction_id"]').val('new');
											$(this).dialog("close");
											$('#vendors-payments-create-save').click();
										},
										'Convert Transaction to Payment': function() {
											$('#vendors-payments-create input[name="replace_transaction_id"]').val(data.data.duplicate_transaction.id);
											$(this).dialog("close");
											$('#vendors-payments-create-save').click();
										}
									}
								}); 
							} else {
								showError(data.error);
							}
						} else {
							$oldPayment = $('#vendors-payments-payments .vendor-payment[rel="'+$('#vendors-payments-create').attr('rel')+'"]');
							$newPayment = $(data.data.payment.html);
							$newPayment.addClass('hidden');
							$oldPayment.after($newPayment);
							$oldPayment.slideUp(function() {
								$(this).remove();
								$newPayment.slideDown(function() {
									rowElementsColorVisible($('#vendors-payments-payments'));
								});
							});
							$('#vendors-payments-create').slideUp(function() {
								createVendorPaymentClearForm();
								$('#vendors-payments-create').slideDown();
							});
							$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
						}
					},
					'json'
				);
			} else {
				// Create New
				$.post(
					'/vendors/json/paymentcreate',
					$('#vendors-payments-create input, #vendors-payments-create select').serialize(),
					function(data) {
						// Don't let the value sit in there in case of error.
						$('#vendors-payments-create input[name="replace_transaction_id"]').val('');
						hidePleaseWait();
						if( data.success != 1 ) {
							if( data.data.duplicate_transaction ) {
								$('#vendors-payments-duplicate-dialog').html('<p class="text-medium">'+data.error+'</p>').modaldialog({
									width: 700,
									dialogClass: 'generated-modal-dialog-success',
									buttons: {
										'Create New Payment': function() {
											$('#vendors-payments-create input[name="replace_transaction_id"]').val('new');
											$(this).dialog("close");
											$('#vendors-payments-create-save').click();
										},
										'Convert Transaction to Payment': function() {
											$('#vendors-payments-create input[name="replace_transaction_id"]').val(data.data.duplicate_transaction.id);
											$(this).dialog("close");
											$('#vendors-payments-create-save').click();
										}
									}
								}); 
							} else {
								showError(data.error);
							}
						} else {
							$newPayment = $(data.data.payment.html);
							$newPayment.addClass('hidden');
							if( $('#vendors-payments-payments .vendor-payment:not(:first-child)').length < 5 &&
								$('#vendors-payments-payments .vendor-payment:last-child').find('.vendor-payment-date').length > 0 ) {
								$('#vendors-payments-payments .vendor-payment:first-child').after($newPayment)
								$newPayment.slideDown(function() {
									rowElementsColorVisible($('#vendors-payments-payments'));
								});
							} else {
								$lastPayment = $('#vendors-payments-payments .vendor-payment:last-child');
								$('#vendors-payments-payments .vendor-payment:first-child').after($newPayment)
								$lastPayment.slideUp(function() {
									$lastPayment.remove();
								});
								$newPayment.slideDown(function() {
									rowElementsColorVisible($('#vendors-payments-payments'));
								});
							}
							$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
							createVendorPaymentClearForm();
						}
					},
					'json'
				);
			}
		});

		var delete_vendor_payment_id = '';
		function deleteVendorPayment() {
			showPleaseWait();
			$.post(
				'/vendors/json/paymentdelete',
				{
					payment_id: delete_vendor_payment_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('#vendors-payments-payments .vendor-payment[rel="'+$('#vendors-payments-create').attr('rel')+'"]').slideUp(function() {
							$(this).remove();
							rowElementsColorVisible($('#vendors-payments-payments'));
						});
						$('#vendors-payments-create').slideUp(function() {
							createVendorPaymentClearForm();
							$('#vendors-payments-create').slideDown();
						});
						$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
					}
				},
				'json'
			);
		}
		
		$('#vendors-payments-create-delete').click(function(e) {
			e.preventDefault();
			delete_vendor_payment_id = $('#vendors-payments-create').attr('rel');
			showConfirm("Are you certain you want to delete this payment?","Yes, Delete.","No.",deleteVendorPayment);
		});

		$('#vendors-payments-create-edit').click(function(e) {
			e.preventDefault();

			$('#vendors-payments-create-actions-showincluded').hide();
			$('#vendors-payments-create-actions-showall').show();

			$('.vendors-payments-create-actions-delete').hide();
			$('.vendors-payments-create-actions-deleteplaceholder').show();
			$('.vendors-payments-create-actions-edit').hide();
			$('.vendors-payments-create-actions-save').show();

			$('#vendors-payments-create-purchases .vendor-paymentpo:not(:first)').each(function() {
				$line = $(this);
				$line.find('.vendor-paymentpo-po').html($line.find('.vendor-paymentpo-po a').text());
				$line.find('.vendor-paymentpo-numeric.balance').text(monetaryPrint(parseFloat($line.find('.vendor-paymentpo-numeric.balance').attr('rel'))));
				$line.find('.vendor-paymentpo-add').show();
				$line.find('.vendor-paymentpo-balancewriteoff').show();
				$writeoffBalance = $line.find('.vendor-paymentpo-balancewriteoff input[type="checkbox"]');
				if( $writeoffBalance.val() != 0 ) {
					$writeoffBalance.prop('disabled',false);
					$writeoffBalance.prop('checked',true);
				}
				$line.find('.vendor-paymentpo-numeric.amount input[type="text"]').attr('readonly',false);
				$line.find('.vendor-paymentpo-so').find('input[type="text"]').attr('readonly',false);
				$line.find('.vendor-paymentpo-invoice').find('input[type="text"]').attr('readonly',false);
				$line.find('.vendor-paymentpo-date_billed').find('input[type="text"]').attr('readonly',false);
				$line.find('.vendor-paymentpo-add input[type="checkbox"]').attr('checked','checked');
				checkboxUpdate($line.find('.vendor-paymentpo-add input[type="checkbox"]'));
				checkboxUpdate($writeoffBalance);
			});
			createVendorPaymentEnableFields();
			createVendorPaymentUpdateTotals();
		});
		
		$('#vendors-payments-create-cancel').click(function (e) {
			e.preventDefault();
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#vendors-payments-create').slideUp(function() {
						createVendorPaymentClearForm();
						$('#vendors-payments-create').slideDown();
					});
				}
			} else {
				$('#vendors-payments-create').slideUp(function() {
					createVendorPaymentClearForm();
					$('#vendors-payments-create').slideDown();
				});
			}
		});

		$('#vendors-payments-create-purchases .vendor-paymentpo:not(:first-child).selected .vendor-paymentpo-add div.checkbox').live('click',function() {
			$line = $(this).closest('.vendor-paymentpo');
			// $line.find('.vendor-paymentpo-add input[type="checkbox"]').attr('checked','checked');
			// checkboxUpdate($line.find('.vendor-paymentpo-add input[type="checkbox"]'));
			createVendorPaymentRemovePurchase($line);
		});

		$('#vendors-payments-create-purchases .vendor-paymentpo:not(:first-child).selected .vendor-paymentpo-balancewriteoff div.checkbox').live('click',function() {
			$checkbox = $(this).find('input[type="checkbox"]');
			if( ! $checkbox.is(":disabled") ) {
				if( $checkbox.is(":checked") ) {
					$checkbox.prop('checked',false);
					checkboxUpdate($checkbox);
					createVendorPaymentUpdateTotals();
				} else {
					$checkbox.prop('checked',true);
					checkboxUpdate($checkbox);
					createVendorPaymentUpdateTotals();
				}
			}
		});
		
		$('#vendors-payments-create input[name="amount"]').change(function() {
			createVendorPaymentUpdateTotals();
		});

		$('#vendors-payments-create-purchases .vendor-paymentpo.selected .vendor-paymentpo-numeric.amount input[type="text"]').live('change',function() {
			createVendorPaymentUpdateTotals();
		});

		$('#vendors-payments-create-actions-showall').click(function(e) {
			e.preventDefault();
			$(this).hide();
			$('#vendors-payments-create-actions-showincluded').show();
			if( $('#vendors-payments-create-purchases li.vendor-paymentpo:last span').length == 0 ) {
				return;
			}
			if( $('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)').length == 0 ) {
				createVendorPaymentSearchPurchases();
			} else {
				$('#vendors-payments-create-purchases li.vendor-paymentpo').slideDown();
			}
		});

		$('#vendors-payments-create-actions-showincluded').click(function(e) {
			e.preventDefault();
			$(this).hide();
			$('#vendors-payments-create-actions-showall').show();
			if( $('#vendors-payments-create-purchases li.vendor-paymentpo:last span').length == 0 ) {
				return;
			}
			$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)').slideUp();
		});

		if( $('#vendors-payments-create').length > 0 ) {
			createVendorPaymentUpdateTotals();
		}

		$('#vendors-payments-create-actions-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$searchTerm = $(this).val();
	 			$foundLine = false;
	 			$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first-child,.selected)').each(function() {
	 				$purchaseNumber = $(this).find('.vendor-paymentpo-po').text();
	 				if( $purchaseNumber == $searchTerm || 
	 					$purchaseNumber.substring(1) == $searchTerm ) {
	 					$foundLine = $(this);
	 				}
	 			});
	 			if( ! $foundLine ) {
		 			$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)').remove();
		 			createVendorPaymentSearchPurchases();
		 		} else {
		 			$foundLine.find('.vendor-paymentpo-add input[type="checkbox"]').attr('checked','checked');
					checkboxUpdate($foundLine.find('.vendor-paymentpo-add input[type="checkbox"]'));
					createVendorPaymentAddPurchase($foundLine);
					$('#vendors-payments-create-actions-search').val('').focus().blur().focus();
		 		}
	 		}
		});

		$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)').live('click',function() {
			$line = $(this);
			$line.find('.vendor-paymentpo-add input[type="checkbox"]').attr('checked','checked');
			checkboxUpdate($line.find('.vendor-paymentpo-add input[type="checkbox"]'));
			createVendorPaymentAddPurchase($line);
		});

		$('#vendors-payments-payments-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-payments-payments-search').attr('rel','0');
	 			vendorPaymentsSearch();
	 		}
		});

		$('#vendors-payments-payments-paging a').live('click', function (e) {
			e.preventDefault();
			$('#vendors-payments-payments-search').attr('rel',$(this).attr('rel'));
			vendorPaymentsSearch();
		});

		/**
		 * Tax Payments
		 */
		
		
		
		$('#vendors-taxpayments-payments-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-taxpayments-payments .vendor-taxpayment:not(:first)').remove();
	 			taxPaymentsSearch();
	 		}
		});

		$('#vendors-taxpayments-create input[name="tax_id"]').select2({
			minimumInputLength: 1,
			ajax: { 
				url: "/vendors/json/taxsearch",
				type: "POST",
				dataType: 'json',
				data: function (term) {
					return {
						search_terms: term
					};
				},
				results: function (data) { 
					var results = new Array();
					for( index in data.data.taxes ) {
						results[index] = {
							id: data.data.taxes[index].id,
							text: data.data.taxes[index].name
						}
					}
					return {results: results};
				}
			}
		});

		$('#vendors-taxpayments-create input[name="date_start"]').datepicker({
			dateFormat: "yy-mm-dd"
		});
		$('#vendors-taxpayments-create input[name="date_end"]').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('#vendors-taxpayments-payments .vendor-taxpayment a.view').live('click',function(e) {
			e.preventDefault();
			loadTaxPayment($(this).closest('.vendor-taxpayment').attr('rel'));
		});

		$('#vendors-taxpayments-create-showdetails').click(function (e) {
			e.preventDefault();
			
			if( $('#vendors-taxpayments-create').attr('rel').length ) {
				popupWindow = popupWindowLoad('/print/taxpayment/'+$('#vendors-taxpayments-create').attr('rel'));
				$(popupWindow.document).ready( function () {
					setTimeout( function () { popupWindow.print(); } , 1000 );
				});
			} else {
				if( $('#vendors-taxpayments-create input[name="tax_expected"]').val().length && 
					parseFloat($('#vendors-taxpayments-create input[name="tax_expected"]').val()) != 0.00 ) {
					var tax_id = $('#vendors-taxpayments-create input[name="tax_id"]').val();
					var date_start = $('#vendors-taxpayments-create input[name="date_start"]').val();
					var date_end = $('#vendors-taxpayments-create input[name="date_end"]').val();

					popupWindow = popupWindowLoad('/print/taxprep/'+tax_id+'/'+date_start+'_'+date_end+'/');
					$(popupWindow.document).ready( function () {
						setTimeout( function () { popupWindow.print(); } , 1000 );
					});
				}
			}
			function printCustomerSale(id) {
				
			}
		});
		
		$('#vendors-taxpayments-create input[name="tax_id"], #vendors-taxpayments-create input[name="date_start"], #vendors-taxpayments-create input[name="date_end"]').change(function() {
			$tax_id = $('#vendors-taxpayments-create input[name="tax_id"]');
			$date_start = $('#vendors-taxpayments-create input[name="date_start"]');
			$date_end = $('#vendors-taxpayments-create input[name="date_end"]');
			if( ! $tax_id.val() ||
				! $tax_id.val().length || 
				! $date_start.val() ||
				! $date_start.val().length ||
				! $date_end.val() ||
				! $date_end.val().length ) {
				// createTaxPaymentClearForm();
				return true;
			}
			showPleaseWait();
			$.post(
				'/vendors/json/taxpaymentprep',
				{
					tax_id: $tax_id.val(),
					date_start: $date_start.val(),
					date_end: $date_end.val()
				},
				function(data) {
					hidePleaseWait();
					if( ! data.success ) {
						showError(data.error);
					} else {
						// Fill in our form.
						$form = $('#vendors-taxpayments-create');
						$form.find('input[name="total_sales"]').attr('rel',data.data.taxes.due.invoiced.form_line_amount);
						$form.find('input[name="taxable_sales"]').attr('rel',data.data.taxes.due.invoiced.form_line_taxable_amount);
						$form.find('input[name="tax_collected"]').attr('rel',data.data.taxes.due.invoiced.amount);
						$form.find('input[name="total_returns"]').attr('rel',data.data.taxes.due.refunded.form_line_amount);
						$form.find('input[name="taxable_returns"]').attr('rel',data.data.taxes.due.refunded.form_line_taxable_amount);
						$form.find('input[name="tax_returned"]').attr('rel',data.data.taxes.due.refunded.amount);
						$form.find('input[name="net_sales"]').attr('rel',data.data.taxes.due.net.form_line_amount);
						$form.find('input[name="net_taxable"]').attr('rel',data.data.taxes.due.net.form_line_taxable_amount);
						$form.find('input[name="tax_expected"]').attr('rel',data.data.taxes.due.net.amount);
						if( data.data.taxes.due.net.amount < 0.00 ) {
							$form.find('input[name="tax_expected"]').attr('rel','0.00');
						}
						// $form.find('input[name="tax_paid"]').attr('rel',data.data.taxes.paid.net.amount);

						createTaxPaymentUpdateForm();
					}
				},
				'json'
			);
		});

		// Down here for listeners
		if( $('#vendors-taxpayments-create-requested_payment_id').length > 0 &&
			$('#vendors-taxpayments-create-requested_payment_id').val().length > 0 ) {
			loadTaxPayment($('#vendors-taxpayments-create-requested_payment_id').val());
		}

		if( $('#vendors-taxpayments-create-requested_tax_id').length > 0 &&
			$('#vendors-taxpayments-create-requested_tax_id').val().length > 0 &&
			$('#vendors-taxpayments-create-requested_tax_name').length > 0 &&
			$('#vendors-taxpayments-create-requested_tax_name').val().length > 0 ) {
			$('#vendors-taxpayments-create input[name="tax_id"]').select2("data", {
				id: $('#vendors-taxpayments-create-requested_tax_id').val(), 
				text: $('#vendors-taxpayments-create-requested_tax_name').val()
			});
		}

		$('#vendors-taxpayments-create input[name="writeoff_amount"], #vendors-taxpayments-create input[name="amount"]').change(function() {
			createTaxPaymentUpdateForm();
		});

		$('#vendors-taxpayments-create input,#vendors-taxpayments-create select').live('change',function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('.vendors-taxpayments-create-cancel').click(function (e) {
			e.preventDefault();
			if( $('#vendors-taxpayments-create input[name="date"]').attr('readonly') != "readonly" &&
				GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#vendors-taxpayments-create').slideUp(function() {
						createTaxPaymentClearForm(true);
						createTaxPaymentEnableFields();
						$('#vendors-taxpayments-create').slideDown();
					});
				}
			} else {
				$('#vendors-taxpayments-create').slideUp(function() {
					createTaxPaymentClearForm(true);
					createTaxPaymentEnableFields();
					$('#vendors-taxpayments-create').slideDown();
				});
			}
		});

		var delete_tax_payment_id = '';
		function deleteTaxPayment() {
			showPleaseWait();
			$.post(
				'/vendors/json/taxpaymentcancel',
				{
					payment_id: delete_tax_payment_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('#vendors-taxpayments-payments .vendor-taxpayment[rel="'+delete_tax_payment_id+'"]').slideUp(function() {
							$(this).remove();
							rowElementsColorVisible($('#vendors-taxpayments-payments'));
						});
						$('#vendors-taxpayments-create').slideUp(function() {
							createTaxPaymentClearForm(true);
							createTaxPaymentEnableFields();
							$('#vendors-taxpayments-create').slideDown();
						});
						$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
					}
				},
				'json'
			);
		}
		
		$('.vendors-taxpayments-create-delete').click(function(e) {
			e.preventDefault();
			delete_tax_payment_id = $('#vendors-taxpayments-create').attr('rel');
			showConfirm("Are you certain you want to delete this payment?","Yes, Delete.","No.",deleteTaxPayment);
		});

		$('.vendors-taxpayments-create-edit').click(function(e) {
			e.preventDefault();
			createTaxPaymentEnableFields();
			$('.vendors-taxpayments-create-canceledit').hide();
			$('.vendors-taxpayments-create-cancelsave').show();
		});

		$('.vendors-taxpayments-create-save').click(function(e) {
			e.preventDefault();
			showPleaseWait();
			$saveElement = $('.vendors-taxpayments-create-save');
			if( $('#vendors-taxpayments-create').attr('rel') &&
				$('#vendors-taxpayments-create').attr('rel').length ) {
				// Update
				$.post(
					'/vendors/json/taxpaymentupdate',
					$('#vendors-taxpayments-create input, #vendors-taxpayments-create select').serialize()+'&payment_id='+$('#vendors-taxpayments-create').attr('rel'),
					function(data) {
						// Don't let the value sit in there in case of error.
						$('#vendors-taxpayments-create input[name="replace_transaction_id"]').val('');
						hidePleaseWait();
						if( data.success != 1 ) {
							if( data.data.duplicate_transaction ) {
								$('#vendors-taxpayments-duplicate-dialog').html('<p class="text-medium">'+data.error+'</p>').modaldialog({
									width: 700,
									dialogClass: 'generated-modal-dialog-success',
									buttons: {
										'Create New Payment': function() {
											$('#vendors-taxpayments-create input[name="replace_transaction_id"]').val('new');
											$(this).dialog("close");
											$saveElement.click();
										},
										'Convert Transaction to Payment': function() {
											$('#vendors-taxpayments-create input[name="replace_transaction_id"]').val(data.data.duplicate_transaction.id);
											$(this).dialog("close");
											$saveElement.click();
										}
									}
								});
							} else {
								showError(data.error);
							}
						} else {
							$oldPayment = $('#vendors-taxpayments-payments .vendor-taxpayment[rel="'+$('#vendors-taxpayments-create').attr('rel')+'"]');
							$newPayment = $(data.data.payment.html);
							$newPayment.addClass('hidden');
							$oldPayment.after($newPayment);
							$oldPayment.slideUp(function() {
								$(this).remove();
								$newPayment.slideDown();
							});
							$('#vendors-taxpayments-create').slideUp(function() {
								createTaxPaymentClearForm(true);
								$('#vendors-taxpayments-create').slideDown();
							});
							$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
						}
					},
					'json'
				);
			} else {
				$.post(
					'/vendors/json/taxpaymentcreate',
					$('#vendors-taxpayments-create input, #vendors-taxpayments-create select').serialize(),
					function(data) {
						// Don't let the value sit in there in case of error.
						$('#vendors-taxpayments-create input[name="replace_transaction_id"]').val('');
						hidePleaseWait();
						if( data.success != 1 ) {
							if( data.data.duplicate_transaction ) {
								$('#vendors-taxpayments-duplicate-dialog').html('<p class="text-medium">'+data.error+'</p>').modaldialog({
									width: 700,
									dialogClass: 'generated-modal-dialog-success',
									buttons: {
										'Create New Payment': function() {
											$('#vendors-taxpayments-create input[name="replace_transaction_id"]').val('new');
											$(this).dialog("close");
											$saveElement.click();
										},
										'Convert Transaction to Payment': function() {
											$('#vendors-taxpayments-create input[name="replace_transaction_id"]').val(data.data.duplicate_transaction.id);
											$(this).dialog("close");
											$saveElement.click();
										}
									}
								});
							} else {
								showError(data.error);
							}
						} else {
							$newPayment = $(data.data.payment.html);
							$newPayment.addClass('hidden');
							if( $('#vendors-taxpayments-payments .vendor-taxpayment:not(:first-child)').length < 5 &&
								$('#vendors-taxpayments-payments .vendor-taxpayment:last-child').find('.vendor-taxpayment-date').length > 0 ) {
								$('#vendors-taxpayments-payments .vendor-taxpayment:first-child').after($newPayment)
								$newPayment.slideDown(function() {
									rowElementsColorVisible($('#vendors-taxpayments-payments'));
								});
							} else {
								$lastPayment = $('#vendors-taxpayments-payments .vendor-taxpayment:last-child');
								$('#vendors-taxpayments-payments .vendor-taxpayment:first-child').after($newPayment)
								$lastPayment.slideUp(function() {
									$lastPayment.remove();
									$newPayment.slideDown(function() {
										rowElementsColorVisible($('#vendors-taxpayments-payments'));
									});
								});
							}
							createTaxPaymentClearForm(true);
							$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
						}
					},
					'json'
				);
			}
		});


		/**
		 * VENDORS - INVOICES - Receiving & Aging Invoices
		 */
		if( $('#vendors-invoices-receive').length ) {
			rowElementsColorVisible($('#vendors-invoices-receive-purchases'));
		}
		
		$('#vendors-invoices-receive-search').live('keyup', function (e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first-child,.selected)').remove();
	 			showPleaseWait();
	 			receiveInvoicesSearchPurchases();
	 		}
		});

		$('#vendors-invoices-receive input, #vendors-invoices-receive select').live('change',function () {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('#vendors-invoices-receive .vendor-invoice-purchase-invoice_amount input').live('keydown', function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
			if( code == 9 ) {
				e.preventDefault();
				$('#vendors-invoices-receive-search').focus();
			}
		});

		$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first, .selected, .invoiced, .vendor-invoice-purchase-difference)').live('click',function() {
			$line = $(this);
			$line.find('.vendor-invoice-purchase-add input[type="checkbox"]').attr('checked','checked');
			checkboxUpdate($line.find('.vendor-invoice-purchase-add input[type="checkbox"]'));
			receiveInvoicesAddPurchase($line);
		});

		$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first-child).selected .vendor-invoice-purchase-add div.checkbox').live('click',function() {
			$line = $(this).closest('li.vendor-invoice-purchase');
			receiveInvoicesRemovePurchase($line);
		});

		$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase span.vendor-invoice-purchase-invoice_amount input[type="text"]').live('change', function() {
			$(this).val(convertCurrencyToNumber($(this).val()).toFixed(2));
			if( $(this).val() != $(this).attr('rel') ) {
				receiveInvoicesAddAdjustment($(this).closest('li.vendor-invoice-purchase'));
			} else {
				receiveInvoicesRemoveAdjustment($(this).closest('li.vendor-invoice-purchase'));
			}
		});

		$('#vendors-invoices-receive-cancel').click(function(e) {
			e.preventDefault();
			if( $(this).attr('disabled') &&
				$(this).attr('disabled').length ) {
				return false;
			}
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first)').slideUp(function() {
						$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first)').remove();
						receiveInvoicesUpdateTotals();
					});
				}
			} else {
				$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first)').slideUp(function() {
					$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first)').remove();
					receiveInvoicesUpdateTotals();
				});
			}
		});

		$('#vendors-invoices-receive-save').click(function(e) {
			e.preventDefault();
			if( $(this).attr('disabled') &&
				$(this).attr('disabled').length ) {
				return false;
			}
			if( $('#vendors-invoices-receive-count').val().length == 0 ) {
				showError("Please add at least one purchase.");
			} else {
				showPleaseWait();
				$.post(
					'/vendors/json/invoiceprocess',
					$('#vendors-invoices-receive input, #vendors-invoices-receive select').serialize(),
					function(data) {
						if( ! data.success ) {
							hidePleaseWait();
							showError(data.error);
						} else {
							hidePleaseWait();
							// TODO - Replace with recorded results?
							$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first,:last)').slideUp(function() {
								$(this).remove();
							});
							$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:last').slideUp(function() {
								$(this).remove();
								receiveInvoicesUpdateTotals();
								for( index in data.data.purchases ) {
									$newPurchaseFormLine = $(data.data.purchases[index].html);
									$newPurchaseFormLine.addClass('hidden');
									$('#vendors-invoices-receive-purchases .vendor-invoice-purchase:last').after($newPurchaseFormLine);
								}
								$('#vendors-invoices-receive-purchases .vendor-invoice-purchase').slideDown();
							});
							GLOBAL_EDIT_FORM_ACTIVE = false;
							showPageSuccess("Invoices have been recorded.");
						}
					},
					'json'
				);
			}
		});

		/**
		 * PRINT CHECKS
		 */
		
		$('#vendors-printchecks-checks-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#vendors-printchecks-checks-search').attr('rel','0');
	 			vendorChecksSearch();
	 		}
		});

		$('#vendors-printchecks-checks-paging a').live('click', function (e) {
			e.preventDefault();
			$('#vendors-printchecks-checks-search').attr('rel',$(this).attr('rel'));
			vendorChecksSearch();
		});
		
		$('#vendors-printchecks-newchecks li.vendor-check .vendor-check-actions div.checkbox.add').live('click',function() {
			$check = $(this).closest('.vendor-check');
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
				$check.removeClass('selected');
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$check.addClass('selected');
			}
			rowElementsColorVisible($('#vendors-printchecks-newchecks'));
		});

		$('#vendors-printchecks-newchecks-clear').click( function (e) {
			e.preventDefault();
			showPleaseWait();
			$.post(
				'/vendors/json/clearchecks',
				{},
				function (data) {
					if( ! data.success ) {
						hidePleaseWait();
						showError(data.error);
					} else {
						$('#vendors-printchecks-newchecks ul li:not(:first)').remove();
						rowElementsColorVisible($('#vendors-printchecks-newchecks'));
						rowElementsColorVisible($('#vendors-printchecks-checks'));
						hidePleaseWait();
					}

				}
			);
		});

		$('#vendors-printchecks-newchecks-print').click( function (e) {
			e.preventDefault();

			$form = $('#vendors-printchecks-newchecks');

			if( ! $form.find('input[name="check_number_start"]').val() ||
				$form.find('input[name="check_number_start"]').val().length  == 0 ) {
				showError("Please enter a starting check number before printing.");
				return;
			}

			showPleaseWait();

			$.post(
				'/vendors/json/printchecks',
				$form.find('input').serialize(),
				function (data) {
					if( ! data.success ) {
						hidePleaseWait();
						showError(data.error);
					} else {
						$form = $('#vendors-printchecks-newchecks-form');
						if( $.browser.msie ) {
							$form.prop("target","_BLANK");
						} else {
							var popupWindow = popupWindowLoad('');
							$(popupWindow.document).ready( function () {
								setTimeout( function () { popupWindow.print(); } , 1000 );
							});
							$form.prop("target",popupWindow.name);
						}
						
						$form.submit();
						$('#vendors-printchecks-newchecks ul li:not(:first)').each(function() {
							if( $(this).find('div.checkbox.add input[type="checkbox"]').is(':checked') ) {
								$(this).remove();
							}
						});
						for( i in data.data.checks ) {
							$newCheck = $(data.data.checks[i]);
							var code = $newCheck.find('a.reprint').attr('rel');
							if( $('#vendors-printchecks-checks ul li[rel="'+code+'"]').length > 0 ) {
								$('#vendors-printchecks-checks ul li[rel="'+code+'"]').remove();
							}
							$('#vendors-printchecks-checks ul li:first').after($newCheck);
							if( $('#vendors-printchecks-checks ul li:last span').length == 0 ) {
								$('#vendors-printchecks-checks ul li:last').remove();
							}
							if( $('#vendors-printchecks-checks ul li').length > 6 ) {
								$('#vendors-printchecks-checks ul li:last').remove();
							}
						}

						rowElementsColorVisible($('#vendors-printchecks-newchecks'));
						rowElementsColorVisible($('#vendors-printchecks-checks'));

						$form.find('input[name="check_number_start"]').val(data.data.next_check_number);

						hidePleaseWait();
						$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
					}
				},
				'json'
			);
		});

		$('#vendors-printchecks-checks li.vendor-check a.reprint').live('click', function (e) {
			e.preventDefault();

			showPleaseWait();
			$.post(
				'/vendors/json/checkadd',
				{
					key: $(this).attr('rel')
				},
				function (data) {
					hidePleaseWait();
					if( ! data.success ) {
						showError(data.error);
					} else {
						$newCheck = $(data.data.newcheck);
						$newCheck.addClass('hidden');
						$('#vendors-printchecks-newchecks ul li.vendor-check:first').after($newCheck);
						$newCheck.slideDown(function () {
							if( $('#vendors-printchecks-newchecks li.vendor-check:last span').length == 0 ) {
								$('#vendors-printchecks-newchecks li.vendor-check:last').remove();
							}
							rowElementsColorVisible($('#vendors-printchecks-newchecks'));
						});
						$('#vendor-print-check-queue-tab-link').text(data.print_check_queue.text);
					}
				},
				'json'
			);
		});

	});

	function vendorChecksSearch() {
		showPleaseWait();
		$('#vendors-printchecks-checks ul li:not(:first)').remove();
		$.post(
			'/vendors/json/checksearch',
			{
				search_terms: $('#vendors-printchecks-checks-search').val(),
				count: 5,
				page: $('#vendors-printchecks-checks-search').attr('rel')
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					for( index in data.data.transactions ) {
						$check = $(data.data.transactions[index].html);
						$check.addClass('hidden');
						$('#vendors-printchecks-checks ul li.vendor-check:last').after($check);
					}
					generateSearchPaging($('#vendors-printchecks-checks-paging'), data.data, 5);
					$('#vendors-printchecks-checks-paging').html('|&nbsp;&nbsp;'+$('#vendors-printchecks-checks-paging').html());
					$('#vendors-printchecks-checks ul li.vendor-check').slideDown(function() {
						rowElementsColorVisible($('#vendors-printchecks-checks'));
					});
				}
			},
			'json'
		);
	}

	function receiveInvoicesAddAdjustment($line) {
		$id = $line.attr('rel');
		if( $('#vendors-invoices-receive-purchases li.vendor-invoice-purchase.vendor-invoice-purchase-difference[rel="'+$id+'"]').length == 0 ) {
			$adjustLine = $($('#vendor-invoice-purchase-difference-template').html());
			$adjustLine.attr('rel',$id);
			$adjustLine.find('input').attr('name',$adjustLine.find('input').attr('name')+$id);
			$adjustLine.find('select').attr('name',$adjustLine.find('select').attr('name')+$id);
			$line.after($adjustLine);
			$adjustLine.slideDown(function() {
				$adjustLine.find('.vendor-invoice-purchase-difference-description input').focus();
			});
		}
	}

	function receiveInvoicesRemoveAdjustment($line) {
		$id = $line.attr('rel');
		if( $('#vendors-invoices-receive-purchases li.vendor-invoice-purchase.vendor-invoice-purchase-difference[rel="'+$id+'"]').length != 0 ) {
			$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase.vendor-invoice-purchase-difference[rel="'+$id+'"]').slideUp(function() {
				$(this).remove();
			});
		}
	}

	function receiveInvoicesUpdateTotals() {
		if( $('#vendors-invoices-receive-purchases .vendor-invoice-purchase.selected:not(.vendor-invoice-purchase-difference)').length == 0 ) {
			$('#vendors-invoices-receive-cancel').attr('disabled','disabled');
			$('#vendors-invoices-receive-save').attr('disabled','disabled');
			$("#vendors-invoices-receive-count").val('');
		} else {
			$('#vendors-invoices-receive-cancel').attr('disabled',false);
			$('#vendors-invoices-receive-save').attr('disabled',false);
			$("#vendors-invoices-receive-count").val($('#vendors-invoices-receive-purchases .vendor-invoice-purchase.selected:not(.vendor-invoice-purchase-difference)').length);
		}
	}

	function receiveInvoicesAddPurchase($line) {
		GLOBAL_EDIT_FORM_ACTIVE = true;
		$line.slideUp(function() {
			$line.find('input[readonly="readonly"]').attr('readonly',false);
			$line.addClass('selected');
			$('#vendors-invoices-receive-purchases .vendor-invoice-purchase:first').after($line);
			$line.slideDown(function() {
				rowElementsColorVisible($('#vendors-invoices-receive-purchases'));
				receiveInvoicesUpdateTotals();
			});
		});
	}

	function receiveInvoicesRemovePurchase($line) {
		receiveInvoicesRemoveAdjustment($line);
		$line.slideUp(function() {
			$line.find('.vendor-invoice-purchase-so input[type="text"]').val($line.find('.vendor-invoice-purchase-so input[type="text"]').attr('rel'));
			$line.find('.vendor-invoice-purchase-invoice_number input[type="text"]').val('').attr('readonly','readonly');
			$line.find('.vendor-invoice-purchase-date_billed input[type="text"]').val('').attr('readonly','readonly');
			$line.find('.vendor-invoice-purchase-invoice_amount input[type="text"]').val('').attr('readonly','readonly');
			$line.removeClass('selected');
			if( $('#vendors-invoices-receive-purchases .vendor-invoice-purchase.selected:last').length == 0 ) {
				$('#vendors-invoices-receive-purchases .vendor-invoice-purchase:first').after($line);
			} else {
				$('#vendors-invoices-receive-purchases .vendor-invoice-purchase.selected:last').after($line);
			}
			$line.slideDown(function() {
				rowElementsColorVisible($('#vendors-invoices-receive-purchases'));
				receiveInvoicesUpdateTotals();
			});
		});
	}

	function receiveInvoicesSearchPurchases() {
		showPleaseWait();
		$.post(
			'/vendors/json/invoicepurchases',
			{
				search_terms: $('#vendors-invoices-receive-search').val(),
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					
					for( index in data.data.purchases ) {
						if( $('#vendors-invoices-receive-purchases li.vendor-invoice-purchase[rel="'+data.data.purchases[index].id+'"]').length == 0 ) {
							$newPurchaseFormLine = $(data.data.purchases[index].html);
							$newPurchaseFormLine.addClass('hidden');
							$('#vendors-invoices-receive-purchases .vendor-invoice-purchase:last').after($newPurchaseFormLine);
						}
					}

					if( $('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first, .selected)').length == 1 ) {
						$singleLine = $('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:first, .selected)');
						if( $('#vendors-invoices-receive-search').val() &&
							(
								$singleLine.find('.vendor-invoice-purchase-po').text().trim().toLowerCase() == $('#vendors-invoices-receive-search').val().trim().toLowerCase() ||
								$singleLine.find('.vendor-invoice-purchase-po').text().trim().toLowerCase().substring(1) == $('#vendors-invoices-receive-search').val().trim().toLowerCase() )
							) {
							$singleLine.find('.vendor-invoice-purchase-add input[type="checkbox"]').attr('checked','checked');
							checkboxUpdate($singleLine.find('.vendor-invoice-purchase-add input[type="checkbox"]'));
							receiveInvoicesAddPurchase($singleLine);
							$('#vendors-invoices-receive-search').val('');
							$('#vendors-invoices-receive-search').focus();
						} else {
							$singleLine.slideDown(function() {
								rowElementsColorVisible($('#vendors-invoices-receive-purchases'));
							});
						}
					} else {
						// Slide them all down.
						$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:not(:last)').slideDown();
						$('#vendors-invoices-receive-purchases li.vendor-invoice-purchase:last').slideDown(function() {
							rowElementsColorVisible($('#vendors-invoices-receive-purchases'));
						});
					}

					/*
					// Set the show all / show included buttons
					$('#vendors-payments-create-actions-showall').hide();
					$('#vendors-payments-create-actions-showincluded').show();

					for( index in data.data.purchases ) {
						if( $('#vendors-payments-create-purchases .vendor-paymentpo[rel="'+data.data.purchases[index].id+'"]').length == 0 ) {
							$newInvoiceFormLine = $(data.data.purchases[index].html);
							$newInvoiceFormLine.addClass('hidden');
							$('#vendors-payments-create-purchases .vendor-paymentpo:last').after($newInvoiceFormLine);
						}
					}
					// If we added only one - and it matches the search term
					if( $('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)').length == 1 ) {
						$singleLine = $('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)');
						if( $('#vendors-payments-create-actions-search').val() &&
							(
								$singleLine.find('.vendor-paymentpo-po').text().trim() == $('#vendors-payments-create-actions-search').val().trim() ||
								$singleLine.find('.vendor-paymentpo-po').text().trim().substring(1) == $('#vendors-payments-create-actions-search').val().trim().toLowerCase() )
							) {
							$singleLine.find('.vendor-paymentpo-add input[type="checkbox"]').attr('checked','checked');
							checkboxUpdate($singleLine.find('.vendor-paymentpo-add input[type="checkbox"]'));
							createVendorPaymentAddPurchase($singleLine);
							$('#vendors-payments-create-actions-search').val('');
							$('#vendors-payments-create-actions-search').focus();
						} else {
							$singleLine.slideDown(function() {
								rowElementsColorVisible($('#vendors-payments-create-purchases'));
							});
						}
					} else {
						// Slide them all down.
						$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:last)').slideDown();
						$('#vendors-payments-create-purchases li.vendor-paymentpo:last').slideDown(function() {
							rowElementsColorVisible($('#vendors-payments-create-purchases'));
						});
					}
					*/
				}
			},
			'json'
		);
	}


	function loadTaxPayment(id) {
		showPleaseWait();
		$.post(
			'/vendors/json/taxpaymentload',
			{
				payment_id: id
			},
			function(data) {
				if( ! data.success ) {
					hidePleaseWait();
					showError(data.error);
				} else {
					createTaxPaymentClearForm();
					createTaxPaymentDisableFields();
					$('.vendors-taxpayments-create-cancelsave').hide();
					$('.vendors-taxpayments-create-canceledit').show();
					$form = $('#vendors-taxpayments-create');
					$form.attr('rel',data.data.payment.id);
					$form.find('input[name="tax_id"]').val(data.data.payment.tax.id);
					$form.find('input[name="tax_id"]').select2("data", {
						id: data.data.payment.tax.id, 
						text: data.data.payment.tax.name
					});
					$form.find('input[name="date"]').val(data.data.payment.date);
					$form.find('input[name="date_start"]').val(data.data.payment.date_start);
					$form.find('input[name="date_end"]').val(data.data.payment.date_end);
					$form.find('input[name="check_number"]').val(data.data.payment.check_number);
					
					$form.find('select[name="payment_account_id"]').select2('data',{
						id: data.data.payment.payment_transaction.account.id,
						text: data.data.payment.payment_transaction.account.name
					});

					$form.find('input[name="amount"]').attr('rel',parseFloat(data.data.payment.amount).toFixed(2));
					
					$form.find('input[name="writeoff_amount"]').attr('rel','0.00');
					if( data.data.payment.writeoff_transaction ) {
						$form.find('input[name="writeoff_amount"]').attr('rel',parseFloat(data.data.payment.writeoff_amount).toFixed(2));
						$form.find('select[name="writeoff_account_id"]').select2('data',{
							id: data.data.payment.writeoff_transaction.account.id,
							text: data.data.payment.writeoff_transaction.account.name
						});
					}

					$form.find('input[name="amount"]').val(monetaryPrint($form.find('input[name="amount"]').attr('rel')));
					$form.find('input[name="writeoff_amount"]').val(monetaryPrint($form.find('input[name="writeoff_amount"]').attr('rel')));

					$form.find('input[name="total_sales"]').attr('rel',data.data.payment.invoiced_line_amount);
					$form.find('input[name="taxable_sales"]').attr('rel',data.data.payment.invoiced_line_taxable_amount);
					$form.find('input[name="tax_collected"]').attr('rel',data.data.payment.invoiced_amount);
					$form.find('input[name="total_returns"]').attr('rel',data.data.payment.refunded_line_amount);
					$form.find('input[name="taxable_returns"]').attr('rel',data.data.payment.refunded_line_taxable_amount);
					$form.find('input[name="tax_returned"]').attr('rel',data.data.payment.refunded_amount);
					$form.find('input[name="net_sales"]').attr('rel',data.data.payment.net_line_amount);
					$form.find('input[name="net_taxable"]').attr('rel',data.data.payment.net_line_taxable_amount);
					$form.find('input[name="tax_expected"]').attr('rel',data.data.payment.net_amount);

					hidePleaseWait();
					createTaxPaymentUpdateForm();
				}
			},
			'json'
		);
	}

	function taxPaymentsSearch() {
		showPleaseWait();
		$.post(
			'/vendors/json/taxpaymentsearch',
			{
				search_terms: $('#vendors-taxpayments-payments-search').val(),
				count: 25
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					for( index in data.data.payments ) {
						$payment = $(data.data.payments[index].html);
						$payment.addClass('hidden');
						$('#vendors-taxpayments-payments .vendor-taxpayment:last').after($payment);
					}
					$('#vendors-taxpayments-payments .vendor-taxpayment').slideDown(function () {
						rowElementsColorVisible($('#vendors-taxpayments-payments'));
					});
				}
			},
			'json'
		);
	}

	function createTaxPaymentDisableFields() {
		$('#vendors-taxpayments-create input[name="tax_id"]').select2('disable');
		$('#vendors-taxpayments-create input[name="date"]').attr('readonly',true);
		$('#vendors-taxpayments-create input[name="date_start"]').attr('readonly',true);
		$('#vendors-taxpayments-create input[name="date_start"]').datepicker("destroy");
		$('#vendors-taxpayments-create input[name="date_end"]').attr('readonly',true);
		$('#vendors-taxpayments-create input[name="date_end"]').datepicker("destroy");
		$('#vendors-taxpayments-create input[name="check_number"]').attr('readonly',true);
		$('#vendors-taxpayments-create input[name="writeoff_amount"]').attr('readonly',true);
		$('#vendors-taxpayments-create input[name="amount"]').attr('readonly',true);
		$('#vendors-taxpayments-create select[name="payment_account_id"]').select2('disable');
		$('#vendors-taxpayments-create select[name="writeoff_account_id"]').select2('disable');
		$('#vendors-taxpayments-create input[name="print_check"]').attr('disabled','disabled');
		$('#vendors-taxpayments-create input[name="print_check"]').closest('.checkbox').addClass('disabled');
	}

	function createTaxPaymentEnableFields() {
		$('#vendors-taxpayments-create input[name="tax_id"]').select2('enable');
		$('#vendors-taxpayments-create input[name="date"]').attr('readonly',false);
		/*
		if( ! edit ) {
			$('#vendors-taxpayments-create input[name="date_start"]').attr('readonly',false);
			$('#vendors-taxpayments-create input[name="date_start"]').datepicker({dateFormat: "yy-mm-dd"});
			$('#vendors-taxpayments-create input[name="date_end"]').attr('readonly',false);
			$('#vendors-taxpayments-create input[name="date_end"]').datepicker({dateFormat: "yy-mm-dd"});
		}
		*/
		$('#vendors-taxpayments-create input[name="check_number"]').attr('readonly',false);
		$('#vendors-taxpayments-create input[name="writeoff_amount"]').attr('readonly',false);
		$('#vendors-taxpayments-create input[name="amount"]').attr('readonly',false);
		$('#vendors-taxpayments-create select[name="payment_account_id"]').select2('enable');
		$('#vendors-taxpayments-create select[name="writeoff_account_id"]').select2('enable');
		$('#vendors-taxpayments-create input[name="print_check"]').attr('disabled',false);
		$('#vendors-taxpayments-create input[name="print_check"]').closest('.checkbox').removeClass('disabled');
	}

	function createTaxPaymentClearForm(full) {
		GLOBAL_EDIT_FORM_ACTIVE = false;
		$form = $('#vendors-taxpayments-create');
		$form.attr('rel','');
		
		$form.find('select[name="payment_account_id"]').select2('data',{})
		$form.find('select[name="writeoff_account_id"]').select2('data',{})

		$('#vendors-taxpayments-create input[name="date_start"]').attr('readonly',false);
		$('#vendors-taxpayments-create input[name="date_start"]').datepicker({dateFormat: "yy-mm-dd"});
		$('#vendors-taxpayments-create input[name="date_end"]').attr('readonly',false);
		$('#vendors-taxpayments-create input[name="date_end"]').datepicker({dateFormat: "yy-mm-dd"});
		
		if( $form.find('select[name="payment_account_id"]').attr('rel') &&
			$form.find('select[name="payment_account_id"]').attr('rel').length > 0 ) {
			$form.find('select[name="payment_account_id"]').select2('data',{
				id: $form.find('select[name="payment_account_id"]').attr('rel'),
				text: $form.find('select[name="payment_account_id"] option[value="'+$form.find('select[name="payment_account_id"]').attr('rel')+'"]').text()
			});
		}

		$form.find('input[name="check_number"]').val('');
		$form.find('input[name="total_sales"]').val('').attr('rel','');
		$form.find('input[name="taxable_sales"]').val('').attr('rel','');
		$form.find('input[name="tax_collected"]').val('').attr('rel','');
		$form.find('input[name="total_returns"]').val('').attr('rel','');
		$form.find('input[name="taxable_returns"]').val('').attr('rel','');
		$form.find('input[name="tax_returned"]').val('').attr('rel','');
		// $form.find('input[name="tax_paid"]').val('').attr('rel','');
		$form.find('input[name="tax_expected"]').val('').attr('rel','');
		$form.find('input[name="total"]').val('').attr('rel','');
		
		$form.find('input[name="amount"]').val('');
		$form.find('input[name="writeoff_amount"]').val('');
		if( full != undefined &&
			full == true ) {
			$form.find('input[name="tax_id"]').select2("data",{});
			$form.find('input[name="date_start"]').val('');
			$form.find('input[name="date_end"]').val('');
			$form.find('input[name="date"]').val('');
		}
		$('.vendors-taxpayments-create-canceledit').hide();
		$('.vendors-taxpayments-create-cancelsave').show();
	}

	function createTaxPaymentUpdateForm() {
		// Create pretty-print read-only fields.
		$form = $('#vendors-taxpayments-create');
		
		$writeoff_amount = convertCurrencyToNumber($form.find('input[name="writeoff_amount"]').val());
		if( ! $writeoff_amount ) {
			$writeoff_amount = 0.00;
		}
		$writeoff_amount = parseFloat($writeoff_amount).toFixed(2);
		$form.find('input[name="writeoff_amount"]').attr('rel',$writeoff_amount);

		$amount = convertCurrencyToNumber($form.find('input[name="amount"]').val());
		if( ! $amount ) {
			$amount = 0.00;
		}
		$amount = parseFloat($amount).toFixed(2);
		$form.find('input[name="amount"]').attr('rel',$amount);
		$total = parseFloat(parseFloat($amount) + parseFloat($writeoff_amount)).toFixed(2);
		
		$form.find('input[name="total_sales"]').val(monetaryPrint($form.find('input[name="total_sales"]').attr('rel')));
		$form.find('input[name="taxable_sales"]').val(monetaryPrint($form.find('input[name="taxable_sales"]').attr('rel')));
		$form.find('input[name="tax_collected"]').val(monetaryPrint($form.find('input[name="tax_collected"]').attr('rel')));
		$form.find('input[name="total_returns"]').val(monetaryPrint($form.find('input[name="total_returns"]').attr('rel')));
		$form.find('input[name="taxable_returns"]').val(monetaryPrint($form.find('input[name="taxable_returns"]').attr('rel')));
		$form.find('input[name="tax_returned"]').val(monetaryPrint($form.find('input[name="tax_returned"]').attr('rel')));
		// $form.find('input[name="tax_paid"]').val(monetaryPrint($form.find('input[name="tax_paid"]').attr('rel')));
		$form.find('input[name="tax_expected"]').val(monetaryPrint($form.find('input[name="tax_expected"]').attr('rel')));
		$form.find('input[name="net_sales"]').val(monetaryPrint($form.find('input[name="net_sales"]').attr('rel')));
		$form.find('input[name="net_taxable"]').val(monetaryPrint($form.find('input[name="net_taxable"]').attr('rel')));

		$form.find('input[name="amount"]').val(parseFloat($form.find('input[name="amount"]').attr('rel')).toFixed(2));
		$form.find('input[name="writeoff_amount"]').val(parseFloat($form.find('input[name="writeoff_amount"]').attr('rel')).toFixed(2));
		
		$form.find('input[name="total"').val(
			parseFloat(
				parseFloat($form.find('input[name="amount"]').attr('rel')) +
				parseFloat($form.find('input[name="writeoff_amount"]').attr('rel'))
			).toFixed(2)
		);
	}

	function createVendorIndexAddresses(form) {
		var addressIndex = 0;
		form.find('.vendors-vendors-create-form-addresses-address.indexed').each(function() {
			if( parseInt($(this).attr('rel')) >= addressIndex ) {
				addressIndex = parseInt($(this).attr('rel'))+1;
			}
		});
		// Index the addresses.
		form.find('.vendors-vendors-create-form-addresses-address:not(.indexed)').each(function() {
			$(this).find('input').each(function() {
				$(this).attr('name',$(this).attr('name')+'-'+addressIndex);
			});
			$(this).attr('rel',addressIndex);
			$(this).addClass('indexed');
			addressIndex++;
		});
		return addressIndex;
	}

	function vendorPaymentsSearch() {
		showPleaseWait();
		$('#vendors-payments-payments ul li:not(:first)').remove();
		$.post(
			'/vendors/json/paymentsearch',
			{
				search_terms: $('#vendors-payments-payments-search').val(),
				count: 5,
				page: $('#vendors-payments-payments-search').attr('rel')
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					for( index in data.data.payments ) {
						$payment = $(data.data.payments[index].html);
						$payment.addClass('hidden');
						$('#vendors-payments-payments .vendor-payment:last').after($payment);
					}
					generateSearchPaging($('#vendors-payments-payments-paging'), data.data, 5);
					$('#vendors-payments-payments-paging').html('|&nbsp;&nbsp;'+$('#vendors-payments-payments-paging').html());
					$('#vendors-payments-payments .vendor-payment').slideDown(function() {
						rowElementsColorVisible($('#vendors-payments-payments'));
					});
				}
			},
			'json'
		);
	}

	function vendorPaymentSearch() {
		var search_vendor_id = $('#vendors-payments-payments-vendor_id').val();
		var search_terms = $('#vendors-vendor-payments-search').val();

		$('#vendors-payments-loadpayments').show();
		$('#vendors-payments-loadpayments').find('.spinme').spin();
		$('#vendors-payments-payments > ul > li:not(:first)').each(function () {
			$(this).remove();
		});

		$.post(
			'/vendors/json/vendorpaymentsearch',
			{
				search_terms: search_terms,
				search_vendor_id: search_vendor_id
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more payments: "+data.error);
				} else {
					if( data.data.transactions.length == 0 ) {
						$('#vendors-payments-endpayments').show();
						$('#vendors-payments-loadpayments').hide();
					} else {
						for( index in data.data.transactions ) {
							$('#vendors-payments-payments > ul').append(data.data.transactions[index].html);
						}
						$('#vendors-payments-loadpayments').hide();	
						rowElementsColorVisible($('#vendors-payments-payments'));
					}
				}
			},
			'json'
		);
	}

	function loadMoreVendorPayments() {
		var last_payment_id = $('#vendors-payments-payments li.vendor-payment:last:not(:first)').attr('rel');
		var last_payment_date = $('#vendors-payments-payments li.vendor-payment:last:not(:first) span.vendor-payment-date').text();
		var search_vendor_id = $('#vendors-payments-payments-vendor_id').val();
		var search_terms = $('#vendors-payments-payments-search').val();
		
		if( ! last_payment_id ||
			last_payment_id == undefined ) {
			last_payment_id = '';
		}

		if( ! last_payment_date ||
			last_payment_date == undefined ) {
			last_payment_date = '';
		}

		if( ! search_terms ||
			search_terms == undefined ) {
			search_terms = '';
		}

		if( ! search_vendor_id ||
			search_vendor_id == undefined ) {
			search_vendor_id = '';
		}

		var count = 5;
		if( $('#vendors-payments-create').length == 0 ) {
			count = 20;
		}

		$('#vendors-payments-loadpayments').show();
		$('#vendors-payments-loadpayments').find('.spinme').spin();

		$.post(
			'/vendors/json/paymentsloadmore',
			{
				last_payment_id: last_payment_id,
				last_payment_date: last_payment_date,
				search_terms: search_terms,
				search_vendor_id: search_vendor_id,
				count: count
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more payments: "+data.error);
				} else {
					if( data.data.payments.length == 0 ) {
						$('#vendors-payments-endpayments').show();
						$('#vendors-payments-loadpayments').hide();
					} else {
						for( index in data.data.payments ) {
							$('#vendors-payments-payments > ul').append(data.data.payments[index].html);
						}
						$('#vendors-payments-loadpayments').hide();
						rowElementsColorVisible($('#vendors-payments-payments'));
					}
				}
			},
			'json'
		);
	}

	function loadMoreVendors() {
		var last_vendor_id = $('#vendors-vendors-vendors li.vendor-vendor:last').attr('rel');
		var last_page = $('#vendors-vendors-vendors').attr('rel');
		var search_terms = $('#vendors-vendors-vendors-search').val();
		
		if( ! last_vendor_id ||
			last_vendor_id == undefined ) {
			last_vendor_id = '';
		}

		if( ! last_page ||
			last_page == undefined ) {
			last_page = 0;
		}

		if( ! search_terms ||
			search_terms == undefined ) {
			search_terms = '';
		}

		$('#vendors-vendors-loadvendors').show();
		$('#vendors-vendors-loadvendors').find('.spinme').spin();

		$.post(
			'/vendors/json/vendorsloadmore',
			{
				last_vendor_id: last_vendor_id,
				last_page: last_page,
				search_terms: search_terms,
				count: 20
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more vendors: "+data.error);
				} else {
					$('#customers-customers-customers').attr('rel',data.data.last_page);
					if( data.data.vendors.length == 0 ) {
						$('#vendors-vendors-endvendors').show();
						$('#vendors-vendors-loadvendors').hide();
					} else {
						for( index in data.data.vendors ) {
							$('#vendors-vendors-vendors > ul').append(data.data.vendors[index].html);
						}
						$('#vendors-vendors-loadvendors').hide();
						rowElementsColorVisible($('#vendors-vendors-vendors'));
					}
				}
			},
			'json'
		);
	}

	function createExpenseUpdateTotals() {
		var total = 0.00;
		$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line').each(function() {
			$line = $(this);
			$quantity = $line.find('input.line-quantity');
			$price = $line.find('input.line-price');
			$total = $line.find('input.line-total');

			if( $price.val() &&
				$price.val().length ) {
				$price.val(convertCurrencyToNumber($price.val()).toFixed(2));
			}

			if( $quantity.val() &&
				$quantity.val().length ) {
				$quantity.val(parseInt($quantity.val()));
			}
			
			if( $quantity.val() &&
				$quantity.val().length &&
				$price.val() &&
				$price.val().length ) {

				// Flip the sign on quantity if negative.
				if( $quantity.val() < 0 ) {
					$price.val( $price.val() * -1 );
					$quantity.val( $quantity.val() * -1 );
				}

				$total.val(parseFloat(monetaryRound(parseFloat($price.val()) * parseInt($quantity.val()))).toFixed(2));
				
				total = parseFloat(parseFloat(total) + parseFloat($total.val())).toFixed(2);
			} else {
				$total.val('0.00');
			}
			
		});
		$('#vendors-expenses-create-form-total').text(monetaryPrint(parseFloat(total).toFixed(2)));
	}

	function createExpenseIndexLines() {
		var lineIndex = 0;
		$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line.indexed').each(function() {
			if( parseInt($(this).attr('rel')) >= lineIndex ) {
				lineIndex = parseInt($(this).attr('rel'))+1;
			}
		});
		$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line:not(.indexed)').each(function() {
			$(this).find('input,select').each(function() {
				$(this).attr('name',$(this).attr('name')+'-'+lineIndex);
			});
			$(this).attr('rel',lineIndex);
			$(this).addClass('indexed');
			lineIndex++;
		});
		return lineIndex;
	}

	function loadExpense(expense_id,refund) {
		if( refund == undefined ) {
			var refund = false;
		}
		$('#vendors-expenses-create').slideUp();
		showPleaseWait();
		$.post(
			'/vendors/json/expenseload',
			{
				expense_id: expense_id
			},
			function(expense_data) {
				if( ! expense_data.success ) {
					hidePleaseWait();
					showError(expense_data.error);
				} else {
					// Grab the vendor's addresses.
					if( refund ) {
						$('#vendors-expenses-create').attr('rel','R');
					} else {
						$('#vendors-expenses-create').attr('rel',expense_id);
					}
					$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line').remove();
					$('#vendors-expenses-create input:not(.ezpz-hint,.datepicker),#vendors-expenses-create select').each(function() {
						$(this).focus().val('').blur().attr('disabled','disabled');
					});
					$('#vendors-expenses-create input.datepicker').each(function() {
						$(this).datepicker("destroy").attr('readonly',true);
					});
					$('#vendors-expenses-create input[name="print_check"]').val('1');
					$('#vendors-expenses-create div.select').addClass('disabled');
					$('#vendors-expenses-create input[name="vendor"]').select2('disable');
					$('#vendors-expenses-create .vendor-expenses-create-new-buttons').hide();
					$('#vendors-expenses-create .vendor-expenses-create-edit-buttons').show();

					$newExpenseLine = $($('#vendors-expenses-create-form-lines-line-template').html());
					$newExpenseLine.find('input,select').each(function() {
						$(this).attr('disabled','disabled');
					});
					$newExpenseLine.find('div.select').addClass('disabled');
					$('#vendors-expenses-create-form-lines').append($newExpenseLine);
					$newExpenseLine.find('input.line-description').autocomplete(expenseDescriptionParams);

					$.post(
						'/vendors/json/vendoraddresses',
						{
							vendor_id: expense_data.data.expense.vendor.id
						},
						function(address_data) {
							if( address_data.success != 1 ) {
								hidePleaseWait();
								showError(data.error);
							} else {
								for( var index in address_data.data.addresses ) {
									$('#vendors-expenses-create select[name="remit_address_id"]').append('<option value="'+address_data.data.addresses[index].id+'">'+address_data.data.addresses[index].address1+'</option>');
								}

								if( refund ) {
									$('#vendors-expenses-create-title').text("Refund Expense "+expense_data.data.expense.expense_number);
								} else {
									$('#vendors-expenses-create-title').text("Expense "+expense_data.data.expense.expense_number);
								}

								if( expense_data.data.expense.remit_address ) {
									$('#vendors-expenses-create select[name="remit_address_id"]').val(expense_data.data.expense.remit_address.id);
								} else {
									$('#vendors-expenses-create select[name="remit_address_id"]').val('');
								}

								if( refund ) {
									$('#vendors-expenses-create input[name="refund_expense_id"]').val(expense_data.data.expense.id);
								}

								// Fill in expense data.
								if( refund ) {
									$('#vendors-expenses-create input[name="date_created"]').val(dateYYYYMMDD());
								} else {
									$('#vendors-expenses-create input[name="date_created"]').val(expense_data.data.expense.date_created);
								}

								if( expense_data.data.expense.vendor.default_account ) {
									$('#vendors-expenses-create input[name="vendor"]').select2("data", {id: expense_data.data.expense.vendor.id+'#'+expense_data.data.expense.vendor.default_remit_address_id+'#'+expense_data.data.expense.vendor.default_account.id+'#'+expense_data.data.expense.vendor.default_account.terms, text: expense_data.data.expense.vendor.display_name});
								} else {
									$('#vendors-expenses-create input[name="vendor"]').select2("data", {id: expense_data.data.expense.vendor.id+'#'+expense_data.data.expense.vendor.default_remit_address_id+'#', text: expense_data.data.expense.vendor.display_name});
								}

								$('#vendors-expenses-create input[name="vendor"]').select2("disable");
											
								$('#vendors-expenses-create select[name="account"]').select2('data',{
									id: expense_data.data.expense.account.id+'#'+expense_data.data.expense.account.terms,
									text: expense_data.data.expense.account.name
								});
								$('#vendors-expenses-create select[name="account"]').select2('disable');

								if( refund ) {
									$('#vendors-expenses-create input[name="invoice_number"]').val(expense_data.data.expense.invoice_number);
								} else {
									$('#vendors-expenses-create input[name="invoice_number"]').val(expense_data.data.expense.invoice_number);
								}
								$('#vendors-expenses-create input[name="so_number"]').val(expense_data.data.expense.so_number);
								$('#vendors-expenses-create input[name="check_number"]').val(expense_data.data.expense.check_number);
											
								// Line Items
								for( line_index in expense_data.data.expense.lines ) {
									$line = $('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line:last-child');
									$line.find('select[name="line-account_id"]').val(expense_data.data.expense.lines[line_index].account.id);
									$line.find('input[name="line-description"]').val(expense_data.data.expense.lines[line_index].description);
									$line.find('input[name="line-quantity"]').val(expense_data.data.expense.lines[line_index].quantity);
									
									if( refund ) {
										$line.find('input[name="line-price"]').val(parseFloat(-1 * parseFloat(expense_data.data.expense.lines[line_index].amount)));
									} else {
										$line.find('input[name="line-price"]').val(expense_data.data.expense.lines[line_index].amount);
									}
									
									$newExpenseLine = $($('#vendors-expenses-create-form-lines-line-template').html());
									$newExpenseLine.find('input,select').each(function() {
										$(this).attr('disabled','disabled');
									});
									$newExpenseLine.find('div.select').addClass('disabled');
									$('#vendors-expenses-create-form-lines').append($newExpenseLine);
									$newExpenseLine.find('input.line-description').autocomplete(expenseDescriptionParams);
								}

								createExpenseUpdateTotals();

								hidePleaseWait();
								$('#vendors-expenses-create').slideDown(function() {
									$('#vendors-expenses-create input:not(.datepicker),#vendors-expenses-create select').each(function() {
										$(this).focus().blur();
									});
									$('#vendors-expenses-create-form-lines select.account_id').each(function () {
										$(this).accountDropdown();
										$(this).select2("disable");
										$(this).closest('span').find('.select2-container').removeClass('select2-container-active');
									});
								});

								if( refund ) {
									// Enable form fields.
									$('#vendors-expenses-create input:not(.ezpz-hint,.datepicker),#vendors-expenses-create select').each(function() {
										$(this).attr('disabled',false).focus().blur();
									});
									$('#vendors-expenses-create .vendor-expenses-create-edit-buttons').hide();
									$('#vendors-expenses-create .vendor-expenses-create-new-buttons').show();
									$('#vendors-expenses-create input.datepicker').each(function() {
										$(this).attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
									});	

									$('#vendors-expenses-create-form-lines select.account_id').each(function () {
										$(this).select2("enable");
										$(this).closest('span').find('.select2-container').removeClass('select2-container-active');
									});
									
									// Disable fields that aren't edit-able.
									$('#vendors-expenses-create input[name="vendor"]').select2('disable');
									$('#vendors-expenses-create select[name="remit_address_id"]').attr('disabled','disabled');
									$('#vendors-expenses-create select[name="remit_address_id"]').closest('div.select').removeClass('disabled');
									$('#vendors-expenses-create select[name="account"]').select2('disable');
									$('#vendors-expenses-create input[name="expense_number"]').attr('disabled','disabled');
								}

							}
						}
					);
					
				}
			},
			'json'
		);
	}

	function createExpenseClearForm() {
		$('#vendors-expenses-create').slideUp(function() {
			GLOBAL_EDIT_FORM_ACTIVE = false;
			$('#vendors-expenses-create-title').text("Create Expense");
			$('#vendors-expenses-create').attr('rel','');
			$('#vendors-expenses-create input[name="refund_expense_id"]').val('');
			$('#vendors-expenses-create-form-lines .vendors-expenses-create-form-lines-line').remove();
			$('#vendors-expenses-create input:not(.ezpz-hint,.datepicker),#vendors-expenses-create select').each(function() {
				$(this).attr('disabled',false).focus().val('').blur();
			});
			$('#vendors-expenses-create select[name="account"]').select2('data',{});
			$('#vendors-expenses-create div.select').removeClass('disabled');
			if( $('#vendors-expenses-create select[name="account"]').attr('data-default') &&
				$('#vendors-expenses-create select[name="account"]').attr('data-default').length ) {
				$('#vendors-expenses-create select[name="account"]').select2('data',{
					id: $('#vendors-expenses-create select[name="account"]').attr('data-default'),
					text: $('#vendors-expenses-create select[name="account"] option[value="'+$('#vendors-expenses-create select[name="account"]').attr('data-default')+'"]').text()
				});
			}
			$('#vendors-expenses-create input.datepicker').each(function() {
				$(this).val(dateYYYYMMDD()).attr('readonly',false).attr('disabled',false).datepicker({dateFormat: "yy-mm-dd"});
			});
			$('#vendors-expenses-create select[name="account"]').select2('enable');
			$('#vendors-expenses-create input[name="vendor"]').select2('data',{});
			$('#vendors-expenses-create input[name="vendor"]').select2('enable');
			$('#vendors-expenses-create-form-total').text(monetaryPrint(0.00)).attr('rel','');
			$('#vendors-expenses-create .vendor-expenses-create-new-buttons').show();
			$('#vendors-expenses-create .vendor-expenses-create-edit-buttons').hide();
			$newExpenseLine = $($('#vendors-expenses-create-form-lines-line-template').html());
			$('#vendors-expenses-create-form-lines').append($newExpenseLine);
			$newExpenseLine.find('input.line-description').autocomplete(expenseDescriptionParams);
			
			$('#vendors-expenses-create input[name="print_check"]').val('1').attr('checked',false);
			checkboxUpdate($('#vendors-expenses-create input[name="print_check"]'));

			$('#vendors-expenses-create').slideDown(function () {
				$newExpenseLine.find('select.account_id').accountDropdown();
				$('#vendors-expenses-create .select2-container').removeClass('select2-container-active');
			});
			GLOBAL_EDIT_FORM_ACTIVE = false;
		});
	}

	function loadMoreExpenses() {
		var last_expense_id = $('#vendors-expenses-expenses li.vendor-expense:last').attr('rel');
		var last_expense_date = $('#vendors-expenses-expenses li.vendor-expense:last span.vendor-expense-date').text();
		var search_vendor_id = $('#vendors-expenses-expenses-vendor_id').val();
		var search_terms = $('#vendors-expenses-expenses-search').val();
		
		if( ! last_expense_id ||
			last_expense_id == undefined ) {
			last_expense_id = '';
		}

		if( ! last_expense_date ||
			last_expense_date == undefined ) {
			last_expense_date = '';
		}

		if( ! search_terms ||
			search_terms == undefined ) {
			search_terms = '';
		}

		if( ! search_vendor_id ||
			search_vendor_id == undefined ) {
			search_vendor_id = '';
		}

		$('#vendors-expenses-loadexpenses').show();
		$('#vendors-expenses-loadexpenses').find('.spinme').spin();

		$.post(
			'/vendors/json/expensesloadmore',
			{
				last_expense_id: last_expense_id,
				last_expense_date: last_expense_date,
				search_terms: search_terms,
				search_vendor_id: search_vendor_id,
				count: 20
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more payments: "+data.error);
				} else {
					if( data.data.expenses.length == 0 ) {
						$('#vendors-expenses-endexpenses').show();
						$('#vendors-expenses-loadexpenses').hide();
					} else {
						for( index in data.data.expenses ) {
							$('#vendors-expenses-expenses > ul').append(data.data.expenses[index].html);
						}
						$('#vendors-expenses-loadexpenses').hide();
						rowElementsColorVisible($('#vendors-expenses-expenses'));
					}
				}
			},
			'json'
		);
	}

	function createPurchaseUpdateTotals() {
		var total = 0.00;
		$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line').each(function() {
			$line = $(this);
			$quantity = $line.find('input.line-quantity');
			$price = $line.find('input.line-price');
			$total = $line.find('input.line-total');

			if( $price.val() &&
				$price.val().length ) {
				$price.val(convertCurrencyToNumber($price.val()).toFixed(2));
			}

			if( $quantity.val() &&
				$quantity.val().length ) {
				$quantity.val(parseInt($quantity.val()));
			}
			
			if( $quantity.val() &&
				$quantity.val().length &&
				$price.val() &&
				$price.val().length ) {

				// Flip the sign on quantity if negative.
				if( $quantity.val() < 0 ) {
					$price.val( $price.val() * -1 );
					$quantity.val( $quantity.val() * -1 );
				}

				$total.val(parseFloat(monetaryRound(parseFloat($price.val()) * parseInt($quantity.val()))).toFixed(2));
				
				total = parseFloat(parseFloat(total) + parseFloat($total.val())).toFixed(2);
			} else {
				$total.val('0.00');
			}
			
		});
		$('#vendors-purchases-create-form-total').text(monetaryPrint(parseFloat(total).toFixed(2)));
		$('#vendors-purchases-create-form-balance').text(monetaryPrint(parseFloat(total).toFixed(2)));
		if( $('#vendors-purchases-create-form-balance').attr('rel') && 
			$('#vendors-purchases-create-form-balance').attr('rel').length ) {
			$('#vendors-purchases-create-form-balance').val(
				parseFloat(
					convertCurrencyToNumber($('#vendors-purchases-create-form-balance').text()) - parseFloat($('#vendors-purchases-create-form-balance').attr('rel'))
				).toFixed(2)
			); 
		}
	}

	function createPurchaseIndexLines() {
		var lineIndex = 0;
		$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line.indexed').each(function() {
			if( parseInt($(this).attr('rel')) >= lineIndex ) {
				lineIndex = parseInt($(this).attr('rel'))+1;
			}
		});
		$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line:not(.indexed)').each(function() {
			$(this).find('input,select').each(function() {
				$(this).attr('name',$(this).attr('name')+'-'+lineIndex);
			});
			$(this).attr('rel',lineIndex);
			$(this).addClass('indexed');
			lineIndex++;
		});
		return lineIndex;
	}


	function loadPurchase(purchase_id,refund) {
		if( refund == undefined ) {
			var refund = false;
		}
		$('#vendors-purchases-create-form-send').hide();
		$('#vendors-purchases-create').slideUp();
		showPleaseWait();
		$.post(
			'/vendors/json/purchaseload',
			{
				purchase_id: purchase_id
			},
			function(purchase_data) {
				if( ! purchase_data.success ) {
					hidePleaseWait();
					showError(purchase_data.error);
				} else {
					// Grab the vendor's addresses.
					if( refund ) {
						$('#vendors-purchases-create').attr('rel','R');
					} else {
						$('#vendors-purchases-create').attr('rel',purchase_id);
					}
					$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line').remove();
					$('#vendors-purchases-create input:not(.ezpz-hint,.send-form,.datepicker),#vendors-purchases-create select').each(function() {
						$(this).focus().val('').blur().attr('disabled','disabled');
					});
					$('#vendors-purchases-create input.datepicker').each(function() {
						$(this).datepicker("destroy").attr('readonly',true);
					});
					$('#vendors-purchases-create input[name="shipping_address_id"]').select2('disable');
					$('#vendors-purchases-create div.select').addClass('disabled');
					$('#vendors-purchases-create .vendor-purchases-create-new-buttons').hide();
					$('#vendors-purchases-create .vendor-purchases-create-edit-buttons').show();

					$newPurchaseLine = $($('#vendors-purchases-create-form-lines-line-template').html());
					$newPurchaseLine.find('input,select').each(function() {
						$(this).attr('disabled','disabled');
					});
					$newPurchaseLine.find('div.select').addClass('disabled');
					$('#vendors-purchases-create-form-lines').append($newPurchaseLine);
					$newPurchaseLine.find('input.line-description').autocomplete(purchaseDescriptionParams);

					$.post(
						'/vendors/json/vendoraddresses',
						{
							vendor_id: purchase_data.data.purchase.vendor.id
						},
						function(address_data) {
							if( address_data.success != 1 ) {
								hidePleaseWait();
								showError(data.error);
							} else {
								$('#vendors-purchases-create-form-send input[name="email"]').val(address_data.data.vendor.email);
								for( var index in address_data.data.addresses ) {
									$('#vendors-purchases-create select[name="remit_address_id"]').append('<option value="'+address_data.data.addresses[index].id+'">'+address_data.data.addresses[index].address1+'</option>');
								}

								if( purchase_data.data.purchase.shipping_address && 
									purchase_data.data.purchase.shipping_address.id ) {
									$('#vendors-purchases-create input[name="shipping_address_id"]').select2('data',{
										id: purchase_data.data.purchase.shipping_address.id,
										text: purchase_data.data.purchase.shipping_address.standard
									});
								} else {
									$('#vendors-purchases-create input[name="shipping_address_id"]').select2('data',{});
								}
								
								if( purchase_data.data.purchase.date_cancelled ) {
									$('#vendors-purchases-create-form-edit').attr('disabled','disabled');
									$('#vendors-purchases-create-form-delete').attr('disabled','disabled');
								} else {
									$('#vendors-purchases-create-form-edit').attr('disabled',false);
									$('#vendors-purchases-create-form-delete').attr('disabled',false);
								}

								if( ! purchase_data.data.purchase.date_cancelled &&
									purchase_data.data.purchase.date_billed &&
									purchase_data.data.purchase.balance == 0.00 ) {
									$('#vendors-purchases-create-form-return').attr('disabled',false);
								} else {
									$('#vendors-purchases-create-form-return').attr('disabled','disabled');
								}

								if( refund ) {
									$('#vendors-purchases-create-title').text("Refund Purchase "+purchase_data.data.purchase.purchase_number);
								} else {
									$('#vendors-purchases-create-title').text("Purchase "+purchase_data.data.purchase.purchase_number);
								}

								if( purchase_data.data.purchase.payments.length > 0 ) {
									$('#vendors-purchases-create-status').html('<span class="text-bold">'+purchase_data.data.purchase.status+' - Payments: </span>');
									var first = true;
									for( i in purchase_data.data.purchase.payments ) {
										if( first ) {
											first = false;
										} else {
											$('#vendors-purchases-create-status').append(',');
										}
										$('#vendors-purchases-create-status').append(' <a href="/vendors/payments/'+purchase_data.data.purchase.payments[i].id+'">'+purchase_data.data.purchase.payments[i].date+'</a>');
									}
								} else {
									$('#vendors-purchases-create-status').html('<span class="text-bold">'+purchase_data.data.purchase.status+'</span>');
								}

								if( refund ) {
									$('#vendors-purchases-create input[name="refund_purchase_id"]').val(purchase_data.data.purchase.id);
								}

								// Fill in purchase data.
								if( refund ) {
									$('#vendors-purchases-create input[name="date_created"]').val(dateYYYYMMDD());
								} else {
									$('#vendors-purchases-create input[name="date_created"]').val(purchase_data.data.purchase.date_created);
								}

								if( refund ) {
									$('#vendors-purchases-create input[name="date_billed"]').val(dateYYYYMMDD());
								} else {
									$('#vendors-purchases-create input[name="date_billed"]').val(purchase_data.data.purchase.date_billed);
								}

								if( refund ) {
									$('#vendors-purchases-create input[name="invoice_number"]').val('');
								} else {
									$('#vendors-purchases-create input[name="invoice_number"]').val(purchase_data.data.purchase.invoice_number);
								}
								
								if( purchase_data.data.purchase.vendor.default_account ) {
									$('#vendors-purchases-create input[name="vendor"]').select2("data", {id: purchase_data.data.purchase.vendor.id+'#'+purchase_data.data.purchase.vendor.default_remit_address_id+'#'+purchase_data.data.purchase.vendor.default_account.id+'#'+purchase_data.data.purchase.vendor.default_account.terms, text: purchase_data.data.purchase.vendor.display_name});
								} else {
									$('#vendors-purchases-create input[name="vendor"]').select2("data", {id: purchase_data.data.purchase.vendor.id+'#'+purchase_data.data.purchase.vendor.default_remit_address_id+'#', text: purchase_data.data.purchase.vendor.display_name});
								}

								$('#vendors-purchases-create input[name="vendor"]').select2("disable");
								$('#vendors-purchases-create input[name="vendor"]').attr('disabled',false);

								if( purchase_data.data.purchase.remit_address ) {
									$('#vendors-purchases-create select[name="remit_address_id"]').val(purchase_data.data.purchase.remit_address.id);
								} else {
									$('#vendors-purchases-create select[name="remit_address_id"]').val('');
								}
								
								$('#vendors-purchases-create select[name="account"]').select2('data',{
									id: purchase_data.data.purchase.account.id+'#'+purchase_data.data.purchase.account.terms,
									text: purchase_data.data.purchase.account.name
								});

								if( refund ) {
									$('#vendors-purchases-create input[name="purchase_number"]').val('R'+purchase_data.data.purchase.purchase_number);
								} else {
									$('#vendors-purchases-create input[name="purchase_number"]').val(purchase_data.data.purchase.purchase_number);
								}

								$('#vendors-purchases-create input[name="so_number"]').val(purchase_data.data.purchase.so_number);
								$('#vendors-purchases-create input[name="quote_number"]').val(purchase_data.data.purchase.quote_number);
								
								// Line Items
								for( line_index in purchase_data.data.purchase.lines ) {
									$line = $('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line:last-child');
									$line.find('select[name="line-account_id"]').val(purchase_data.data.purchase.lines[line_index].account.id);
									$line.find('input[name="line-description"]').val(purchase_data.data.purchase.lines[line_index].description);
									$line.find('input[name="line-quantity"]').val(purchase_data.data.purchase.lines[line_index].quantity);
									
									if( refund ) {
										$line.find('input[name="line-price"]').val(parseFloat(-1 * parseFloat(purchase_data.data.purchase.lines[line_index].amount)));
									} else {
										$line.find('input[name="line-price"]').val(purchase_data.data.purchase.lines[line_index].amount);
									}
									
									$newPurchaseLine = $($('#vendors-purchases-create-form-lines-line-template').html());
									$newPurchaseLine.find('input,select').each(function() {
										$(this).attr('disabled','disabled');
									});
									$newPurchaseLine.find('div.select').addClass('disabled');
									$('#vendors-purchases-create-form-lines').append($newPurchaseLine);
									$newPurchaseLine.find('input.line-description').autocomplete(purchaseDescriptionParams);
								}

								if( refund ) {
									$('#vendors-purchases-create-form-balance').attr('rel','');
								} else {
									$('#vendors-purchases-create-form-balance').attr('rel',parseFloat(purchase_data.data.purchase.total - purchase_data.data.purchase.balance));
								}

								createPurchaseUpdateTotals();

								hidePleaseWait();
								$('#vendors-purchases-create').slideDown(function() {
									$('#vendors-purchases-create input:not(.datepicker),#vendors-purchases-create select').each(function() {
										$(this).focus().blur();
									});
									$('#vendors-purchases-create-form-lines select.account_id').each(function () {
										$(this).accountDropdown();
										$(this).select2("disable");
									});
								});

								if( refund ) {
									// Enable form fields.
									$('#vendors-purchases-create input:not(.ezpz-hint,.datepicker),#vendors-purchases-create select').each(function() {
										$(this).attr('disabled',false).focus().blur();
									});
									$('#vendors-purchases-create .vendor-purchases-create-edit-buttons').hide();
									$('#vendors-purchases-create .vendor-purchases-create-new-buttons').show();

									$('#customers-sales-create-form-lines select.account_id').each(function () {
										$(this).select2("enable");
									});
									
									$('#vendors-purchases-create input.datepicker').each(function() {
										$(this).attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
									});	

									// Disable fields that aren't edit-able.
									$('#vendors-purchases-create input[name="vendor"]').select2('disable');
									$('#vendors-purchases-create select[name="remit_address_id"]').attr('disabled','disabled');
									$('#vendors-purchases-create select[name="remit_address_id"]').closest('div.select').removeClass('disabled');
									$('#vendors-purchases-create select[name="account"]').select2('disable');
									$('#vendors-purchases-create input[name="purchase_number"]').attr('disabled','disabled');
									$('#vendors-purchases-create input[name="quote_number"]').attr('disabled','disabled');
									
								}
								
							}
						},
						'json'
					);
				}
			},
			'json'
		);
	}

	function createPurchaseClearForm() {
		$('#vendors-purchases-create').slideUp(function() {
			GLOBAL_EDIT_FORM_ACTIVE = false;
			$('#vendors-purchases-create-form-send').hide();
			$('#vendors-purchases-create-title').text($('#vendors-purchases-create-title').attr('rel'));
			$('#vendors-purchases-create-status').html('<span class="text-bold">'+$('#vendors-purchases-create-status').attr('rel')+'</span>');
			$('#vendors-purchases-create').attr('rel','');
			$('#vendors-purchases-create input[name="refund_purchase_id"]').val('');
			$('#vendors-purchases-create-form-lines .vendors-purchases-create-form-lines-line').remove();
			$('#vendors-purchases-create input:not(.ezpz-hint,.send-form,.datepicker),#vendors-purchases-create select').each(function() {
				$(this).attr('disabled',false).focus().val('').blur();
			});
			$('#vendors-purchases-create input.datepicker').each(function() {
				$(this).val(dateYYYYMMDD()).attr('readonly',false).attr('disabled',false).datepicker({dateFormat: "yy-mm-dd"});
			});
			$('#vendors-purchases-create div.select').removeClass('disabled');
			$('#vendors-purchases-create select[name="account"]').select2('enable');
			$('#vendors-purchases-create select[name="account"]').select2('data',{});
			if( $('#vendors-purchases-create select[name="account"]').attr('data-default') &&
				$('#vendors-purchases-create select[name="account"]').attr('data-default').length ) {
				$('#vendors-purchases-create select[name="account"]').select2('data',{
					id: $('#vendors-purchases-create select[name="account"]').attr('data-default'),
					text: $('#vendors-purchases-create select[name="account"] option[value="'+$('#vendors-purchases-create select[name="account"]').attr('data-default')+'"]').text()
				});
			}
			$('#vendors-purchases-create input[name="vendor"]').select2('data',{});
			$('#vendors-purchases-create input[name="vendor"]').select2('enable');
			$('#vendors-purchases-create input[name="shipping_address_id"]').select2('data',{});
			$('#vendors-purchases-create input[name="shipping_address_id"]').select2('enable');
			$('#vendors-purchases-create .vendor-purchases-create-new-buttons').show();
			$('#vendors-purchases-create .vendor-purchases-create-edit-buttons').hide();
			$('#vendors-purchases-create-form-edit').attr('disabled',false);
			$('#vendors-purchases-create-form-delete').attr('disabled',false);
			$('#vendors-purchases-create-form-return').attr('disabled',false);
			$newPurchaseLine = $($('#vendors-purchases-create-form-lines-line-template').html());
			$('#vendors-purchases-create-form-lines').append($newPurchaseLine);
			$newPurchaseLine.find('input.line-description').autocomplete(purchaseDescriptionParams);
			createPurchaseUpdateTotals();
			$('#vendors-purchases-create').slideDown(function () {
				$newPurchaseLine.find('select.account_id').accountDropdown();
				$('#vendors-purchases-create .select2-container').removeClass('select2-container-active');
			});
		});
	}

	function loadMorePurchases() {
		var last_purchase_id = $('#vendors-purchases-purchases li.vendor-purchase:last').attr('rel');
		var last_purchase_date = $('#vendors-purchases-purchases li.vendor-purchase:last span.vendor-purchase-date').text();
		var search_vendor_id = $('#vendors-purchases-purchases-vendor_id').val();
		var search_terms = $('#vendors-purchases-purchases-search').val();
		
		if( ! last_purchase_id ||
			last_purchase_id == undefined ) {
			last_purchase_id = '';
		}

		if( ! last_purchase_date ||
			last_purchase_date == undefined ) {
			last_purchase_date = '';
		}

		if( ! search_terms ||
			search_terms == undefined ) {
			search_terms = '';
		}

		if( ! search_vendor_id ||
			search_vendor_id == undefined ) {
			search_vendor_id = '';
		}

		$('#vendors-purchases-loadpurchases').show();
		$('#vendors-purchases-loadpurchases').find('.spinme').spin();

		$.post(
			'/vendors/json/purchasesloadmore',
			{
				last_purchase_id: last_purchase_id,
				last_purchase_date: last_purchase_date,
				search_terms: search_terms,
				search_vendor_id: search_vendor_id,
				count: 20
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more payments: "+data.error);
				} else {
					if( data.data.purchases.length == 0 ) {
						$('#vendors-purchases-endpurchases').show();
						$('#vendors-purchases-loadpurchases').hide();
					} else {
						for( index in data.data.purchases ) {
							$('#vendors-purchases-purchases > ul').append(data.data.purchases[index].html);
						}
						$('#vendors-purchases-loadpurchases').hide();
						rowElementsColorVisible($('#vendors-purchases-purchases'));
					}
				}
			},
			'json'
		);
	}

	function loadVendorPayment(id) {
		showPleaseWait();
		$('#vendors-payments-create').slideUp();
		$.post(
			'/vendors/json/paymentload',
			{
				payment_id: id
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					createVendorPaymentClearForm();
					$('#vendors-payments-create').attr('rel',data.data.payment.id);
					// Assign appropriate values to batch payment form and make everything readonly.
					$('#vendors-payments-create input[name="date"]').val(data.data.payment.date);
					$('#vendors-payments-create input[name="vendor_id"]').select2('data',{id:data.data.payment.vendor.id, text: data.data.payment.vendor.display_name});

					createVendorPaymentFetchAddresses();

					$('#vendors-payments-create input[name="purchase_total"]').val(data.data.payment.amount);
					$('#vendors-payments-create input[name="check_number"]').val(data.data.payment.check_number);
					$('#vendors-payments-create input[name="amount"]').val(parseFloat(data.data.payment.payment_transaction.amount).toFixed(2));
					
					$('#vendors-payments-create select[name="payment_account_id"]').select2('data',{
						id: data.data.payment.payment_transaction.account.id,
						text: data.data.payment.payment_transaction.account.name
					});

					if( data.data.payment.writeoff_transaction ) {
						$('#vendors-payments-create input[name="writeoff_amount"]').val(parseFloat(data.data.payment.writeoff_transaction.amount * -1).toFixed(2));
						$('#vendors-payments-create select[name="writeoff_account_id"]').select2('data',{
							id: data.data.payment.writeoff_transaction.account.id,
							text: data.data.payment.writeoff_transaction.account.name
						});
					}
					
					for( index in data.data.payment.purchase_payments ) {
						$line = $(data.data.payment.purchase_payments[index].html)
						$line.addClass('selected');
						$('#vendors-payments-create-purchases .vendor-paymentpo:last').after($line);
					}

					createVendorPaymentDisableFields();
					
					// Adjust Buttons
					$('.vendors-payments-create-actions-save').hide();
					$('.vendors-payments-create-actions-deleteplaceholder').hide();
					$('.vendors-payments-create-actions-delete').show();
					$('.vendors-payments-create-actions-edit').show();
					
					$('#vendors-payments-create').slideDown(function() {
						rowElementsColorVisible($('#vendors-payments-create-purchases'));
					});
				}
			},
			'json'
		);
	}

	function createVendorPaymentAddPurchase($line) {
		$line.slideUp(function() {
			$balance = parseFloat($line.find('.vendor-paymentpo-numeric.balance').attr('rel')).toFixed(2);
			$line.find('.vendor-paymentpo-numeric.amount').find('input[type="text"]').val($balance);
			$line.find('.vendor-paymentpo-numeric.amount').find('input[type="text"]').attr('readonly',false);
			$line.find('.vendor-paymentpo-date_billed').find('input[type="text"]').attr('readonly',false).attr('placeholder','Optional');
			if( ! $line.find('.vendor-paymentpo-date_billed').find('input[type="text"]').val().length ) {
				$line.find('.vendor-paymentpo-date_billed').find('input[type="text"]').val(dateYYYYMMDD());
			}
			$line.find('.vendor-paymentpo-invoice').find('input[type="text"]').attr('readonly',false).attr('placeholder','Optional');;
			$('#vendors-payments-create-purchases .vendor-paymentpo:first').after($line);
			$line.addClass('selected');
			$line.slideDown(function() {
				rowElementsColorVisible($('#vendors-payments-create-purchases'));
				createVendorPaymentUpdateTotals();
			});
		});
	}

	function createVendorPaymentRemovePurchase($line) {
		$line.slideUp(function() {
			$balance = parseFloat($line.find('.vendor-paymentpo-numeric.balance').attr('rel')).toFixed(2);
			$line.find('.vendor-paymentpo-numeric.amount').find('input[type="text"]').val('0.00');
			$line.find('.vendor-paymentpo-numeric.amount').find('input[type="text"]').attr('readonly',true);
			$date_billed = $line.find('.vendor-paymentpo-date_billed').find('input[type="text"]');
			$date_billed.attr('readonly',true);
			$date_billed.val($date_billed.attr('rel'));
			$date_billed.removeAttr('placeholder');
			$invoice = $line.find('.vendor-paymentpo-invoice').find('input[type="text"]');
			$invoice.attr('readonly',true);
			$invoice.val($invoice.attr('rel'));
			$invoice.removeAttr('placeholder');
			$line.removeClass('selected');
			// Where do we add this?
			if( $('#vendors-payments-create-purchases .vendor-paymentpo.selected:last').length == 0 ) {
				$('#vendors-payments-create-purchases .vendor-paymentpo:first').after($line);
			} else {
				$('#vendors-payments-create-purchases .vendor-paymentpo.selected:last').after($line);
			}
			$line.find('.vendor-paymentpo-balancewriteoff input[type="checkbox"]').prop('checked',false);
			$line.find('.vendor-paymentpo-balancewriteoff input[type="checkbox"]').prop('disabled',true);
			checkboxUpdate($line.find('.vendor-paymentpo-balancewriteoff input[type="checkbox"]'));
			$line.find('.vendor-paymentpo-add input[type="checkbox"]').prop('checked',false);
			checkboxUpdate($line.find('.vendor-paymentpo-add input[type="checkbox"]'));
			$line.slideDown(function() {
				rowElementsColorVisible($('#vendors-payments-create-purchases'));
				createVendorPaymentUpdateTotals();
			});
		});
	}

	function createVendorPaymentUpdateTotals() {
		$amount = $('#vendors-payments-create input[name="amount"]');
		if( $amount.val().length == 0 ) {
			$amount.val('0.00');
		}
		$amount.val(parseFloat($amount.val()).toFixed(2));
		$total = 0.00;
		// $balance = 0.00;
		$writeoff = 0.00;
		$('#vendors-payments-create-purchases .vendor-paymentpo.selected').each(function() {
			$line = $(this);
			
			$lineAmount = $line.find('.vendor-paymentpo-numeric.amount').find('input[type="text"]');
			if( $lineAmount.val().length == 0 ) {
				$lineAmount.val('0.00');
			}
			$lineAmount.val(parseFloat(convertCurrencyToNumber($lineAmount.val())).toFixed(2));

			$linePurchaseBalance = parseFloat(parseFloat($line.find('.vendor-paymentpo-numeric.balance').attr('rel')).toFixed(2));

			// $balance += parseFloat($linePurchaseBalance).toFixed(2);

			$total += parseFloat($lineAmount.val());
			
			$lineBalance = parseFloat(parseFloat(parseFloat($line.find('.vendor-paymentpo-numeric.balance').attr('rel')).toFixed(2))-parseFloat($lineAmount.val()).toFixed(2));

			$lineWriteoff = $line.find('.vendor-paymentpo-balancewriteoff input[type="checkbox"]');
			if( parseFloat($lineBalance).toFixed(2) != "0.00" ) {
				$lineWriteoff.attr('disabled',false);
				checkboxUpdate($lineWriteoff);
			} else {
				$lineWriteoff.attr('disabled','disabled');
				checkboxUpdate($lineWriteoff);
			}

			if( $lineWriteoff.is(':checked') ) {
				$lineWriteoff.val(parseFloat($lineBalance).toFixed(2));
				$writeoff += parseFloat(parseFloat($lineBalance).toFixed(2));
				$lineBalance = 0.00;
			}

			$line.find('.vendor-paymentpo-numeric.balancenew').text(
				monetaryPrint(
					monetaryRound(
						$lineBalance
					)
				)
			);
		});
		$('#vendors-payments-create input[name="purchase_total"]').val(parseFloat(parseFloat($total) + parseFloat($writeoff)).toFixed(2));
		$('#vendors-payments-create input[name="amount"]').val(parseFloat($total).toFixed(2));
		$('#vendors-payments-create input[name="writeoff_amount"]').val(parseFloat($writeoff).toFixed(2));

		if( $writeoff != 0.00 &&
			( 
				$('#vendors-payments-create select[name="writeoff_account_id"]').val() == undefined || 
				$('#vendors-payments-create select[name="writeoff_account_id"]').val().length == 0 
			) ) {
			$('#vendors-payments-create-save').attr('disabled',true);
			$('#vendors-payments-create select[name="writeoff_account_id"]').closest('span').find('div.select2-container').addClass('unclassified');
		} else {
			$('#vendors-payments-create-save').attr('disabled',false);
			$('#vendors-payments-create select[name="writeoff_account_id"]').closest('span').find('div.select2-container').removeClass('unclassified');
		}
	}

	function createVendorPaymentClearForm() {
		GLOBAL_EDIT_FORM_ACTIVE = false;
		$('#vendors-payments-create input[name="replace_transaction_id"]').val('');
		$('#vendors-payments-create').attr('rel','');
		createVendorPaymentEnableFields();
		$('#vendors-payments-create-purchases .vendor-paymentpo:not(:first)').remove();
		$('#vendors-payments-create input[name="vendor_id"]').select2('data',{});
		$('#vendors-payments-create input[name="check_number"]').val('');
				
		$('#vendors-payments-create select[name="payment_account_id"]').select2('data',{});
		if( $('#vendors-payments-create select[name="payment_account_id"]').attr('rel') &&
			$('#vendors-payments-create select[name="payment_account_id"]').attr('rel').length > 0 ) {
			$('#vendors-payments-create select[name="payment_account_id"]').select2('data',{
				id: $('#vendors-payments-create select[name="payment_account_id"]').attr('rel'),
				text: $('#vendors-payments-create select[name="payment_account_id"] option[value="'+$('#vendors-payments-create select[name="payment_account_id"]').attr('rel')+'"]').text()
			});
		}
		
		$('#vendors-payments-create select[name="writeoff_account_id"]').select2('data',{
			id: '',
			text: $('#vendors-payments-create select[name="writeoff_account_id"] option[value=""]').text()
		});
		$('#customers-payments-create input[name="date"]').val(dateYYYYMMDD());

		$('#vendors-payments-create input[name="purchase_total"]').val('0.00');
		$('#vendors-payments-create input[name="writeoff_amount"]').val('0.00');
		$('#vendors-payments-create input[name="amount"]').val('0.00');
		
		$('#vendors-payments-create input[name="print_check"]').val('1').attr('checked',false);
		checkboxUpdate($('#vendors-payments-create input[name="print_check"]'));

		// Reset buttons
		$('.vendors-payments-create-actions-delete').hide();
		$('.vendors-payments-create-actions-edit').hide();
		$('.vendors-payments-create-actions-save').show();
		$('.vendors-payments-create-actions-deleteplaceholder').show();
		
		$('#vendors-payments-create-actions-search').val('');
	}

	function createVendorPaymentSearchPurchases() {
		showPleaseWait();
		$.post(
			'/vendors/json/paymentpurchases',
			{
				vendor_id: $('#vendors-payments-create input[name="vendor_id"]').val(),
				search_terms: $('#vendors-payments-create-actions-search').val(),
				oldest_first: '1'
			}, 
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					// Set the show all / show included buttons
					$('#vendors-payments-create-actions-showall').hide();
					$('#vendors-payments-create-actions-showincluded').show();

					for( index in data.data.purchases ) {
						if( $('#vendors-payments-create-purchases .vendor-paymentpo[rel="'+data.data.purchases[index].id+'"]').length == 0 ) {
							$newInvoiceFormLine = $(data.data.purchases[index].html);
							$newInvoiceFormLine.addClass('hidden');
							$('#vendors-payments-create-purchases .vendor-paymentpo:last').after($newInvoiceFormLine);
						}
					}
					// If we added only one - and it matches the search term
					if( $('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)').length == 1 ) {
						$singleLine = $('#vendors-payments-create-purchases li.vendor-paymentpo:not(:first, .selected)');
						if( $('#vendors-payments-create-actions-search').val() &&
							(
								$singleLine.find('.vendor-paymentpo-po').text().trim() == $('#vendors-payments-create-actions-search').val().trim() ||
								$singleLine.find('.vendor-paymentpo-po').text().trim().substring(1) == $('#vendors-payments-create-actions-search').val().trim().toLowerCase() )
							) {
							$singleLine.find('.vendor-paymentpo-add input[type="checkbox"]').attr('checked','checked');
							checkboxUpdate($singleLine.find('.vendor-paymentpo-add input[type="checkbox"]'));
							createVendorPaymentAddPurchase($singleLine);
							$('#vendors-payments-create-actions-search').val('');
							$('#vendors-payments-create-actions-search').focus();
						} else {
							$singleLine.slideDown(function() {
								rowElementsColorVisible($('#vendors-payments-create-purchases'));
							});
						}
					} else {
						// Slide them all down.
						$('#vendors-payments-create-purchases li.vendor-paymentpo:not(:last)').slideDown();
						$('#vendors-payments-create-purchases li.vendor-paymentpo:last').slideDown(function() {
							rowElementsColorVisible($('#vendors-payments-create-purchases'));
						});
					}
				}
			},
			'json'
		);
	}

	var createVendorPaymentAddresses = {};

	function createVendorPaymentFetchAddresses() {
		delete createVendorPaymentAddresses;
		createVendorPaymentAddresses = {};
		$.post(
			'/vendors/json/vendoraddresses',
			{
				vendor_id: $('#vendors-payments-create input[name="vendor_id"]').val()
			},
			function ( data ) {
				if( ! data.success ) {
					showError(data.error);
				} else {
					$('#vendors-payments-address-dialog input[name="vendor_id"]').val(data.data.vendor.id);
					$select = $('#vendors-payments-address-dialog select[name="address_id"]');
					$select.find('option').remove()
					// Do we want to offer a blank option?
					$option = $('<option value="">&nbsp;</option>');
					$option = $('<option value="new">Create New Address</option>');
					$select.append($option);
					for( index in data.data.addresses ) {
						$option = $('<option value="'+
							data.data.addresses[index].id+'">'+
							data.data.addresses[index].address1+' '+
							data.data.addresses[index].address2+', '+
							data.data.addresses[index].city+' '+
							data.data.addresses[index].state+', '+
							data.data.addresses[index].zip+' '+
							data.data.addresses[index].country+
							'</option>');
						createVendorPaymentAddresses[data.data.addresses[index].id] = {
							address1: data.data.addresses[index].address1,
							address2: data.data.addresses[index].address2,
							city: data.data.addresses[index].city,
							state: data.data.addresses[index].state,
							zip: data.data.addresses[index].zip,
							country: data.data.addresses[index].country
						}
						$select.append($option);
					}
				}
			},
			'json'
		);
	}

	function createVendorPaymentEnableFields() {
		$('#vendors-payments-create-actions').slideDown();
		$('#vendors-payments-create input[name="date"]').attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
		$('#vendors-payments-create input[name="check_number"]').attr('readonly',false);
		if( ! $('#vendors-payments-create').attr('rel') || 
			! $('#vendors-payments-create').attr('rel').length ) {
			$('#vendors-payments-create input[name="vendor_id"]').select2('enable');
		}
		$('#vendors-payments-create div.select').removeClass('disabled');
		$('#vendors-payments-create select[name="payment_account_id"]').select2('enable');
		$('#vendors-payments-create select[name="writeoff_account_id"]').select2('enable');
		$('#vendors-payments-create input[name="print_check"]').attr('disabled',false);
		$('#vendors-payments-create input[name="print_check"]').closest('.checkbox').removeClass('disabled');
	}

	function createVendorPaymentDisableFields() {
		$('#vendors-payments-create-actions').hide();
		$('#vendors-payments-create input[name="date"]').attr('readonly',true).datepicker("destroy");
		$('#vendors-payments-create input[name="amount"]').attr('readonly','readonly');
		$('#vendors-payments-create input[name="check_number"]').attr('readonly','readonly');
		$('#vendors-payments-create input[name="vendor_id"]').select2('disable');
		$('#vendors-payments-create div.select').addClass('disabled');
		$('#vendors-payments-create select[name="payment_account_id"]').select2('disable');
		$('#vendors-payments-create select[name="writeoff_account_id"]').select2('disable');
		$('#vendors-payments-create input[name="print_check"]').attr('disabled','disabled');
		$('#vendors-payments-create input[name="print_check"]').closest('.checkbox').addClass('disabled');
	}

	var popupWindow;
	function printVendorExpense(id) {
		popupWindow = popupWindowLoad('/print/vendorexpense/'+id);
		$(popupWindow.document).ready( function () {
			setTimeout( function () { popupWindow.print(); } , 1000 );
		});
	}

	function printVendorPurchase(id) {
		popupWindow = popupWindowLoad('/print/vendorpurchase/'+id);
		$(popupWindow.document).ready( function () {
			setTimeout( function () { popupWindow.print(); } , 1000 );
		});
	}

	function printVendorPayment(id) {
		popupWindow = popupWindowLoad('/print/vendorpayment/'+id);
		$(popupWindow.document).ready( function () {
			setTimeout( function () { popupWindow.print(); } , 1000 );
		});
	}

}