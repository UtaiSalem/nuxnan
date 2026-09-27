<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair `course_groups.privacy`, which drifted out of the dev/prod MySQL schema.
 *
 * Migration 2026_01_03_020322_update_course_groups_full_cycle already adds this
 * column, but it is guarded by `if (! Schema::hasColumn(...))` and is marked as
 * run in the `migrations` table on databases whose schema was later rebuilt from
 * a dump that lacks the column — so it never re-adds it. The app writes privacy
 * behind its own `Schema::hasColumn('course_groups', 'privacy')` guard
 * (CourseGroupController::store) and the value is intended to persist, so the
 * missing column silently disables the public/private join feature on MySQL and
 * makes every test that creates a group with a privacy value fail there.
 *
 * This repair is idempotent: it only adds the column when it is absent, matching
 * the original enum definition. `down()` is a deliberate no-op — the column
 * belongs to the create/update migration's base schema, not to this repair.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_groups', 'privacy')) {
            return;
        }

        Schema::table('course_groups', function (Blueprint $table) {
            $table->enum('privacy', ['public', 'private'])
                ->default('public')
                ->after('status')
                ->comment('public: anyone can join, private: request only');
        });
    }

    public function down(): void
    {
        // No-op: `privacy` is part of the base schema owned by
        // 2026_01_03_020322_update_course_groups_full_cycle.
    }
};
