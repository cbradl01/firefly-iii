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
        // 1. Create account_categories table
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
            $table->timestamps();
        });

        // 2. Create account_behaviors table
        Schema::create('account_behaviors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
            $table->string('calculation_method', 50);
            $table->timestamps();
        });

        // 3. Create relationship_types table
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
            $table->json('metadata_schema')->nullable(); // JSON schema for validation
            $table->timestamps();
        });

        // 4. Create security_positions table
        Schema::create('security_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('security_type', 50); // 'stock', 'bond', 'mutual_fund', 'etf', 'crypto'
            $table->string('symbol', 20)->index();
            $table->string('name', 255); // Full name of the security
            $table->decimal('shares', 15, 6)->default(0);
            $table->decimal('cost_basis', 15, 2)->default(0);
            $table->decimal('current_price', 15, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->json('metadata')->nullable(); // Additional security-specific data
            $table->timestamps();
            
            $table->unique(['account_id', 'symbol']);
            $table->index(['symbol', 'security_type']);
        });

        // 5. Create position_allocations table
        Schema::create('position_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('security_positions');
            $table->foreignId('container_account_id')->constrained('accounts');
            $table->decimal('shares', 15, 6);
            $table->decimal('cost_basis', 15, 2);
            $table->decimal('allocation_percentage', 5, 2)->default(100.00);
            $table->timestamps();
            
            $table->unique(['position_id', 'container_account_id']);
            $table->index('container_account_id');
        });

        // 6. Create account_relationships table
        Schema::create('account_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_account_id')->constrained('accounts');
            $table->foreignId('child_account_id')->constrained('accounts');
            $table->foreignId('relationship_type_id')->constrained('relationship_types');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['parent_account_id', 'child_account_id', 'relationship_type_id']);
            $table->index(['parent_account_id', 'relationship_type_id']);
            $table->index(['child_account_id', 'relationship_type_id']);
        });

        // 7. Create account_meta table (replacing account_meta)
        Schema::dropIfExists('account_meta');
        Schema::create('account_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('name', 100);
            $table->text('data');
            $table->timestamps();
            
            $table->unique(['account_id', 'name']);
            $table->index('name');
        });

        // 8. Backup existing account_types data
        $existingAccountTypes = DB::table('account_types')->get()->toArray();
        
        // 9. Update accounts table to remove foreign key constraint first
        Schema::table('accounts', function (Blueprint $table) {
            // Drop old foreign key if it exists
            $table->dropForeign(['account_type_id']);
        });

        // 10. Drop old account_types table and create new one
        Schema::dropIfExists('account_types');
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('category_id')->constrained('account_categories');
            $table->foreignId('behavior_id')->constrained('account_behaviors');
            $table->text('description');
            $table->string('firefly_mapping', 50)->nullable(); // For Firefly III compatibility
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 11. Insert seed data for categories and behaviors
        DB::table('account_categories')->insert([
            ['name' => 'Asset', 'description' => 'Resources owned by the entity', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Liability', 'description' => 'Debts and obligations', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Expense', 'description' => 'Costs and expenditures', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Revenue', 'description' => 'Income and earnings', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Equity', 'description' => 'Ownership interest', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('account_behaviors')->insert([
            ['name' => 'Simple', 'description' => 'Standard balance calculation', 'calculation_method' => 'direct_balance', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Container', 'description' => 'Sum of contained accounts and positions', 'calculation_method' => 'sum_contained', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Security', 'description' => 'Shares × current price', 'calculation_method' => 'shares_times_price', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cash', 'description' => 'Direct balance with currency', 'calculation_method' => 'direct_balance', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 12. Map existing account types to new structure
        $assetCategoryId = DB::table('account_categories')->where('name', 'Asset')->first()->id;
        $liabilityCategoryId = DB::table('account_categories')->where('name', 'Liability')->first()->id;
        $expenseCategoryId = DB::table('account_categories')->where('name', 'Expense')->first()->id;
        $revenueCategoryId = DB::table('account_categories')->where('name', 'Revenue')->first()->id;

        $simpleBehaviorId = DB::table('account_behaviors')->where('name', 'Simple')->first()->id;
        $containerBehaviorId = DB::table('account_behaviors')->where('name', 'Container')->first()->id;
        $securityBehaviorId = DB::table('account_behaviors')->where('name', 'Security')->first()->id;
        $cashBehaviorId = DB::table('account_behaviors')->where('name', 'Cash')->first()->id;

        // Create mapping for existing account types
        $typeMapping = [
            'Asset account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Asset account'],
            'Default account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Default account'],
            'Cash account' => ['category' => $assetCategoryId, 'behavior' => $cashBehaviorId, 'firefly_mapping' => 'Cash account'],
            'Expense account' => ['category' => $expenseCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Expense account'],
            'Revenue account' => ['category' => $revenueCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Revenue account'],
            'Initial balance account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Initial balance account'],
            'Beneficiary account' => ['category' => $expenseCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Beneficiary account'],
            'Import account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Import account'],
            'Loan' => ['category' => $liabilityCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Loan'],
            'Reconciliation account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Reconciliation account'],
            'Debt' => ['category' => $liabilityCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Debt'],
            'Mortgage' => ['category' => $liabilityCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Mortgage'],
            'Liability credit account' => ['category' => $liabilityCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Liability credit account'],
            'Holding account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Holding account'],
            'Brokerage account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Brokerage account'],
            'Credit card' => ['category' => $liabilityCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Credit card'],
            'Stock market account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Stock market account'],
            'Education Account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Asset account'],
            'Health Account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Asset account'],
            'Private Equity' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Asset account'],
            // Investment-focused account types
            'Venture Capital Account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Asset account'],
            'Private Equity Account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Asset account'],
            'Alternative Investment Account' => ['category' => $assetCategoryId, 'behavior' => $containerBehaviorId, 'firefly_mapping' => 'Asset account'],
            'E-commerce Account' => ['category' => $assetCategoryId, 'behavior' => $simpleBehaviorId, 'firefly_mapping' => 'Asset account'],
        ];

        // 13. Insert new account types based on existing data
        $newTypeIds = [];
        foreach ($existingAccountTypes as $existingType) {
            $mapping = $typeMapping[$existingType->type] ?? [
                'category' => $assetCategoryId, 
                'behavior' => $simpleBehaviorId, 
                'firefly_mapping' => $existingType->type
            ];
            
            $newId = DB::table('account_types')->insertGetId([
                'name' => $existingType->type,
                'category_id' => $mapping['category'],
                'behavior_id' => $mapping['behavior'],
                'description' => $existingType->type . ' account type',
                'firefly_mapping' => $mapping['firefly_mapping'],
                'active' => true,
                'created_at' => $existingType->created_at,
                'updated_at' => $existingType->updated_at,
            ]);
            
            $newTypeIds[$existingType->id] = $newId;
        }

        // 13.5. Create new account types that don't exist in existing data
        $newAccountTypes = [
            'Education Account' => 'Education-related accounts (Coverdell, 529, UTMA/UGMA)',
            'Health Account' => 'Health-related accounts (HSA, FSA, HRA)',
            'Private Equity' => 'Private equity investments and funds',
            // Investment-focused account types
            'Venture Capital Account' => 'Fund-based private investments (rolling funds, venture funds, syndicates)',
            'Private Equity Account' => 'Direct private company investments (SAFEs, direct equity)',
            'Alternative Investment Account' => 'Mixed alternative investments (venture capital, private equity, real estate)',
            'E-commerce Account' => 'Online storefronts and e-commerce platforms (Facebook shops, Etsy, Shopify, etc.)',
        ];

        foreach ($newAccountTypes as $typeName => $description) {
            // Check if this account type already exists
            $existingType = DB::table('account_types')->where('name', $typeName)->first();
            if (!$existingType) {
                $mapping = $typeMapping[$typeName];
                DB::table('account_types')->insert([
                    'name' => $typeName,
                    'category_id' => $mapping['category'],
                    'behavior_id' => $mapping['behavior'],
                    'description' => $description,
                    'firefly_mapping' => $mapping['firefly_mapping'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 14. Update accounts table to use new account_type_ids
        foreach ($newTypeIds as $oldId => $newId) {
            DB::table('accounts')
                ->where('account_type_id', $oldId)
                ->update(['account_type_id' => $newId]);
        }

        // 15. Re-add foreign key constraint to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('account_type_id')->references('id')->on('account_types');
        });

        // 16. Insert relationship types
        DB::table('relationship_types')->insert([
            ['name' => 'contains', 'description' => 'Account contains another account', 'metadata_schema' => '{"component_type": "string"}', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'holds_security', 'description' => 'Account holds a security position', 'metadata_schema' => '{"symbol": "string", "shares": "number"}', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'linked_to', 'description' => 'Account linked to another account', 'metadata_schema' => '{"link_type": "string"}', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order
        Schema::dropIfExists('account_meta');
        Schema::dropIfExists('account_relationships');
        Schema::dropIfExists('position_allocations');
        Schema::dropIfExists('security_positions');
        Schema::dropIfExists('relationship_types');
        Schema::dropIfExists('account_types');
        Schema::dropIfExists('account_behaviors');
        Schema::dropIfExists('account_categories');
    }
};
