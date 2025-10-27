<?php

/**
 * AccountRepository.php
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

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBehavior;
use FireflyIII\Models\AccountCategory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Location;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Override;

use function Safe\json_encode;

/**
 * Class AccountRepository.
 */
class AccountRepository implements AccountRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    /**
     * Moved here from account CRUD.
     */
    public function destroy(Account $account, ?Account $moveTo): bool
    {
        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, $moveTo);

        return true;
    }

    /**
     * Find account with same name OR same IBAN or both, but not the same type or ID.
     */
    public function expandWithDoubles(Collection $accounts): Collection
    {
        $result = new Collection();

        /** @var Account $account */
        foreach ($accounts as $account) {
            $byName = $this->user->accounts()->where('name', $account->name)
                ->where('id', '!=', $account->id)->first()
            ;
            if (null !== $byName) {
                $result->push($account);
                $result->push($byName);

                continue;
            }
            if (null !== $account->iban) {
                $byIban = $this->user->accounts()->where('iban', $account->iban)
                    ->where('id', '!=', $account->id)->first()
                ;
                if (null !== $byIban) {
                    $result->push($account);
                    $result->push($byIban);

                    continue;
                }
            }
            $result->push($account);
        }

        return $result;
    }

    public function findByAccountNumber(string $number, array $types): ?Account
    {
        $dbQuery = $this->user
            ->accounts()
            ->where('accounts.active', true)
            ->where('accounts.account_number', $number)
        ;

        if (0 !== count($types)) {
            $dbQuery->accountTypeIn($types);
        }

        /** @var null|Account */
        return $dbQuery->first(['accounts.*']);
    }

    public function findByIbanNull(string $iban, array $types): ?Account
    {
        $iban  = Steam::filterSpaces($iban);
        $query = $this->user->accounts()->where('iban', '!=', '')->whereNotNull('iban');

        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }

        /** @var null|Account */
        return $query->where('iban', $iban)->first(['accounts.*']);
    }

    public function findByName(string $name, array $types): ?Account
    {
        $query   = $this->user->accounts();

        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        Log::debug(sprintf('Searching for account named "%s" (of user #%d) of the following type(s)', $name, $this->user->id), ['types' => $types]);

        $query->where('accounts.name', $name);

        /** @var null|Account $account */
        $account = $query->first(['accounts.*']);
        if (null === $account) {
            Log::debug(sprintf('There is no account with name "%s" of types', $name), $types);

            return null;
        }
        Log::debug(sprintf('Found #%d (%s) with type id %d', $account->id, $account->name, $account->account_type_id));

        return $account;
    }

    #[Override]
    public function getAccountBalances(Account $account): Collection
    {
        return $account->accountBalances;
    }

    /**
     * Return account type or null if not found.
     */
    public function getAccountTypeByType(string $type): ?AccountType
    {
        // Find the account type by name in the account_types table
        return AccountType::where('name', ucfirst($type))
            ->where('active', true)
            ->first();
    }
    
    /**
     * Get all account types by category name
     */
    public function getAccountTypesByCategory(string $categoryName): \Illuminate\Database\Eloquent\Collection
    {
        return AccountType::whereHas('category', function ($query) use ($categoryName) {
            $query->where('name', $categoryName);
        })
        ->where('active', true)
        ->with(['category', 'behavior'])
        ->get();
    }
    
    /**
     * Get all account types by behavior name
     */
    public function getAccountTypesByBehavior(string $behaviorName): \Illuminate\Database\Eloquent\Collection
    {
        return AccountType::whereHas('behavior', function ($query) use ($behaviorName) {
            $query->where('name', $behaviorName);
        })
        ->where('active', true)
        ->with(['category', 'behavior'])
        ->get();
    }
    

    /**
     * Get account category by name
     */
    public function getAccountCategoryByName(string $name): ?AccountCategory
    {
        return AccountCategory::where('name', $name)->first();
    }

    /**
     * Get account behavior by name
     */
    public function getAccountBehaviorByName(string $name): ?AccountBehavior
    {
        return AccountBehavior::where('name', $name)->first();
    }

    public function getAccountsById(array $accountIds): Collection
    {
        $query = $this->user->accounts();

        if (0 !== count($accountIds)) {
            $query->whereIn('accounts.id', $accountIds);
        }
        $query->orderBy('accounts.active', 'DESC');
        // No additional sorting - let client handle it

        return $query->get(['accounts.*']);
    }

    public function getActiveAccountsByType(array $types): Collection
    {
        $query = $this->user->accounts()->with(
            [  // @phpstan-ignore-line
                'attachments',
                'accountHolder',
                'institutionEntity',
            ]
        );
        // Always apply accountTypeIn scope, even for empty arrays
        // The scope will handle empty arrays by returning no results
        $query->accountTypeIn($types);
        $query->where('accounts.active', true);
        // No additional sorting - let client handle it

        return $query->get(['accounts.*']);
    }

    public function getAttachments(Account $account): Collection
    {
        $set  = $account->attachments()->get();

        /** @var Storage $disk */
        $disk = Storage::disk('upload');

        return $set->each(
            static function (Attachment $attachment) use ($disk) { // @phpstan-ignore-line
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes_text  = null !== $notes ? $notes->text : '';

                return $attachment;
            }
        );
    }

    /**
     * @throws FireflyException
     */
    public function getCashAccount(): Account
    {
        /** @var AccountType $type */
        $type    = AccountType::where('type', AccountTypeEnum::CASH->value)->first();

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate('Cash account', $type->type);
    }

    public function getCreditTransactionGroup(Account $account): ?TransactionGroup
    {
        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::LIABILITY_CREDIT->value])
            ->first(['transaction_journals.*'])
        ;

        return $journal?->transactionGroup;
    }

    public function getInactiveAccountsByType(array $types): Collection
    {
        $query = $this->user->accounts()->with(
            [ // @phpstan-ignore-line
                'accountHolder',
                'institutionEntity',
            ]
        );
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        $query->where('accounts.active', 0);
        // No additional sorting - let client handle it

        return $query->get(['accounts.*']);
    }

    public function getLocation(Account $account): ?Location
    {
        /** @var null|Location */
        return $account->locations()->first();
    }

    /**
     * Get note text or null.
     */
    public function getNoteText(Account $account): ?string
    {
        return $account->notes()->first()?->text;
    }

    /**
     * Returns the amount of the opening balance for this account.
     */
    public function getOpeningBalanceAmount(Account $account, bool $convertToPrimary): ?string
    {
        $journal     = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::OPENING_BALANCE->value, TransactionTypeEnum::LIABILITY_CREDIT->value])
            ->first(['transaction_journals.*'])
        ;
        if (null === $journal) {
            return null;
        }
        $transaction = $journal->transactions()->where('account_id', $account->id)->first();
        if (null === $transaction) {
            return null;
        }
        if ($convertToPrimary) {
            return $transaction->native_amount ?? '0';
        }

        return $transaction->amount;
    }

    /**
     * Return date of opening balance as string or null.
     */
    public function getOpeningBalanceDate(Account $account): ?string
    {
        return TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::OPENING_BALANCE->value, TransactionTypeEnum::LIABILITY_CREDIT->value])
            ->first(['transaction_journals.*'])?->date->format('Y-m-d H:i:s')
        ;
    }

    public function getOpeningBalanceGroup(Account $account): ?TransactionGroup
    {
        $journal = $this->getOpeningBalance($account);

        return $journal?->transactionGroup;
    }

    public function getOpeningBalance(Account $account): ?TransactionJournal
    {
        return TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::OPENING_BALANCE->value])
            ->first(['transaction_journals.*'])
        ;
    }

    public function getPiggyBanks(Account $account): Collection
    {
        return $account->piggyBanks()->get();
    }

    /**
     * @throws FireflyException
     */
    public function getReconciliation(Account $account): ?Account
    {
        if (AccountTypeEnum::ASSET->value !== $account->accountType->type) {
            throw new FireflyException(sprintf('%s is not an asset account.', $account->name));
        }
        $currency = $this->getAccountCurrency($account) ?? app('amount')->getPrimaryCurrency();
        $name     = trans('firefly.reconciliation_account_name', ['name' => $account->name, 'currency' => $currency->code]);

        /** @var AccountType $type */
        $type     = AccountType::where('type', AccountTypeEnum::RECONCILIATION->value)->first();

        /** @var null|Account $current */
        $current  = $this->user->accounts()->where('account_type_id', $type->id)
            ->where('name', $name)
            ->first()
        ;

        if (null !== $current) {
            return $current;
        }

        $data     = [
            'account_type_id'   => null,
            'account_type_name' => AccountTypeEnum::RECONCILIATION->value,
            'active'            => true,
            'name'              => $name,
            'currency_id'       => $currency->id,
            'currency_code'     => $currency->code,
        ];

        /** @var AccountFactory $factory */
        $factory  = app(AccountFactory::class);
        $factory->setUser($account->user);

        return $factory->create($data);
    }

    public function getAccountCurrency(Account $account): ?TransactionCurrency
    {
        $type       = $account->accountType->type;
        $list       = config('firefly.valid_currency_account_types');

        // return null if not in this list.
        if (!in_array($type, $list, true)) {
            return null;
        }
        $currencyId = (int) $this->getMetaValue($account, 'currency_id');
        if ($currencyId > 0) {
            return Amount::getTransactionCurrencyById($currencyId);
        }

        return null;
    }

    /**
     * Return meta value for account. Null if not found.
     */
    public function getMetaValue(Account $account, string $field): ?string
    {
        $value = $account->getAttribute($field);
        return $value ? (string) $value : null;
    }

    public function count(array $types): int
    {
        return $this->user->accounts()->accountTypeIn($types)->count();
    }

    public function find(int $accountId): ?Account
    {
        /** @var null|Account */
        return $this->user->accounts()->find($accountId);
    }

    public function getUsedCurrencies(Account $account): Collection
    {
        $info        = $account->transactions()->distinct()->groupBy('transaction_currency_id')->get(['transaction_currency_id'])->toArray();
        $currencyIds = [];
        foreach ($info as $entry) {
            $currencyIds[] = (int) $entry['transaction_currency_id'];
        }
        $currencyIds = array_unique($currencyIds);

        return TransactionCurrency::whereIn('id', $currencyIds)->get();
    }

    public function isLiability(Account $account): bool
    {
        return in_array($account->accountType->type, [AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value], true);
    }


    public function getAccountsByType(array $types, ?array $sort = [], ?int $limit = null): Collection
    {
        $res     = array_intersect([AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value], $types);
        $query   = $this->user->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }

        // add sort parameters
        $allowed = config('firefly.allowed_db_sort_parameters.Account', []);
        $sorted  = 0;
        if (0 !== count($sort)) {
            foreach ($sort as $param) {
                if (in_array($param[0], $allowed, true)) {
                    $query->orderBy($param[0], $param[1]);
                    ++$sorted;
                }
            }
        }

        if (0 === $sorted) {
            // Add join for sorting by account type
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            
            $query->orderBy('accounts.institution', 'ASC');
            $query->orderBy('account_types.name', 'ASC');
            $query->orderBy('accounts.name', 'ASC'); // Use account name as third sort since holder_ids is JSON
            
            app('log')->debug('🔧 [DEBUG] Server-side sorting applied:', [
                'method' => 'getAccountsByType',
                'order_by' => 'accounts.institution ASC, account_types.name ASC, accounts.name ASC',
                'types' => $types
            ]);
        }

        // Apply limit if provided
        if ($limit !== null) {
            $query->limit($limit);
        }

        $result = $query->get(['accounts.*']);
        
        // Log first and last institutions for debugging
        if ($result->count() > 0) {
            $firstInstitution = $result->first()->institution ?? 'null';
            $lastInstitution = $result->last()->institution ?? 'null';
            app('log')->debug('🔧 [DEBUG] Server-side result order:', [
                'total_accounts' => $result->count(),
                'first_institution' => $firstInstitution,
                'last_institution' => $lastInstitution
            ]);
        }

        return $result;
    }

    /**
     * Get all accounts with pagination and proper sorting.
     */
    public function getAllAccountsPaginated(int $page, int $pageSize, ?string $sortColumn = null, ?string $sortDirection = 'asc'): array
    {
        $repoStartTime = microtime(true);
        
        // Get total count
        $countStartTime = microtime(true);
        $total = $this->user->accounts()->count();
        $countTime = microtime(true) - $countStartTime;
        
        // Build query with proper sorting and eager load relationships
        $query = $this->user->accounts()->with('accountType');
        $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
        
        // Apply sorting based on parameters
        if ($sortColumn && $sortDirection) {
            $this->applySorting($query, $sortColumn, $sortDirection);
        } else {
            // Default sorting
            $query->orderBy('accounts.institution', 'ASC');
            $query->orderBy('account_types.name', 'ASC');
            $query->orderBy('accounts.name', 'ASC');
        }
        
        // Apply pagination
        $queryStartTime = microtime(true);
        $offset = ($page - 1) * $pageSize;
        $accounts = $query->offset($offset)->limit($pageSize)->get(['accounts.*']);
        
        // Load the accountType relationship for each account
        $accounts->load('accountType');
        $queryTime = microtime(true) - $queryStartTime;
        
        $totalRepoTime = microtime(true) - $repoStartTime;
        
        app('log')->debug('🔧 [PERF] getAllAccountsPaginated - Repository Performance:', [
            'total_time_ms' => round($totalRepoTime * 1000, 2),
            'count_query_ms' => round($countTime * 1000, 2),
            'main_query_ms' => round($queryTime * 1000, 2),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'sort_column' => $sortColumn,
            'sort_direction' => $sortDirection,
            'returned_count' => $accounts->count(),
            'first_institution' => $accounts->first() ? $accounts->first()->institution : 'none',
            'last_institution' => $accounts->last() ? $accounts->last()->institution : 'none'
        ]);
        
        return [
            'accounts' => $accounts,
            'total' => $total
        ];
    }

    /**
     * Apply sorting to the query based on column and direction.
     */
    private function applySorting($query, string $sortColumn, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'DESC' : 'ASC';
        
        switch ($sortColumn) {
            case 'institution':
                $query->orderBy('accounts.institution', $direction);
                $query->orderBy('account_types.name', 'ASC');
                $query->orderBy('accounts.name', 'ASC');
                break;
            case 'account_type':
                $query->orderBy('account_types.name', $direction);
                $query->orderBy('accounts.institution', 'ASC');
                $query->orderBy('accounts.name', 'ASC');
                break;
            case 'name':
                $query->orderBy('accounts.name', $direction);
                $query->orderBy('accounts.institution', 'ASC');
                $query->orderBy('account_types.name', 'ASC');
                break;
            case 'active':
                $query->orderBy('accounts.active', $direction);
                $query->orderBy('accounts.institution', 'ASC');
                $query->orderBy('account_types.name', 'ASC');
                $query->orderBy('accounts.name', 'ASC');
                break;
            case 'balance':
                // For balance sorting, we'll need to calculate balances
                // This is more complex and might require a different approach
                $query->orderBy('accounts.institution', 'ASC');
                $query->orderBy('account_types.name', 'ASC');
                $query->orderBy('accounts.name', 'ASC');
                break;
            default:
                // Default sorting if column not recognized
                $query->orderBy('accounts.institution', 'ASC');
                $query->orderBy('account_types.name', 'ASC');
                $query->orderBy('accounts.name', 'ASC');
                break;
        }
    }

    /**
     * Get total count of accounts by type for pagination.
     */
    public function getAccountsByTypeCount(array $types): int
    {
        $query = $this->user->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        
        return $query->count();
    }

    /**
     * Returns the date of the very first transaction in this account.
     */
    public function oldestJournalDate(Account $account): ?Carbon
    {
        $journal = $this->oldestJournal($account);

        return $journal?->date;
    }

    /**
     * Returns the date of the very first transaction in this account.
     */
    public function oldestJournal(Account $account): ?TransactionJournal
    {
        /** @var null|TransactionJournal $first */
        $first = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->orderBy('transaction_journals.date', 'ASC')
            ->orderBy('transaction_journals.order', 'DESC')
            ->where('transaction_journals.user_id', $this->user->id)
            ->orderBy('transaction_journals.id', 'ASC')
            ->first(['transaction_journals.id'])
        ;
        if (null !== $first) {
            /** @var null|TransactionJournal */
            return TransactionJournal::find($first->id);
        }

        return null;
    }

    #[Override]
    public function periodCollection(Account $account, Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('periodCollection(#%d, %s, %s)', $account->id, $start->format('Y-m-d'), $end->format('Y-m-d')));

        return $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', '=', 'transactions.transaction_currency_id')
            ->leftJoin('transaction_currencies as foreign_currencies', 'foreign_currencies.id', '=', 'transactions.foreign_currency_id')
            ->where('transaction_journals.date', '>=', $start)
            ->where('transaction_journals.date', '<=', $end)
            ->get([
                // currencies
                'transaction_currencies.id as currency_id',
                'transaction_currencies.code as currency_code',
                'transaction_currencies.name as currency_name',
                'transaction_currencies.symbol as currency_symbol',
                'transaction_currencies.decimal_places as currency_decimal_places',

                // foreign
                'foreign_currencies.id as foreign_currency_id',
                'foreign_currencies.code as foreign_currency_code',
                'foreign_currencies.name as foreign_currency_name',
                'foreign_currencies.symbol as foreign_currency_symbol',
                'foreign_currencies.decimal_places as foreign_currency_decimal_places',

                // fields
                'transaction_journals.date',
                'transaction_types.type',
                'transaction_journals.transaction_currency_id',
                'transactions.amount',
                'transactions.native_amount as pc_amount',
                'transactions.foreign_amount',
            ])
            ->toArray()
        ;

    }

    /**
     * @throws FireflyException
     */
    public function update(Account $account, array $data): Account
    {
        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);

        return $service->update($account, $data);
    }

    public function searchAccount(string $query, array $types, int $limit): Collection
    {
        $dbQuery = $this->user->accounts()
            ->where('accounts.active', true)
            ->orderBy('accounts.institution', 'DESC')
            ->with(['accountType'])
        ;
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->whereLike('name', $search);
            }
        }
        if (0 !== count($types)) {
            $dbQuery->accountTypeIn($types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }

    public function searchAccountNr(string $query, array $types, int $limit): Collection
    {
        $dbQuery = $this->user->accounts()->distinct()
            ->where('accounts.active', true)
            ->orderBy('accounts.institution', 'DESC')
            ->with(['accountType'])
        ;
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->where(
                    static function (EloquentBuilder $q1) use ($search): void {
                        $q1->whereLike('accounts.iban', $search);
                        $q1->orWhereLike('accounts.account_number', $search);
                    }
                );
            }
        }
        if (0 !== count($types)) {
            $dbQuery->accountTypeIn($types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Account
    {
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }
}
