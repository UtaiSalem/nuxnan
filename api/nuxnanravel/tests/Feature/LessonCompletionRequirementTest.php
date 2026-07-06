<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCompletionRequirementTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;

    protected $student;

    protected $course;

    protected $lesson;

    protected $assignment;

    protected $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->teacher->id]);

        // Add student to course
        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 'active',
        ]);

        // Add teacher to course as admin
        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->lesson = Lesson::create([
            'course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'title' => 'Test Lesson',
            'publication_status' => Lesson::STATUS_PUBLISHED,
            'access_type' => Lesson::ACCESS_FREE,
            'require_completion_before_exercises' => true,
        ]);

        $this->assignment = $this->lesson->assignments()->create([
            'title' => 'Test Assignment',
            'description' => 'Assignment content',
            'status' => 1,
        ]);

        $this->question = $this->lesson->questions()->create([
            'user_id' => $this->teacher->id,
            'course_id' => $this->course->id,
            'text' => 'Test Question',
            'points' => 10,
        ]);

        $this->option = QuestionOption::create([
            'optionable_type' => Question::class,
            'optionable_id' => $this->question->id,
            'text' => 'Correct Answer',
            'is_correct' => true,
        ]);
    }

    public function test_student_cannot_submit_assignment_if_lesson_not_completed(): void
    {
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/assignments/{$this->assignment->id}/answers", [
                'course_id' => $this->course->id,
                'content' => 'My answer',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'LESSON_COMPLETION_REQUIRED',
            ]);
    }

    public function test_student_can_submit_assignment_if_lesson_completed(): void
    {
        // Mark as completed
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/assignments/{$this->assignment->id}/answers", [
                'course_id' => $this->course->id,
                'content' => 'My answer',
            ]);

        // Backend might return 200 or 201
        $response->assertStatus(200);
    }

    public function test_student_can_submit_assignment_if_requirement_disabled(): void
    {
        $this->lesson->update(['require_completion_before_exercises' => false]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/assignments/{$this->assignment->id}/answers", [
                'course_id' => $this->course->id,
                'content' => 'My answer',
            ]);

        $response->assertStatus(200);
    }

    public function test_student_cannot_answer_lesson_question_if_lesson_not_completed(): void
    {
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}/answer", [
                'answer_id' => $this->option->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'LESSON_COMPLETION_REQUIRED',
            ]);
    }

    public function test_student_can_answer_lesson_question_if_lesson_completed(): void
    {
        // Mark as completed
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/questions/{$this->question->id}/answer", [
                'answer_id' => $this->option->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_admin_can_bypass_completion_requirement(): void
    {
        $response = $this->actingAs($this->teacher, 'api')
            ->postJson("/api/assignments/{$this->assignment->id}/answers", [
                'course_id' => $this->course->id,
                'content' => 'Admin answer',
            ]);

        // Admin can always submit
        $response->assertStatus(200);
    }
}
