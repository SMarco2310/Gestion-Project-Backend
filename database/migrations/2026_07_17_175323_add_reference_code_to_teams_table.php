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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('reference_code')->nullable()->after('organization_id');
        });

        // Backfill reference_code for existing teams grouped by organization
        $organizations = \App\Models\Organization::with('teams')->get();
        foreach ($organizations as $org) {
            $counter = 1;
            foreach ($org->teams()->orderBy('created_at')->get() as $team) {
                $team->reference_code = 'EQ-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
                // Save without triggering events (boot method) to prevent overriding
                $team->saveQuietly();
                $counter++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('reference_code');
        });
    }
};
