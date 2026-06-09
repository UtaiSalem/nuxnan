<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLessonReorderTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $course;
    protected $lessons;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->student = User::factory()->create();
        
        $this->course = Course::factory()->create(['user_id' => $this->admin->id]);
        
        // Create some lessons
        $this->lessons = Lesson::factory()->count(3)->create([
            'course_id' => $this->course->id
        ]);
    }

    /** @test */
    public function course_admin_can_reorder_lessons()
    {
        $lessonIds = $this->lessons->pluck('id')->toArray();
        // New order: [ID2, ID0, ID1]
        $newOrder = [
            $lessonIds[2],
            $lessonIds[0],
            $lessonIds[1],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/lessons/reorder", [
                'lessons' => $newOrder
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify in DB - Controller sets order as index + 1
        $this->assertDatabaseHas('lessons', ['id' => $lessonIds[2], 'order' => 1]);
        $this->assertDatabaseHas('lessons', ['id' => $lessonIds[0], 'order' => 2]);
        $this->assertDatabaseHas('lessons', ['id' => $lessonIds[1], 'order' => 3]);
    }

    /** @test */
    public function non_admin_cannot_reorder_lessons()
    {
        $lessonIds = $this->lessons->pluck('id')->toArray();
        $newOrder = [$lessonIds[0]];

        $response = $this->actingAs($this->student, 'api')
            ->patchJson("/api/courses/{$this->course->id}/lessons/reorder", [
                'lessons' => $newOrder
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_reorder_lessons_from_another_course()
    {
        $otherCourse = Course::factory()->create();
        $otherLesson = Lesson::factory()->create(['course_id' => $otherCourse->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/lessons/reorder", [
                'lessons' => [$otherLesson->id]
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function reorder_with_empty_array_returns_422()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/lessons/reorder", [
                'lessons' => []
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function reorder_preserves_order_after_index_fetch()
    {
        $lessonIds = $this->lessons->pluck('id')->toArray();
        $newOrder = [$lessonIds[1], $lessonIds[2], $lessonIds[0]];

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/lessons/reorder", [
                'lessons' => $newOrder
            ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/courses/{$this->course->id}/lessons");

        $response->assertStatus(200);
        
        $lessons = $response->json()['lessons'];
        $returnedIds = collect(isset($lessons['data']) ? $lessons['data'] : $lessons)->pluck('id')->toArray();
        
        // Check if the order matches the newOrder
        $this->assertEquals($newOrder, $returnedIds);
    }
}
