<?php

/**
 * AccountFieldValidationService.php
 * Copyright (c) 2024 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use Illuminate\Support\Facades\Log;

/**
 * Service for validating account field requirements based on account type
 */
class AccountFieldValidationService
{
    /**
     * Validate that all required fields are present for the given account type
     *
     * @param array $data Account data to validate
     * @param AccountType $accountType The account type
     * @throws FireflyException If required fields are missing
     */
    public function validateRequiredFields(array $data, AccountType $accountType): void
    {
        $requiredFields = $this->getRequiredFields($accountType, $data);
        $missingFields = [];

        // Check all required fields
        foreach ($requiredFields as $field) {
            if (!$this->hasValidValue($data, $field)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = sprintf(
                'Account type "%s" requires the following fields: %s',
                $accountType->type,
                implode(', ', $missingFields)
            );
            
            Log::error('Account field validation failed', [
                'account_type' => $accountType->type,
                'missing_fields' => $missingFields,
                'provided_data' => array_keys($data)
            ]);
            
            throw new FireflyException($message);
        }
    }

    /**
     * Get all valid fields (required + optional) for an account type
     *
     * @param AccountType $accountType
     * @param array $data Account data (needed to determine credit card assets)
     * @return array Array of valid field names
     */
    public function getValidFields(AccountType $accountType, array $data = []): array
    {
        $requirements = $this->getFieldRequirements($accountType);
        $validFields = [];

        // Add shared fields (with safety check)
        if (isset($requirements['shared'])) {
            $validFields = array_merge(
                $validFields,
                $requirements['shared']['required'] ?? [],
                $requirements['shared']['optional'] ?? []
            );
        }

        // Add type-specific fields from metadata schema
        if (isset($requirements['type_specific'])) {
            $validFields = array_merge(
                $validFields,
                $requirements['type_specific']['required'] ?? [],
                $requirements['type_specific']['optional'] ?? []
            );
        }

        // All field requirements now come from metadata_schema
        // No fallback to old config-based approach needed

        return array_unique($validFields);
    }

    /**
     * Get field requirements for an account type
     *
     * @param AccountType $accountType
     * @return array Field requirements structure
     */
    public function getFieldRequirements(AccountType $accountType): array
    {
        // Get requirements from the account type's metadata schema
        $metadataSchema = $accountType->metadata_schema;
        if ($metadataSchema) {
            $schema = is_string($metadataSchema) ? json_decode($metadataSchema, true) : $metadataSchema;
            
            if ($schema && isset($schema['required_fields']) && isset($schema['optional_fields'])) {
                return [
                    'shared' => [
                        'required' => ['name', 'active', 'currency_id', 'institution', 'owner', 'product_name'],
                        'optional' => ['account_number', 'BIC', 'include_net_worth', 'notes', 'iban', 'category', 'behavior', 'account_type']
                    ],
                    'type_specific' => [
                        'required' => $schema['required_fields'] ?? [],
                        'optional' => $schema['optional_fields'] ?? []
                    ]
                ];
            }
        }
        
        // No fallback - all requirements should come from metadata_schema
        return [
            'shared' => [
                'required' => ['name', 'active', 'currency_id', 'institution', 'owner', 'product_name'],
                'optional' => ['account_number', 'BIC', 'include_net_worth', 'notes', 'iban', 'category', 'behavior', 'account_type']
            ],
            'type_specific' => [
                'required' => [],
                'optional' => []
            ]
        ];
    }

    /**
     * Get required fields for an account type
     *
     * @param AccountType $accountType
     * @param array $data Account data (needed to determine credit card assets)
     * @return array Array of required field names
     */
    public function getRequiredFields(AccountType $accountType, array $data = []): array
    {
        $requirements = $this->getFieldRequirements($accountType);
        $requiredFields = [];

        // Add shared required fields (with safety check)
        if (isset($requirements['shared'])) {
            $requiredFields = array_merge($requiredFields, $requirements['shared']['required'] ?? []);
        }

        // Add type-specific required fields from metadata_schema
        if (isset($requirements['type_specific'])) {
            $requiredFields = array_merge($requiredFields, $requirements['type_specific']['required'] ?? []);
        }

        return array_unique($requiredFields);
    }

    /**
     * Get optional fields for an account type
     *
     * @param AccountType $accountType
     * @param array $data Account data (needed to determine credit card assets)
     * @return array Array of optional field names
     */
    public function getOptionalFields(AccountType $accountType, array $data = []): array
    {
        $requirements = $this->getFieldRequirements($accountType);
        $optionalFields = [];

        // Add shared optional fields (with safety check)
        if (isset($requirements['shared'])) {
            $optionalFields = array_merge($optionalFields, $requirements['shared']['optional'] ?? []);
        }

        // Add type-specific optional fields from metadata_schema
        if (isset($requirements['type_specific'])) {
            $optionalFields = array_merge($optionalFields, $requirements['type_specific']['optional'] ?? []);
        }

        return array_unique($optionalFields);
    }

    /**
     * Check if a field has a valid (non-empty) value
     *
     * @param array $data
     * @param string $field
     * @return bool
     */
    private function hasValidValue(array $data, string $field): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }

        $value = $data[$field];
        
        // Consider null, empty string, and empty array as invalid
        if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
            return false;
        }

        return true;
    }


    /**
     * Get field requirements summary for an account type (useful for UI/documentation)
     *
     * @param AccountType $accountType
     * @param array $data Account data (needed to determine credit card assets)
     * @return array Array with 'required' and 'optional' field arrays
     */
    public function getFieldRequirementsSummary(AccountType $accountType, array $data = []): array
    {
        return [
            'required' => $this->getRequiredFields($accountType, $data),
            'optional' => $this->getOptionalFields($accountType, $data)
        ];
    }

    /**
     * Check if a specific field is required for an account type
     *
     * @param string $fieldName
     * @param AccountType $accountType
     * @param array $data Account data (needed to determine credit card assets)
     * @return bool
     */
    public function isFieldRequired(string $fieldName, AccountType $accountType, array $data = []): bool
    {
        $requiredFields = $this->getRequiredFields($accountType, $data);
        return in_array($fieldName, $requiredFields, true);
    }

    /**
     * Check if a specific field is valid for an account type
     *
     * @param string $fieldName
     * @param AccountType $accountType
     * @param array $data Account data (needed to determine credit card assets)
     * @return bool
     */
    public function isFieldValid(string $fieldName, AccountType $accountType, array $data = []): bool
    {
        $validFields = $this->getValidFields($accountType, $data);
        return in_array($fieldName, $validFields, true);
    }

    /**
     * Get all available account types and their field requirements (useful for documentation)
     *
     * @return array
     */
    public function getAllAccountTypeRequirements(): array
    {
        $requirements = $this->getFieldRequirements(new AccountType());
        $result = [];

        foreach ($requirements as $typeKey => $typeRequirements) {
            if ($typeKey === 'shared') {
                continue; // Skip shared, it's included in all types
            }

            $result[$typeKey] = [
                'required' => array_merge($requirements['shared']['required'] ?? [], $typeRequirements['required']),
                'optional' => array_merge($requirements['shared']['optional'] ?? [], $typeRequirements['optional'])
            ];
        }

        return $result;
    }

    /**
     * Check if this is a credit card asset account
     *
     * @param AccountType $accountType
     * @param array $data
     * @return bool
     */
    private function isCreditCardAsset(AccountType $accountType, array $data): bool
    {
        // Credit card assets are asset accounts with account_role = 'ccAsset'
        return AccountTypeEnum::ASSET->value === $accountType->type 
            && isset($data['account_role']) 
            && $data['account_role'] === 'ccAsset';
    }
}
