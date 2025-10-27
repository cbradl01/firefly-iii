# Server-Side Sorting Guide

This guide explains how to use the enhanced table sorting system with server-side sorting support for large datasets.

## Overview

The table sorting system now supports both client-side and server-side sorting:

- **Client-side sorting**: Uses bootstrap-sortable for small datasets (existing behavior)
- **Server-side sorting**: Uses AJAX for large datasets with database-level sorting and pagination

## Usage

### 1. Using the Sortable Table Partial (Recommended)

For new tables, use the enhanced partial with server-side sorting:

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
    'emptyMessage': 'No entities found',
    'serverSideSorting': true,
    'serverSideUrl': route('my-route'),
    'serverSideMethod': 'GET',
    'serverSideAjax': true
} %}
```

### 2. Manual Implementation

For existing tables, add server-side sorting manually:

```twig
<!-- Your existing table -->
<table class="table sortable table-striped table-hover" id="my-table">
    <!-- table content -->
</table>

<script nonce="{{ JS_NONCE }}">
    $(document).ready(function() {
        TableSorting.initServerSideSorting('#my-table', {
            url: window.location.pathname,
            method: 'GET',
            ajax: true
        });
    });
</script>
```

## Backend Implementation

### Controller Changes

Your controller needs to handle sort parameters:

```php
public function index(Request $request)
{
    $page = $request->get('page', 1);
    $pageSize = $request->get('pageSize', 50);
    $sortColumn = $request->get('sort');
    $sortDirection = $request->get('direction', 'asc');
    
    // Get paginated and sorted data
    $result = $this->repository->getPaginated($page, $pageSize, $sortColumn, $sortDirection);
    
    return view('my-view', [
        'items' => $result['items'],
        'total' => $result['total'],
        'sortColumn' => $sortColumn,
        'sortDirection' => $sortDirection,
        'pageSize' => $pageSize
    ]);
}
```

### Repository Changes

Your repository should support sorting:

```php
public function getPaginated(int $page, int $pageSize, ?string $sortColumn = null, ?string $sortDirection = 'asc'): array
{
    $query = $this->model->newQuery();
    
    // Apply sorting
    if ($sortColumn && $sortDirection) {
        $this->applySorting($query, $sortColumn, $sortDirection);
    } else {
        $query->orderBy('created_at', 'desc'); // Default sort
    }
    
    // Apply pagination
    $total = $query->count();
    $items = $query->offset(($page - 1) * $pageSize)
                   ->limit($pageSize)
                   ->get();
    
    return [
        'items' => $items,
        'total' => $total
    ];
}

private function applySorting($query, string $sortColumn, string $sortDirection): void
{
    $direction = strtolower($sortDirection) === 'desc' ? 'DESC' : 'ASC';
    
    switch ($sortColumn) {
        case 'name':
            $query->orderBy('name', $direction);
            break;
        case 'amount':
            $query->orderBy('amount', $direction);
            break;
        // Add more columns as needed
        default:
            $query->orderBy('created_at', 'DESC');
            break;
    }
}
```

## Configuration Options

### TableSorting.initServerSideSorting() Options

- `url`: The URL to send sort requests to (default: current page)
- `method`: HTTP method (default: 'GET')
- `ajax`: Enable AJAX sorting (default: true)
- `onSort`: Custom sort handler function

### Sortable Table Partial Options

- `serverSideSorting`: Enable server-side sorting (default: false)
- `serverSideUrl`: URL for sort requests
- `serverSideMethod`: HTTP method for sort requests
- `serverSideAjax`: Enable AJAX sorting

## Benefits

1. **Scalable**: Works with any dataset size
2. **Efficient**: Database-level sorting and pagination
3. **Consistent**: Same API across all tables
4. **Flexible**: Can be used with existing tables
5. **User-Friendly**: AJAX updates without page reloads

## Migration from Client-Side Sorting

To migrate an existing table to server-side sorting:

1. Add sort parameter handling to your controller
2. Update your repository to support sorting
3. Add the JavaScript initialization
4. Test with large datasets

The system is backward compatible - existing client-side sorted tables will continue to work unchanged.
