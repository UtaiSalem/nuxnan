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
        // 1. Add nullable username first if not exists
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 191)->nullable()->after('name');
            });
            
            // 2. Backfill: copy name -> username
            DB::statement('UPDATE users SET username = name WHERE username IS NULL');
        }

        // Add unique if not exists
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username');
            });
        } catch (\Exception $e) {}
        
        // 3. Make username NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 191)->nullable(false)->change();
        });
        
        // 4. Drop unique index from name if exists
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        } catch (\Exception $e) {}
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
