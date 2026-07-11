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
        // Change enum to string for more flexibility
        DB::statement("ALTER TABLE taches MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'not done'");
        
        // Migrate existing status data
        DB::statement("UPDATE taches SET status = 'done' WHERE status = 'terminé'");
        DB::statement("UPDATE taches SET status = 'not done' WHERE status != 'done'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE taches MODIFY COLUMN status ENUM('à faire', 'en cours', 'terminé') NOT NULL DEFAULT 'à faire'");
        DB::statement("UPDATE taches SET status = 'terminé' WHERE status = 'done'");
        DB::statement("UPDATE taches SET status = 'à faire' WHERE status = 'not done'");
    }
};
