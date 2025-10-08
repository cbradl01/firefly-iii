<?php

/**
 * ImportController.php
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

namespace FireflyIII\Http\Controllers\Account;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
// use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class ImportController
 */
class ImportController extends Controller
{
    use BasicDataSupport;

    private AccountRepositoryInterface $repository;

    /**
     * ImportController constructor.
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
     * Show the import form.
     *
     * @return Factory|View
     */
    public function importCsv(Request $request, string $objectType)
    {
        $subTitleIcon = 'fa-file-text-o';
        $subTitle = 'Import ' . ucfirst($objectType) . ' Accounts from CSV';

        return view('accounts.import-csv', compact('subTitleIcon', 'subTitle', 'objectType'));
    }

    /**
     * Process the CSV import.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function processCsv(Request $request, string $objectType)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getPathname();

        try {
            // Load the CSV file
            $rows = [];
            if (($handle = fopen($filePath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }

            if (empty($rows)) {
                return redirect()->back()->withErrors(['csv_file' => 'The CSV file is empty.']);
            }

            // Get header row
            $headers = array_shift($rows);
            $headers = array_map('strtolower', array_map('trim', $headers));
            
            Log::info('CSV Import - Headers found', ['headers' => $headers]);
            Log::info('CSV Import - Processing ' . count($rows) . ' data rows');

            // Find required columns
            $nameIndex = array_search('name', $headers);
            $accountNameIndex = array_search('account_name', $headers);
            $institutionIndex = array_search('institution', $headers);
            $accountTypeIndex = array_search('account_type', $headers);
            $openingBalanceIndex = array_search('opening_balance', $headers);
            $openingBalanceDateIndex = array_search('opening_balance_date', $headers);
            $currencyCodeIndex = array_search('currency_code', $headers);
            $ibanIndex = array_search('iban', $headers);
            $accountNumberIndex = array_search('account_number', $headers);
            $notesIndex = array_search('notes', $headers);
            $activeIndex = array_search('active', $headers);
            
            // Find liability-specific columns
            $accountTypeIdIndex = array_search('account_type_id', $headers);
            $liabilityTypeIndex = array_search('liability_type', $headers);
            $liabilityDirectionIndex = array_search('liability_direction', $headers);
            $interestIndex = array_search('interest', $headers);
            $interestPeriodIndex = array_search('interest_period', $headers);
            
            Log::info('CSV Import - Column indices', [
                'name_index' => $nameIndex,
                'account_name_index' => $accountNameIndex,
                'institution_index' => $institutionIndex,
                'opening_balance_index' => $openingBalanceIndex,
            ]);

            if ($nameIndex === false && $accountNameIndex === false) {
                return redirect()->back()->withErrors(['csv_file' => 'Required column "name" or "account_name" not found.']);
            }

            $createdCount = 0;
            $skippedCount = 0;
            $errors = [];
            $skippedReasons = [];

            foreach ($rows as $rowIndex => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    // Log first few rows for debugging
                    if ($rowIndex < 3) {
                        Log::info('CSV Import - Processing row ' . ($rowIndex + 2), ['row_data' => $row]);
                    }

                    // Get account name (prefer 'name' over 'account_name')
                    $accountName = '';
                    if ($nameIndex !== false && isset($row[$nameIndex])) {
                        $accountName = trim($row[$nameIndex]);
                    } elseif ($accountNameIndex !== false && isset($row[$accountNameIndex])) {
                        $accountName = trim($row[$accountNameIndex]);
                    }

                    if (empty($accountName)) {
                        $skippedCount++;
                        $skippedReasons[] = "Row " . ($rowIndex + 2) . ": Empty account name";
                        continue;
                    }

                    // Check if account already exists
                    $types = config(sprintf('firefly.accountTypesByIdentifier.%s', $objectType));
                    $existingAccount = $this->repository->findByName($accountName, $types);
                    if ($existingAccount) {
                        $skippedCount++;
                        $skippedReasons[] = "Row " . ($rowIndex + 2) . ": Account '$accountName' already exists";
                        continue;
                    }

                    // Prepare account data
                    $accountData = [
                        'name' => $accountName,
                        'active' => true,
                        'currency_id' => $this->defaultCurrency->id,
                        'virtual_balance' => '',
                        'iban' => ($ibanIndex !== false && isset($row[$ibanIndex])) ? trim($row[$ibanIndex]) : '',
                        'BIC' => '',
                        'account_number' => ($accountNumberIndex !== false && isset($row[$accountNumberIndex])) ? trim($row[$accountNumberIndex]) : '',
                        'account_role' => 'defaultAsset',
                        'opening_balance' => '',
                        'opening_balance_date' => null,
                        'cc_type' => '',
                        'cc_monthly_payment_date' => '',
                        'notes' => ($notesIndex !== false && isset($row[$notesIndex])) ? trim($row[$notesIndex]) : '',
                        'interest' => '',
                        'interest_period' => 'monthly',
                        'include_net_worth' => '1',
                        'liability_direction' => '',
                    ];

                    // Handle liability-specific data from CSV
                    if ($objectType === 'liabilities') {
                        // Read liability_type from CSV
                        if ($liabilityTypeIndex !== false && isset($row[$liabilityTypeIndex])) {
                            $liabilityType = trim($row[$liabilityTypeIndex]);
                            if (!empty($liabilityType)) {
                                $accountData['type'] = 'liability';
                                $accountData['liability_type'] = $liabilityType;
                            }
                        }
                        
                        // Read liability_direction from CSV
                        if ($liabilityDirectionIndex !== false && isset($row[$liabilityDirectionIndex])) {
                            $liabilityDirection = trim($row[$liabilityDirectionIndex]);
                            if (!empty($liabilityDirection)) {
                                $accountData['liability_direction'] = $liabilityDirection;
                            }
                        }
                        
                        // Read interest from CSV
                        if ($interestIndex !== false && isset($row[$interestIndex])) {
                            $interest = trim($row[$interestIndex]);
                            if (!empty($interest) && is_numeric($interest)) {
                                $accountData['interest'] = $interest;
                            }
                        }
                        
                        // Read interest_period from CSV
                        if ($interestPeriodIndex !== false && isset($row[$interestPeriodIndex])) {
                            $interestPeriod = trim($row[$interestPeriodIndex]);
                            if (!empty($interestPeriod)) {
                                $accountData['interest_period'] = $interestPeriod;
                            }
                        }
                        
                        // Remove account_role for liability accounts
                        unset($accountData['account_role']);
                    } else {
                        // For asset accounts, set the type explicitly
                        $accountData['type'] = 'asset';
                    }

                    // Set opening balance if provided
                    if ($openingBalanceIndex !== false && isset($row[$openingBalanceIndex]) && !empty(trim($row[$openingBalanceIndex]))) {
                        $openingBalance = trim($row[$openingBalanceIndex]);
                        // Clean and validate the opening balance
                        $openingBalance = preg_replace('/[^0-9.-]/', '', $openingBalance);
                        if (is_numeric($openingBalance)) {
                            $accountData['opening_balance'] = $openingBalance;
                            if ($openingBalanceDateIndex !== false && isset($row[$openingBalanceDateIndex]) && !empty(trim($row[$openingBalanceDateIndex]))) {
                                try {
                                    $accountData['opening_balance_date'] = \Carbon\Carbon::parse(trim($row[$openingBalanceDateIndex]));
                                } catch (\Exception $e) {
                                    // Use today's date if parsing fails
                                    $accountData['opening_balance_date'] = today();
                                }
                            } else {
                                $accountData['opening_balance_date'] = today();
                            }
                        }
                    }

                    // Set active status if provided
                    if ($activeIndex !== false && isset($row[$activeIndex])) {
                        $activeValue = strtolower(trim($row[$activeIndex]));
                        $accountData['active'] = in_array($activeValue, ['1', 'true', 'yes', 'active']);
                    }

                    // Create the account using the API endpoint
                    try {
                        $account = $this->createAccountViaAPI($accountData);
                        $createdCount++;

                        Log::channel('audit')->info('Imported account from CSV.', [
                            'account_id' => $account->id,
                            'account_name' => $account->name,
                            'row_index' => $rowIndex + 2, // +2 because we removed header and arrays are 0-indexed
                        ]);
                    } catch (\Exception $e) {
                        $skippedCount++;
                        $skippedReasons[] = "Row " . ($rowIndex + 2) . ": Failed to create account '$accountName' - " . $e->getMessage();
                        Log::error('Error creating account from CSV row ' . ($rowIndex + 2), [
                            'error' => $e->getMessage(),
                            'account_data' => $accountData,
                        ]);
                    }

                } catch (\Exception $e) {
                    $skippedCount++;
                    $skippedReasons[] = "Row " . ($rowIndex + 2) . ": Processing error - " . $e->getMessage();
                    Log::error('Error importing account from CSV row ' . ($rowIndex + 2), [
                        'error' => $e->getMessage(),
                        'row_data' => $row,
                    ]);
                }
            }

            // Prepare success message
            $message = sprintf(
                'Successfully imported %d accounts from CSV. %d accounts were skipped.',
                $createdCount,
                $skippedCount
            );

            // Add detailed reasons for skipped accounts
            if (!empty($skippedReasons)) {
                $message .= "\n\nSkipped accounts:\n" . implode("\n", array_slice($skippedReasons, 0, 10));
                if (count($skippedReasons) > 10) {
                    $message .= "\n(and " . (count($skippedReasons) - 10) . " more skipped accounts)";
                }
            }

            if (!empty($errors)) {
                $message .= "\n\nOther errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= ' (and ' . (count($errors) - 5) . ' more errors)';
                }
            }

            return redirect()->route('accounts.index', [$objectType])
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error processing CSV file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            return redirect()->back()->withErrors(['csv_file' => 'Error processing CSV file: ' . $e->getMessage()]);
        }
    }




    /**
     * Show the JSON import form
     */
    public function importJson(string $objectType)
    {
        $subTitle = 'Import ' . ucfirst($objectType) . ' accounts from JSON';
        
        return view('accounts.import-json', compact('objectType', 'subTitle'));
    }

    /**
     * Import accounts from JSON file (alternative to CSV)
     * This method provides explicit account definitions
     */
    public function importFromJson(Request $request, string $objectType)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json|max:10240', // 10MB max
        ]);

        $file = $request->file('json_file');
        
        try {
            $jsonContent = file_get_contents($file->getPathname());
            $accounts = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->withErrors(['json_file' => 'Invalid JSON file: ' . json_last_error_msg()]);
            }
            
            if (!is_array($accounts)) {
                return redirect()->back()->withErrors(['json_file' => 'JSON file must contain an array of accounts']);
            }
            
            $createdCount = 0;
            $skippedCount = 0;
            $skippedReasons = [];
            
            foreach ($accounts as $index => $accountData) {
                try {
                    // Handle source account for liability credit accounts
                    $sourceAccountId = null;
                    if (isset($accountData['source_account_id']) && !empty($accountData['source_account_id'])) {
                        $sourceAccountId = $accountData['source_account_id'];
                        unset($accountData['source_account_id']); // Remove from account data
                    }
                    
                    // Create account directly using the repository
                    Log::info('Creating account from JSON', ['account_data' => $accountData]);
                    
                    // Use the account factory to create the account
                    $factory = app(\FireflyIII\Factory\AccountFactory::class);
                    $factory->setUser(auth()->user());
                    $account = $factory->create($accountData);
                    
                    if (!$account) {
                        throw new \Exception('Failed to create account');
                    }
                    
                    // Update the opening balance transaction to use the real source account
                    if ($sourceAccountId && $account) {
                        $this->updateOpeningBalanceSourceAccount($account, $sourceAccountId);
                    }
                    
                    $createdCount++;
                    
                    Log::channel('audit')->info('Imported account from JSON.', [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'account_index' => $index + 1,
                    ]);
                    
                } catch (\Exception $e) {
                    $skippedCount++;
                    $skippedReasons[] = "Account " . ($index + 1) . ": " . $e->getMessage();
                    Log::error('Error importing account from JSON', [
                        'account_index' => $index + 1,
                        'account_data' => $accountData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $message = "Import completed successfully! Created {$createdCount} accounts.";
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} accounts.";
            }
            
            if (!empty($skippedReasons)) {
                $message .= "\n\nSkipped accounts: " . implode('; ', array_slice($skippedReasons, 0, 5));
                if (count($skippedReasons) > 5) {
                    $message .= ' (and ' . (count($skippedReasons) - 5) . ' more)';
                }
            }
            
            return redirect()->route('accounts.index', [$objectType])
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Error processing JSON file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);
            
            return redirect()->back()->withErrors(['json_file' => 'Error processing JSON file: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update the opening balance transaction to use a real source account
     */
    private function updateOpeningBalanceSourceAccount(Account $account, int $sourceAccountId): void
    {
        try {
            // Get the opening balance transaction group
            $openingBalance = $this->repository->getOpeningBalance($account);
            if (!$openingBalance) {
                Log::warning('No opening balance found for account', ['account_id' => $account->id]);
                return;
            }
            
            // Find the source transaction (negative amount)
            $sourceTransaction = $openingBalance->transactions()->where('amount', '<', 0)->first();
            if (!$sourceTransaction) {
                Log::warning('No source transaction found in opening balance', ['account_id' => $account->id]);
                return;
            }
            
            // Verify the source account exists and belongs to the user
            $sourceAccount = \FireflyIII\Models\Account::where('id', $sourceAccountId)
                ->where('user_id', Auth::id())
                ->first();
                
            if (!$sourceAccount) {
                Log::warning('Source account not found or does not belong to user', [
                    'source_account_id' => $sourceAccountId,
                    'user_id' => Auth::id()
                ]);
                return;
            }
            
            // Update the source transaction to use the real account
            $sourceTransaction->account_id = $sourceAccountId;
            $sourceTransaction->save();
            
            Log::info('Updated opening balance source account', [
                'account_id' => $account->id,
                'source_account_id' => $sourceAccountId,
                'source_account_name' => $sourceAccount->name
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update opening balance source account', [
                'account_id' => $account->id,
                'source_account_id' => $sourceAccountId,
                'error' => $e->getMessage()
            ]);
        }
    }

}
