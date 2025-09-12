# Download and Consolidate Feature

## Overview

This feature adds a new "Download & Consolidate" button to the Firefly III account interface that combines two existing operations:
1. Download transactions from the bank using LastPass and Selenium automation
2. Consolidate the downloaded transactions

## Implementation Details

### Architecture

The automation functionality is implemented as a plugin architecture where:
- **Firefly III** provides the UI integration (buttons and templates)
- **PFinance Microservice** provides the automation logic and JavaScript controller
- The JavaScript is served from the microservice and loaded into Firefly III pages

### Frontend Changes

#### 1. Button Addition (`resources/views/accounts/show.twig`)
- Added a new button with ID `download-and-consolidate-btn` in the automation controls section
- Button uses Bootstrap styling with primary color and includes both download and compress icons
- Button is positioned between the "Download Transactions" and "Download All Statements" buttons
- Updated script reference to load automation.js from the pfinance microservice

#### 2. JavaScript Handler (`pfinance-microservice/static/js/automation.js`)
- Located in the pfinance microservice for proper separation of concerns
- Added event listener for the new button in the `bindEvents()` method
- Implemented `downloadAndConsolidate()` method that:
  - Checks for LastPass authentication
  - Downloads transactions using the existing automation service
  - Consolidates transactions using the existing consolidation API
  - Provides user feedback throughout the process
  - Reloads the page after successful completion
- Updated `submitLastPassAuth()` to handle the new pending action type

### Backend Integration

The feature leverages existing backend services:
- **Download**: Uses the pfinance microservice automation endpoints
- **Consolidate**: Uses the Firefly III API that proxies to the pfinance microservice

### User Experience

1. User clicks "Download & Consolidate" button
2. If not authenticated with LastPass, authentication modal appears
3. After authentication, the process begins:
   - Button shows "Downloading & Consolidating..." with spinner
   - User sees "Downloading transactions..." message
   - After download completes, user sees "Download completed. Now consolidating..."
   - Upon completion, user sees success message and page reloads
4. If any step fails, appropriate error messages are displayed

### Error Handling

- Validates account selection before starting
- Checks LastPass authentication status
- Handles download failures gracefully
- Handles consolidation failures gracefully
- Provides clear error messages to the user
- Restores button state on completion or error

## Usage

1. Navigate to any account page in Firefly III (`/accounts/show/<account_id>/all`)
2. Look for the "🤖 Automation Controls" section
3. Click the "Download & Consolidate" button (blue button with download and compress icons)
4. Authenticate with LastPass if prompted
5. Wait for the process to complete

## Technical Notes

- The feature maintains the same security model as existing automation features
- Uses the same LastPass credential storage and encryption
- Follows the same error handling patterns as other automation features
- Integrates seamlessly with the existing automation controller architecture

## Plugin Architecture

The automation functionality is designed as a plugin that can be developed and maintained separately from the core Firefly III application:

### File Organization
- **Firefly III**: Contains only the UI integration (button in template)
- **PFinance Microservice**: Contains the automation logic and JavaScript controller
- **Static Assets**: JavaScript is served from the microservice at `http://localhost:5001/static/js/automation.js`

### Benefits
- **Separation of Concerns**: Automation logic stays with the automation service
- **Independent Development**: Can be developed and deployed separately
- **Version Control**: Automation code is tracked in the pfinance repository
- **Maintainability**: Clear boundaries between UI and business logic

### Deployment
1. Deploy the pfinance microservice with the updated automation.js
2. Update Firefly III template to reference the microservice's static assets
3. Both services can be updated independently
