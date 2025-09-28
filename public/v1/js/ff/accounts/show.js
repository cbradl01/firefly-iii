/*
 * show.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

var fixHelper = function (e, tr) {
    "use strict";
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function (index) {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
    });
    console.log('fixHelper')
    return $helper;
};

$(function () {
    "use strict";
    //lineChart(chartUrl, 'overview-chart');
    lineNoStartZeroChart(chartUrl, 'overview-chart');
    if (!showAll) {
        multiCurrencyPieChart(incomeCategoryUrl, 'account-cat-in');
        multiCurrencyPieChart(expenseCategoryUrl, 'account-cat-out');
        multiCurrencyPieChart(expenseBudgetUrl, 'account-budget-out');
    }

    // Initialize consolidation button functionality
    initializeConsolidationButton();
    
    // Initialize generate firefly transactions button functionality
    initializeGenerateFireflyTransactionsButton();
    
    // Initialize consolidate and generate button functionality
    initializeConsolidateAndGenerateButton('#consolidate-and-generate-btn', 'accounts show', '<span class="fa fa-fw fa-spinner fa-spin"></span> Processing...');

    console.log("Checking if sortable is defined:", $(".sortable-table tbody").sortable);

    // sortable!
    if (typeof $(".sortable-table tbody").sortable !== "undefined") {
        console.log('Initializing sortable');
        $(".sortable-table tbody").sortable(
            {
                helper: fixHelper,
                items: 'tr:not(.ignore)',
                stop: sortStop,
                handle: '.handle',
                start: function (event, ui) {
                    console.log('Drag started');
                    // Build a placeholder cell that spans all the cells in the row
                    var cellCount = 0;
                    $('td, th', ui.helper).each(function () {
                        // For each TD or TH try and get it's colspan attribute, and add that or 1 to the total
                        var colspan = 1;
                        var colspanAttr = $(this).attr('colspan');
                        if (colspanAttr > 1) {
                            colspan = colspanAttr;
                        }
                        cellCount += colspan;
                    });

                    // Add the placeholder UI - note that this is the item's content, so TD rather than TR
                    ui.placeholder.html('<td colspan="' + cellCount + '">&nbsp;</td>');
                }
            }
        );
    } else {
        console.error('Sortable is not defined. Ensure jQuery UI is loaded.');
    }
});

function sortStop(event, ui) {
    "use strict";
    var current = $(ui.item);
    var thisDate = current.data('date');
    var originalBG = current.css('backgroundColor');


    if (current.prev().data('date') !== thisDate && current.next().data('date') !== thisDate) {
        // animate something with color:
        current.animate({backgroundColor: "#d9534f"}, 200, function () {
            $(this).animate({backgroundColor: originalBG}, 200);
            return undefined;
        });

        return false;
    }

    // do update
    var list = $('tr[data-date="' + thisDate + '"]');
    var submit = [];
    $.each(list, function (i, v) {
        var row = $(v);
        var id = row.data('id');
        submit.push(id);
    });

    // do extra animation when done?
    $.post('transactions/reorder', {items: submit, date: thisDate});

    current.animate({backgroundColor: "#5cb85c"}, 200, function () {
        $(this).animate({backgroundColor: originalBG}, 200);
        return undefined;
    });
    return undefined;
}

/**
 * Initialize the consolidation button functionality
 */
function initializeConsolidationButton() {
    "use strict";
    
    console.log('Initializing consolidation button...');
    
    // Use event delegation to handle clicks on the consolidation button
    $(document).on('click', '#consolidate-transactions-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Consolidation button clicked!');
        
        var accountId = $(this).data('account-id');
        var button = $(this);
        var originalText = button.html();
        
        console.log('Account ID:', accountId);
        console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
        
        // Show loading state
        button.html('<span class="fa fa-fw fa-spinner fa-spin"></span> Consolidating...');
        button.prop('disabled', true);
        
        // Make API call to consolidate transactions
        $.ajax({
            url: '/api/v1/pfinance/consolidate-transactions-for-account',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                account_id: accountId.toString()
            }),
            success: function(response) {
                console.log('Consolidation success:', response);
                // Show success message
                showAlert('success', 'Transactions consolidated successfully!', 'The account transactions have been processed and consolidated.');
                
                // Reload the page to show updated data
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error('Consolidation error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                
                var errorMessage = 'An error occurred while consolidating transactions.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert('danger', 'Consolidation Failed', errorMessage);
            },
            complete: function() {
                // Restore button state
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Also check if button exists for debugging
    var button = $('#consolidate-transactions-btn');
    if (button.length === 0) {
        console.error('Consolidation button not found!');
    } else {
        console.log('Consolidation button found, account ID:', button.data('account-id'));
        
        // Test if button is clickable by adding a simple click handler
        button.on('click.test', function() {
            console.log('Direct button click test successful!');
        });
        
        // Trigger a test click
        setTimeout(function() {
            console.log('Testing button click...');
            button.trigger('click.test');
        }, 1000);
    }
}

/**
 * Show alert message to user
 */
function showAlert(type, title, message) {
    "use strict";
    
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible">' +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span></button>' +
        '<strong>' + title + '</strong> ' + message +
        '</div>';
    
    // Insert alert at the top of the content area
    $('.row').first().before(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

/**
 * Initialize the generate firefly transactions button functionality
 */
function initializeGenerateFireflyTransactionsButton() {
    "use strict";
    
    console.log('Initializing generate firefly transactions button...');
    
    // Use event delegation to handle clicks on the generate firefly transactions button
    $(document).on('click', '#generate-firefly-transactions-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Generate Firefly Transactions button clicked!');
        
        var accountId = $(this).data('account-id');
        var button = $(this);
        var originalText = button.html();
        
        console.log('Account ID:', accountId);
        console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
        
        // Show loading state
        button.html('<span class="fa fa-fw fa-spinner fa-spin"></span> Generating...');
        button.prop('disabled', true);
        
        // Make API call to generate firefly transactions
        $.ajax({
            url: '/api/v1/pfinance/generate-firefly-transactions-for-account',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                account_id: accountId.toString()
            }),
            success: function(response) {
                console.log('Generate Firefly Transactions success:', response);
                // Show success message
                showAlert('success', 'Firefly Transactions Generated!', 'The account transactions have been converted to Firefly III format. You can now import them using the import button.');
                
                // Reload the page to show updated data
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error('Generate Firefly Transactions error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                
                var errorMessage = 'An error occurred while generating Firefly transactions.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert('danger', 'Generation Failed', errorMessage);
            },
            complete: function() {
                // Restore button state
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Also check if button exists for debugging
    var button = $('#generate-firefly-transactions-btn');
    if (button.length === 0) {
        console.error('Generate Firefly Transactions button not found!');
    } else {
        console.log('Generate Firefly Transactions button found, account ID:', button.data('account-id'));
    }
}

