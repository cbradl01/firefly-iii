<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Account\PfinanceRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Api\ApiResponse;
use FireflyIII\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class PfinanceController.
 */
class PfinanceController extends Controller
{
    private string $pfinanceServiceUrl;

    public function __construct()
    {
        parent::__construct();
        $this->pfinanceServiceUrl = config('firefly.pfinance_service_url', 'http://pfinance-microservice:5001');
    }

    /**
     * Get account metadata and calculate account path for PFinance integration.
     */
    private function getAccountMetadataForPfinance(string $accountId): array
    {
        $account = Account::with(['accountType', 'accountHolder', 'institutionEntity'])->find($accountId);
        
        if (!$account) {
            throw new \Exception("Account not found: {$accountId}");
        }

        // Get all account data
        $accountData = $account->toArray();

        // Add calculated fields using model properties
        $accountData['account_path'] = $account->account_path;
        $accountData['institution'] = $account->institutionEntity?->name ?? $account->institution;
        $accountData['account_holders'] = $account->account_holders;
        $accountData['account_type'] = $account->accountType?->name;
        
        return $accountData;
    }

    /**
     * Consolidate all transactions.
     */
    public function consolidateTransactions(Request $request): JsonResponse
    {
        try {
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/consolidate-transactions');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error consolidating transactions',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Consolidate transactions for a specific account.
     */
    public function consolidateTransactionsForAccount(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            // Get account metadata and calculate account path
            $accountData = $this->getAccountMetadataForPfinance($accountId);
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/consolidate-transactions/' . $accountId, [
                'account_data' => $accountData
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error consolidating transactions for account',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Generate Firefly-III transactions.
     */
    public function generateFireflyTransactions(Request $request): JsonResponse
    {
        try {
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/generate-firefly-transactions');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error generating Firefly transactions',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Generate Firefly-III transactions for a specific account.
     */
    public function generateFireflyTransactionsForAccount(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            // Get account metadata and calculate account path
            $accountData = $this->getAccountMetadataForPfinance($accountId);
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/generate-firefly-transactions/' . $accountId, [
                'account_data' => $accountData
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error generating Firefly transactions',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Error generating Firefly transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Consolidate and generate Firefly-III transactions for a specific account.
     */
    public function consolidateAndGenerateTransactionsForAccount(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            // Get account metadata and calculate account path
            $accountData = $this->getAccountMetadataForPfinance($accountId);
            
            // First, consolidate transactions
            $consolidateResponse = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/consolidate-transactions/' . $accountId, [
                'account_data' => $accountData
            ]);
            
            if (!$consolidateResponse->successful()) {
                Log::error('PFinance consolidation service error', [
                    'status' => $consolidateResponse->status(),
                    'body' => $consolidateResponse->body()
                ]);
                
                return response()->json([
                    'message' => 'Error consolidating transactions for account',
                    'category' => 'error'
                ], 500);
            }
            
            // Then, generate Firefly transactions
            $generateResponse = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/generate-firefly-transactions/' . $accountId, [
                'account_data' => $accountData
            ]);
            
            if (!$generateResponse->successful()) {
                Log::error('PFinance generation service error', [
                    'status' => $generateResponse->status(),
                    'body' => $generateResponse->body()
                ]);
                
                return response()->json([
                    'message' => 'Error generating Firefly transactions for account',
                    'category' => 'error'
                ], 500);
            }
            
            // Return combined success response
            return response()->json([
                'message' => 'Transactions consolidated and Firefly transactions generated successfully',
                'category' => 'success',
                'consolidation_result' => $consolidateResponse->json(),
                'generation_result' => $generateResponse->json()
            ]);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Import Firefly-III transactions for a specific account.
     */
    public function importFireflyTransactions(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/import-firefly-transactions', [
                'account_id' => $accountId
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error importing Firefly transactions',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Push transactions to Google for a specific account.
     */
    public function pushToGoogle(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/push-to-google', [
                'account_id' => $accountId
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error pushing to Google',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Get Google transactions for a specific account.
     */
    public function getGoogleTransactions(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/get-google-transactions', [
                'account_id' => $accountId
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error getting Google transactions',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Reset database for a specific account.
     */
    public function resetDb(PfinanceRequest $request): JsonResponse
    {
        try {
            $accountId = $request->get('account_id');
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/reset-db', [
                'account_id' => $accountId
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error resetting database',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Match unknown counterparties in transactions.
     */
    public function matchTransactions(Request $request): JsonResponse
    {
        try {
            $requestData = $request->all();
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/match-transactions', $requestData);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error('PFinance service error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'message' => 'Error matching transactions',
                'category' => 'error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
                'category' => 'error'
            ], 503);
        }
    }

    /**
     * Get PFinance service status.
     */
    public function getStatus(): JsonResponse
    {
        try {
            $response = Http::get($this->pfinanceServiceUrl . '/api/v1/accounts/status');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'status' => 'unhealthy',
                'service' => 'pfinance-microservice',
                'error' => 'Service not responding'
            ], 503);
            
        } catch (\Exception $e) {
            Log::error('PFinance service exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'unhealthy',
                'service' => 'pfinance-microservice',
                'error' => 'Service unavailable'
            ], 503);
        }
    }

    /**
     * Import accounts using the modern AccountFactory approach.
     * This endpoint provides a consistent way to create accounts programmatically
     * using the same logic as the web import functionality.
     */
    public function importAccounts(Request $request): JsonResponse
    {
        try {
            // Validate the request using FieldDefinitions
            $accountValidationRules = \FireflyIII\FieldDefinitions\FieldDefinitions::getValidationRules('account');
            
            // Build validation rules for the accounts array
            $validationRules = ['accounts' => 'required|array|min:1'];
            foreach ($accountValidationRules as $field => $rule) {
                $validationRules["accounts.*.{$field}"] = $rule;
            }
            
            $request->validate($validationRules);

            $accounts = $request->input('accounts');
            $createdCount = 0;
            $skippedCount = 0;
            $skippedReasons = [];
            $createdAccounts = [];

            foreach ($accounts as $index => $accountData) {
                try {
                    // Validate account_type_name exists in database
                    if (isset($accountData['account_type_name']) && !empty($accountData['account_type_name'])) {
                        $accountType = \FireflyIII\Models\AccountType::where('name', $accountData['account_type_name'])
                            ->where('active', true)
                            ->first();
                        
                        if (!$accountType) {
                            $skippedCount++;
                            $accountName = $this->generateAccountName($accountData);
                            $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountName}'): Account type '{$accountData['account_type_name']}' does not exist in database.";
                            continue;
                        }
                    } else {
                        $skippedCount++;
                        $accountName = $this->generateAccountName($accountData);
                        $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountName}'): No account_type_name specified.";
                        continue;
                    }

                    // Add default currency if not present
                    if ((!isset($accountData['currency_id']) || $accountData['currency_id'] == 0) && !isset($accountData['currency_code'])) {
                        $primaryCurrency = app('amount')->getPrimaryCurrencyByUserGroup(auth()->user()->userGroup);
                        $accountData['currency_id'] = $primaryCurrency->id;
                    }

                    // Handle account holders - validation already ensures this is a non-empty array
                    $accountHolders = $accountData['account_holders'];
                    
                    // Validate that all account holders exist as financial entities
                    $accountHolderEntities = [];
                    $missingHolders = [];
                    
                    foreach ($accountHolders as $holderName) {
                        $holderEntity = $this->findExistingEntity($holderName);
                        if (!$holderEntity) {
                            $missingHolders[] = $holderName;
                        } else {
                            $accountHolderEntities[] = $holderEntity;
                        }
                    }
                    
                    if (!empty($missingHolders)) {
                        $skippedCount++;
                        $accountName = $this->generateAccountName($accountData);
                        $missingList = implode(', ', $missingHolders);
                        $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountName}'): Account holder(s) '{$missingList}' do not exist as financial entities. Please create the financial entities first.";
                        continue;
                    }
                    
                    // Set account holder IDs as array
                    $accountData['account_holder_ids'] = array_map(fn($entity) => $entity->id, $accountHolderEntities);
                    
                    // Store all account holders for entity role creation
                    $accountData['_account_holder_entities'] = $accountHolderEntities;

                    // Validate institution entity exists (don't create)
                    if (isset($accountData['institution']) && !empty($accountData['institution'])) {
                        $institutionName = $accountData['institution'];
                        $institutionEntity = $this->findExistingEntity($institutionName, 'institution');
                        if (!$institutionEntity) {
                            $skippedCount++;
                            $accountName = $this->generateAccountName($accountData);
                            $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountName}'): Institution '{$institutionName}' does not exist as a financial entity. Please create the financial entity first.";
                            continue;
                        }
                        $accountData['institution_id'] = $institutionEntity->id;
                    }

                    // Handle opening balance - both fields required or neither
                    $hasOpeningBalance = isset($accountData['opening_balance']) && !empty($accountData['opening_balance']);
                    $hasOpeningBalanceDate = isset($accountData['opening_balance_date']) && !empty($accountData['opening_balance_date']);
                    
                    Log::debug('Opening balance processing', [
                        'has_opening_balance' => $hasOpeningBalance,
                        'has_opening_balance_date' => $hasOpeningBalanceDate,
                        'opening_balance' => $accountData['opening_balance'] ?? 'NOT_SET',
                        'opening_balance_date' => $accountData['opening_balance_date'] ?? 'NOT_SET',
                        'account_name' => $accountData['name'] ?? 'NOT_SET'
                    ]);
                    
                    if ($hasOpeningBalance && $hasOpeningBalanceDate) {
                        // Both provided - convert date to Carbon
                        try {
                            $originalDate = $accountData['opening_balance_date'];
                            $accountData['opening_balance_date'] = \Carbon\Carbon::parse($accountData['opening_balance_date']);
                            Log::debug('Opening balance date converted to Carbon', [
                                'original_date' => $originalDate,
                                'converted_date' => $accountData['opening_balance_date']->toDateString(),
                                'account_name' => $accountData['name'] ?? 'NOT_SET'
                            ]);
                        } catch (\Exception $e) {
                            // If parsing fails, remove both fields
                            Log::debug('Opening balance date parsing failed, removing both fields', [
                                'error' => $e->getMessage(),
                                'account_name' => $accountData['name'] ?? 'NOT_SET'
                            ]);
                            unset($accountData['opening_balance']);
                            unset($accountData['opening_balance_date']);
                        }
                    } else {
                        // Either missing or both missing - remove both fields
                        Log::debug('Opening balance data incomplete, removing both fields', [
                            'account_name' => $accountData['name'] ?? 'NOT_SET'
                        ]);
                        unset($accountData['opening_balance']);
                        unset($accountData['opening_balance_date']);
                    }

                        // Use the AccountFactory to create the account (same as importFromJson)
                        $factory = app(\FireflyIII\Factory\AccountFactory::class);
                        $factory->setUser(auth()->user());
                        
                        // If no name is provided, let AccountFactory generate it
                        // If name is provided, use it (AccountFactory will use it if present)
                        $account = $factory->create($accountData);
                    
                    if (!$account) {
                        throw new \Exception('Failed to create account');
                    }

                    // Note: Entity roles are not automatically created - they must be set up separately if needed

                    $createdAccounts[] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'account_type_name' => $account->accountType->name,
                        'account_holders' => $account->account_holders,
                        'institution' => $account->institution,
                    ];

                    $createdCount++;

                } catch (\Exception $e) {
                    $skippedCount++;
                    $accountName = $this->generateAccountName($accountData);
                    $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountName}'): " . $e->getMessage();
                    Log::error('Failed to create account via API', [
                        'account_data' => $accountData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'message' => 'Account import completed',
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'created_accounts' => $createdAccounts,
                'skipped_reasons' => $skippedReasons,
            ]);

        } catch (\Exception $e) {
            Log::error('Account import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Account import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate account name from components for error messages and display.
     * Uses provided name if available, otherwise generates from institution, product_name, and account_holders.
     */
    private function generateAccountName(array $accountData): string
    {
        if (isset($accountData['name']) && !empty($accountData['name'])) {
            return $accountData['name'];
        }
        
        // Handle account_holders as array
        $accountHolders = $accountData['account_holders'] ?? ['Unknown'];
        $holdersString = is_array($accountHolders) ? implode(', ', $accountHolders) : $accountHolders;
        
        return ($accountData['institution'] ?? 'Unknown') . ' - ' . 
               ($accountData['product_name'] ?? 'Unknown') . ' (' . 
               $holdersString . ')';
    }

    /**
     * Find an existing entity without creating a new one.
     * Used for account holders that must already exist.
     * Searches across all entity types since account holders can be individuals, businesses, trusts, etc.
     */
    private function findExistingEntity(string $name, ?string $type = null): ?\FireflyIII\Models\FinancialEntity
    {
        $query = \FireflyIII\Models\FinancialEntity::where('name', $name)
            ->where('is_active', true);
            
        // If a specific type is provided, filter by it
        if ($type !== null) {
            $query->where('entity_type', $type);
        }
        
        return $query->first();
    }

    /**
     * Create or find an entity (account holder or institution).
     * This is a simplified version of the method from ImportController.
     * Used for institutions that can be created automatically.
     */
    private function createOrFindEntity(string $name, string $type): \FireflyIII\Models\FinancialEntity
    {
        // Try to find existing entity
        $entity = \FireflyIII\Models\FinancialEntity::where('name', $name)
            ->where('entity_type', $type)
            ->where('is_active', true)
            ->first();

        if ($entity) {
            return $entity;
        }

        // Create new entity
        $entity = \FireflyIII\Models\FinancialEntity::create([
            'name' => $name,
            'entity_type' => $type,
            'display_name' => $name,
            'is_active' => true,
        ]);

        return $entity;
    }

    /**
     * Create AccountEntityRole records for multiple account holders (joint accounts).
     * Assumes equal ownership for all holders.
     */
    private function createAccountEntityRoles(\FireflyIII\Models\Account $account, array $accountHolderEntities): void
    {
        $holderCount = count($accountHolderEntities);
        $ownershipPercentage = 100.0 / $holderCount; // Equal ownership
        
        foreach ($accountHolderEntities as $entity) {
            // Check if the entity role already exists
            $existingRole = \FireflyIII\Models\AccountEntityRole::where([
                'account_id' => $account->id,
                'entity_id' => $entity->id,
                'role_type' => \FireflyIII\Models\AccountEntityRole::ROLE_OWNER,
            ])->first();
            
            if (!$existingRole) {
                \FireflyIII\Models\AccountEntityRole::create([
                    'account_id' => $account->id,
                    'entity_id' => $entity->id,
                    'role_type' => \FireflyIII\Models\AccountEntityRole::ROLE_OWNER,
                    'percentage' => $ownershipPercentage,
                    'is_active' => true,
                    'role_metadata' => [
                        'created_via' => 'joint_account_import',
                        'ownership_type' => 'equal'
                    ]
                ]);
            }
        }
        
        Log::info('Created joint account entity roles', [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'holder_count' => $holderCount,
            'ownership_percentage' => $ownershipPercentage,
            'holders' => array_map(fn($e) => $e->name, $accountHolderEntities)
        ]);
    }

    /**
     * Import financial entities from JSON data
     * POST /api/v1/pfinance/import-entities
     */
    public function importEntities(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'entities' => 'required|array|min:1',
                'entities.*.name' => 'required|string|max:255',
                'entities.*.entity_type' => 'required|string|in:individual,business,trust,institution,other',
                'entities.*.display_name' => 'nullable|string|max:50',
                'entities.*.description' => 'nullable|string|max:1000',
                'entities.*.contact_info' => 'nullable|array',
                'entities.*.contact_info.email' => 'nullable|email|max:255',
                'entities.*.contact_info.phone' => 'nullable|string|max:50',
                'entities.*.contact_info.address' => 'nullable|string|max:500',
                'entities.*.contact_info.website' => 'nullable|url|max:255',
                'entities.*.metadata' => 'nullable|array',
                'entities.*.is_active' => 'nullable|boolean',
            ]);

            $entities = $request->input('entities');
            $createdCount = 0;
            $skippedCount = 0;
            $createdEntities = [];
            $skippedReasons = [];

            foreach ($entities as $index => $entityData) {
                try {
                    // Check if entity already exists
                    $existingEntity = \FireflyIII\Models\FinancialEntity::where('name', $entityData['name'])->first();
                    
                    if ($existingEntity) {
                        $skippedCount++;
                        $skippedReasons[] = "Entity " . ($index + 1) . " ('{$entityData['name']}'): Entity already exists (ID: {$existingEntity->id})";
                        continue;
                    }

                    // Set defaults
                    $entityData['display_name'] = $entityData['display_name'] ?? substr($entityData['name'], 0, 50);
                    $entityData['is_active'] = $entityData['is_active'] ?? true;
                    $entityData['user_group_id'] = auth()->user()->user_group_id;
                    $entityData['contact_info'] = $entityData['contact_info'] ?? [];
                    $entityData['metadata'] = $entityData['metadata'] ?? [];

                    // Create the entity
                    $entity = \FireflyIII\Models\FinancialEntity::create($entityData);

                    $createdEntities[] = [
                        'id' => $entity->id,
                        'name' => $entity->name,
                        'entity_type' => $entity->entity_type,
                        'display_name' => $entity->display_name,
                    ];

                    $createdCount++;

                } catch (\Exception $e) {
                    $skippedCount++;
                    $skippedReasons[] = "Entity " . ($index + 1) . " ('{$entityData['name']}'): " . $e->getMessage();
                    Log::error('Failed to create entity via API', [
                        'entity_data' => $entityData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'message' => 'Entity import completed',
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'created_entities' => $createdEntities,
                'skipped_reasons' => $skippedReasons,
            ]);

        } catch (\Exception $e) {
            Log::error('Entity import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Entity import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
