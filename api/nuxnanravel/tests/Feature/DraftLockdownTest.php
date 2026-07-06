<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftLockdownTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create();
        $this->course = Course::factory()->create();

        // Enroll student in course
        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => 1, // Student
            'status' => 1, // Active
        ]);
    }

    /**
     * Test Lesson visibility and interaction.
     */
    public function test_student_cannot_access_draft_lesson(): void
    {
        $lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'publication_status' => 'draft',
        ]);

        // 1. Show Lesson
        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons/{$lesson->id}");

        // Note: CourseLessonController::show currently might return 401 if not enough points,
        // or 404 if not found. My new service should trigger 404 for drafts in show().
        $response->assertStatus(404);

        // 2. Start Progress
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/start");
        $response->assertStatus(403);
    }

    /**
     * Test Assignment visibility.
     */
    public function test_student_cannot_access_draft_assignment(): void
    {
        $assignment = Assignment::factory()->create([
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $this->course->id,
            'status' => 0, // Draft
        ]);

        // 1. Show Assignment
        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/courses/{$this->course->id}/assignments/{$assignment->id}");
        $response->assertStatus(404);

        // 2. Submit Answer
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/assignments/{$assignment->id}/answers", [
                'content' => 'Test answer',
                'course_id' => $this->course->id,
            ]);
        $response->assertStatus(403);
    }

    /**
     * Test Quiz visibility.
     */
    public function test_student_cannot_access_inactive_quiz(): void
    {
        $quiz = CourseQuiz::factory()->create([
            'course_id' => $this->course->id,
            'is_active' => 0,
        ]);

        // 1. Show Quiz
        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}");
        $response->assertStatus(404);

        // 2. Start Attempt
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/courses/{$this->course->id}/quizzes/{$quiz->id}/results");
        $response->assertStatus(403);
    }

    /**
     * Test Admin still has access.
     */
    public function test_admin_can_access_draft_content(): void
    {
        $admin = User::factory()->create();
        $this->course->update(['user_id' => $admin->id]); // Make owner

        $lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'publication_status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons/{$lesson->id}");

        $response->assertStatus(200);
    }
}
