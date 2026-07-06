<?php

namespace Tests\Feature\Learn\Course;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicCreateUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $course;

    protected $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->course = Course::factory()->create([
            'user_id' => $this->admin->id,
        ]);
        $this->lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'min_read' => 0,
        ]);
    }

    /** @test */
    public function admin_can_create_topic_with_minimal_data()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", [
                'title' => 'Test Topic Only Title',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('topics', [
            'lesson_id' => $this->lesson->id,
            'title' => 'Test Topic Only Title',
            'content' => null,
            'min_read' => 0,
        ]);
    }

    /** @test */
    public function admin_can_create_topic_with_full_data()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", [
                'title' => 'Full Topic',
                'content' => 'Full Content',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'min_read' => 5,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('topics', [
            'title' => 'Full Topic',
            'content' => 'Full Content',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'min_read' => 5,
        ]);
    }

    /** @test */
    public function creating_topic_updates_lesson_min_read()
    {
        $initialMinRead = $this->lesson->min_read;

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", [
                'title' => 'Test Topic',
                'min_read' => 10,
            ]);

        $this->assertEquals($initialMinRead + 10, $this->lesson->fresh()->min_read);
    }

    /** @test */
    public function updating_topic_adjusts_lesson_min_read_atomically()
    {
        $topic = Topic::factory()->create([
            'lesson_id' => $this->lesson->id,
            'min_read' => 10,
        ]);
        $this->lesson->increment('min_read', 10);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/{$topic->id}", [
                'title' => 'Updated Topic',
                'min_read' => 15,
            ]);

        $this->assertEquals(15, $this->lesson->fresh()->min_read);
    }

    /** @test */
    public function non_admin_cannot_create_topic()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", [
                'title' => 'Unauthorized Topic',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function validation_errors_returned_for_missing_title()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", [
                'content' => 'Some content',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function youtube_url_must_be_https()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", [
                'title' => 'Test',
                'youtube_url' => 'http://unsecure.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['youtube_url']);
    }

    /** @test */
    public function sort_order_auto_increments()
    {
        Topic::factory()->create(['lesson_id' => $this->lesson->id, 'sort_order' => 1]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/topics", ['title' => 'Topic 2']);

        $this->assertDatabaseHas('topics', ['title' => 'Topic 2', 'sort_order' => 2]);
    }
}
