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
        // Create the pivot table
        Schema::create('tache_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tache_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Migrate existing tags
        DB::table('taches')->whereNotNull('tag_id')->orderBy('id')->chunk(100, function ($taches) {
            $inserts = [];
            foreach ($taches as $tache) {
                $inserts[] = [
                    'tache_id' => $tache->id,
                    'tag_id' => $tache->tag_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (count($inserts) > 0) {
                DB::table('tache_tag')->insert($inserts);
            }
        });

        // Drop the old tag_id column
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['tag_id']);
            $table->dropColumn('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->foreignId('tag_id')->nullable()->constrained('tags')->nullOnDelete();
        });

        // Migrate data back (take the first tag of each task)
        DB::table('tache_tag')->orderBy('id')->chunk(100, function ($tacheTags) {
            foreach ($tacheTags as $tacheTag) {
                // Ignore if it already has a tag_id (taking the first one)
                $hasTag = DB::table('taches')->where('id', $tacheTag->tache_id)->whereNotNull('tag_id')->exists();
                if (!$hasTag) {
                    DB::table('taches')->where('id', $tacheTag->tache_id)->update(['tag_id' => $tacheTag->tag_id]);
                }
            }
        });

        Schema::dropIfExists('tache_tag');
    }
};
