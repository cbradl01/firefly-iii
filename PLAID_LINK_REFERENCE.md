# Plaid Link Integration Reference

This file contains the Plaid Link integration code that was removed from the accounts/show.twig template. It can be used as a reference for future Plaid Link implementation.

## Original Template Code

### HTML Structure
```html
<!-- Plugin System: Load automation section if plugin is enabled -->
<div id="plugin-automation-section"></div>
```

### CSS and JavaScript Includes
```html
<!-- PFinance Automation Plugin -->
<link rel="stylesheet" href="plugins/PFinance/static/css/automation.css?v={{ FF_VERSION }}" nonce="{{ JS_NONCE }}">
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js" nonce="{{ JS_NONCE }}"></script>
<script type="text/javascript" src="plugins/PFinance/static/js/plaid-link.js?v={{ FF_VERSION }}" nonce="{{ JS_NONCE }}"></script>
```

### JavaScript Loading Code
```javascript
<script type="text/javascript" nonce="{{ JS_NONCE }}">
    // Load automation section template
    document.addEventListener('DOMContentLoaded', function() {
        fetch('plugins/PFinance/static/templates/automation-section.html')
            .then(response => response.text())
            .then(html => {
                // Replace template variables
                const accountId = '{{ account.id }}';
                const processedHtml = html
                    .replace(/ACCOUNT_ID_PLACEHOLDER/g, accountId);
                
                const targetElement = document.getElementById('plugin-automation-section');
                if (targetElement) {
                    targetElement.innerHTML = processedHtml;
                    console.log('PFinance automation plugin loaded successfully');
                    
                    // Bind Plaid Link functionality to the download transactions button
                    const downloadBtn = document.getElementById('download-transactions-btn');
                    if (downloadBtn && window.plaidLinkController) {
                        downloadBtn.addEventListener('click', function() {
                            const accountId = this.getAttribute('data-account-id');
                            window.plaidLinkController.downloadTransactionsWithPlaid(accountId);
                        });
                        console.log('Plaid Link functionality bound to download button');
                    }
                    
                }
            })
            .catch(error => console.error('Error loading automation template:', error));
    });
</script>
```

## Key Components for Future Implementation

### 1. Template Loading System
- Uses `fetch()` to load external HTML templates
- Supports template variable replacement (e.g., `ACCOUNT_ID_PLACEHOLDER`)
- Dynamic DOM insertion into designated container

### 2. Plaid Link Integration
- References `window.plaidLinkController` global object
- Binds to button with ID `download-transactions-btn`
- Uses `data-account-id` attribute for account identification
- Calls `downloadTransactionsWithPlaid(accountId)` method

### 3. External Dependencies
- **Socket.IO**: For real-time communication (`socket.io.min.js`)
- **Plaid Link JS**: Custom implementation (`plaid-link.js`)
- **Automation CSS**: Styling for automation controls (`automation.css`)

### 4. Template Structure
- External HTML template: `plugins/PFinance/static/templates/automation-section.html`
- Container element: `#plugin-automation-section`
- Button element: `#download-transactions-btn`

## Implementation Notes

### Security Considerations
- Uses `nonce="{{ JS_NONCE }}"` for Content Security Policy compliance
- External scripts loaded from CDN (Socket.IO)
- Template loading from local plugin directory

### Error Handling
- Try-catch for template loading failures
- Console logging for debugging
- Graceful degradation if elements not found

### Integration Points
- Account ID passed from Twig template: `{{ account.id }}`
- Version parameter for cache busting: `{{ FF_VERSION }}`
- Nonce for security: `{{ JS_NONCE }}`

## Future Implementation Suggestions

1. **Modular Approach**: Consider creating a separate Plaid Link module
2. **Configuration**: Make Plaid Link settings configurable
3. **Error Handling**: Implement user-friendly error messages
4. **Loading States**: Add loading indicators for better UX
5. **Security**: Review and update security measures for production use

## Related Files (if they exist)
- `plugins/PFinance/static/css/automation.css`
- `plugins/PFinance/static/js/plaid-link.js`
- `plugins/PFinance/static/templates/automation-section.html`

## Automation Section Template Structure

The original automation section template (`automation-section.html`) included:

### UI Components
- **Status Display**: Real-time automation status with loading indicators
- **Action Buttons**: 
  - Download Statement
  - Download Transactions (Plaid Link integration)
  - Download & Consolidate
  - Download All Statements
  - Automation Config
- **Account Information**: Dynamic account details display

### Button Structure
```html
<button type="button"
        class="btn btn-info mb-2"
        id="download-transactions-btn"
        data-account-id="ACCOUNT_ID_PLACEHOLDER">
    <span class="fa fa-credit-card"></span> Download Transactions
</button>
```

## Plaid Link Controller Implementation

The `plaid-link.js` file contains a complete Plaid Link integration:

### Key Features
- **PlaidLinkController Class**: Main controller for Plaid Link functionality
- **Link Token Management**: Fetches link tokens from backend
- **Transaction Download**: `downloadTransactionsWithPlaid(accountId)` method
- **Error Handling**: Comprehensive error handling and user feedback
- **CSRF Protection**: Includes CSRF token in requests

### Integration Points
- **Backend API**: `/api/v1/plaid/link-token` endpoint
- **Global Object**: `window.plaidLinkController` for external access
- **Event Binding**: Automatic binding to download buttons

## Implementation Recommendations

### For Future Plaid Link Integration:

1. **Keep the PlaidLinkController**: The existing `plaid-link.js` is well-structured
2. **Template System**: The external template loading approach is flexible
3. **Security**: Maintain CSRF token usage and nonce implementation
4. **UI Components**: The button structure and styling can be reused
5. **Error Handling**: The existing error handling patterns are solid

### Minimal Implementation
If you want to add Plaid Link back in the future, you would need:
- The `plaid-link.js` file
- A simple button with `id="download-transactions-btn"`
- The PlaidLinkController initialization code
- Backend API endpoint for link tokens

---
*This reference was created when the automation section was removed from accounts/show.twig*
