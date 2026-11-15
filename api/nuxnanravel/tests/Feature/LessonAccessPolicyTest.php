<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Models\LessonAnswerQuestion;
use App\Models\PointsTransaction;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\UserAnswerQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Owner policy (2026-11-15):
 *  - reading a lesson is free for everyone;
 *  - answering lesson questions and quizzes is for approved course members;
 *  - changing an answer that was already submitted costs the question's pp_fine.
 */
class LessonAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $member;

    protected User $outsider;

    protected Course $course;

    protected Lesson $lesson;

    protected Question $question;

    protected QuestionOption $right;

    protected QuestionOption $wrong;

    protected QuestionOption $wrong2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create();
        $this->member = User::factory()->create(['pp' => 20]);
        $this->outsider = User::factory()->create(['pp' => 20]);

        $this->course = Course::factory()->create(['user_id' => $this->teacher->id]);

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->member->id,
            'course_member_status' => 1,
        ]);

        $this->lesson = Lesson::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'title' => 'Lesson',
            'publication_status' => Lesson::STATUS_PUBLISHED,
            'access_type' => Lesson::ACCESS_FREE,
            'point_tuition_fee' => 7,
            'require_completion_before_exercises' => false,
        ]);

        $this->question = $this->lesson->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $this->course->id,
            'text' => 'Q',
            'points' => 10,
            'pp_fine' => 5,
        ]);

        $this->right = $this->option($this->question, 'Right', true);
        $this->wrong = $this->option($this->question, 'Wrong', false);
        $this->wrong2 = $this->option($this->question, 'Wrong 2', false);
        $this->question->update(['correct_option_id' => $this->right->id]);
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

    private function answerLesson(User $user, int $optionId)
    {
        return $this->actingAs($user, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}/answer", [
                'answer_id' => $optionId,
            ]);
    }

    // ── Reading is free ──────────────────────────────────────────────────

    public function test_viewing_a_lesson_never_charges_points(): void
    {
        $url = "/api/courses/{$this->course->id}/lessons/{$this->lesson->id}";
        $this->actingAs($this->outsider, 'api')->getJson($url)->assertOk();
        $this->actingAs($this->outsider, 'api')->getJson($url)->assertOk()->assertJsonPath('access.is_locked', false);

        $this->assertEquals(20, $this->outsider->fresh()->pp);
    }

    public function test_legacy_paid_lesson_is_free_to_read_and_unlock(): void
    {
        $this->lesson->update(['access_type' => Lesson::ACCESS_POINTS, 'point_tuition_fee' => 7]);

        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons/{$this->lesson->id}")
            ->assertOk()
            ->assertJsonPath('access.is_locked', false);

        $this->actingAs($this->outsider, 'api')
            ->postJson("/api/courses/{$this->course->id}/lessons/{$this->lesson->id}/unlock")
            ->assertOk();

        $this->assertEquals(20, $this->outsider->fresh()->pp);
    }

    public function test_teacher_cannot_put_a_price_on_a_lesson(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->postJson("/api/courses/{$this->course->id}/lessons/{$this->lesson->id}", [
                '_method' => 'PUT',
                'title' => 'Lesson',
                // the real form always sends these (the endpoint reads them unguarded)
                'description' => '',
                'content' => '',
                'youtube_url' => '',
                'status' => 1,
                'access_type' => 'points',
                'point_tuition_fee' => 50,
            ])
            ->assertSuccessful();

        $fresh = $this->lesson->fresh();
        $this->assertSame(Lesson::ACCESS_FREE, $fresh->access_type);
        $this->assertEquals(0, $fresh->point_tuition_fee);
    }

    public function test_non_member_reads_lesson_but_gets_no_questions(): void
    {
        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons/{$this->lesson->id}")
            ->assertOk()
            ->assertJsonPath('lesson.can_answer_questions', false)
            ->assertJsonCount(0, 'lesson.questions');

        $this->actingAs($this->outsider, 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/questions")
            ->assertStatus(403);
    }

    public function test_member_gets_questions_with_the_lesson(): void
    {
        $this->actingAs($this->member, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons/{$this->lesson->id}")
            ->assertOk()
            ->assertJsonPath('lesson.can_answer_questions', true)
            ->assertJsonCount(1, 'lesson.questions');
    }

    // ── Answering is members-only ────────────────────────────────────────

    public function test_non_member_cannot_answer_lesson_question(): void
    {
        $this->answerLesson($this->outsider, $this->right->id)
            ->assertStatus(403)
            ->assertJson(['code' => 'NOT_COURSE_MEMBER']);

        $this->assertDatabaseCount('lesson_answer_questions', 0);
    }

    public function test_pending_member_cannot_answer_lesson_question(): void
    {
        $pending = User::factory()->create();
        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $pending->id,
            'course_member_status' => 0,
        ]);

        $this->answerLesson($pending, $this->right->id)->assertStatus(403);
    }

    public function test_member_first_answer_is_free(): void
    {
        $this->answerLesson($this->member, $this->wrong->id)
            ->assertOk()
            ->assertJson(['is_correct' => false, 'pp_deducted' => 0]);

        $this->assertEquals(20, $this->member->fresh()->pp);
    }

    // ── Changing an answer costs pp_fine ─────────────────────────────────

    public function test_changing_lesson_answer_charges_pp_fine_through_ledger(): void
    {
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();

        $this->answerLesson($this->member, $this->right->id)
            ->assertOk()
            ->assertJson(['is_correct' => true, 'pp_deducted' => 5, 'user_pp' => 15]);

        $this->assertEquals(15, $this->member->fresh()->pp);
        $this->assertSame(1, PointsTransaction::where('user_id', $this->member->id)
            ->where('source_type', 'lesson_answer_edit')
            ->where('transaction_type', 'spend')
            ->where('amount', 5)
            ->count());

        $answer = LessonAnswerQuestion::where('user_id', $this->member->id)->firstOrFail();
        $this->assertSame($this->right->id, (int) $answer->answer_id);
    }

    public function test_resubmitting_the_same_option_is_free(): void
    {
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();
        $this->answerLesson($this->member, $this->wrong->id)->assertOk()->assertJson(['pp_deducted' => 0]);

        $this->assertEquals(20, $this->member->fresh()->pp);
    }

    public function test_changing_answer_without_enough_points_changes_nothing(): void
    {
        $this->member->update(['pp' => 3]);
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();

        $this->answerLesson($this->member, $this->right->id)
            ->assertStatus(422)
            ->assertJson(['code' => 'INSUFFICIENT_PP', 'required_pp' => 5]);

        $this->assertEquals(3, $this->member->fresh()->pp);
        $this->assertSame($this->wrong->id, (int) LessonAnswerQuestion::firstOrFail()->answer_id);
    }

    public function test_zero_fine_makes_changes_free(): void
    {
        $this->question->update(['pp_fine' => 0]);

        $this->answerLesson($this->member, $this->wrong->id)->assertOk();
        $this->answerLesson($this->member, $this->wrong2->id)->assertOk()->assertJson(['pp_deducted' => 0]);

        $this->assertEquals(20, $this->member->fresh()->pp);
    }

    public function test_teacher_can_set_pp_fine_on_lesson_question(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions", ['text' => 'New', 'points' => 1, 'pp_fine' => 3])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['text' => 'New', 'pp_fine' => 3]);

        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", ['text' => 'Q', 'points' => 10])
            ->assertOk();

        $this->assertSame(5, $this->question->fresh()->pp_fine, 'pp_fine must survive an update that omits it');
    }

    // ── Paid answer reveal ───────────────────────────────────────────────

    private function reveal(User $user)
    {
        return $this->actingAs($user, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}/reveal");
    }

    public function test_reveal_requires_answering_first(): void
    {
        $this->reveal($this->member)
            ->assertStatus(422)
            ->assertJson(['code' => 'ANSWER_FIRST']);

        $this->assertEquals(20, $this->member->fresh()->pp);
    }

    public function test_every_reveal_is_charged_and_returns_readable_key_only(): void
    {
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();

        $first = $this->reveal($this->member)
            ->assertOk()
            ->assertJson([
                'pp_deducted' => 5,
                'user_pp' => 15,
                'revealed' => ['correct_option_text' => 'Right'],
            ]);

        // Reading material only — no option id the UI could highlight.
        $this->assertArrayNotHasKey('correct_option_id', $first->json('revealed'));

        // Revealing again costs again — nothing is remembered.
        $this->reveal($this->member)->assertOk()->assertJson(['pp_deducted' => 5, 'user_pp' => 10]);

        $this->assertEquals(10, $this->member->fresh()->pp);
        $this->assertSame(2, PointsTransaction::where('user_id', $this->member->id)
            ->where('source_type', 'lesson_answer_reveal')
            ->count());
    }

    public function test_question_list_never_carries_the_key_after_a_reveal(): void
    {
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();
        $this->reveal($this->member)->assertOk();

        $question = $this->actingAs($this->member, 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/questions")
            ->assertOk()
            ->json('data.0');

        $this->assertArrayNotHasKey('revealed', $question);
        $this->assertArrayNotHasKey('correct_option_id', $question);
        $this->assertArrayNotHasKey('is_correct', $question['options'][0]);
    }

    public function test_reveal_without_enough_points_reveals_nothing(): void
    {
        $this->member->update(['pp' => 3]);
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();

        $this->reveal($this->member)
            ->assertStatus(422)
            ->assertJson(['code' => 'INSUFFICIENT_PP'])
            ->assertJsonMissingPath('revealed');

        $this->assertEquals(3, $this->member->fresh()->pp);
    }

    public function test_non_member_cannot_reveal(): void
    {
        $this->reveal($this->outsider)->assertStatus(403)->assertJson(['code' => 'NOT_COURSE_MEMBER']);
    }

    public function test_after_reveal_learner_may_still_change_answer_and_score(): void
    {
        $this->answerLesson($this->member, $this->wrong->id)->assertOk();
        $this->reveal($this->member)->assertOk();

        $this->answerLesson($this->member, $this->right->id)
            ->assertOk()
            ->assertJson(['is_correct' => true, 'points' => 10, 'pp_deducted' => 5]);

        $this->assertEquals(10, $this->member->fresh()->pp);
    }

    // ── Explanation in the lesson question form ──────────────────────────

    public function test_teacher_writes_explanation_and_learner_reads_it_on_reveal(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", [
                'text' => 'Q',
                'points' => 10,
                'explanation' => 'Because the right answer is right',
            ])
            ->assertOk();

        $this->answerLesson($this->member, $this->wrong->id)->assertOk();

        $this->reveal($this->member)
            ->assertOk()
            ->assertJsonPath('revealed.explanation', 'Because the right answer is right');
    }

    public function test_teacher_can_create_question_with_explanation(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions", [
                'text' => 'New',
                'points' => 1,
                'explanation' => 'Why',
            ])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['text' => 'New', 'explanation' => 'Why']);
    }

    public function test_update_without_explanation_keeps_question_and_option_explanations(): void
    {
        $this->question->update(['explanation' => 'Keep me']);
        $this->right->update(['explanation' => 'Option note']);

        // Same shape the form sends when the editor could not load the old
        // explanation (co-teacher): no `explanation` keys at all.
        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", [
                'text' => 'Q',
                'points' => 10,
                'options' => [
                    ['id' => $this->right->id, 'text' => 'Right', 'is_correct' => 'true'],
                    ['id' => $this->wrong->id, 'text' => 'Wrong', 'is_correct' => 'false'],
                    ['id' => $this->wrong2->id, 'text' => 'Wrong 2', 'is_correct' => 'false'],
                ],
            ])
            ->assertOk();

        $this->assertSame('Keep me', $this->question->fresh()->explanation);
        $this->assertSame('Option note', $this->right->fresh()->explanation);
    }

    private function coTeacher(): User
    {
        $coTeacher = User::factory()->create();
        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $coTeacher->id,
            'course_member_status' => 1,
            'role' => 4, // course admin (Course::isAdmin: role 4 + status 1)
            'status' => 1,
        ]);

        return $coTeacher;
    }

    public function test_co_teacher_sees_answer_key_of_questions_they_did_not_write(): void
    {
        $this->question->update(['explanation' => 'Why']);

        $question = $this->actingAs($this->coTeacher(), 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/questions")
            ->assertOk()
            ->json('data.0');

        $this->assertSame($this->right->id, $question['correct_option_id']);
        $this->assertSame('Why', $question['explanation']);
        $right = collect($question['options'])->firstWhere('id', $this->right->id);
        $this->assertTrue((bool) $right['is_correct']);
    }

    public function test_member_still_never_sees_answer_key(): void
    {
        $question = $this->actingAs($this->member, 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/questions")
            ->assertOk()
            ->json('data.0');

        $this->assertArrayNotHasKey('correct_option_id', $question);
        $this->assertArrayNotHasKey('is_correct', $question['options'][0]);
    }

    public function test_update_without_is_correct_keeps_the_correct_option(): void
    {
        $this->actingAs($this->coTeacher(), 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", [
                'text' => 'Q',
                'points' => 10,
                'options' => [
                    ['id' => $this->right->id, 'text' => 'Right'],
                    ['id' => $this->wrong->id, 'text' => 'Wrong'],
                    ['id' => $this->wrong2->id, 'text' => 'Wrong 2'],
                ],
            ])
            ->assertOk();

        $this->assertTrue((bool) $this->right->fresh()->is_correct);
    }

    public function test_explanation_is_capped(): void
    {
        $this->actingAs($this->teacher, 'api')
            ->putJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}", [
                'text' => 'Q',
                'points' => 10,
                'explanation' => str_repeat('ก', 5001),
            ])
            ->assertStatus(422);
    }

    // ── Quizzes ──────────────────────────────────────────────────────────

    private function makeQuiz(): array
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
        ]);

        $right = $this->option($question, 'Right', true);
        $question->update(['correct_option_id' => $right->id]);

        return [$quiz, $question->fresh(), $right];
    }

    public function test_non_member_cannot_start_quiz(): void
    {
        [$quiz] = $this->makeQuiz();

        $this->actingAs($this->outsider, 'api')
            ->postJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}/results", [])
            ->assertStatus(403)
            ->assertJson(['code' => 'NOT_COURSE_MEMBER']);
    }

    public function test_non_member_cannot_answer_quiz_question(): void
    {
        [$quiz, $question, $right] = $this->makeQuiz();

        $this->actingAs($this->outsider, 'api')
            ->postJson("/api/quizs/{$quiz->id}/questions/{$question->id}/answers", [
                'answer_id' => $right->id,
                'course_id' => $this->course->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('user_answer_questions', 0);
    }

    public function test_quiz_answer_ignores_forged_course_id(): void
    {
        [$quiz, $question, $right] = $this->makeQuiz();
        $otherCourse = Course::factory()->create();

        $this->actingAs($this->member, 'api')
            ->postJson("/api/quizs/{$quiz->id}/questions/{$question->id}/answers", [
                'answer_id' => $right->id,
                'course_id' => $otherCourse->id,
            ])
            ->assertSuccessful();

        $this->assertSame($this->course->id, (int) UserAnswerQuestion::firstOrFail()->course_id);
    }

    public function test_quiz_rejects_question_from_another_quiz(): void
    {
        [$quiz] = $this->makeQuiz();
        [, $foreignQuestion, $foreignRight] = $this->makeQuiz();

        $this->actingAs($this->member, 'api')
            ->postJson("/api/quizs/{$quiz->id}/questions/{$foreignQuestion->id}/answers", [
                'answer_id' => $foreignRight->id,
                'course_id' => $this->course->id,
            ])
            ->assertStatus(404)
            ->assertJson(['code' => 'QUESTION_NOT_IN_QUIZ']);
    }
}
