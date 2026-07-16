<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns
        Schema::table('projets', function (Blueprint $table) {
            $table->uuid('workspace_id')->nullable()->after('organization_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });

        Schema::table('taches', function (Blueprint $table) {
            $table->uuid('workspace_id')->nullable()->after('projet_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });

        // 2. Data Migration
        // Create default workspace for each organization and assign projects & tasks
        $organizations = DB::table('organizations')->get();
        
        foreach ($organizations as $org) {
            $workspaceId = Str::uuid()->toString();
            
            DB::table('workspaces')->insert([
                'id' => $workspaceId,
                'name' => 'General Workspace',
                'description' => 'Default workspace for ' . $org->name,
                'organization_id' => $org->id,
                'kanban_columns' => $org->kanban_columns,
                'kanban_colors' => $org->kanban_colors,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add all org members to this workspace
            $orgUsers = DB::table('organization_user')->where('organization_id', $org->id)->get();
            foreach ($orgUsers as $orgUser) {
                DB::table('workspace_user')->insertOrIgnore([
                    'workspace_id' => $workspaceId,
                    'user_id' => $orgUser->user_id,
                    'role' => $orgUser->role,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Move projects to this workspace
            DB::table('projets')
                ->where('organization_id', $org->id)
                ->update(['workspace_id' => $workspaceId]);
        }

        // Move tasks to the project's workspace
        $projets = DB::table('projets')->whereNotNull('workspace_id')->get();
        foreach ($projets as $projet) {
            DB::table('taches')
                ->where('projet_id', $projet->id)
                ->update(['workspace_id' => $projet->workspace_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });

        Schema::table('projets', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};
