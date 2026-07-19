<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\Classroom;
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

    public function test_posting_to_department_scope_requires_membership()
    {
        $owner = User::factory()->create(['pp' => 1000]);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $department = AcademyGroup::create([
            'academy_id' => $academy->id,
            'name' => 'IT Department',
            'type' => 'department',
        ]);

        $member = User::factory()->create(['pp' => 1000]);
        $academy->members()->attach($member->id, ['status' => 2]);
        $department->members()->attach($member->id, ['role' => 'member']);

        $outsider = User::factory()->create(['pp' => 1000]);
        $academy->members()->attach($outsider->id, ['status' => 2]);

        // Non-member of the department must not be able to post into its feed
        $this->actingAs($outsider, 'api')
            ->postJson("/api/academies/{$academy->id}/posts", [
                'content' => 'Sneaky post',
                'scope_type' => 'department',
                'scope_id' => $department->id,
            ])
            ->assertStatus(403);

        // Department member can post
        $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/posts", [
                'content' => 'Department update',
                'scope_type' => 'department',
                'scope_id' => $department->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('post.scope_type', 'department')
            ->assertJsonPath('post.scope_id', $department->id);
    }

    public function test_posting_to_classroom_scope_requires_membership()
    {
        $owner = User::factory()->create(['pp' => 1000]);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $teacher = User::factory()->create(['pp' => 1000]);
        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);
        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $outsider = User::factory()->create(['pp' => 1000]);
        $academy->members()->attach($outsider->id, ['status' => 2]);

        // Academy member who is not in the classroom must not be able to post
        $this->actingAs($outsider, 'api')
            ->postJson("/api/academies/{$academy->id}/posts", [
                'content' => 'Sneaky classroom post',
                'scope_type' => 'classroom',
                'scope_id' => $classroom->id,
            ])
            ->assertStatus(403);

        // Homeroom teacher can post
        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/posts", [
                'content' => 'Classroom update',
                'scope_type' => 'classroom',
                'scope_id' => $classroom->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('post.scope_type', 'classroom')
            ->assertJsonPath('post.scope_id', $classroom->id);
    }

    public function test_posting_rejects_scope_from_another_academy_and_forged_academy_scope_id()
    {
        $owner = User::factory()->create(['pp' => 1000]);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $otherOwner = User::factory()->create(['pp' => 1000]);
        $otherAcademy = Academy::factory()->create(['user_id' => $otherOwner->id]);
        $otherDepartment = AcademyGroup::create([
            'academy_id' => $otherAcademy->id,
            'name' => 'Other Department',
            'type' => 'department',
        ]);

        // Department of another academy → 404, even for the academy owner
        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/posts", [
                'content' => 'Cross academy post',
                'scope_type' => 'department',
                'scope_id' => $otherDepartment->id,
            ])
            ->assertStatus(404);

        // Academy scope always binds to the current academy, ignoring forged scope_id
        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/posts", [
                'content' => 'Normal academy post',
                'scope_id' => $otherAcademy->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('post.scope_type', 'academy')
            ->assertJsonPath('post.scope_id', $academy->id);
    }
}
