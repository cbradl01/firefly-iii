<?php

/**
 * Account Field Names Constants
 * 
 * Centralized constants for all account field names to avoid hard-coding
 * strings throughout the codebase. This makes maintenance much easier.
 */
declare(strict_types=1);

namespace FireflyIII\Constants;

class AccountFieldNames
{
    // Basic account fields
    public const ACCOUNT_NUMBER = 'account_number';
    public const ROUTING_NUMBER = 'routing_number';
    public const INSTITUTION = 'institution';
    public const OWNER = 'owner';
    public const PRODUCT_NAME = 'product_name';
    
    // Select fields
    public const ACCOUNT_ROLE = 'account_role';
    public const LIABILITY_DIRECTION = 'liability_direction';
    public const OVERDRAFT_PROTECTION = 'overdraft_protection';
    
    // Retirement account fields
    public const PLAN_ADMINISTRATOR = 'plan_administrator';
    public const BENEFICIARIES = 'beneficiaries';
    public const CONTRIBUTION_LIMIT = 'contribution_limit';
    public const CURRENT_CONTRIBUTION = 'current_contribution';
    public const EMPLOYER_MATCH = 'employer_match';
    
    // Investment account fields
    public const INVESTMENT_STYLE = 'investment_style';
    
    // Trust account fields
    public const TRUST_NAME = 'trust_name';
    public const TRUSTEE_NAME = 'trustee_name';
    public const TRUST_TYPE = 'trust_type';
    
    // Custodial account fields
    public const CUSTODIAN_NAME = 'custodian_name';
    public const MINOR_NAME = 'minor_name';
    public const ACCOUNT_TYPE = 'account_type';
    
    // Cash management fields
    public const CHECK_WRITING = 'check_writing';
    public const DEBIT_CARD = 'debit_card';
    
    // Legacy/alternative field names (for backward compatibility)
    public const BROKERAGE_FIRM = 'brokerage_firm';
    public const BUSINESS_NAME = 'business_name';
    public const BUSINESS_TYPE = 'business_type';
    public const INTEREST_RATE = 'interest_rate';
    public const MINIMUM_BALANCE = 'minimum_balance';
    public const TARGET_AMOUNT = 'target_amount';
    public const CURRENT_BALANCE = 'current_balance';
    public const JOINT_OWNERS = 'joint_owners';
    public const EMPLOYER_ID = 'employer_id';
    public const EMPLOYER_NAME = 'employer_name';
    public const EMPLOYER_CONTRIBUTION = 'employer_contribution';
    public const CURRENCY_CODE = 'currency_code';
    public const LOCATION = 'location';
    public const WALLET_ADDRESS = 'wallet_address';
    public const WALLET_TYPE = 'wallet_type';
    public const EXCHANGE = 'exchange';
    public const EMAIL = 'email';
    public const USERNAME = 'username';
    public const CREDIT_LIMIT = 'credit_limit';
    public const PAYMENT_DUE_DATE = 'payment_due_date';
    public const LOAN_AMOUNT = 'loan_amount';
    public const VEHICLE_INFO = 'vehicle_info';
    public const LENDER_NAME = 'lender_name';
    public const PROPERTY_ADDRESS = 'property_address';
    public const SCHOOL_NAME = 'school_name';
    public const LOAN_PURPOSE = 'loan_purpose';
    public const ISA_TYPE = 'isa_type';
    public const CONTRIBUTION_RATE = 'contribution_rate';
    
    /**
     * Get all field name constants as an array
     */
    public static function getAll(): array
    {
        return [
            self::ACCOUNT_NUMBER,
            self::ROUTING_NUMBER,
            self::INSTITUTION,
            self::OWNER,
            self::PRODUCT_NAME,
            self::ACCOUNT_ROLE,
            self::LIABILITY_DIRECTION,
            self::OVERDRAFT_PROTECTION,
            self::PLAN_ADMINISTRATOR,
            self::BENEFICIARIES,
            self::CONTRIBUTION_LIMIT,
            self::CURRENT_CONTRIBUTION,
            self::EMPLOYER_MATCH,
            self::INVESTMENT_STYLE,
            self::TRUST_NAME,
            self::TRUSTEE_NAME,
            self::TRUST_TYPE,
            self::CUSTODIAN_NAME,
            self::MINOR_NAME,
            self::ACCOUNT_TYPE,
            self::CHECK_WRITING,
            self::DEBIT_CARD,
        ];
    }
    
    /**
     * Get commonly used field combinations
     */
    public static function getCommonFieldSets(): array
    {
        return [
            'basic_account_fields' => [
                self::INSTITUTION,
                self::OWNER,
                self::PRODUCT_NAME,
            ],
            'banking_details_fields' => [
                self::ACCOUNT_NUMBER,
                self::ROUTING_NUMBER,
            ],
            'retirement_account_fields' => [
                self::PLAN_ADMINISTRATOR,
                self::BENEFICIARIES,
                self::CONTRIBUTION_LIMIT,
                self::CURRENT_CONTRIBUTION,
                self::EMPLOYER_MATCH,
            ],
            'trust_account_fields' => [
                self::TRUST_NAME,        // Name of the trust (this becomes the "owner")
                self::TRUSTEE_NAME,      // Person/entity managing the trust
                self::BENEFICIARIES,     // People who benefit from the trust
                self::TRUST_TYPE,        // Optional: type of trust (revocable, irrevocable, etc.)
            ],
            'custodial_account_fields' => [
                self::CUSTODIAN_NAME,    // Person/entity holding the assets
                self::MINOR_NAME,        // Name of the minor
            ],
            'investment_account_fields' => [
                self::INVESTMENT_STYLE,
            ],
            'cash_management_fields' => [
                self::CHECK_WRITING,
                self::DEBIT_CARD,
                self::OVERDRAFT_PROTECTION,
            ],
        ];
    }
    
    /**
     * Get fields for trust accounts specifically
     * Trust accounts have a special structure where the trust itself is the "owner"
     */
    public static function getTrustAccountFields(): array
    {
        return [
            self::TRUST_NAME,        // The trust name (becomes the account "owner")
            self::TRUSTEE_NAME,      // Person/entity managing the trust
            self::BENEFICIARIES,     // People who benefit from the trust
            self::TRUST_TYPE,        // Optional: type of trust
            self::INSTITUTION,       // Financial institution holding the trust assets
            self::PRODUCT_NAME,      // Specific trust product name
        ];
    }
    
    /**
     * Get fields for custodial accounts specifically
     * Custodial accounts are held by a custodian for a minor
     */
    public static function getCustodialAccountFields(): array
    {
        return [
            self::CUSTODIAN_NAME,    // Person/entity holding the assets
            self::MINOR_NAME,        // Name of the minor
            self::INSTITUTION,       // Financial institution
            self::PRODUCT_NAME,      // Specific account product name
        ];
    }
}
