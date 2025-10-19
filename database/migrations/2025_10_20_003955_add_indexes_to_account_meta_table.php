<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_meta', function (Blueprint $table) {
            // Add indexes for frequently queried metadata fields
            $table->index(['account_id', 'name'], 'account_meta_account_name_idx');
            $table->index(['name', 'data'], 'account_meta_name_data_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_meta', function (Blueprint $table) {
            $table->dropIndex('account_meta_account_name_idx');
            $table->dropIndex('account_meta_name_data_idx');
        });
    }
};
