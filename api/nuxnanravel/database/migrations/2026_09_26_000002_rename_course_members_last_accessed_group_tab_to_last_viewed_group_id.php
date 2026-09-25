<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The column stores a course_groups.id (the group the admin last viewed),
     * not a tab index — the "tab" name was misleading. Rename it to reflect
     * what it actually holds. Type/nullable/default are preserved by rename.
     */
    public function up(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->renameColumn('last_accessed_group_tab', 'last_viewed_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->renameColumn('last_viewed_group_id', 'last_accessed_group_tab');
        });
    }
};
