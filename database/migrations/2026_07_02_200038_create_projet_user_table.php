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
        Schema::create('projet_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('projet_id')->constrained('projets', 'id')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projet_user');
    }
};
