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
        Schema::table('topics', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('lesson_id');
            $table->index(['lesson_id', 'sort_order']);
        });

        // Backfill: ตั้ง sort_order ตาม created_at, id ภายใน lesson_id เดียวกัน
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE topics t
                JOIN (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY lesson_id ORDER BY created_at, id) AS rn
                    FROM topics
                ) ranked ON ranked.id = t.id
                SET t.sort_order = ranked.rn
            ');
        } else {
            $topics = DB::table('topics')->orderBy('lesson_id')->orderBy('created_at')->orderBy('id')->get();
            $currentLessonId = null;
            $order = 0;
            foreach ($topics as $topic) {
                if ($topic->lesson_id !== $currentLessonId) {
                    $currentLessonId = $topic->lesson_id;
                    $order = 1;
                } else {
                    $order++;
                }
                DB::table('topics')->where('id', $topic->id)->update(['sort_order' => $order]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['lesson_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
