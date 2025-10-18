# Field Input Renderer Usage Guide

The `FieldInputRenderer` is a centralized JavaScript module that provides consistent field input rendering across all parts of the application that use field definitions from `FieldDefinitions.php`.

## Basic Usage

### 1. Include the Script

```html
<script src="{{ asset('js/field-input-renderer.js') }}"></script>
```

### 2. Generate Input HTML

```javascript
// Basic usage
const inputHtml = FieldInputRenderer.generateInput(fieldDefinition, fieldName, defaultValue);

// With options
const inputHtml = FieldInputRenderer.generateInput(fieldDefinition, fieldName, defaultValue, {
    namePrefix: 'field_',
    cssClass: 'form-control',
    id: 'my-field-id',
    placeholder: 'Enter value...'
});
```

### 3. Load Financial Entities for Dropdowns

```javascript
// Load all financial entity dropdowns on the page
FieldInputRenderer.loadFinancialEntitiesForDropdowns();

// Load for a specific container
FieldInputRenderer.loadFinancialEntitiesForDropdowns(document.getElementById('my-container'));
```

## Examples

### Account Template Edit Modal

```javascript
// Generate input for account holder field
const accountHolderInput = FieldInputRenderer.generateInput(
    {
        input_type: 'financial_entity_select',
        options: { exclude_entity_types: ['institution'] }
    },
    'account_holder',
    'John Doe',
    {
        namePrefix: 'field_default_',
        cssClass: 'form-control input-sm'
    }
);
```

### Financial Entity Edit Modal

```javascript
// Generate input for any field
const fieldInput = FieldInputRenderer.generateInput(
    fieldData,
    fieldName,
    currentValue,
    {
        cssClass: 'form-control',
        id: fieldName
    }
);
```

### Transaction Edit Form (Future Use)

```javascript
// Generate input for transaction fields
const transactionInput = FieldInputRenderer.generateInput(
    transactionFieldDefinition,
    'amount',
    '100.00',
    {
        namePrefix: 'transaction_',
        cssClass: 'form-control'
    }
);
```

## Supported Input Types

- `text` - Standard text input
- `textarea` - Multi-line text input
- `select` - Dropdown with predefined options
- `financial_entity_select` - Dropdown populated with financial entities
- `checkbox` - Boolean checkbox
- `date` - Date picker
- `datetime` - Date and time picker
- `number` - Numeric input
- `decimal` - Decimal number input
- `email` - Email input with validation
- `url` - URL input with validation
- `tel` - Telephone number input
- `beneficiaries` - Special complex input for trust beneficiaries

## Field Definition Structure

```javascript
const fieldDefinition = {
    input_type: 'select',           // Type of input to render
    data_type: 'string',            // Data type for validation
    required: true,                 // Whether field is required
    options: {                      // Options for select inputs
        'value1': 'Label 1',
        'value2': 'Label 2'
    },
    // For financial_entity_select:
    options: {
        exclude_entity_types: ['institution']
    }
};
```

## Options Parameter

```javascript
const options = {
    namePrefix: 'field_',           // Prefix for the name attribute
    cssClass: 'form-control',       // CSS classes for the input
    id: 'my-field',                 // ID for the input element
    placeholder: 'Enter value...',  // Placeholder text
    containerClass: 'form-group'    // CSS class for container div
};
```

## Integration with FieldDefinitions.php

The renderer works seamlessly with field definitions from `FieldDefinitions.php`:

```php
// In FieldDefinitions.php
'account_holder' => [
    'data_type' => 'string',
    'input_type' => 'financial_entity_select',
    'category' => 'basic_info',
    'required' => true,
    'validation' => 'required|string|max:255',
    'options' => [
        'exclude_entity_types' => ['institution']
    ]
],
```

```javascript
// In JavaScript
const inputHtml = FieldInputRenderer.generateInput(
    fieldDefinition,  // From FieldDefinitions.php
    'account_holder',
    defaultValue
);
```

## Benefits

1. **Consistency** - All field inputs render the same way across the application
2. **Maintainability** - Changes to input rendering only need to be made in one place
3. **Reusability** - Can be used in any part of the application that uses field definitions
4. **Type Safety** - Centralized handling of different input types
5. **Future-Proof** - Easy to add new input types or modify existing ones

## Future Enhancements

- Support for more complex input types (file uploads, rich text editors, etc.)
- Integration with form validation libraries
- Support for conditional field rendering
- Integration with accessibility features (ARIA labels, etc.)
