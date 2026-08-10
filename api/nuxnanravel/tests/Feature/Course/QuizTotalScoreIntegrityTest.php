<?php

namespace Tests\Feature\Course;

use App\Constants\QuizConstants;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswerQuestion;
use App\Services\CourseScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuizTotalScoreIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfect_score_passes_even_when_total_score_counter_is_zero()
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $teacher->id]);

        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);

        $quiz = CourseQuiz::forceCreate([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'title' => 'Test Quiz',
            'passing_score' => 50,
            'total_score' => 0,
            'total_questions' => 0,
        ]);

        Question::$skipQuizCounterSync = true;
        for ($i = 0; $i < 5; $i++) {
            Question::forceCreate([
                'questionable_id' => $quiz->id,
                'questionable_type' => CourseQuiz::class,
                'course_id' => $course->id,
                'user_id' => $teacher->id,
                'text' => "Question $i",
                'points' => 1,
            ]);
        }
        Question::$skipQuizCounterSync = false;

        DB::table('course_quizzes')->where('id', $quiz->id)->update([
            'total_score' => 0,
            'total_questions' => 0,
        ]);

        $result = CourseQuizResult::forceCreate([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'status' => 0,
        ]);

        $questions = Question::where('questionable_id', $quiz->id)->get();
        foreach ($questions as $q) {
            UserAnswerQuestion::forceCreate([
                'user_id' => $student->id,
                'question_id' => $q->id,
                'quiz_id' => $quiz->id,
                'course_id' => $course->id,
                'correct_option_id' => 1,
                'answer_id' => 1,
                'points' => 1,
            ]);
        }

        $this->actingAs($student, 'api');

        Queue::fake();

        $response = $this->putJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/results/{$result->id}", [
            'finalize' => true,
        ]);

        $response->assertStatus(200);

        $result->refresh();

        $this->assertEquals(100, $result->percentage);
        $this->assertEquals(QuizConstants::STATUS_PASSED, $result->status);
    }

    public function test_question_observer_keeps_quiz_counters_in_sync()
    {
        $teacher = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);

        $quiz = CourseQuiz::forceCreate([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'title' => 'Test Quiz',
            'passing_score' => 50,
            'total_score' => 0,
            'total_questions' => 0,
        ]);

        $questions = [];
        for ($i = 0; $i < 3; $i++) {
            $questions[] = Question::create([
                'questionable_id' => $quiz->id,
                'questionable_type' => CourseQuiz::class,
                'course_id' => $course->id,
                'user_id' => $teacher->id,
                'text' => "Question $i",
                'points' => 2,
            ]);
        }

        $quiz->refresh();
        $this->assertEquals(6, $quiz->total_score);
        $this->assertEquals(3, $quiz->total_questions);

        $questions[0]->update(['points' => 5]);
        $quiz->refresh();
        $this->assertEquals(9, $quiz->total_score);
        $this->assertEquals(3, $quiz->total_questions);

        $questions[1]->delete();
        $quiz->refresh();
        $this->assertEquals(7, $quiz->total_score);
        $this->assertEquals(2, $quiz->total_questions);
    }

    public function test_course_score_service_uses_real_question_points_when_counter_is_zero()
    {
        $teacher = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);

        $quiz = CourseQuiz::forceCreate([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'title' => 'Test Quiz',
            'passing_score' => 50,
            'total_score' => 0,
            'total_questions' => 0,
        ]);

        Question::$skipQuizCounterSync = true;
        Question::forceCreate([
            'questionable_id' => $quiz->id,
            'questionable_type' => CourseQuiz::class,
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'text' => 'Question',
            'points' => 10,
        ]);
        Question::$skipQuizCounterSync = false;

        DB::table('course_quizzes')->where('id', $quiz->id)->update([
            'total_score' => 0,
            'total_questions' => 0,
        ]);

        $service = app(CourseScoreService::class);
        $struct = $service->getCourseStructure($course->id);

        $this->assertEquals(10, $struct['quizMax']);
    }
}
