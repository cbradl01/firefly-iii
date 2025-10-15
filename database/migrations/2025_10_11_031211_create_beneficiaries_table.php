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
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->string('relationship')->nullable(); // spouse, child, parent, etc.
            $table->enum('priority', ['primary', 'secondary', 'tertiary', 'quaternary'])->default('primary');
            $table->decimal('percentage', 5, 2)->nullable(); // 0.00 to 100.00
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['account_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
