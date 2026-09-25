<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * last_accessed_group_tab stores a course_groups.id, but was declared as a
     * signed tinyInteger (max 127). Group ids already exceed 127, so selecting
     * one of those groups would overflow on MySQL STRICT mode. Widen it to
     * unsignedBigInteger so it matches the range of course_groups.id.
     */
    public function up(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->unsignedBigInteger('last_accessed_group_tab')->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        // Narrowing back to tinyInteger would overflow on any value > 127, which
        // MySQL STRICT mode rejects. Clamp out-of-range values (a cosmetic
        // "last viewed group" preference) to 0 before shrinking the column.
        DB::table('course_members')
            ->where('last_accessed_group_tab', '>', 127)
            ->update(['last_accessed_group_tab' => 0]);

        Schema::table('course_members', function (Blueprint $table) {
            $table->tinyInteger('last_accessed_group_tab')->nullable()->default(0)->change();
        });
    }
};
