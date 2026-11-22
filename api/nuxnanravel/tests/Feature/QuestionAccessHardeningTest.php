<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PointsTransaction;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\UserAnswerQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * - LessonQuestionController: only course admins write questions; students
 *   only read questions of a visible lesson.
 * - Answer-key fields (is_correct, explanation) stay hidden from students.
 * - Editing a quiz answer charges pp_fine through the points ledger.
 */
class QuestionAccessHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected Course $course;

    protected Lesson $lesson;

    protected Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->teacher->id]);

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 'active',
        ]);

        $this->lesson = $this->makeLesson(Lesson::STATUS_PUBLISHED);
        $this->markLessonRead($this->student, $this->lesson);
        $this->question = $this->makeLessonQuestion($this->lesson);
    }

    private function makeLesson(string $status): Lesson
    {
        return Lesson::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'title' => 'Lesson '.$status,
            'publication_status' => $status,
            'access_type' => Lesson::ACCESS_FREE,
            'require_completion_before_exercises' => false,
        ]);
    }

    private function makeLessonQuestion(Lesson $lesson): Question
    {
        $question = $lesson->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $this->course->id,
            'text' => 'Q',
            'points' => 10,
            'explanation' => 'Because B is right',
        ]);

        QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $question->id,
            'text' => 'B',
            'is_correct' => true,
            'explanation' => 'Correct: B',
        ]);

        return $question;
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

    // ── Lesson questions: write access ────────────────────────────────────

    public function test_student_cannot_create_lesson_question(): void
    {
        $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions", ['text' => 'hack', 'points' => 999])
            ->assertStatus(403);

        $this->assertDatabaseMissing('questions', ['text' => 'hack']);
    }

    public function test_student_cannot_update_lesson_question(): void
    {
        $this->actingAs($this->student, 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", ['text' => 'hack', 'points' => 999])
            ->assertStatus(403);

        $this->assertSame('Q', $this->question->fresh()->text);
    }

    public function test_student_cannot_delete_lesson_question(): void
    {
        $this->actingAs($this->student, 'api')
            ->deleteJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}")
            ->assertStatus(403);

        $this->assertNotNull($this->question->fresh());
    }

    public function test_teacher_can_still_update_lesson_question(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", ['text' => 'Edited', 'points' => 5])
            ->assertOk();

        $this->assertSame('Edited', $this->question->fresh()->text);
    }

    public function test_teacher_cannot_update_question_through_another_lesson(): void
    {
        $otherLesson = $this->makeLesson(Lesson::STATUS_PUBLISHED);

        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/lessons/{$otherLesson->id}/questions/{$this->question->id}", ['text' => 'x', 'points' => 1])
            ->assertStatus(404);
    }

    // ── Lesson questions: read access ─────────────────────────────────────

    public function test_student_cannot_list_questions_of_draft_lesson(): void
    {
        $draft = $this->makeLesson(Lesson::STATUS_DRAFT);
        $this->makeLessonQuestion($draft);

        $this->actingAs($this->student, 'api')
            ->getJson("/api/lessons/{$draft->id}/questions")
            ->assertStatus(404);
    }

    public function test_teacher_can_list_questions_of_draft_lesson(): void
    {
        $draft = $this->makeLesson(Lesson::STATUS_DRAFT);
        $this->makeLessonQuestion($draft);

        $this->actingAs($this->teacher, 'api')
            ->getJson("/api/lessons/{$draft->id}/questions")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_student_does_not_receive_answer_key_or_explanations(): void
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/questions")
            ->assertOk();

        $question = $response->json('data.0');
        $this->assertArrayNotHasKey('explanation', $question);
        $this->assertArrayNotHasKey('correct_option_id', $question);
        $this->assertArrayNotHasKey('is_correct', $question['options'][0]);
        $this->assertArrayNotHasKey('explanation', $question['options'][0]);
    }

    public function test_question_author_receives_answer_key_and_explanations(): void
    {
        $response = $this->actingAs($this->teacher, 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/questions")
            ->assertOk();

        $this->assertSame('Because B is right', $response->json('data.0.explanation'));
        $this->assertTrue((bool) $response->json('data.0.options.0.is_correct'));
        $this->assertSame('Correct: B', $response->json('data.0.options.0.explanation'));
    }

    public function test_show_rejects_question_from_another_lesson(): void
    {
        $otherLesson = $this->makeLesson(Lesson::STATUS_PUBLISHED);
        // The student has read the other lesson, so the request reaches the lesson/question mismatch check (404) instead of stopping at the read gate.
        $this->markLessonRead($this->student, $otherLesson);

        $this->actingAs($this->student, 'api')
            ->getJson("/api/lessons/{$otherLesson->id}/questions/{$this->question->id}")
            ->assertStatus(404);
    }

    // ── Quiz answer edit: pp_fine via ledger ──────────────────────────────

    /**
     * @return array{0: CourseQuiz, 1: Question, 2: QuestionOption, 3: QuestionOption}
     */
    private function makeQuizWithFine(int $ppFine): array
    {
        $quiz = CourseQuiz::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        $question = $quiz->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $this->course->id,
            'text' => 'Quiz Q',
            'points' => 10,
            'pp_fine' => $ppFine,
        ]);

        $right = QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $question->id,
            'text' => 'Right',
            'is_correct' => true,
        ]);

        $wrong = QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $question->id,
            'text' => 'Wrong',
            'is_correct' => false,
        ]);

        // Grading reads is_correct; the answer row mirrors correct_option_id.
        $question->update(['correct_option_id' => $right->id]);

        return [$quiz, $question->fresh(), $right, $wrong];
    }

    private function answerQuiz(CourseQuiz $quiz, Question $question, int $optionId): UserAnswerQuestion
    {
        $this->actingAs($this->student, 'api')
            ->postJson("/api/quizs/{$quiz->id}/questions/{$question->id}/answers", ['answer_id' => $optionId, 'course_id' => $this->course->id])
            ->assertSuccessful();

        return UserAnswerQuestion::where('user_id', $this->student->id)
            ->where('question_id', $question->id)
            ->firstOrFail();
    }

    public function test_editing_quiz_answer_charges_pp_fine_through_ledger(): void
    {
        [$quiz, $question, $right, $wrong] = $this->makeQuizWithFine(5);
        $this->student->update(['pp' => 20]);

        $answer = $this->answerQuiz($quiz, $question, $wrong->id);

        $this->actingAs($this->student, 'api')
            ->putJson("/api/quizs/{$quiz->id}/questions/{$question->id}/answers/{$answer->id}", ['answer_id' => $right->id])
            ->assertOk()
            ->assertJson(['pp_deducted' => 5, 'user_pp' => 15]);

        $this->assertEquals(15, $this->student->fresh()->pp);
        $this->assertSame(1, PointsTransaction::where('user_id', $this->student->id)
            ->where('source_type', 'quiz_answer_edit')
            ->where('transaction_type', 'spend')
            ->where('amount', 5)
            ->count());
        $this->assertSame($right->id, (int) $answer->fresh()->answer_id);
    }

    public function test_editing_quiz_answer_without_enough_points_changes_nothing(): void
    {
        [$quiz, $question, $right, $wrong] = $this->makeQuizWithFine(5);
        $this->student->update(['pp' => 3]);

        $answer = $this->answerQuiz($quiz, $question, $wrong->id);

        $this->actingAs($this->student, 'api')
            ->putJson("/api/quizs/{$quiz->id}/questions/{$question->id}/answers/{$answer->id}", ['answer_id' => $right->id])
            ->assertStatus(422)
            ->assertJson(['required_pp' => 5]);

        $this->assertEquals(3, $this->student->fresh()->pp);
        $this->assertSame(0, PointsTransaction::where('source_type', 'quiz_answer_edit')->count());
        $this->assertSame($wrong->id, (int) $answer->fresh()->answer_id);
    }
}
