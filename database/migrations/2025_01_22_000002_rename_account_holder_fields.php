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
        // Rename the columns (data should already be in correct format from previous migration)
        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('account_holder', 'account_holders');
            $table->renameColumn('account_holder_id', 'account_holder_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert data back to old format
        $this->convertDataBack();
        
        // Rename columns back
        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('account_holders', 'account_holder');
            $table->renameColumn('account_holder_ids', 'account_holder_id');
        });
    }

    /**
     * Convert existing data to new format
     */
    private function convertExistingData(): void
    {
        $accounts = DB::table('accounts')
            ->whereNotNull('account_holder')
            ->get();

        foreach ($accounts as $account) {
            $accountHolder = $account->account_holder;
            
            // If it's already an array (JSON), keep as-is
            if ($this->isJsonArray($accountHolder)) {
                continue;
            }
            
            // Convert string to single-element array
            $accountHolderArray = [$accountHolder];
            
            DB::table('accounts')
                ->where('id', $account->id)
                ->update([
                    'account_holder' => json_encode($accountHolderArray),
                    'updated_at' => now()
                ]);
        }
        
        // Convert account_holder_id to account_holder_ids array
        $accountsWithHolderId = DB::table('accounts')
            ->whereNotNull('account_holder_id')
            ->get();

        foreach ($accountsWithHolderId as $account) {
            $holderId = $account->account_holder_id;
            $holderIdArray = [$holderId];
            
            DB::table('accounts')
                ->where('id', $account->id)
                ->update([
                    'account_holder_id' => json_encode($holderIdArray),
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Convert data back to old format
     */
    private function convertDataBack(): void
    {
        $accounts = DB::table('accounts')
            ->whereNotNull('account_holders')
            ->get();

        foreach ($accounts as $account) {
            $accountHolders = $account->account_holders;
            
            // If it's a JSON array, convert to comma-separated string
            if ($this->isJsonArray($accountHolders)) {
                $accountHolderArray = json_decode($accountHolders, true);
                if (is_array($accountHolderArray) && !empty($accountHolderArray)) {
                    $accountHolderString = implode(', ', $accountHolderArray);
                    
                    DB::table('accounts')
                        ->where('id', $account->id)
                        ->update([
                            'account_holders' => $accountHolderString,
                            'updated_at' => now()
                        ]);
                }
            }
        }
        
        // Convert account_holder_ids back to single account_holder_id
        $accountsWithHolderIds = DB::table('accounts')
            ->whereNotNull('account_holder_ids')
            ->get();

        foreach ($accountsWithHolderIds as $account) {
            $holderIds = $account->account_holder_ids;
            
            if ($this->isJsonArray($holderIds)) {
                $holderIdArray = json_decode($holderIds, true);
                if (is_array($holderIdArray) && !empty($holderIdArray)) {
                    $primaryHolderId = $holderIdArray[0]; // Take first ID
                    
                    DB::table('accounts')
                        ->where('id', $account->id)
                        ->update([
                            'account_holder_ids' => $primaryHolderId,
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
