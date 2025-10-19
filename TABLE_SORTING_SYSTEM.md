# Centralized Table Sorting System

This document describes the centralized table sorting and styling system implemented across the Firefly III application.

## Overview

The table sorting system provides consistent, accessible, and user-friendly table sorting functionality across all tables in the application. It builds upon the existing `bootstrap-sortable` library while adding enhancements for better user experience and accessibility.

## Components

### 1. CSS Styling (`/public/css/table-sorting.css`)

Provides consistent visual styling for sortable tables including:
- Hover effects on sortable columns
- Visual indicators for sort direction (arrows, text indicators)
- Active sorting state styling
- Responsive design support
- Dark mode compatibility
- Accessibility improvements

### 2. JavaScript Functionality (`/public/js/table-sorting.js`)

Enhances the bootstrap-sortable library with:
- Automatic initialization for all `.table-sortable` tables
- Keyboard navigation support (Enter/Space to sort)
- Loading states during sorting operations
- Accessibility features (ARIA labels, focus management)
- Server-side sorting support
- Programmatic sorting control

### 3. Reusable Table Partial (`/resources/views/partials/sortable-table.twig`)

A flexible Twig partial for creating sortable tables with:
- Configurable columns with sorting options
- Custom row templates
- Responsive design
- Empty state handling
- Automatic asset inclusion

## Usage

### Basic Implementation

To make any table sortable, simply add the `table-sortable` class:

```html
<table class="table table-sortable table-striped table-hover">
    <thead>
        <tr>
            <th data-defaultsign="az">Name</th>
            <th data-defaultsign="_19">Amount</th>
            <th data-defaultsort="disabled">Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td data-value="john doe">John Doe</td>
            <td data-value="100.50">$100.50</td>
            <td>...</td>
        </tr>
    </tbody>
</table>
```

### Sort Types

The `data-defaultsign` attribute determines how columns are sorted:

- `az` - Alphabetical (a-z, z-a)
- `AZ` - Case-sensitive alphabetical (A-Z, Z-A)
- `_19` - Numeric (1-9, 9-1)
- `month` - Month names (jan-dec, dec-jan)

### Data Values

For proper sorting, add `data-value` attributes to table cells:

```html
<td data-value="john doe">John Doe</td>
<td data-value="100.50">$100.50</td>
```

The `data-value` should contain the raw sortable value (lowercase for text, numeric for numbers).

### Disabling Sorting

To disable sorting on a column, add `data-defaultsort="disabled"`:

```html
<th data-defaultsort="disabled">Actions</th>
```

### Using the Reusable Partial

```twig
{% include 'partials.sortable-table' with {
    'tableId': 'my-table',
    'columns': [
        {
            'key': 'name',
            'title': 'Name',
            'sortable': true,
            'sortType': 'az',
            'width': '25%'
        },
        {
            'key': 'amount',
            'title': 'Amount',
            'sortable': true,
            'sortType': '_19',
            'width': '15%',
            'align': 'right'
        }
    ],
    'rows': entities,
    'rowTemplate': 'partials.entity-row',
    'emptyMessage': 'No entities found'
} %}
```

## JavaScript API

### Automatic Initialization

Tables are automatically initialized when the page loads:

```javascript
// All tables with .table-sortable class are automatically initialized
TableSorting.init();
```

### Manual Initialization

```javascript
// Initialize a specific table
TableSorting.initTable('#my-table');

// Initialize with options
TableSorting.initTable('#my-table', {
    applyLast: true
});
```

### Programmatic Sorting

```javascript
// Sort by column
TableSorting.sortBy('#my-table', 'name', 'asc');

// Get current sort state
const sortState = TableSorting.getSortState('#my-table');
console.log(sortState.column, sortState.direction);

// Reset to default sort
TableSorting.resetSort('#my-table');
```

### Server-Side Sorting

For tables that need server-side sorting:

```javascript
TableSorting.initServerSideSorting('#my-table', {
    url: '/api/entities',
    onSort: function(column, direction) {
        // Custom sorting logic
        window.location.href = `/entities?sort=${column}&direction=${direction}`;
    }
});
```

## Accessibility Features

- **Keyboard Navigation**: Press Enter or Space on sortable column headers
- **ARIA Labels**: Automatic labeling for screen readers
- **Focus Management**: Visual focus indicators
- **Screen Reader Support**: Proper role attributes and descriptions

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills)
- Mobile browsers

## Performance Considerations

- Client-side sorting is performed in-memory
- Large datasets (>1000 rows) should consider server-side sorting
- Loading states prevent multiple simultaneous sort operations

## Examples

### Financial Entities Table

```html
<table class="table table-sortable table-striped table-hover" id="financial-entities-table">
    <thead>
        <tr>
            <th data-defaultsign="az">Name</th>
            <th data-defaultsign="az">Type</th>
            <th data-defaultsign="az">Description</th>
            <th data-defaultsign="_19">Accounts</th>
            <th data-defaultsign="_19">Total Balance</th>
            <th data-defaultsort="disabled">Actions</th>
        </tr>
    </thead>
    <tbody>
        {% for entity in entities %}
            <tr>
                <td data-value="{{ entity.display_name|lower }}">{{ entity.display_name }}</td>
                <td data-value="{{ entity.entity_type|lower }}">{{ entity.entity_type|title }}</td>
                <td data-value="{{ entity.description|default('')|lower }}">{{ entity.description|default('No description') }}</td>
                <td data-value="{{ entityStats.owned_accounts_count }}">{{ entityStats.owned_accounts_count }}</td>
                <td data-value="{{ entityStats.owned_accounts_balance }}">${{ entityStats.owned_accounts_balance|number_format(2) }}</td>
                <td>...</td>
            </tr>
        {% endfor %}
    </tbody>
</table>
```

### Accounts Table

```html
<table class="table table-sortable table-responsive table-hover" id="accounts-table">
    <thead>
        <tr>
            <th data-defaultsign="az">Type</th>
            <th data-defaultsign="az">Institution</th>
            <th data-defaultsign="az">Owner</th>
            <th data-defaultsign="_19">Balance</th>
            <th data-defaultsign="az">Active</th>
            <th data-defaultsort="disabled">Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Account rows with data-value attributes -->
    </tbody>
</table>
```

## Migration Guide

### From Existing Tables

1. Add `table-sortable` class to your table
2. Add `data-defaultsign` attributes to sortable column headers
3. Add `data-value` attributes to table cells
4. Include the CSS and JS assets
5. Test sorting functionality

### From Bootstrap-Sortable

The system is backward compatible with existing bootstrap-sortable implementations. Simply add the new CSS and JS files to get enhanced functionality.

## Troubleshooting

### Common Issues

1. **Sorting not working**: Check that `data-defaultsign` attributes are present
2. **Incorrect sort order**: Verify `data-value` attributes contain proper sortable values
3. **Styling issues**: Ensure CSS file is loaded after Bootstrap
4. **JavaScript errors**: Check browser console for conflicts

### Debug Mode

Enable debug logging:

```javascript
// Add to browser console
window.TableSorting.debug = true;
```

## Future Enhancements

- Multi-column sorting
- Custom sort functions
- Sort persistence across page reloads
- Advanced filtering integration
- Export functionality
- Virtual scrolling for large datasets

