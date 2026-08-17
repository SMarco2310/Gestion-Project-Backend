<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_entitlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id')->unique();
            $table->unsignedBigInteger('klea_subscription_id')->nullable();
            $table->unsignedBigInteger('klea_plan_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->enum('status', ['pending', 'active', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->json('features')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_entitlements');
    }
};
