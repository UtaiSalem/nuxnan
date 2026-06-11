<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $student;

    protected $course;

    protected $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->admin->id]);

        $this->lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'publication_status' => 'published',
            'access_type' => 'free',
        ]);

        CourseMember::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function student_can_complete_lesson_via_endpoint()
    {
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/complete");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'completed' => true,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function student_can_toggle_lesson_completion()
    {
        // First complete it
        $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/complete");

        // Toggle (should uncomplete)
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'completed' => false,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'in_progress',
        ]);

        // Toggle again (should complete via service)
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'completed' => true,
            ]);
    }
}
