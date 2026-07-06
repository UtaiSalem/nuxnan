<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_members', 'draft_earned_score')) {
            return;
        }

        Schema::table('course_members', function (Blueprint $table) {
            $table->renameColumn('draft_total_score', 'draft_earned_score');
            $table->renameColumn('final_total_score', 'final_earned_score');
        });

        Schema::table('course_members', function (Blueprint $table) {
            $table->decimal('draft_max_score', 10, 2)->default(0)->after('draft_earned_score');
            $table->decimal('draft_percentage', 5, 2)->default(0)->after('draft_max_score');

            $table->decimal('final_max_score', 10, 2)->default(0)->after('final_earned_score');
            $table->decimal('final_percentage', 5, 2)->default(0)->after('final_max_score');

            $table->decimal('bonus_points', 8, 2)->nullable()->change();
            $table->decimal('achieved_score', 10, 2)->nullable()->change();

            $table->timestamp('last_score_synced_at')->nullable()->after('grade_progress');
        });

        // Data backfill
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE course_members cm
                JOIN courses c ON cm.course_id = c.id
                SET cm.final_max_score = c.total_score,
                    cm.final_percentage = CASE WHEN c.total_score > 0 THEN (COALESCE(cm.final_earned_score, 0) / c.total_score) * 100 ELSE 0 END
                WHERE c.finalization_status IN ('published', 'finalized', 'archived')
            ");

            DB::statement('
                UPDATE course_members cm
                JOIN courses c ON cm.course_id = c.id
                SET cm.draft_max_score = c.total_score,
                    cm.draft_percentage = CASE WHEN c.total_score > 0 THEN (COALESCE(cm.draft_earned_score, 0) / c.total_score) * 100 ELSE 0 END
            ');
        } else {
            // SQLite or other: Use subqueries
            DB::statement("
                UPDATE course_members
                SET final_max_score = (SELECT total_score FROM courses WHERE courses.id = course_members.course_id),
                    final_percentage = (
                        SELECT CASE WHEN total_score > 0 THEN (COALESCE(final_earned_score, 0) / total_score) * 100 ELSE 0 END 
                        FROM courses WHERE courses.id = course_members.course_id
                    )
                WHERE course_id IN (SELECT id FROM courses WHERE finalization_status IN ('published', 'finalized', 'archived'))
            ");

            DB::statement('
                UPDATE course_members
                SET draft_max_score = (SELECT total_score FROM courses WHERE courses.id = course_members.course_id),
                    draft_percentage = (
                        SELECT CASE WHEN total_score > 0 THEN (COALESCE(draft_earned_score, 0) / total_score) * 100 ELSE 0 END 
                        FROM courses WHERE courses.id = course_members.course_id
                    )
            ');
        }
    }

    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->renameColumn('draft_earned_score', 'draft_total_score');
            $table->renameColumn('final_earned_score', 'final_total_score');
        });

        Schema::table('course_members', function (Blueprint $table) {
            $table->dropColumn('draft_max_score');
            $table->dropColumn('draft_percentage');
            $table->dropColumn('final_max_score');
            $table->dropColumn('final_percentage');

            $table->integer('bonus_points')->nullable()->change();
            $table->integer('achieved_score')->nullable()->change();

            $table->dropColumn('last_score_synced_at');
        });
    }
};
