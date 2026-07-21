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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reminder_days_before_start')->default(2);
            $table->time('reminder_time_start')->default('08:00:00');
            $table->integer('reminder_days_before_end')->default(2);
            $table->time('reminder_time_end')->default('08:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_days_before_start',
                'reminder_time_start',
                'reminder_days_before_end',
                'reminder_time_end'
            ]);
        });
    }
};
