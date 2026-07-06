<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Lesson;
use App\Models\Topic;
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
                'topics' => $newOrder,
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
                'topics' => [$topicIds[0]],
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
                'groups' => $newOrder,
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
                'groups' => [$groupIds[0]],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function reorder_rejects_topic_from_other_lesson()
    {
        $otherLesson = Lesson::factory()->create(['course_id' => $this->course->id]);
        $otherTopic = Topic::factory()->create([
            'lesson_id' => $otherLesson->id,
            'course_id' => $this->course->id,
            'user_id' => $this->admin->id,
        ]);

        $topicIds = $this->topics->pluck('id')->toArray();
        $invalidOrder = [$topicIds[0], $topicIds[1], $otherTopic->id];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", [
                'topics' => $invalidOrder,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function reorder_rejects_group_from_other_course()
    {
        $otherCourse = Course::factory()->create(['user_id' => $this->admin->id]);
        $otherGroup = CourseGroup::factory()->create([
            'course_id' => $otherCourse->id,
            'user_id' => $this->admin->id,
        ]);

        $groupIds = $this->groups->pluck('id')->toArray();
        $invalidOrder = [$groupIds[0], $groupIds[1], $otherGroup->id];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/courses/{$this->course->id}/groups/reorder", [
                'groups' => $invalidOrder,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function creating_topic_sets_sort_order_to_max_plus_one()
    {
        $maxSortOrder = $this->topics->max('sort_order');

        $newTopic = Topic::factory()->create([
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals($maxSortOrder + 1, $newTopic->sort_order);
    }

    /** @test */
    public function creating_course_group_sets_sort_order_to_max_plus_one()
    {
        $maxSortOrder = $this->groups->max('sort_order');

        $newGroup = CourseGroup::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals($maxSortOrder + 1, $newGroup->sort_order);
    }

    /** @test */
    public function reorder_is_idempotent()
    {
        $topicIds = $this->topics->pluck('id')->toArray();
        $newOrder = [$topicIds[2], $topicIds[0], $topicIds[1]];

        // First time
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", ['topics' => $newOrder])
            ->assertStatus(200);

        // Second time
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", ['topics' => $newOrder])
            ->assertStatus(200);

        $this->assertDatabaseHas('topics', ['id' => $topicIds[2], 'sort_order' => 1]);
        $this->assertDatabaseHas('topics', ['id' => $topicIds[0], 'sort_order' => 2]);
        $this->assertDatabaseHas('topics', ['id' => $topicIds[1], 'sort_order' => 3]);
    }

    /** @test */
    public function reorder_with_subset_of_ids_returns_422()
    {
        $topicIds = $this->topics->pluck('id')->toArray();
        $subset = [$topicIds[0], $topicIds[1]]; // Missing index 2

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", [
                'topics' => $subset,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function reorder_with_duplicate_ids_returns_422()
    {
        $topicIds = $this->topics->pluck('id')->toArray();
        $duplicates = [$topicIds[0], $topicIds[1], $topicIds[1]];

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/lessons/{$this->lesson->id}/topics/reorder", [
                'topics' => $duplicates,
            ]);

        $response->assertStatus(422);
    }
}
