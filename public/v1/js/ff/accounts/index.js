/*
 * index.js
 * Copyright (c) 2020 james@firefly-iii.org
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

var fixObjectHelper = function (e, tr) {
    "use strict";
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function (index) {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
    });
    return $helper;
};

$(function () {
    "use strict";
    // table may have multiple tbody's.
    $('#sortable-table').find('tbody').sortable(
        {
            helper: fixObjectHelper,
            stop: stopSorting,
            items: 'tr.sortable-object',
            handle: '.object-handle',
            start: function (event, ui) {
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
    
    // Initialize consolidate and generate button functionality
    initializeConsolidateAndGenerateButton();
});


function stopSorting() {
    "use strict";
    $.each($('#sortable-table>tbody>tr.sortable-object'), function (i, v) {
        var holder = $(v);
        var position = parseInt(holder.data('position'));
        var originalOrder = parseInt(holder.data('order'));
        var id = holder.data('id');
        var newOrder;

        if (position === i) {
            // not changed, position is what it should be.
            return;
        }

        if (position < i) {
            // position is less.
            console.log('Account #' + id + ' has moved down from position ' + originalOrder + ' to ' + (i + 1));
        }
        if (position > i) {
            console.log('Account #' + id + ' has moved up from position ' + originalOrder + ' to ' + (i + 1));
        }
        // update position:
        holder.data('position', i);
        newOrder = i + 1;

        // post new position via API!
        //$.post('api/v1/accounts/' + id, {order: newOrder, _token: token});
        $.ajax({
            url: 'api/v1/accounts/' + id,
            data: JSON.stringify({order: newOrder}),
            type: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
            },
        });
    });

}

/**
 * Initialize the consolidate and generate button functionality for accounts index
 */
function initializeConsolidateAndGenerateButton() {
    "use strict";
    
    console.log('Initializing consolidate and generate button for accounts index...');
    
    // Use event delegation to handle clicks on the consolidate and generate button
    $(document).on('click', '.consolidate-and-generate-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Consolidate and Generate button clicked on accounts index!');
        
        var accountId = $(this).data('account-id');
        var button = $(this);
        var originalText = button.html();
        
        console.log('Account ID:', accountId);
        console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
        
        // Show loading state
        button.html('<span class="fa fa-fw fa-spinner fa-spin"></span>');
        button.prop('disabled', true);
        
        // Make API call to consolidate and generate transactions
        $.ajax({
            url: '/api/v1/pfinance/consolidate-and-generate-transactions-for-account',
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
                console.log('Consolidate and Generate success:', response);
                // Show success message
                showAlert('success', 'Transactions Processed Successfully!', 'The account transactions have been consolidated and converted to Firefly III format. You can now import them using the import button.');
                
                // Reload the page to show updated data
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error('Consolidate and Generate error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                
                var errorMessage = 'An error occurred while processing transactions.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert('danger', 'Processing Failed', errorMessage);
            },
            complete: function() {
                // Restore button state
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Also check if buttons exist for debugging
    var buttons = $('.consolidate-and-generate-btn');
    if (buttons.length === 0) {
        console.log('No consolidate and generate buttons found on accounts index');
    } else {
        console.log('Found ' + buttons.length + ' consolidate and generate buttons on accounts index');
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
