<?php

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BulkUpdateController extends Controller
{
    /**
     * Bulk update account status (active/inactive)
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'changes' => 'required|array|min:1',
                'changes.*.id' => 'required|integer|exists:accounts,id',
                'changes.*.active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $changes = $request->input('changes');
            $updatedCount = 0;
            $errors = [];

            foreach ($changes as $change) {
                try {
                    $account = Account::where('id', $change['id'])
                        ->where('user_id', auth()->id())
                        ->first();

                    if (!$account) {
                        $errors[] = "Account ID {$change['id']} not found or not accessible";
                        continue;
                    }

                    $account->active = $change['active'];
                    $account->save();

                    $updatedCount++;

                    Log::info('Account status updated via bulk update', [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'new_status' => $change['active'] ? 'active' : 'inactive',
                        'user_id' => auth()->id()
                    ]);

                } catch (\Exception $e) {
                    $errors[] = "Failed to update account ID {$change['id']}: " . $e->getMessage();
                    Log::error('Failed to update account status in bulk update', [
                        'account_id' => $change['id'],
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id()
                    ]);
                }
            }

            if ($updatedCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully updated {$updatedCount} account(s)",
                    'updated_count' => $updatedCount,
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No accounts were updated',
                    'errors' => $errors
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Bulk account status update failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
