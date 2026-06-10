<?php

namespace Tests\Feature\Learn\Course;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\TopicImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopicImageDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $course;
    protected $lesson;
    protected $topic;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->course = Course::factory()->create([
            'user_id' => $this->admin->id
        ]);
        $this->lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
        ]);
        $this->topic = Topic::factory()->create([
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);
    }

    /** @test */
    public function admin_can_delete_topic_image()
    {
        $filename = 'test-image.jpg';
        $path = 'images/courses/lessons/topics/' . $filename;
        Storage::disk('public')->put($path, 'dummy content');

        $image = TopicImage::create([
            'topic_id' => $this->topic->id,
            'filename' => $filename
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/topics/{$this->topic->id}/images/{$image->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('topic_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    /** @test */
    public function cannot_delete_image_of_another_topic()
    {
        $topic2 = Topic::factory()->create([
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        $image = TopicImage::create([
            'topic_id' => $topic2->id,
            'filename' => 'topic2-image.jpg'
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/topics/{$this->topic->id}/images/{$image->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('topic_images', ['id' => $image->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_topic_image()
    {
        $user = User::factory()->create();
        $image = TopicImage::create([
            'topic_id' => $this->topic->id,
            'filename' => 'unauthorized.jpg'
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/topics/{$this->topic->id}/images/{$image->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('topic_images', ['id' => $image->id]);
    }

    /** @test */
    public function safe_delete_does_not_remove_file_if_used_elsewhere()
    {
        $filename = 'shared-image.jpg';
        $path = 'images/courses/lessons/topics/' . $filename;
        Storage::disk('public')->put($path, 'shared content');

        $image1 = TopicImage::create([
            'topic_id' => $this->topic->id,
            'filename' => $filename
        ]);

        $topic2 = Topic::factory()->create([
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        $image2 = TopicImage::create([
            'topic_id' => $topic2->id,
            'filename' => $filename
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/topics/{$this->topic->id}/images/{$image1->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('topic_images', ['id' => $image1->id]);
        $this->assertDatabaseHas('topic_images', ['id' => $image2->id]);
        
        // File should still exist because $image2 uses it
        Storage::disk('public')->assertExists($path);
    }
}
