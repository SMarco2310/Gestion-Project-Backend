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
        // The create migration was later amended to name this column organization_id
        // directly, so on a fresh database there is nothing to rename. Only databases
        // built before that amendment still carry workplace_id.
        if (! Schema::hasColumn('invitations', 'workplace_id')) {
            return;
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->renameColumn('workplace_id', 'organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('invitations', 'organization_id')) {
            return;
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->renameColumn('organization_id', 'workplace_id');
        });
    }
};
