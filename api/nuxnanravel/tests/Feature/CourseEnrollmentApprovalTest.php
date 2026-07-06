<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use App\Models\CourseSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup initial data if needed
    }

    /**
     * Test Step 3: auto_accept_members=0 + Paid Course
     * Result: Wallet NOT charged immediately, status=0, course_member_status=0
     */
    public function test_auto_accept_off_paid_course_enrollment_flow()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['wallet' => 1000]);

        $course = Course::factory()->create([
            'user_id' => $admin->id,
            'tuition_fees' => 500,
        ]);

        // Ensure course settings has auto_accept_members = 0
        CourseSetting::updateOrCreate(
            ['course_id' => $course->id],
            ['auto_accept_members' => 0]
        );

        // Act: User enrolls
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(200);

        // Assert: Wallet NOT charged
        $this->assertEquals(1000, $user->fresh()->wallet);

        // Assert: CourseMember created with pending status
        $this->assertDatabaseHas('course_members', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 0,
            'course_member_status' => 0,
        ]);

        $member = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->first();

        // Step 5: Admin approves
        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/approve");

        $response->assertStatus(200);

        // Assert: Status updated but still waiting for payment (status=0)
        $this->assertDatabaseHas('course_members', [
            'id' => $member->id,
            'status' => 0,
            'course_member_status' => 1,
        ]);

        // Step 7: User completes payment
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/complete-payment");

        $response->assertStatus(200);

        // Assert: Wallet charged
        $this->assertEquals(500, $user->fresh()->wallet);

        // Assert: Member now active
        $this->assertDatabaseHas('course_members', [
            'id' => $member->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);
    }

    /**
     * Test auto_accept_members=1 + Paid Course
     * Result: Wallet charged immediately, status=1, course_member_status=1
     */
    public function test_auto_accept_on_paid_course_enrollment_flow()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['wallet' => 1000]);

        $course = Course::factory()->create([
            'user_id' => $admin->id,
            'tuition_fees' => 500,
        ]);

        // Ensure course settings has auto_accept_members = 1
        CourseSetting::updateOrCreate(
            ['course_id' => $course->id],
            ['auto_accept_members' => 1]
        );

        // Act: User enrolls
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(200);

        // Assert: Wallet charged immediately
        $this->assertEquals(500, $user->fresh()->wallet);

        // Assert: CourseMember created with active status
        $this->assertDatabaseHas('course_members', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);
    }

    /**
     * Test Step 6: Admin rejects request
     */
    public function test_admin_rejects_enrollment_request()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $admin->id]);
        CourseSetting::updateOrCreate(['course_id' => $course->id], ['auto_accept_members' => 0]);

        // User enrolls
        $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/members");
        $member = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->first();

        // Admin rejects
        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/reject");

        $response->assertStatus(200);

        // Assert: CourseMember deleted
        $this->assertDatabaseMissing('course_members', ['id' => $member->id]);
    }

    /**
     * Test Step 1: Sync bug - approve group member should sync course_member_status
     */
    public function test_group_member_approval_syncs_course_member_status()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $course = Course::factory()->create(['user_id' => $admin->id]);
        CourseSetting::updateOrCreate(['course_id' => $course->id], ['auto_accept_members' => 0]);

        $group = CourseGroup::create([
            'course_id' => $course->id,
            'name' => 'Test Group',
            'user_id' => $admin->id,
            'privacy' => 'public',
        ]);

        // User requests to join group
        $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/groups/{$group->id}/members");

        // Should have a pending CourseMember
        $member = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->first();
        $this->assertEquals(0, $member->course_member_status);

        $groupMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', $user->id)->first();

        // Admin approves group member
        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$groupMember->id}/approve");

        $response->assertStatus(200);

        // Assert: course_member_status synced to 1
        $this->assertEquals(1, $member->fresh()->course_member_status);
    }

    /**
     * Test Priority 4: Free course group approval activates member (status=1)
     */
    public function test_free_course_group_approval_activates_member()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $course = Course::factory()->create([
            'user_id' => $admin->id,
            'tuition_fees' => 0,
        ]);
        CourseSetting::updateOrCreate(['course_id' => $course->id], ['auto_accept_members' => 0]);

        $group = CourseGroup::create([
            'course_id' => $course->id,
            'name' => 'Free Group',
            'user_id' => $admin->id,
            'privacy' => 'public',
        ]);

        // User requests to join group
        $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/groups/{$group->id}/members");

        $groupMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', $user->id)->first();

        // Admin approves group member
        $this->actingAs($admin, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/members/{$groupMember->id}/approve");

        // Assert: status is 1 because course is free
        $member = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->first();
        $this->assertEquals(1, $member->status);
        $this->assertEquals(1, $member->course_member_status);
    }

    /**
     * Test Priority 3: completePayment self-heals stuck free member
     */
    public function test_complete_payment_self_heals_stuck_free_member()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $course = Course::factory()->create([
            'user_id' => $admin->id,
            'tuition_fees' => 0,
        ]);

        // Manually create a stuck member: approved but status=0
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'course_member_status' => 1,
            'status' => 0,
        ]);

        // User calls complete-payment
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/complete-payment");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'เปิดใช้งานแล้ว');

        // Assert: status now 1
        $this->assertEquals(1, $member->fresh()->status);
    }
}
