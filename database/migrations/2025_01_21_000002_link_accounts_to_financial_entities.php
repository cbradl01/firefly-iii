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
        // Step 1: Add foreign key columns to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_holder_id')->nullable()->constrained('financial_entities');
            $table->foreignId('institution_id')->nullable()->constrained('financial_entities');
        });

        // Step 2: Create missing financial entities for institutions
        $this->createInstitutionEntities();

        // Step 3: Link existing account_holder data to financial entities
        $this->linkAccountHolders();

        // Step 4: Link existing institution data to financial entities
        $this->linkInstitutions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['account_holder_id']);
            $table->dropForeign(['institution_id']);
            $table->dropColumn(['account_holder_id', 'institution_id']);
        });
    }

    /**
     * Create financial entities for institutions that don't exist
     */
    private function createInstitutionEntities(): void
    {
        $institutions = [
            'Bank of America' => 'bank',
            'Venmo' => 'fintech',
            'Merrill Lynch' => 'bank',
            'Shopify' => 'fintech',
            'Chase' => 'bank',
            'American Express' => 'bank',
            'Square' => 'fintech',
            'PayPal' => 'fintech',
            'Charles Schwab' => 'brokerage',
            'Facebook' => 'fintech',
            'USAA' => 'bank',
            'Etsy' => 'fintech',
            'SHH' => 'other',
        ];

        foreach ($institutions as $name => $type) {
            // Check if institution already exists
            $existing = DB::table('financial_entities')
                ->where('name', $name)
                ->where('entity_type', 'institution')
                ->first();

            if (!$existing) {
                DB::table('financial_entities')->insert([
                    'name' => $name,
                    'entity_type' => 'institution',
                    'display_name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Link account_holder data to financial entities
     */
    private function linkAccountHolders(): void
    {
        $accountHolders = [
            'CRB Trust' => 'trust',
            'Kaitlyn' => 'individual',
            'MD' => 'business',
        ];

        foreach ($accountHolders as $name => $type) {
            // Find or create the financial entity
            $entity = DB::table('financial_entities')
                ->where('name', $name)
                ->where('entity_type', $type)
                ->first();

            if (!$entity) {
                $entityId = DB::table('financial_entities')->insertGetId([
                    'name' => $name,
                    'entity_type' => $type,
                    'display_name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $entityId = $entity->id;
            }

            // Update accounts with this account_holder
            DB::table('accounts')
                ->where('account_holder', $name)
                ->update(['account_holder_id' => $entityId]);
        }
    }

    /**
     * Link institution data to financial entities
     */
    private function linkInstitutions(): void
    {
        $institutions = [
            'Bank of America',
            'Venmo',
            'Merrill Lynch',
            'Shopify',
            'Chase',
            'American Express',
            'Square',
            'PayPal',
            'Charles Schwab',
            'Facebook',
            'USAA',
            'Etsy',
            'SHH',
        ];

        foreach ($institutions as $institutionName) {
            // Find the financial entity for this institution
            $entity = DB::table('financial_entities')
                ->where('name', $institutionName)
                ->where('entity_type', 'institution')
                ->first();

            if ($entity) {
                // Update accounts with this institution
                DB::table('accounts')
                    ->where('institution', $institutionName)
                    ->update(['institution_id' => $entity->id]);
            }
        }
    }
};

