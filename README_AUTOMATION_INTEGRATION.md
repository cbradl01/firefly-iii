# Firefly-III Automation Integration

This document describes the integration of automation controls into Firefly-III for automated statement and transaction downloads via the PFinance microservice.

## Overview

The automation integration adds a new section to Firefly-III account pages that provides:

- **Automated Statement Downloads**: Download monthly statements from financial institutions
- **Transaction Downloads**: Download transaction data for specific date ranges
- **Real-time Status Monitoring**: Live updates on automation operations
- **Institution Configuration**: Enable/disable automation for specific banks
- **Seamless Integration**: Native Firefly-III UI design and behavior

## Architecture

### Components

1. **UI Integration** (`resources/views/accounts/show.twig`)
   - Adds automation section to account show pages
   - Uses Firefly-III's existing template structure
   - Integrates with existing account information

2. **JavaScript Controller** (`public/v1/js/ff/automation/automation.js`)
   - Handles automation UI interactions
   - Communicates with PFinance microservice API
   - Provides real-time status updates

3. **Styling** (`public/v1/css/automation.css`)
   - Matches Firefly-III's design language
   - Responsive design for mobile devices
   - Dark mode support

4. **API Integration**
   - Connects to PFinance microservice endpoints
   - Handles authentication and error states
   - Provides user feedback

## Installation

### 1. Files Added

The following files have been added to Firefly-III:

```
firefly-iii/
├── resources/views/accounts/show.twig (modified)
├── public/v1/js/ff/automation/automation.js (new)
├── public/v1/css/automation.css (new)
├── setup_automation_integration.py (new)
└── README_AUTOMATION_INTEGRATION.md (new)
```

### 2. Run Setup Script

```bash
cd firefly-iii
python setup_automation_integration.py
```

### 3. Start PFinance Microservice

```bash
cd ../pfinance/pfinance-microservice
python run.py
```

### 4. Access Firefly-III

Navigate to any account page in Firefly-III to see the automation controls.

## UI Features

### Automation Section

The automation section appears on account show pages and includes:

- **Status Display**: Real-time automation status for the current account
- **Action Buttons**: Download statements, transactions, and configure settings
- **Account Information**: Details about automation support and configuration

### Controls Available

1. **📄 Download Statement**: Download the latest statement for the selected account
2. **💳 Download Transactions**: Download transaction data for the selected account
3. **📋 Download All Statements**: Download statements for all enabled accounts
4. **⚙️ Automation Config**: Open configuration modal to manage institutions

### Status Indicators

- **✅ Completed**: Operation completed successfully
- **⏳ Running**: Operation is currently running
- **❌ Failed**: Operation failed with error details
- **❓ Unknown**: No status information available

## Configuration

### Institution Management

Use the "⚙️ Automation Config" button to:

- **Enable/Disable Institutions**: Turn automation on/off for specific banks
- **View Supported Banks**: See which institutions are supported
- **Monitor Status**: Check automation status for each institution

### API Configuration

The integration connects to the PFinance microservice API:

- **Base URL**: `/api/v1`
- **Endpoints**: `/automation/*`
- **Authentication**: Uses existing Firefly-III session

## Usage

### Downloading Statements

1. Navigate to an account page in Firefly-III
2. Locate the "🤖 Automation" section
3. Click "📄 Download Statement"
4. Monitor the status display for progress
5. Check for success/error messages

### Downloading Transactions

1. Navigate to an account page in Firefly-III
2. Locate the "🤖 Automation" section
3. Click "💳 Download Transactions"
4. The system will download the last month's transactions
5. Monitor the status display for progress

### Managing Institutions

1. Click "⚙️ Automation Config" on any account page
2. View enabled and supported institutions
3. Click "Enable" or "Disable" for specific banks
4. Changes are applied immediately

## API Integration

### Endpoints Used

The Firefly-III integration calls these PFinance microservice endpoints:

- `GET /api/v1/automation/status/{account_id}` - Get automation status
- `POST /api/v1/automation/download-statement/{account_id}` - Download statement
- `POST /api/v1/automation/download-transactions/{account_id}` - Download transactions
- `POST /api/v1/automation/download-all-statements` - Download all statements
- `GET /api/v1/automation/config` - Get configuration
- `POST /api/v1/automation/enable-institution/{institution}` - Enable institution
- `POST /api/v1/automation/disable-institution/{institution}` - Disable institution

### Error Handling

The integration provides comprehensive error handling:

- **Network Errors**: Shows user-friendly error messages
- **API Errors**: Displays specific error details from the microservice
- **Validation Errors**: Prevents invalid operations
- **Timeout Handling**: Graceful handling of slow operations

## Styling

### Design Principles

The automation controls follow Firefly-III's design principles:

- **Consistent Colors**: Uses Firefly-III's color palette
- **Typography**: Matches existing font styles and sizes
- **Spacing**: Follows Firefly-III's spacing guidelines
- **Icons**: Uses Font Awesome icons consistent with the app

### Responsive Design

The automation section is fully responsive:

- **Desktop**: Full-width layout with side-by-side sections
- **Tablet**: Stacked layout with appropriate spacing
- **Mobile**: Single-column layout with touch-friendly buttons

### Dark Mode Support

The automation controls support Firefly-III's dark mode:

- **Automatic Detection**: Detects system dark mode preference
- **Color Adaptation**: Adjusts colors for dark backgrounds
- **Contrast**: Maintains proper contrast ratios

## Troubleshooting

### Common Issues

1. **Automation controls don't appear**
   - Check browser console for JavaScript errors
   - Ensure automation.js and automation.css are loaded
   - Verify the account page template was updated

2. **API calls fail**
   - Ensure PFinance microservice is running
   - Check CORS settings in the microservice
   - Verify the API base URL is correct

3. **Automation doesn't work**
   - Check LastPass credentials are configured
   - Verify institution is enabled in configuration
   - Check automation logs for errors

### Debug Mode

Enable debug logging in the browser console:

```javascript
// In browser console
localStorage.setItem('automation_debug', 'true');
```

### Logs

Check these locations for error information:

- **Browser Console**: JavaScript errors and API responses
- **PFinance Microservice Logs**: Server-side automation logs
- **Firefly-III Logs**: Application logs

## Development

### Adding New Features

To extend the automation integration:

1. **New API Endpoints**: Add to PFinance microservice first
2. **UI Controls**: Add buttons to the automation section
3. **JavaScript**: Implement new functionality in automation.js
4. **Styling**: Add CSS for new UI elements

### Customization

The integration can be customized:

- **Colors**: Modify automation.css for different color schemes
- **Layout**: Adjust the template structure in show.twig
- **Functionality**: Extend the JavaScript controller
- **API**: Add new endpoints to the microservice

## Security

### Data Privacy

- **Local Processing**: All automation runs locally via the microservice
- **No External Services**: No data is sent to external services
- **Secure Storage**: Downloaded files are stored securely

### Authentication

- **Session-based**: Uses Firefly-III's existing authentication
- **API Security**: Microservice endpoints are protected
- **Credential Management**: Uses LastPass for secure credential storage

## Performance

### Optimization

The integration is optimized for performance:

- **Lazy Loading**: JavaScript loads only when needed
- **Efficient Polling**: Status updates use minimal resources
- **Caching**: API responses are cached where appropriate
- **Minimal DOM**: Lightweight UI components

### Resource Usage

- **Memory**: Minimal memory footprint
- **CPU**: Low CPU usage during idle
- **Network**: Efficient API calls with minimal overhead

## Support

For issues and questions:

1. Check the troubleshooting section above
2. Review the PFinance microservice documentation
3. Check browser console for error details
4. Verify all components are properly configured

## License

This integration is part of the Firefly-III project and follows the same licensing terms.
