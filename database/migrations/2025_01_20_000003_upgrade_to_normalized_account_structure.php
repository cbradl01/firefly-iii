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
        // 1. Add new columns to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('account_categories');
            $table->foreignId('behavior_id')->nullable()->constrained('account_behaviors');
        });

        // 2. Migrate existing account_type_id to new structure
        $this->migrateAccountTypes();

        // 3. Make new columns required (after migration)
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->foreignId('behavior_id')->nullable(false)->change();
        });

        // 4. Remove old foreign key constraint
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['account_type_id']);
        });

        // 5. Drop old account_type_id column
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('account_type_id');
        });

        // 6. Drop the account_types table
        Schema::dropIfExists('account_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate account_types table
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
            
            $table->unique('name');
        });

        // 2. Add account_type_id column back to accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable()->constrained('account_types');
        });

        // 3. Migrate data back (this is complex and may not be perfect)
        $this->migrateBackToAccountTypes();

        // 4. Make account_type_id required
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable(false)->change();
        });

        // 5. Remove new columns
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['behavior_id']);
            $table->dropColumn(['category_id', 'behavior_id']);
        });
    }

    /**
     * Migrate existing account_type_id to new category_id and behavior_id structure
     */
    private function migrateAccountTypes(): void
    {
        // Get all accounts with their current account_type
        $accounts = DB::table('accounts')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->select('accounts.id', 'accounts.account_type_id', 'account_types.name')
            ->get();

        foreach ($accounts as $account) {
            // Map old account type names to new category and behavior
            $mapping = $this->getAccountTypeMapping($account->name);
            
            if ($mapping) {
                DB::table('accounts')
                    ->where('id', $account->id)
                    ->update([
                        'category_id' => $mapping['category_id'],
                        'behavior_id' => $mapping['behavior_id']
                    ]);
            }
        }
    }

    /**
     * Get the mapping from old account type to new category and behavior
     */
    private function getAccountTypeMapping(string $accountTypeName): ?array
    {
        // Get category and behavior IDs
        $categories = DB::table('account_categories')->pluck('id', 'name');
        $behaviors = DB::table('account_behaviors')->pluck('id', 'name');

        $mappings = [
            'Asset account' => [
                'category' => 'Asset',
                'behavior' => 'Simple'
            ],
            'Default account' => [
                'category' => 'Asset', 
                'behavior' => 'Simple'
            ],
            'Brokerage account' => [
                'category' => 'Asset',
                'behavior' => 'Container'
            ],
            'Cash account' => [
                'category' => 'Asset',
                'behavior' => 'Simple'
            ],
            'Credit card' => [
                'category' => 'Liability',
                'behavior' => 'Simple'
            ],
            'Debt' => [
                'category' => 'Liability',
                'behavior' => 'Simple'
            ],
            'Loan' => [
                'category' => 'Liability',
                'behavior' => 'Simple'
            ],
            'Mortgage' => [
                'category' => 'Liability',
                'behavior' => 'Simple'
            ],
            'Liability credit account' => [
                'category' => 'Liability',
                'behavior' => 'Simple'
            ],
            'Expense account' => [
                'category' => 'Expense',
                'behavior' => 'Simple'
            ],
            'Revenue account' => [
                'category' => 'Revenue',
                'behavior' => 'Simple'
            ],
            'Beneficiary account' => [
                'category' => 'Asset',
                'behavior' => 'Simple'
            ],
            'Import account' => [
                'category' => 'Asset',
                'behavior' => 'Simple'
            ],
            'Initial balance account' => [
                'category' => 'Asset',
                'behavior' => 'Simple'
            ],
            'Reconciliation account' => [
                'category' => 'Asset',
                'behavior' => 'Simple'
            ]
        ];

        if (!isset($mappings[$accountTypeName])) {
            // Default fallback
            return [
                'category_id' => $categories['Asset'] ?? 1,
                'behavior_id' => $behaviors['Simple'] ?? 1
            ];
        }

        $mapping = $mappings[$accountTypeName];
        
        return [
            'category_id' => $categories[$mapping['category']] ?? 1,
            'behavior_id' => $behaviors[$mapping['behavior']] ?? 1
        ];
    }

    /**
     * Migrate back to account_types (for rollback)
     */
    private function migrateBackToAccountTypes(): void
    {
        // This is complex and may not be perfect since we're losing information
        // We'll create a generic mapping back
        
        $accounts = DB::table('accounts')
            ->join('account_categories', 'accounts.category_id', '=', 'account_categories.id')
            ->join('account_behaviors', 'accounts.behavior_id', '=', 'account_behaviors.id')
            ->select('accounts.id', 'account_categories.name as category_name', 'account_behaviors.name as behavior_name')
            ->get();

        foreach ($accounts as $account) {
            // Create a simple mapping back to account types
            $accountTypeName = $this->getReverseMapping($account->category_name, $account->behavior_name);
            
            // Find or create the account type
            $accountType = DB::table('account_types')->where('name', $accountTypeName)->first();
            
            if (!$accountType) {
                $accountTypeId = DB::table('account_types')->insertGetId([
                    'name' => $accountTypeName,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $accountTypeId = $accountType->id;
            }

            DB::table('accounts')
                ->where('id', $account->id)
                ->update(['account_type_id' => $accountTypeId]);
        }
    }

    /**
     * Get reverse mapping from category/behavior back to account type name
     */
    private function getReverseMapping(string $categoryName, string $behaviorName): string
    {
        $mappings = [
            'Asset' => [
                'Simple' => 'Asset account',
                'Container' => 'Brokerage account',
                'Security' => 'Asset account',
                'Cash' => 'Cash account'
            ],
            'Liability' => [
                'Simple' => 'Debt',
                'Container' => 'Debt',
                'Security' => 'Debt',
                'Cash' => 'Debt'
            ],
            'Expense' => [
                'Simple' => 'Expense account',
                'Container' => 'Expense account',
                'Security' => 'Expense account',
                'Cash' => 'Expense account'
            ],
            'Revenue' => [
                'Simple' => 'Revenue account',
                'Container' => 'Revenue account',
                'Security' => 'Revenue account',
                'Cash' => 'Revenue account'
            ],
            'Equity' => [
                'Simple' => 'Asset account',
                'Container' => 'Asset account',
                'Security' => 'Asset account',
                'Cash' => 'Asset account'
            ]
        ];

        return $mappings[$categoryName][$behaviorName] ?? 'Asset account';
    }
};
