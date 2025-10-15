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
        // 1. Add account_type_id column to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable()->constrained('account_types');
        });

        // 2. Migrate existing category_id/behavior_id combinations to account_type_id
        $this->migrateToAccountTypes();

        // 3. Make account_type_id required and remove old columns
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable(false)->change();
            $table->dropForeign(['category_id']);
            $table->dropForeign(['behavior_id']);
            $table->dropColumn(['category_id', 'behavior_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add back category_id and behavior_id columns
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('account_categories');
            $table->foreignId('behavior_id')->nullable()->constrained('account_behaviors');
        });

        // 2. Migrate data back from account_type_id to category_id/behavior_id
        $this->migrateBackToCategoryBehavior();

        // 3. Make category_id and behavior_id required and remove account_type_id
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->foreignId('behavior_id')->nullable(false)->change();
            $table->dropForeign(['account_type_id']);
            $table->dropColumn('account_type_id');
        });
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