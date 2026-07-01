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
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255); // required
            $table->text('description')->nullable();
            $table->string('reference_code', 255)->nullable();
            $table->enum('priority',['faible', 'moyen', 'élevé'])->default('moyen');
            $table->enum('status',['à faire', 'en cours', 'terminé'])->default('à faire');
            $table->enum('tag',['bug', 'feature', 'improvement', 'documentation', 'design', 'testing', 'deployment'])->nullable();
            $table->date('due_date')->default(now()->addDays(7));
            $table->foreignId('parent_task_id')->nullable()->constrained('taches')->onDelete('cascade');
            $table->foreignId('projet_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // $table->unique(['user_id', 'reference_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
