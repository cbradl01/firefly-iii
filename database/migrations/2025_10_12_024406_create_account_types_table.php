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
        // Create account_types table
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('category_id')->constrained('account_categories');
            $table->foreignId('behavior_id')->constrained('account_behaviors');
            $table->text('description')->nullable();
            $table->json('metadata_schema')->nullable(); // JSON schema for account metadata
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'active']);
            $table->index(['behavior_id', 'active']);
        });

        // Seed account types
        $this->seedAccountTypes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }

    /**
     * Seed account types based on current database state
     */
    private function seedAccountTypes(): void
    {
        // Get category and behavior IDs
        $categories = DB::table('account_categories')->pluck('id', 'name');
        $behaviors = DB::table('account_behaviors')->pluck('id', 'name');

        $accountTypes = [
            // Asset account types
            [
                'name' => 'cash',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Cash'],
                'description' => 'Cash Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['currency_code', 'location']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'checking_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Checking Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'routing_number', 'overdraft_protection']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'savings_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Savings Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'routing_number', 'interest_rate']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'investment_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Investment Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'brokerage_firm', 'beneficiaries']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'cryptocurrency',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Cryptocurrency Wallet',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['wallet_address', 'currency_code']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'digital_wallet',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Digital Wallet (PayPal, Venmo, etc.)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'currency_code']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Payment Processor',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Payment processor account (Square, Stripe, etc.)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'processor_name']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Retirement Account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Retirement account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Liability account types
            [
                'name' => 'Credit card',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Credit card account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['liability_direction'],
                    'optional_fields' => ['account_number', 'credit_limit', 'interest_rate', 'payment_due_date']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Debt',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'General debt account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['liability_direction'],
                    'optional_fields' => ['interest', 'interest_period', 'liability_type']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Loan',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['liability_direction'],
                    'optional_fields' => ['interest', 'interest_period', 'liability_type', 'loan_amount']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Expense account types
            [
                'name' => 'Expense account',
                'category_id' => $categories['Expense'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'General expense account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['expense_category']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Revenue account types
            [
                'name' => 'Revenue account',
                'category_id' => $categories['Revenue'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'General revenue account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['revenue_source']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('account_types')->insert($accountTypes);
    }
};