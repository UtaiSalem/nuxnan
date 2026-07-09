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

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$groupMember->id}");

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
}
