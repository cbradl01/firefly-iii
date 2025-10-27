<?php

declare(strict_types=1);

namespace FireflyIII\FieldDefinitions;

class FieldDefinitions
{
    /**
     * Baseline default values by data type
     * These are used when no field-specific or template-specific default is defined
     */
    public const DATA_TYPE_DEFAULTS = [
        'string' => '',
        'text' => '',
        'decimal' => null,
        'integer' => null,
        'boolean' => false,
        'date' => null,
        'json' => null,
        'array' => null,
    ];

    /**
     * Fields that apply to all entity types (inheritance)
     */
    public const ENTITY_FIELDS = [
        'tax_id_type' => [
            'data_type' => 'string',
            'input_type' => 'select',
            'category' => 'tax',
            'validation' => 'in:ssn,ein,itin',
            'show_help' => true,
            'options' => [
                'ssn' => 'Social Security Number (SSN)',
                'ein' => 'Employer Identification Number (EIN)',
                'itin' => 'Individual Taxpayer Identification Number (ITIN)']
        ],
        'tax_id_number' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'tax',
            'validation' => 'string|max:20',
            'show_help' => true
        ],
        // Address fields common to all entity types
        'address' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'address',
            'validation' => 'string|max:500'
        ],
        'city' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'address',
            'validation' => 'string|max:100'
        ],
        'state' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'address',
            'validation' => 'string|max:100'
        ],
        'country' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'address',
            'validation' => 'string|max:100'
        ],
        'postal_code' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'address',
            'validation' => 'string|max:20'
        ],
        'trust_beneficiaries' => [
            'data_type' => 'json',
            'input_type' => 'beneficiaries',
            'category' => 'beneficiary',
            'validation' => 'nullable|string'
        ],
    ];

    /**
     * Account-specific fields (streamlined)
     */
    public const ACCOUNT_FIELDS = [
        // Core Required Fields
        'account_holders' => [
            'data_type' => 'array',
            'input_type' => 'financial_entity_multiselect',
            'category' => 'basic_info',
            'validation' => 'required|array|min:1',
            'overview_link' => true,
            'options' => [
                'exclude_entity_types' => ['institution']
            ]
        ],
        'institution' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'required|string|max:255',
            'overview_link' => true
        ],
        'product_name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'required|string|max:255'
        ],
        'active' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'basic_info',
            'validation' => 'boolean',
            'default' => true
        ],
        'currency_id' => [
            'data_type' => 'integer',
            'input_type' => 'currency_select',
            'category' => 'basic_info',
            'validation' => 'nullable|integer|exists:transaction_currencies,id'
        ],

        // Core Optional Fields
        'description' => [
            'data_type' => 'string',
            'input_type' => 'textarea',
            'category' => 'basic_info',
            'validation' => 'string|max:1000'
        ],
        'account_number' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'string|max:50'
        ],
        'routing_number' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'string|size:9'
        ],
        'opening_date' => [
            'data_type' => 'date',
            'input_type' => 'date',
            'category' => 'basic_info',
            'validation' => 'date'
        ],
        'closing_date' => [
            'data_type' => 'date',
            'input_type' => 'date',
            'category' => 'basic_info',
            'validation' => 'date|after:opening_date'
        ],
        'notes' => [
            'data_type' => 'string',
            'input_type' => 'textarea',
            'category' => 'basic_info',
            'validation' => 'string|max:1000'
        ],

        // Financial Fields
        'current_balance' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric'
        ],
        'virtual_balance' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric'
        ],
        'interest_rate' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric|min:0|max:100'
        ],
        'minimum_balance' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric|min:0'
        ],
        'credit_limit' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric|min:0'
        ],
        'available_credit' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric|min:0'
        ],
        'minimum_payment' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial',
            'validation' => 'numeric|min:0'
        ],
        'payment_due_date' => [
            'data_type' => 'date',
            'input_type' => 'date',
            'category' => 'financial',
            'validation' => 'date'
        ],
        'payment_frequency' => [
            'data_type' => 'string',
            'input_type' => 'select',
            'category' => 'financial',
            'validation' => 'in:weekly,biweekly,monthly,quarterly,annually',
            'options' => [
                'weekly' => 'Weekly',
                'biweekly' => 'Bi-weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually' => 'Annually'
            ]
        ],

        // Feature Flags
        'check_writing' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'debit_card' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'credit_card' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'online_banking' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features',
            'default' => true  // Override baseline boolean default of false
        ],
        'mobile_banking' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'atm_access' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'wire_transfer' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'ach_transfer' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'bill_pay' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'direct_deposit' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'automatic_payments' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],
        'overdraft_protection' => [
            'data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'
        ],

        // Investment Fields
        'contribution_limit' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'investment',
            'validation' => 'numeric|min:0'
        ],
        'current_contribution' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'investment',
            'validation' => 'numeric|min:0'
        ],
        'employer_match' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'investment',
            'validation' => 'numeric|min:0|max:100'
        ],
        'vesting_schedule' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'investment',
            'validation' => 'string|max:255'
        ],
        'withdrawal_penalties' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'investment',
            'validation' => 'numeric|min:0'
        ],
        'required_minimum_distribution' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'investment',
            'validation' => 'numeric|min:0'
        ],
        'investment_style' => [
            'data_type' => 'string',
            'input_type' => 'select',
            'category' => 'investment',
            'validation' => 'in:conservative,moderate,aggressive',
            'options' => [
                'conservative' => 'Conservative',
                'moderate' => 'Moderate',
                'aggressive' => 'Aggressive'
            ]
        ],

        // Digital/Crypto Fields
        'wallet_address' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'digital',
            'validation' => 'string|max:255'
        ],
        'wallet_type' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'digital',
            'validation' => 'string|max:100'
        ],
        'crypto_type' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'digital',
            'validation' => 'string|max:100'
        ],
        'processor_type' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'digital',
            'validation' => 'string|max:100'
        ],
        'api_key' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'digital',
            'validation' => 'string|max:255'
        ],
        'webhook_url' => [
            'data_type' => 'string',
            'input_type' => 'url',
            'category' => 'digital',
            'validation' => 'url|max:255'
        ],
        'integration_status' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'digital',
            'validation' => 'string|max:100'
        ],

        // Fee Fields
        'monthly_fee' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'fees',
            'validation' => 'numeric|min:0'
        ],
        'annual_fee' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'fees',
            'validation' => 'numeric|min:0'
        ],
        'transaction_fee' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'fees',
            'validation' => 'numeric|min:0'
        ],
        'late_payment_fee' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'fees',
            'validation' => 'numeric|min:0'
        ],
        'overdraft_fee' => [
            'data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'fees',
            'validation' => 'numeric|min:0'
        ]
    ];

    /**
     * Institution-specific fields
     */
    public const INSTITUTION_FIELDS = [
        // Basic Information
        'institution_name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'required|string|max:255'
        ],
        'institution_type' => [
            'data_type' => 'string',
            'input_type' => 'select',
            'category' => 'basic_info',
            'show_help' => true,
            'options' => [
                'bank' => 'Bank',
                'credit_union' => 'Credit Union',
                'brokerage' => 'Brokerage Firm',
                'investment_firm' => 'Investment Firm',
                'insurance_company' => 'Insurance Company',
                'fintech' => 'Fintech Company',
                'other' => 'Other']],

        // Contact Information
        'institution_phone' => [
            'data_type' => 'string',
            'input_type' => 'tel',
            'category' => 'contact',
            'validation' => 'string|max:20'
        ],
        'institution_email' => [
            'data_type' => 'string',
            'input_type' => 'email',
            'category' => 'contact',
            'validation' => 'email|max:255'
        ],
        'institution_website' => [
            'data_type' => 'string',
            'input_type' => 'url',
            'category' => 'contact',
            'validation' => 'url|max:255'
        ],
        'institution_customer_service_phone' => ['data_type' => 'string',
            'input_type' => 'tel',
            'category' => 'contact','validation' => 'string|max:20'],

        // Location Information
        'institution_address' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'location','validation' => 'string|max:500'],
        'institution_city' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'location','validation' => 'string|max:100'],
        'institution_state' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'location','validation' => 'string|max:100'],
        'institution_country' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'location','validation' => 'string|max:100'],
        'institution_postal_code' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'location','validation' => 'string|max:20'],
        'institution_latitude' => ['data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'location','validation' => ['numeric', 'between:-90,90']],
        'institution_longitude' => ['data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'location','validation' => ['numeric', 'between:-180,180']],

        // Regulatory Information
        'federal_reserve_id' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'regulatory','validation' => ['string', 'max:50']],
        'fdic_certificate_number' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'regulatory','validation' => ['string', 'max:50']],
        'ncua_number' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'regulatory','validation' => ['string', 'max:50']],
        'sec_number' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'regulatory','validation' => ['string', 'max:50']],

        // Business Information
        'established_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'business','validation' => 'date|before:today'],
        'assets_under_management' => ['data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'business','validation' => ['numeric', 'min:0']],
        'number_of_branches' => ['data_type' => 'integer',
            'input_type' => 'number',
            'category' => 'business','validation' => ['integer', 'min:0']],
        'number_of_employees' => ['data_type' => 'integer',
            'input_type' => 'number',
            'category' => 'business','validation' => ['integer', 'min:0']]];

    /**
     * Trust-specific fields
     */
    public const TRUST_FIELDS = [
        'trust_type' => ['data_type' => 'string',
            'input_type' => 'select',
            'category' => 'legal',
            'validation' => 'required|string',
            'options' => [
                'revocable_living' => 'Revocable Living Trust',
                'irrevocable_living' => 'Irrevocable Living Trust',
                'testamentary' => 'Testamentary Trust',
                'charitable' => 'Charitable Trust',
                'special_needs' => 'Special Needs Trust',
                'spendthrift' => 'Spendthrift Trust',
                'bypass' => 'Bypass Trust',
                'qtip' => 'QTIP Trust',
                'other' => 'Other']],
        'trustee_name' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'legal',
            'validation' => 'required|string|max:255'],
        'trust_established_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'legal','validation' => 'date|before:today'],
        'trust_termination_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'legal','validation' => 'date|after:trust_established_date'],
        'trust_purpose' => ['data_type' => 'string',
            'input_type' => 'textarea',
            'category' => 'legal','validation' => 'string|max:1000']];

    /**
     * Business-specific fields
     */
    public const BUSINESS_FIELDS = [
        'business_type' => ['data_type' => 'string',
            'input_type' => 'select',
            'category' => 'legal',
            'validation' => 'required|string',
            'options' => [
                'sole_proprietorship' => 'Sole Proprietorship',
                'partnership' => 'Partnership',
                'llc' => 'Limited Liability Company (LLC)',
                'corporation' => 'Corporation',
                's_corporation' => 'S Corporation',
                'c_corporation' => 'C Corporation',
                'nonprofit' => 'Nonprofit Corporation',
                'professional_corp' => 'Professional Corporation',
                'other' => 'Other']],
        'legal_structure' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'legal','validation' => 'string|max:255'],
        'registration_number' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'legal','validation' => 'string|max:100'],
        'license_number' => ['data_type' => 'string',
            'input_type' => 'text',
            'category' => 'legal','validation' => 'string|max:100'],
        'authorized_representatives' => ['data_type' => 'json',
            'input_type' => 'json',
            'category' => 'legal','validation' => 'array'],
        'corporate_resolution' => ['data_type' => 'string',
            'input_type' => 'textarea',
            'category' => 'legal','validation' => 'string|max:1000'],
        'business_established_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'legal','validation' => 'date|before:today'],
        'business_dissolution_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'legal','validation' => ['date', 'after:business_established_date']]];

    /**
     * Individual-specific fields
     */
    public const INDIVIDUAL_FIELDS = [
        // Basic fields for individuals
        'individual_name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'required|string|max:255'
        ],
        'display_name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => 'required|string|max:255',
            'show_help' => true
        ],
        'date_of_birth' => [
            'data_type' => 'date',
            'input_type' => 'date',
            'category' => 'personal',
            'validation' => 'date|before:today'
        ],
        'marital_status' => [
            'data_type' => 'string',
            'input_type' => 'select',
            'category' => 'tax',
            'options' => [
                'single' => 'Single',
                'married' => 'Married',
                'divorced' => 'Divorced',
                'widowed' => 'Widowed',
                'separated' => 'Separated',
                'domestic_partnership' => 'Domestic Partnership',
                'civil_union' => 'Civil Union',
                'other' => 'Other'
            ]
        ],
        'citizenship' => [
            'data_type' => 'string',
            'input_type' => 'select',
            'category' => 'tax',
            'options' => [
                'us_citizen' => 'US Citizen',
                'permanent_resident' => 'Permanent Resident',
                'visa_holder' => 'Visa Holder',
                'non_resident' => 'Non-Resident',
                'dual_citizen' => 'Dual Citizen',
                'other' => 'Other']]];

    /**
     * Get all fields for a specific target type
     */
    public static function getFieldsForTargetType(string $targetType): array
    {
        $fields = [];
        
        // Add specific fields for the target type first
        $specificFields = match ($targetType) {
            'account' => self::ACCOUNT_FIELDS,
            'institution' => self::INSTITUTION_FIELDS,
            'trust' => self::TRUST_FIELDS,
            'business' => self::BUSINESS_FIELDS,
            'individual' => self::INDIVIDUAL_FIELDS,
            default => []};
        
        $fields = array_merge($fields, $specificFields);
        
        // Add entity fields (inheritance) at the end for all entity types
        if (in_array($targetType, ['individual', 'trust', 'business', 'advisor', 'custodian', 'plan_administrator', 'institution'])) {
            $fields = array_merge($fields, self::ENTITY_FIELDS);
        }
        
        return $fields;
    }

    /**
     * Get fields grouped by category for a target type
     */
    public static function getFieldsByCategory(string $targetType): array
    {
        $fields = self::getFieldsForTargetType($targetType);
        $grouped = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            $category = $fieldData['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$fieldName] = $fieldData;
        }
        
        return $grouped;
    }

    /**
     * Get validation rules for a target type
     */
    public static function getValidationRules(string $targetType): array
    {
        $fields = self::getFieldsForTargetType($targetType);
        $rules = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            if (isset($fieldData['validation'])) {
                $validation = $fieldData['validation'];
                
                // If validation doesn't start with 'required', add nullable
                if (strpos($validation, 'required') !== 0) {
                    // Check if nullable is already in the validation string
                    if (strpos($validation, 'nullable') === false) {
                        $validation = 'nullable|' . $validation;
                    }
                }
                
                $rules[$fieldName] = $validation;
            }
        }
        
        return $rules;
    }

    /**
     * Get a specific field definition
     */
    public static function getField(string $fieldName, string $targetType): ?array
    {
        $fields = self::getFieldsForTargetType($targetType);
        return $fields[$fieldName] ?? null;
    }

    /**
     * Get display name for a field from translations
     */
    public static function getDisplayName(string $fieldName): string
    {
        return __('firefly.' . $fieldName) ?: $fieldName;
    }

    /**
     * Get description for a field from translations
     */
    public static function getDescription(string $fieldName): string
    {
        return __('firefly.' . $fieldName . '_description') ?: '';
    }

    /**
     * Get fields with translated display names and descriptions
     */
    public static function getFieldsWithTranslations(string $targetType): array
    {
        $fields = self::getFieldsForTargetType($targetType);
        $translatedFields = [];

        foreach ($fields as $fieldName => $fieldData) {
            $translatedFields[$fieldName] = array_merge($fieldData, [
                'display_name' => self::getDisplayName($fieldName),
                'description' => self::getDescription($fieldName)]);
        }

        return $translatedFields;
    }

    /**
     * Get fields grouped by category with translations
     */
    public static function getFieldsByCategoryWithTranslations(string $targetType): array
    {
        $fields = self::getFieldsWithTranslations($targetType);
        $grouped = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            $category = $fieldData['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$fieldName] = $fieldData;
        }
        
        return $grouped;
    }

    /**
     * Get field default value with hierarchical override support
     * 
     * Priority order:
     * 1. Template-specific default (highest priority)
     * 2. Field-specific default (from FieldDefinitions)
     * 3. Data-type baseline default (lowest priority)
     * 
     * @param string $fieldName
     * @param string $targetType
     * @param array|null $templateOverrides Template-specific field overrides
     * @return mixed
     */
    public static function getFieldDefault(string $fieldName, string $targetType, ?array $templateOverrides = null): mixed
    {
        $field = self::getField($fieldName, $targetType);
        
        if (!$field) {
            return null;
        }
        
        // Priority 1: Template-specific default (highest priority)
        if ($templateOverrides && isset($templateOverrides[$fieldName]['default'])) {
            return $templateOverrides[$fieldName]['default'];
        }
        
        // Priority 2: Field-specific default from FieldDefinitions
        if (isset($field['default'])) {
            return $field['default'];
        }
        
        // Priority 3: Data-type baseline default (lowest priority)
        $dataType = $field['data_type'] ?? 'string';
        return self::DATA_TYPE_DEFAULTS[$dataType] ?? null;
    }

    /**
     * Get all field defaults for a target type with template overrides
     * 
     * @param string $targetType
     * @param array|null $templateOverrides Template-specific field overrides
     * @return array
     */
    public static function getFieldDefaults(string $targetType, ?array $templateOverrides = null): array
    {
        $fields = self::getFieldsForTargetType($targetType);
        $defaults = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            $defaults[$fieldName] = self::getFieldDefault($fieldName, $targetType, $templateOverrides);
        }
        
        return $defaults;
    }

    /**
     * Get fields that should be displayed in the overview
     * By default, all fields are shown unless explicitly hidden
     * 
     * @param string $targetType
     * @return array
     */
    public static function getOverviewFields(string $targetType): array
    {
        $fields = self::getFieldsForTargetType($targetType);
        $overviewFields = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            // Show field unless explicitly hidden
            if (!isset($fieldData['hide_from_overview']) || $fieldData['hide_from_overview'] !== true) {
                $overviewFields[$fieldName] = $fieldData;
            }
        }
        
        return $overviewFields;
    }

    /**
     * Get the display value for a field in the overview
     * 
     * @param string $fieldName
     * @param mixed $value
     * @param array $fieldData
     * @return string
     */
    public static function getOverviewDisplayValue(string $fieldName, $value, array $fieldData): string
    {
        if (empty($value) && $value !== 0 && $value !== false) {
            return '<span class="text-muted">-</span>';
        }
        
        // Handle different data types
        switch ($fieldData['data_type'] ?? 'string') {
            case 'boolean':
                return $value ? '<span class="fa fa-check text-success"></span> Yes' : '<span class="fa fa-times text-muted"></span> No';
            
            case 'date':
                return $value ? date('M j, Y', strtotime($value)) : '<span class="text-muted">-</span>';
            
            case 'decimal':
                return number_format((float)$value, 2);
            
            case 'select':
                $options = $fieldData['options'] ?? [];
                return $options[$value] ?? $value;
            
            default:
                return htmlspecialchars($value);
        }
    }

    /**
     * Get the display label for a field (using translations)
     * 
     * @param string $fieldName
     * @return string
     */
    public static function getOverviewLabel(string $fieldName): string
    {
        return self::getDisplayName($fieldName);
    }

}
