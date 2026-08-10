<?php

use App\Models\Course;
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
        if (! Schema::hasTable('course_quiz_regrade_backups')) {
            Schema::create('course_quiz_regrade_backups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('result_id');
                $table->decimal('old_score', 10, 2)->nullable();
                $table->decimal('old_percentage', 8, 2)->nullable();
                $table->integer('old_status')->nullable();
                $table->timestamps();

                $table->index('result_id', 'cq_regrade_result_idx');
            });
        }

        // Re-sync course_quizzes.total_score / total_questions
        $brokenQuizzes = DB::table('course_quizzes as q')
            ->leftJoin('questions as qs', function ($join) {
                $join->on('qs.questionable_id', '=', 'q.id')
                    ->where('qs.questionable_type', '=', 'App\\Models\\CourseQuiz');
            })
            ->groupBy('q.id', 'q.total_score', 'q.total_questions')
            ->havingRaw('q.total_score <> COALESCE(SUM(COALESCE(qs.points, 1)), 0) OR q.total_questions <> COUNT(qs.id)')
            ->selectRaw('q.id, q.total_score, q.total_questions, COALESCE(SUM(COALESCE(qs.points, 1)), 0) AS real_score, COUNT(qs.id) AS real_questions')
            ->get();

        foreach ($brokenQuizzes as $quiz) {
            DB::table('course_quizzes')->where('id', $quiz->id)->update([
                'total_score' => $quiz->real_score,
                'total_questions' => $quiz->real_questions,
            ]);
        }

        $quizzes = DB::table('course_quizzes')->get(['id', 'total_score', 'passing_score', 'course_id'])->keyBy('id');
        $courseIdsToSync = [];

        DB::table('course_quiz_results')->chunkById(500, function ($results) use ($quizzes, &$courseIdsToSync) {
            $quizIds = $results->pluck('quiz_id')->filter()->unique()->values()->toArray();
            $userIds = $results->pluck('user_id')->filter()->unique()->values()->toArray();

            $answerSums = [];
            if (! empty($quizIds) && ! empty($userIds)) {
                $sums = DB::table('user_answer_questions')
                    ->whereIn('quiz_id', $quizIds)
                    ->whereIn('user_id', $userIds)
                    ->groupBy('quiz_id', 'user_id')
                    ->selectRaw('quiz_id, user_id, SUM(points) as total_points')
                    ->get();
                foreach ($sums as $sum) {
                    $answerSums[$sum->quiz_id.'_'.$sum->user_id] = $sum->total_points;
                }
            }

            foreach ($results as $result) {
                if ($result->status === 0 || $result->status === '0') {
                    continue;
                }

                $quizInfo = $quizzes->get($result->quiz_id);
                if (! $quizInfo) {
                    continue;
                }

                $score = $answerSums[$result->quiz_id.'_'.$result->user_id] ?? 0;

                $percentage = $quizInfo->total_score > 0
                    ? round(($score / $quizInfo->total_score) * 100, 2)
                    : 0;

                $status = round($percentage, 2) >= $quizInfo->passing_score ? 3 : 4;

                if ($result->score != $score || round($result->percentage, 2) != $percentage || (int) $result->status !== $status) {
                    DB::table('course_quiz_regrade_backups')->insert([
                        'result_id' => $result->id,
                        'old_score' => $result->score,
                        'old_percentage' => $result->percentage,
                        'old_status' => $result->status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('course_quiz_results')->where('id', $result->id)->update([
                        'score' => $score,
                        'percentage' => $percentage,
                        'status' => $status,
                    ]);

                    if ($quizInfo->course_id) {
                        $courseIdsToSync[] = $quizInfo->course_id;
                    }
                }
            }
        });

        $courseIdsToSync = array_unique($courseIdsToSync);
        foreach ($courseIdsToSync as $courseId) {
            try {
                $course = Course::find($courseId);
                if ($course) {
                    app(CourseScoreService::class)->syncCourseTotalScore($course);
                    $course->courseMembers()->chunk(200, function ($members) {
                        foreach ($members as $member) {
                            app(CourseScoreService::class)->recompute($member);
                        }
                    });
                }
            } catch (Exception $e) {
                Log::error("Failed to sync course $courseId during regrade finish: ".$e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('course_quiz_regrade_backups')) {
            DB::table('course_quiz_regrade_backups')
                ->orderBy('id', 'desc')
                ->chunkById(500, function ($backups) {
                    foreach ($backups as $backup) {
                        DB::table('course_quiz_results')->where('id', $backup->result_id)->update([
                            'score' => $backup->old_score,
                            'percentage' => $backup->old_percentage,
                            'status' => $backup->old_status,
                        ]);
                    }
                });

            Schema::dropIfExists('course_quiz_regrade_backups');
        }
    }
};
