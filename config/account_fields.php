<?php

/**
 * Account Field Definitions
 * 
 * This file defines all available account fields for templates.
 * Field names are stable identifiers used in code.
 * Labels can be changed without breaking functionality.
 */

use FireflyIII\Constants\AccountFieldNames;

return [
    // Basic account fields
    AccountFieldNames::ACCOUNT_NUMBER => [
        'label' => 'firefly.account_number',
        'type' => 'text',
        'description' => 'The account number or identifier',
        'required' => false,
    ],
    AccountFieldNames::ROUTING_NUMBER => [
        'label' => 'firefly.routing_number',
        'type' => 'text',
        'description' => 'Bank routing number (for US accounts)',
        'required' => false,
    ],
    AccountFieldNames::INSTITUTION => [
        'label' => 'firefly.institution',
        'type' => 'text',
        'description' => 'The financial institution managing the account',
        'required' => true,
    ],
    AccountFieldNames::OWNER => [
        'label' => 'firefly.owner',
        'type' => 'text',
        'description' => 'Account owner name',
        'required' => true,
    ],
    AccountFieldNames::PRODUCT_NAME => [
        'label' => 'firefly.product_name',
        'type' => 'text',
        'description' => 'Specific product or account type name',
        'required' => false,
    ],
    
    // Select fields
    'liability_direction' => [
        'label' => 'firefly.liability_direction',
        'type' => 'select',
        'description' => 'Direction of liability (credit/debit)',
        'required' => false,
        'options' => ['debit', 'credit'],
    ],
    'overdraft_protection' => [
        'label' => 'firefly.overdraft_protection',
        'type' => 'select',
        'description' => 'Overdraft protection status',
        'required' => false,
        'options' => ['yes', 'no'],
    ],
    
    // Retirement account fields
    'plan_administrator' => [
        'label' => 'firefly.plan_administrator',
        'type' => 'text',
        'description' => 'Employer or entity administering the plan',
        'required' => false,
    ],
    'beneficiaries' => [
        'label' => 'firefly.beneficiaries',
        'type' => 'text',
        'description' => 'Account beneficiaries',
        'required' => false,
    ],
    'contribution_limit' => [
        'label' => 'firefly.contribution_limit',
        'type' => 'text',
        'description' => 'Annual contribution limit',
        'required' => false,
    ],
    'current_contribution' => [
        'label' => 'firefly.current_contribution',
        'type' => 'text',
        'description' => 'Current year contribution amount',
        'required' => false,
    ],
    'employer_match' => [
        'label' => 'firefly.employer_match',
        'type' => 'text',
        'description' => 'Employer matching contribution details',
        'required' => false,
    ],
    
    // Investment account fields
    'investment_style' => [
        'label' => 'firefly.investment_style',
        'type' => 'select',
        'description' => 'Investment approach or style',
        'required' => false,
        'options' => ['conservative', 'moderate', 'aggressive', 'custom'],
    ],
    
    // Trust account fields
    'trust_name' => [
        'label' => 'firefly.trust_name',
        'type' => 'text',
        'description' => 'Name of the trust',
        'required' => false,
    ],
    'trustee_name' => [
        'label' => 'firefly.trustee_name',
        'type' => 'text',
        'description' => 'Name of the trustee managing the account',
        'required' => false,
    ],
    'trust_type' => [
        'label' => 'firefly.trust_type',
        'type' => 'select',
        'description' => 'Type of trust',
        'required' => false,
        'options' => ['revocable', 'irrevocable', 'testamentary'],
    ],
    
    // Custodial account fields
    'custodian_name' => [
        'label' => 'firefly.custodian_name',
        'type' => 'text',
        'description' => 'Name of the custodian holding the assets',
        'required' => false,
    ],
    'minor_name' => [
        'label' => 'firefly.minor_name',
        'type' => 'text',
        'description' => 'Name of the minor for custodial accounts',
        'required' => false,
    ],
    'account_type' => [
        'label' => 'firefly.account_type',
        'type' => 'select',
        'description' => 'Specific account type classification',
        'required' => false,
    ],
    
    // Cash management fields
    'check_writing' => [
        'label' => 'firefly.check_writing',
        'type' => 'select',
        'description' => 'Check writing privileges',
        'required' => false,
        'options' => ['yes', 'no'],
    ],
    'debit_card' => [
        'label' => 'firefly.debit_card',
        'type' => 'select',
        'description' => 'Debit card availability',
        'required' => false,
        'options' => ['yes', 'no'],
    ],
];
