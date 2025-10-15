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
            $table->json('metadata_schema')->nullable(); // JSON schema for type-specific fields
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'behavior_id']);
        });

        // Insert common account types
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
     * Seed common account types
     */
    private function seedAccountTypes(): void
    {
        // Get category and behavior IDs
        $categories = DB::table('account_categories')->pluck('id', 'name');
        $behaviors = DB::table('account_behaviors')->pluck('id', 'name');

        $accountTypes = [
            // Asset account types
            [
                'name' => 'Asset account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'General asset account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'routing_number', 'iban']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Default account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Default asset account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'routing_number', 'iban']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Checking Account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Checking account for daily transactions',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'routing_number', 'iban']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Savings Account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Savings account for storing money',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'routing_number', 'iban']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Cash account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Cash'],
                'description' => 'Physical cash account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['currency_code']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Brokerage account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'General brokerage account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Individual Brokerage',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Individual brokerage account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Joint Brokerage',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Joint brokerage account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Roth IRA',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Roth Individual Retirement Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Traditional IRA',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Traditional Individual Retirement Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '401(k)',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => '401(k) retirement account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries', 'employer_id', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Solo 401(k)',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Solo 401(k) retirement account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Health Savings Account (HSA)',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Health Savings Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Cryptocurrency',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Cryptocurrency wallet',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['wallet_address', 'currency_code']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Digital Wallet',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Digital wallet (PayPal, Venmo, etc.)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'currency_code']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Private Equity',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Private equity investment',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['investment_amount', 'company_name']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Bonds',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Bond investment',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'maturity_date', 'interest_rate']
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
                    'optional_fields' => ['account_number', 'credit_limit', 'interest_rate']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Credit Card Asset',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Credit card treated as asset (money owed to you)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['cc_monthly_payment_date', 'cc_type']
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
            [
                'name' => 'Auto Loan',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Auto loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['liability_direction'],
                    'optional_fields' => ['interest', 'interest_period', 'liability_type', 'vehicle_info']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Mortgage',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Mortgage account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['liability_direction'],
                    'optional_fields' => ['interest', 'interest_period', 'liability_type', 'property_address']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Personal Loans',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Personal loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['liability_direction'],
                    'optional_fields' => ['interest', 'interest_period', 'liability_type', 'lender_name']
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
            ],

            // Special account types
            [
                'name' => 'Beneficiary account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Beneficiary account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['beneficiaries']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Import account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Import account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => []
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Initial balance account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Initial balance account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => []
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Reconciliation account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Reconciliation account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => []
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Custodial Account (UTMA/UGMA)',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Custodial account for minors',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'custodian_name', 'beneficiaries']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Coverdell ESA',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Coverdell Education Savings Account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'beneficiaries', 'contribution_limit']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Business Checking',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Business checking account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'routing_number', 'business_name']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Payment Processor',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Payment processor account (Square, Stripe, etc.)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_role'],
                    'optional_fields' => ['account_number', 'processor_name']
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('account_types')->insert($accountTypes);
    }
};