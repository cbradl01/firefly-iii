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
        Schema::dropIfExists('field_definitions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration drops the field_definitions table
        // The table structure is now managed in FieldDefinitions.php config
        // If rollback is needed, the table would need to be recreated manually
        // with the current field definitions from the config file
    }
};
