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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->change();
            $table->string('last_name')->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('provider_id')->nullable()->after('id');
            $table->string('password')->nullable()->change();
        });

        // Split the newly renamed first_name (which currently holds the full name) into first_name and last_name
        \Illuminate\Support\Facades\DB::table('users')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $nameParts = explode(' ', $user->first_name ?? '', 2);
                $firstName = $nameParts[0] ?: '';
                $lastName = count($nameParts) > 1 ? $nameParts[1] : '';

                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
