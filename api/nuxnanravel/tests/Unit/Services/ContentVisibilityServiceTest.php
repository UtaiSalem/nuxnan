<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Assignment;
use App\Models\CourseQuiz;
use App\Services\ContentVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContentVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentVisibilityService $service;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContentVisibilityService::class);
        $this->student = User::factory()->create();
    }

    // ---- Lessons ---------------------------------------------------------

    public function test_can_view_published_lesson(): void
    {
        $lesson = Lesson::factory()->create(['publication_status' => Lesson::STATUS_PUBLISHED]);
        $this->assertTrue($this->service->canStudentViewLesson($this->student, $lesson));
    }

    public function test_cannot_view_draft_lesson(): void
    {
        $lesson = Lesson::factory()->create(['publication_status' => 'draft']);
        $this->assertFalse($this->service->canStudentViewLesson($this->student, $lesson));
    }

    // ---- Assignments -----------------------------------------------------

    public function test_can_view_published_course_assignment(): void
    {
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create([
            'status' => 1,
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $course->id,
        ]);
        $this->assertTrue($this->service->canStudentViewAssignment($this->student, $assignment));
    }

    public function test_cannot_view_draft_assignment(): void
    {
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create([
            'status' => 0,
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $course->id,
        ]);
        $this->assertFalse($this->service->canStudentViewAssignment($this->student, $assignment));
    }

    public function test_can_view_published_assignment_under_published_lesson(): void
    {
        $lesson = Lesson::factory()->create(['publication_status' => Lesson::STATUS_PUBLISHED]);
        $assignment = Assignment::factory()->create([
            'status' => 1,
            'assignmentable_type' => Lesson::class,
            'assignmentable_id' => $lesson->id,
        ]);
        $this->assertTrue($this->service->canStudentViewAssignment($this->student, $assignment));
    }

    public function test_cannot_view_published_assignment_under_draft_lesson(): void
    {
        $lesson = Lesson::factory()->create(['publication_status' => 'draft']);
        $assignment = Assignment::factory()->create([
            'status' => 1,
            'assignmentable_type' => Lesson::class,
            'assignmentable_id' => $lesson->id,
        ]);
        $this->assertFalse($this->service->canStudentViewAssignment($this->student, $assignment));
    }

    public function test_cannot_view_published_assignment_under_draft_topic_lesson(): void
    {
        $lesson = Lesson::factory()->create(['publication_status' => 'draft']);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id]);
        $assignment = Assignment::factory()->create([
            'status' => 1,
            'assignmentable_type' => Topic::class,
            'assignmentable_id' => $topic->id,
        ]);
        $this->assertFalse($this->service->canStudentViewAssignment($this->student, $assignment));
    }

    // ---- Quizzes ---------------------------------------------------------

    public function test_can_view_active_quiz(): void
    {
        $quiz = CourseQuiz::factory()->create(['is_active' => 1]);
        $this->assertTrue($this->service->canStudentViewQuiz($this->student, $quiz));
    }

    public function test_cannot_view_inactive_quiz(): void
    {
        $quiz = CourseQuiz::factory()->create(['is_active' => 0]);
        $this->assertFalse($this->service->canStudentViewQuiz($this->student, $quiz));
    }

    // ---- assertVisibleOrFail --------------------------------------------

    public function test_assert_visible_or_fail_throws_404_for_draft_lesson(): void
    {
        $lesson = Lesson::factory()->create(['publication_status' => 'draft']);
        
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('เนื้อหานี้ยังไม่เปิดให้เข้าใช้งาน');
        
        try {
            $this->service->assertVisibleOrFail($lesson, $this->student, 404);
        } catch (HttpException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_assert_visible_or_fail_throws_403_for_draft_assignment(): void
    {
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create([
            'status' => 0,
            'assignmentable_type' => Course::class,
            'assignmentable_id' => $course->id,
        ]);
        
        $this->expectException(HttpException::class);
        
        try {
            $this->service->assertVisibleOrFail($assignment, $this->student, 403);
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }
}
