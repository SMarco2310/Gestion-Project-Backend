<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->string('board_column')->nullable();
        });

        Schema::table('projets', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
        });

        // Migrate existing tasks
        // Set board_column = status
        DB::statement("UPDATE taches SET board_column = status");

        // Reset status to 'à faire' if it's not a native column
        DB::statement("UPDATE taches SET status = 'à faire' WHERE LOWER(status) NOT IN ('à faire', 'to do', 'en cours', 'in progress', 'terminé', 'done')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });

        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn('board_column');
        });
    }
};
