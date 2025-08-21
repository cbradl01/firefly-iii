<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Account\PfinanceRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Api\ApiResponse;
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
            
            $response = Http::post($this->pfinanceServiceUrl . '/api/v1/accounts/consolidate-transactions/' . $accountId);
            
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
}
