<?php

/**
 * DataController.php
 * Development controller for clearing financial data
 * 
 * WARNING: This controller is for development purposes only!
 * It will permanently delete all financial data from the database.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Dev;

use FireflyIII\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class DataController
 */
class DataController extends Controller
{
    /**
     * Clear all financial data for development purposes
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clearAllData(Request $request): JsonResponse
    {
        Log::warning('DEV: Clearing all financial data - this is irreversible!');
        
        // Only allow in development environment
        if (!app()->environment('local', 'development')) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in development environment'
            ], 403);
        }

        try {
            Log::warning('DEV: Clearing all financial data - this is irreversible!');
            
            // Call the PostgreSQL function
            $result = DB::selectOne('SELECT clear_all_financial_data() AS result');
            $data = json_decode($result->result, true);
            
            if ($data['success']) {
                Log::warning('DEV: All financial data cleared successfully', $data);
                return response()->json([
                    'success' => true,
                    'message' => 'All financial data cleared successfully',
                    'details' => $data
                ]);
            } else {
                Log::error('DEV: Failed to clear financial data', $data);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear financial data: ' . $data['message'],
                    'details' => $data
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('DEV: Exception while clearing financial data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Exception occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
