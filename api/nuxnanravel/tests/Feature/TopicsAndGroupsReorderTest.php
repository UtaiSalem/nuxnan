<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicsAndGroupsReorderTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $course;
    protected $lesson;
    protected $topics;
    protected $groups;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->student = User::factory()->create();

        $this->course = Course::factory()->create(['user_id' => $this->admin->id]);
        $this->lesson = Lesson::factory()->create(['course_id' => $this->course->id]);

        $this->topics = Topic::factory()->count(3)->create([
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'user_id' => $this->admin->id,
        ]);

        $this->groups = CourseGroup::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function admin_can_reorder_topics()
    {
        $topicIds = $this->topics->pluck('id')->toArray();
        $newOrder = [$topicIds[2], $topicIds[0], $topicIds[1]];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", [
                'topics' => $newOrder
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('topics', ['id' => $topicIds[2], 'sort_order' => 1]);
        $this->assertDatabaseHas('topics', ['id' => $topicIds[0], 'sort_order' => 2]);
        $this->assertDatabaseHas('topics', ['id' => $topicIds[1], 'sort_order' => 3]);
    }

    /** @test */
    public function non_admin_cannot_reorder_topics()
    {
        $topicIds = $this->topics->pluck('id')->toArray();
        
        $response = $this->actingAs($this->student, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", [
                'topics' => [$topicIds[0]]
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_reorder_course_groups()
    {
        $groupIds = $this->groups->pluck('id')->toArray();
        $newOrder = [$groupIds[1], $groupIds[2], $groupIds[0]];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/groups/reorder", [
                'groups' => $newOrder
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('course_groups', ['id' => $groupIds[1], 'sort_order' => 1]);
        $this->assertDatabaseHas('course_groups', ['id' => $groupIds[2], 'sort_order' => 2]);
        $this->assertDatabaseHas('course_groups', ['id' => $groupIds[0], 'sort_order' => 3]);
    }

    /** @test */
    public function non_admin_cannot_reorder_course_groups()
    {
        $groupIds = $this->groups->pluck('id')->toArray();

        $response = $this->actingAs($this->student, 'api')
            ->patchJson("/api/courses/{$this->course->id}/groups/reorder", [
                'groups' => [$groupIds[0]]
            ]);

        $response->assertStatus(403);
    }
}
