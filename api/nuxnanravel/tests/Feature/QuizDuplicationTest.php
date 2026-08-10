<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizDuplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @return array{0: User, 1: Course, 2: CourseQuiz, 3: Question}
     */
    private function setUpQuizWithQuestion(): array
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $quiz = CourseQuiz::factory()->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);

        $question = Question::create([
            'questionable_type' => 'App\Models\CourseQuiz',
            'questionable_id' => $quiz->id,
            'course_id' => $course->id,
            'user_id' => $user->id,
            'text' => 'คำถามต้นฉบับ',
            'points' => 2,
        ]);

        $option = QuestionOption::create([
            'optionable_type' => 'App\Models\Question',
            'optionable_id' => $question->id,
            'text' => 'ตัวเลือกที่ถูก',
            'is_correct' => 1,
            'position' => 1,
        ]);

        $question->update(['correct_option_id' => $option->id, 'correct_answers' => $option->id]);

        return [$user, $course, $quiz, $question];
    }

    public function test_duplicated_questions_are_retagged_to_the_destination_course()
    {
        [$user, , $quiz] = $this->setUpQuizWithQuestion();

        $targetCourse = Course::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->postJson("/api/quizzes/{$quiz->id}/duplicate", [
            'course_id' => $targetCourse->id,
            'title' => 'สำเนา',
        ]);

        $response->assertOk();

        $newQuiz = CourseQuiz::where('course_id', $targetCourse->id)->firstOrFail();
        $this->assertSame($user->id, $newQuiz->user_id);

        $newQuestions = Question::where('questionable_type', 'App\Models\CourseQuiz')
            ->where('questionable_id', $newQuiz->id)
            ->get();

        $this->assertCount(1, $newQuestions);
        $this->assertSame($targetCourse->id, $newQuestions->first()->course_id);
        $this->assertSame($user->id, $newQuestions->first()->user_id);

        // No question may claim a course other than the one holding its quiz.
        $this->assertSame(0, Question::where('questionable_type', 'App\Models\CourseQuiz')
            ->whereIn('questionable_id', CourseQuiz::pluck('id'))
            ->get()
            ->filter(fn ($q) => $q->course_id !== CourseQuiz::find($q->questionable_id)->course_id)
            ->count());
    }

    public function test_duplicated_quiz_keeps_the_correct_answer_key()
    {
        [$user, , $quiz] = $this->setUpQuizWithQuestion();

        $targetCourse = Course::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'api')->postJson("/api/quizzes/{$quiz->id}/duplicate", [
            'course_id' => $targetCourse->id,
        ])->assertOk();

        $newQuiz = CourseQuiz::where('course_id', $targetCourse->id)->firstOrFail();
        $newQuestion = Question::where('questionable_type', 'App\Models\CourseQuiz')
            ->where('questionable_id', $newQuiz->id)
            ->firstOrFail();

        $correctOption = $newQuestion->options()->where('is_correct', 1)->firstOrFail();

        $this->assertSame($correctOption->id, $newQuestion->correct_option_id);
        $this->assertNotSame($quiz->id, $newQuiz->id);
    }

    public function test_a_stranger_cannot_duplicate_someone_elses_quiz()
    {
        [, , $quiz] = $this->setUpQuizWithQuestion();

        $stranger = User::factory()->create();
        $strangerCourse = Course::factory()->create(['user_id' => $stranger->id]);

        $this->actingAs($stranger, 'api')->postJson("/api/quizzes/{$quiz->id}/duplicate", [
            'course_id' => $strangerCourse->id,
        ])->assertForbidden();

        $this->assertSame(0, CourseQuiz::where('course_id', $strangerCourse->id)->count());
    }

    public function test_a_quiz_cannot_be_duplicated_into_a_course_the_user_does_not_own()
    {
        [$user, , $quiz] = $this->setUpQuizWithQuestion();

        $stranger = User::factory()->create();
        $strangerCourse = Course::factory()->create(['user_id' => $stranger->id]);

        $this->actingAs($user, 'api')->postJson("/api/quizzes/{$quiz->id}/duplicate", [
            'course_id' => $strangerCourse->id,
        ])->assertForbidden();

        $this->assertSame(0, CourseQuiz::where('course_id', $strangerCourse->id)->count());
    }
}
