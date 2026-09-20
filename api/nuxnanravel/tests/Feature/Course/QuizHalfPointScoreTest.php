<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizHalfPointScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_question_with_half_point()
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

        $this->actingAs($teacher, 'api');

        $response = $this->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions", [
            'text' => 'Half point question',
            'points' => 0.5,
        ]);

        $response->assertStatus(200);

        $question = Question::where('questionable_id', $quiz->id)->first();
        $this->assertNotNull($question);
        $this->assertEqualsWithDelta(0.5, (float) $question->points, 0.001);
    }

    public function test_points_must_be_multiple_of_half()
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

        $this->actingAs($teacher, 'api');

        $response = $this->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions", [
            'text' => 'Invalid point question',
            'points' => 0.3,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('points');

        $response = $this->postJson("/api/courses/{$course->id}/quizzes/{$quiz->id}/questions", [
            'text' => 'Zero point question',
            'points' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('points');
    }

    public function test_quiz_total_score_sums_half_points()
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

        Question::create([
            'questionable_id' => $quiz->id,
            'questionable_type' => CourseQuiz::class,
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'text' => 'Question 1',
            'points' => 0.5,
        ]);

        Question::create([
            'questionable_id' => $quiz->id,
            'questionable_type' => CourseQuiz::class,
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'text' => 'Question 2',
            'points' => 0.5,
        ]);

        Question::create([
            'questionable_id' => $quiz->id,
            'questionable_type' => CourseQuiz::class,
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'text' => 'Question 3',
            'points' => 1.5,
        ]);

        $quiz->refresh();
        $this->assertEqualsWithDelta(2.5, (float) $quiz->total_score, 0.001);
    }
}
