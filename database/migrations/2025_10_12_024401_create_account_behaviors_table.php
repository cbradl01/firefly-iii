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
        Schema::create('account_behaviors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
            $table->string('calculation_method', 50);
            $table->timestamps();
        });

        // Seed account behaviors
        $this->seedAccountBehaviors();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_behaviors');
    }

    /**
     * Seed account behaviors
     */
    private function seedAccountBehaviors(): void
    {
        $behaviors = [
            ['name' => 'Simple', 'description' => 'Simple account with basic balance tracking', 'calculation_method' => 'sum'],
            ['name' => 'Container', 'description' => 'Container account that holds other accounts', 'calculation_method' => 'sum_children'],
            ['name' => 'Security', 'description' => 'Security account for stocks, bonds, etc.', 'calculation_method' => 'sum_securities'],
            ['name' => 'Cash', 'description' => 'Cash account for currency holdings', 'calculation_method' => 'sum_cash'],
        ];

        foreach ($behaviors as $behavior) {
            DB::table('account_behaviors')->insert(array_merge($behavior, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
};
