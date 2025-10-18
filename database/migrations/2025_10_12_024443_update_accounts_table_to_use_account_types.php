<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration is no longer needed as we're using account_templates instead of account_types
        // The account_type_id column exists but references a non-existent table
        // The system now uses template_id which references account_templates
        return;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to rollback as this migration does nothing
        return;
    }

    /**
     * Migrate existing accounts from category_id/behavior_id to account_type_id
     */
    private function migrateToAccountTypes(): void
    {
        // Get all accounts with their current category and behavior
        $accounts = DB::table('accounts')
            ->select('id', 'category_id', 'behavior_id')
            ->get();

        foreach ($accounts as $account) {
            // Find the matching account type
            $accountType = DB::table('account_types')
                ->where('category_id', $account->category_id)
                ->where('behavior_id', $account->behavior_id)
                ->where('active', true)
                ->first();

            if ($accountType) {
                // Update the account with the account_type_id
                DB::table('accounts')
                    ->where('id', $account->id)
                    ->update(['account_type_id' => $accountType->id]);
            } else {
                // If no exact match, try to find a generic match
                $genericAccountType = $this->findGenericAccountType($account->category_id, $account->behavior_id);
                if ($genericAccountType) {
                    DB::table('accounts')
                        ->where('id', $account->id)
                        ->update(['account_type_id' => $genericAccountType->id]);
                } else {
                    // Fallback to "Asset account" if no match found
                    $fallbackType = DB::table('account_types')
                        ->where('name', 'Asset account')
                        ->first();
                    
                    if ($fallbackType) {
                        DB::table('accounts')
                            ->where('id', $account->id)
                            ->update(['account_type_id' => $fallbackType->id]);
                    }
                }
            }
        }
    }

    /**
     * Find a generic account type for the given category/behavior combination
     */
    private function findGenericAccountType(int $categoryId, int $behaviorId): ?object
    {
        $category = DB::table('account_categories')->where('id', $categoryId)->first();
        $behavior = DB::table('account_behaviors')->where('id', $behaviorId)->first();

        if (!$category || !$behavior) {
            return null;
        }

        // Map to generic account types
        $genericMappings = [
            'Asset' => [
                'Simple' => 'Asset account',
                'Container' => 'Brokerage account',
                'Security' => 'Brokerage account',
                'Cash' => 'Cash account'
            ],
            'Liability' => [
                'Simple' => 'Debt'
            ],
            'Expense' => [
                'Simple' => 'Expense account'
            ],
            'Revenue' => [
                'Simple' => 'Revenue account'
            ]
        ];

        $accountTypeName = $genericMappings[$category->name][$behavior->name] ?? null;
        
        if ($accountTypeName) {
            return DB::table('account_types')
                ->where('name', $accountTypeName)
                ->where('active', true)
                ->first();
        }

        return null;
    }

    /**
     * Migrate data back from account_type_id to category_id/behavior_id
     */
    private function migrateBackToCategoryBehavior(): void
    {
        $accounts = DB::table('accounts')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->select('accounts.id', 'account_types.category_id', 'account_types.behavior_id')
            ->get();

        foreach ($accounts as $account) {
            DB::table('accounts')
                ->where('id', $account->id)
                ->update([
                    'category_id' => $account->category_id,
                    'behavior_id' => $account->behavior_id
                ]);
        }
    }
};