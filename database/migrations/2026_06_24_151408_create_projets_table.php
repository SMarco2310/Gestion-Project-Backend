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
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('reference_code');
            $table->enum('status',['à faire', 'en cours', 'terminé'])->default('à faire');
            $table->date('start_date')->default(now());
            $table->date('end_date')->default(now()->addDays(7));
            $table->foreignId('organization_id')->constrained('organizations', 'id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['organization_id', 'reference_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projets');
    }
};
