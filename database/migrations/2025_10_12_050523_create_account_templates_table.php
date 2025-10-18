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
            $table->string('name', 100)->unique(); // Stable identifier (never changes)
            $table->string('label', 100); // Display name (can change)
            $table->string('template_name', 255)->unique(); // Template name for display
            $table->foreignId('category_id')->constrained('account_categories');
            $table->foreignId('behavior_id')->constrained('account_behaviors');
            $table->text('description')->nullable();
            $table->json('metadata_schema')->nullable(); // JSON schema for account metadata and field requirements
            $table->boolean('is_system_template')->default(false); // System vs user-created
            $table->foreignId('created_by_user_id')->nullable()->constrained('users'); // null for system templates
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'behavior_id', 'active']);
            $table->index(['is_system_template', 'active']);
            $table->index(['name', 'active']);
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
        // Get category and behavior IDs
        $categories = DB::table('account_categories')->pluck('id', 'name');
        $behaviors = DB::table('account_behaviors')->pluck('id', 'name');

        $templates = [
            // Asset Account Templates - Simple Behavior
            [
                'name' => 'checking_account',
                'label' => 'Checking Account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Checking account for daily transactions (personal, business, trust, etc.)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_holder_type', 'overdraft_protection', 'institution'],
                    'optional_fields' => [
                        'account_number', 
                        'routing_number', 
                        'authorized_signers', 
                        'signature_authority', 
                        'required_documents',
                        'interest_rate',
                        'minimum_balance',
                        'fee_structure'
                    ]
                ]),
            ],
            [
                'name' => 'savings_account',
                'label' => 'Savings Account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Savings account',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_holder_type', 'overdraft_protection', 'institution'],
                    'optional_fields' => [
                        'account_number', 
                        'routing_number', 
                        'authorized_signers', 
                        'signature_authority', 
                        'required_documents',
                        'interest_rate',
                        'minimum_balance',
                        'fee_structure'
                    ]
                ]),
            ],
            [
                'name' => 'emergency_fund',
                'label' => 'Emergency Fund',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Emergency fund savings account',
                'metadata_schema' => json_encode([
                    'account_fields' => [
                        'account_number' => ['required' => false, 'default' => ''],
                        'routing_number' => ['required' => false, 'default' => ''],
                        'target_amount' => ['required' => false, 'default' => ''],
                        'current_balance' => ['required' => false, 'default' => '']
                    ]
                ]),

            ],
            [
                'name' => 'Health Savings Account',
                'template_name' => 'health_savings_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Health Savings Accounts (HSAs) are tax-advantaged savings accounts for qualified medical expenses.',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_holder_type', 'overdraft_protection', 'institution'],
                    'optional_fields' => [
                        'account_number', 
                        'routing_number', 
                        'authorized_signers', 
                        'signature_authority', 
                        'required_documents',
                        'interest_rate',
                        'minimum_balance',
                        'fee_structure',
                        'contribution_limit',
                        'current_contribution',
                        'employer_contribution'
                    ]
                ]),

            ],

            // Asset Account Templates - Container Behavior
            [
                'name' => 'Brokerage Account',
                'template_name' => 'brokerage_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Investment brokerage account (individual, joint, business, trust, etc.)',
                'metadata_schema' => json_encode([
                    'required_fields' => ['account_holder_type', 'overdraft_protection', 'institution'],
                    'optional_fields' => [
                        'account_number', 
                        'brokerage_firm', 
                        'authorized_signers', 
                        'signature_authority', 
                        'beneficiaries',
                        'investment_style',
                        'interest_rate',
                        'minimum_balance',
                        'fee_structure'
                    ]
                ]),

            ],
            [
                'name' => 'Roth IRA',
                'template_name' => 'roth_ira',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Roth Individual Retirement Account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'brokerage_firm', 'beneficiaries', 'contribution_limit', 'current_contribution']
                ]),

            ],
            [
                'name' => 'Traditional IRA',
                'template_name' => 'traditional_ira',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Traditional Individual Retirement Account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'brokerage_firm', 'beneficiaries', 'contribution_limit', 'current_contribution']
                ]),

            ],
            [
                'name' => '401(k)',
                'template_name' => '401k',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Employer-sponsored 401(k) retirement account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'employer_id', 'employer_name', 'beneficiaries', 'contribution_limit', 'employer_match']
                ]),

            ],
            [
                'name' => 'Cryptocurrency Wallet',
                'template_name' => 'cryptocurrency_wallet',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Container'],
                'description' => 'Cryptocurrency wallet',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['wallet_address', 'currency_code', 'wallet_type', 'exchange']
                ]),

            ],

            // Asset Account Templates - Cash Behavior
            [
                'name' => 'Cash Wallet',
                'template_name' => 'cash_wallet',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Cash'],
                'description' => 'Physical cash wallet',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['currency_code', 'location']
                ]),

            ],

            // Digital Payment Platforms - Simple Behavior
            [
                'name' => 'PayPal Account',
                'template_name' => 'paypal_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'PayPal digital wallet account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'email', 'currency_code']
                ]),

            ],
            [
                'name' => 'Venmo Account',
                'template_name' => 'venmo_account',
                'category_id' => $categories['Asset'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Venmo digital wallet account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'username', 'currency_code']
                ]),

            ],

            // Liability Account Templates - Simple Behavior
            [
                'name' => 'Personal Credit Card',
                'template_name' => 'personal_credit_card',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Personal credit card account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'credit_limit', 'interest_rate', 'payment_due_date']
                ]),

            ],
            [
                'name' => 'Business Credit Card',
                'template_name' => 'business_credit_card',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Business credit card account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'credit_limit', 'interest_rate', 'business_name']
                ]),

            ],
            [
                'name' => 'Auto Loan',
                'template_name' => 'auto_loan',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Automobile loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'loan_amount', 'interest_rate', 'vehicle_info', 'lender_name']
                ]),

            ],
            [
                'name' => 'Home Mortgage',
                'template_name' => 'home_mortgage',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Home mortgage loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'loan_amount', 'interest_rate', 'property_address', 'lender_name']
                ]),

            ],
            [
                'name' => 'Student Loan',
                'template_name' => 'student_loan',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Student loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'loan_amount', 'interest_rate', 'lender_name', 'school_name']
                ]),

            ],
            [
                'name' => 'Personal Loan',
                'template_name' => 'personal_loan',
                'category_id' => $categories['Liability'],
                'behavior_id' => $behaviors['Simple'],
                'description' => 'Personal loan account',
                'metadata_schema' => json_encode([
                    'required_fields' => [],
                    'optional_fields' => ['account_number', 'loan_amount', 'interest_rate', 'lender_name', 'loan_purpose']
                ]),

            ]
        ];

        foreach ($templates as $template) {
            $template['is_system_template'] = true;
            $template['created_by_user_id'] = null;
            $template['active'] = true;
            $template['created_at'] = now();
            $template['updated_at'] = now();
        }
        
        DB::table('account_templates')->insert($templates);
    }
};