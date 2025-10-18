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
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
            $table->timestamps();
        });

        // Seed account categories
        $this->seedAccountCategories();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_categories');
    }

    /**
     * Seed account categories
     */
    private function seedAccountCategories(): void
    {
        $categories = [
            ['name' => 'Asset', 'description' => 'Assets are things you own that have value'],
            ['name' => 'Liability', 'description' => 'Liabilities are debts you owe'],
            ['name' => 'Expense', 'description' => 'Expenses are money you spend'],
            ['name' => 'Revenue', 'description' => 'Revenue is money you earn'],
        ];

        foreach ($categories as $category) {
            DB::table('account_categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
};
