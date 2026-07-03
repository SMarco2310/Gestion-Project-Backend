<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tags = [
            ['name' => 'BUG', 'color' => '#ef4444', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'FEATURE', 'color' => '#3b82f6', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IMPROVEMENT', 'color' => '#10b981', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DOCUMENTATION', 'color' => '#f59e0b', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DESIGN', 'color' => '#8b5cf6', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'TESTING', 'color' => '#f97316', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DEPLOYMENT', 'color' => '#06b6d4', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tags')->insert($tags);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tags')->where('is_default', true)->delete();
    }
};
