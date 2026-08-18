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
        Schema::table('organization_entitlements', function (Blueprint $table) {
            $table->unsignedBigInteger('last_transaction_id')->nullable()->after('last_webhook_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_entitlements', function (Blueprint $table) {
            $table->dropColumn('last_transaction_id');
        });
    }
};
