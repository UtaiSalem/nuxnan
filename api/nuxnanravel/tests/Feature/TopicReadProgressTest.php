<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\TopicReadProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicReadProgressTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $student;

    protected $course;

    protected $lesson;

    protected $topics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->admin->id]);

        $this->lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'publication_status' => 'published',
        ]);

        // Create some topics
        $this->topics = Topic::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'published',
            'min_read' => 1, // 1 minute
        ]);
    }

    /** @test */
    public function student_can_start_reading_topic()
    {
        $topic = $this->topics[0];

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/start");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'progress' => [
                    'status' => 'in_progress',
                    'required_seconds' => 60,
                ],
            ]);

        $this->assertDatabaseHas('topic_read_progress', [
            'user_id' => $this->student->id,
            'topic_id' => $topic->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function start_reading_is_idempotent()
    {
        $topic = $this->topics[0];

        $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/start");

        $firstStartedAt = TopicReadProgress::first()->started_at->toDateTimeString();

        // Wait a bit
        $this->travel(5)->seconds();

        $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/start");

        $this->assertEquals($firstStartedAt, TopicReadProgress::first()->started_at->toDateTimeString());
    }

    /** @test */
    public function cannot_complete_without_starting()
    {
        $topic = $this->topics[0];

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/complete");

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function cannot_complete_too_fast()
    {
        $topic = $this->topics[0]; // min_read = 1 (60s)

        $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/start");

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/complete");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 'topic_read_too_fast',
            ]);

        $this->assertDatabaseHas('topic_read_progress', [
            'topic_id' => $topic->id,
            'violation_count' => 1,
        ]);
    }

    /** @test */
    public function can_complete_after_required_time()
    {
        $topic = $this->topics[0];

        $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/start");

        $this->travel(61)->seconds();

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/complete");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'progress' => [
                    'status' => 'completed',
                ],
            ]);

        // elapsed_seconds must be a positive integer stored in DB
        $progress = TopicReadProgress::where('user_id', $this->student->id)
            ->where('topic_id', $topic->id)
            ->first();
        $this->assertGreaterThanOrEqual(60, $progress->elapsed_seconds);
        $this->assertIsInt($progress->elapsed_seconds);
    }

    /** @test */
    public function elapsed_seconds_clamps_to_zero_on_edge_case()
    {
        $topic = $this->topics[0];

        // Manually create a progress with started_at slightly in the future (edge case)
        $progress = TopicReadProgress::create([
            'user_id' => $this->student->id,
            'topic_id' => $topic->id,
            'course_id' => $topic->course_id,
            'lesson_id' => $topic->lesson_id,
            'status' => TopicReadProgress::STATUS_IN_PROGRESS,
            'started_at' => now()->addSeconds(10),
            'required_seconds_snapshot' => 0, // no min_read requirement
        ]);

        $progress->markAsCompleted();
        $progress->refresh();

        $this->assertEquals(0, $progress->elapsed_seconds);
        $this->assertEquals('completed', $progress->status);
    }

    /** @test */
    public function complete_is_idempotent()
    {
        $topic = $this->topics[0];

        $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/start");
        $this->travel(61)->seconds();
        $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/complete");

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/topics/{$topic->id}/reading/complete");

        $response->assertStatus(200)
            ->assertJson(['already_completed' => true]);
    }

    /** @test */
    public function all_topics_completed_auto_completes_lesson()
    {
        foreach ($this->topics as $topic) {
            $this->actingAs($this->student, 'api')
                ->postJson("/api/topics/{$topic->id}/reading/start");
            $this->travel(61)->seconds();
            $this->actingAs($this->student, 'api')
                ->postJson("/api/topics/{$topic->id}/reading/complete");
        }

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function lesson_progress_summary_returns_all_topics()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson("/api/lessons/{$this->lesson->id}/topics/reading-progress");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'progress')
            ->assertJson([
                'summary' => [
                    'total_topics' => 2,
                    'completed_topics' => 0,
                ],
            ]);
    }
}
