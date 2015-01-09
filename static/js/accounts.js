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

if ( document.body.className.match(new RegExp('(\\s|^)accounts(\\s|$)')) !== null ) {

	/**
	 * Javascript for pages related to accounts/
	 */

	$(function() {

		/**
		 * CHART
		 */
		$('#accounts-chart-accounts .account-actions .delete').live('click',function (e) {
			e.preventDefault();

			$parentAccountRow = $(this).closest('li');
			if( $parentAccountRow.hasClass('list-master') ) {
				if( $parentAccountRow.next('li.list-container').find('ul li:first').hasClass('account-delete') ) {
					$parentAccountRow.next('li.list-container').find('ul li:first').slideUp(function() {
						$(this).remove();
					});
					return true;
				}
			} else {
				if( $parentAccountRow.next('li').hasClass('account-delete') ) {
					$parentAccountRow.next('li').slideUp(function() {
						$(this).remove();
					});
					return true;
				}
			}

			if( $parentAccountRow.hasClass('list-master') ) {
				// Has child accounts.
				$deleteForm = $($('#accounts-chart-delete-children-template').html());
				$deleteForm.addClass('hidden');
				$parentAccountRow.next('li.list-container').find('ul:first').prepend($deleteForm);
				$deleteForm.slideDown();
			} else {
				// Show Form
				$deleteForm = $($('#accounts-chart-delete-form-template').html());
				$deleteForm.addClass('hidden');
				$deleteForm.attr('rel',$parentAccountRow.attr('rel'));
				$parentAccountRow.after($deleteForm);
				$deleteForm.slideDown(function() {
					var totalChildWidth = 35;
					$deleteForm.find('span').each(function() {
						totalChildWidth += $(this).outerWidth();
					});
					if( totalChildWidth > $deleteForm.innerWidth() ) {
						totalChildWidth = 35;
						$deleteForm.find('span:not(.account-delete-transfer)').each(function() {
							totalChildWidth += $(this).outerWidth();
						});
						$deleteForm.find('span.account-delete-transfer').width(parseInt($deleteForm.innerWidth()-totalChildWidth));
					}
				});
			}
		});

		$('#accounts-chart-accounts .account-actions .new').live('click',function (e) {
			e.preventDefault();

			$parentAccountRow = $(this).closest('li');
			if( $parentAccountRow.hasClass('list-master') ) {
				if( $parentAccountRow.next('li.list-container').find('ul li:first').hasClass('account-edit') ) {
					$parentAccountRow.next('li.list-container').find('ul li:first').slideUp(function() {
						$(this).remove();
					});
					return true;
				}
			} else {
				if( $parentAccountRow.next('li').hasClass('account-edit') ) {
					$parentAccountRow.next('li').slideUp(function() {
						$(this).remove();
					});
					return true;
				}
			}

			$newAccountRow = $($('#accounts-chart-edit-template').html());
			$newAccountRow.addClass('hidden');
			if( $parentAccountRow.hasClass('list-master') ) {
				// Insert into next li.list-container
				$parentAccountRow.next('li.list-container').find('ul:first').prepend($newAccountRow);
			} else {
				// After
				$parentAccountRow.after($newAccountRow);
			}

			// Set the parent account.
			$newAccountRow.find('select[name="parent_account_id"]').val($parentAccountRow.attr('rel'));
			// Set the Account Type Inheritance
			if( $parentAccountRow.find('input.account_type_id:first').length &&
				$parentAccountRow.find('input.account_type_id:first').val().length > 0 ) {
				$newAccountRow.find('select[name="account_type_id"]').val($parentAccountRow.find('input.account_type_id:first').val());
			} else {
				// Try to do it based on name.
				var name = $parentAccountRow.find('span.account-name').text().trim();
				var guessVal = false;
				$newAccountRow.find('select[name="account_type_id"] option').each(function() {
					if( ! guessVal &&
						$(this).html().trim().toLowerCase() == name.toLowerCase() ) {
						guessVal = $(this).val();
					}
					if( ! guessVal &&
						$(this).html().trim().toLowerCase() == name.toLowerCase().substring(0,name.length - 1) ) {
						guessVal = $(this).val();
					}
				});
				if( guessVal ) {
					$newAccountRow.find('select[name="account_type_id"]').val(guessVal);
				}
			}
			$newAccountRow.find('.account-edit-name').width(100);
			$newAccountRow.slideDown(function() {
				var totalChildWidth = 0;
				$newAccountRow.find('span:not(.account-edit-name)').each(function() {
					totalChildWidth += parseInt($(this).outerWidth());
				});
				$newAccountRow.find('.account-edit-name').width(parseInt($newAccountRow.innerWidth()-totalChildWidth-30));
				$newAccountRow.find('.account-edit-name input[type="text"]').focus();
			});
			// Adjust name entry width.
			
		});

		$('#accounts-chart-accounts .account-delete .cancel').live('click',function (e) {
			e.preventDefault();

			$deleteRow = $(this).closest('li');
			$deleteRow.slideUp(function() {
				$deleteRow.remove();
			});
		});

		var delete_account_id = '';
		var delete_transfer_account_id = '';
		function deleteAccount() {
			showPleaseWait();
			$.post(
				'/accounts/json/accountdelete',
				{
					account_id: delete_account_id,
					transfer_account_id: delete_transfer_account_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						window.location.reload();
					}
				},
				'json'
			);
		}
		
		$('#accounts-chart-accounts .account-delete .delete').live('click',function (e) {
			e.preventDefault();

			$selectTransfer = $(this).closest('li.account-delete').find('select option:selected');
			delete_account_id = $(this).closest('li.account-delete').attr('rel');
			delete_transfer_account_id = $selectTransfer.val();
			showConfirm("Are you sure you want to delete this account?  All transactions will be transfered to "+$selectTransfer.text()+".","Yes, Delete it.","No.",deleteAccount);
		});
		
		$('#accounts-chart-accounts .account-edit .cancel').live('click',function(e) {
			e.preventDefault();
			$editRow = $(this).closest('li');
			$editRow.slideUp(function() {
				if( $editRow.attr('rel') &&
					$editRow.attr('rel').length > 0 ) {
					// Show the previous row.
					$('#accounts-chart-accounts li[rel="'+$editRow.attr('rel')+'"]:not(.account-edit)').slideDown();
				}
				$editRow.remove();
			});
		});

		$('#accounts-chart-accounts .account-edit .save').live('click',function (e) {
			e.preventDefault();

			showPleaseWait();
			$editRow = $(this).closest('li');
			if( $editRow.attr('rel') &&
				$editRow.attr('rel').length ) {
				$.post(
					'/accounts/json/accountupdate',
					$editRow.find('input,select').serialize()+'&account_id='+$editRow.attr('rel'),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							// TODO - DO SOME FANCY INSERTION?
							// This seems to work nicely actually.
							window.location.reload();
						}
					},
					'json'
				);
			} else {
				$.post(
					'/accounts/json/accountcreate',
					$editRow.find('input,select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							// TODO - DO SOME FANCY INSERTION?
							// This seems to work nicely actually.
							window.location.reload();
						}
					},
					'json'
				);
			}
		});
		
		$('#accounts-chart-accounts .account-actions .edit').live('click',function(e) {
			e.preventDefault();
			$accountRow = $(this).closest('li');
			$editAccountRow = $($('#accounts-chart-edit-template').html());
			$editAccountRow.addClass('hidden');
			$editAccountRow.find('input[name="name"]').val($accountRow.find('input.name').val());
			$editAccountRow.find('select[name="account_type_id"]').val($accountRow.find('input.account_type_id').val());
			$editAccountRow.find('select[name="parent_account_id"]').val($accountRow.find('input.parent_account_id').val());
			$editAccountRow.find('select[name="terms"]').val($accountRow.find('input.terms').val());
			$editAccountRow.attr('rel',$accountRow.attr('rel'));
			$accountRow.after($editAccountRow);
			$accountRow.slideUp(function() {
				$editAccountRow.slideDown();
			})
		});

		/**
		 * VIEW
		 */
		
		var jumpLoadMore = false;
		var jumpMonth = false;

		function accountsViewJumpCancel() {
			jumpLoadMore = false;
			showPleaseWait('Finishing Last Query...');
		}

		function accountsViewJump() {
			if ( ! jumpLoadMore ) {
				rowElementsColorVisible($('#accounts-view-transactions'));
				hidePleaseWait();
				$scrollTo = $('#accounts-view-transactions > ul >li.account-transaction-month-'+jumpMonth+':last');
				if( $scrollTo.length == 0 ) {
					$scrollTo = $('#accounts-view-transactions > ul >li.account-transaction:last');
				}
				$('html,body').animate(
					{
						scrollTop: parseInt($scrollTo.offset().top - 100)
					},
					2000
				);
				return;
			}
			$.post(
				'/accounts/json/transactionsjumptomonth',
				{
					account_id: $('#accounts-view-fields-account_id').val(),
					last_transaction_id: $('#accounts-view-transactions  li.account-transaction:not(.static-row):last').attr('rel'),
					last_transaction_date: $('#accounts-view-transactions  li.account-transaction:not(.static-row):last span.account-transaction-date').text(),
					month: jumpMonth
				},
				function(data) {
					if( data.success != 1 ) {
						hidePleaseWait();
						showError(data.error);
					} else {
						var lastDate = false;
						for( index in data.data.transactions ) {
							$('#accounts-view-transactions > ul').append(data.data.transactions[index].html);
							lastDate = data.data.transactions[index].date;
						}
						if( data.data.transactions.length > 0 ) {
							showPleaseWait(
								'Loading... '+lastDate,
								'Cancel',
								accountsViewJumpCancel
							);
							setTimeout(function() {
								accountsViewJump();
							},150);
							return;
						}
						jumpLoadMore = false;
						rowElementsColorVisible($('#accounts-view-transactions'));
						hidePleaseWait();
						$scrollTo = $('#accounts-view-transactions > ul >li.account-transaction-month-'+jumpMonth+':last');
						if( $scrollTo.length == 0 ) {
							$scrollTo = $('#accounts-view-transactions > ul >li.account-transaction:last');
						}
						$('html,body').animate(
							{
								scrollTop: parseInt($scrollTo.offset().top - 100)
							},
							2000
						);
					}
				},
				'json'
			);
		}

		$('#accounts-view-jump').change(function() {
			jumpMonth = $(this).val();
			if( $('.account-transaction-month-'+jumpMonth+':last').length == 1 ) {
				// Scroll
				$('html,body').animate(
					{
						scrollTop: parseInt($('.account-transaction-month-'+jumpMonth+':last').offset().top - 100)
					},
					2000
				);
			} else {
				showPleaseWait(
					'Loading...',
					'Cancel',
					accountsViewJumpCancel
				);
				jumpLoadMore = true;
				setTimeout(function() {
					accountsViewJump();
				},150);
			}
		});


		var loadMoreOffset = 2000;
		if( $('#accounts-view-transactions').length > 0  &&
			$('#accounts-import-upload').length == 0 &&
			$('#accounts-import-classify').length == 0 &&
			$('#accounts-import-save').length == 0 ) {
			$(window).scroll(function () { 
				if( ( $(window).height() + $(window).scrollTop() + loadMoreOffset ) >= $('#accounts-view-transactions').height() ) {
					if( $('#accounts-view-loadtransactions').is(':visible') ||
						$('#accounts-view-endtransactions').is(':visible') ) {
						// Do nothing - we're already loading...
					} else {
						$lastTransaction = $('#accounts-view-transactions li.account-transaction:not(.static-row):last');
						var last_transaction_id = $lastTransaction.attr('rel');
						if( ! last_transaction_id ||
							last_transaction_id == undefined ) {
							return;
						}
						var last_transaction_date = $lastTransaction.find('span.account-transaction-date').text();
						$('#accounts-view-loadtransactions').show();
						$('#accounts-view-loadtransactions').find('.spinme').spin();
						$.post(
							'/accounts/json/transactionsloadmore',
							{
								account_id: $('#accounts-view-fields-account_id').val(),
								last_transaction_id: last_transaction_id,
								last_transaction_date: last_transaction_date
							},
							function(data) {
								if( data.success != 1 ) {
									showError("Error loading more transactions: "+data.error);
								} else {
									if( data.data.transactions.length == 0 ) {
										$('#accounts-view-endtransactions').show();
										$('#accounts-view-loadtransactions').hide();
									} else {
										for( index in data.data.transactions ) {
											$('#accounts-view-transactions > ul').append(data.data.transactions[index].html);
										}
										$('#accounts-view-loadtransactions').hide();
										rowElementsColorVisible($('#accounts-view-transactions'));
										// Load more if screen still isn't scrolled.
										$(window).trigger('scroll');
									}
								}
							},
							'json'
						);
					}
				}
			});
		}

		// Show / hide split on current transactions
		$('#accounts-view-transactions li:not(.new,.import,.edit) span.account-transaction-split a').live('click',function (e) {
			e.preventDefault();

			$transaction = $(this).closest('.account-transaction');
			$id = $transaction.attr('rel');
			$transactionSplit = $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]');

			if( $transactionSplit.is(':visible') ) {
				$transactionSplit.slideUp();
			} else {
				$transactionSplit.slideDown();
			}
		});

		$('#accounts-view-add-transaction').click(function (e) {
			e.preventDefault();

			if( $('#accounts-view-transactions li.new').length > 0 ) {
				// Toggle show/hide
				if( $('#accounts-view-transactions li.new:first').is(':visible') ) {
					$('#accounts-view-transactions li.new').slideUp(function() {
						$(this).remove();
					});
				} else {
					$('#accounts-view-transactions li.new').slideDown();
				}
			} else {
				$newTransaction = $($('#accounts-view-add-transaction-template').html()).addClass('hidden');
				$('#accounts-view-transactions li:first').after($newTransaction);
				$newTransaction.find('input').ezpz_hint();
				$newTransaction.slideDown();
			}
		});

		$('#accounts-view-transactions li.new span.account-transaction-split a').live('click',function (e) {
			e.preventDefault();
		 	
			$accountTransaction = $(this).closest('li.account-transaction');

		 	$accountTransaction.find('span.account-transaction-transfer').html(
		 		$accountTransaction.find('span.account-transaction-transfer select option[value="'+$('#accounts-view-fields-account_id').val()+'"]').html().split('&nbsp;').join('')
		 	).removeClass('text-center').addClass('text-left');
			$(this).closest('span.account-transaction-split').html('&nbsp;');
		 	
			$splitContainer = $accountTransaction.next('li.list-container');
			$newTransactionSplit = $($('#accounts-view-add-split-template').html()).addClass('hidden');
		 	$anotherNewTransactionSplit = $($('#accounts-view-add-split-template').html()).addClass('hidden');
		 	
		 	$splitContainer.find('li.account-transaction.actions').before($newTransactionSplit);
		 	$splitContainer.find('li.account-transaction.actions').before($anotherNewTransactionSplit);

		 	$newTransactionSplit.slideDown();
		 	$anotherNewTransactionSplit.slideDown();
		 	
		 	return false;
		 });

		$('#accounts-view-transactions li.static-row.account-transaction.new:not(.actions,.import) span.account-transaction-transfer select').live('change',function() {
			if( $(this).closest('li.static-row').next('li.static-row').hasClass('actions') ) {
				$newTransactionSplit = $($('#accounts-view-add-split-template').html()).addClass('hidden');
				
				$(this).closest('li.static-row').next('li.actions').before($newTransactionSplit);
				
				$newTransactionSplit.slideDown();
			}
		});

		$('#accounts-view-transactions a.accounts-view-add-transaction-cancel').live('click',function (e) {
			e.preventDefault();
			$('#accounts-view-transactions li.new').each(function() {
				$(this).slideUp(function() {
					$(this).remove();
				});
			});
		});

		$('#accounts-view-transactions li.split-transaction.new a.accounts-view-add-transaction-save').live('click',function (e) {
			e.preventDefault();

		 	showPleaseWait();
		 	// Add iterator to all splits if they exist.
		 	var i = 0;
		 	$('#accounts-view-transactions li.split-transaction.new li.static-row').each(function() {
		 		$(this).find('input:not(.indexed),select:not(.indexed)').each(function() {
		 			$(this).attr('name',$(this).attr('name')+'-'+i);
		 			$(this).addClass('indexed');
		 		});
		 		i++;
		 	});
		 	$.post(
		 		'/accounts/json/transactioncreate',
		 		$('#accounts-view-transactions li.new input,#accounts-view-transactions li.new select').serialize(),
		 		function(data) {
		 			if( data.success != 1 ) {
		 				hidePleaseWait();
		 				showError(data.error);
		 			} else {
		 				// Remove inputs and add new transaction.
		 				$('#accounts-view-transactions li.new').each(function() {
		 					$(this).remove();
		 				});
		 				$newTransaction = $(data.data.transaction.html);
		 				$newTransaction.find('li.account-transaction').addClass('hidden');
		 				$('#accounts-view-transactions > ul > li:first').after($newTransaction);
		 				hidePleaseWait();
		 				$newTransaction.find('li.account-transaction').slideDown();
		 				rowElementsColorVisible($('#accounts-view-transactions'));
		 			}
		 		},
		 		'json'
		 	);
		 });
		
		/**
		 * View and Import
		 */
		
		$('span.account-transaction-credit input:not(.ezpz-hint), span.account-transaction-debit input:not(.ezpz-hint)').live('change',function () {
			$(this).val(convertCurrencyToNumber($(this).val()).toFixed(2));
			if( $(this).closest('span').hasClass('account-transaction-credit') ) {
				$(this).closest('li').find('.account-transaction-debit input:not(.ezpz-hint)').val('').focus().blur();
			} else {
				$(this).closest('li').find('.account-transaction-credit input:not(.ezpz-hint)').val('').focus().blur();
			}
		});

		/**
		 * View -> Delete
		 */
		$('#accounts-view-transactions li.account-transaction[rel] span.account-transaction-delete a').live('click',function (e) {
			e.preventDefault();

			// Load transaction to edit.
			$transaction = $(this).closest('li.account-transaction');
			
			$deleteTransaction = $($('#accounts-view-delete-transaction-template').html());
			$deleteTransaction.addClass('hidden');
			$deleteTransaction.find('input[name="transaction-id"]').val($transaction.attr('rel'));
			$transaction.after($deleteTransaction);
			$deleteTransaction.slideDown();
		});

		$('#accounts-view-transactions li.account-transaction a.accounts-view-delete-transaction-cancel').live('click',function (e) {
			e.preventDefault();

			$deleteTransaction = $(this).closest('li.account-transaction');
			$deleteTransactionContainer = $deleteTransaction.closest('.list-container');
			$id = $deleteTransaction.find('input[name="transaction-id"]').val();
			
			$deleteTransactionContainer.slideUp(function () {
				$deleteTransactionContainer.remove();
			});
		});

		$('#accounts-view-transactions li.account-transaction a.accounts-view-delete-transaction-save').live('click',function (e) {
			e.preventDefault();

			$deleteTransaction = $(this).closest('li.account-transaction');
			$deleteTransactionContainer = $deleteTransaction.closest('.list-container');
			$id = $deleteTransaction.find('input[name="transaction-id"]').val();

			$transaction = $('#accounts-view-transactions li.account-transaction:not(.edit)[rel="'+$id+'"]');
			$transactionSplit = $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]');

			if( confirm("Are you sure you want to delete this transaction?") ) {
				showPleaseWait();
				$.post(
					'/accounts/json/transactiondelete',
					{
						transaction_id: $id
					},
					function(data) {
						hidePleaseWait();
						if( data.success ) {
							$transaction.slideUp(function () {
								$transaction.remove();
								rowElementsColorVisible($('#accounts-view-transactions'));
							});
							if( $transactionSplit ) {
								$transactionSplit.slideUp(function () {
									$transactionSplit.remove();

								});
							}
							$deleteTransactionContainer.slideUp(function() {
								$deleteTransactionContainer.remove();
							});
						} else {
							showError(data.error);
						}
					},
					'json'
				);
			}
		});

		/**
		 * View -> Edit
		 */
		$('#accounts-view-transactions li.account-transaction[rel] span.account-transaction-edit a').live('click',function (e) {
			e.preventDefault();

			if( $(this).attr('href') != "#" ) {
				if( confirm("This transaction is tied to a "+$(this).attr('rel')+". Click 'OK' to go to its page to perform an edit.") ) {
					window.location.href = $(this).attr('href');
				}
			} else {
				// Load transaction to edit.
				$transaction = $(this).closest('li.account-transaction');
				$id = $transaction.attr('rel');
				$transactionSplit = false;

				$editTransaction = $($('#accounts-view-edit-transaction-template').html());
				$editTransaction.addClass('hidden');
				$editTransaction.find('input[name="transaction-id"]').val($transaction.attr('rel'));
				$editTransaction.find('input[name="transaction-date"]').val($transaction.find('.account-transaction-date').text().trim());
				$editTransaction.find('input[name="transaction-number"]').val($transaction.find('.account-transaction-number').text().trim());
				$editTransaction.find('input[name="transaction-description"]').val($transaction.find('.account-transaction-description').text().trim());
				
				$editTransaction.find('input[name="transaction-credit"]').val(convertCurrencyToNumber($transaction.find('.account-transaction-credit').text().trim()));
				$editTransaction.find('input[name="transaction-debit"]').val(convertCurrencyToNumber($transaction.find('.account-transaction-debit').text().trim()));
				
				if( $editTransaction.find('input[name="transaction-credit"]').val() == "0" ) {
					$editTransaction.find('input[name="transaction-credit"]').val('');
				}
				if( $editTransaction.find('input[name="transaction-debit"]').val() == "0" ) {
					$editTransaction.find('input[name="transaction-debit"]').val('');
				}

				$editTransactionSplit = $($('#accounts-view-edit-transaction-container-template').html());

				if( $transaction.find('.account-transaction-transfer').attr('rel') ) {
					// Transfer
					$editTransaction.find('select[name="transaction-transfer"]').val($transaction.find('.account-transaction-transfer').attr('rel'));
				} else {
					// Split
					$editTransaction.find('span.account-transaction-split').html('&nbsp;');
					$editTransaction.find('span.account-transaction-transfer').html(
				 		$editTransaction.find('span.account-transaction-transfer select option[value="'+$('#accounts-view-fields-account_id').val()+'"]').html().split('&nbsp;').join('')
				 	).removeClass('text-center').addClass('text-left');

					$transactionSplit = $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]');

					$transactionSplit.find('li.account-transaction').each(function() {
						$currentSplit = $(this);
						$newEditSplit = $($('#accounts-view-edit-split-template').html());
						$newEditSplit.find('select[name="transaction-split-transfer"]').val($currentSplit.find('.account-transaction-transfer').attr('rel'));

						$newEditSplit.find('input[name="transaction-split-credit"]').val(convertCurrencyToNumber($currentSplit.find('.account-transaction-credit').text().trim()));
						$newEditSplit.find('input[name="transaction-split-debit"]').val(convertCurrencyToNumber($currentSplit.find('.account-transaction-debit').text().trim()));
						
						if( $newEditSplit.find('input[name="transaction-split-credit"]').val() == "0" ) {
							$newEditSplit.find('input[name="transaction-split-credit"]').val('');
						}
						if( $newEditSplit.find('input[name="transaction-split-debit"]').val() == "0" ) {
							$newEditSplit.find('input[name="transaction-split-debit"]').val('');
						}
						
						$editTransactionSplit.find('li.account-transaction.actions').before($newEditSplit);
					});

					$newEditSplit = $($('#accounts-view-edit-split-template').html());
					$editTransactionSplit.find('li.account-transaction.actions').before($newEditSplit);
				}
				$transaction.before($editTransaction);
				$editTransaction.after($editTransactionSplit);
				$transaction.slideUp(function() {
					$editTransaction.slideDown();
					$editTransactionSplit.slideDown();
				});
				if( $transactionSplit ) {
					$transactionSplit.slideUp();
				}
			}
			rowElementsColorVisible($('#accounts-view-transactions'));
		});

		$('#accounts-view-transactions li.account-transaction a.accounts-view-edit-transaction-cancel').live('click',function (e) {
			e.preventDefault();

			$editTransactionSplit = $(this).closest('li.list-container');
			$editTransaction = $editTransactionSplit.prev('li.account-transaction');
			$id = $editTransaction.find('input[name="transaction-id"]').val();
			
			$transaction = $('#accounts-view-transactions li.account-transaction:not(.edit)[rel="'+$id+'"]');
			$transactionSplit = false;
			if( $transaction.next('li.account-transaction').hasClass('transaction-split') ) {
				$transactionSplit = $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]');
			}

			$editTransaction.slideUp(function() {
				$editTransactionSplit.slideUp(function() {
					$editTransaction.remove();
					$editTransactionSplit.remove();
					$transaction.slideDown(function() {
						rowElementsColorVisible($('#accounts-view-transactions'));
					});
					if( $transactionSplit ) {
						$transactionSplit.slideDown();
					}
				});
			});
		});

		$('#accounts-view-transactions li.account-transaction a.accounts-view-edit-transaction-delete').live('click',function (e) {
			e.preventDefault();

			$editTransactionSplit = $(this).closest('li.list-container');
			$editTransaction = $editTransactionSplit.prev('li.account-transaction');
			$id = $editTransaction.find('input[name="transaction-id"]').val();

			$transaction = $('#accounts-view-transactions li.account-transaction:not(.edit)[rel="'+$id+'"]');
			$transactionSplit = false;
			if( $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]').length ) {
				$transactionSplit = $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]');
			}
			
			if( confirm("Are you sure you want to delete this transaction?") ) {
				showPleaseWait();
				$.post(
					'/accounts/json/transactiondelete',
					{
						transaction_id: $id
					},
					function(data) {
						hidePleaseWait();
						if( data.success ) {
							$editTransaction.slideUp(function() {
								$editTransaction.remove();
								$editTransactionSplit.slideUp(function() {
									$editTransactionSplit.remove();
									rowElementsColorVisible($('#accounts-view-transactions'));
								});
							});
							$transaction.remove();
							$transactionSplit.remove();
						} else {
							showError(data.error);
						}
					},
					'json'
				);
			}
		});
		
		$('#accounts-view-transactions li.account-transaction a.accounts-view-edit-transaction-save').live('click',function (e) {
			e.preventDefault();
			$editTransactionSplit = $(this).closest('li.list-container');
			$editTransaction = $editTransactionSplit.prev('li.account-transaction');
			$id = $editTransaction.find('input[name="transaction-id"]').val();

			$transaction = $('#accounts-view-transactions li.account-transaction:not(.edit)[rel="'+$id+'"]');
			$transactionSplit = false;
			if( $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]').length ) {
				$transactionSplit = $('#accounts-view-transactions li.split-transaction:not(.edit)[rel="'+$id+'"]');
			}

			showPleaseWait();

			var i = 0;
		 	$editTransactionSplit.find('li.static-row').each(function() {
		 		$(this).find('input:not(.indexed),select:not(.indexed)').each(function() {
		 			$(this).attr('name',$(this).attr('name')+'-'+i);
		 			$(this).addClass('indexed');
		 		});
		 		i++;
		 	});
		 	$.post(
		 		'/accounts/json/transactionupdate',
		 		$editTransaction.add($editTransactionSplit).find('input,select').serialize()+'&transaction_id='+$id,
		 		function(data) {
		 			hidePleaseWait();
		 			if( data.success != 1 ) {
		 				showError(data.error);
		 			} else {
		 				$newTransaction = $(data.data.transaction.html);
		 				$newTransaction.find('li.account-transaction:first').addClass('hidden');

		 				$editTransaction.slideUp(function() {
		 					$editTransactionSplit.remove();
		 				});
		 				$editTransactionSplit.slideUp(function() {
		 					$editTransactionSplit.remove();
		 				});
		 				$transaction.before($newTransaction);
		 				$transaction.remove();
		 				if( $transactionSplit ) {
		 					$transactionSplit.remove();
		 				}
		 				
		 				$newTransaction.find('li.account-transaction').slideDown(function() {
		 					rowElementsColorVisible($('#accounts-view-transactions'));
		 				});
		 			}
		 		},
		 		'json'
		 	);

		});

		$('#accounts-view-transactions li.edit span.account-transaction-split a').live('click',function(e) {
			e.preventDefault();
		 	
			$accountTransaction = $(this).closest('li.account-transaction');
			$id = $accountTransaction.attr('rel');

		 	$accountTransaction.find('span.account-transaction-transfer').html(
		 		$accountTransaction.find('span.account-transaction-transfer select option[value="'+$('#accounts-view-fields-account_id').val()+'"]').html().split('&nbsp;').join('')
		 	).removeClass('text-center').addClass('text-left');
			$(this).closest('span.account-transaction-split').html('&nbsp;');

			$splitContainer = $accountTransaction.next('li.list-container');
			$newTransactionSplit = $($('#accounts-view-edit-split-template').html()).addClass('hidden');
		 	$anotherNewTransactionSplit = $($('#accounts-view-edit-split-template').html()).addClass('hidden');
		 	
		 	$splitContainer.find('li.account-transaction.actions').before($newTransactionSplit);
		 	$splitContainer.find('li.account-transaction.actions').before($anotherNewTransactionSplit);

		 	$newTransactionSplit.slideDown();
		 	$anotherNewTransactionSplit.slideDown();
		 	
		 	return false;
		 });

		$('#accounts-view-transactions li.static-row.account-transaction.edit:not(.actions,.import) span.account-transaction-transfer select').live('change',function() {
			if( $(this).closest('li.static-row').next('li.static-row').hasClass('actions') ) {
				$newTransactionSplit = $($('#accounts-view-edit-split-template').html()).addClass('hidden');
				
				$(this).closest('li.static-row').next('li.actions').before($newTransactionSplit);
				
				$newTransactionSplit.slideDown();
			}
		});
			

		/**
		 * IMPORT
		 */
		
		$('#accounts-import-upload-button').click(function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		$('#accounts-import-classify-import').click(function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		if( $('#accounts-import-save').length > 0 ) {

			$('#accounts-view-transactions li.account-transaction').each(function() {
				if( $(this).attr('rel') && $(this).attr('rel').length > 0 ) {
					var transfer_val = $('input[name="import-transaction-'+$(this).attr('rel')+'-transfer_account"]').val();
					if( transfer_val &&
						transfer_val.length ) {
						$(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').val(transfer_val);
					}
				}
			});

			colorAccountTransactionImportRows($('#accounts-view-transactions'));

			$('#accounts-view-transactions li.account-transaction select').live('change',function() {
				colorAccountTransactionImportRows($('#accounts-view-transactions'));
			});
		}

		// Yes - this really needs to be defined here.
		var import_row_index = 0;

		$('#accounts-import-save').click(function() {
			var valid = true;
			$('#accounts-view-transactions li.account-transaction.import:not(.static-row)').each(function() {
				if( $(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').length &&
					$(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').val().length == 0 ) {
					valid = false;
				} else if( ! $(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').length ) {
					$container = $(this).next('li');
					if( ! $container.hasClass('list-container') ) {
						// Something went terribly wrong.
						alert("Something has gone terribly wrong!");
						valid = false;
					}
					if( $container.find('select[value!=""]').length == 0 ) {
						valid = false;
					} else {
						$container.find('select').each(function() {
							if( $(this).val().length != 0 &&
								$(this).closest('li').find('input[value!=""]:not(.ezpz-hint)').length == 0 ) {
								valid = false;
							}
						});
					}
				}
			});

			if( ! valid ) {
				showError("One or more of the transactions requires additional input before being saved.");
				return;
			}

			showPleaseWait();
			
			// Add index values to each input.
			$('#accounts-view-transactions li.split-transaction.new li.static-row:not(.indexed)').each(function() {
		 		$(this).find('input,select').each(function() {
		 			$(this).attr('name',$(this).attr('name')+'-'+import_row_index);
		 		});
		 		$(this).find('input.split-key').attr('name','split-key-'+import_row_index);
		 		$(this).addClass('indexed');
		 		import_row_index++;
		 	});

			// Serialize into form field.
			$importdata = $('#accounts-view-transactions input:not(.ezpz-hint),#accounts-view-transactions select:not(.ezpz-hint)').serializeObject();

			$('#accounts-import-save-form input[name="importdata"]').val(JSON.stringify($importdata));
			
			$.post(
				'/accounts/json/importvalidatetransactions',
				$('#accounts-import-save-form input').serialize(),
				function(data) {
					if( ! data.success ) {
						hidePleaseWait();
						showError(data.error);
					} else {
						$('#accounts-import-save-form').submit();
					}
				},
				'json'
			);

		});

		$('#accounts-view-transactions li.import span.account-transaction-split a').live('click',function() {
			$li = $(this).closest('li.import');
			
			$li.find('span.account-transaction-transfer').html(
		 		$li.find('span.account-transaction-transfer select option[value="'+$('#accounts-view-fields-account_id').val()+'"]').html().split('&nbsp;').join('')
		 	).removeClass('text-center').addClass('text-left');

			var hash = $li.attr('rel');
			$(this).closest('span.account-transaction-split').html('&nbsp;');
			$newTransactionSplitContainer = $($('#accounts-view-add-splitcontainer-template').html()).addClass('hidden');
			$newTransactionSplitContainer.attr('rel',hash);
			$newTransactionSplit = $($('#accounts-view-add-split-template').html());
			$newTransactionSplit.find('input,select').each(function() {
				$(this).attr('name',$(this).attr('name').replace('--','-'+hash+'-'));
			});
			$newTransactionSplit.find('input.split-key').each(function() {
				$(this).val($(this).val().replace('--','-'+hash+'-'));
			});
			$anotherNewTransactionSplit = $($('#accounts-view-add-split-template').html());
			$anotherNewTransactionSplit.find('input,select').each(function() {
				$(this).attr('name',$(this).attr('name').replace('--','-'+hash+'-'));
			});
			$anotherNewTransactionSplit.find('input.split-key').each(function() {
				$(this).val($(this).val().replace('--','-'+hash+'-'));
			});
			$newTransactionSplitContainer.find('ul').append($newTransactionSplit);
			$newTransactionSplitContainer.find('ul').append($anotherNewTransactionSplit);
			$newTransactionSplit.find('input').ezpz_hint();
			$anotherNewTransactionSplit.find('input').ezpz_hint();
			$li.after($newTransactionSplitContainer);
			$newTransactionSplitContainer.slideDown();
			colorAccountTransactionImportRows($('#accounts-view-transactions'));
			return false;
		});
		
		$('#accounts-view-transactions li.static-row.account-transaction.new:not(.import) span.account-transaction-transfer select').live('change',function() {
			if( $(this).closest('li.static-row').is(':last-child') ) {
				$newTransactionSplit = $($('#accounts-view-add-split-template').html()).addClass('hidden');
				$(this).closest('li.list-container').append($newTransactionSplit);
				$newTransactionSplit.find('input').ezpz_hint();
				$newTransactionSplit.slideDown();
			}
		});

		$('#accounts-view-transactions li.static-row.account-transaction.new.import span.account-transaction-transfer select').live('change',function() {
			if( $(this).closest('li.static-row').is(':last-child') ) {
				var hash = $(this).closest('li.list-container').attr('rel');
				$newTransactionSplit = $($('#accounts-view-add-split-template').html()).addClass('hidden');
				$newTransactionSplit.find('input,select').each(function() {
					$(this).attr('name',$(this).attr('name').replace('--','-'+hash+'-'));
				});
				$newTransactionSplit.find('input.split-key').each(function() {
					$(this).val($(this).val().replace('--','-'+hash+'-'));
				});
				$(this).closest('li.list-container').append($newTransactionSplit);
				$newTransactionSplit.find('input').ezpz_hint();
				$newTransactionSplit.slideDown();
			}
			colorAccountTransactionImportRows($('#accounts-view-transactions'));
		});

		/**
		 * RECONCILE ACCOUNT
		 */
		if( $('#accounts-reconcile-status').length > 0 ) {
			accountReconcileUpdateTotals();

			$('#accounts-reconcile-status').attr('rel',120);
			$(window).scroll(function() {
				var reconcileStatusOffset = parseInt($('#accounts-reconcile-status').attr('rel'));
				var reconcileStatusInitOffset = reconcileStatusOffset - 30;
				if( $(this).scrollTop() >= reconcileStatusInitOffset ) {
					// $('#accounts-reconcile-status').css('top',($(this).scrollTop()-reconcileStatusOffset)+'px');
					$('#accounts-reconcile-status').stop().animate({top: ($(this).scrollTop()-reconcileStatusOffset)+'px'}, 300, 'swing');
				} else {
					$('#accounts-reconcile-status').stop().animate({top: '120px'}, 300, 'swing');
					// $('#accounts-reconcile-status').css('top','120px');
				}

				if( $(this).scrollTop() >= ( parseInt($('a[name="funds-out"]').position().top) ) ) {
					$("#accounts-reconcile-status-jump-in").show();
					$("#accounts-reconcile-status-jump-out").hide();
				} else {
					$("#accounts-reconcile-status-jump-out").show();
					$("#accounts-reconcile-status-jump-in").hide();
				}
			});

		}

		$('.accounts-reconcile-form-actions a.check-all').live('click',function(e) {
			e.preventDefault();
			$(this).closest('.reconcile-form').find('.account-reconcile-transaction[rel]').each(function() {
				$(this).addClass('selected');
				$(this).find('input[type="checkbox"]').attr('checked','checked');
				checkboxUpdate($(this).find('input[type="checkbox"]'));
			});
			$(this).closest('.reconcile-form').find('.account-reconcile-transaction').each(function() {
				$(this).find('input[type="checkbox"]').attr('checked','checked');
				checkboxUpdate($(this).find('input[type="checkbox"]'));
			});
			accountReconcileUpdateTotals();
		});

		$('.accounts-reconcile-form-actions a.uncheck-all').live('click',function(e) {
			e.preventDefault();
			$(this).closest('.reconcile-form').find('.account-reconcile-transaction[rel]').each(function() {
				$(this).removeClass('selected');
				$(this).find('input[type="checkbox"]').attr('checked',false);
				checkboxUpdate($(this).find('input[type="checkbox"]'));
			});
			$(this).closest('.reconcile-form').find('.account-reconcile-transaction').each(function() {
				$(this).find('input[type="checkbox"]').attr('checked',false);
				checkboxUpdate($(this).find('input[type="checkbox"]'));
			});
			accountReconcileUpdateTotals();
		});

		$('#accounts-reconcile-form-in-checkbox, #accounts-reconcile-form-out-checkbox').live('click', function (e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			var checkbox = $(this).find('input[type="checkbox"]');
			if( checkbox.is(':checked') ) {
				checkbox.attr('checked',false);
				checkboxUpdate(checkbox);
				$(this).closest('.reconcile-form').find('.uncheck-all').click();
			} else {
				checkbox.attr('checked','checked');
				checkboxUpdate(checkbox);
				$(this).closest('.reconcile-form').find('.check-all').click();
			}
		});
		
		$('#accounts-reconcile-prep-date,#accounts-reconcile-prep-balance_start,#accounts-reconcile-prep-balance_end').live('change',function() {
			accountReconcileUpdateTotals();
		});

		$('#accounts-reconcile-prep-date').datepicker({ dateFormat: "yy-mm-dd" });	

		// Start Process
		$('#accounts-reconcile-prep-start').live('click',function(e) {
			e.preventDefault();
			if( ! $('#accounts-reconcile-prep-date').val().length ) {
				showError("Please enter a statement date.");
			} else {
				$('#accounts-reconcile-prep').slideUp(function() {
					$('#accounts-reconcile-previous').hide();
					$('#accounts-reconcile-form').fadeIn();
					$('#accounts-reconcile-status').fadeIn();
				});
			}
		});

		// Checkbox Listeners.
		$('.account-reconcile-transaction-include .checkbox.manual').live('click',function() {
			var checkbox = $(this).find(':checkbox');
			if( checkbox.is(':disabled') ) {
				return;
			}
			if( checkbox.is(':checked') ) {
				checkbox.prop('checked',false);
				$(this).closest('.account-reconcile-transaction').removeClass('selected');
			} else {
				checkbox.prop('checked',true);
				$(this).closest('.account-reconcile-transaction').addClass('selected');
			}
			checkboxUpdate(checkbox);
			accountReconcileUpdateTotals();
		});

		$('.account-reconcile-transaction[rel] .account-reconcile-transaction-amount, .account-reconcile-transaction[rel] .account-reconcile-transaction-description').live('click',function() {
			if( ! $(this).closest('.account-reconcile-transaction').hasClass('selected') ) {
				var checkbox = $(this).closest('.account-reconcile-transaction').find(':checkbox');
				if( checkbox.is(':disabled') ) {
					return;
				}
				if( checkbox.is(':checked') ) {
					checkbox.prop('checked',false);
					$(this).closest('.account-reconcile-transaction').removeClass('selected');
				} else {
					checkbox.prop('checked',true);
					$(this).closest('.account-reconcile-transaction').addClass('selected');
				}
				checkboxUpdate(checkbox);
				accountReconcileUpdateTotals();
			}
		});

		// Save
		$('#accounts-reconcile-status-save').live('click',function(e) {
			e.preventDefault();
			if( monetaryRound($(this).closest('.form').find('input[name="balance_difference"]').attr('rel')) != 0.00 ) {
				return showError("You must have a reconciled balance equal to the statement balance before saving.");
			}
			
			showPleaseWait();
			$.post(
				'/accounts/json/reconcilevalidate',
				$('#accounts-reconcile-status input,#accounts-reconcile-form input').serialize(),
				function(data) {
					if( data.success != 1 ) {
						hidePleaseWait();
						showError(data.error);
					} else {
						$('#accounts-reconcile-status-save').closest('form').submit();
					}
				},
				'json'
			);
		});

		// Delete
		$('#accounts-reconcile-delete').live('click', function(e) {
			e.preventDefault();
			showPleaseWait();
			$('#accounts-reconcile-delete-form').submit();
		});

		/* 
		// Removed from UI
		var delete_transaction_id = '';
		function deleteTransaction() {
			showPleaseWait();
			$.post(
				'/accounts/json/transactiondelete',
				{
					transaction_id: delete_transaction_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('.account-reconcile-transaction[rel="'+delete_transaction_id+'"]').slideUp(function() {
							$(this).remove();
							$('.row-elements-alternating-colors').each(function() {
								rowElementsColorVisible($(this));
							});
						});
					}
				},
				'json'
			);
		}
		
		// Delete Transaction
		$('.account-reconcile-transaction-delete a').live('click',function(e) {
			e.preventDefault();
			if( $(this).closest('.account-reconcile-transaction').attr('rel') &&
				$(this).closest('.account-reconcile-transaction').attr('rel').length ) {
				delete_transaction_id = $(this).closest('.account-reconcile-transaction').attr('rel');
				showConfirm("Are you sure you want to delete this transaction?.","Yes, Delete it.","No.",deleteTransaction);
			}
		});
		*/
		
		// Account Reconcile Add Transactions
		$('.accounts-reconcile-form-actions a.add-transaction').live('click', function (e) {
			e.preventDefault();
			$target = $(this).closest('.reconcile-form').find('div.row-elements ul');
			
			// Check if a transaction form is already there - if so remove it.
			if( $target.find('li.account-reconcile-transaction.new').length > 0 ) {
				$target.find('li.account-reconcile-transaction.new').slideUp(function () {
					$(this).remove();
				});
			} else {
				// Else create one
				$newTransaction = $($('#accounts-reconcile-new-transaction-template').html());
				$newTransaction.addClass('hidden');
				$target.find('li:first').after($newTransaction);
				$newTransaction.slideDown();
			}
		});

		$('li.account-reconcile-transaction.new .account-reconcile-transaction-credit input').live('change', function(e) {
			if( $(this).val() && 
				$(this).val().length ) {
				$(this).closest('.account-reconcile-transaction').find('.account-reconcile-transaction-debit input').val('');
			}
		});

		$('li.account-reconcile-transaction.new .account-reconcile-transaction-debit input').live('change', function(e) {
			if( $(this).val() && 
				$(this).val().length ) {
				$(this).closest('.account-reconcile-transaction').find('.account-reconcile-transaction-credit input').val('');
			}
		});

		$('li.account-reconcile-transaction.new span.account-reconcile-transaction-split a').live('click', function (e) {
			e.preventDefault();
		 	
			$accountTransaction = $(this).closest('li.account-reconcile-transaction');
			$accountTransactionActions = $accountTransaction.next('.account-reconcile-transaction-actions');
			var account_id = $accountTransaction.find('input[name="transaction-account-id"]').val();
		 	$accountTransaction.find('span.account-reconcile-transaction-transfer').html(
		 		$accountTransaction.find('span.account-reconcile-transaction-transfer select option[value="'+account_id+'"]').html().split('&nbsp;').join('')
		 	).removeClass('text-center').addClass('text-left').css('text-indent','3px').css('padding-top','1px');
			$accountTransaction.find('span.account-reconcile-transaction-split').html('&nbsp;');
		 	
			$accountTransactionSplit1 = $($('#accounts-reconcile-new-split-template').html());
			$accountTransactionSplit1.addClass('hidden');
			$accountTransactionSplit2 = $($('#accounts-reconcile-new-split-template').html());
			$accountTransactionSplit2.addClass('hidden');

			$accountTransactionActions.before($accountTransactionSplit1);
			$accountTransactionActions.before($accountTransactionSplit2);
			
		 	$accountTransactionSplit1.slideDown();
		 	$accountTransactionSplit2.slideDown();
		 	
		 	return false;
		});

		$('li.account-reconcile-transaction.new.split span.account-reconcile-transaction-transfer select').live('change', function (e) {
			if( $(this).closest('li.account-reconcile-transaction').next('li.account-reconcile-transaction').hasClass('account-reconcile-transaction-actions') ) {
				$accountTransactionActions = $(this).closest('li.account-reconcile-transaction').next('li.account-reconcile-transaction');
				$accountTransactionSplit1 = $($('#accounts-reconcile-new-split-template').html());
				$accountTransactionSplit1.addClass('hidden');
				$accountTransactionActions.before($accountTransactionSplit1);
				$accountTransactionSplit1.slideDown();
			}
		});

		$('a.account-reconcile-transaction-new-cancel').live('click', function (e) {
			e.preventDefault();
			$target = $(this).closest('.reconcile-form').find('div.row-elements ul');
			$target.find('li.account-reconcile-transaction.new').slideUp(function () {
				$(this).remove();
			});
		});

		$('a.account-reconcile-transaction-new-save').live('click', function (e) {
			e.preventDefault();
			$target = $(this).closest('.reconcile-form').find('div.row-elements ul');
			
			e.preventDefault();

		 	showPleaseWait();
		 	// Add iterator to all splits if they exist.
		 	var i = 0;
		 	$target.find('li.account-reconcile-transaction.new.split').each(function() {
		 		$(this).find('input:not(.indexed),select:not(.indexed)').each(function() {
		 			$(this).attr('name',$(this).attr('name')+'-'+i);
		 			$(this).addClass('indexed');
		 		});
		 		i++;
		 	});
		 	
		 	$.post(
		 		'/accounts/json/transactioncreate',
		 		$target.find('li.account-reconcile-transaction.new input,li.account-reconcile-transaction.new select').serialize(),
		 		function(data) {
		 			if( data.success != 1 ) {
		 				hidePleaseWait();
		 				showError(data.error);
		 			} else {
		 				// The returned HTML is no good - create a new transaction.
		 				$newTransaction = $($('#accounts-reconcile-transaction-template').html());
		 				$newTransaction.addClass('hidden');

		 				$newTransaction.find('span.account-reconcile-transaction-date').html(data.data.transaction.date);
		 				$newTransaction.find('span.account-reconcile-transaction-number').html(
		 					( data.data.transaction.code ||
		 					  data.data.transaction.code.length > 0 )
		 					? data.data.transaction.code
		 					: '&nbsp;'
		 				);
		 				$newTransaction.find('span.account-reconcile-transaction-description').html(
		 					( data.data.transaction.description.length > 0 )
		 					? data.data.transaction.description
		 					: '&nbsp;'
		 				);
		 				$checkbox = $newTransaction.find('span.account-reconcile-transaction-include input[type="checkbox"]');
		 				for( i in data.data.transaction.account_transactions ) {
		 					if( data.data.transaction.account_transactions[i].account.id == $('input[name="account_id"]').val() ) {
		 						$newTransaction.find('span.account-reconcile-transaction-amount').text(monetaryPrint(
		 							Math.abs(data.data.transaction.account_transactions[i].amount)
		 						));
		 						$checkbox.attr('rel',monetaryRound(
		 							data.data.transaction.account_transactions[i].amount * 
		 							parseInt(data.data.transaction.account_transactions[i].account.type.table_sign)
		 						));
		 						$newTransaction.attr('rel',data.data.transaction.account_transactions[i].id);
		 						$checkbox.attr('name','include-transaction-'+data.data.transaction.account_transactions[i].id);
		 					}
		 				}
		 				
		 				$checkbox.attr('checked','checked');
		 				
		 				checkboxUpdate($newTransaction.find('span.account-reconcile-transaction-include input[type="checkbox"]'));

		 				$newTransaction.addClass('selected');

		 				$target.find('li.account-reconcile-transaction.new').slideUp(function () {
							$(this).remove();
						});
						$target.find('li:first').after($newTransaction);
		 				hidePleaseWait();

		 				$newTransaction.slideDown();
		 				
						accountReconcileUpdateTotals();
		 			}
		 		},
		 		'json'
		 	);
		});


		/**
		 * STARTING BALANCE
		 */
		if( $('#accounts-startingbalance-form').length > 0 ) {
			accountStartingBalanceUpdateTotal();

			$('#accounts-startingbalance-status').attr('rel',135);
			$(window).scroll(function() {
				var startingbalanceStatusOffset = parseInt($('#accounts-startingbalance-status').attr('rel'));
				var startingbalanceStatusInitOffset = startingbalanceStatusOffset ;
				if( $(this).scrollTop() >= startingbalanceStatusInitOffset ) {
					$('#accounts-startingbalance-status').stop().animate({top: ($(this).scrollTop()-startingbalanceStatusOffset+100)+'px'}, 300, 'swing');
					//$('#accounts-startingbalance-status').css('top',($(this).scrollTop()-startingbalanceStatusOffset+100)+'px');
				} else {
					$('#accounts-startingbalance-status').stop().animate({top: '88px'}, 300, 'swing');
					//$('#accounts-startingbalance-status').css('top','88px');
				}
			});
		}

		$('#accounts-startingbalance-status input[name="date"]').datepicker({ dateFormat: "yy-mm-dd" });

		$('#accounts-reconcile-prep-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('.accounts-startingbalance-form-account .accounts-startingbalance-form-account-credit input[type="text"]').live('change',function() {
			// Clear debit and update
			if( $(this).val() && 
				$(this).val().length > 0 ) {
				$(this).closest('.accounts-startingbalance-form-account').find('.accounts-startingbalance-form-account-debit input[type="text"]').val('');
			}
			accountStartingBalanceUpdateTotal();
		});

		$('.accounts-startingbalance-form-account .accounts-startingbalance-form-account-debit input[type="text"]').live('change',function() {
			// Clear credit and update
			if( $(this).val() && 
				$(this).val().length > 0 ) {
				$(this).closest('.accounts-startingbalance-form-account').find('.accounts-startingbalance-form-account-credit input[type="text"]').val('');
			}
			accountStartingBalanceUpdateTotal();
		});

		$('#accounts-startingbalance-status-save').click(function() {
			showPleaseWait();
			$form = $('#accounts-startingbalance-status-save').closest('form');
			$.post(
				'/accounts/json/startingbalancevalidate',
				$form.find('input').serialize(),
				function(data) {
					if( ! data.success ) {
						hidePleaseWait();
						showError(data.error);
					} else {
						$form.submit();
					}
				},
				'json'
			);
		});


		// Trigger scroll to auto-load content if necessary.
		$(window).trigger('scroll');

	});

	function accountStartingBalanceUpdateTotal() {
		 
		var credit_total = 0.00;
		var debit_total = 0.00;

		$('.accounts-startingbalance-form-account').each(function() {
			$credit = $(this).find('.accounts-startingbalance-form-account-credit input[type="text"]');
			$debit = $(this).find('.accounts-startingbalance-form-account-debit input[type="text"]');

			if( $credit.val() && 
				$credit.val().length ) {
				$credit.val(convertCurrencyToNumber($credit.val()).toFixed(2));
				credit_total = monetaryRound( credit_total + parseFloat($credit.val()) );
			}
			
			if( $debit.val() && 
				$debit.val().length ) {
				$debit.val(convertCurrencyToNumber($debit.val()).toFixed(2));
				debit_total = monetaryRound( debit_total + parseFloat($debit.val()) );
			}
			
		});

		var balance = monetaryRound( credit_total - debit_total );

		$('#accounts-startingbalance-status input[name="debit_total"]').val(monetaryPrint(debit_total));
		$('#accounts-startingbalance-status input[name="credit_total"]').val(monetaryPrint(credit_total));
		$('#accounts-startingbalance-status input[name="balance"]').val(monetaryPrint(balance));


	}


	function accountReconcileUpdateTotals() {
		if( ! $('#accounts-reconcile-prep').is(':visible') ) {
			$('#accounts-reconcile-status').fadeTo(1,1);
		}

		var balance_start_value = convertCurrencyToNumber($('#accounts-reconcile-prep-balance_start').val());
		$('#accounts-reconcile-prep-balance_start').attr('rel',balance_start_value);
		$('#accounts-reconcile-prep-balance_start').val(monetaryPrint(balance_start_value));
		var balance_end_value = convertCurrencyToNumber($('#accounts-reconcile-prep-balance_end').val());
		$('#accounts-reconcile-prep-balance_end').attr('rel',balance_end_value);
		$('#accounts-reconcile-prep-balance_end').val(monetaryPrint(balance_end_value));

		$('#accounts-reconcile-status input[name="date"]').val($('#accounts-reconcile-prep-date').val());

		$balance_start = $('#accounts-reconcile-status input[name="balance_start"]');
		$balance_end = $('#accounts-reconcile-status input[name="balance_end"]');
		$balance_reconciled = $('#accounts-reconcile-status input[name="balance_reconciled"]');
		$balance_difference = $('#accounts-reconcile-status input[name="balance_difference"]');
		
		$balance_start.attr('rel',balance_start_value);
		$balance_start.val(monetaryPrint(balance_start_value));
		$balance_end.attr('rel',balance_end_value);
		$balance_end.val(monetaryPrint(balance_end_value));

		var balance_reconciled_value = monetaryRound(balance_start_value);
		
		$('.account-reconcile-transaction[rel] .account-reconcile-transaction-include .checkbox input[type="checkbox"]:checked').each(function() {
			if( $(this).attr('rel') ) {
				balance_reconciled_value =
					monetaryRound(
						balance_reconciled_value + 
						monetaryRound(parseFloat($(this).attr('rel')))
					);
			}
		});
		
		$balance_reconciled.attr('rel',balance_reconciled_value);
		$balance_reconciled.val(monetaryPrint(balance_reconciled_value));

		var balance_difference_value = monetaryRound(balance_end_value - balance_reconciled_value);

		$balance_difference.attr('rel',balance_difference_value);
		$balance_difference.val(monetaryPrint(balance_difference_value));
	}

	// monetaryPrint()
	/* function convertNumberToCurrency(value) {} */

	function colorAccountTransactionImportRows(container) {
		container.find('li.account-transaction').each(function() {
			if( $(this).attr('rel') && 
				$(this).attr('rel').length > 0 ) {
				$(this).removeClass("unclassified").removeClass("classified").removeClass("duplicate");
				if( ! $(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').length ) {
					$container = $(this).next('li');
					if( ! $container.hasClass('list-container') ) {
						// Something went terribly wrong.
						return;
					}
					$container.removeClass("unclassified").removeClass("classified").removeClass("duplicate");
					var classified = false;
					$container.find('select').each(function() {
						if( $(this).val().length ) {
							classified = true;
						}
					});
					if( classified ) {
						$container.addClass('classified');
						$container.prev('li.account-transaction').removeClass("unclassified").removeClass("classified").removeClass("duplicate").addClass('classified');
					} else {
						$container.addClass('unclassified');
						$container.prev('li.account-transaction').removeClass("unclassified").removeClass("classified").removeClass("duplicate").addClass('unclassified');
					}
				} else if( ! $(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').val() ||
					$(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').val().length == 0 ) {
					$(this).addClass("unclassified");
				} else if( $(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').val() == "duplicate" ||
						   $(this).find('select[name="import-transaction-'+$(this).attr('rel')+'-transaction-transfer"]').val() == "ignore" ) {
					$(this).addClass("duplicate");
				} else {
					$(this).addClass("classified");
				}
			}
		});
	}

}