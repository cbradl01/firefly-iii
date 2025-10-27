<?php

/**
 * IndexController.php
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

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use BasicDataSupport;

    private AccountRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string) trans('firefly.accounts'));

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function inactive(Request $request, string $objectType)
    {
        $inactivePage  = true;
        $subTitle      = (string) trans(sprintf('firefly.%s_accounts_inactive', $objectType));
        $subTitleIcon  = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $types         = config(sprintf('firefly.accountTypesByIdentifier.%s', $objectType));
        $collection    = $this->repository->getInactiveAccountsByType($types);
        $total         = $collection->count();
        $page          = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $pageSize      = (int) app('preferences')->get('listPageSize', 50)->data;
        $accounts      = $collection->slice(($page - 1) * $pageSize, $pageSize);
        unset($collection);

        /** @var Carbon $start */
        $start         = clone session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end           = clone session('end', today(config('app.timezone'))->endOfMonth());

        // #10618 go to the end of the previous day.
        $start->subSecond();

        $ids           = $accounts->pluck('id')->toArray();
        Log::debug(sprintf('inactive start: accountsBalancesOptimized("%s")', $start->format('Y-m-d H:i:s')));
        Log::debug(sprintf('inactive end: accountsBalancesOptimized("%s")', $end->format('Y-m-d H:i:s')));
        $startBalances = Steam::accountsBalancesOptimized($accounts, $start, $this->primaryCurrency, $this->convertToPrimary);
        $endBalances   = Steam::accountsBalancesOptimized($accounts, $end, $this->primaryCurrency, $this->convertToPrimary);
        $activities    = Steam::getLastActivities($ids);


        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances): void {
                $currency                   = $this->repository->getAccountCurrency($account);
                $account->lastActivityDate  = $this->isInArrayDate($activities, $account->id);
                $account->startBalances     = Steam::filterAccountBalance($startBalances[$account->id] ?? [], $account, $this->convertToPrimary, $currency);
                $account->endBalances       = Steam::filterAccountBalance($endBalances[$account->id] ?? [], $account, $this->convertToPrimary, $currency);
                $account->differences       = $this->subtract($account->startBalances, $account->endBalances);
                $account->interest          = Steam::bcround($this->repository->getMetaValue($account, 'interest'), 4);
                $account->interestPeriod    = (string) trans(sprintf('firefly.interest_calc_%s', $this->repository->getMetaValue($account, 'interest_period')));
                $account->accountTypeString = (string) trans(sprintf('firefly.account_type_%s', $account->accountType->type));
                $account->current_debt      = '0';
                $account->currency          = $currency ?? $this->primaryCurrency;
                $account->iban              = implode(' ', str_split((string) $account->iban, 4));
            }
        );

        // make paginator:
        $accounts      = new LengthAwarePaginator($accounts, $total, $pageSize, $page);
        $accounts->setPath(route('accounts.inactive.index', [$objectType]));

        return view('accounts.index', compact('objectType', 'inactivePage', 'subTitleIcon', 'subTitle', 'page', 'accounts'));
    }

    private function subtract(array $startBalances, array $endBalances): array
    {
        $result = [];
        foreach ($endBalances as $key => $value) {
            $result[$key] = bcsub((string) $value, $startBalances[$key] ?? '0');
        }

        return $result;
    }

    /**
     * Show all accounts grouped by type.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function all(Request $request)
    {
        $startTime = microtime(true);
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        app('log')->debug('🔧 [DEBUG] IndexController::all - Starting account loading');
        $subTitle = 'All Accounts';
        $subTitleIcon = 'fa-university';
        
        // Get pagination and sorting parameters
        $page = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        // Use custom page size for accounts table, with fallback to user preference or default
        $requestedPageSize = $request->get('pageSize');
        if ($requestedPageSize) {
            $pageSize = (int) $requestedPageSize;
        } else {
            $pageSize = (int) app('preferences')->get('accountsPageSize', 100)->data;
        }
        $sortColumn = $request->get('sort');
        $sortDirection = $request->get('direction', 'asc');
        
        // Get paginated accounts with proper sorting at database level
        $dbStartTime = microtime(true);
        $result = $this->repository->getAllAccountsPaginated($page, $pageSize, $sortColumn, $sortDirection);
        $accounts = $result['accounts'];
        $total = $result['total'];
        $dbTime = microtime(true) - $dbStartTime;
        
        app('log')->debug('🔧 [PERF] Database query completed:', [
            'time_ms' => round($dbTime * 1000, 2),
            'accounts_returned' => $accounts->count(),
            'total_accounts' => $total
        ]);
        
        /** @var Carbon $start */
        $start = clone session('start', today(config('app.timezone'))->startOfMonth());
        /** @var Carbon $end */
        $end = clone session('end', today(config('app.timezone'))->endOfMonth());
        $start->subSecond();

        $ids = $accounts->pluck('id')->toArray();

        // Get account IDs that have transactions (one efficient query)
        $transactionQueryStart = microtime(true);
        $accountsWithTransactions = \FireflyIII\Models\Transaction::whereIn('account_id', $ids)
            ->distinct()
            ->pluck('account_id')
            ->toArray();
        $transactionQueryTime = microtime(true) - $transactionQueryStart;

        $activitiesStart = microtime(true);
        $activities = Steam::getLastActivities($ids);
        $activitiesTime = microtime(true) - $activitiesStart;
        
        app('log')->debug('🔧 [PERF] Transaction queries completed:', [
            'transaction_query_ms' => round($transactionQueryTime * 1000, 2),
            'activities_query_ms' => round($activitiesTime * 1000, 2),
            'accounts_with_transactions' => count($accountsWithTransactions),
            'total_accounts_checked' => count($ids)
        ]);
        
        $balanceCalcStart = microtime(true);
        $balanceCalcCount = 0;
        $balanceCalcTime = 0;
        
        $metaQueryTime = 0;
        $metaQueryCount = 0;
        $translationTime = 0;
        $translationCount = 0;
        
        // Pre-load currencies, translations, financial entities, and meta values in one query to avoid N+1 queries
        $metaStart = microtime(true);
        $currencies = \FireflyIII\Models\TransactionCurrency::all()->keyBy('id');
        
        // Pre-load all financial entities to avoid N+1 queries in accountGetAccountHolders
        $financialEntities = \FireflyIII\Models\FinancialEntity::all()->keyBy('name');
        
        // Pre-load account data that was previously in account_meta
        // institution is now a direct field on accounts table
        // account_type is accessed through the accountType relationship
        $metaValues = $accounts->mapWithKeys(function ($account) {
            return [
                $account->id => collect([
                    'institution' => (object) ['data' => $account->institution ?? ''],
                    'account_type' => (object) ['data' => $account->accountType?->name ?? '']
                ])
            ];
        });
        
        // Pre-calculate account holders to avoid repeated function calls in template
        $accountHolders = $accounts->mapWithKeys(function ($account) use ($financialEntities) {
            $holders = $this->getAccountHolders($account, $financialEntities);
            return [
                $account->id => $holders
            ];
        });
        
        // Pre-calculate institution and account type values to avoid repeated function calls
        $accountInstitutions = $accounts->mapWithKeys(function ($account) {
            return [
                $account->id => $account->institution ?? ''
            ];
        });
        
        $accountTypeNames = $accounts->mapWithKeys(function ($account) {
            return [
                $account->id => $account->accountType?->name ?? ''
            ];
        });
        
        // Pre-calculate product names to avoid function calls in template
        $accountProductNames = $accounts->mapWithKeys(function ($account) {
            return [
                $account->id => $account->product_name ?? ''
            ];
        });
        
        // Pre-calculate all account data for template optimization
        $accountData = $accounts->mapWithKeys(function ($account) use ($accountInstitutions, $accountTypeNames, $accountHolders, $accountProductNames) {
            $institution = $accountInstitutions[$account->id] ?? '';
            $accountType = $accountTypeNames[$account->id] ?? '';
            $holders = $accountHolders[$account->id] ?? collect();
            $productName = $accountProductNames[$account->id] ?? '';
            
            $data = [
                'institution' => $institution,
                'institution_lower' => strtolower($institution),
                'account_type' => $accountType,
                'account_type_lower' => strtolower($accountType),
                'holders' => $holders,
                'holders_text' => $holders->map(fn($h) => $h->name)->join(', '),
                'holders_lower' => strtolower($holders->map(fn($h) => $h->name)->join(', ')),
                'product_name' => $productName,
                'product_name_lower' => strtolower($productName),
                'account_number' => $account->account_number ?? '',
                'account_number_lower' => strtolower($account->account_number ?? ''),
                'iban' => $account->iban ?? '',
                'iban_formatted' => implode(' ', str_split($account->iban ?? '', 4)),
                'active' => $account->active,
                'active_class' => $account->active ? 'row-active' : 'row-inactive',
                'status' => $account->active ? 'active' : 'inactive',
                'beneficiaries' => $this->getAccountBeneficiaries($account),
                'beneficiaries_count' => $this->getAccountBeneficiariesCount($account)
            ];
            
            // Debug: Log first account's data to see what we're generating
            static $firstAccountLogged = false;
            if (!$firstAccountLogged) {
                app('log')->debug('🔧 [DEBUG] First account data:', [
                    'account_id' => $account->id,
                    'institution_lower' => $data['institution_lower'],
                    'account_type_lower' => $data['account_type_lower'],
                    'holders_lower' => $data['holders_lower'],
                    'product_name_lower' => $data['product_name_lower'],
                    'account_number_lower' => $data['account_number_lower'],
                    'beneficiaries_count' => $data['beneficiaries_count']
                ]);
                $firstAccountLogged = true;
            }
            
            return [$account->id => $data];
        });
        
        // Pre-load all account type and interest period translations to avoid repeated translation calls
        $accountTypes = $accounts->pluck('accountType.type')->unique()->toArray();
        $interestPeriods = $accounts->pluck('interest_period')->filter()->unique()->toArray();
        if (empty($interestPeriods)) {
            $interestPeriods = ['monthly']; // Default fallback
        }
        
        $translations = [];
        foreach ($accountTypes as $type) {
            $translations['account_type_' . $type] = trans(sprintf('firefly.account_type_%s', $type));
        }
        foreach ($interestPeriods as $period) {
            $translations['interest_calc_' . $period] = trans(sprintf('firefly.interest_calc_%s', $period));
        }
        
        $metaQueryTime = microtime(true) - $metaStart;
        $metaQueryCount = 3;
        
        $accounts->each(
            function (Account $account) use ($activities, $accountsWithTransactions, $start, $end, $currencies, $translations, &$balanceCalcCount, &$balanceCalcTime, &$metaQueryTime, &$metaQueryCount, &$translationTime, &$translationCount): void {
                $currency = $currencies->get($account->currency_id) ?? $this->primaryCurrency;
                $account->lastActivityDate = $this->isInArrayDate($activities, $account->id);
                
                // Calculate balances only if account has transactions
                if (in_array($account->id, $accountsWithTransactions)) {
                    $balanceStart = microtime(true);
                    
                    // Calculate current balance (as of today) for display
                    $now = today()->endOfDay();
                    $currentBalance = Steam::filterAccountBalance(
                        Steam::finalAccountBalance($account, $now, $this->primaryCurrency, $this->convertToPrimary), 
                        $account, 
                        $this->convertToPrimary, 
                        $currency
                    );
                    
                    // Set both startBalances and endBalances to current balance for display
                    $account->startBalances = $currentBalance;
                    $account->endBalances = $currentBalance;
                    
                    // Also set current_balance for the template
                    $account->current_balance = $currentBalance['balance'] ?? '0';
                    $account->currency_symbol = $currency->symbol ?? $this->primaryCurrency->symbol;
                    $account->currency_decimal_places = $currency->decimal_places ?? $this->primaryCurrency->decimal_places;
                    
                    // Debug logging
                    app('log')->debug('Setting balances for account', [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'currentBalance' => $currentBalance,
                        'endBalances' => $account->endBalances,
                        'current_balance' => $account->current_balance
                    ]);
                    
                    $balanceCalcTime += microtime(true) - $balanceStart;
                    $balanceCalcCount++;
                } else {
                    // No transactions, so balance is 0 - set directly without any calculations
                    $account->startBalances = ["balance" => "0", $this->primaryCurrency->code => "0"];
                    $account->endBalances = ["balance" => "0", $this->primaryCurrency->code => "0"];
                    $account->current_balance = "0";
                    $account->currency_symbol = $currency->symbol ?? $this->primaryCurrency->symbol;
                    $account->currency_decimal_places = $currency->decimal_places ?? $this->primaryCurrency->decimal_places;
                }
                
                $account->differences = $this->subtract($account->startBalances, $account->endBalances);
                
                // Get values directly from account fields (no meta queries needed)
                $account->interest = Steam::bcround($account->interest ?? 0, 4);
                $account->product_name = $account->product_name ?? $account->accountTypeString;
                
                // Use pre-loaded translations instead of calling trans() repeatedly
                $translationStart = microtime(true);
                $interestPeriod = $account->interest_period ?? 'monthly';
                $account->interestPeriod = $translations['interest_calc_' . $interestPeriod] ?? $interestPeriod;
                $account->accountTypeString = $translations['account_type_' . $account->accountType->type] ?? $account->accountType->type;
                $translationTime += microtime(true) - $translationStart;
                $translationCount += 0; // No translation calls now - all pre-loaded
                
                $account->current_debt = '0';
                $account->currency = $currency ?? $this->primaryCurrency;
                $account->iban = implode(' ', str_split((string) $account->iban, 4));
            }
        );
        
        $balanceCalcTotalTime = microtime(true) - $balanceCalcStart;
        
        app('log')->debug('🔧 [PERF] Balance calculation completed:', [
            'total_time_ms' => round($balanceCalcTotalTime * 1000, 2),
            'accounts_with_balance_calc' => $balanceCalcCount,
            'accounts_without_transactions' => $accounts->count() - $balanceCalcCount,
            'avg_balance_calc_ms' => $balanceCalcCount > 0 ? round(($balanceCalcTime * 1000) / $balanceCalcCount, 2) : 0,
            'currency_query_time_ms' => round($metaQueryTime * 1000, 2),
            'currency_query_count' => $metaQueryCount,
            'translation_time_ms' => round($translationTime * 1000, 2),
            'translation_count' => $translationCount,
            'other_time_ms' => round(($balanceCalcTotalTime - $balanceCalcTime - $metaQueryTime - $translationTime) * 1000, 2)
        ]);

        // Calculate total account counts for display
        $totalAccounts = $total;
        $visibleAccounts = $accounts->count();
        
        $totalTime = microtime(true) - $startTime;
        
        app('log')->debug('🔧 [PERF] IndexController::all - COMPLETE PERFORMANCE SUMMARY:', [
            'total_time_ms' => round($totalTime * 1000, 2),
            'database_query_ms' => round($dbTime * 1000, 2),
            'transaction_query_ms' => round($transactionQueryTime * 1000, 2),
            'activities_query_ms' => round($activitiesTime * 1000, 2),
            'balance_calculation_ms' => round($balanceCalcTotalTime * 1000, 2),
            'accounts_returned' => $accounts->count(),
            'accounts_with_transactions' => $balanceCalcCount,
            'accounts_without_transactions' => $accounts->count() - $balanceCalcCount,
            'breakdown' => [
                'db_query_percent' => round(($dbTime / $totalTime) * 100, 1),
                'transaction_queries_percent' => round((($transactionQueryTime + $activitiesTime) / $totalTime) * 100, 1),
                'balance_calc_percent' => round(($balanceCalcTotalTime / $totalTime) * 100, 1),
                'other_percent' => round((($totalTime - $dbTime - $transactionQueryTime - $activitiesTime - $balanceCalcTotalTime) / $totalTime) * 100, 1)
            ]
        ]);
        
        app('log')->debug('🔧 [PERF] IndexController::all - Starting view rendering');
        $viewStartTime = microtime(true);
        
        // Define variables for the view
        $convertToPrimary = $this->convertToPrimary;
        $primaryCurrency = $this->primaryCurrency;
        
        $view = view('accounts.all', compact('subTitle', 'subTitleIcon', 'accounts', 'totalAccounts', 'visibleAccounts', 'sortColumn', 'sortDirection', 'pageSize', 'financialEntities', 'metaValues', 'accountHolders', 'accountInstitutions', 'accountTypeNames', 'accountData', 'convertToPrimary', 'primaryCurrency'));
        
        // Force the view to render immediately to see if that's where the delay is
        app('log')->debug('🔧 [PERF] IndexController::all - About to render view to string');
        $renderStartTime = microtime(true);
        $renderedContent = $view->render();
        $renderTime = microtime(true) - $renderStartTime;
        app('log')->debug('🔧 [PERF] IndexController::all - View rendered to string', [
            'render_time_ms' => round($renderTime * 1000, 2),
            'content_length' => strlen($renderedContent)
        ]);
        
        $viewTime = microtime(true) - $viewStartTime;
        app('log')->debug('🔧 [PERF] IndexController::all - View rendering completed', [
            'view_time_ms' => round($viewTime * 1000, 2)
        ]);
        
        app('log')->debug('🔧 [PERF] IndexController::all - About to return view');
        $returnTime = microtime(true);
        $result = $view;
        $returnTime = microtime(true) - $returnTime;
        app('log')->debug('🔧 [PERF] IndexController::all - View returned', [
            'return_time_ms' => round($returnTime * 1000, 2)
        ]);
        
        // Add a final log to see when the controller actually exits
        app('log')->debug('🔧 [PERF] IndexController::all - Controller exiting');
        app('log')->debug('🔧 [PERF] IndexController::all - About to return view to browser');
        
        return $result;
    }

    /**
     * Show list of accounts.
     *
     * @return Factory|View
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request, string $objectType)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $subTitle      = (string) trans(sprintf('firefly.%s_accounts', $objectType));
        $subTitleIcon  = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $types         = config(sprintf('firefly.accountTypesByIdentifier.%s', $objectType));


        $collection    = $this->repository->getActiveAccountsByType($types);
        $total         = $collection->count();
        $page          = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $pageSize      = (int) app('preferences')->get('listPageSize', 50)->data;
        $accounts      = $collection->slice(($page - 1) * $pageSize, $pageSize);
        $inactiveCount = $this->repository->getInactiveAccountsByType($types)->count();

        app('log')->debug(sprintf('Count of collection: %d, count of accounts: %d', $total, $accounts->count()));

        unset($collection);

        /** @var Carbon $start */
        $start         = clone session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end           = clone session('end', today(config('app.timezone'))->endOfMonth());

        // #10618 go to the end of the previous day.
        $start->subSecond();

        $ids           = $accounts->pluck('id')->toArray();
        
        // Check if any accounts have transactions to avoid expensive balance calculations
        $hasTransactions = \FireflyIII\Models\Transaction::whereIn('account_id', $ids)->exists();
        
        if ($hasTransactions) {
            Log::debug(sprintf('index start: accountsBalancesOptimized("%s")', $start->format('Y-m-d H:i:s')));
            Log::debug(sprintf('index end: accountsBalancesOptimized("%s")', $end->format('Y-m-d H:i:s')));
            $startBalances = Steam::accountsBalancesOptimized($accounts, $start, $this->primaryCurrency, $this->convertToPrimary);
            $endBalances   = Steam::accountsBalancesOptimized($accounts, $end, $this->primaryCurrency, $this->convertToPrimary);
        } else {
            // No transactions, so all balances are 0
            $startBalances = [];
            $endBalances = [];
            foreach ($accounts as $account) {
                $startBalances[$account->id] = ["balance" => "0", $this->primaryCurrency->code => "0"];
                $endBalances[$account->id] = ["balance" => "0", $this->primaryCurrency->code => "0"];
            }
        }
        
        $activities    = Steam::getLastActivities($ids);


        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances): void {
                $interest                     = (string) $this->repository->getMetaValue($account, 'interest');
                $interest                     = '' === $interest ? '0' : $interest;
                $currency                     = $this->repository->getAccountCurrency($account);

                $account->startBalances       = Steam::filterAccountBalance($startBalances[$account->id] ?? [], $account, $this->convertToPrimary, $currency);
                $account->endBalances         = Steam::filterAccountBalance($endBalances[$account->id] ?? [], $account, $this->convertToPrimary, $currency);
                $account->differences         = $this->subtract($account->startBalances, $account->endBalances);
                $account->lastActivityDate    = $this->isInArrayDate($activities, $account->id);
                $account->interest            = Steam::bcround($interest, 4);
                $account->interestPeriod      = (string) trans(
                    sprintf('firefly.interest_calc_%s', $this->repository->getMetaValue($account, 'interest_period'))
                );
                $account->accountTypeString   = (string) trans(sprintf('firefly.account_type_%s', $account->accountType->type));
                $account->location            = $this->repository->getLocation($account);
                $account->liability_direction = $this->repository->getMetaValue($account, 'liability_direction');
                $account->current_debt        = $this->repository->getMetaValue($account, 'current_debt') ?? '-';
                $account->currency            = $currency ?? $this->primaryCurrency;
                $account->iban                = implode(' ', str_split((string) $account->iban, 4));


            }
        );
        // make paginator:
        app('log')->debug(sprintf('Count of accounts before LAP: %d', $accounts->count()));

        /** @var LengthAwarePaginator $accounts */
        $accounts      = new LengthAwarePaginator($accounts, $total, $pageSize, $page);
        $accounts->setPath(route('accounts.index', [$objectType]));

        app('log')->debug(sprintf('Count of accounts after LAP (1): %d', $accounts->count()));
        app('log')->debug(sprintf('Count of accounts after LAP (2): %d', $accounts->getCollection()->count()));

        return view('accounts.index', compact('objectType', 'inactiveCount', 'subTitleIcon', 'subTitle', 'page', 'accounts'));
    }
    
    /**
     * Get account holders for an account
     */
    private function getAccountHolders($account, $financialEntities)
    {
        if (!$account->account_holder_ids) {
            return collect();
        }
        
        $holderIds = is_string($account->account_holder_ids) 
            ? json_decode($account->account_holder_ids, true) 
            : $account->account_holder_ids;
            
        if (!is_array($holderIds)) {
            return collect();
        }
        
        return collect($holderIds)->map(function ($holderId) use ($financialEntities) {
            return $financialEntities->get($holderId);
        })->filter();
    }
    
    /**
     * Get account beneficiaries for an account
     */
    private function getAccountBeneficiaries($account)
    {
        // This is a simplified version - you may need to implement the actual logic
        // based on how accountGetBeneficiaries works in your system
        return [];
    }
    
    /**
     * Get account beneficiaries count
     */
    private function getAccountBeneficiariesCount($account)
    {
        $beneficiaries = $this->getAccountBeneficiaries($account);
        if (empty($beneficiaries)) {
            return 0;
        }
        
        $total = 0;
        foreach ($beneficiaries as $priority => $beneficiaryList) {
            $total += count($beneficiaryList);
        }
        
        return $total;
    }
}
