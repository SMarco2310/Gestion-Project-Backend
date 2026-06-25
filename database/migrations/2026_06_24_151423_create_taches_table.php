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
            $table->enum('priority',['low', 'medium', 'high'])->default('medium');
            $table->enum('status',['todo', 'in_progress', 'done'])->default('todo');
            $table->date('due_date');
            $table->foreignId('projet_id')->constrained()->onDelete('cascade');
            $table->timestamps();
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
