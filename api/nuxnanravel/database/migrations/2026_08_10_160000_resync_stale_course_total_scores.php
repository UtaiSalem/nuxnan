<?php

use App\Models\Course;
use App\Models\CourseExternalScore;
use App\Services\CourseScoreService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('course_total_score_backups')) {
            Schema::create('course_total_score_backups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id');
                $table->decimal('old_total_score', 12, 2)->nullable();
                $table->timestamps();

                $table->index('course_id', 'course_total_score_bk_idx');
            });
        }

        $courseScoreService = app(CourseScoreService::class);

        Course::select('id', 'total_score')->chunk(200, function ($courses) use ($courseScoreService) {
            foreach ($courses as $course) {
                try {
                    $internal = $courseScoreService->calculateInternalTotalScore($course);
                    $external = (float) CourseExternalScore::where('course_id', $course->id)->sum('max_score');
                    $expected = max(0, $internal + $external);

                    if ((float) $course->total_score !== (float) $expected) {
                        DB::table('course_total_score_backups')->insert([
                            'course_id' => $course->id,
                            'old_total_score' => $course->total_score,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $courseScoreService->syncCourseTotalScore($course);

                        $course->courseMembers()->chunk(200, function ($members) use ($courseScoreService) {
                            foreach ($members as $member) {
                                $courseScoreService->recompute($member);
                            }
                        });
                    }
                } catch (Throwable $e) {
                    Log::error('Failed to resync course total score', [
                        'course_id' => $course->id,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('course_total_score_backups')) {
            DB::table('course_total_score_backups')->orderBy('id')->chunk(200, function ($backups) {
                foreach ($backups as $backup) {
                    DB::table('courses')
                        ->where('id', $backup->course_id)
                        ->update(['total_score' => $backup->old_total_score]);
                }
            });

            Schema::dropIfExists('course_total_score_backups');

            // Note: down() restores the course totals but does NOT undo the
            // member recompute() calls, because the pre-repair member values were not captured.
        }
    }
};
