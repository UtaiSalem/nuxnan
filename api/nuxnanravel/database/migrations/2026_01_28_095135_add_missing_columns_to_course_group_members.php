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
        Schema::table('course_group_members', function (Blueprint $table) {
            if (!Schema::hasColumn('course_group_members', 'role')) {
                $table->enum('role', ['admin', 'moderator', 'member'])->default('member')->after('user_id');
            }
            if (!Schema::hasColumn('course_group_members', 'request_status')) {
                $table->enum('request_status', ['pending', 'approved', 'rejected'])->default('approved')->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_group_members', function (Blueprint $table) {
            if (Schema::hasColumn('course_group_members', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('course_group_members', 'request_status')) {
                $table->dropColumn('request_status');
            }
        });
    }
};
