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
        Schema::table('organizations', function (Blueprint $table) {
            $table->json('kanban_columns')->nullable();
        });

        // Use raw SQL to modify the ENUM to VARCHAR safely without doctrine/dbal
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE taches MODIFY status VARCHAR(255) DEFAULT 'à faire'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('kanban_columns');
        });

        // Can't reliably convert back to ENUM if custom statuses exist, so we leave it as VARCHAR
        // \Illuminate\Support\Facades\DB::statement("ALTER TABLE taches MODIFY status ENUM('à faire', 'en cours', 'terminé') DEFAULT 'à faire'");
    }
};
