<?php

/**
 * AccountFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Factory;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Events\StoredAccount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use Illuminate\Support\Facades\Log;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\AccountFieldValidationService;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\Services\Internal\Support\LocationServiceTrait;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\User;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    use AccountServiceTrait;
    use LocationServiceTrait;

    protected AccountRepositoryInterface $accountRepository;
    protected AccountFieldValidationService $fieldValidationService;
    private array                        $canHaveOpeningBalance;
    private array                        $canHaveVirtual;
    private User                         $user;

    /**
     * AccountFactory constructor.
     */
    public function __construct()
    {
        $this->accountRepository     = app(AccountRepositoryInterface::class);
        $this->fieldValidationService = app(AccountFieldValidationService::class);
        $this->canHaveVirtual        = config('firefly.can_have_virtual_amounts');
        $this->canHaveOpeningBalance = config('firefly.can_have_opening_balance');
    }

    /**
     * @throws FireflyException
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        Log::debug(sprintf('findOrCreate("%s", "%s")', $accountName, $accountType));

        $type   = $this->accountRepository->getAccountTypeByType($accountType);
        if (!$type instanceof AccountType) {
            throw new FireflyException(sprintf('Cannot find account type "%s"', $accountType));
        }

        /** @var null|Account $return */
        $return = $this->user->accounts->where('account_type_id', $type->id)->where('name', $accountName)->first();

        if (null === $return) {
            Log::debug('Found nothing. Will create a new one.');
            $return = $this->create(
                [
                    'user_id'           => $this->user->id,
                    'user_group_id'     => $this->user->user_group_id,
                    'name'              => $accountName,
                    'account_type_id'   => $type->id,
                    'account_type_name' => null,
                    'virtual_balance'   => '0',
                    'iban'              => null,
                    'active'            => true,
                ]
            );
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    public function create(array $data): Account
    {
        Log::debug('Now in AccountFactory::create()');
        $type         = $this->getAccountType($data);
        $data['iban'] = $this->filterIban($data['iban'] ?? null);

        // Validate all fields for this account type
        $this->fieldValidationService->validateFields($data, $type);

        // account may exist already:
        $return = $this->findExistingAccount($data, $type);

        if ($return instanceof Account) {
            return $return;
        }

        $return       = $this->createAccount($type, $data);

        event(new StoredAccount($return));

        return $return;
    }

    /**
     * @throws FireflyException
     */
    protected function getAccountType(array $data): ?AccountType
    {
        $accountTypeId   = array_key_exists('account_type_id', $data) ? (int) $data['account_type_id'] : 0;
        $accountTypeName = array_key_exists('account_type_name', $data) ? $data['account_type_name'] : null;
        $templateName    = array_key_exists('template', $data) ? $data['template'] : null;
        $category        = array_key_exists('category', $data) ? $data['category'] : null;
        $behavior        = array_key_exists('behavior', $data) ? $data['behavior'] : null;
        $result          = null;
        
        // Debug: Log the incoming data
        Log::debug('AccountFactory::getAccountType - Incoming data:', [
            'account_type_id' => $accountTypeId,
            'account_type_name' => $accountTypeName,
            'template' => $templateName,
            'category' => $category,
            'behavior' => $behavior,
            'all_data_keys' => array_keys($data)
        ]);
        
        // 1. Try to find by template name (now treated as account type name)
        if (null !== $templateName) {
            Log::debug(sprintf('Looking for account type with name: "%s"', $templateName));
            $result = AccountType::where('name', $templateName)->where('active', true)->first();
            if ($result) {
                Log::debug(sprintf('Found account type: "%s"', $result->name));
            } else {
                Log::debug(sprintf('No account type found with name: "%s"', $templateName));
                // Let's also check what account types exist
                $allTypes = AccountType::where('active', true)->pluck('name')->toArray();
                Log::debug('Available account types:', $allTypes);
            }
        }
        
        // 2. Try to find by account_type_id (direct lookup in new system)
        if (null === $result && $accountTypeId > 0) {
            $result = AccountType::find($accountTypeId);
        }
        
        // 3. Try to find by account_type_name
        if (null === $result && null !== $accountTypeName) {
            $result = AccountType::where('name', $accountTypeName)->where('active', true)->first();
        }
        
        // 4. Try to find by category and behavior combination
        if (null === $result && null !== $category && null !== $behavior) {
            $result = $this->findAccountTypeByCategoryAndBehavior($category, $behavior);
        }

        if (null === $result) {
            Log::warning(sprintf('Found NO account type based on %d and "%s" (template: %s, category: %s, behavior: %s)', $accountTypeId, $accountTypeName, $templateName, $category, $behavior));

            throw new FireflyException(sprintf('AccountFactory::create() was unable to find account type #%d ("%s").', $accountTypeId, $accountTypeName));
        }
        
        Log::debug(sprintf('Found account type based on %d and "%s" (template: %s, category: %s, behavior: %s): "%s"', $accountTypeId, $accountTypeName, $templateName, $category, $behavior, $result->name));

        return $result;
    }

    /**
     * Find account type by category and behavior
     */
    private function findAccountTypeByCategoryAndBehavior(string $category, string $behavior): ?AccountType
    {
        // Get category and behavior IDs
        $categoryModel = $this->accountRepository->getAccountCategoryByName($category);
        $behaviorModel = $this->accountRepository->getAccountBehaviorByName($behavior);
        
        if (!$categoryModel || !$behaviorModel) {
            return null;
        }

        // Find account type by category and behavior
        return AccountType::where('category_id', $categoryModel->id)
            ->where('behavior_id', $behaviorModel->id)
            ->where('active', true)
            ->first();
    }

    /**
     * Find existing account based on required identifying fields
     * Uses institution, account_holders, product_name, and account_number for uniqueness
     */
    private function findExistingAccount(array $data, AccountType $type): ?Account
    {
        Log::debug('Now in AccountFactory::findExistingAccount()', [
            'institution' => $data['institution'] ?? 'NOT_SET',
            'account_holders' => $data['account_holders'] ?? 'NOT_SET', 
            'product_name' => $data['product_name'] ?? 'NOT_SET',
            'account_number' => $data['account_number'] ?? 'NOT_SET'
        ]);

        // All accounts are required to have institution, account_holders, and product_name
        $institution = $data['institution'] ?? null;
        $accountHolders = $data['account_holders'] ?? null;
        $productName = $data['product_name'] ?? null;
        $accountNumber = $data['account_number'] ?? null;

        if (!$institution || !$accountHolders || !$productName) {
            Log::debug('Missing required identifying fields for account search', [
                'has_institution' => !empty($institution),
                'has_account_holders' => !empty($accountHolders),
                'has_product_name' => !empty($productName)
            ]);
            return null;
        }

        // Build the base query with required fields
        // Use PostgreSQL JSON equality operator with proper type casting
        $query = $this->user->accounts()
            ->where('account_type_id', $type->id)
            ->where('institution', $institution)
            ->whereRaw('account_holders::text = ?::text', [json_encode($accountHolders)])
            ->where('product_name', $productName);

        // If account number is provided, include it in the uniqueness check
        if ($accountNumber) {
            $query->where('account_number', $accountNumber);
            Log::debug('Including account_number in uniqueness check', ['account_number' => $accountNumber]);
        } else {
            Log::debug('No account_number provided, using base uniqueness criteria');
        }

        // Get all matching records to ensure uniqueness
        $matches = $query->get();
        
        if ($matches->count() === 0) {
            Log::debug('No existing account found');
            return null;
        }
        
        if ($matches->count() === 1) {
            Log::debug('Found exactly one existing account', ['account_id' => $matches->first()->id]);
            return $matches->first();
        }
        
        // Multiple matches found - this indicates a data integrity issue
        $accountIds = $matches->pluck('id')->toArray();
        $accountNames = $matches->pluck('name')->toArray();
        
        Log::error('Multiple accounts found with same uniqueness criteria', [
            'count' => $matches->count(),
            'account_ids' => $accountIds,
            'account_names' => $accountNames,
            'institution' => $institution,
            'product_name' => $productName,
            'account_holders' => $accountHolders,
            'account_number' => $accountNumber
        ]);
        
        // Throw an exception to prevent data corruption
        throw new FireflyException(sprintf(
            'Data integrity error: Found %d accounts with identical uniqueness criteria. ' .
            'Account IDs: %s, Names: %s. ' .
            'Criteria: institution="%s", product_name="%s", account_holders=%s, account_number="%s". ' .
            'This must be resolved before proceeding.',
            $matches->count(),
            implode(', ', $accountIds),
            implode(', ', $accountNames),
            $institution,
            $productName,
            json_encode($accountHolders),
            $accountNumber ?? 'null'
        ));
    }

    /**
     * @deprecated Use findExistingAccount instead
     */
    public function find(string $accountName, string $accountType): ?Account
    {
        Log::debug(sprintf('Now in AccountFactory::find("%s", "%s")', $accountName, $accountType));
        $type = $this->accountRepository->getAccountTypeByType($accountType);

        /** @var null|Account */
        return $this->user->accounts()->where('name', $accountName)->first();
    }

    /**
     * @throws FireflyException
     */
    private function createAccount(AccountType $type, array $data): Account
    {

        // create it:
        $virtualBalance = array_key_exists('virtual_balance', $data) ? $data['virtual_balance'] : null;
        
        // Handle 'active' field, defaulting to true when not provided
        $active = $data['active'] ?? true;
        
        // Use provided name or generate one from the identifying fields
        $accountName = $data['name'] ?? null;
        
        if (!$accountName) {
            // Generate a meaningful name from the identifying fields
            $institution = $data['institution'] ?? 'Unknown Institution';
            $productName = $data['product_name'] ?? 'Unknown Product';
            $accountHolders = $data['account_holders'] ?? ['Unknown Holder'];
            
            // Handle both single and multiple account holders
            if (is_array($accountHolders) && !empty($accountHolders)) {
                $holderDisplay = count($accountHolders) === 1 ? $accountHolders[0] : implode(', ', $accountHolders);
            } else {
                $holderDisplay = 'Unknown Holder';
            }
            
            $accountName = "{$institution} - {$productName} ({$holderDisplay})";
        }
        
        Log::debug('AccountFactory::createAccount - Name handling', [
            'provided_name' => $data['name'] ?? 'NOT_PROVIDED',
            'final_name' => $accountName,
            'institution' => $data['institution'] ?? 'NOT_PROVIDED',
            'product_name' => $data['product_name'] ?? 'NOT_PROVIDED',
            'account_holders' => $data['account_holders'] ?? 'NOT_PROVIDED',
            'data_keys' => array_keys($data)
        ]);

        // Get all account fields from FieldDefinitions
        $accountFields = Account::getAccountFields();
        
        // Start with the core required fields
        $databaseData = [
            'user_id'         => $this->user->id,
            'user_group_id'   => $this->user->user_group_id,
            'account_type_id' => $type->id,
            'template_id'     => null, // No longer using templates
            'entity_id'       => $data['entity_id'] ?? null, // TODO: this field is not needed on accounts
            'name'            => $accountName,
            'virtual_balance' => $virtualBalance,
            'active'          => $active,
            'iban'            => $data['iban'],
            'account_holders' => $data['account_holders'],
            'institution'     => $data['institution'],
            'product_name'    => $data['product_name'],
        ];
        
        // Add all account fields from FieldDefinitions, using data value or null
        // Skip fields we've already handled explicitly
        $excludedFields = ['active', 'name', 'account_holders', 'institution', 'product_name'];
        foreach ($accountFields as $fieldName => $fieldConfig) {
            if (!in_array($fieldName, $excludedFields)) {
                $databaseData[$fieldName] = $data[$fieldName] ?? null;
            }
        }
        
        // Add foreign key fields if available
        $databaseData['account_holder_ids'] = $data['account_holder_ids'] ?? null; // TODO: remove null check
        $databaseData['institution_id'] = $data['institution_id'] ?? null; // TODO: remove null check
        
        // Log the fields being included for debugging
        Log::debug('AccountFactory::createAccount - Fields included', [
            'total_fields' => count($databaseData),
            'field_names' => array_keys($databaseData),
            'account_fields_count' => count($accountFields)
        ]);
        // fix virtual balance when it's empty
        if ('' === (string) $databaseData['virtual_balance']) {
            $databaseData['virtual_balance'] = null;
        }
        // remove virtual balance when not an asset account
        if ($type && !in_array($type->type, $this->canHaveVirtual, true)) {
            $databaseData['virtual_balance'] = null;
        }
        // Debug: Log the data being sent to Account::create
        Log::debug('AccountFactory::createAccount - About to create account', [
            'database_data' => $databaseData,
            'name_value' => $databaseData['name'] ?? 'NOT_SET'
        ]);
        
        // create account!
        $account        = Account::create($databaseData);
        Log::channel('audit')->info(sprintf('Account #%d ("%s") has been created.', $account->id, $account->name));

        // update meta data:
        $data           = $this->cleanMetaDataArray($account, $data);
        $this->storeMetaData($account, $data);

        // create opening balance (only asset accounts)
        try {
            $this->storeOpeningBalance($account, $data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }

        // create credit liability data (only liabilities)
        try {
            $this->storeCreditLiability($account, $data);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }

        // create notes
        $notes          = array_key_exists('notes', $data) ? $data['notes'] : '';
        $this->updateNote($account, $notes);

        // create location
        $this->storeNewLocation($account, $data);

        // Account order functionality has been removed

        // refresh and return
        $account->refresh();

        return $account;
    }

    /**
     * @throws FireflyException
     */
    private function cleanMetaDataArray(Account $account, array $data): array
    {
        $currencyId           = array_key_exists('currency_id', $data) ? (int) $data['currency_id'] : 0;
        $currencyCode         = array_key_exists('currency_code', $data) ? (string) $data['currency_code'] : '';
        $accountRole          = array_key_exists('account_role', $data) ? (string) $data['account_role'] : null;
        $currency             = $this->getCurrency($currencyId, $currencyCode);

        // only asset account may have a role:
        if ($account->accountType && AccountTypeEnum::ASSET->value !== $account->accountType->type) {
            $accountRole = '';
        }
        // only liability may have direction:
        if (array_key_exists('liability_direction', $data) && $account->accountType && !in_array($account->accountType->type, config('firefly.valid_liabilities'), true)) {
            $data['liability_direction'] = null;
        }
        $data['account_role'] = $accountRole;
        $data['currency_id']  = $currency->id;

        return $data;
    }

    private function storeMetaData(Account $account, array $data): void
    {
        // Metadata is now stored directly in the accounts table during creation
        // No need for separate metadata storage
        Log::info('AccountFactory::storeMetaData - Metadata already stored in accounts table', [
            'account_id' => $account->id,
            'account_name' => $account->name
        ]);
    }

    /**
     * @throws FireflyException
     */
    private function storeOpeningBalance(Account $account, array $data): void
    {
        $accountCategoryId = $account->accountType->category_id;

        if (in_array($accountCategoryId, [1], true)) {
            if ($this->validOBData($data)) {
                $openingBalance     = $data['opening_balance'];
                $openingBalanceDate = $data['opening_balance_date'];
                $this->updateOBGroupV2($account, $openingBalance, $openingBalanceDate);
            }
            if (!$this->validOBData($data)) {
                $this->deleteOBGroup($account);
            }
        }
    }

    /**
     * @throws FireflyException
     */
    private function storeCreditLiability(Account $account, array $data): void
    {
        Log::debug('storeCreditLiability');
        $account->refresh();
        
        // Check if accountType relationship is loaded and not null
        if (!$account->accountType) {
            Log::debug('Account type not found, skipping credit liability processing');
            return;
        }
        
        $accountType = $account->accountType->type;
        $direction   = $this->accountRepository->getMetaValue($account, 'liability_direction');
        $valid       = config('firefly.valid_liabilities');
        if (in_array($accountType, $valid, true)) {
            Log::debug('Is a liability with credit ("i am owed") direction.');
            if ($this->validOBData($data)) {
                Log::debug('Has valid CL data.');
                $openingBalance     = $data['opening_balance'];
                $openingBalanceDate = $data['opening_balance_date'];
                // store credit transaction.
                $this->updateCreditTransaction($account, $direction, $openingBalance, $openingBalanceDate);
            }
            if (!$this->validOBData($data)) {
                Log::debug('Does NOT have valid CL data, deletr any CL transaction.');
                $this->deleteCreditTransaction($account);
            }
        }
    }


    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
    }

}
