<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn('tag');
            $table->foreignId('tag_id')->nullable()->constrained('tags')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['tag_id']);
            $table->dropColumn('tag_id');
            $table->enum('tag', ['bug', 'feature', 'improvement', 'documentation', 'design', 'testing', 'deployment'])->nullable();
        });
    }
};
