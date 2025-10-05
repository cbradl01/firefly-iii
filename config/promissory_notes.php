<?php

/*
 * Promissory Notes Configuration
 * 
 * This configuration file provides settings specific to promissory note management
 * in Firefly III using the existing liability system.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Promissory Note Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for promissory note accounts when created.
    |
    */
    'defaults' => [
        'liability_direction' => 'credit', // I am owed this amount
        'include_net_worth' => true,
        'active' => true,
        'interest_period' => 'monthly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Naming patterns for promissory note related accounts.
    |
    */
    'naming' => [
        'promissory_note_pattern' => 'Promissory Note - {borrower_name}',
        'interest_income_pattern' => 'Interest Income - {borrower_name}',
        'borrower_name_placeholder' => '{borrower_name}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Descriptions
    |--------------------------------------------------------------------------
    |
    | Default transaction descriptions for promissory note transactions.
    |
    */
    'descriptions' => [
        'opening_balance' => 'Initial promissory note principal',
        'interest_payment' => 'Interest payment from {borrower_name}',
        'principal_payment' => 'Principal payment from {borrower_name}',
        'balloon_payment' => 'Final balloon payment from {borrower_name}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Interest Calculation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for interest calculations and periods.
    |
    */
    'interest' => [
        'periods' => [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly', 
            'quarterly' => 'Quarterly',
            'half-year' => 'Semi-Annual',
            'yearly' => 'Annual',
        ],
        'calculation_methods' => [
            'simple' => 'Simple Interest',
            'compound' => 'Compound Interest',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for promissory note data.
    |
    */
    'validation' => [
        'min_principal' => 1.00,
        'max_principal' => 999999999.99,
        'min_interest_rate' => 0.01,
        'max_interest_rate' => 50.00,
        'required_fields' => [
            'borrower_name',
            'principal_amount',
            'interest_rate',
            'interest_period',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Settings
    |--------------------------------------------------------------------------
    |
    | Settings for promissory note reporting and analytics.
    |
    */
    'reporting' => [
        'default_date_range' => '1Y', // 1 year
        'include_interest_income' => true,
        'include_principal_balance' => true,
        'group_by_borrower' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automated promissory note management.
    |
    */
    'automation' => [
        'auto_calculate_interest' => true,
        'auto_record_interest_payments' => false, // Manual approval recommended
        'reminder_days_before_due' => 7,
        'default_payment_frequency' => 'monthly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with external systems.
    |
    */
    'integration' => [
        'api_enabled' => true,
        'webhook_events' => [
            'promissory_note_created',
            'interest_payment_received',
            'principal_payment_received',
            'note_paid_off',
        ],
    ],
];
