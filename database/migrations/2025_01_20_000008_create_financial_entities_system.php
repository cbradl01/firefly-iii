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
        // 1. Create financial_entities table
        Schema::create('financial_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "John Smith", "Smith Family Trust", "ABC Financial Advisors"
            $table->string('entity_type'); // 'individual', 'trust', 'business', 'advisor', 'custodian'
            $table->string('display_name')->nullable(); // How to show in UI
            $table->text('description')->nullable();
            $table->json('contact_info')->nullable(); // phone, email, address, etc.
            $table->json('metadata')->nullable(); // Additional entity-specific data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['entity_type', 'is_active']);
            $table->index('name');
        });

        // 2. Create entity_relationships table (for complex relationships)
        Schema::create('entity_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('financial_entities')->onDelete('cascade');
            $table->foreignId('related_entity_id')->constrained('financial_entities')->onDelete('cascade');
            $table->string('relationship_type'); // 'spouse', 'beneficiary', 'trustee', 'custodian', 'advisor'
            $table->json('relationship_metadata')->nullable(); // Additional relationship data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['entity_id', 'related_entity_id', 'relationship_type']);
            $table->index(['entity_id', 'relationship_type']);
        });

        // 3. Create user_entity_permissions table (who can manage what)
        Schema::create('user_entity_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('entity_id')->constrained('financial_entities')->onDelete('cascade');
            $table->string('permission_level'); // 'view', 'edit', 'admin'
            $table->json('permission_metadata')->nullable(); // Additional permission data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'entity_id']);
            $table->index(['user_id', 'permission_level']);
        });

        // 4. Add entity_id to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('user_id')->constrained('financial_entities')->onDelete('set null');
            $table->index('entity_id');
        });

        // 5. Create account_entity_roles table (for complex account ownership)
        Schema::create('account_entity_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('entity_id')->constrained('financial_entities')->onDelete('cascade');
            $table->string('role_type'); // 'owner', 'beneficiary', 'trustee', 'custodian', 'advisor'
            $table->decimal('percentage', 5, 2)->nullable(); // For partial ownership/beneficiary percentages
            $table->json('role_metadata')->nullable(); // Additional role-specific data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['account_id', 'entity_id', 'role_type']);
            $table->index(['account_id', 'role_type']);
            $table->index(['entity_id', 'role_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_entity_roles');
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });
        Schema::dropIfExists('user_entity_permissions');
        Schema::dropIfExists('entity_relationships');
        Schema::dropIfExists('financial_entities');
    }
};
