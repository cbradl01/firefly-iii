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
        // Create account_templates table
        Schema::create('account_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('account_type_id')->constrained('account_types');
            $table->text('description')->nullable();
            $table->json('metadata_preset')->nullable(); // JSON preset for common metadata
            $table->json('suggested_fields')->nullable(); // JSON array of suggested field names
            $table->boolean('is_system_template')->default(false); // System vs user-created
            $table->foreignId('created_by_user_id')->nullable()->constrained('users'); // null for system templates
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['account_type_id', 'active']);
            $table->index(['is_system_template', 'active']);
        });

        // Seed common templates
        $this->seedAccountTemplates();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_templates');
    }

    /**
     * Seed common account templates
     */
    private function seedAccountTemplates(): void
    {
        // Get account type IDs
        $accountTypes = DB::table('account_types')->pluck('id', 'name');

        $templates = [
            // Asset Account Templates
            [
                'name' => 'Personal Checking',
                'account_type_id' => $accountTypes['Checking Account'],
                'description' => 'Personal checking account for daily transactions',
                'metadata_preset' => json_encode([
                    'account_role' => 'defaultAsset',
                    'ownership' => 'individual'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'routing_number', 'institution', 'overdraft_protection'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Business Checking',
                'account_type_id' => $accountTypes['Business Checking'],
                'description' => 'Business checking account',
                'metadata_preset' => json_encode([
                    'account_role' => 'defaultAsset',
                    'ownership' => 'business'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'routing_number', 'business_name', 'business_type'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'High-Yield Savings',
                'account_type_id' => $accountTypes['Savings Account'],
                'description' => 'High-yield savings account',
                'metadata_preset' => json_encode([
                    'account_role' => 'savingAsset',
                    'ownership' => 'individual'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'routing_number', 'interest_rate', 'minimum_balance'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Emergency Fund',
                'account_type_id' => $accountTypes['Savings Account'],
                'description' => 'Emergency fund savings account',
                'metadata_preset' => json_encode([
                    'account_role' => 'savingAsset',
                    'purpose' => 'emergency_fund'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'routing_number', 'target_amount', 'current_balance'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Individual Brokerage',
                'account_type_id' => $accountTypes['Individual Brokerage'],
                'description' => 'Individual investment brokerage account',
                'metadata_preset' => json_encode([
                    'account_role' => 'brokerageAsset',
                    'ownership' => 'individual'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'brokerage_firm', 'beneficiaries', 'investment_style'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Joint Brokerage',
                'account_type_id' => $accountTypes['Joint Brokerage'],
                'description' => 'Joint investment brokerage account',
                'metadata_preset' => json_encode([
                    'account_role' => 'brokerageAsset',
                    'ownership' => 'joint'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'brokerage_firm', 'joint_owners', 'beneficiaries'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Roth IRA',
                'account_type_id' => $accountTypes['Roth IRA'],
                'description' => 'Roth Individual Retirement Account',
                'metadata_preset' => json_encode([
                    'account_role' => 'brokerageAsset',
                    'account_type' => 'roth_ira'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'brokerage_firm', 'beneficiaries', 'contribution_limit', 'current_contribution'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Traditional IRA',
                'account_type_id' => $accountTypes['Traditional IRA'],
                'description' => 'Traditional Individual Retirement Account',
                'metadata_preset' => json_encode([
                    'account_role' => 'brokerageAsset',
                    'account_type' => 'traditional_ira'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'brokerage_firm', 'beneficiaries', 'contribution_limit', 'current_contribution'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '401(k)',
                'account_type_id' => $accountTypes['401(k)'],
                'description' => 'Employer-sponsored 401(k) retirement account',
                'metadata_preset' => json_encode([
                    'account_role' => 'brokerageAsset',
                    'account_type' => '401k'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'employer_id', 'employer_name', 'beneficiaries', 'contribution_limit', 'employer_match'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Health Savings Account',
                'account_type_id' => $accountTypes['Health Savings Account (HSA)'],
                'description' => 'Health Savings Account for medical expenses',
                'metadata_preset' => json_encode([
                    'account_role' => 'savingAsset',
                    'account_type' => 'hsa'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'contribution_limit', 'current_contribution', 'employer_contribution'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Cash Wallet',
                'account_type_id' => $accountTypes['Cash account'],
                'description' => 'Physical cash wallet',
                'metadata_preset' => json_encode([
                    'account_role' => 'cashWalletAsset',
                    'ownership' => 'individual'
                ]),
                'suggested_fields' => json_encode([
                    'currency_code', 'location'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Cryptocurrency Wallet',
                'account_type_id' => $accountTypes['Cryptocurrency'],
                'description' => 'Cryptocurrency wallet',
                'metadata_preset' => json_encode([
                    'account_role' => 'defaultAsset',
                    'asset_type' => 'cryptocurrency'
                ]),
                'suggested_fields' => json_encode([
                    'wallet_address', 'currency_code', 'wallet_type', 'exchange'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'PayPal Account',
                'account_type_id' => $accountTypes['Digital Wallet'],
                'description' => 'PayPal digital wallet account',
                'metadata_preset' => json_encode([
                    'account_role' => 'defaultAsset',
                    'wallet_provider' => 'paypal'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'email', 'currency_code'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Venmo Account',
                'account_type_id' => $accountTypes['Digital Wallet'],
                'description' => 'Venmo digital wallet account',
                'metadata_preset' => json_encode([
                    'account_role' => 'defaultAsset',
                    'wallet_provider' => 'venmo'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'username', 'currency_code'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Liability Account Templates
            [
                'name' => 'Personal Credit Card',
                'account_type_id' => $accountTypes['Credit card'],
                'description' => 'Personal credit card account',
                'metadata_preset' => json_encode([
                    'liability_direction' => 'credit',
                    'ownership' => 'individual'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'credit_limit', 'interest_rate', 'payment_due_date'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Business Credit Card',
                'account_type_id' => $accountTypes['Credit card'],
                'description' => 'Business credit card account',
                'metadata_preset' => json_encode([
                    'liability_direction' => 'credit',
                    'ownership' => 'business'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'credit_limit', 'interest_rate', 'business_name'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Auto Loan',
                'account_type_id' => $accountTypes['Auto Loan'],
                'description' => 'Automobile loan account',
                'metadata_preset' => json_encode([
                    'liability_direction' => 'debit',
                    'liability_type' => 'auto_loan'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'loan_amount', 'interest_rate', 'vehicle_info', 'lender_name'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Home Mortgage',
                'account_type_id' => $accountTypes['Mortgage'],
                'description' => 'Home mortgage loan account',
                'metadata_preset' => json_encode([
                    'liability_direction' => 'debit',
                    'liability_type' => 'mortgage'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'loan_amount', 'interest_rate', 'property_address', 'lender_name'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Student Loan',
                'account_type_id' => $accountTypes['Personal Loans'],
                'description' => 'Student loan account',
                'metadata_preset' => json_encode([
                    'liability_direction' => 'debit',
                    'liability_type' => 'student_loan'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'loan_amount', 'interest_rate', 'lender_name', 'school_name'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Personal Loan',
                'account_type_id' => $accountTypes['Personal Loans'],
                'description' => 'Personal loan account',
                'metadata_preset' => json_encode([
                    'liability_direction' => 'debit',
                    'liability_type' => 'personal_loan'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'loan_amount', 'interest_rate', 'lender_name', 'loan_purpose'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // International Templates
            [
                'name' => 'UK ISA Account',
                'account_type_id' => $accountTypes['Savings Account'],
                'description' => 'UK Individual Savings Account',
                'metadata_preset' => json_encode([
                    'account_role' => 'savingAsset',
                    'country' => 'UK',
                    'account_type' => 'isa'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'isa_type', 'contribution_limit', 'current_contribution'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Canadian TFSA',
                'account_type_id' => $accountTypes['Savings Account'],
                'description' => 'Canadian Tax-Free Savings Account',
                'metadata_preset' => json_encode([
                    'account_role' => 'savingAsset',
                    'country' => 'Canada',
                    'account_type' => 'tfsa'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'contribution_limit', 'current_contribution'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Australian Superannuation',
                'account_type_id' => $accountTypes['401(k)'],
                'description' => 'Australian Superannuation retirement account',
                'metadata_preset' => json_encode([
                    'account_role' => 'brokerageAsset',
                    'country' => 'Australia',
                    'account_type' => 'superannuation'
                ]),
                'suggested_fields' => json_encode([
                    'account_number', 'employer_name', 'contribution_rate', 'employer_contribution'
                ]),
                'is_system_template' => true,
                'created_by_user_id' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('account_templates')->insert($templates);
    }
};