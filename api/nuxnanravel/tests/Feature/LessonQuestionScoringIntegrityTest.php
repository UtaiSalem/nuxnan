<?php

namespace Tests\Feature;

use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonAnswerQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\CourseScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\MissingValue;
use Tests\TestCase;

class LessonQuestionScoringIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;

    protected $student;

    protected $course;

    protected $lesson;

    protected $otherLesson;

    protected $question;

    protected $correctOption;

    protected $wrongOption;

    protected $foreignQuestion;

    protected $foreignCorrectOption;

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

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->lesson = $this->makeLesson('Lesson A');
        $this->otherLesson = $this->makeLesson('Lesson B');

        $this->question = $this->makeQuestion($this->lesson);
        $this->correctOption = $this->makeOption($this->question, 'Right', true);
        $this->wrongOption = $this->makeOption($this->question, 'Wrong', false);

        // A correct option living on a question of a *different* lesson.
        $this->foreignQuestion = $this->makeQuestion($this->otherLesson);
        $this->foreignCorrectOption = $this->makeOption($this->foreignQuestion, 'Foreign right', true);
    }

    private function makeLesson(string $title): Lesson
    {
        return Lesson::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'title' => $title,
            'publication_status' => Lesson::STATUS_PUBLISHED,
            'access_type' => Lesson::ACCESS_FREE,
            'require_completion_before_exercises' => false,
        ]);
    }

    private function makeQuestion(Lesson $lesson): Question
    {
        return $lesson->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $this->course->id,
            'text' => 'Q for '.$lesson->title,
            'points' => 10,
        ]);
    }

    private function makeOption(Question $question, string $text, bool $isCorrect): QuestionOption
    {
        return QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $question->id,
            'text' => $text,
            'is_correct' => $isCorrect,
        ]);
    }

    private function answer(int $lessonId, int $questionId, int $answerId)
    {
        return $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lessonId}/questions/{$questionId}/answer", [
                'answer_id' => $answerId,
            ]);
    }

    public function test_student_cannot_score_using_option_from_another_question(): void
    {
        $response = $this->answer(
            $this->lesson->id,
            $this->question->id,
            $this->foreignCorrectOption->id
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('answer_id');

        $this->assertDatabaseCount('lesson_answer_questions', 0);
    }

    public function test_answering_question_that_belongs_to_another_lesson_is_rejected(): void
    {
        $response = $this->answer(
            $this->lesson->id,
            $this->foreignQuestion->id,
            $this->foreignCorrectOption->id
        );

        $response->assertStatus(404)
            ->assertJson(['code' => 'QUESTION_NOT_IN_LESSON']);

        $this->assertDatabaseCount('lesson_answer_questions', 0);
    }

    public function test_valid_correct_answer_still_scores_and_reports_summary(): void
    {
        $response = $this->answer(
            $this->lesson->id,
            $this->question->id,
            $this->correctOption->id
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_correct' => true,
                'points' => 10,
                'quiz_summary' => [
                    'total_questions' => 1,
                    'answered_count' => 1,
                    'correct_count' => 1,
                    'earned_points' => 10,
                    'total_points' => 10,
                    'all_answered' => true,
                    'all_correct' => true,
                    'percentage' => 100,
                    'just_completed' => true,
                ],
            ]);
    }

    public function test_just_completed_only_fires_on_the_transition(): void
    {
        $this->answer($this->lesson->id, $this->question->id, $this->correctOption->id)
            ->assertJsonPath('quiz_summary.just_completed', true);

        // Re-submitting the same correct answer must not re-fire the transition.
        $this->answer($this->lesson->id, $this->question->id, $this->correctOption->id)
            ->assertJsonPath('quiz_summary.just_completed', false)
            ->assertJsonPath('quiz_summary.all_correct', true);

        $this->assertDatabaseCount('lesson_answer_questions', 1);
    }

    public function test_wrong_answer_scores_zero(): void
    {
        $this->answer($this->lesson->id, $this->question->id, $this->wrongOption->id)
            ->assertStatus(200)
            ->assertJson([
                'is_correct' => false,
                'points' => 0,
            ]);

        $this->assertSame(0, (int) LessonAnswerQuestion::first()->points);
    }

    public function test_resubmitting_updates_the_existing_row_rather_than_duplicating(): void
    {
        $this->answer($this->lesson->id, $this->question->id, $this->wrongOption->id);
        $this->answer($this->lesson->id, $this->question->id, $this->correctOption->id);

        $this->assertDatabaseCount('lesson_answer_questions', 1);
        $this->assertSame($this->correctOption->id, (int) LessonAnswerQuestion::first()->answer_id);
    }

    public function test_answer_key_is_hidden_from_students_but_visible_to_author(): void
    {
        $this->question->update(['correct_option_id' => $this->correctOption->id]);

        $asStudent = (new QuestionResource($this->question->fresh()));
        $asAuthor = (new QuestionResource($this->question->fresh()));

        $this->actingAs($this->student, 'api');
        $studentPayload = $asStudent->toArray(request());
        $this->assertInstanceOf(
            MissingValue::class,
            $studentPayload['correct_option_id'],
            'correct_option_id must not be exposed to learners'
        );

        $this->actingAs($this->teacher, 'api');
        $authorPayload = $asAuthor->toArray(request());
        $this->assertSame($this->correctOption->id, $authorPayload['correct_option_id']);
    }

    public function test_question_moved_between_lessons_is_counted_once(): void
    {
        $this->answer($this->lesson->id, $this->question->id, $this->correctOption->id)
            ->assertStatus(200);

        $this->question->update(['questionable_id' => $this->otherLesson->id]);

        $this->answer($this->otherLesson->id, $this->question->id, $this->correctOption->id)
            ->assertStatus(200);

        $this->assertDatabaseCount('lesson_answer_questions', 2);

        $studentMember = CourseMember::where('course_id', $this->course->id)
            ->where('user_id', $this->student->id)
            ->first();
        $service = app(CourseScoreService::class);

        $this->assertSame(10.0, $service->computeBreakdown($studentMember)->lessonQuestionEarned);
        $this->assertSame(
            10.0,
            $service->computeBulkBreakdown($this->course, collect([$studentMember]))[$studentMember->id]->lessonQuestionEarned
        );
    }

    public function test_question_score_uses_current_points_instead_of_stale_answer_snapshot(): void
    {
        $this->foreignQuestion->update(['points' => 0]);

        $this->answer($this->lesson->id, $this->question->id, $this->correctOption->id)
            ->assertStatus(200);

        $this->question->update(['points' => 4]);

        $this->assertSame(10, (int) LessonAnswerQuestion::first()->points);

        $studentMember = CourseMember::where('course_id', $this->course->id)
            ->where('user_id', $this->student->id)
            ->first();
        $service = app(CourseScoreService::class);
        $breakdown = $service->computeBreakdown($studentMember);

        $this->assertSame(4.0, $breakdown->lessonQuestionEarned);
        $this->assertSame(4.0, $breakdown->lessonQuestionMax);
        $this->assertSame(
            4.0,
            $service->computeBulkBreakdown($this->course, collect([$studentMember]))[$studentMember->id]->lessonQuestionEarned
        );
    }
}
