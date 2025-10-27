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
        // First, convert existing string data to array format
        $this->convertExistingAccountHolderData();
        
        // Then change the column type to array using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder TYPE json USING account_holder::json');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder DROP NOT NULL');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder DROP DEFAULT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert array data back to string format
        $this->convertArrayDataToString();
        
        // Change the column type back to string using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder TYPE varchar(255) USING account_holder::text');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder DROP NOT NULL');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder DROP DEFAULT');
    }

    /**
     * Convert existing string account_holder data to array format
     */
    private function convertExistingAccountHolderData(): void
    {
        $accounts = DB::table('accounts')
            ->whereNotNull('account_holder')
            ->where('account_holder', '!=', '')
            ->get();

        foreach ($accounts as $account) {
            $accountHolder = $account->account_holder;
            
            // If it's already an array (JSON), skip
            if ($this->isJsonArray($accountHolder)) {
                continue;
            }
            
            // Convert string to single-element array (simplified logic)
            $accountHolderArray = [$accountHolder];
            
            DB::table('accounts')
                ->where('id', $account->id)
                ->update([
                    'account_holder' => json_encode($accountHolderArray),
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Convert array account_holder data back to string format
     */
    private function convertArrayDataToString(): void
    {
        $accounts = DB::table('accounts')
            ->whereNotNull('account_holder')
            ->where('account_holder', '!=', '')
            ->get();

        foreach ($accounts as $account) {
            $accountHolder = $account->account_holder;
            
            // If it's a JSON array, convert to comma-separated string
            if ($this->isJsonArray($accountHolder)) {
                $accountHolderArray = json_decode($accountHolder, true);
                if (is_array($accountHolderArray) && !empty($accountHolderArray)) {
                    $accountHolderString = implode(', ', $accountHolderArray);
                    
                    DB::table('accounts')
                        ->where('id', $account->id)
                        ->update([
                            'account_holder' => $accountHolderString,
                            'updated_at' => now()
                        ]);
                }
            }
        }
    }

    /**
     * Check if a string is a valid JSON array
     */
    private function isJsonArray(string $string): bool
    {
        $decoded = json_decode($string, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }
};
