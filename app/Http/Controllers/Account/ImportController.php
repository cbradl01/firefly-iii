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
use FireflyIII\Services\AccountClassificationService;
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
    private AccountClassificationService $classificationService;

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
                $this->classificationService = app(AccountClassificationService::class);

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
                        'currency_id' => Auth::user()->currency->id,
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
        
        // Get available entities for mapping reference
        $entityMapping = $this->getEntityMapping();
        
        return view('accounts.import-json', compact('objectType', 'subTitle', 'entityMapping'));
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
            
            // Get entity mapping for owner field
            $entityMapping = $this->getEntityMapping();
            
            // Check if any entities are available for mapping
            if (empty($entityMapping)) {
                return redirect()->back()->withErrors(['json_file' => 'No financial entities are available for owner mapping. Please create some entities first before importing accounts.']);
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
                    
                    // Use account classification service to map account type to Firefly III format
                    if (isset($accountData['account_type'])) {
                        $mapping = $this->classificationService->getFireflyMapping($accountData['account_type']);
                        
                        if ($mapping) {
                            // Use the mapping from the classification system for Firefly III internal processing
                            $accountData['account_type_name'] = $mapping['firefly_type'];
                            $accountData['account_role'] = $mapping['firefly_role'];
                            
                            // Keep the descriptive account type as metadata for display
                            // Don't unset it - let it be stored as metadata
                            
                            Log::info('Mapped account type using classification system', [
                                'descriptive_type' => $accountData['account_type'],
                                'firefly_type' => $mapping['firefly_type'],
                                'firefly_role' => $mapping['firefly_role']
                            ]);
                        } else {
                            // Fallback to original behavior if no mapping found
                            $accountData['account_type_name'] = $accountData['account_type'];
                            
                            Log::warning('No classification mapping found for account type', [
                                'account_type' => $accountData['account_type']
                            ]);
                        }
                        
                        // Don't unset account_type - let it be stored as metadata for display
                    }
                    
        // Handle template_name field for account type mapping
        if (isset($accountData['template_name']) && !empty($accountData['template_name'])) {
            // Look up template by template_name in database
            $template = \FireflyIII\Models\AccountTemplate::where('template_name', $accountData['template_name'])
                ->where('active', true)
                ->first();
            
            if ($template) {
                $accountData['template'] = $template->name; // Use the display name for AccountFactory
                unset($accountData['template_name']); // Remove template_name, keep template
                
                Log::info('Using template for account type mapping', [
                    'template_name' => $accountData['template_name'],
                    'template_display_name' => $template->name,
                    'account_name' => $accountData['name'] ?? 'Unknown'
                ]);
            } else {
                Log::warning('Template not found for account import', [
                    'template_name' => $accountData['template_name'],
                    'account_name' => $accountData['name'] ?? 'Unknown'
                ]);
                // Continue without template - will fall back to category/behavior mapping
            }
        }
                    
                    // Debug currency information
                    Log::info('Currency debug', [
                        'currency_id' => $accountData['currency_id'] ?? 'NOT_SET',
                        'currency_code' => $accountData['currency_code'] ?? 'NOT_SET',
                        'has_currency_id' => isset($accountData['currency_id']),
                        'has_currency_code' => isset($accountData['currency_code'])
                    ]);
                    
                    // Add default currency if not present or is 0
                    if ((!isset($accountData['currency_id']) || $accountData['currency_id'] == 0) && !isset($accountData['currency_code'])) {
                        // Get the user's primary currency
                        $primaryCurrency = app('amount')->getPrimaryCurrencyByUserGroup(Auth::user()->userGroup);
                        $accountData['currency_id'] = $primaryCurrency->id;
                        Log::info('Added default currency_id to account data', [
                            'currency_id' => $primaryCurrency->id,
                            'currency_code' => $primaryCurrency->code
                        ]);
                    }
                    
                    // Map owner to entity ID - this is required for import
                    if (isset($accountData['owner']) && !empty($accountData['owner'])) {
                        $ownerDisplayName = $accountData['owner'];
                        if (isset($entityMapping[$ownerDisplayName])) {
                            $accountData['entity_id'] = $entityMapping[$ownerDisplayName];
                            Log::info('Mapped owner to entity', [
                                'owner' => $ownerDisplayName,
                                'entity_id' => $accountData['entity_id']
                            ]);
                        } else {
                            // Skip this account if owner doesn't match any entity
                            $skippedCount++;
                            $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountData['name']}'): Owner '{$ownerDisplayName}' does not match any existing financial entity. Available entities: " . implode(', ', array_keys($entityMapping));
                            Log::warning('Skipping account - owner not found in entity mapping', [
                                'account_name' => $accountData['name'],
                                'owner' => $ownerDisplayName,
                                'available_entities' => array_keys($entityMapping)
                            ]);
                            continue; // Skip to next account
                        }
                    } else {
                        // Skip accounts without owner
                        $skippedCount++;
                        $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountData['name']}'): No owner specified. All accounts must have an owner that matches an existing financial entity.";
                        Log::warning('Skipping account - no owner specified', [
                            'account_name' => $accountData['name']
                        ]);
                        continue; // Skip to next account
                    }
                    
                    // Create account directly using the repository
                    Log::info('Creating account from JSON', ['account_data' => $accountData]);
                    Log::info('Owner field check', [
                        'owner' => $accountData['owner'] ?? 'NOT_SET', 
                        'entity_id' => $accountData['entity_id'] ?? 'NOT_SET',
                        'institution' => $accountData['institution'] ?? 'NOT_SET',
                        'owner_type' => gettype($accountData['owner'] ?? null),
                        'institution_type' => gettype($accountData['institution'] ?? null),
                        'owner_empty' => empty($accountData['owner'] ?? null),
                        'institution_empty' => empty($accountData['institution'] ?? null)
                    ]);
                    
                    // Extract beneficiaries data before creating account
                    $beneficiariesData = null;
                    if (isset($accountData['beneficiaries'])) {
                        $beneficiariesData = $accountData['beneficiaries'];
                        unset($accountData['beneficiaries']); // Remove from account data
                    }
                    
                    // Use the account factory to create the account
                    $factory = app(\FireflyIII\Factory\AccountFactory::class);
                    $factory->setUser(Auth::user());
                    $account = $factory->create($accountData);
                    
                    if (!$account) {
                        throw new \Exception('Failed to create account');
                    }
                    
                    // Create beneficiaries if provided
                    if ($beneficiariesData && $account) {
                        $this->createBeneficiaries($account, $beneficiariesData);
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
            
            if ($createdCount > 0) {
                $message = "Import completed successfully! Created {$createdCount} accounts.";
                if ($skippedCount > 0) {
                    $message .= " Skipped {$skippedCount} accounts due to owner mapping issues.";
                }
            } else {
                $message = "Import failed! No accounts were created.";
                if ($skippedCount > 0) {
                    $message .= " All {$skippedCount} accounts were skipped due to owner mapping issues.";
                }
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
     * Get entity mapping for owner field mapping
     * Maps owner display names to entity IDs
     */
    private function getEntityMapping(): array
    {
        $user = Auth::user();
        
        // Get all entities that the user has permission to see
        $entities = \FireflyIII\Models\FinancialEntity::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('is_active', true)->get();
        
        $mapping = [];
        foreach ($entities as $entity) {
            // Use display_name if available, otherwise use name
            $displayName = $entity->display_name ?: $entity->name;
            $mapping[$displayName] = $entity->id;
        }
        
        Log::info('Entity mapping created', [
            'mapping' => $mapping,
            'total_entities' => count($mapping)
        ]);
        
        return $mapping;
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

    /**
     * Create beneficiaries for an account from JSON data
     */
    private function createBeneficiaries(Account $account, array $beneficiariesData): void
    {
        try {
            // Handle both old format (with primary/contingency) and new format (direct array)
            $beneficiariesToCreate = [];
            
            if (isset($beneficiariesData['primary']) && is_array($beneficiariesData['primary'])) {
                // Old format: { "primary": [...], "contingency": [...] }
                foreach ($beneficiariesData['primary'] as $beneficiary) {
                    $beneficiariesToCreate[] = array_merge($beneficiary, ['priority' => 'primary']);
                }
                
                if (isset($beneficiariesData['contingency']) && is_array($beneficiariesData['contingency'])) {
                    foreach ($beneficiariesData['contingency'] as $beneficiary) {
                        $beneficiariesToCreate[] = array_merge($beneficiary, ['priority' => 'secondary']);
                    }
                }
            } elseif (is_array($beneficiariesData)) {
                // New format: direct array of beneficiaries
                $beneficiariesToCreate = $beneficiariesData;
            }
            
            foreach ($beneficiariesToCreate as $beneficiaryData) {
                // Ensure required fields
                if (!isset($beneficiaryData['name']) || empty($beneficiaryData['name'])) {
                    continue;
                }
                
                // Set default priority if not specified
                if (!isset($beneficiaryData['priority'])) {
                    $beneficiaryData['priority'] = 'primary';
                }
                
                // Validate priority
                $validPriorities = ['primary', 'secondary', 'tertiary', 'quaternary'];
                if (!in_array($beneficiaryData['priority'], $validPriorities)) {
                    $beneficiaryData['priority'] = 'primary';
                }
                
                // Create the beneficiary
                $beneficiary = new \FireflyIII\Models\Beneficiary([
                    'account_id' => $account->id,
                    'name' => $beneficiaryData['name'],
                    'relationship' => $beneficiaryData['relationship'] ?? null,
                    'priority' => $beneficiaryData['priority'],
                    'percentage' => $beneficiaryData['percentage'] ?? null,
                    'email' => $beneficiaryData['email'] ?? null,
                    'phone' => $beneficiaryData['phone'] ?? null,
                    'notes' => $beneficiaryData['notes'] ?? null,
                ]);
                
                $beneficiary->save();
                
                Log::info('Created beneficiary for account', [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'beneficiary_name' => $beneficiary->name,
                    'beneficiary_priority' => $beneficiary->priority,
                    'beneficiary_percentage' => $beneficiary->percentage,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to create beneficiaries for account', [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'beneficiaries_data' => $beneficiariesData,
                'error' => $e->getMessage()
            ]);
        }
    }


}
