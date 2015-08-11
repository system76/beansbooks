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

if ( document.body.className.match(new RegExp('(\\s|^)customers(\\s|$)')) !== null ) {

	var saleDescriptionCache = {};
	var saleDescriptionParams = {
		autoFocus: true,
		minLength: 2,
		select: function( event, ui ) {
			$description = $(event.target);
			$description.change();
			$price = $description.closest('.customers-sales-create-form-lines-line').find('input.line-price');
			if( $price.val().length == 0 ) {
				$price.val(ui.item.amount);
			}
			$account_id = $description.closest('.customers-sales-create-form-lines-line').find('select.account_id');
			$account_id.select2('data',{
				id: ui.item.account_id,
				text: $account_id.find('option[value="'+ui.item.account_id+'"]').text()
			});
		},
		source: function(request, response) {
			var search = request.term.toLowerCase();
			if( saleDescriptionCache[search] != null ) {
				response(saleDescriptionCache[search]);
				return;
			}
			$.post(
				'/customers/json/salelines',
				{
					search_term: search
				},
				function(data) {
					saleDescriptionCache[search] = [];
					for( i in data.data.lines ) {
						if( parseInt(i) < 5 ) {
							saleDescriptionCache[search].push({
								label: data.data.lines[i].description,
								value: data.data.lines[i].description,
								amount: data.data.lines[i].amount,
								account_id: data.data.lines[i].account_id
							});
						}
					}
					response(saleDescriptionCache[search]);
				},
				'json'
			);
		}
	};

	var customer_invoice_view = false;

	$(function() {

		/**
		 * Customers / Sales
		 */
		
		if( $('#customers-sales-sales').length > 0 ) {
			
			createSaleUpdateTaxTemplateVisibility();

			// Check for a default sale ID.
			if( $('#customers-sales-create-requested_sale_id').length > 0 &&
				$('#customers-sales-create-requested_sale_id').val().length > 0 ) {
				loadSale($('#customers-sales-create-requested_sale_id').val());
			}
		}

		if( $('#customers-payments-create-requested_payment_id').length > 0 &&
			$('#customers-payments-create-requested_payment_id').val().length > 0 ) {
			loadPayment($('#customers-payments-create-requested_payment_id').val());
		}

		$('#customers-sales-sales .customer-sale .view-sale').live('click',function() {
			// If we're on the sales page we AJAX - otherwise... return true.
			if( $('#customers-sales-create').length == 0 ) {
				return true;
			}
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadSale($(this).closest('.customer-sale').attr('rel'));
			return false;
		});

		$('#customers-sales-sales .customer-sale .view-invoice').live('click',function() {
			// If we're on the sales page we AJAX - otherwise... return true.
			if( $('#customers-sales-create').length == 0 ) {
				return true;
			}
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadSale($(this).closest('.customer-sale').attr('rel'));
			return false;
		});

		$('#customers-sales-create input,#customers-sales-create select').live('change',function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('input.datepicker').each(function() {
			$(this).datepicker({ dateFormat: "yy-mm-dd" });
		});

		if( $('input[name="invoice_view"]').length == 1 && 
			$('input[name="invoice_view"]').val() == "1" ) {
			customer_invoice_view = true;
		}

		var cancel_customer_sale_id = '';
		function cancelCustomerSale() {
			showPleaseWait();
			$saleLine = $('#customers-sales-sales .customer-sale[rel="'+cancel_customer_sale_id+'"]');
			$.post(
				'/customers/json/salecancel',
				{
					sale_id: cancel_customer_sale_id,
					invoice_view: ( customer_invoice_view ? '1' : '' )
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						if( $saleLine.length > 0 ) {
							$saleLine.slideUp(function() {
								$saleLine.remove();
								if( data.data.sale ) {
									$newSale = $(data.data.sale.html);
									$newSale.addClass('hidden');
									$('#customers-sales-sales .customer-sale:first').after($newSale);
									$newSale.slideDown(function() {
										rowElementsColorVisible($('#customers-sales-sales'));
									});
								} else {
									rowElementsColorVisible($('#customers-sales-sales'));
								}
							});
						}
						if( $('#customers-sales-create').attr('rel') &&
							$('#customers-sales-create').attr('rel') == cancel_customer_sale_id ) {
							createSaleClearForm();
						}
					}
				},
				'json'
			);
		}

		$('#customers-sales-sales .customer-sale .cancel').live('click',function(e) {
			e.preventDefault();
			cancel_customer_sale_id = $(this).closest('.customer-sale').attr('rel');
			showConfirm("Are you certain you want to delete this sale?","Yes, Delete.","No.",cancelCustomerSale);
			return false;
		});

		$('#customers-sales-sales .customer-sale .refund').live('click',function() {
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadSale($(this).closest('.customer-sale').attr('rel'),true);
			return false;
		});

		$('#customers-sales-sales .customer-sale .send').live('click',function(e) {
			e.preventDefault();
			$sale = $(this).closest('.customer-sale');
			// Check if we've already got a send template loaded.
			if( $('#customers-sales-sales .customer-sale-send[rel="'+$sale.attr('rel')+'"]').length > 0 ) {
				$('#customers-sales-sales .customer-sale-send[rel="'+$sale.attr('rel')+'"]').slideUp(function() {
					$(this).remove();
				});
			} else {
				$sendSale = $($('#customers-sales-send-template').html());
				$sendSale.addClass('hidden');
				$sendSale.attr('rel',$sale.attr('rel'));
				$sendSale.find('input[name="email"]').val($sale.find('input.email').val());
				$sale.after($sendSale);
				if( $('#customers-sales-sales .customer-sale-invoice[rel="'+$sale.attr('rel')+'"]').length > 0 ) {
					$('#customers-sales-sales .customer-sale-invoice[rel="'+$sale.attr('rel')+'"]').slideUp(function () {
						$('#customers-sales-sales .customer-sale-invoice[rel="'+$sale.attr('rel')+'"]').remove();
						$sendSale.slideDown();
					});
				} else {
					$sendSale.slideDown();
				}
				
			}
		});

		$('#customers-sales-sales .customer-sale-send .customer-sale-send-email .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$doneCheckbox = $(this).closest('.customer-sale-send').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-send .customer-sale-send-mail .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$doneCheckbox = $(this).closest('.customer-sale-send').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-send .customer-sale-send-done .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$emailCheckbox = $(this).closest('.customer-sale-send').find('input[name="send-email"]');
				$mailCheckbox = $(this).closest('.customer-sale-send').find('input[name="send-mail"]');
				$emailCheckbox.attr('checked',false);
				$mailCheckbox.attr('checked',false);
				checkboxUpdate($emailCheckbox);
				checkboxUpdate($mailCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-send .send-cancel').live('click',function(e) {
			e.preventDefault();
			$sendSale = $(this).closest('li.customer-sale-send');
			$sendSale.slideUp(function() {
				$sendSale.remove();
			});
		});

		$('#customers-sales-sales .customer-sale-send .send-submit').live('click',function(e) {
			e.preventDefault();
			showPleaseWait();
			var print = false;
			if( $(this).closest('.customer-sale-send').find('input[name="send-mail"]').is(':checked') ) {
				print = true;
			}
			$.post(
				'/customers/json/salesend',
				$(this).closest('.customer-sale-send').find('input').serialize()+'&sale_id='+$(this).closest('.customer-sale-send').attr('rel')+'&invoice_view='+( customer_invoice_view ? '1' : ''),
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$oldSale = $('#customers-sales-sales .customer-sale:not(.customer-sale-send)[rel="'+data.data.sale.id+'"]');
						$sendSale = $('#customers-sales-sales .customer-sale.customer-sale-send[rel="'+data.data.sale.id+'"]');
						$newSale = $(data.data.sale.html)
						$newSale.addClass('hidden');
						$oldSale.after($newSale);
						$sendSale.slideUp();
						$oldSale.slideUp(function() {
							$newSale.slideDown(function() {
								rowElementsColorVisible($('#customers-sales-sales'));
							});
							$oldSale.remove();
							$sendSale.remove();
						});
						
						// TODO FANCY ANIMATION COLORS 
						if( print ) {
							printCustomerSale(data.data.sale.id);
						}
					}
				},
				'json'
			);
		});

		/**
		 *
		 * 
		 *  INVOICE ! ! ! 
		 *
		 * 
		 */
		$('#customers-sales-sales .customer-sale .invoice').live('click',function(e) {
			e.preventDefault();
			$sale = $(this).closest('.customer-sale');
			// Check if we've already got a send template loaded.
			if( $('#customers-sales-sales .customer-sale-invoice[rel="'+$sale.attr('rel')+'"]').length > 0 ) {
				$('#customers-sales-sales .customer-sale-invoice[rel="'+$sale.attr('rel')+'"]').slideUp(function() {
					$(this).remove();
				});
			} else {
				$sendSale = $($('#customers-sales-invoice-template').html());
				$sendSale.addClass('hidden');
				$sendSale.attr('rel',$sale.attr('rel'));
				$sendSale.find('input[name="email"]').val($sale.find('input.email').val());
				$sale.after($sendSale);
				if( $('#customers-sales-sales .customer-sale-send[rel="'+$sale.attr('rel')+'"]').length > 0 ) {
					$('#customers-sales-sales .customer-sale-send[rel="'+$sale.attr('rel')+'"]').slideUp(function() {
						$('#customers-sales-sales .customer-sale-send[rel="'+$sale.attr('rel')+'"]').remove();
						$sendSale.slideDown();
					});
				} else {
					$sendSale.slideDown();
				}
			}
		});

		$('#customers-sales-sales .customer-sale-invoice .customer-sale-send-email .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$doneCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
				$noneCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-none"]');
				$noneCheckbox.attr('checked',false);
				checkboxUpdate($noneCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-invoice .customer-sale-send-mail .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$doneCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
				$noneCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-none"]');
				$noneCheckbox.attr('checked',false);
				checkboxUpdate($noneCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-invoice .customer-sale-send-done .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$emailCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-email"]');
				$emailCheckbox.attr('checked',false);
				checkboxUpdate($emailCheckbox);
				$mailCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-mail"]');
				$mailCheckbox.attr('checked',false);
				checkboxUpdate($mailCheckbox);
				$noneCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-none"]');
				$noneCheckbox.attr('checked',false);
				checkboxUpdate($noneCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-invoice .customer-sale-send-none .checkbox').live('click',function(e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$emailCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-email"]');
				$emailCheckbox.attr('checked',false);
				checkboxUpdate($emailCheckbox);
				$mailCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-mail"]');
				$mailCheckbox.attr('checked',false);
				checkboxUpdate($mailCheckbox);
				$doneCheckbox = $(this).closest('.customer-sale-invoice').find('input[name="send-done"]');
				$doneCheckbox.attr('checked',false);
				checkboxUpdate($doneCheckbox);
			}
		});

		$('#customers-sales-sales .customer-sale-invoice .send-cancel').live('click',function(e) {
			e.preventDefault();
			$sendSale = $(this).closest('li.customer-sale-invoice');
			$sendSale.slideUp(function() {
				$sendSale.remove();
			});
		});

		$('#customers-sales-sales .customer-sale-invoice .send-submit').live('click',function(e) {
			e.preventDefault();
			showPleaseWait();
			var print = false;
			if( $(this).closest('.customer-sale-invoice').find('input[name="send-mail"]').is(':checked') ) {
				print = true;
			}
			$saleinvoice = $(this).closest('.customer-sale-invoice');

			$.post(
				'/customers/json/salesendvalidate',
				$saleinvoice.find('input').serialize()+'&sale_id='+$saleinvoice.attr('rel'),
				function (validatedata) {
					if( ! validatedata.success ) {
						hidePleaseWait();
						showError(validatedata.error);
					} else {
						$.post(
							'/customers/json/saleinvoice',
							$saleinvoice.find('input').serialize()+'&sale_id='+$saleinvoice.attr('rel'),
							function(data) {
								hidePleaseWait();
								if( data.success != 1 ) {
									showError(data.error);
								} else {
									$oldSale = $('#customers-sales-sales .customer-sale:not(.customer-sale-invoice)[rel="'+data.data.sale.id+'"]');
									$sendSale = $('#customers-sales-sales .customer-sale.customer-sale-invoice[rel="'+data.data.sale.id+'"]');
									$newSale = $(data.data.sale.html)
									$newSale.addClass('hidden');
									$oldSale.after($newSale);
									$sendSale.slideUp();
									$oldSale.slideUp(function() {
										$newSale.slideDown(function() {
											rowElementsColorVisible($('#customers-sales-sales'));
										});
										$oldSale.remove();
										$sendSale.remove();
									});
									
									// TODO FANCY ANIMATION COLORS 
									if( print ) {
										printCustomerSale(data.data.sale.id);
									}
								}
							},
							'json'
						);
					}
				},
				'json'
			);
		});

		$('#customers-sales-create-form-invoice .checkbox').live('click', function (e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				if( $checkbox.attr('name') == "send-done" ) {
					$emailCheckbox = $('#customers-sales-create-form-invoice input[name="send-email"]');
					$mailCheckbox = $('#customers-sales-create-form-invoice input[name="send-mail"]');
					$emailCheckbox.attr('checked',false);
					$mailCheckbox.attr('checked',false);
					checkboxUpdate($emailCheckbox);
					checkboxUpdate($mailCheckbox);
				} else {
					$doneCheckbox = $('#customers-sales-create-form-invoice input[name="send-done"]');
					$doneCheckbox.attr('checked',false);
					checkboxUpdate($doneCheckbox);
				}
			}
		});

		$('#customers-sales-create-form-send .checkbox').live('click', function (e) {
			e.preventDefault();
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				if( $checkbox.attr('name') == "send-done" ) {
					$emailCheckbox = $('#customers-sales-create-form-send input[name="send-email"]');
					$mailCheckbox = $('#customers-sales-create-form-send input[name="send-mail"]');
					$emailCheckbox.attr('checked',false);
					$mailCheckbox.attr('checked',false);
					checkboxUpdate($emailCheckbox);
					checkboxUpdate($mailCheckbox);
				} else {
					$doneCheckbox = $('#customers-sales-create-form-send input[name="send-done"]');
					$doneCheckbox.attr('checked',false);
					checkboxUpdate($doneCheckbox);
				}
			}
		});

		$('#customers-sales-create-form-refund').live('click', function (e) {
			e.preventDefault();
			loadSale($('#customers-sales-create').attr('rel'),true);
		});

		// TODO - Known bug, we're not checking for sale ID to be valid.
		$('#customers-sales-create-form-send-submit').live('click', function (e) {
			e.preventDefault();
			if( $('#customers-sales-create-form-send').attr('rel') == "send" ) {
				showPleaseWait();
				$.post(
					'/customers/json/salesend',
					$('#customers-sales-create-form-send').find('input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel')+'&invoice_view='+( customer_invoice_view ? '1' : ''),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							$('#customers-sales-create-form-send').slideUp();
							$('#customers-sales-create-status').html('<span class="status">'+data.data.sale.status+'</span>');
							if( data.data.sale.payments.length > 0 ) {
								$('#customers-sales-create-status').html('<span class="text-bold">'+data.data.sale.status+' - Payments: </span>');
								var first = true;
								for( i in data.data.sale.payments ) {
									if( first ) {
										first = false;
									} else {
										$('#customers-sales-create-status').append(',');
									}
									$('#customers-sales-create-status').append(' <a href="/customers/payments/'+data.data.sale.payments[i].id+'">'+data.data.sale.payments[i].date+'</a>');
								}
							} else {
								$('#customers-sales-create-status').html('<span class="text-bold">'+data.data.sale.status+'</span>');
							}
							// TODO FANCY COLOR ANIMATION
							if( $('#customers-sales-create-form-send input[name="send-mail"]').is(':checked') ) {
								printCustomerSale(data.data.sale.id);
							}
						}
					},
					'json'
				);
			} else {
				showPleaseWait();
				createSaleIndexLines();
				// Validate First
				$.post(
					'/customers/json/salesendvalidate',
					$('#customers-sales-create-form-send').find('input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel'),
					function(datavalid) {
						if( datavalid.success != 1 ) {
							hidePleaseWait();
							showError(datavalid.error);
						} else {
							if( $('#customers-sales-create').attr('rel') &&
								$('#customers-sales-create').attr('rel') == "R" ) {
								// REFUND
								// Re-enable all disabled fields.
								// TODO - disabled fields should be readonly instead
								$('#customers-sales-create input[disabled],#customers-sales-create select[disabled]').each(function() {
									$(this).attr('disabled',false).focus().blur();
								});
								createSaleUpdateTotals();
								$.post(
									'/customers/json/salerefund',
									$('#customers-sales-create input,#customers-sales-create select').serialize(),
									function(datacreate) {
										if( datacreate.success != 1 ) {
											hidePleaseWait();
											showError(datacreate.error);
										} else {

											$('#customers-sales-create').attr('rel',datacreate.data.sale.id);

											$.post(
												'/customers/json/salesend',
												$('#customers-sales-create-form-send').find('input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel')+'&invoice_view='+( customer_invoice_view ? '1' : ''),
												function(data) {
													hidePleaseWait();
													if( data.success != 1 ) {
														showError(data.error);
													} else {
														$('#customers-sales-create-form-send').slideUp();
														
														$oldSale = $('#customers-sales-sales .customer-sale[rel="'+$('#customers-sales-create input[name="refund_sale_id"]').val()+'"]:first-child');
														createSaleClearForm();
														$newSale = $(data.data.sale.html);
														$newSale.addClass('hidden');
														$oldSale.find('a.refund').remove();
														$('#customers-sales-sales .customer-sale:first').after($newSale);
														$newSale.slideDown(function() {
															rowElementsColorVisible($('#customers-sales-sales'));
														});
														// TODO - ADD COLOR ANIMATION
														
														if( data.data.sale.payments.length > 0 ) {
															$('#customers-sales-create-status').html('<span class="text-bold">'+data.data.sale.status+' - Payments: </span>');
															var first = true;
															for( i in data.data.sale.payments ) {
																if( first ) {
																	first = false;
																} else {
																	$('#customers-sales-create-status').append(',');
																}
																$('#customers-sales-create-status').append(' <a href="/customers/payments/'+data.data.sale.payments[i].id+'">'+data.data.sale.payments[i].date+'</a>');
															}
														} else {
															$('#customers-sales-create-status').html('<span class="text-bold">'+data.data.sale.status+'</span>');
														}
														
														// TODO FANCY COLOR ANIMATION
														if( $('#customers-sales-create-form-send input[name="send-mail"]').is(':checked') ) {
															printCustomerSale(data.data.sale.id);
														}
													}
												},
												'json'
											);
											
										}
									},
									'json'
								);
							} else if( $('#customers-sales-create').attr('rel') &&
								$('#customers-sales-create').attr('rel').length ) {
								// UPDATE
								$.post(
									'/customers/json/saleupdate',
									$('#customers-sales-create input,#customers-sales-create select').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel'),
									function(datacreate) {
										hidePleaseWait();
										if( datacreate.success != 1 ) {
											showError(datacreate.error);
										} else {
											
											$('#customers-sales-create').attr('rel',datacreate.data.sale.id);

											$.post(
												'/customers/json/salesend',
												$('#customers-sales-create-form-send').find('input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel')+'&invoice_view='+( customer_invoice_view ? '1' : ''),
												function(data) {
													hidePleaseWait();
													if( data.success != 1 ) {
														showError(data.error);
													} else {
														$('#customers-sales-create-form-send').slideUp();
														createSaleClearForm();
											
														$newSale = $(data.data.sale.html);
														$newSale.addClass('hidden');

														if( $('#customers-sales-sales .customer-sale[rel="'+data.data.sale.id+'"]').length > 0 ) {
															$oldSale = $('#customers-sales-sales .customer-sale[rel="'+data.data.sale.id+'"]:first');
															$oldSale.before($newSale);
															$oldSale.slideUp();
														} else {
															$('#customers-sales-sales .customer-sale:first').after($newSale);
														}

														$newSale.slideDown(function() {
															rowElementsColorVisible($('#customers-sales-sales'));
														});
														// TODO - ADD COLOR ANIMATION
														if( $('#customers-sales-create-form-send input[name="send-mail"]').is(':checked') ) {
															printCustomerSale(data.data.sale.id);
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
									'/customers/json/salecreate',
									$('#customers-sales-create input,#customers-sales-create select').serialize(),
									function(datacreate) {
										hidePleaseWait();
										if( datacreate.success != 1 ) {
											showError(datacreate.error);
										} else {
											$('#customers-sales-create').attr('rel',datacreate.data.sale.id);

											$.post(
												'/customers/json/salesend',
												$('#customers-sales-create-form-send').find('input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel')+'&invoice_view='+( customer_invoice_view ? '1' : ''),
												function(data) {
													hidePleaseWait();
													if( data.success != 1 ) {
														showError(data.error);
													} else {
														$('#customers-sales-create-form-send').slideUp();
														createSaleClearForm();
														$newSale = $(data.data.sale.html);
														$newSale.addClass('hidden');
														$('#customers-sales-sales .customer-sale:first').after($newSale);
														$newSale.slideDown(function() {
															$noSales = $('#customers-sales-sales .customer-sale:last');
															if( $noSales.find('span').length == 0 ) {
																$noSales.slideUp(function() {
																	$noSales.remove();
																});
															}
															rowElementsColorVisible($('#customers-sales-sales'));
														});
														if( $('#customers-sales-create-form-send input[name="send-mail"]').is(':checked') ) {
															printCustomerSale(data.data.sale.id);
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

		$('#customers-sales-create-form-send-cancel').live('click', function (e) {
			e.preventDefault();
			$('#customers-sales-create-form-send').slideUp();
		});

		$('#customers-sales-create-form-savesend').live('click', function (e) {
			e.preventDefault();
			if( $('#customers-sales-create-form-send').is(':visible') ) {
				$('#customers-sales-create-form-send').slideUp();
			} else {
				$('#customers-sales-create-form-send').attr('rel','save');
				$('#customers-sales-create-form-send').slideDown();
			}
		});

		$('#customers-sales-create-form-onlysend').live('click', function (e) {
			e.preventDefault();
			if( $('#customers-sales-create-form-send').is(':visible') ) {
				$('#customers-sales-create-form-send').slideUp();
			} else if ( $('#customers-sales-create-form-invoice').is(':visible') ) {
				$('#customers-sales-create-form-send').attr('rel','send');
				$('#customers-sales-create-form-invoice').slideUp(function() {
					$('#customers-sales-create-form-send').slideDown();
				});
			} else {
				$('#customers-sales-create-form-send').attr('rel','send');
				$('#customers-sales-create-form-send').slideDown();
			}
		});

		// DELETE
		$('#customers-sales-create-form-delete').live('click', function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') &&
				$(this).attr('disabled').length ) {
				return;
			}
			cancel_customer_sale_id = $('#customers-sales-create').attr('rel');
			showConfirm("Are you certain you want to delete this sale?","Yes, Delete.","No.",cancelCustomerSale);
			return false;
		});

		// INVOICE
		$('#customers-sales-create-form-convertinvoice').live('click', function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') &&
				$(this).attr('disabled').length ) {
				return;
			}
			if( $('#customers-sales-create-form-invoice').is(':visible') ) {
				$('#customers-sales-create-form-invoice').slideUp();
			} else if( $('#customers-sales-create-form-send').is(':visible') ) {
				$('#customers-sales-create-form-send').slideUp(function() {
					$('#customers-sales-create-form-invoice').slideDown();
				});
			} else {
				// Fill with email, etc.
				$('#customers-sales-create-form-invoice').slideDown();
			}
		});

		$('#customers-sales-create-form-print').live('click', function (e) {
			e.preventDefault();
			printCustomerSale($('#customers-sales-create').attr('rel'));
		});

		$('#customers-sales-create-form-invoice-cancel').live('click', function (e) {
			$('#customers-sales-create-form-invoice').slideUp();
		});

		$('#customers-sales-create-form-invoice-submit').live('click', function (e) {
			e.preventDefault();
			showPleaseWait();
			var print = false;
			if( $('#customers-sales-create-form-invoice input[name="send-mail"]').is(':checked') ) {
				print = true;
			}
			$.post(
				'/customers/json/salesendvalidate',
				$('#customers-sales-create-form-invoice input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel'),
				function (validatedata) {
					if( ! validatedata.success ) {
						hidePleaseWait();
						showError(validatedata.error);
					} else {
						$.post(
							'/customers/json/saleinvoice',
							$('#customers-sales-create-form-invoice input').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel'),
							function(data) {
								hidePleaseWait();
								if( data.success != 1 ) {
									showError(data.error);
								} else {
									createSaleClearForm();
									$oldSale = $('#customers-sales-sales .customer-sale:not(.customer-sale-invoice)[rel="'+data.data.sale.id+'"]');
									$sendSale = $('#customers-sales-sales .customer-sale.customer-sale-invoice[rel="'+data.data.sale.id+'"]');
									$newSale = $(data.data.sale.html)
									$newSale.addClass('hidden');
									$oldSale.after($newSale);
									$sendSale.slideUp();
									$oldSale.slideUp(function() {
										$newSale.slideDown(function() {
											rowElementsColorVisible($('#customers-sales-sales'));
										});
										$oldSale.remove();
										$sendSale.remove();
									});
									
									// TODO FANCY ANIMATION COLORS 
									if( print ) {
										printCustomerSale(data.data.sale.id);
									}
								}
							},
							'json'
						);
					}
				},
				'json'
			);
		});


		/**
		 * Create Customer And Address Dialogs
		 */
		
		$('#customers-sales-dialog-customer-create').modaldialog({
			autoOpen: false,
			width: 500,
			buttons: { 
				"Cancel": function() { 
					$(this).dialog("close");
					$(this).find('input, select').resetFieldValues();
				},
				"Save Customer": function() { 
					$currentDialog = $(this);
					showPleaseWait();
					$.post(
						'/customers/json/customercreate',
						$currentDialog.find('input,select').serialize(),
						function(data) {
							hidePleaseWait();
							if( data.success != 1 ) {
								showError(data.error);
							} else {
								// KISS
								var customer = data.data.customer;
								var select2data = {
									text: customer.display_name
								};
								if( customer.default_account ) {
									select2data.id = 	customer.id+'#'+
												customer.default_billing_address_id+'#'+
												customer.default_shipping_address_id+'#'+
												customer.default_account.id+'#'+
												customer.default_account.terms;
								} else {
									select2data.id = 	customer.id+'#'+
												customer.default_billing_address_id+'#'+
												customer.default_shipping_address_id+'#';
								}
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
								$('#customers-sales-create input[name="customer"]').select2("data",select2data);
								$('#customers-sales-create input[name="customer"]').change();
								if( customer.default_account ) {
									$('#customers-sales-create select[name="account"]').select2("data",{ id: customer.default_account.id+'#'+customer.default_account.terms, text: customer.default_account.name });
								}
							}
						},
						'json'
					);
				}
			}
		});
		
		$('#customers-sales-dialog-customer-create-link').click(function() {
			$('#customers-sales-dialog-customer-create').dialog("open");
		});

		$('#customers-sales-dialog-address-create').modaldialog({
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
						'/customers/json/customeraddresscreate',
						$currentDialog.find('input,select').serialize(),
						function(data){
							hidePleaseWait();
							if( ! data.success ) {
								showError(data.error);
							} else {
								var address = data.data.address;
								$('#customers-sales-create select[name="billing_address_id"],#customers-sales-create select[name="shipping_address_id"]').append('<option value="'+address.id+'">'+address.address1+'</option>');
								$('#customers-sales-create select[name="'+$currentDialog.attr('rel')+'"]').val(address.id);
								$currentDialog.dialog("close");
								$currentDialog.find('input, select').resetFieldValues();
							}
						},
						'json'
					);
				}
			}
		});

		$('#customers-sales-dialog-address-create-link-billing').click(function() {
			if( ! $('#customers-sales-create input[name="customer"]').val() ||
				! $('#customers-sales-create input[name="customer"]').val().length ) {
				showError("You must select a customer before creating an address.");
				return;
			}
			var customer = $('#customers-sales-create input[name="customer"]').val().split('#');;
			$('#customers-sales-dialog-address-create input[name="customer_id"]').val(customer[0]);
			$('#customers-sales-dialog-address-create').attr('rel','billing_address_id');
			$('#customers-sales-dialog-address-create').dialog("open")
		});

		$('#customers-sales-dialog-address-create-link-shipping').click(function() {
			if( ! $('#customers-sales-create input[name="customer"]').val() ||
				! $('#customers-sales-create input[name="customer"]').val().length ) {
				showError("You must select a customer before creating an address.");
				return;
			}
			var customer = $('#customers-sales-create input[name="customer"]').val().split('#');;
			$('#customers-sales-dialog-address-create input[name="customer_id"]').val(customer[0]);
			$('#customers-sales-dialog-address-create').attr('rel','shipping_address_id');
			$('#customers-sales-dialog-address-create').dialog("open")
		});

		$('#customers-sales-sales .customer-sale .print').live('click',function(e) {
			e.preventDefault();
			printCustomerSale($(this).closest('li.customer-sale').attr('rel'));
		});

		// SALES SEARCH
		$('#customers-sales-sales-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
			if(code == 13) {
				$('#customers-sales-sales li.customer-sale:not(:first-child)').remove();
		 		$('#customers-sales-endsales').hide();
		 		$('#customers-sales-loadsales').show();
		 		loadMoreSales();
	 		}
		});

		// SEARCH PASTDUE
		$('#customers-sales-sales-show-pastdue').click(function() {
			$(this).hide();
			$('#customers-sales-sales-show-all').show();
			$('#customers-sales-sales-pastdue').val('1');
			$('#customers-sales-sales li.customer-sale:not(:first-child)').remove();
			$('#customers-sales-endsales').hide();
			$('#customers-sales-loadsales').show();
			loadMoreSales();
		});

		$('#customers-sales-sales-show-all').click(function() {
			$(this).hide();
			$('#customers-sales-sales-show-pastdue').show();
			$('#customers-sales-sales-pastdue').val('0');
			$('#customers-sales-sales li.customer-sale:not(:first-child)').remove();
			$('#customers-sales-endsales').hide();
			$('#customers-sales-loadsales').show();
			loadMoreSales();
		});

		$('#customers-sales-create-button').click(function() {
			if( ! $('#customers-sales-create').is(':visible') ) {
				$('#customers-sales-create').attr('rel','');
				$('#customers-sales-create').slideDown();
			}
			return false;
		});

		$('#customers-sales-create-form-cancel').click(function (e) {
			e.preventDefault();
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					if( $('#customers-sales-create').attr('rel') &&
						$('#customers-sales-create').attr('rel').length > 0 ) {
						if( $('#customers-sales-create').attr('rel') == "R" ) {
							loadSale($('#customers-sales-create input[name="refund_sale_id"]').val());
						} else {
							loadSale($('#customers-sales-create').attr('rel'));
						}
					} else {
						createSaleClearForm();
					}
				}
			} else {
				if( $('#customers-sales-create').attr('rel') &&
					$('#customers-sales-create').attr('rel').length > 0 ) {
					if( $('#customers-sales-create').attr('rel') == "R" ) {
						loadSale($('#customers-sales-create input[name="refund_sale_id"]').val());
					} else {
						loadSale($('#customers-sales-create').attr('rel'));
					}
				} else {
					createSaleClearForm();
				}
			}
		});

		$('#customers-sales-create-form-editcancel').click(function (e) {
			e.preventDefault();
			createSaleClearForm();
			return false;
		});

		$('#customers-sales-create-form-edit').click(function (e) {
			e.preventDefault();
			if( $(this).attr('disabled') &&
				$(this).attr('disabled').length ) {
				return;
			}
			if( ! $('#customers-sales-create').attr('rel') ||
				$('#customers-sales-create').attr('rel').length == 0 ) {
				showError("An unexpected error has occurred.<br>You should reload the page before going any further.");
				return;
			}
			$('#customers-sales-create input:not(.ezpz-hint,.datepicker,.line-total),#customers-sales-create select').each(function() {
				$(this).attr('readonly',false).attr('disabled',false).focus().blur();
			});
			$('#customers-sales-create select[name="account"]').select2('enable');
			$('#customers-sales-create select[name="account"]').attr('disabled',false).attr('readonly',false);
			
			$('#customers-sales-create-form-lines select.account_id').each(function () {
				$(this).select2("enable");
			});
			$('#customers-sales-create input.datepicker').each(function() {
				$(this).attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
			});
			$('#customers-sales-create input.tax-exempt').each(function() {
				$(this).attr('disabled',false);
				checkboxUpdate($(this));
			});
			$('#customers-sales-create div.select').removeClass('disabled');
			$('#customers-sales-create .customer-sales-create-new-buttons').show();
			$('#customers-sales-create .customer-sales-create-edit-buttons').hide();
			return false;
		});



		$('#customers-sales-create-form-save').click(function(e) {
			e.preventDefault();
			showPleaseWait();
			// Serialize and submit.
			createSaleIndexLines();
			if( $('#customers-sales-create').attr('rel') &&
				$('#customers-sales-create').attr('rel') == "R" ) {
				// REFUND
				// Re-enable all disabled fields.
				$('#customers-sales-create input[disabled],#customers-sales-create select[disabled]').each(function() {
					$(this).attr('disabled',false).focus().blur();
				});
				createSaleUpdateTotals();
				$.post(
					'/customers/json/salerefund',
					$('#customers-sales-create input,#customers-sales-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							$oldSale = $('#customers-sales-sales .customer-sale[rel="'+$('#customers-sales-create input[name="refund_sale_id"]').val()+'"]:first-child');
							createSaleClearForm();
							$newSale = $(data.data.sale.html);
							$newSale.addClass('hidden');
							$oldSale.find('a.refund').remove();
							$('#customers-sales-sales .customer-sale:first').after($newSale);
							$newSale.slideDown(function() {
								rowElementsColorVisible($('#customers-sales-sales'));
							});
							// TODO - ADD COLOR ANIMATION
							
						}
					},
					'json'
				);
			} else if( $('#customers-sales-create').attr('rel') &&
				$('#customers-sales-create').attr('rel').length ) {
				// UPDATE
				$.post(
					'/customers/json/saleupdate',
					$('#customers-sales-create input,#customers-sales-create select').serialize()+'&sale_id='+$('#customers-sales-create').attr('rel'),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createSaleClearForm();
							
							$newSale = $(data.data.sale.html);
							$newSale.addClass('hidden');

							if( $('#customers-sales-sales .customer-sale[rel="'+data.data.sale.id+'"]').length > 0 ) {
								$oldSale = $('#customers-sales-sales .customer-sale[rel="'+data.data.sale.id+'"]:first');
								$oldSale.before($newSale);
								$oldSale.slideUp();
							} else {
								$('#customers-sales-sales .customer-sale:first').after($newSale);
							}

							$newSale.slideDown(function() {
								rowElementsColorVisible($('#customers-sales-sales'));
							});
							// TODO - ADD COLOR ANIMATION
						}
					},
					'json'
				);
			} else {
				// NEW
				$.post(
					'/customers/json/salecreate',
					$('#customers-sales-create input,#customers-sales-create select').serialize(),
					function(data) {
						hidePleaseWait();
						if( data.success != 1 ) {
							showError(data.error);
						} else {
							createSaleClearForm();
							$newSale = $(data.data.sale.html);
							$newSale.addClass('hidden');
							$('#customers-sales-sales .customer-sale:first').after($newSale);
							$newSale.slideDown(function() {
								$noSales = $('#customers-sales-sales .customer-sale:last');
								if( $noSales.find('span').length == 0 ) {
									$noSales.slideUp(function() {
										$noSales.remove();
									});
								}
								rowElementsColorVisible($('#customers-sales-sales'));
							});
							
						}
					},
					'json'
				);
			}
		});

		$('#customers-sales-create input[placeholder]').ezpz_hint();

		// Add one option.
		if( $('#customers-sales-create-form-lines').length ) {
			$newSaleLine = $($('#customers-sales-create-form-lines-line-template').html());
			$newSaleLine.addClass('hidden');
			$('#customers-sales-create-form-lines').append($newSaleLine);
			$newSaleLine.find('input.line-description').autocomplete(saleDescriptionParams);
			$newSaleLine.slideDown(function () {
				$newSaleLine.css('overflow','');
				$newSaleLine.find('select.account_id').accountDropdown();
			});
		}

		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line input.line-description').live('change',function() {
			if( $(this).val() &&
				$(this).val().length &&
				$(this).closest('.customers-sales-create-form-lines-line').is(':last-child') &&
				$(this).closest('.customers-sales-create-form-lines-line').find('select.account_id').val().length > 0 ) {
				$newSaleLine = $($('#customers-sales-create-form-lines-line-template').html());
				$newSaleLine.addClass('hidden');

				if( $('#customers-sales-create input[name="refund_sale_id"]').val().length > 0 &&
					$('#customers-sales-refund-default_account_id').val().length > 0 ) {
					$newSaleLine.find('select[name="line-account_id"]').val($('#customers-sales-refund-default_account_id').val());
				}

				$('#customers-sales-create-form-lines').append($newSaleLine);
				$newSaleLine.find('input.line-description').autocomplete(saleDescriptionParams);
				$newSaleLine.slideDown(function () {
					$newSaleLine.css('overflow','');
					$newSaleLine.find('select.account_id').accountDropdown();
				});
			}
		});

		
		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line input.line-quantity').live('change', function () {
			createSaleUpdateTotals();
		});

		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line input.line-price').live('change',function() {
			createSaleUpdateTotals();
		});

		// Consider adding a click counter here to warn the user why exempt won't work.
		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line .line-tax-exempt').live('click',function(e) {
			e.preventDefault();
			if( $('#customers-sales-create input[name="form_tax_exempt"]').is(':checked') ) {
				return;
			}
			if( $('#customers-sales-create input[name="form_tax_exempt"]').is(':disabled') ) {
				return;
			}
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
			}
			createSaleUpdateTotals();
		});

		$('#customers-sales-create .form-taxes input[type="checkbox"]').live('click', function () {
			if( $(this).attr('name') == "form_tax_exempt" ) {
				createSaleToggleTaxExempt();
			}
			createSaleUpdateTotals();
		});

		$('#customers-sales-create .form-taxes .option .label').live('click', function () {
			if( $(this).closest('.option').find('input[type="checkbox"]').attr('name') == "form_tax_exempt" ) {
				createSaleToggleTaxExempt();
			}
			createSaleUpdateTotals();
		});

		$('#customers-sales-create input[name="customer"]').select2({
			minimumInputLength: 1,
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/customers/json/customersloadmore",
				type: "POST",
				dataType: 'json',
				data: function (term) {
					return {
						last_customer_id: '',
						search_terms: term, // search term
						count: 1000,
					};
				},
				results: function (data) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					var results = new Array();
					for( index in data.data.customers ) {
						results[index] = {
							text: data.data.customers[index].display_name,
							id: 
								data.data.customers[index].id
								+'#'+
								( typeof data.data.customers[index].default_billing_address_id != "undefined" &&
									data.data.customers[index].default_billing_address_id != null &&
									data.data.customers[index].default_billing_address_id.length ? data.data.customers[index].default_billing_address_id : '' )
								+'#'+
								( typeof data.data.customers[index].default_shipping_address_id != "undefined" &&
									data.data.customers[index].default_shipping_address_id != null &&
									data.data.customers[index].default_shipping_address_id.length ? data.data.customers[index].default_shipping_address_id : '' )
								+'#'+
								( typeof data.data.customers[index].default_account != "undefined" && 
									data.data.customers[index].default_account != null && 
									typeof data.data.customers[index].default_account.id != "undefined" &&
									data.data.customers[index].default_account.id != null &&
									data.data.customers[index].default_account.id.length ? data.data.customers[index].default_account.id : '' )
								+'#'+
								( typeof data.data.customers[index].default_account != "undefined" && 
									data.data.customers[index].default_account != null && 
									typeof data.data.customers[index].default_account.terms != "undefined" ? data.data.customers[index].default_account.terms : '' )
						};
					}
					return {results: results};
				}
			}
		});

		
		$('#customers-sales-create input[name="customer"]').change(function() {
			if( ! $(this).val() ||
				! $(this).val().length ) {
				$('#customers-sales-create select[name="billing_address_id"] option[value!=""],#customers-sales-create select[name="shipping_address_id"] option[value!=""]').remove();
				return true;
			}
			var customer = $(this).val().split('#');
			showPleaseWait();
			if( customer[3].length ) {
				$('#customers-sales-create select[name="account"]').select2('data',{
					id: customer[3]+'#'+customer[4],
					text: $('#customers-sales-create select[name="account"] option[value="'+customer[3]+'#'+customer[4]+'"]').text()
				});
			}

			$.post(
				'/customers/json/customeraddresses',
				{
					customer_id: customer[0]
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('#customers-sales-create-form-send input[name="email"]').val(data.data.customer.email);
						$('#customers-sales-create-form-invoice input[name="email"]').val(data.data.customer.email);
						$('#customers-sales-create select[name="billing_address_id"] option[value!=""],#customers-sales-create select[name="shipping_address_id"] option[value!=""]').remove();
						for( var index in data.data.addresses ) {
							$('#customers-sales-create select[name="billing_address_id"],#customers-sales-create select[name="shipping_address_id"]').append('<option value="'+data.data.addresses[index].id+'">'+data.data.addresses[index].address1+'</option>');
						}
						$('#customers-sales-create select[name="billing_address_id"]').val(customer[1]);
						$('#customers-sales-create select[name="shipping_address_id"]').val(customer[2]);
						$('#customers-sales-create select[name="billing_address_id"]').focus();
					}
				},
				'json'
			);
		});

		var customersSaleCreateNextInput;
		function customersSaleCreateGoNextInput(input) {
			customersSaleCreateNextInput = input;
			setTimeout( function() {
				customersSaleCreateNextInput.focus();
			}, 10);
		}
		
		$('#customers-sales-create select[name="account"]').live('change' , function() {
			if( $('#customers-sales-create input[name="date_due"]') &&
				$('#customers-sales-create input[name="date_due"]').length ) {
				customersSaleCreateGoNextInput($('#customers-sales-create input[name="date_due"]').focus());
			} else {
				customersSaleCreateGoNextInput($('#customers-sales-create input[name="sale_number"]').focus());
			}
		});

		$('#customers-sales-create-form-lines select.account_id').live('change' , function() {
			customersSaleCreateGoNextInput($(this).closest('div.row').find('input.line-quantity').focus());
		});
		
		/**
		 * Customers / Customers
		 */
		
		/*
		if( $('#customers-customers-customers').length > 0 ) {
			$(window).scroll(function () { 
				if( ( $(window).height() + $(window).scrollTop() ) >= $('#customers-customers-customers').height() ) {
					if( $('#customers-customers-loadcustomers').is(':visible') ||
						$('#customers-customers-endcustomers').is(':visible') ) {
						// Do nothing - we're already loading...
					} else {
						loadMoreCustomers();
					}
				}
			});
		}
		*/
		
		// CUSTOMERS SEARCH
		$('#customers-customers-customers-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#customers-customers-customers').attr('rel','');
	 			$('#customers-customers-customers li.customer-customer:not(:first-child)').remove();
	 			$('#customers-customers-endcustomers').hide();
	 			$('#customers-customers-loadcustomers').show();
	 			loadMoreCustomers();
	 		}
		});

		$('#customers-customers-create input,#customers-customers-create select').live('change', function () {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});
		
		$('#customers-customers-create-form-address-save').click(function (e) {
			e.preventDefault();
			$form = $('#customers-customers-create-form');
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
			$template = $($('#customers-customers-create-form-addresses-address-template').html());
			if( $form.attr('rel') &&
				$form.attr('rel').length > 0 ) {
				$template = $('.customers-customers-create-form-addresses-address[rel="'+$form.attr('rel')+'"]');
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

			$('#customers-customers-create-form-address-canceledit').attr('disabled','disabled');
			if( $template.is(':visible') ) {
				$template.fadeIn();
			} else {
				$('#customers-customers-create-form-addresses .clear').before($template);
			}
			$template.slideDown();
			$form.attr('rel','');
			$form.find('input[rel="address1"]').focus();

			return false;
		});

		$('#customers-customers-create-form-cancel').click(function() {
			$form = $('#customers-customers-create-form');
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#customers-customers-create').slideUp(function() {
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
							$form.find('select[name="default_account_id"]').select2('data',{
								id: $form.find('select[name="default_account_id"]').attr('rel'), 
								text: $form.find('select[name="default_account_id"] option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
							});
						}
						$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());
						$form.find('.customers-customers-create-form-addresses-address').each(function() {
							$(this).remove();
						});
						$('#customers-customers-create').slideDown();
						GLOBAL_EDIT_FORM_ACTIVE = false;
					});
				}
			} else {
				$('#customers-customers-create').slideUp(function() {
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
						$form.find('select[name="default_account_id"]').select2('data',{
							id: $form.find('select[name="default_account_id"]').attr('rel'), 
							text: $form.find('select[name="default_account_id"] option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
						});
					}
					$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());
					$form.find('.customers-customers-create-form-addresses-address').each(function() {
						$(this).remove();
					});
					$('#customers-customers-create').slideDown();
				});
			}
		});
		
		function saveNewCustomer() {
			showPleaseWait();
			$.post(
				'/customers/json/customercreate',
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
							$form.find('select[name="default_account_id"]').select2('data',{
								id: $form.find('select[name="default_account_id"]').attr('rel'), 
								text: $form.find('select[name="default_account_id"] option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
							});
						}
						$form.find('select.address[rel="country"]').val($form.find('select.address[rel="country"] option[rel="default"]').val());
						$form.find('.customers-customers-create-form-addresses-address').each(function() {
							$(this).remove();
						});
						
						// Add Customer to top.
						$customer = $(data.data.customer.html);
						$customer.addClass('hidden');
						$('#customers-customers-customers li:first').after($customer);
						$customer.slideDown();
						
						// Remove "No Customers Found" if exists.
						$noCustomer = $('#customers-customers-customers li:last');
						if( $noCustomer.find('span').length == 0 ) {
							$noCustomer.slideUp(function() {
								$noCustomer.remove();
							});
						}

						rowElementsColorVisible($('#customers-customers-customers'));
					}
				},
				'json'
			);
		}

		$('#customers-customers-create-form-save').click(function(e) {
			e.preventDefault();
			var valid = true;
			$form = $('#customers-customers-create-form');
			$form.find('input.customer.required, select.customer.required').each(function() {
				if( $(this).val().length == 0 ) {
					valid = false;
				}
			});
			if( ! valid ) {
				showError("Please fill in all required fields:<br>First Name, Last Name, Email Address.");
				return;
			}
			var address = false;
			$form.find('input.address,select.address').each(function() {
				if( $(this).val() &&
					$(this).val().length &&
					$(this).attr('rel') != "country" &&
					$(this).attr('rel') != "default-billing" &&
					$(this).attr('rel') != "default-shipping" ) {
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
				$('#customers-customers-create-form-address-save').click();
			}
			
			var addressIndex = createCustomerIndexAddresses($form);
			// Addresses not required anymore.
			/*
			if( addressIndex == 0 ) {
				showConfirm("This customer has no addresses.  Without an address, you won't be able to create an sale for them.  Are you sure you don't want to add an address?","Yes, Create Anyways.","Cancel",saveNewCustomer);
			} else {
				saveNewCustomer();
			}
			*/
			saveNewCustomer();

		});
		
		$('.customers-customers-create-form-addresses-address a.edit').live('click',function() {
			$address = $(this).closest('.customers-customers-create-form-addresses-address');
			$form = $('#customers-customers-create-form');
			createCustomerIndexAddresses($form);
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
			$('#customers-customers-create-form-address-canceledit').attr('disabled',false);
		});

		$('#customers-customers-create-form-address-canceledit').click(function() {
			$(this).attr('disabled','disabled');
			$form = $('#customers-customers-create-form');
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
		 * Customer View
		 */
		
		$('#customers-customer-edit input,#customers-customer-edit select').focus(function() {
			if( $('.edit-buttons-placeholder:visible').length > 0 ) {
				$('.edit-buttons-placeholder').hide();
				$('.edit-buttons').show();
			}
			return true;
		});

		$('#customers-customer-edit input,#customers-customer-edit select').live('change', function () {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('#customers-customer-edit-cancel').click(function (e) {
			e.preventDefault();
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#customers-customer-edit input[type="text"],#customers-customer-edit select').each(function() {
						$(this).val($('#customers-customer-edit input[type="hidden"].'+($(this).attr('name'))).val());
					});
					$default_account = $('#customers-customer-edit select[name="default_account_id"]');
					if( $default_account.attr('rel') &&
						$default_account.attr('rel').length ) {
						$default_account.select2('data',{
							id: $default_account.attr('rel'), 
							text: $default_account.find('option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
						});
					} else {
						$default_account.select2('data',{});
					}
					$('.edit-buttons').hide();
					$('.edit-buttons-placeholder').show();
					GLOBAL_EDIT_FORM_ACTIVE = false;
				}
			} else {
				$('#customers-customer-edit input[type="text"],#customers-customer-edit select').each(function() {
					$(this).val($('#customers-customer-edit input[type="hidden"].'+($(this).attr('name'))).val());
				});
				$default_account = $('#customers-customer-edit select[name="default_account_id"]');
				if( $default_account.attr('rel') &&
					$default_account.attr('rel').length ) {
					$default_account.select2('data',{
						id: $default_account.attr('rel'), 
						text: $default_account.find('option[value="'+$form.find('select[name="default_account_id"]').attr('rel')+'"]').text()
					});
				} else {
					$default_account.select2('data',{});
				}
				$('.edit-buttons').hide();
				$('.edit-buttons-placeholder').show();
			}
			return false;
		});
		
		$('#customers-customer-edit-save').click(function() {
			showPleaseWait();
			$.post(
				'/customers/json/customerupdate',
				$('#customers-customer-edit input,#customers-customer-edit select').serialize(),
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

		$('#customers-customer-address-add').click(function() {
			$form = $('#customers-customer-address-form');
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
						$('#customers-customer-address-form-canceledit').hide();
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
				$form.find('select[name="country"]').val($form.find('select[name="country"] option[rel="default"]').val());
				$form.slideDown();
				$('#customers-customer-address-form-canceledit').hide();
			}
		});

		$('#customers-customer-address-form-canceledit').click(function() {
			$form = $('#customers-customer-address-form');
			$form.slideUp(function() {
				$form.attr('rel','');
				$form.find('input[type="text"],select').val('');
				$form.find('input[type="checkbox"]').each(function() {
					$(this).attr('checked',false);
					checkboxUpdate($(this));
				});
			});
		});

		$('#customers-customer-addresses-container .customer-address a.edit').live('click',function() {
			$address = $(this).closest('.customer-address');
			$form = $('#customers-customer-address-form');
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
				$('#customers-customer-address-form-canceledit').show();
			});
		});

		$('#customers-customer-address-form-save').click(function() {
			showPleaseWait();
			$form = $('#customers-customer-address-form');
			if( $form.attr('rel') &&
				$form.attr('rel').length > 0 ) {
				// Existing Address
				$.post(
					'/customers/json/customeraddressupdate',
					$form.find('input,select').serialize()+'&address_id='+$form.attr('rel'),
					function(data) {
						if( ! data.success ) {
							hidePleaseWait();
							showError(data.error);
						} else {
							if( data.data.address.default_shipping ) {
								$('#customers-customer-addresses-container .customer-address div.default-shipping').hide();
							}
							if( data.data.address.default_billing ) {
								$('#customers-customer-addresses-container .customer-address div.default-billing').hide();
							}
							$oldAddress = $('#customers-customer-addresses-container .customer-address[rel="'+$form.attr('rel')+'"]');
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
								$form.find('select[name="country"]').val($form.find('select[name="country"] option[rel="default"]').val());
							});
						}
					},
					'json'
				);
			} else {
				// New Address
				$.post(
					'/customers/json/customeraddresscreate',
					$form.find('input,select').serialize(),
					function(data){
						if( ! data.success ) {
							hidePleaseWait();
							showError(data.error);
						} else {
							if( data.data.address.default_shipping ) {
								$('#customers-customer-addresses-container .customer-address div.default-shipping').hide();
							}
							if( data.data.address.default_billing ) {
								$('#customers-customer-addresses-container .customer-address div.default-billing').hide();
							}
							$newAddress = $(data.data.address.html);
							$newAddress.addClass('hidden');
							if( $('#customers-customer-addresses-container div.clear:last').length > 0 ) {
								$('#customers-customer-addresses-container div.clear:last').before($newAddress);
							} else {
								$('#customers-customer-addresses-container').append($newAddress);
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
		 * Payments View
		 */
		
		$('#customers-payments-payments-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			$('#customers-payments-payments-search').attr('rel','0');
	 			paymentsSearch();
	 		}
		});

		$('#customers-payments-payments-paging a').live('click', function (e) {
			e.preventDefault();
			$('#customers-payments-payments-search').attr('rel',$(this).attr('rel'));
			paymentsSearch();
		});

		// TODO - FIX
		
		
		/**
		 * Create Payment
		 */
		
		$('#customers-payments-create input,#customers-payments-create select').live('change',function() {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		});

		$('#customers-payments-create select[name="writeoff_account_id"]').change(function() {
			createPaymentBatchUpdateTotals();
		});

		$('#customers-payments-create select[name="adjustment_account_id"]').change(function() {
			createPaymentBatchUpdateTotals();
		});

		$('#customers-payments-create input[name="adjustment_amount"]').change(function() {
			createPaymentBatchUpdateTotals();
		});

		$('#customers-payments-create-save').click(function(e) {
			e.preventDefault();
			showPleaseWait();
			if( $('#customers-payments-create').attr('rel') &&
				$('#customers-payments-create').attr('rel').length ) {
				// Update
				$.post(
					'/customers/json/paymentupdate',
					$('#customers-payments-create input, #customers-payments-create select').serialize()+'&payment_id='+$('#customers-payments-create').attr('rel'),
					function(data) {
						// Don't let the value sit in there in case of error.
						$('#customers-payments-create input[name="replace_transaction_id"]').val('');
						hidePleaseWait();
						if( data.success != 1 ) {
							if( data.data.duplicate_transaction ) {
								$('#customers-payments-duplicate-dialog').html('<p class="text-medium">'+data.error+'</p>').modaldialog({
									width: 700,
									dialogClass: 'generated-modal-dialog-success',
									buttons: {
										'Create New Payment': function() {
											$('#customers-payments-create input[name="replace_transaction_id"]').val('new');
											$(this).dialog("close");
											$('#customers-payments-create-save').click();
										},
										'Convert Transaction to Payment': function() {
											$('#customers-payments-create input[name="replace_transaction_id"]').val(data.data.duplicate_transaction.id);
											$(this).dialog("close");
											$('#customers-payments-create-save').click();
										}
									}
								});
							} else {
								showError(data.error);
							}
						} else {
							$oldPayment = $('#customers-payments-payments .customer-payment[rel="'+$('#customers-payments-create').attr('rel')+'"]');
							$newPayment = $(data.data.payment.html);
							$newPayment.addClass('hidden');
							$oldPayment.after($newPayment);
							$oldPayment.slideUp(function() {
								$(this).remove();
								$newPayment.slideDown();
							});
							$('#customers-payments-create').slideUp(function() {
								createPaymentBatchClearForm();
								$('#customers-payments-create').slideDown();
							});
						}
					},
					'json'
				);
				
			} else {
				// Create New
				$.post(
					'/customers/json/paymentcreate',
					$('#customers-payments-create input, #customers-payments-create select').serialize(),
					function(data) {
						// Don't let the value sit in there in case of error.
						$('#customers-payments-create input[name="replace_transaction_id"]').val('');
						hidePleaseWait();
						if( data.success != 1 ) {
							if( data.data.duplicate_transaction ) {
								$('#customers-payments-duplicate-dialog').html('<p class="text-medium">'+data.error+'</p>').modaldialog({
									width: 700,
									dialogClass: 'generated-modal-dialog-success',
									buttons: {
										'Create New Payment': function() {
											$('#customers-payments-create input[name="replace_transaction_id"]').val('new');
											$(this).dialog("close");
											$('#customers-payments-create-save').click();
										},
										'Convert Transaction to Payment': function() {
											$('#customers-payments-create input[name="replace_transaction_id"]').val(data.data.duplicate_transaction.id);
											$(this).dialog("close");
											$('#customers-payments-create-save').click();
										}
									}
								});
							} else {
								showError(data.error);
							}
						} else {
							$newPayment = $(data.data.payment.html);
							$newPayment.addClass('hidden');
							if( $('#customers-payments-payments .customer-payment:not(:first-child)').length < 5 &&
								$('#customers-payments-payments .customer-payment:last-child').find('.customer-payment-date').length > 0 ) {
								$('#customers-payments-payments .customer-payment:first-child').after($newPayment)
								$newPayment.slideDown(function() {
									rowElementsColorVisible($('#customers-payments-payments'));
								});
							} else {
								$lastPayment = $('#customers-payments-payments .customer-payment:last-child');
								$('#customers-payments-payments .customer-payment:first-child').after($newPayment)
								$lastPayment.slideUp(function() {
									$lastPayment.remove();
									$newPayment.slideDown(function() {
										rowElementsColorVisible($('#customers-payments-payments'));
									});
								});
							}
							createPaymentBatchClearForm();
						}
					},
					'json'
				);
			}
		});
		

		var delete_customer_payment_id = '';
		function deleteCustomerPayment() {
			showPleaseWait();
			$.post(
				'/customers/json/paymentdelete',
				{
					payment_id: delete_customer_payment_id
				},
				function(data) {
					hidePleaseWait();
					if( data.success != 1 ) {
						showError(data.error);
					} else {
						$('#customers-payments-payments .customer-payment[rel="'+$('#customers-payments-create').attr('rel')+'"]').slideUp(function() {
							$(this).remove();
							rowElementsColorVisible($('#customers-payments-payments'));
						});
						$('#customers-payments-create').slideUp(function() {
							createPaymentBatchClearForm();
							$('#customers-payments-create').slideDown();
						});
					}
				},
				'json'
			);
		}
		
		$('#customers-payments-create-delete').click(function(e) {
			e.preventDefault();
			delete_customer_payment_id = $('#customers-payments-create').attr('rel');
			showConfirm("Are you certain you want to delete this payment?","Yes, Delete.","No.",deleteCustomerPayment);
		});

		$('#customers-payments-create-edit').click(function(e) {
			e.preventDefault();
			$('#customers-payments-create-actions-showincluded').hide();
			$('#customers-payments-create-actions-showall').show();

			$('.customers-payments-create-actions-delete').hide();
			$('.customers-payments-create-actions-deleteplaceholder').show();
			$('.customers-payments-create-actions-edit').hide();
			$('.customers-payments-create-actions-save').show();
			$('#customers-payments-create-sales .customer-batchpayment:not(:first)').each(function() {
				$line = $(this);
				$line.find('.customer-batchpayment-numeric.balance').text(monetaryPrint(parseFloat($line.find('.customer-batchpayment-numeric.balance').attr('rel'))));
				$line.find('.customer-batchpayment-balancewriteoff').show();
				$writeoffBalance = $line.find('.customer-batchpayment-balancewriteoff input[type="checkbox"]');
				if( $writeoffBalance.val() != 0 ) {
					$writeoffBalance.prop('disabled',false);
					$writeoffBalance.prop('checked',true);
				}
				$line.find('.customer-batchpayment-add').show();
				$line.find('.customer-batchpayment-numeric.amount input[type="text"]').attr('readonly',false);
				$line.find('.customer-batchpayment-add input[type="checkbox"]').attr('checked','checked');
				checkboxUpdate($line.find('.customer-batchpayment-add input[type="checkbox"]'));
				checkboxUpdate($writeoffBalance);
			});
			createPaymentBatchEnableFields();
			createPaymentBatchUpdateTotals();
		});

		$('#customers-payments-create-cancel').click(function() {
			if( GLOBAL_EDIT_FORM_ACTIVE ) {
				if( confirm("Are you sure?  Your changes will be lost.") ) {
					$('#customers-payments-create').slideUp(function() {
						createPaymentBatchClearForm();
						$('#customers-payments-create').slideDown();
					});
				}
			} else {
				$('#customers-payments-create').slideUp(function() {
					createPaymentBatchClearForm();
					$('#customers-payments-create').slideDown();
				});
			}
		});

		$('#customers-payments-create-sales .customer-batchpayment:not(:first-child).selected .customer-batchpayment-add div.checkbox').live('click',function() {
			createPaymentBatchRemoveSale($(this).closest('.customer-batchpayment'));
		});

		$('#customers-payments-create-sales .customer-batchpayment:not(:first-child).selected .customer-batchpayment-balancewriteoff div.checkbox').live('click',function() {
			$checkbox = $(this).find('input[type="checkbox"]');
			if( ! $checkbox.is(":disabled") ) {
				if( $checkbox.is(":checked") ) {
					$checkbox.prop('checked',false);
					checkboxUpdate($checkbox);
					createPaymentBatchUpdateTotals();
				} else {
					$checkbox.prop('checked',true);
					checkboxUpdate($checkbox);
					createPaymentBatchUpdateTotals();
				}
			}
		});
		
		$('#customers-payments-create input[name="amount"]').change(function() {
			createPaymentBatchUpdateTotals();
		});

		$('#customers-payments-create-sales .customer-batchpayment.selected .customer-batchpayment-numeric.amount input[type="text"]').live('change',function() {
			createPaymentBatchUpdateTotals();
		});

		$('#customers-payments-create-actions-showall').click(function() {
			$(this).hide();
			$('#customers-payments-create-actions-showincluded').show();
			if( $('#customers-payments-create-sales li.customer-batchpayment:not(:first, .selected)').length == 0 ) {
				createPaymentBatchSearchSales();
			} else {
				$('#customers-payments-create-sales li.customer-batchpayment').slideDown();
			}
		});

		$('#customers-payments-create-actions-showincluded').click(function() {
			$(this).hide();
			$('#customers-payments-create-actions-showall').show();
			$('#customers-payments-create-sales li.customer-batchpayment:not(:first, .selected)').slideUp();
		});

		if( $('#customers-payments-create').length > 0 ) {
			createPaymentBatchUpdateTotals();
		}

		$('#customers-payments-create-actions-search').live('keyup',function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
	 		if(code == 13) {
	 			// First check if we have an exact match loaded.
	 			$searchTerm = $(this).val();
	 			$foundLine = false;
	 			$('#customers-payments-create-sales li.customer-batchpayment:not(:first-child,.selected)').each(function() {
	 				$saleNumber = $(this).find('.customer-batchpayment-sale').text();
	 				if( $saleNumber == $searchTerm || 
	 					$saleNumber.substring(1) == $searchTerm ) {
	 					$foundLine = $(this);
	 				}
	 			});
	 			if( ! $foundLine ) {
		 			$('#customers-payments-create-sales li.customer-batchpayment:not(:first, .selected)').remove();
		 			createPaymentBatchSearchSales();
		 		} else {
		 			$foundLine.find('.customer-batchpayment-add input[type="checkbox"]').attr('checked','checked');
					checkboxUpdate($foundLine.find('.customer-batchpayment-add input[type="checkbox"]'));
		 			createPaymentBatchAddSale($foundLine);
		 			$('#customers-payments-create-actions-search').val('').focus().blur().focus();
		 		}
	 		}
		});

		$('#customers-payments-create-sales li.customer-batchpayment:not(:first, .selected)').live('click',function() {
			$line = $(this);
			$line.find('.customer-batchpayment-add input[type="checkbox"]').attr('checked','checked');
			checkboxUpdate($line.find('.customer-batchpayment-add input[type="checkbox"]'));
			createPaymentBatchAddSale($line);
		});

		/**
		 * Payment Result Actions
		 */
		$('.customer-payment a.print').live('click',function(e) {
			e.preventDefault();
			printCustomerPayment($(this).closest('li.customer-payment').attr('rel'));
		});

		/*
		$('.customer-payment a.view').live('click',function() {
			loadPayment($(this).closest('.customer-payment').attr('rel'));
		});
		*/
	
		$('#customers-payments-payments .customer-payment .view').live('click',function(e) {
			// If we're on the sales page we AJAX - otherwise... return true.
			if( $('#customers-payments-create').length == 0 ) {
				return true;
			}
			e.preventDefault();
			$("html, body").animate(
				{
					scrollTop: 0
				},
				500
			);
			loadPayment($(this).closest('.customer-payment').attr('rel'));
			return false;
		});

	});



	// Ugly.
	// Fix with an increment check or something.
	// TODO: GENERALIZE
	function createCustomerIndexAddresses(form) {
		var addressIndex = 0;
		form.find('.customers-customers-create-form-addresses-address.indexed').each(function() {
			if( parseInt($(this).attr('rel')) >= addressIndex ) {
				addressIndex = parseInt($(this).attr('rel'))+1;
			}
		});
		// Index the addresses.
		form.find('.customers-customers-create-form-addresses-address:not(.indexed)').each(function() {
			$(this).find('input').each(function() {
				$(this).attr('name',$(this).attr('name')+'-'+addressIndex);
			});
			$(this).attr('rel',addressIndex);
			$(this).addClass('indexed');
			addressIndex++;
		});
		return addressIndex;
	}

	// TODO: GENERALIZE
	function createSaleIndexLines() {
		var lineIndex = 0;
		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line.indexed').each(function() {
			if( parseInt($(this).attr('rel')) >= lineIndex ) {
				lineIndex = parseInt($(this).attr('rel'))+1;
			}
		});
		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line:not(.indexed)').each(function() {
			$(this).find('input,select').each(function() {
				$(this).attr('name',$(this).attr('name')+'-'+lineIndex);
			});
			$(this).attr('rel',lineIndex);
			$(this).addClass('indexed');
			lineIndex++;
		});
		$form_tax_ids = $('#customers-sales-create input[name="form-tax_ids"]');
		$form_tax_ids.val('#');
		$('#customers-sales-create .form-tax:checked').each(function() {
			$form_tax_ids.val($form_tax_ids.val()+$(this).val()+'#');
		});
		return lineIndex;
	}

	function createSaleToggleTaxExempt() {
		var formTaxExempt = $('#customers-sales-create input[name="form_tax_exempt"]').is(':checked') ? true : false;
		if( formTaxExempt ) {
			$('#customers-sales-create .form-taxes .tax-exempt-reason').show();
			$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line').each(function() {
				$line = $(this);
				$line.find('input.tax-exempt').attr('checked','checked');
				checkboxUpdate($line.find('input.tax-exempt'));
			});
			$lineTemplate = $('#customers-sales-create-form-lines-line-template .customers-sales-create-form-lines-line');
			$lineTemplate.find('input.tax-exempt').attr('checked','checked');
			checkboxUpdate($lineTemplate.find('input.tax-exempt'));
		} else {
			$('#customers-sales-create .form-taxes .tax-exempt-reason').hide();
			$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line').each(function() {
				$line = $(this);
				$line.find('input.tax-exempt').attr('checked',false);
				checkboxUpdate($line.find('input.tax-exempt'));
			});
			$lineTemplate = $('#customers-sales-create-form-lines-line-template .customers-sales-create-form-lines-line');
			$lineTemplate.find('input.tax-exempt').attr('checked',false);
			checkboxUpdate($lineTemplate.find('input.tax-exempt'));
		}
	}

	function createSaleUpdateTotals() {
		var total = 0.00;
		var totalTaxes = 0.00;
		var formTaxExempt = $('#customers-sales-create input[name="form_tax_exempt"]').is(':checked') ? true : false;
		var taxes = {};

		$('#customers-sales-create .form-taxes .form-tax:checked').each(function () {
			$tax = $(this);
			taxes[$tax.val()] = {
				percent: parseFloat($tax.attr('data-tax-percent')),
				amount: 0.00,
				total: 0.00
			};
		});

		$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line').each(function() {
			$line = $(this);
			$quantity = $line.find('input.line-quantity');
			$price = $line.find('input.line-price');
			$total = $line.find('input.line-total');
			var lineTaxExempt = $line.find('input.tax-exempt').is(':checked') ? true : false;
			
			if( $price.val() &&
				$price.val().length ) {
				$price.val(convertCurrencyToNumber($price.val()).toFixed(2));
			}

			if( $quantity.val() &&
				$quantity.val().length ) {
				$quantity.val(Math.round(parseFloat($quantity.val())*1000) / 1000);
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

				$total.val(parseFloat(monetaryRound(parseFloat($price.val()) * parseFloat($quantity.val()))).toFixed(2));

				if( ! formTaxExempt && 
					! lineTaxExempt ) {
					for( tax_id in taxes ) {
						taxes[tax_id].amount += parseFloat($total.val());
					}
				}

				total = parseFloat(parseFloat(total) + parseFloat($total.val())).toFixed(2);
			} else {
				$total.val('0.00');
			}
			
		});

		for( tax_id in taxes ) {
			taxes[tax_id].total = monetaryRound(parseFloat(taxes[tax_id].percent) * parseFloat(taxes[tax_id].amount));
			
			totalTaxes = parseFloat(
				parseFloat(totalTaxes) +
				parseFloat(taxes[tax_id].total)
			);
		}


		$('#customers-sales-create-form-subtotal').text(monetaryPrint(parseFloat(total).toFixed(2)));
		$('#customers-sales-create-form-taxes').text(monetaryPrint(parseFloat(totalTaxes).toFixed(2)));
		total = parseFloat(parseFloat(total) + parseFloat(totalTaxes));
		$('#customers-sales-create-form-total').text(monetaryPrint(parseFloat(total).toFixed(2)));
		$('#customers-sales-create-form-balance').text(monetaryPrint(parseFloat(total).toFixed(2)));
		if( $('#customers-sales-create-form-balance').attr('rel') && 
			$('#customers-sales-create-form-balance').attr('rel').length ) {
			$('#customers-sales-create-form-balance').text(monetaryPrint(
				parseFloat(
					convertCurrencyToNumber($('#customers-sales-create-form-balance').text()) - 
					parseFloat($('#customers-sales-create-form-balance').attr('rel'))
				).toFixed(2)
			)); 
		}
	}

	function loadSale(sale_id,refund) {
		if( refund == undefined ) {
			var refund = false;
		}
		$('#customers-sales-create').slideUp();
		$('#customers-sales-create-form-send').hide();
		
		showPleaseWait();
		$.post(
			'/customers/json/saleload',
			{
				sale_id: sale_id
			},
			function(sale_data) {
				if( ! sale_data.success ) {
					hidePleaseWait();
					showError(sale_data.error);
				} else {
					// Grab the customer's addresses.
					if( refund ) {
						$('#customers-sales-create').attr('rel','R');
					} else {
						$('#customers-sales-create').attr('rel',sale_id);
					}
					$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line').remove();
					$('#customers-sales-create input[name="customer"]').select2('disable');
					$('#customers-sales-create select[name="account"]').select2('disable');
					$('#customers-sales-create input:not(.ezpz-hint,.send-form,.datepicker,.form-tax,.form-tax-exempt,.tax-exempt),#customers-sales-create select').each(function() {
						$(this).focus().val('').blur().attr('readonly','readonly');
					});
					$('#customers-sales-create input.form-tax, #customers-sales-create input.form-tax-exempt').each(function () {
						$(this).attr('disabled','disabled');
						$(this).attr('checked',false);
						checkboxUpdate($(this));
					});
			
					$('#customers-sales-create input.datepicker').each(function() {
						$(this).attr('readonly','readonly').datepicker("destroy");
					});
					$('#customers-sales-create div.select').addClass('disabled');
					
					$('#customers-sales-create .customer-sales-create-new-buttons').hide();
					$('#customers-sales-create .customer-sales-create-edit-buttons').show();

					$('#customers-sales-create-form-refund').attr('disabled','disabled');

					var required_tax_ids = [];
					for( i in sale_data.data.sale.taxes ) {
						if( required_tax_ids.indexOf(sale_data.data.sale.taxes[i].tax.id) < 0 ) {
							required_tax_ids.push(sale_data.data.sale.taxes[i].tax.id);
						}
					}
					createSaleUpdateTaxTemplateVisibility(required_tax_ids);
					
					for( i in sale_data.data.sale.taxes ) {
						$('#customers-sales-create .form-taxes input.form-tax[value="'+sale_data.data.sale.taxes[i].tax.id+'"]').attr('checked','checked');
					}

					if( sale_data.data.sale.tax_exempt ) {
						$('#customers-sales-create .form-taxes input.form-tax-exempt').attr('checked','checked');
					} else {
						$('#customers-sales-create .form-taxes input.form-tax-exempt').attr('checked',false);
					}

					if( sale_data.data.sale.tax_exempt_reason ) {
						$('#customers-sales-create .form-taxes input[name="form_tax_exempt_reason"]').val(sale_data.data.sale.tax_exempt_reason);
					} else {
						$('#customers-sales-create .form-taxes input[name="form_tax_exempt_reason"]').val('');
					}

					createSaleToggleTaxExempt();

					$newSaleLine = $($('#customers-sales-create-form-lines-line-template').html());
					$newSaleLine.find('input:not(.tax-exempt),select').each(function() {
						$(this).attr('disabled','disabled');
					});
					$newSaleLine.find('input.tax-exempt').attr('disabled','disabled');
					checkboxUpdate($newSaleLine.find('input.tax-exempt'));
					$('#customers-sales-create-form-lines').append($newSaleLine);
					$newSaleLine.find('input.line-description').autocomplete(saleDescriptionParams);
					$newSaleLine.find('div.select').addClass('disabled');

					$.post(
						'/customers/json/customeraddresses',
						{
							customer_id: sale_data.data.sale.customer.id
						},
						function(address_data) {
							if( address_data.success != 1 ) {
								hidePleaseWait();
								showError(data.error);
							} else {
								$('#customers-sales-create-form-send input[name="email"]').val(address_data.data.customer.email);
								$('#customers-sales-create-form-invoice input[name="email"]').val(address_data.data.customer.email);
								for( var index in address_data.data.addresses ) {
									$('#customers-sales-create select[name="billing_address_id"],#customers-sales-create select[name="shipping_address_id"]').append('<option value="'+address_data.data.addresses[index].id+'">'+address_data.data.addresses[index].address1+'</option>');
								}
								
								if( sale_data.data.sale.balance == 0 &&
									! sale_data.data.sale.refund_sale_id ) {
									$('#customers-sales-create-form-refund').attr('disabled',false);
								}


								if( customer_invoice_view ) {
									if( sale_data.data.sale.date_cancelled &&
										sale_data.data.sale.date_cancelled.length > 0 ) {
										$('#customers-sales-create-form-delete').attr('disabled', 'disabled');
										$('#customers-sales-create-form-edit').attr('disabled', 'disabled');
										$('#customers-sales-create-form-refund').attr('disabled', 'disabled');
										$('#customers-sales-create-form-onlysend').attr('disabled', 'disabled');
									} else {
										$('#customers-sales-create-form-delete').attr('disabled', false);
										$('#customers-sales-create-form-edit').attr('disabled', false);
										$('#customers-sales-create-form-onlysend').attr('disabled', false);
									}									
								} else {
									if( (
											sale_data.data.sale.date_billed &&
											sale_data.data.sale.date_billed.length > 0
										) ||
										(
											sale_data.data.sale.date_cancelled &&
											sale_data.data.sale.date_cancelled.length > 0
										) ) {
										$('#customers-sales-create-form-convertinvoice').attr('disabled', 'disabled');
										$('#customers-sales-create-form-edit').attr('disabled', 'disabled');
										$('#customers-sales-create-form-delete').attr('disabled', 'disabled');
									} else {
										$('#customers-sales-create-form-convertinvoice').attr('disabled', false);
										$('#customers-sales-create-form-edit').attr('disabled', false);
										$('#customers-sales-create-form-delete').attr('disabled', false);
									}
								}

								if( customer_invoice_view ) {
									if( refund ) {
										$('#customers-sales-create-title').text("Refund Invoice "+sale_data.data.sale.sale_number);
									} else {
										$('#customers-sales-create-title').text("Invoice "+sale_data.data.sale.sale_number);
									}
								} else {
									if( refund ) {
										$('#customers-sales-create-title').text("Refund Sale "+sale_data.data.sale.sale_number);
									} else {
										$('#customers-sales-create-title').text("Sale "+sale_data.data.sale.sale_number);
									}
								}
								
								
								if( sale_data.data.sale.payments.length > 0 ) {
									$('#customers-sales-create-status').html('<span class="text-bold">'+sale_data.data.sale.status+' - Payments: </span>');
									var first = true;
									for( i in sale_data.data.sale.payments ) {
										if( first ) {
											first = false;
										} else {
											$('#customers-sales-create-status').append(',');
										}
										$('#customers-sales-create-status').append(' <a href="/customers/payments/'+sale_data.data.sale.payments[i].id+'">'+sale_data.data.sale.payments[i].date+'</a>');
									}
								} else {
									$('#customers-sales-create-status').html('<span class="text-bold">'+sale_data.data.sale.status+'</span>');
								}
								
								
								if( refund ) {
									$('#customers-sales-create input[name="refund_sale_id"]').val(sale_data.data.sale.id);
								}

								// Fill in sale data.
								if( refund ) {
									$('#customers-sales-create input[name="date_created"]').val(dateYYYYMMDD());
								} else {
									$('#customers-sales-create input[name="date_created"]').val(sale_data.data.sale.date_created);
								}
								$('#customers-sales-create input[name="date_created"]').attr('readonly',true).datepicker("destroy");

								if( refund ) {
									$('#customers-sales-create input[name="date_billed"]').val(dateYYYYMMDD());
								} else {
									$('#customers-sales-create input[name="date_billed"]').val(sale_data.data.sale.date_billed);
								}

								if( sale_data.data.sale.customer.default_account ) {
									$('#customers-sales-create input[name="customer"]').select2("data", {id: sale_data.data.sale.customer.id+'#'+sale_data.data.sale.customer.default_billing_address_id+'#'+sale_data.data.sale.customer.default_shipping_address_id+'#'+sale_data.data.sale.customer.default_account.id+'#'+sale_data.data.sale.customer.default_account.terms, text: sale_data.data.sale.customer.display_name});
								} else {
									$('#customers-sales-create input[name="customer"]').select2("data", {id: sale_data.data.sale.customer.id+'#'+sale_data.data.sale.customer.default_billing_address_id+'#'+sale_data.data.sale.customer.default_shipping_address_id+'#', text: sale_data.data.sale.customer.display_name});
								}
								
								if( sale_data.data.sale.billing_address ) {
									$('#customers-sales-create select[name="billing_address_id"]').val(sale_data.data.sale.billing_address.id);
								} else {
									$('#customers-sales-create select[name="billing_address_id"]').val('');
								}

								if( sale_data.data.sale.shipping_address ) {
									$('#customers-sales-create select[name="shipping_address_id"]').val(sale_data.data.sale.shipping_address.id);
								} else {
									$('#customers-sales-create select[name="shipping_address_id"]').val('');
								}
								
								$('#customers-sales-create select[name="account"]').select2("data", {id: sale_data.data.sale.account.id+'#'+sale_data.data.sale.account.terms, text: sale_data.data.sale.account.name});

								if( refund ) {
									$('#customers-sales-create input[name="date_due"]').val('');
								} else {
									$('#customers-sales-create input[name="date_due"]').val(sale_data.data.sale.date_due);
								}
								if( refund ) {
									$('#customers-sales-create input[name="sale_number"]').val('R'+sale_data.data.sale.sale_number);
								} else {
									$('#customers-sales-create input[name="sale_number"]').val(sale_data.data.sale.sale_number);
								}

								$('#customers-sales-create input[name="quote_number"]').val(sale_data.data.sale.quote_number);
								$('#customers-sales-create input[name="po_number"]').val(sale_data.data.sale.po_number);
								
								// Line Items
								for( line_index in sale_data.data.sale.lines ) {
									$line = $('#customers-sales-create-form-lines .customers-sales-create-form-lines-line:last-child');
									$line.find('select[name="line-account_id"]').val(sale_data.data.sale.lines[line_index].account.id);
									if( refund &&
										$('#customers-sales-refund-default_account_id').val().length > 0 ) {
										$line.find('select[name="line-account_id"]').val($('#customers-sales-refund-default_account_id').val());
									}
									$line.find('input[name="line-description"]').val(sale_data.data.sale.lines[line_index].description);
									$line.find('input[name="line-quantity"]').val(sale_data.data.sale.lines[line_index].quantity);
									
									if( refund ) {
										$line.find('input[name="line-price"]').val(parseFloat(-1 * parseFloat(sale_data.data.sale.lines[line_index].amount)));
									} else {
										$line.find('input[name="line-price"]').val(sale_data.data.sale.lines[line_index].amount);
									}
									
									if( sale_data.data.sale.lines[line_index].tax_exempt ) {
										$line.find('input.tax-exempt').attr('checked','checked');
									} else {
										$line.find('input.tax-exempt').attr('checked',false);
									}

									checkboxUpdate($line.find('input.tax-exempt'));

									$newSaleLine = $($('#customers-sales-create-form-lines-line-template').html());
									$newSaleLine.find('input:not(.tax-exempt),select').each(function() {
										$(this).attr('disabled','disabled');
									});
									$newSaleLine.find('input.tax-exempt').attr('disabled','disabled');
									checkboxUpdate($newSaleLine.find('input.tax-exempt'));
									$newSaleLine.find('div.select').addClass('disabled');
									if( refund &&
										$('#customers-sales-refund-default_account_id').val().length > 0 ) {
										$newSaleLine.find('select[name="line-account_id"]').val($('#customers-sales-refund-default_account_id').val());
									}
									$('#customers-sales-create-form-lines').append($newSaleLine);
									$newSaleLine.find('input.line-description').autocomplete(saleDescriptionParams);
								}

								// Balance
								if( refund ) {
									$('#customers-sales-create-form-balance').attr('rel','');
								} else {															// 			POSITIVE VALUE 			  + 		NEGATIVE VALUE
									$('#customers-sales-create-form-balance').attr('rel',parseFloat(parseFloat(sale_data.data.sale.total) + parseFloat(sale_data.data.sale.balance)).toFixed(2));
								}
								
								createSaleUpdateTotals();

								hidePleaseWait();
								$('#customers-sales-create').slideDown(function() {
									$('#customers-sales-create input:not(.datepicker),#customers-sales-create select').each(function() {
										$(this).focus().blur();
									});
									$('#customers-sales-create-form-lines select.account_id').each(function () {
										$(this).accountDropdown();
										$(this).select2("disable");
									});

									if( refund ) {
										$('#customers-sales-create-form-lines select.account_id').each(function () {
											$(this).select2("enable");
										});
									}
								});

								if( refund ) {
									// Enable form fields.
									
									$('#customers-sales-create input[name="customer"]').select2('enable');
									$('#customers-sales-create select[name="account"]').select2('enable');
									
									$('#customers-sales-create input:not(.ezpz-hint,.datepicker),#customers-sales-create select').each(function() {
										$(this).attr('readonly',false).attr('disabled',false).focus().blur();
									});

									$('#customers-sales-create input.datepicker').each(function() {
										$(this).attr('readonly',false).attr('disabled',false).datepicker({dateFormat: "yy-mm-dd"});
									});

									$('#customers-sales-create input.tax-exempt').each(function() {
										$(this).attr('disabled',false);
										checkboxUpdate($(this));
									});

									$('#customers-sales-create .customer-sales-create-edit-buttons').hide();
									$('#customers-sales-create .customer-sales-create-new-buttons').show();
									
									// Disable fields that aren't edit-able.
									$('#customers-sales-create input[name="customer"]').select2('disable');
									$('#customers-sales-create select[name="account"]').select2('disable');
									$('#customers-sales-create select[name="billing_address_id"]').attr('disabled','disabled');
									$('#customers-sales-create select[name="shipping_address_id"]').attr('disabled','disabled');
									$('#customers-sales-create select[name="account"]').attr('readonly','readonly');
									$('#customers-sales-create input[name="sale_number"]').attr('disabled','disabled');
									
									/*
									$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line:not(:last-child)').each(function() {
										
										// $(this).find('input[type="checkbox"].line-tax').attr('disabled','disabled');
										// $(this).find('select[name="line-account_id"]').attr('disabled','disabled');
									});
									*/
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

	function createSaleUpdateTaxTemplateVisibility(required_tax_ids) {
		if( ! required_tax_ids ) {
			required_tax_ids = [];
		}

		$formTaxes = $('.form-taxes');

		var no_tax = true;
		if( $formTaxes.find('.visible-tax').length ) {
			no_tax = false;
		}
		
		$formTaxes.find('.hidden-tax').hide();
		$formTaxes.find('.no-tax').hide();
		$formTaxes.find('.hidden-tax').each(function () {
			var tax_id = $(this).find('input[type="checkbox"]').val();
			if( required_tax_ids.indexOf(tax_id) >= 0 ) {
				no_tax = false;
				$(this).show();
			}
		});

		if( no_tax ) {
			$formTaxes.find('.no-tax').show();
		}
	}

	function createSaleClearForm() {
		$('#customers-sales-create').slideUp(function() {
			GLOBAL_EDIT_FORM_ACTIVE = false;
			createSaleUpdateTaxTemplateVisibility();
			$('#customers-sales-create-form-send').hide();
			$('#customers-sales-create-title').text($('#customers-sales-create-title').attr('rel'));
			$('#customers-sales-create-status').html('<span class="text-bold">'+$('#customers-sales-create-status').attr('rel')+'</span>');
			$('#customers-sales-create').attr('rel','');
			$('#customers-sales-create input[name="refund_sale_id"]').val('');
			$('#customers-sales-create-form-lines .customers-sales-create-form-lines-line').remove();
			$('#customers-sales-create input:not(.ezpz-hint,.send-form,.datepicker,.form-tax,.form-tax-exempt),#customers-sales-create select').each(function() {
				if( $(this).hasClass('select2-offscreen') ) {
					$(this).val('');
				} else {
					$(this).attr('readonly',false).attr('disabled',false).focus().val('').blur();
				}
			});
			$('#customers-sales-create input.form-tax, #customers-sales-create input.form-tax-exempt').each(function () {
				$(this).attr('disabled',false);
				$(this).attr('checked',false);
				checkboxUpdate($(this));
			});
			$('#customers-sales-create input.datepicker').each(function() {
				$(this).attr('readonly',false).val(dateYYYYMMDD()).datepicker({dateFormat: "yy-mm-dd"});
			});

			$('#customers-sales-create input[name="customer"]').select2('data',{});
			$('#customers-sales-create input[name="customer"]').select2('enable');
			$('#customers-sales-create select[name="account"]').select2('data',{});
			$('#customers-sales-create select[name="account"]').attr('disabled',false).attr('readonly',false).select2('enable');
			
			if( $('#customers-sales-create select[name="account"]').attr('rel') &&
				$('#customers-sales-create select[name="account"]').attr('rel').length ) {
				$('#customers-sales-create select[name="account"]').select2('data',{
					id: $('#customers-sales-create select[name="account"]').attr('rel'),
					text: $('#customers-sales-create select[name="account"] option[value="'+$('#customers-sales-create select[name="account"]').attr('rel')+'"]').text()
				});
			}

			$('#customers-sales-create .select').removeClass('disabled');

			$('#customers-sales-create .customer-sales-create-new-buttons').show();
			$('#customers-sales-create .customer-sales-create-edit-buttons').hide();
			
			$('#customers-sales-create-form-refund').attr('disabled','disabled');

			$('#customers-sales-create-form-convertinvoice').attr('readonly',false);
			$('#customers-sales-create-form-edit').attr('readonly',false);
			$('#customers-sales-create-form-delete').attr('readonly',false);
			
			$('#customers-sales-create-form-subtotal').text(monetaryPrint(0.00)).attr('rel','');
			$('#customers-sales-create-form-taxes').text(monetaryPrint(0.00)).attr('rel','');
			$('#customers-sales-create-form-total').text(monetaryPrint(0.00)).attr('rel','');
			$('#customers-sales-create-form-balance').text(monetaryPrint(0.00)).attr('rel','');

			$newSaleLine = $($('#customers-sales-create-form-lines-line-template').html());
			$('#customers-sales-create-form-lines').append($newSaleLine);
			$newSaleLine.find('input.line-description').autocomplete(saleDescriptionParams);
			$('#customers-sales-create').slideDown(function() {
				$('#customers-sales-create .select2-container').removeClass('select2-container-active');
				$newSaleLine.find('select.account_id').accountDropdown();

			});
		});
	}

	function loadMoreSales() {
		var last_sale_id = $('#customers-sales-sales li.customer-sale:last').attr('rel');
		var last_sale_date = $('#customers-sales-sales li.customer-sale:last span.customer-sale-date').text();
		var search_terms = $('#customers-sales-sales-search').val();
		var search_customer_id = $('#customers-sales-sales-customer_id').val();
		var search_past_due = $('#customers-sales-sales-pastdue').val();
		var search_invoiced = $('#customers-sales-search-invoiced').val();
		var invoice_view = 0;
		if( search_invoiced == "1" ) {
			invoice_view = 1;
		}

		if( ! last_sale_id ||
			last_sale_id == undefined ) {
			last_sale_id = '';
		}
		if( ! last_sale_date ||
			last_sale_date == undefined ) {
			last_sale_date = '';
		}
		if( ! search_terms ||
			search_terms == undefined ) {
			search_terms = '';
		}
		if( ! search_customer_id ||
			search_customer_id == undefined ) {
			search_customer_id = '';
		}
		if( ! search_past_due ||
			search_past_due == undefined ) {
			search_past_due = '0';
		}

		$('#customers-sales-loadsales').show();
		$('#customers-sales-loadsales').find('.spinme').spin();
		$.post(
			'/customers/json/salesloadmore',
			{
				last_sale_id: last_sale_id,
				last_sale_date: last_sale_date,
				search_terms: search_terms,
				search_past_due: search_past_due,
				search_invoiced: search_invoiced,
				count: 20,
				invoice_view: invoice_view
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more sales: "+data.error);
				} else {
					if( data.data.sales.length == 0 ) {
						$('#customers-sales-endsales').show();
						$('#customers-sales-loadsales').hide();
					} else {
						for( index in data.data.sales ) {
							$('#customers-sales-sales > ul').append(data.data.sales[index].html);
						}
						$('#customers-sales-loadsales').hide();
						rowElementsColorVisible($('#customers-sales-sales'));
					}
				}
			},
			'json'
		);
	}


	function loadMoreCustomers() {
		var last_customer_id = $('#customers-customers-customers li.customer-customer:last').attr('rel');
		var last_page = $('#customers-customers-customers').attr('rel');
		var search_terms = $('#customers-customers-customers-search').val();

		if( ! last_customer_id ||
			last_customer_id == undefined ) {
			last_customer_id = '';
		}

		if( ! last_page ||
			last_page == undefined ) {
			last_page = 0;
		}

		if( ! search_terms ||
			search_terms == undefined ) {
			search_terms = '';
		}

		$('#customers-customers-loadcustomers').show();
		$('#customers-customers-loadcustomers').find('.spinme').spin();
		$.post(
			'/customers/json/customersloadmore',
			{
				last_customer_id: last_customer_id,
				last_page: last_page,
				search_terms: search_terms
			},
			function(data) {
				if( data.success != 1 ) {
					showError("Error loading more customers: "+data.error);
				} else {
					$('#customers-customers-customers').attr('rel',data.data.last_page);
					if( data.data.customers.length == 0 ) {
						$('#customers-customers-endcustomers').show();
						$('#customers-customers-loadcustomers').hide();
					} else {
						for( index in data.data.customers ) {
							$('#customers-customers-customers > ul').append(data.data.customers[index].html);
						}
						$('#customers-customers-loadcustomers').hide();
						rowElementsColorVisible($('#customers-customers-customers'));
					}
				}
			},
			'json'
		);

	}

	function createPaymentBatchAddSale($line) {
		$line.slideUp(function() {
			$balance = parseFloat($line.find('.customer-batchpayment-numeric.balance').attr('rel')).toFixed(2);
			$line.find('.customer-batchpayment-numeric.amount').find('input[type="text"]').val($balance);
			$line.find('.customer-batchpayment-numeric.amount').find('input[type="text"]').attr('readonly',false);
			$('#customers-payments-create-sales .customer-batchpayment:first').after($line);
			$line.addClass('selected');
			$line.slideDown(function() {
				rowElementsColorVisible($('#customers-payments-create-sales'));
			});
			createPaymentBatchUpdateTotals();
		});
	}

	function createPaymentBatchRemoveSale($line) {
		$line.slideUp(function() {
			$balance = parseFloat($line.find('.customer-batchpayment-numeric.balance').attr('rel')).toFixed(2);
			$line.find('.customer-batchpayment-numeric.amount').find('input[type="text"]').val('0.00');
			$line.find('.customer-batchpayment-numeric.amount').find('input[type="text"]').attr('readonly',true);
			$line.find('.customer-batchpayment-numeric.balancenew').text(monetaryPrint(parseFloat($balance)));
			$line.removeClass('selected');
			// Where do we add this?
			if( $('#customers-payments-create-sales .customer-batchpayment.selected:last').length == 0 ) {
				$('#customers-payments-create-sales .customer-batchpayment:first').after($line);
			} else {
				$('#customers-payments-create-sales .customer-batchpayment.selected:last').after($line);
			}
			$line.find('.customer-batchpayment-balancewriteoff input[type="checkbox"]').prop('checked',false);
			$line.find('.customer-batchpayment-balancewriteoff input[type="checkbox"]').prop('disabled',true);
			checkboxUpdate($line.find('.customer-batchpayment-balancewriteoff input[type="checkbox"]'));
			$line.find('.customer-batchpayment-add input[type="checkbox"]').prop('checked',false);
			checkboxUpdate($line.find('.customer-batchpayment-add input[type="checkbox"]'));
			$line.slideDown(function() {
				rowElementsColorVisible($('#customers-payments-create-sales'));
				createPaymentBatchUpdateTotals();
			});
		});
	}

	function createPaymentBatchUpdateTotals() {
		$amount = $('#customers-payments-create input[name="amount"]');
		if( ! $amount.val() ||
			$amount.val().length == 0 ) {
			$amount.val('0.00');
		}
		$amount.val(parseFloat($amount.val()).toFixed(2));

		$total = 0.00;
		// $balance = 0.00;
		$writeoff = 0.00;
		
		$adjustment = parseFloat($('#customers-payments-create input[name="adjustment_amount"]').val());
		$adjustment = $adjustment ? $adjustment : 0.00;
		
		$('#customers-payments-create-sales .customer-batchpayment.selected').each(function() {
			$line = $(this);
			
			$lineAmount = $line.find('.customer-batchpayment-numeric.amount').find('input[type="text"]');
			if( $lineAmount.val().length == 0 ) {
				$lineAmount.val('0.00');
			}
			$lineAmount.val(parseFloat(convertCurrencyToNumber($lineAmount.val())).toFixed(2));
			
			$lineSaleBalance = parseFloat(parseFloat($line.find('.customer-batchpayment-numeric.balance').attr('rel')).toFixed(2));

			// $balance += parseFloat($lineSaleBalance).toFixed(2);

			$total += parseFloat($lineAmount.val());
			
			$lineBalance = parseFloat(
				parseFloat(parseFloat($line.find('.customer-batchpayment-numeric.balance').attr('rel')).toFixed(2)) - 
				parseFloat(parseFloat($lineAmount.val()).toFixed(2))
			);

			$lineWriteoff = $line.find('.customer-batchpayment-balancewriteoff input[type="checkbox"]');
			if( parseFloat($lineBalance).toFixed(2) != "0.00" ) {
				$lineWriteoff.attr('disabled',false);
				checkboxUpdate($lineWriteoff);
			} else {
				$lineWriteoff.attr('disabled','disabled');
				checkboxUpdate($lineWriteoff);
			}

			if( $lineWriteoff.is(':checked') ) {
				$lineWriteoff.val(parseFloat($lineBalance).toFixed(2));
				$writeoff -= parseFloat(parseFloat($lineBalance).toFixed(2));
				$lineBalance = 0.00;
			}

			$line.find('.customer-batchpayment-numeric.balancenew').text(
				monetaryPrint(
					monetaryRound(
						$lineBalance
					)
				)
			);
		});
		
		$lineTotal = $total;
		$total += $adjustment;

		$('#customers-payments-create input[name="sale_total"]').val(parseFloat(parseFloat($lineTotal) - parseFloat($writeoff)).toFixed(2));
		$('#customers-payments-create input[name="amount"]').val(parseFloat($total).toFixed(2));
		$('#customers-payments-create input[name="writeoff_amount"]').val(parseFloat($writeoff).toFixed(2));
		$('#customers-payments-create input[name="adjustment_amount"]').val(parseFloat($adjustment).toFixed(2));

		if( $writeoff != 0.00 &&
			( 
				$('#customers-payments-create select[name="writeoff_account_id"]').val() == undefined || 
				$('#customers-payments-create select[name="writeoff_account_id"]').val().length == 0 
			) ) {
			$('#customers-payments-create-save').attr('disabled',true);
			$('#customers-payments-create select[name="writeoff_account_id"]').closest('span').find('div.select2-container').addClass('unclassified');
		} else {
			$('#customers-payments-create-save').attr('disabled',false);
			$('#customers-payments-create select[name="writeoff_account_id"]').closest('span').find('div.select2-container').removeClass('unclassified');
		}

		if( $adjustment != 0.00 &&
			( 
				$('#customers-payments-create select[name="adjustment_account_id"]').val() == undefined || 
				$('#customers-payments-create select[name="adjustment_account_id"]').val().length == 0 
			) ) {
			$('#customers-payments-create-save').attr('disabled',true);
			$('#customers-payments-create select[name="adjustment_account_id"]').closest('span').find('div.select2-container').addClass('unclassified');
		} else {
			$('#customers-payments-create-save').attr('disabled',false);
			$('#customers-payments-create select[name="adjustment_account_id"]').closest('span').find('div.select2-container').removeClass('unclassified');
		}

		if( $total != 0.00 ||
			$writeoff != 0.00 ) {
			GLOBAL_EDIT_FORM_ACTIVE = true;
		}
	}

	function createPaymentBatchClearForm(dontLoadSales) {
		if( ! dontLoadSales ||
			dontLoadSales == undefined ) {
			dontLoadSales = false;
		}
		GLOBAL_EDIT_FORM_ACTIVE = false;
		$('#customers-payments-create input[name="replace_transaction_id"]').val('');
		$('#customers-payments-create').attr('rel','');
		createPaymentBatchEnableFields();
		$('#customers-payments-create-sales .customer-batchpayment:not(:first)').remove();
		
		$('#customers-payments-create select[name="deposit_account_id"]').select2('data',{});
		if( $('#customers-payments-create select[name="deposit_account_id"]').attr('rel') &&
			$('#customers-payments-create select[name="deposit_account_id"]').attr('rel').length > 0 ) {
			$('#customers-payments-create select[name="deposit_account_id"]').select2('data',{
				id: $('#customers-payments-create select[name="deposit_account_id"]').attr('rel'),
				text: $('#customers-payments-create select[name="deposit_account_id"] option[value="'+$('#customers-payments-create select[name="deposit_account_id"]').attr('rel')+'"]').text()
			});
		}
		
		$('#customers-payments-create select[name="writeoff_account_id"]').select2('data',{
			id: '',
			text: $('#customers-payments-create select[name="writeoff_account_id"] option[value=""]').text()
		});
		$('#customers-payments-create select[name="adjustment_account_id"]').select2('data',{
			id: '',
			text: $('#customers-payments-create select[name="adjustment_account_id"] option[value=""]').text()
		});

		$('#customers-payments-create input[name="date"]').val(dateYYYYMMDD());
		$('#customers-payments-create input[name="sale_total"]').val('0.00');
		$('#customers-payments-create input[name="writeoff_amount"]').val('0.00');
		$('#customers-payments-create input[name="adjustment_amount"]').val('0.00');
		$('#customers-payments-create input[name="amount"]').val('0.00');
		
		// Reset buttons
		$('.customers-payments-create-actions-delete').hide();
		$('.customers-payments-create-actions-edit').hide();
		$('.customers-payments-create-actions-save').show();
		$('.customers-payments-create-actions-deleteplaceholder').show();
		
		$('#customers-payments-create-actions-search').val('');
		if( dontLoadSales == false ) {
			createPaymentBatchSearchSales(true);
		}
	}

	function createPaymentBatchSearchSales(balance) {
		if( balance ) {
			balance = 1;
		} else {
			balance = 0;
		}
		showPleaseWait();
		$.post(
			'/customers/json/customersales',
			{
				search_terms: $('#customers-payments-create-actions-search').val(),
				oldest_first: '1',
				has_balance: balance
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					for( index in data.data.sales ) {
						if( $('#customers-payments-create-sales .customer-batchpayment[rel="'+data.data.sales[index].id+'"]').length == 0 ) {
							$newSaleFormLine = $(data.data.sales[index].html);
							$newSaleFormLine.addClass('hidden');
							$('#customers-payments-create-sales .customer-batchpayment:last').after($newSaleFormLine);
							// $newSaleFormLine.slideDown();
						}
					}
					// If we added only one - and it matches the search term
					if( $('#customers-payments-create-sales li.customer-batchpayment:not(:first, .selected)').length == 1 ) {
						$singleLine = $('#customers-payments-create-sales li.customer-batchpayment:not(:first, .selected)');
						if( $singleLine.attr('rel') == $('#customers-payments-create-actions-search').val().trim() ||
							$singleLine.find('.customer-batchpayment-sale').text().substring(1) == $('#customers-payments-create-actions-search').val().trim().toLowerCase() ) {
							$singleLine.find('.customer-batchpayment-add input[type="checkbox"]').attr('checked','checked');
							checkboxUpdate($singleLine.find('.customer-batchpayment-add input[type="checkbox"]'));
							createPaymentBatchAddSale($singleLine);
							$('#customers-payments-create-actions-search').val('');
							$('#customers-payments-create-actions-search').focus();
						} else {
							$singleLine.slideDown(function() {
								rowElementsColorVisible($('#customers-payments-create-sales'));
							});
						}
					} else {
						// Slide them all down.
						$('#customers-payments-create-sales li.customer-batchpayment:not(:last)').slideDown();
						$('#customers-payments-create-sales li.customer-batchpayment:last').slideDown(function() {
							rowElementsColorVisible($('#customers-payments-create-sales'));
						});
					}
				}
			},
			'json'
		);
	}

	function loadPayment(id) {
		showPleaseWait();
		$('#customers-payments-create').slideUp();
		$.post(
			'/customers/json/paymentload',
			{
				payment_id: id
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					createPaymentBatchClearForm(true);
					$('#customers-payments-create').attr('rel',data.data.payment.id);
					// Assign appropriate values to batch payment form and make everything readonly.
					$('#customers-payments-create input[name="date"]').val(data.data.payment.date);
					$('#customers-payments-create input[name="sale_total"]').val(data.data.payment.amount);
					$('#customers-payments-create input[name="amount"]').val(parseFloat(-1 * data.data.payment.deposit_transaction.amount).toFixed(2));
					
					$('#customers-payments-create select[name="deposit_account_id"]').select2('data',{
						id: data.data.payment.deposit_transaction.account.id,
						text: data.data.payment.deposit_transaction.account.name
					});

					if( data.data.payment.writeoff_transaction ) {
						$('#customers-payments-create input[name="sale_total"]').val(parseFloat(
							parseFloat($('#customers-payments-create input[name="sale_total"]').val()) -
							data.data.payment.writeoff_transaction.amount
						).toFixed(2));

						$('#customers-payments-create input[name="writeoff_amount"]').val(parseFloat(
							data.data.payment.writeoff_transaction.amount
						).toFixed(2));
						
						$('#customers-payments-create select[name="writeoff_account_id"]').select2('data',{
							id: data.data.payment.writeoff_transaction.account.id,
							text: data.data.payment.writeoff_transaction.account.name
						});
					}

					if( data.data.payment.adjustment_transaction ) {
						$('#customers-payments-create input[name="sale_total"]').val(parseFloat(
							parseFloat($('#customers-payments-create input[name="sale_total"]').val()) -
							data.data.payment.adjustment_transaction.amount
						).toFixed(2));

						$('#customers-payments-create input[name="adjustment_amount"]').val(parseFloat(
							data.data.payment.adjustment_transaction.amount
						).toFixed(2));
						
						$('#customers-payments-create select[name="adjustment_account_id"]').select2('data',{
							id: data.data.payment.adjustment_transaction.account.id,
							text: data.data.payment.adjustment_transaction.account.name
						});
					}

					for( index in data.data.payment.sale_payments ) {
						$line = $(data.data.payment.sale_payments[index].html)
						$line.addClass('selected');
						$('#customers-payments-create-sales .customer-batchpayment:last').after($line);
					}

					createPaymentBatchDisableFields();
					
					// Adjust Buttons
					$('.customers-payments-create-actions-save').hide();
					$('.customers-payments-create-actions-deleteplaceholder').hide();
					$('.customers-payments-create-actions-delete').show();
					$('.customers-payments-create-actions-edit').show();
					
					$('#customers-payments-create').slideDown(function() {
						rowElementsColorVisible($('#customers-payments-create-sales'));
					});
				}
			},
			'json'
		);
	}

	function createPaymentBatchDisableFields() {
		$('#customers-payments-create-actions').hide();
		$('#customers-payments-create input[name="date"]').attr('readonly',true).datepicker("destroy");
		$('#customers-payments-create input[name="amount"]').attr('readonly',true);
		$('#customers-payments-create select[name="deposit_account_id"]').select2('disable');
		$('#customers-payments-create select[name="writeoff_account_id"]').select2('disable');
		$('#customers-payments-create select[name="adjustment_account_id"]').select2('disable');
		$('#customers-payments-create input[name="adjustment_amount"]').attr('readonly',true);
	}

	function createPaymentBatchEnableFields() {
		$('#customers-payments-create-actions').slideDown();
		$('#customers-payments-create input[name="date"]').attr('readonly',false).datepicker({dateFormat: "yy-mm-dd"});
		$('#customers-payments-create input[name="amount"]').attr('readonly',true);
		$('#customers-payments-create select[name="deposit_account_id"]').select2('enable');
		$('#customers-payments-create select[name="writeoff_account_id"]').select2('enable');
		$('#customers-payments-create select[name="adjustment_account_id"]').select2('enable');
		$('#customers-payments-create input[name="adjustment_amount"]').attr('readonly',false);
	}

	function paymentsSearch() {
		showPleaseWait();
		$('#customers-payments-payments ul li:not(:first)').remove();
		$.post(
			'/customers/json/paymentsearch',
			{
				search_terms: $('#customers-payments-payments-search').val(),
				count: 5,
				page: $('#customers-payments-payments-search').attr('rel')
			},
			function(data) {
				hidePleaseWait();
				if( data.success != 1 ) {
					showError(data.error);
				} else {
					for( index in data.data.payments ) {
						$payment = $(data.data.payments[index].html);
						$payment.addClass('hidden');
						$('#customers-payments-payments .customer-payment:last').after($payment);
					}
					generateSearchPaging($('#customers-payments-payments-paging'), data.data, 5);
					$('#customers-payments-payments-paging').html('|&nbsp;&nbsp;'+$('#customers-payments-payments-paging').html());
					$('#customers-payments-payments .customer-payment').slideDown(function() {
						rowElementsColorVisible($('#customers-payments-payments'));
					});
				}
			},
			'json'
		);
	}

	var popupWindow;
	function printCustomerSale(id) {
		popupWindow = popupWindowLoad('/print/customersale/'+id);
		$(popupWindow.document).ready( function () {
			setTimeout( function () { popupWindow.print(); } , 1000 );
		});
	}

	function printCustomerPayment(id) {
		popupWindow = popupWindowLoad('/print/customerpayment/'+id);
		$(popupWindow.document).ready( function () {
			setTimeout( function () { popupWindow.print(); } , 1000 );
		});
	}

}