<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseGroupMemberRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_remove_from_group_clears_both_group_member_and_course_member_state()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
        ]);

        $groupMember = CourseGroupMember::create([
            'course_id' => $course->id,
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 1,
            'role' => 'member',
            'request_status' => 'approved',
        ]);

        $courseMember = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'group_id' => $group->id,
            'group_member_status' => 1,
        ]);

        // The route param is the member's user_id (what the frontend members list carries).
        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // The group membership row itself is removed (not just detached from CourseMember).
        $this->assertDatabaseMissing('course_group_members', ['id' => $groupMember->id]);

        // The member stays in the course, but their group pointer is reset.
        $courseMember->refresh();
        $this->assertNull($courseMember->group_id);
        $this->assertEquals(0, $courseMember->group_member_status);
        $this->assertDatabaseHas('course_members', [
            'id' => $courseMember->id,
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_also_clears_pending_join_request()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
            'privacy' => 'private',
        ]);

        $groupMember = CourseGroupMember::create([
            'course_id' => $course->id,
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 0,
            'role' => 'member',
            'request_status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$user->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('course_group_members', ['id' => $groupMember->id]);
    }

    public function test_remove_missing_member_returns_404()
    {
        $admin = User::factory()->create();
        $stranger = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$stranger->id}");

        $response->assertStatus(404)->assertJsonPath('success', false);
    }

    public function test_member_can_leave_group_themselves()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
        ]);

        $groupMember = CourseGroupMember::create([
            'course_id' => $course->id,
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 1,
            'role' => 'member',
            'request_status' => 'approved',
        ]);

        $courseMember = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'group_id' => $group->id,
            'group_member_status' => 1,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/members/leave");

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseMissing('course_group_members', ['id' => $groupMember->id]);
        $courseMember->refresh();
        $this->assertNull($courseMember->group_id);
        $this->assertEquals(0, $courseMember->group_member_status);
    }

    /**
     * Regression: the removal must target the member by user_id, not by
     * course_group_members.id. The old handler ran CourseGroupMember::find($param)
     * first, so if another member's row PK happened to equal the target's user_id
     * it deleted the WRONG member. Here we force that PK collision.
     */
    public function test_remove_targets_correct_member_when_ids_collide()
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $victim = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
        ]);

        // Victim's membership row is given a PK equal to the target's user_id.
        $victimMembership = new CourseGroupMember;
        $victimMembership->id = $target->id;
        $victimMembership->course_id = $course->id;
        $victimMembership->group_id = $group->id;
        $victimMembership->user_id = $victim->id;
        $victimMembership->status = 1;
        $victimMembership->role = 'member';
        $victimMembership->request_status = 'approved';
        $victimMembership->save();

        $targetMembership = CourseGroupMember::create([
            'course_id' => $course->id,
            'group_id' => $group->id,
            'user_id' => $target->id,
            'status' => 1,
            'role' => 'member',
            'request_status' => 'approved',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$target->id}")
            ->assertStatus(200);

        // The intended target is gone; the colliding victim is untouched.
        $this->assertDatabaseMissing('course_group_members', ['id' => $targetMembership->id]);
        $this->assertDatabaseHas('course_group_members', [
            'id' => $victimMembership->id,
            'user_id' => $victim->id,
        ]);
    }

    public function test_non_admin_cannot_remove_member()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $outsider = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
        ]);

        CourseGroupMember::create([
            'course_id' => $course->id,
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 1,
            'role' => 'member',
            'request_status' => 'approved',
        ]);

        $response = $this->actingAs($outsider, 'api')
            ->deleteJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$user->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('course_group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
    }
}
