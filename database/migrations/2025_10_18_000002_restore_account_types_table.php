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
        // Create account_types table with original Firefly III structure
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('category_id')->constrained('account_categories');
            $table->foreignId('behavior_id')->constrained('account_behaviors');
            $table->text('description')->nullable();
            $table->json('firefly_mapping')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'behavior_id', 'active']);
            $table->index(['name', 'active']);
        });

        // Migrate data from account_templates to account_types
        $this->migrateDataFromTemplates();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }

    /**
     * Migrate data from account_templates to account_types
     */
    private function migrateDataFromTemplates(): void
    {
        // Get all account templates
        $templates = DB::table('account_templates')->get();
        
        foreach ($templates as $template) {
            // Map template data to account_type structure
            $accountTypeData = [
                'name' => $template->name,
                'category_id' => $template->category_id,
                'behavior_id' => $template->behavior_id,
                'description' => $template->description,
                'firefly_mapping' => $template->metadata_schema, // Map metadata_schema to firefly_mapping
                'active' => $template->active,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ];
            
            // Insert into account_types
            $accountTypeId = DB::table('account_types')->insertGetId($accountTypeData);
            
            // Update any accounts that reference this template to use the new account_type_id
            DB::table('accounts')
                ->where('template_id', $template->id)
                ->update(['account_type_id' => $accountTypeId]);
        }
    }
};
