<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Realigns questions.course_id with the course that actually owns the quiz.
 *
 * CourseQuizController::duplicateQuiz replicated each question without retagging
 * it, so a quiz copied into another course kept questions pointing at the course
 * it was copied from. The owning quiz is the source of truth here — a question is
 * reached through `questionable_id`, and `course_id` is only a denormalised copy.
 *
 * Lesson-attached questions are untouched: they never drifted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasTable('course_quizzes')) {
            return;
        }

        // Keep the pre-repair value so down() can restore it exactly.
        if (! Schema::hasTable('questions_course_id_backup')) {
            Schema::create('questions_course_id_backup', function ($table) {
                $table->unsignedBigInteger('question_id')->primary();
                $table->unsignedBigInteger('course_id')->nullable();
            });
        }

        $drifted = DB::table('questions as q')
            ->join('course_quizzes as cq', 'cq.id', '=', 'q.questionable_id')
            ->where('q.questionable_type', 'App\Models\CourseQuiz')
            ->whereColumn('q.course_id', '!=', 'cq.course_id')
            ->select('q.id', 'q.course_id', 'cq.course_id as quiz_course_id')
            ->get();

        foreach ($drifted->chunk(500) as $chunk) {
            DB::table('questions_course_id_backup')->insertOrIgnore(
                $chunk->map(fn ($row) => [
                    'question_id' => $row->id,
                    'course_id' => $row->course_id,
                ])->all()
            );

            foreach ($chunk->groupBy('quiz_course_id') as $courseId => $rows) {
                DB::table('questions')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['course_id' => $courseId]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions_course_id_backup')) {
            return;
        }

        foreach (DB::table('questions_course_id_backup')->orderBy('question_id')->cursor() as $row) {
            DB::table('questions')
                ->where('id', $row->question_id)
                ->update(['course_id' => $row->course_id]);
        }

        Schema::drop('questions_course_id_backup');
    }
};
