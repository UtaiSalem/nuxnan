<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH /api/courses/{course}/members/update-last-viewed-group
 *
 * Backs the "remember the group I last viewed" preference on the course admin
 * pages (members, attendances, gradebook, ...). See useLastViewedGroup.ts.
 */
class CourseLastViewedGroupTest extends TestCase
{
    use RefreshDatabase;

    private function url(Course $course): string
    {
        return "/api/courses/{$course->id}/members/update-last-viewed-group";
    }

    /**
     * Regression: the course owner is an admin without a course_members row.
     * The endpoint used to 404 for them, which surfaced as the toast
     * "ไม่สามารถบันทึกกลุ่มเริ่มต้นได้" on /Learn/Courses/{id}/members.
     */
    public function test_non_member_owner_gets_successful_no_op()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner, 'api')
            ->patchJson($this->url($course), ['last_viewed_group_id' => $group->id])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('skipped', true);

        $this->assertDatabaseMissing('course_members', [
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_enrolled_member_preference_is_saved()
    {
        $owner = User::factory()->create();
        $teacher = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        $this->actingAs($teacher, 'api')
            ->patchJson($this->url($course), ['last_viewed_group_id' => $group->id])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('skipped');

        $this->assertSame($group->id, $member->fresh()->last_viewed_group_id);
    }

    /**
     * Regression: the column used to be tinyInteger (max 127) while storing real
     * group ids. Only MySQL enforces the range — sqlite passes either way.
     */
    public function test_group_id_above_tinyint_range_is_stored_intact()
    {
        $owner = User::factory()->create();
        $teacher = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $group = CourseGroup::factory()->create([
            'id' => 1000,
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        $this->actingAs($teacher, 'api')
            ->patchJson($this->url($course), ['last_viewed_group_id' => $group->id])
            ->assertStatus(200);

        $this->assertSame(1000, $member->fresh()->last_viewed_group_id);
    }

    public function test_group_from_another_course_is_rejected()
    {
        $owner = User::factory()->create();
        $teacher = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $otherCourse = Course::factory()->create(['user_id' => $owner->id]);
        $foreignGroup = CourseGroup::factory()->create([
            'course_id' => $otherCourse->id,
            'user_id' => $owner->id,
        ]);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        $this->actingAs($teacher, 'api')
            ->patchJson($this->url($course), ['last_viewed_group_id' => $foreignGroup->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('last_viewed_group_id');

        $this->assertNotSame($foreignGroup->id, $member->fresh()->last_viewed_group_id);
    }

    public function test_guest_is_rejected()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $group = CourseGroup::factory()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
        ]);

        $this->patchJson($this->url($course), ['last_viewed_group_id' => $group->id])
            ->assertStatus(401);
    }
}
