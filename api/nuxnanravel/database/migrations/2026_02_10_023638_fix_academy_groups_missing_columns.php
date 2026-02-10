<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes the academy_groups table which was missing essential columns
     * (academy_id, name, description, type, settings) due to a previous migration issue.
     */
    public function up(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            // Add academy_id column if it doesn't exist
            if (!Schema::hasColumn('academy_groups', 'academy_id')) {
                $table->unsignedBigInteger('academy_id')->after('id');
                
                // Add the foreign key constraint
                $table->foreign('academy_id')
                    ->references('id')
                    ->on('academies')
                    ->onDelete('cascade');
            }
            
            // Add name column if it doesn't exist
            if (!Schema::hasColumn('academy_groups', 'name')) {
                $table->string('name')->after('academy_id');
            }
            
            // Add description column if it doesn't exist
            if (!Schema::hasColumn('academy_groups', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            
            // Add type column if it doesn't exist (department, classroom, club)
            if (!Schema::hasColumn('academy_groups', 'type')) {
                $table->string('type')->default('classroom')->after('description');
            }
            
            // Add settings column if it doesn't exist
            if (!Schema::hasColumn('academy_groups', 'settings')) {
                $table->json('settings')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            if (Schema::hasColumn('academy_groups', 'academy_id')) {
                $table->dropForeign(['academy_id']);
                $table->dropColumn('academy_id');
            }
            if (Schema::hasColumn('academy_groups', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('academy_groups', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('academy_groups', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('academy_groups', 'settings')) {
                $table->dropColumn('settings');
            }
        });
    }
};
