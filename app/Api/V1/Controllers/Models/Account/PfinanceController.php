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
        $accountData['account_holder'] = $account->accountHolder?->name ?? $account->account_holder;
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
            // Validate the request
            $request->validate([
                'accounts' => 'required|array|min:1',
                'accounts.*.name' => 'nullable|string|max:1024', // Name is auto-generated, not required
                'accounts.*.account_type_name' => 'required|string|max:1024',
                'accounts.*.account_holder' => 'required|string|max:255',
                'accounts.*.institution' => 'required|string|max:255', // Required for name generation
                'accounts.*.product_name' => 'required|string|max:255', // Required for name generation
            ]);

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
                            $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountData['name']}'): Account type '{$accountData['account_type_name']}' does not exist in database.";
                            continue;
                        }
                    } else {
                        $skippedCount++;
                        $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountData['name']}'): No account_type_name specified.";
                        continue;
                    }

                    // Add default currency if not present
                    if ((!isset($accountData['currency_id']) || $accountData['currency_id'] == 0) && !isset($accountData['currency_code'])) {
                        $primaryCurrency = app('amount')->getPrimaryCurrencyByUserGroup(auth()->user()->userGroup);
                        $accountData['currency_id'] = $primaryCurrency->id;
                    }

                    // Automatically create and link account holder entity
                    if (isset($accountData['account_holder']) && !empty($accountData['account_holder'])) {
                        $accountHolderName = $accountData['account_holder'];
                        $accountHolderEntity = $this->createOrFindEntity($accountHolderName, 'individual');
                        $accountData['account_holder_id'] = $accountHolderEntity->id;
                    } else {
                        $skippedCount++;
                        $skippedReasons[] = "Account " . ($index + 1) . " ('{$accountData['name']}'): No account holder specified.";
                        continue;
                    }

                    // Automatically create and link institution entity
                    if (isset($accountData['institution']) && !empty($accountData['institution'])) {
                        $institutionName = $accountData['institution'];
                        $institutionEntity = $this->createOrFindEntity($institutionName, 'institution');
                        $accountData['institution_id'] = $institutionEntity->id;
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

                    $createdAccounts[] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'account_type_name' => $account->accountType->name,
                        'account_holder' => $account->account_holder,
                        'institution' => $account->institution,
                    ];

                    $createdCount++;

                } catch (\Exception $e) {
                    $skippedCount++;
                    $accountName = $accountData['name'] ?? ($accountData['institution'] . ' - ' . $accountData['product_name'] . ' (' . $accountData['account_holder'] . ')');
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
     * Create or find an entity (account holder or institution).
     * This is a simplified version of the method from ImportController.
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
}
