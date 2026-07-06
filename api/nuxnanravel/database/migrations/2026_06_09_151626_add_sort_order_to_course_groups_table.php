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
        Schema::table('course_groups', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('course_id');
            $table->index(['course_id', 'sort_order']);
        });

        // Backfill: ตั้ง sort_order ตาม created_at, id ภายใน course_id เดียวกัน
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE course_groups cg
                JOIN (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY course_id ORDER BY created_at, id) AS rn
                    FROM course_groups
                ) ranked ON ranked.id = cg.id
                SET cg.sort_order = ranked.rn
            ');
        } else {
            $groups = DB::table('course_groups')->orderBy('course_id')->orderBy('created_at')->orderBy('id')->get();
            $currentCourseId = null;
            $order = 0;
            foreach ($groups as $group) {
                if ($group->course_id !== $currentCourseId) {
                    $currentCourseId = $group->course_id;
                    $order = 1;
                } else {
                    $order++;
                }
                DB::table('course_groups')->where('id', $group->id)->update(['sort_order' => $order]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_groups', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
