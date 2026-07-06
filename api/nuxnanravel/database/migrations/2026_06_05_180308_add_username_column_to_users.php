<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable username first if not exists
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 191)->nullable()->unique()->after('name');
            });

            // 2. Backfill: copy name -> username
            DB::statement('UPDATE users SET username = name WHERE username IS NULL');

            // 3. Make username NOT NULL
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 191)->nullable(false)->change();
            });
        }

        // 4. Drop unique index from name if it exists
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        } catch (Exception $e) {
            // Ignore if index doesn't exist or already dropped
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add unique to name
        Schema::table('users', function (Blueprint $table) {
            $table->unique('name');
        });

        // 2. Drop username
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
