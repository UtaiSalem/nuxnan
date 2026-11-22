<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\Lesson;
use App\Models\LessonAnswerQuestion;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\UserAnswerQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "ตั้งค่าทั้งชุด": one request sets points and/or pp_fine on every question
 * of a lesson or quiz, and re-scores answers already given.
 */
class QuestionBulkSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course = Course::factory()->create(['user_id' => $this->teacher->id]);

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'course_member_status' => 1,
        ]);
    }

    private function option(Question $question, string $text, bool $correct): QuestionOption
    {
        return QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $question->id,
            'text' => $text,
            'is_correct' => $correct,
        ]);
    }

    /**
     * The lesson read gate: a student must read the lesson before its questions open.
     */
    private function markLessonRead(User $user, Lesson $lesson): void
    {
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
            'time_spent_seconds' => $lesson->getRequiredReadSeconds(),
        ]);
    }

    // ── Lesson question set ──────────────────────────────────────────────

    private function lessonWithQuestions(int $n = 3): Lesson
    {
        $lesson = Lesson::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'title' => 'L',
            'publication_status' => Lesson::STATUS_PUBLISHED,
            'access_type' => Lesson::ACCESS_FREE,
            'require_completion_before_exercises' => false,
        ]);

        for ($i = 0; $i < $n; $i++) {
            $q = $lesson->questions()->create([
                'user_id' => $this->teacher->id,
                'course_id' => $this->course->id,
                'text' => "Q{$i}",
                'points' => 1,
                'pp_fine' => 0,
            ]);
            $right = $this->option($q, 'Right', true);
            $this->option($q, 'Wrong', false);
            $q->update(['correct_option_id' => $right->id]);
        }

        return $lesson;
    }

    public function test_teacher_sets_points_and_fine_for_every_lesson_question(): void
    {
        $lesson = $this->lessonWithQuestions(3);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/lessons/{$lesson->id}/questions/bulk-settings", ['points' => 2.5, 'pp_fine' => 4])
            ->assertOk()
            ->assertJson(['updated' => 3]);

        $this->assertSame(3, $lesson->questions()->where('points', 2.5)->where('pp_fine', 4)->count());
    }

    public function test_only_fine_can_be_changed_leaving_points_alone(): void
    {
        $lesson = $this->lessonWithQuestions(2);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/lessons/{$lesson->id}/questions/bulk-settings", ['pp_fine' => 7])
            ->assertOk();

        $this->assertSame(2, $lesson->questions()->where('points', 1)->where('pp_fine', 7)->count());
    }

    public function test_lesson_points_change_rescores_existing_correct_answers(): void
    {
        $lesson = $this->lessonWithQuestions(1);
        $this->markLessonRead($this->student, $lesson);
        $question = $lesson->questions()->first();
        $right = $question->options()->where('is_correct', true)->first();

        $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/questions/{$question->id}/answer", ['answer_id' => $right->id])
            ->assertOk();

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/lessons/{$lesson->id}/questions/bulk-settings", ['points' => 3])
            ->assertOk();

        $this->assertEquals(3, LessonAnswerQuestion::firstOrFail()->points);
    }

    public function test_student_cannot_change_lesson_set(): void
    {
        $lesson = $this->lessonWithQuestions(2);

        $this->actingAs($this->student, 'api')
            ->patchJson("/api/lessons/{$lesson->id}/questions/bulk-settings", ['points' => 100])
            ->assertStatus(403);

        $this->assertSame(2, $lesson->questions()->where('points', 1)->count());
    }

    public function test_request_must_change_something(): void
    {
        $lesson = $this->lessonWithQuestions(1);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/lessons/{$lesson->id}/questions/bulk-settings", [])
            ->assertStatus(422);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/lessons/{$lesson->id}/questions/bulk-settings", ['points' => 0.3])
            ->assertStatus(422);
    }

    // ── Quiz ─────────────────────────────────────────────────────────────

    /**
     * @return array{0: CourseQuiz, 1: Question, 2: QuestionOption}
     */
    private function quizWithQuestions(int $n = 2): array
    {
        $quiz = CourseQuiz::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'is_active' => true,
            'passing_score' => 50,
        ]);

        $first = null;
        $firstRight = null;
        for ($i = 0; $i < $n; $i++) {
            $q = $quiz->questions()->create([
                'user_id' => $this->teacher->id,
                'course_id' => $this->course->id,
                'text' => "QQ{$i}",
                'points' => 1,
            ]);
            $right = $this->option($q, 'Right', true);
            $this->option($q, 'Wrong', false);
            $q->update(['correct_option_id' => $right->id]);
            $first ??= $q;
            $firstRight ??= $right;
        }

        return [$quiz->fresh(), $first->fresh(), $firstRight];
    }

    public function test_teacher_sets_every_quiz_question_and_quiz_total_follows(): void
    {
        [$quiz] = $this->quizWithQuestions(4);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}/questions/bulk-settings", ['points' => 2, 'pp_fine' => 3])
            ->assertOk()
            ->assertJson(['updated' => 4, 'quiz_total_score' => 8]);

        $this->assertEquals(8, $quiz->fresh()->total_score);
        $this->assertSame(4, $quiz->questions()->where('points', 2)->where('pp_fine', 3)->count());
    }

    public function test_quiz_points_change_rescores_results_without_closing_open_attempts(): void
    {
        [$quiz, $question, $right] = $this->quizWithQuestions(2);

        $result = CourseQuizResult::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'quiz_id' => $quiz->id,
            'status' => 1,
            'started_at' => now(),
        ]);
        UserAnswerQuestion::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'quiz_id' => $quiz->id,
            'question_id' => $question->id,
            'answer_id' => $right->id,
            'correct_option_id' => $right->id,
            'points' => 1,
        ]);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}/questions/bulk-settings", ['points' => 5])
            ->assertOk();

        $this->assertEquals(5, UserAnswerQuestion::firstOrFail()->points);
        $fresh = $result->fresh();
        $this->assertEquals(5, $fresh->score);
        $this->assertEquals(50, round($fresh->percentage));
        $this->assertSame(1, (int) $fresh->status, 'an attempt still in progress must not be closed');
    }

    public function test_student_cannot_change_quiz_set(): void
    {
        [$quiz] = $this->quizWithQuestions(1);

        $this->actingAs($this->student, 'api')
            ->patchJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}/questions/bulk-settings", ['points' => 100])
            ->assertStatus(403);
    }

    public function test_quiz_must_belong_to_course_in_url(): void
    {
        [$quiz] = $this->quizWithQuestions(1);
        $otherCourse = Course::factory()->create(['user_id' => $this->teacher->id]);

        $this->actingAs($this->teacher, 'api')
            ->patchJson("/api/courses/{$otherCourse->id}/quizzes/{$quiz->id}/questions/bulk-settings", ['points' => 2])
            ->assertStatus(404);
    }

    // ── Quiz question CRUD is for managers only ──────────────────────────

    public function test_student_cannot_create_update_or_delete_quiz_questions(): void
    {
        [$quiz, $question] = $this->quizWithQuestions(1);
        $base = "/api/courses/{$this->course->id}/quizzes/{$quiz->id}/questions";

        $this->actingAs($this->student, 'api')
            ->postJson($base, ['text' => 'hack', 'points' => 1])
            ->assertStatus(403);

        $this->actingAs($this->student, 'api')
            ->putJson("{$base}/{$question->id}", ['text' => 'hack', 'points' => 999])
            ->assertStatus(403);

        $this->actingAs($this->student, 'api')
            ->deleteJson("{$base}/{$question->id}")
            ->assertStatus(403);

        $this->assertSame('QQ0', $question->fresh()->text);
    }

    public function test_teacher_can_still_update_quiz_question(): void
    {
        [$quiz, $question] = $this->quizWithQuestions(1);

        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}/questions/{$question->id}", ['text' => 'Edited', 'points' => 2])
            ->assertOk();

        $this->assertSame('Edited', $question->fresh()->text);
    }
}
