<?php

declare(strict_types=1);

namespace FireflyIII\FieldDefinitions;

class FieldDefinitions
{
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
     * Account-specific fields
     */
    public const ACCOUNT_FIELDS = [
        // Basic Information
        'name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'required' => true,
            'validation' => 'required|string|max:255'],
        'display_name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'required' => true,
            'validation' => 'string|max:255'],
        'description' => [
            'data_type' => 'string',
            'input_type' => 'textarea',
            'category' => 'basic_info',
            'validation' => 'string|max:1000'],
        'account_number' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => ['string', 'max:50']
        ],
        'routing_number' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => ['string', 'size:9']
        ],
        'iban' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => ['string', 'max:34']
        ],
        'swift_code' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'validation' => ['string', 'size:8,11']
        ],
        'account_holder_type' => ['data_type' => 'string',
            'input_type' => 'select',
            'category' => 'basic_info','options' => [
                'individual' => 'Individual',
                'joint' => 'Joint',
                'business' => 'Business',
                'trust' => 'Trust',
                'custodial' => 'Custodial',
                'other' => 'Other']],
        'account_status' => ['data_type' => 'string',
            'input_type' => 'select',
            'category' => 'basic_info','options' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'closed' => 'Closed',
                'suspended' => 'Suspended']],
        'opening_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'basic_info','validation' => ['date']],
        'closing_date' => ['data_type' => 'date',
            'input_type' => 'date',
            'category' => 'basic_info','validation' => ['date', 'after:opening_date']],

        // Legal
        'power_of_attorney' => ['data_type' => 'string',
            'input_type' => 'textarea',
            'category' => 'legal','validation' => 'string|max:1000'],

        // Financial
        'interest_rate' => ['data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial','validation' => ['numeric', 'min:0', 'max:100']],
        'minimum_balance' => ['data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial','validation' => ['numeric', 'min:0']],
        'credit_limit' => ['data_type' => 'decimal',
            'input_type' => 'number',
            'category' => 'financial','validation' => ['numeric', 'min:0']],

        // Features
        'check_writing' => ['data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'],
        'debit_card' => ['data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features'],
        'online_banking' => ['data_type' => 'boolean',
            'input_type' => 'checkbox',
            'category' => 'features']];

    /**
     * Institution-specific fields
     */
    public const INSTITUTION_FIELDS = [
        // Basic Information
        'institution_name' => [
            'data_type' => 'string',
            'input_type' => 'text',
            'category' => 'basic_info',
            'required' => true,
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
            'category' => 'legal','required' => true,
            'validation' => ['required', 'string'],
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
            'category' => 'legal','required' => true,
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
            'category' => 'legal','required' => true,
            'validation' => ['required', 'string'],
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
            'required' => true,
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
                $rules[$fieldName] = $fieldData['validation'];
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
}
