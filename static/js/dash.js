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

if ( document.body.className.match(new RegExp('(\\s|^)dash(\\s|$)')) !== null ) {

	/**
	 * Javascript for pages related to dash/
	 */

	$(function() {

		// Dash Index
		if( $('#dash-index-chart-incomeexpense').length > 0 ) {
			$('input.dash-index-chart-incomeexpense-date').datepicker({
				dateFormat: 'yy-mm-dd'
			});
			dashIncomeExpenseRefresh();
			$('input.dash-index-chart-incomeexpense-date').live('change',function() {
				dashIncomeExpenseRefresh();
			});
		}

		// Dash Index
		if( $('#dash-index-chart-income').length > 0 ) {
			$('input.dash-index-chart-income-date').datepicker({
				dateFormat: 'yy-mm-dd'
			});
			dashIncomeRefresh();
			$('input.dash-index-chart-income-date').live('change',function() {
				dashIncomeRefresh();
			});
		}

		if( $('#dash-index-chart-expenses').length > 0 ) {
			dashExpensesRefresh();
			$('.dash-index-chart-expenses-date select').live('change',function() {
				dashExpensesRefresh();
			});
		}

		$('.dash-index-show-hidden-lines').click(function (e) {
			e.preventDefault();
			$lines = $(this).closest('.dash-index-chart-formlist').find('.line.hidden');
			$(this).closest('.line').slideUp(function() {
				$lines.show();
			});
		});

		// Close Books - Such a wonky use case.
		
		$('.dash-index-close-books-include_account_ids').live('change', function (e) {
			$currentSelect = $(this);
			$currentSelectDiv = $currentSelect.closest('div.select');
			var createNew = true;
			$('select.dash-index-close-books-include_account_ids').each(function () {
				if( ! $(this).val() ||
					! $(this).val().length ) {
					createNew = false;
				}
			});
			if( createNew ) {
				$newSelectDiv = $currentSelectDiv.clone();
				$newSelectDiv.find('select').val('');
				$('.dash-index-close-books-include_accounts').append($newSelectDiv);
			}
		});

		// In case an account is selected by default...
		$('.dash-index-close-books-include_account_ids').trigger('change');


		$('#dash-index-close-books-submit').click(function (e) {
			e.preventDefault();
			if( confirm("Are you sure you want to close your books?  Click 'OK' to continue.") ) {
				showPleaseWait();
				$message = $(this).closest('.dash-index-message');
				var include_account_ids = ',';
				$('.dash-index-close-books-include_account_ids').each(function () {
					if( $(this).val() && 
						$(this).val().length ) {
						include_account_ids += $(this).val()+',';
					}
				});

				$message.find('input[name="include_account_ids"]').val(include_account_ids);
				$.post(
					'/dash/json/closebooks',
					$message.find('input, select').serialize(),
					function (data) {
						hidePleaseWait();
						if( ! data.success ) {
							showError(data.error);
						} else {
							$newMessage = $(data.data.message);
							$newMessage.addClass('hidden');
							$message.after($newMessage);
							$message.slideUp(function () {
								$newMessage.slideDown();
							});
						}
					},
					'json'
				);
			}
		});

		// Print Report
		$('a.report-print').click(function() {
			$html = $('#report-content').html();
			$window = popupWindowLoad('');
			$window.document.clear();
			$window.document.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><body style="font-family: Helvetica, Verdana, Arial, sans-serif;">');
			$window.document.write($html);
			$window.document.write('<style> a { color: black; text-decoration: none; } </style>');
			$window.document.write('</body></html>');
			$window.print();
		});
		

		// Balance Sheet
		$('input.report-balance-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-balance-date').live('change', function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		$('.checkbox.report-balance-zero-toggle').live('click', function() {
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
				$('.report-zero').hide();
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$('.report-zero').show();
			}
		});

		if( $('.checkbox.report-balance-zero-toggle').length > 0 &&
			$('.checkbox.report-balance-zero-toggle input[type="checkbox"]').is(':checked') ) {
			$('.report-zero').show();
		}
		

		// Income Statement
		$('input.report-income-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-income-date').live('change', function() {
			$date_start = $('input[name="date_start"]');
			$date_start.removeClass('unclassified');
			$date_end = $('input[name="date_end"]');
			$date_end.removeClass('unclassified');

			if( $date_start.datepicker("getDate").getTime() != $date_end.datepicker("getDate").getTime() &&
				$date_start.datepicker("getDate").getTime() > $date_end.datepicker("getDate").getTime() ) {
				holdSaleReport = true;
				if( $(this).attr('name') == "date_start" ) {
					$date_end.datepicker("setDate", $date_start.val());
					$date_end.addClass('unclassified');
				} else {
					$date_start.datepicker("setDate", $date_end.val());
					$date_start.addClass('unclassified');
				}
			} else {
				showPleaseWait();
				$(this).closest('form').submit();
			}
		});

		$('.checkbox.report-income-zero-toggle').live('click', function() {
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
				$('.report-zero').hide();
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$('.report-zero').show();
			}
		});

		if( $('.checkbox.report-income-zero-toggle').length > 0 &&
			$('.checkbox.report-income-zero-toggle input[type="checkbox"]').is(':checked') ) {
			$('.report-zero').show();
		}

		// General Ledger
		$('input.report-ledger-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-ledger-date').live('change', function() {
			$date_start = $('input[name="date_start"]');
			$date_start.removeClass('unclassified');
			$date_end = $('input[name="date_end"]');
			$date_end.removeClass('unclassified');

			if( $date_start.datepicker("getDate").getTime() != $date_end.datepicker("getDate").getTime() &&
				$date_start.datepicker("getDate").getTime() > $date_end.datepicker("getDate").getTime() ) {
				holdSaleReport = true;
				if( $(this).attr('name') == "date_start" ) {
					$date_end.datepicker("setDate", $date_start.val());
					$date_end.addClass('unclassified');
				} else {
					$date_start.datepicker("setDate", $date_end.val());
					$date_start.addClass('unclassified');
				}
			} else {
				showPleaseWait();
				$(this).closest('form').submit();
			}
		});

		$('select.report-ledger-account').live('change', function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		// Sales
		$('input.report-sales-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		var holdSaleReport = false;

		$('input.report-sales-date,select.report-sales-interval').live('change', function() {
			
			$date_start = $('input[name="date_start"]');
			$date_start.removeClass('unclassified');
			$date_end = $('input[name="date_end"]');
			$date_end.removeClass('unclassified');

			if( $date_start.datepicker("getDate").getTime() != $date_end.datepicker("getDate").getTime() &&
				$date_start.datepicker("getDate").getTime() > $date_end.datepicker("getDate").getTime() ) {
				holdSaleReport = true;
				if( $(this).attr('name') == "date_start" ) {
					$date_end.datepicker("setDate", $date_start.val());
					$date_end.addClass('unclassified');
				} else {
					$date_start.datepicker("setDate", $date_end.val());
					$date_start.addClass('unclassified');
				}
			} else {
				showPleaseWait();
				$(this).closest('form').submit();
			}
		});

		// Budget
		$('select.report-budget-select').live('change', function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		$('.checkbox.report-budget-zero-toggle').live('click', function() {
			$checkbox = $(this).find('input[type="checkbox"]');
			if( $checkbox.is(':checked') ) {
				$checkbox.attr('checked',false);
				checkboxUpdate($checkbox);
				$('.report-zero').hide();
			} else {
				$checkbox.attr('checked','checked');
				checkboxUpdate($checkbox);
				$('.report-zero').show();
			}
			colorBudgetLines();
		});

		if( $('.checkbox.report-budget-zero-toggle').length > 0 &&
			$('.checkbox.report-budget-zero-toggle input[type="checkbox"]').is(':checked') ) {
			$('.report-zero').show();
		}

		colorBudgetLines();

		// Payables
		$('input#report-payables-vendor').select2({
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
						var id = data.data.vendors[index].id;
						results[index] = {
							id: id,
							text: data.data.vendors[index].company_name
						}
					}
					results[(parseInt(index)+1)] = {
						id: false,
						text: "All Vendors"
					}
					return {results: results};
				}
			}
		});
		
		if( $('input#report-payables-vendor').length > 0 ) {
			if( $('input#report-payables-vendor').val().length > 0 ) {
				$('input#report-payables-vendor').select2("data", {
					id: $('input#report-payables-vendor').val(),
					text: $('input#report-payables-vendor-defaultname').val(),
				});
			} else {
				$('input#report-payables-vendor').select2("data", {
					id: false,
					text: "All Vendors"
				});
			}
		}
		
		$('input#report-payables-vendor').live('change',function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		// Purchase Orders
		$('input#report-purchaseorders-vendor').select2({
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
						var id = data.data.vendors[index].id;
						results[index] = {
							id: id,
							text: data.data.vendors[index].company_name
						}
					}
					results[(parseInt(index)+1)] = {
						id: false,
						text: "All Vendors"
					}
					return {results: results};
				}
			}
		});
		
		if( $('input#report-purchaseorders-vendor').length > 0 ) {
			if( $('input#report-purchaseorders-vendor').val().length > 0 ) {
				$('input#report-purchaseorders-vendor').select2("data", {
					id: $('input#report-purchaseorders-vendor').val(),
					text: $('input#report-purchaseorders-vendor-defaultname').val(),
				});
			} else {
				$('input#report-purchaseorders-vendor').select2("data", {
					id: false,
					text: "All Vendors"
				});
			}
		}
		
		$('input#report-purchaseorders-vendor').live('change',function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		// Receivables
		$('input#report-receivables-customer').select2({
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
							id: data.data.customers[index].id,
							text: data.data.customers[index].first_name+' '+data.data.customers[index].last_name
						}
					}
					results[(parseInt(index)+1)] = {
						id: false,
						text: "All Customers"
					}
					return {results: results};
				}
			}
		});
		
		if( $('input#report-receivables-customer').length > 0 ) {
			if( $('input#report-receivables-customer').val().length > 0 ) {
				$('input#report-receivables-customer').select2("data", {
					id: $('input#report-receivables-customer').val(),
					text: $('input#report-receivables-customer-defaultname').val(),
				});
			} else {
				$('input#report-receivables-customer').select2("data", {
					id: false,
					text: "All Customers"
				});
			}
		}
		
		$('select.report-receivables-select, input#report-receivables-customer').live('change',function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		// Sales Orders
		$('input#report-salesorders-customer').select2({
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
							id: data.data.customers[index].id,
							text: data.data.customers[index].first_name+' '+data.data.customers[index].last_name
						}
					}
					results[(parseInt(index)+1)] = {
						id: false,
						text: "All Customers"
					}
					return {results: results};
				}
			}
		});
		
		if( $('input#report-salesorders-customer').length > 0 ) {
			if( $('input#report-salesorders-customer').val().length > 0 ) {
				$('input#report-salesorders-customer').select2("data", {
					id: $('input#report-salesorders-customer').val(),
					text: $('input#report-salesorders-customer-defaultname').val(),
				});
			} else {
				$('input#report-salesorders-customer').select2("data", {
					id: false,
					text: "All Customers"
				});
			}
		}
		
		$('select.report-salesorders-select, input#report-salesorders-customer').live('change',function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		// Customers
		$('input.report-customer-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-customer-date, input#report-customer-customer').live('change', function() {
			$date_start = $('input[name="date_start"]');
			$date_start.removeClass('unclassified');
			$date_end = $('input[name="date_end"]');
			$date_end.removeClass('unclassified');

			if( $date_start.datepicker("getDate").getTime() != $date_end.datepicker("getDate").getTime() &&
				$date_start.datepicker("getDate").getTime() > $date_end.datepicker("getDate").getTime() ) {
				holdSaleReport = true;
				if( $(this).attr('name') == "date_start" ) {
					$date_end.datepicker("setDate", $date_start.val());
					$date_end.addClass('unclassified');
				} else {
					$date_start.datepicker("setDate", $date_end.val());
					$date_start.addClass('unclassified');
				}
			} else {
				showPleaseWait();
				$(this).closest('form').submit();
			}
		});

		$('input#report-customer-customer').select2({
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
						results[parseInt(index)] = {
							id: data.data.customers[index].id,
							text: data.data.customers[index].first_name+' '+data.data.customers[index].last_name
						}
					}
					results[(parseInt(index)+1)] = {
						id: 't20',
						text: "Top 20"
					}
					results[(parseInt(index)+2)] = {
						id: 't50',
						text: "Top 50"
					}
					results[(parseInt(index)+3)] = {
						id: 't100',
						text: "Top 100"
					}
					return {results: results};
				}
			}
		});
		
		if( $('input#report-customer-customer').length > 0 ) {
			if( $('input#report-customer-customer').val().length > 0 ) {
				$('input#report-customer-customer').select2("data", {
					id: $('input#report-customer-customer').val(),
					text: $('input#report-customer-customer-defaultname').val(),
				});
			} else {
				$('input#report-customer-customer').select2("data", {
					id: 't20',
					text: "Top 20"
				});
			}
		}

		// Vendors
		$('input.report-vendor-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-vendor-date, input#report-vendor-vendor').live('change', function() {
			$date_start = $('input[name="date_start"]');
			$date_start.removeClass('unclassified');
			$date_end = $('input[name="date_end"]');
			$date_end.removeClass('unclassified');

			if( $date_start.datepicker("getDate").getTime() != $date_end.datepicker("getDate").getTime() &&
				$date_start.datepicker("getDate").getTime() > $date_end.datepicker("getDate").getTime() ) {
				holdSaleReport = true;
				if( $(this).attr('name') == "date_start" ) {
					$date_end.datepicker("setDate", $date_start.val());
					$date_end.addClass('unclassified');
				} else {
					$date_start.datepicker("setDate", $date_end.val());
					$date_start.addClass('unclassified');
				}
			} else {
				showPleaseWait();
				$(this).closest('form').submit();
			}
		});

		$('input#report-vendor-vendor').select2({
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
						var id = data.data.vendors[index].id;
						results[index] = {
							id: id,
							text: data.data.vendors[index].company_name
						}
					}
					results[(parseInt(index)+1)] = {
						id: 't20',
						text: "Top 20"
					}
					results[(parseInt(index)+2)] = {
						id: 't50',
						text: "Top 50"
					}
					results[(parseInt(index)+3)] = {
						id: 't100',
						text: "Top 100"
					}
					return {results: results};
				}
			}
		});
		
		if( $('input#report-vendor-vendor').length > 0 ) {
			if( $('input#report-vendor-vendor').val().length > 0 ) {
				$('input#report-vendor-vendor').select2("data", {
					id: $('input#report-vendor-vendor').val(),
					text: $('input#report-vendor-vendor-defaultname').val(),
				});
			} else {
				$('input#report-vendor-vendor').select2("data", {
					id: 't20',
					text: "Top 20"
				});
			}
		}

		// Taxes
		$('input.report-taxes-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-taxes-date, input#report-taxes-tax').live('change', function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

		$('input#report-taxes-tax').select2({
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
		
		if( $('input#report-taxes-tax').length > 0 ) {
			if( $('input#report-taxes-tax').val().length > 0 ) {
				$('input#report-taxes-tax').select2("data", {
					id: $('input#report-taxes-tax').val(),
					text: $('input#report-taxes-tax-defaultname').val(),
				});
			}
		}

		// Trial Balance
		$('input.report-trial-date').datepicker({
			dateFormat: "yy-mm-dd"
		});

		$('input.report-trial-date').live('change', function() {
			showPleaseWait();
			$(this).closest('form').submit();
		});

	});

	function colorBudgetLines() {
		$('tr.income-line:visible:even').each(function() {
			$(this).find('td').css('background','white');
		});
		$('tr.income-line:visible:odd').each(function() {
			$(this).find('td').css('background','#e8f1e8');
		});
		$('tr.expense-line:visible:even').each(function() {
			$(this).find('td').css('background','white');
		});
		$('tr.expense-line:visible:odd').each(function() {
			$(this).find('td').css('background','#e8f1e8');
		});
		
	}

	function dashIncomeExpenseRefresh() {
		$('#dash-index-chart-incomeexpense').hide();
		$('.dash-index-chart-incomeexpense-loading').show();
		$('.dash-index-chart-incomeexpense-loading-spinner').spin();

		$.post(
			'/dash/json/incomeexpense',
			$('input.dash-index-chart-incomeexpense-date').serialize(),
			function(data) {
				$('.dash-index-chart-incomeexpense-loading').hide();
				$('#dash-index-chart-incomeexpense').show();
				if( ! data.success ) {
					showError(data.error);
				} else {
					dashIncomeExpenseDrawChart(data.data.date_ranges,data.data.income_data,data.data.expense_data);
				}
			},
			'json'
		);
	}

	function dashIncomeExpenseDrawChart(dates,incomes,expenses) {
		var incomeData = [];
		var expenseData = [];
		var ticks = [];
		var labels = [];
		for( i in dates ) {
			labels.push(dates[i]);
			ticks.push((parseInt(i)*3+2));
			incomeData.push(incomes[i]);
			expenseData.push(expenses[i]);
		}

		// Canvas Element
		var incomeExpenseChart = new Chart($("#dash-index-chart-incomeexpense-chart").get(0).getContext("2d")).Bar({
			labels: labels,
			datasets: [
				{
					fillColor : "rgba(5,125,159,0.7)",
					strokeColor : "rgba(5,125,159,1)",
					data : incomeData,
					label : "Income"
				},
				{
					fillColor : "rgba(255,35,0,0.7)",
					strokeColor : "rgba(255,35,0,1)",
					data : expenseData,
					label : "COGS + Expenses"
				}
			]
		},{
			legendVisible: true,
			legendWidth: 0.27,
			legendFontSize: 11,
			legendPadding: 3
		});
	}

	function dashIncomeRefresh() {
		$('#dash-index-chart-income').hide();
		$('.dash-index-chart-income-loading').show();
		$('.dash-index-chart-income-loading-spinner').spin();

		$.post(
			'/dash/json/incomedaterange',
			$('input.dash-index-chart-income-date').serialize(),
			function(data) {
				$('.dash-index-chart-income-loading').hide();
				$('#dash-index-chart-income').show();
				if( ! data.success ) {
					showError(data.error);
				} else {
					dashIncomeDrawChart(data.data.date_ranges,data.data.income,data.data.gross_income,data.data.expense,data.data.net_income);
				}
			},
			'json'
		);
	}

	function dashIncomeDrawChart(dates,income,gross_income,expense,net_income) {

		var incomeChart = new Chart($("#dash-index-chart-income-chart").get(0).getContext("2d")).Line({
			labels: dates,
			datasets: [
				{
					fillColor : "rgba(5,125,159,0.4)",
					strokeColor : "rgba(5,125,159,0.5)",
					data : income,
					label : "Income"
				},
				{
					fillColor : "rgba(35,141,67,0.6)",
					strokeColor : "rgba(35,141,67,0.8)",
					data : gross_income,
					label : "Gross Income"
				},
				{
					fillColor : "rgba(255,35,0,0.7)",
					strokeColor : "rgba(255,35,0,0.8)",
					data : expense,
					label : "Expenses"
				}
			]
		},{
			pointDot: false,
			legendVisible: true,
			legendWidth: 0.25,
			legendFontSize: 11,
			legendPadding: 3
		});
	}


	function dashExpensesRefresh() {
		$('#dash-index-chart-expenses').hide();
		$('.dash-index-chart-expenses-loading').show();
		$('.dash-index-chart-expenses-loading-spinner').spin();

		$.post(
			'/dash/json/monthlyexpenses',
			$('.dash-index-chart-expenses-date select').serialize(),
			function(data) {
				$('.dash-index-chart-expenses-loading').hide();
				$('#dash-index-chart-expenses').show();
				if( ! data.success ) {
					showError(data.error);
				} else {
					dashExpensesDrawChart(data.data.expense_data);
				}
			},
			'json'
		);
	}

	function dashExpensesDrawChart(expenses) {
		var expenseData = [];

		var colors = [
			'#FF7373',
			'#5CCCCC',
			'#FF7400',
			'#269926',
			'#A60000',
			'#1D7373',
			'#FF9640',
			'#67E667'
		];

		var expenseChartOptions = {
			dataLabelVisible: true,
			dataLabelFontColor: "#000000",
			dataLabelScale: 0.9,
			chartScale: 0.8
		}

		for( i in expenses ) {
			expenseData.push({
				value: expenses[i].amount,
				color: colors[ i % 8 ],
				label: expenses[i].name
			});
		}
		
		var expenseChart = new Chart($("#dash-index-chart-expenses-chart").get(0).getContext("2d")).Pie(expenseData,expenseChartOptions);
	}

}