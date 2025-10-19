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
     * Validate all fields (required and optional) for the given account type
     *
     * @param array $data Account data to validate
     * @param AccountType $accountType The account type
     * @throws FireflyException If validation fails
     */
    public function validateFields(array $data, AccountType $accountType): void
    {
        // Get validation rules from FieldDefinitions
        $validationRules = \FireflyIII\FieldDefinitions\FieldDefinitions::getValidationRules('account');
        
        // Create a validator instance
        $validator = \Illuminate\Support\Facades\Validator::make($data, $validationRules);
        
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $message = sprintf(
                'Account validation failed for type "%s": %s',
                $accountType->name,
                implode(', ', array_map(function($field, $fieldErrors) {
                    return $field . ': ' . implode(', ', $fieldErrors);
                }, array_keys($errors), $errors))
            );
            
            Log::error('Account field validation failed', [
                'account_type' => $accountType->name,
                'validation_errors' => $errors,
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
        return array_keys($requirements);
    }

    /**
     * Get field requirements for an account type
     *
     * @param AccountType $accountType
     * @return array Array of field configurations with 'required' and 'default' properties
     */
    public function getFieldRequirements(AccountType $accountType): array
    {
        // Get requirements from the account type's firefly_mapping
        $fireflyMapping = $accountType->firefly_mapping;
        
        if (!$fireflyMapping || !isset($fireflyMapping['account_fields'])) {
            throw new FireflyException(
                sprintf(
                    'Account type "%s" (ID: %d) has no firefly_mapping data. This indicates a data integrity issue.',
                    $accountType->name,
                    $accountType->id
                )
            );
        }
        
        $accountFields = $fireflyMapping['account_fields'];
        
        // Add system fields that are always required
        # TODO: add this field to the FieldDefinitions class
        $accountFields['currency_id'] = ['required' => true, 'default' => null];
        
        return $accountFields;
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
        $requirements = $this->getFieldRequirements($accountType);
        return isset($requirements[$fieldName]['required']) && $requirements[$fieldName]['required'] === true;
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
