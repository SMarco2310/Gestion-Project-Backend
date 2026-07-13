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
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['tache_id']);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignUuid('tache_id')->nullable()->change();
            $table->foreign('tache_id')->references('id')->on('taches')->cascadeOnDelete();
            
            $table->foreignUuid('projet_id')->nullable()->after('tache_id')->constrained('projets')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['projet_id']);
            $table->dropColumn('projet_id');
            
            $table->dropForeign(['tache_id']);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignUuid('tache_id')->nullable(false)->change();
            $table->foreign('tache_id')->references('id')->on('taches')->cascadeOnDelete();
        });
    }
};
