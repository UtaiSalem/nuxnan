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

class LessonTopicGatedCompletionTest extends TestCase
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
            'access_type' => 'free',
        ]);

        $this->topics = Topic::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'published',
            'min_read' => 1,
        ]);
    }

    private function markTopicRead($topic): void
    {
        TopicReadProgress::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'topic_id' => $topic->id,
            'status' => TopicReadProgress::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'required_seconds_snapshot' => 60,
            'elapsed_seconds' => 300,
        ]);
    }

    /** @test */
    public function student_cannot_mark_lesson_read_when_no_topic_read()
    {
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 'topics_incomplete',
            ])
            ->assertJsonPath('topic_summary.total_topics', 2);

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function student_cannot_mark_lesson_read_when_topics_partially_read()
    {
        $this->markTopicRead($this->topics[0]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

        $response->assertStatus(422)
            ->assertJsonPath('topic_summary.completed_topics', 1);
    }

    /** @test */
    public function student_can_mark_lesson_read_when_all_topics_read()
    {
        $this->markTopicRead($this->topics[0]);
        $this->markTopicRead($this->topics[1]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

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
    public function lesson_without_topics_can_still_be_marked_read()
    {
        $lessonWithoutTopics = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'publication_status' => 'published',
            'access_type' => 'free',
            'min_read' => 0, // เฟส 2: บทเรียนไม่มีหัวข้อย่อยและไม่บังคับเวลา = มาร์คได้
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$lessonWithoutTopics->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
            ]);
    }

    /** @test */
    public function course_admin_bypasses_topic_gate()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
            ]);
    }

    /** @test */
    public function student_can_always_uncomplete_even_if_topics_incomplete()
    {
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'time_spent_seconds' => 600,
        ]);

        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'completed' => false,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function complete_endpoint_is_gated_too()
    {
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/lessons/{$this->lesson->id}/progress/complete");

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'topics_incomplete',
            ]);
    }
}
