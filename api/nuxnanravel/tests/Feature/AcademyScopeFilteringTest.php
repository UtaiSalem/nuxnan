<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\SchoolAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyScopeFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_announcement_index_filters_by_academy_scope_by_default()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        // Announcement A: academy scope
        SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'scope_type' => 'academy',
            'scope_id' => $academy->id,
            'created_by' => $owner->id,
            'title' => 'Academy Announcement',
            'content' => 'Content',
            'is_published' => true,
        ]);

        // Announcement B: department scope
        SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'scope_type' => 'department',
            'scope_id' => 999,
            'created_by' => $owner->id,
            'title' => 'Department Announcement',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Academy Announcement');
    }

    public function test_announcement_index_filters_by_department_scope_with_auth()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $department = AcademyGroup::create([
            'academy_id' => $academy->id,
            'name' => 'IT Department',
            'type' => 'department',
        ]);

        $member = User::factory()->create();
        $academy->members()->attach($member->id, ['status' => 2]);
        $department->members()->attach($member->id, ['role' => 'member']);

        // Announcement
        SchoolAnnouncement::create([
            'academy_id' => $academy->id,
            'scope_type' => 'department',
            'scope_id' => $department->id,
            'created_by' => $owner->id,
            'title' => 'IT Announcement',
            'content' => 'Content',
            'is_published' => true,
        ]);

        // Access as member
        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements?scope_type=department&scope_id={$department->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'IT Announcement');

        // Access as non-member outsider
        $outsider = User::factory()->create();
        $academy->members()->attach($outsider->id, ['status' => 2]);
        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/announcements?scope_type=department&scope_id={$department->id}")
            ->assertStatus(403);
    }

    public function test_activity_index_filters_by_academy_scope_by_default()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        // Post A: academy scope
        $postA = AcademyPost::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'content' => 'Academy Feed Post',
            'scope_type' => 'academy',
            'scope_id' => $academy->id,
        ]);

        Activity::create([
            'user_id' => $owner->id,
            'activityable_type' => AcademyPost::class,
            'activityable_id' => $postA->id,
            'activity_type' => 'create_post',
        ]);

        // Post B: department scope
        $postB = AcademyPost::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'content' => 'Department Feed Post',
            'scope_type' => 'department',
            'scope_id' => 999,
        ]);

        Activity::create([
            'user_id' => $owner->id,
            'activityable_type' => AcademyPost::class,
            'activityable_id' => $postB->id,
            'activity_type' => 'create_post',
        ]);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/activities")
            ->assertStatus(200)
            ->assertJsonCount(1, 'activities.data')
            ->assertJsonPath('activities.data.0.target_resource.content', 'Academy Feed Post');
    }

    public function test_get_activities_endpoint_filters_by_scope()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $department = AcademyGroup::create([
            'academy_id' => $academy->id,
            'name' => 'Support Department',
            'type' => 'department',
        ]);

        // Post A: academy scope
        $postA = AcademyPost::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'content' => 'Academy Feed Post',
            'scope_type' => 'academy',
            'scope_id' => $academy->id,
        ]);

        Activity::create([
            'user_id' => $owner->id,
            'activityable_type' => AcademyPost::class,
            'activityable_id' => $postA->id,
            'activity_type' => 'create_post',
        ]);

        // Post B: department scope
        $postB = AcademyPost::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'content' => 'Support Feed Post',
            'scope_type' => 'department',
            'scope_id' => $department->id,
        ]);

        Activity::create([
            'user_id' => $owner->id,
            'activityable_type' => AcademyPost::class,
            'activityable_id' => $postB->id,
            'activity_type' => 'create_post',
        ]);

        // Fetch using get-activities endpoint with scope params
        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/activities?scope_type=department&scope_id={$department->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'activities.data')
            ->assertJsonPath('activities.data.0.target_resource.content', 'Support Feed Post');
    }
}
