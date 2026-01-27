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
        // Check if SUPER_ADMIN role already exists
        $existingRole = DB::table('roles')->where('name', 'SUPER_ADMIN')->first();

        if (!$existingRole) {
            // Insert SUPER_ADMIN role
            DB::table('roles')->insert([
                'name' => 'SUPER_ADMIN',
                'description' => 'Super Administrator with highest privileges',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'SUPER_ADMIN')->delete();
    }
};
