/**
 * Centralized Table Sorting System
 * Provides consistent table sorting functionality across the application
 */

window.TableSorting = {
    /**
     * Initialize table sorting for all sortable tables on the page
     */
    init: function() {
        // Bootstrap-sortable auto-initializes, so we just need to add our enhancements
        this.addCustomEnhancements();
    },

    /**
     * Add custom enhancements to table sorting
     */
    addCustomEnhancements: function() {
        // Add keyboard navigation support
        this.addKeyboardNavigation();
        
        // Add loading states
        this.addLoadingStates();
        
        // Add accessibility improvements
        this.addAccessibilityFeatures();
    },

    /**
     * Add keyboard navigation support for sortable columns
     */
    addKeyboardNavigation: function() {
        $('.table.sortable th[data-defaultsign]:not([data-defaultsort=disabled])').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
    },

    /**
     * Add loading states during sorting operations
     */
    addLoadingStates: function() {
        $('.table.sortable').on('before-sort', function() {
            $(this).addClass('loading');
        });
        
        $('.table.sortable').on('sorted', function() {
            $(this).removeClass('loading');
        });
        
        // Update sort icons when sorting occurs
        $('.table.sortable').on('sorted', function() {
            const $table = $(this);
            console.log('Sort completed, updating icons...');
            
            // Reset all icons to inactive state
            $table.find('th[data-defaultsign]:not([data-defaultsort=disabled])').each(function() {
                const $th = $(this);
                const $icon = $th.find('.fa-sort, .fa-sort-asc, .fa-sort-desc');
                
                // Remove existing sort classes
                $icon.removeClass('fa-sort-asc fa-sort-desc').addClass('fa-sort sort-inactive');
            });
            
            // Update the active column's icon
            const $activeHeader = $table.find('th.up, th.down');
            console.log('Active header found:', $activeHeader.length);
            
            if ($activeHeader.length > 0) {
                const $icon = $activeHeader.find('.fa-sort, .fa-sort-asc, .fa-sort-desc');
                console.log('Icon found:', $icon.length);
                console.log('Header classes:', $activeHeader.attr('class'));
                
                if ($activeHeader.hasClass('up')) {
                    console.log('Setting ascending icon');
                    $icon.removeClass('fa-sort fa-sort-desc sort-inactive').addClass('fa-sort-asc');
                } else if ($activeHeader.hasClass('down')) {
                    console.log('Setting descending icon');
                    $icon.removeClass('fa-sort fa-sort-asc sort-inactive').addClass('fa-sort-desc');
                }
            }
        });
    },

    /**
     * Add accessibility features
     */
    addAccessibilityFeatures: function() {
        // Add ARIA labels and sort icons to sortable columns
        $('.table.sortable th[data-defaultsign]:not([data-defaultsort=disabled])').each(function() {
            const $th = $(this);
            const text = $th.text().trim();
            
            // Add sort icon if not already present (before the text)
            if ($th.find('.fa-sort, .fa-sort-asc, .fa-sort-desc').length === 0) {
                $th.prepend('<span class="fa fa-sort sort-inactive"></span> ');
            }
            
            $th.attr('aria-label', `Sort by ${text}`);
            $th.attr('tabindex', '0');
            $th.attr('role', 'button');
        });
    },

    /**
     * Initialize sorting for a specific table
     * @param {string|jQuery} table - Table selector or jQuery object
     * @param {object} options - Sorting options
     */
    initTable: function(table, options = {}) {
        const $table = $(table);
        
        if ($table.length === 0) {
            console.warn('Table not found:', table);
            return;
        }

        // Add sortable class if not present
        if (!$table.hasClass('sortable')) {
            $table.addClass('sortable');
        }

        // Bootstrap-sortable auto-initializes, so we just add custom enhancements
        this.addCustomEnhancements();
    },

    /**
     * Sort a table by a specific column
     * @param {string|jQuery} table - Table selector or jQuery object
     * @param {string} column - Column to sort by
     * @param {string} direction - Sort direction ('asc' or 'desc')
     */
    sortBy: function(table, column, direction = 'asc') {
        const $table = $(table);
        const $header = $table.find(`th[data-defaultsign="${column}"]`);
        
        if ($header.length === 0) {
            console.warn('Column not found:', column);
            return;
        }

        // Check if bootstrap-sortable is available
        if (typeof $.bootstrapSortable !== 'function') {
            console.warn('bootstrap-sortable library not loaded. Table sorting will not work.');
            return;
        }

        // Trigger sort using bootstrap-sortable
        $table.bootstrapSortable({
            sortingHeader: $header,
            direction: direction
        });
    },

    /**
     * Get current sort state of a table
     * @param {string|jQuery} table - Table selector or jQuery object
     * @returns {object} Sort state with column and direction
     */
    getSortState: function(table) {
        const $table = $(table);
        const $activeHeader = $table.find('th.sortable-header:has(.fa-sort-asc), th.sortable-header:has(.fa-sort-desc)');
        
        if ($activeHeader.length === 0) {
            return { column: null, direction: null };
        }

        const column = $activeHeader.attr('data-defaultsign');
        const direction = $activeHeader.find('.fa-sort-asc').length > 0 ? 'asc' : 'desc';
        
        return { column, direction };
    },

    /**
     * Reset table sorting to default state
     * @param {string|jQuery} table - Table selector or jQuery object
     */
    resetSort: function(table) {
        const $table = $(table);
        const $defaultHeader = $table.find('th[data-defaultsort]');
        
        if ($defaultHeader.length > 0) {
            this.sortBy(table, $defaultHeader.attr('data-defaultsign'), 'asc');
        }
    },

    /**
     * Add sorting to a table with server-side support
     * @param {string|jQuery} table - Table selector or jQuery object
     * @param {object} options - Configuration options
     */
    initServerSideSorting: function(table, options = {}) {
        const $table = $(table);
        const config = {
            url: options.url || window.location.href,
            method: options.method || 'GET',
            ajax: options.ajax !== false, // Default to true for AJAX sorting
            onSort: options.onSort || function(column, direction) {
                if (config.ajax) {
                    // AJAX-based sorting
                    TableSorting.sortTableAjax($table, column, direction, config);
                } else {
                    // Page reload sorting
                    const url = new URL(config.url);
                    if (direction) {
                        url.searchParams.set('sort', column);
                        url.searchParams.set('direction', direction);
                    } else {
                        // Clear sorting parameters
                        url.searchParams.delete('sort');
                        url.searchParams.delete('direction');
                    }
                    window.location.href = url.toString();
                }
            },
            ...options
        };

        // Add click handlers for server-side sorting
        $table.find('th[data-defaultsign]:not([data-defaultsort=disabled])').on('click', function() {
            const column = $(this).attr('data-defaultsign');
            const currentState = TableSorting.getSortState($table);
            
            let direction;
            if (currentState.column === column) {
                // Same column - cycle through: asc -> desc -> no sort -> asc
                if (currentState.direction === 'asc') {
                    direction = 'desc';
                } else if (currentState.direction === 'desc') {
                    direction = null; // Clear sorting
                } else {
                    direction = 'asc';
                }
            } else {
                // Different column - start with ascending
                direction = 'asc';
            }
            
            config.onSort(column, direction);
        });
    },

    /**
     * Sort table using AJAX
     * @param {jQuery} $table - Table jQuery object
     * @param {string} column - Column to sort by
     * @param {string} direction - Sort direction
     * @param {object} config - Configuration options
     */
    sortTableAjax: function($table, column, direction, config) {
        // Show loading state
        $table.addClass('loading');
        
        // Prepare data for AJAX request
        const ajaxData = { page: 1 }; // Reset to first page when sorting
        if (direction) {
            ajaxData.sort = column;
            ajaxData.direction = direction;
        }
        
        // Make AJAX request
        $.ajax({
            url: config.url,
            method: config.method,
            data: ajaxData,
            success: function(response) {
                // Extract the table content from the response
                const $newTable = $(response).find('#' + $table.attr('id'));
                const $newTbody = $newTable.find('tbody');
                const $newThead = $newTable.find('thead');
                
                // Update the table content
                $table.find('tbody').html($newTbody.html());
                $table.find('thead').html($newThead.html());
                
                // Re-bind event handlers for the new content
                TableSorting.initServerSideSorting($table, config);
                
                // Update URL without page reload
                const url = new URL(window.location);
                if (direction) {
                    url.searchParams.set('sort', column);
                    url.searchParams.set('direction', direction);
                } else {
                    // Clear sorting parameters
                    url.searchParams.delete('sort');
                    url.searchParams.delete('direction');
                }
                url.searchParams.delete('page'); // Reset to page 1
                window.history.pushState({}, '', url);
                
                console.log('🔧 [DEBUG] Server-side sort completed:', {
                    column: column,
                    direction: direction,
                    rows_returned: $newTbody.find('tr').length
                });
            },
            error: function(xhr, status, error) {
                console.error('Sort request failed:', error);
                alert('Failed to sort table. Please try again.');
            },
            complete: function() {
                $table.removeClass('loading');
            }
        });
    }
};

// Auto-initialize when DOM is ready
$(document).ready(function() {
    console.log('🔧 [DEBUG] table-sorting.js - DOM ready, initializing TableSorting');
    // Bootstrap-sortable auto-initializes, so we just add our enhancements
    TableSorting.init();
    console.log('🔧 [DEBUG] table-sorting.js - TableSorting.init() completed');
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TableSorting;
}
