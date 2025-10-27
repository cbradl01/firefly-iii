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
        // Drop the foreign key constraint first
        DB::statement('ALTER TABLE accounts DROP CONSTRAINT IF EXISTS accounts_account_holder_id_foreign');
        
        // Convert account_holder_ids column to JSON array type
        // First convert bigint to text, then to JSON array
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder_ids TYPE json USING json_build_array(account_holder_ids)');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder_ids DROP NOT NULL');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder_ids DROP DEFAULT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert account_holder_ids column back to bigint
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder_ids TYPE bigint USING account_holder_ids::text::bigint');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder_ids DROP NOT NULL');
        DB::statement('ALTER TABLE accounts ALTER COLUMN account_holder_ids DROP DEFAULT');
    }
};
