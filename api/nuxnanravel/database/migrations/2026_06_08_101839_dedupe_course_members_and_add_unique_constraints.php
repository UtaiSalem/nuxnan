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
        // 1. Deduplicate course_members: keep highest ID per (course_id, user_id)
        $duplicateMembers = DB::table('course_members')
            ->select('course_id', 'user_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('course_id', 'user_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($duplicateMembers as $dup) {
            DB::table('course_members')
                ->where('course_id', $dup->course_id)
                ->where('user_id', $dup->user_id)
                ->where('id', '<', $dup->max_id)
                ->delete();
        }

        // 2. Deduplicate course_group_members: keep highest ID per (course_id, group_id, user_id)
        $duplicateGroupMembers = DB::table('course_group_members')
            ->select('course_id', 'group_id', 'user_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('course_id', 'group_id', 'user_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($duplicateGroupMembers as $dup) {
            DB::table('course_group_members')
                ->where('course_id', $dup->course_id)
                ->where('group_id', $dup->group_id)
                ->where('user_id', $dup->user_id)
                ->where('id', '<', $dup->max_id)
                ->delete();
        }

        // 3. Add unique constraints
        Schema::table('course_members', function (Blueprint $table) {
            $table->unique(['course_id', 'user_id'], 'course_members_unique_enrollment');
        });

        Schema::table('course_group_members', function (Blueprint $table) {
            $table->unique(['course_id', 'group_id', 'user_id'], 'course_group_members_unique_membership');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->dropUnique('course_members_unique_enrollment');
        });

        Schema::table('course_group_members', function (Blueprint $table) {
            $table->dropUnique('course_group_members_unique_membership');
        });
    }
};
