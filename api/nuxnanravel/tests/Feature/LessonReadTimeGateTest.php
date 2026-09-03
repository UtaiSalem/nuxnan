<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Topic;
use App\Models\TopicReadProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonReadTimeGateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $student;

    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->admin->id]);
    }

    private function makeLesson(int $minRead)
    {
        return Lesson::factory()->create([
            'course_id' => $this->course->id,
            'publication_status' => 'published',
            'access_type' => 'free',
            'min_read' => $minRead,
        ]);
    }

    /** @test */
    public function lesson_without_read_time_requirement_can_be_marked()
    {
        $lesson = $this->makeLesson(0);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
            ]);
    }

    /** @test */
    public function student_cannot_mark_lesson_read_before_spending_required_time()
    {
        $lesson = $this->makeLesson(2);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/toggle");

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'lesson_read_too_short',
            ])
            ->assertJsonPath('read_time.required_seconds', 120)
            ->assertJsonPath('read_time.spent_seconds', 0);
    }

    /** @test */
    public function student_can_mark_lesson_read_after_spending_required_time()
    {
        $lesson = $this->makeLesson(1);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
            'time_spent_seconds' => 60,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
            ]);
    }

    /** @test */
    public function course_admin_bypasses_read_time_gate()
    {
        $lesson = $this->makeLesson(30);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
            ]);
    }

    /** @test */
    public function all_topics_read_bypasses_read_time_gate()
    {
        $lesson = $this->makeLesson(30);

        $topics = Topic::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'lesson_id' => $lesson->id,
            'status' => 'published',
            'min_read' => 1,
        ]);

        foreach ($topics as $topic) {
            TopicReadProgress::create([
                'user_id' => $this->student->id,
                'course_id' => $this->course->id,
                'lesson_id' => $lesson->id,
                'topic_id' => $topic->id,
                'status' => TopicReadProgress::STATUS_COMPLETED,
                'started_at' => now()->subMinutes(5),
                'completed_at' => now(),
                'required_seconds_snapshot' => 60,
                'elapsed_seconds' => 300,
            ]);
        }

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
            ]);
    }

    /** @test */
    public function time_spent_is_clamped_on_first_update()
    {
        $lesson = $this->makeLesson(60);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/time-spent", ['seconds' => 3600]);

        $response->assertStatus(200)
            ->assertJsonPath('granted_seconds', 60);

        $this->assertDatabaseHas('lesson_progress', [
            'lesson_id' => $lesson->id,
            'time_spent_seconds' => 60,
        ]);
    }

    /** @test */
    public function time_spent_is_clamped_to_elapsed_wall_time_on_later_updates()
    {
        $lesson = $this->makeLesson(60);

        $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/time-spent", ['seconds' => 10]);

        $this->travel(20)->seconds();

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lesson->id}/progress/time-spent", ['seconds' => 3600]);

        $granted = $response->json('granted_seconds');
        $this->assertGreaterThan(0, $granted);
        $this->assertLessThanOrEqual(25, $granted);
    }

    /** @test */
    public function progress_show_returns_read_time_and_can_complete()
    {
        $lesson = $this->makeLesson(2);

        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/lessons/{$lesson->id}/progress");

        $response->assertStatus(200)
            ->assertJsonPath('read_time.required_seconds', 120)
            ->assertJsonPath('can_complete', false);
    }
}
