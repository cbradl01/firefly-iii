# Account Field Definitions Reference

This document serves as a comprehensive reference for all account field definitions that have been considered for the Firefly III account management system. Fields are categorized by their purpose and include descriptions of when and how they might be used in the future.

## Table of Contents

1. [Core Fields (Currently Implemented)](#core-fields-currently-implemented)
2. [Deprecated Fields (Removed from Current Implementation)](#deprecated-fields-removed-from-current-implementation)
3. [Future Enhancement Fields](#future-enhancement-fields)
4. [Security & Compliance Fields](#security--compliance-fields)
5. [Advanced Financial Fields](#advanced-financial-fields)
6. [Institution-Level Fields](#institution-level-fields)
7. [System-Level Fields](#system-level-fields)
8. [Implementation Guidelines](#implementation-guidelines)

---

## Core Fields (Currently Implemented)

These fields are currently active in the system and should be maintained.

### Basic Information
- **`institution`** - Bank or financial institution name
  - **Usage**: Always required for bank accounts
  - **Future**: Could be linked to institution entities for better data consistency

- **`account_holder`** - Financial entity that owns this account
  - **Usage**: Always required, links to financial entities
  - **Future**: Could support multiple account holders for joint accounts

- **`account_status`** - Active, Inactive, Closed, Suspended
  - **Usage**: Always required for account state management
  - **Future**: Could include more granular statuses like "Pending", "Restricted"

- **`description`** - Account description
  - **Usage**: Optional user-friendly description
  - **Future**: Could support rich text or markdown

- **`account_number`** - Account number
  - **Usage**: Optional for most account types
  - **Future**: Could include encryption for sensitive data

- **`routing_number`** - Bank routing number
  - **Usage**: Optional for US bank accounts
  - **Future**: Could support international routing codes

- **`opening_date`** - When account was opened
  - **Usage**: Optional historical tracking
  - **Future**: Could be used for account age calculations

- **`closing_date`** - When account was closed
  - **Usage**: Optional for closed accounts
  - **Future**: Could trigger automatic archiving processes

- **`notes`** - General notes about the account
  - **Usage**: Optional free-form text
  - **Future**: Could support structured notes or tags

### Financial Fields
- **`current_balance`** - Current account balance
  - **Usage**: Optional for balance tracking
  - **Future**: Could support multiple currencies or historical balances

- **`interest_rate`** - Annual interest rate (%)
  - **Usage**: For interest-bearing accounts
  - **Future**: Could support variable rates or rate history

- **`minimum_balance`** - Minimum required balance
  - **Usage**: For accounts with minimum requirements
  - **Future**: Could trigger alerts when balance falls below

- **`credit_limit`** - Credit limit for credit accounts
  - **Usage**: For credit cards and lines of credit
  - **Future**: Could support multiple credit limits or limit history

- **`available_credit`** - Available credit remaining
  - **Usage**: For credit accounts
  - **Future**: Could be calculated automatically from transactions

- **`minimum_payment`** - Minimum payment amount
  - **Usage**: For debt accounts
  - **Future**: Could support variable minimum payments

- **`payment_due_date`** - When payments are due
  - **Usage**: For debt accounts
  - **Future**: Could support multiple due dates or grace periods

- **`payment_frequency`** - How often payments are made
  - **Usage**: For debt accounts
  - **Future**: Could support custom frequencies

### Feature Flags
- **`check_writing`** - Account has check writing capability
- **`debit_card`** - Account has debit card access
- **`credit_card`** - Account has credit card access
- **`online_banking`** - Account has online banking access
- **`mobile_banking`** - Account has mobile banking access
- **`atm_access`** - Account has ATM access
- **`wire_transfer`** - Account has wire transfer capability
- **`ach_transfer`** - Account has ACH transfer capability
- **`bill_pay`** - Account has bill payment service
- **`direct_deposit`** - Account has direct deposit setup
- **`automatic_payments`** - Account has automatic payment capability
- **`overdraft_protection`** - Account has overdraft protection enabled

### Investment Fields
- **`contribution_limit`** - Annual contribution limit
- **`current_contribution`** - Current year contributions
- **`employer_match`** - Employer matching percentage
- **`vesting_schedule`** - Vesting timeline
- **`withdrawal_penalties`** - Early withdrawal penalties
- **`required_minimum_distribution`** - RMD requirements
- **`investment_style`** - Conservative, Moderate, Aggressive

### Digital/Crypto Fields
- **`wallet_address`** - Crypto wallet address
- **`wallet_type`** - Digital wallet type
- **`crypto_type`** - Cryptocurrency type
- **`processor_type`** - Payment processor type
- **`api_key`** - API access key
- **`webhook_url`** - Webhook endpoint
- **`integration_status`** - Third-party integration status

### Fee Fields
- **`monthly_fee`** - Monthly maintenance fee
- **`annual_fee`** - Annual fee
- **`transaction_fee`** - Per-transaction fee
- **`late_payment_fee`** - Late payment penalty
- **`overdraft_fee`** - Overdraft fee amount

---

## Deprecated Fields (Removed from Current Implementation)

These fields were removed to simplify the system but are documented here for future reference.

### Redundant Fields
- **`name`** - Account name/identifier
  - **Removed Reason**: Institution + product name is sufficient
  - **Future Use**: Could be re-added if users need custom account names
  - **Implementation**: Simple text field, could be auto-generated from institution + type

- **`display_name`** - User-friendly display name
  - **Removed Reason**: Redundant with description
  - **Future Use**: Could be useful for complex naming scenarios
  - **Implementation**: Text field with character limits

- **`account_holder_type`** - Individual, Joint, Business, Trust, etc.
  - **Removed Reason**: Implied by account_holder entity
  - **Future Use**: Could be useful for joint accounts or complex ownership
  - **Implementation**: Dropdown with predefined options

### Institution-Level Fields
- **`iban`** - International Bank Account Number
  - **Removed Reason**: Should be institution-level, not account-level
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Text field with IBAN validation

- **`swift_code`** - SWIFT/BIC code
  - **Removed Reason**: Should be institution-level, not account-level
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Text field with SWIFT code validation

### Unclear Purpose Fields
- **`saving`** - Account has savings feature enabled
  - **Removed Reason**: Unclear purpose and usage
  - **Future Use**: Could be useful for distinguishing savings vs checking features
  - **Implementation**: Boolean checkbox

- **`zoom_level`** - Map zoom level
  - **Removed Reason**: Not account-relevant
  - **Future Use**: Could be useful for location-based account features
  - **Implementation**: Number field with range validation

---

## Future Enhancement Fields

These fields could be valuable additions in future versions of the system.

### Advanced Security Fields
- **`authorized_signers`** - People authorized to sign
  - **Future Use**: For business accounts or complex authorization
  - **Implementation**: JSON array of signer objects with names, roles, limits
  - **Considerations**: Privacy, data protection, audit trails

- **`signature_authority`** - Signature requirements
  - **Future Use**: For business accounts with specific signature requirements
  - **Implementation**: Text field or structured data
  - **Considerations**: Legal compliance, audit requirements

- **`biometric_access`** - Account has biometric access enabled
  - **Future Use**: For modern banking features
  - **Implementation**: Boolean checkbox
  - **Considerations**: Privacy, security implications

- **`two_factor_auth`** - Account has two-factor authentication enabled
  - **Future Use**: For security-conscious users
  - **Implementation**: Boolean checkbox
  - **Considerations**: User privacy, security best practices

### Advanced Financial Fields
- **`maximum_balance`** - Maximum allowed balance
  - **Future Use**: For accounts with balance limits
  - **Implementation**: Number field with currency support
  - **Considerations**: Regulatory compliance, account type restrictions

- **`overdraft_account`** - Account that provides overdraft protection
  - **Future Use**: For linking overdraft protection accounts
  - **Implementation**: Foreign key to account table
  - **Considerations**: Circular reference prevention, data integrity

- **`overdraft_limit`** - Overdraft protection limit
  - **Future Use**: For overdraft protection features
  - **Implementation**: Number field with currency support
  - **Considerations**: Regulatory compliance, fee calculations

### Advanced Fee Fields
- **`fee_structure`** - Detailed fee breakdown
  - **Future Use**: For complex fee structures
  - **Implementation**: JSON object with fee types and amounts
  - **Considerations**: Data structure design, fee calculation logic

- **`fee_waiver_conditions`** - Conditions for fee waivers
  - **Future Use**: For fee waiver tracking
  - **Implementation**: Text field or structured data
  - **Considerations**: Business logic complexity, user interface design

- **`early_withdrawal_penalty`** - Penalty for early withdrawal
  - **Future Use**: For retirement accounts or CDs
  - **Implementation**: Number field with percentage or fixed amount
  - **Considerations**: Regulatory compliance, calculation accuracy

### Advanced Investment Fields
- **`investment_style`** - Conservative, Moderate, Aggressive
  - **Future Use**: For investment account management
  - **Implementation**: Dropdown with predefined options
  - **Considerations**: Investment strategy alignment, risk assessment

### Advanced Digital Fields
- **`private_key_location`** - Where private keys are stored
  - **Future Use**: For crypto account security
  - **Implementation**: Text field (encrypted)
  - **Considerations**: Security, encryption, access control

---

## Security & Compliance Fields

These fields are important for security and compliance but were removed to simplify the current implementation.

### Security Fields
- **`pin_number`** - PIN for access
  - **Removed Reason**: Security risk to store
  - **Future Use**: Could be useful for account access management
  - **Implementation**: Encrypted field with access controls
  - **Considerations**: Encryption, access logging, security audits

- **`security_questions`** - Security question setup
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for account recovery
  - **Implementation**: JSON array of question-answer pairs (encrypted)
  - **Considerations**: Encryption, privacy, security best practices

### Compliance Fields
- **`audit_trail`** - Account has audit trail logging enabled
  - **Removed Reason**: System-level feature
  - **Future Use**: Could be useful for compliance tracking
  - **Implementation**: Boolean checkbox with logging configuration
  - **Considerations**: Performance impact, storage requirements

- **`required_documents`** - Required documentation
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for compliance tracking
  - **Implementation**: JSON array of document types
  - **Considerations**: Document management, compliance tracking

- **`verification_status`** - Account verification status
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for compliance tracking
  - **Implementation**: Dropdown with status options
  - **Considerations**: Compliance requirements, status tracking

- **`compliance_notes`** - Compliance-related notes
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for compliance tracking
  - **Implementation**: Text field with access controls
  - **Considerations**: Access control, audit requirements

- **`compliance_status`** - Regulatory compliance status
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for compliance tracking
  - **Implementation**: Dropdown with status options
  - **Considerations**: Regulatory requirements, status tracking

- **`reporting_requirements`** - Tax reporting needs
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for tax reporting
  - **Implementation**: JSON array of reporting requirements
  - **Considerations**: Tax compliance, reporting automation

---

## Advanced Financial Fields

These fields could be valuable for advanced financial management features.

### Balance and Credit Fields
- **`principal_balance`** - Principal amount owed
  - **Future Use**: For debt accounts with interest
  - **Implementation**: Number field with currency support
  - **Considerations**: Interest calculations, payment tracking

### Interest Fields
- **`interest_period`** - How often interest is calculated
  - **Future Use**: For interest-bearing accounts
  - **Implementation**: Dropdown with period options
  - **Considerations**: Interest calculation accuracy, compounding

### Payment Fields
- **`payment_frequency`** - How often payments are made
  - **Future Use**: For debt accounts
  - **Implementation**: Dropdown with frequency options
  - **Considerations**: Payment scheduling, automation

---

## Institution-Level Fields

These fields should be moved to institution entities rather than account entities.

### Institution Information
- **`fdic_insured`** - Account is FDIC insured
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Boolean checkbox on institution
  - **Considerations**: Regulatory compliance, insurance tracking

- **`fraud_protection`** - Account has fraud protection enabled
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Boolean checkbox on institution
  - **Considerations**: Security features, user awareness

- **`identity_theft_protection`** - Account has identity theft protection
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Boolean checkbox on institution
  - **Considerations**: Security features, user awareness

- **`account_protection`** - Account has account protection services
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Boolean checkbox on institution
  - **Considerations**: Security features, user awareness

- **`insurance_amount`** - Insurance coverage amount
  - **Future Use**: Could be added to institution entities
  - **Implementation**: Number field with currency support
  - **Considerations**: Insurance tracking, regulatory compliance

---

## System-Level Fields

These fields are more appropriate for system-level configuration rather than account-level data.

### System Configuration
- **`external_reference`** - External system reference
  - **Removed Reason**: Unnecessary complexity
  - **Future Use**: Could be useful for system integration
  - **Implementation**: Text field with validation
  - **Considerations**: System integration, data mapping

- **`internal_reference`** - Internal reference number
  - **Removed Reason**: Unnecessary complexity
  - **Future Use**: Could be useful for internal tracking
  - **Implementation**: Text field with validation
  - **Considerations**: Internal tracking, data organization

- **`priority`** - Account priority level
  - **Removed Reason**: Unnecessary complexity
  - **Future Use**: Could be useful for account management
  - **Implementation**: Dropdown with priority levels
  - **Considerations**: User interface design, account organization

- **`risk_level`** - Risk assessment
  - **Removed Reason**: Institution-level feature
  - **Future Use**: Could be useful for risk management
  - **Implementation**: Dropdown with risk levels
  - **Considerations**: Risk assessment, compliance requirements

### System Features
- **`tags`** - Account tags for organization
  - **Removed Reason**: Use notes instead
  - **Future Use**: Could be useful for account organization
  - **Implementation**: JSON array of tags
  - **Considerations**: Tag management, search functionality

- **`data_retention_period`** - How long data is kept
  - **Removed Reason**: System-level feature
  - **Future Use**: Could be useful for data management
  - **Implementation**: Dropdown with retention periods
  - **Considerations**: Data privacy, regulatory compliance

- **`export_capabilities`** - Data export options
  - **Removed Reason**: System-level feature
  - **Future Use**: Could be useful for data export
  - **Implementation**: JSON array of export formats
  - **Considerations**: Data export, user experience

- **`notification_preferences`** - Alert/notification settings
  - **Removed Reason**: System-level feature
  - **Future Use**: Could be useful for user notifications
  - **Implementation**: JSON object with notification settings
  - **Considerations**: User preferences, notification management

- **`reporting_frequency`** - How often reports are generated
  - **Removed Reason**: System-level feature
  - **Future Use**: Could be useful for reporting
  - **Implementation**: Dropdown with frequency options
  - **Considerations**: Reporting automation, user preferences

- **`statement_cycle`** - Statement generation cycle
  - **Removed Reason**: System-level feature
  - **Future Use**: Could be useful for statement management
  - **Implementation**: Dropdown with cycle options
  - **Considerations**: Statement generation, user preferences

---

## Implementation Guidelines

### When to Add New Fields

1. **User Demand**: Fields should be added based on actual user needs
2. **Regulatory Requirements**: Fields required for compliance should be prioritized
3. **System Integration**: Fields needed for third-party integrations
4. **Data Quality**: Fields that improve data accuracy and completeness

### How to Add New Fields

1. **Database Schema**: Add field to `field_definitions` table
2. **Model Updates**: Update relevant models and relationships
3. **Validation Rules**: Define appropriate validation rules
4. **User Interface**: Add field to relevant forms and views
5. **Documentation**: Update this reference document

### Field Design Principles

1. **Simplicity**: Keep fields simple and focused
2. **Consistency**: Use consistent naming and data types
3. **Validation**: Always include appropriate validation
4. **Documentation**: Document field purpose and usage
5. **Future-Proofing**: Consider future expansion needs

### Data Type Guidelines

- **Text Fields**: Use for names, descriptions, and free-form text
- **Number Fields**: Use for amounts, rates, and quantities
- **Date Fields**: Use for dates and timestamps
- **Boolean Fields**: Use for yes/no or on/off features
- **Dropdown Fields**: Use for predefined options
- **JSON Fields**: Use for complex structured data

### Security Considerations

1. **Sensitive Data**: Encrypt sensitive fields like PINs and keys
2. **Access Control**: Implement appropriate access controls
3. **Audit Trails**: Log changes to sensitive fields
4. **Data Privacy**: Consider privacy implications of stored data
5. **Compliance**: Ensure fields meet regulatory requirements

---

## Conclusion

This reference document provides a comprehensive overview of all account field definitions that have been considered for the Firefly III account management system. While the current implementation focuses on essential fields to maintain simplicity and usability, this document serves as a valuable resource for future development and enhancement.

When considering new fields, always evaluate their necessity, implementation complexity, and impact on user experience. The goal is to maintain a balance between functionality and simplicity while ensuring the system remains maintainable and user-friendly.

---

*Last Updated: October 18, 2025*
*Version: 1.0*
