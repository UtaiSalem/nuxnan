<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end coverage of the course lifecycle guard. Exercises the same
 * HTTP endpoints student users hit in production.
 */
class CourseLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->instructor = User::factory()->create();
    }

    private function makeCourse(array $attrs = [], int $autoAccept = 1): Course
    {
        $course = Course::factory()->create(array_merge([
            'user_id' => $this->instructor->id,
            'instructor_id' => $this->instructor->id,
            'tuition_fees' => 0,
        ], $attrs));

        CourseSetting::updateOrCreate(
            ['course_id' => $course->id],
            ['auto_accept_members' => $autoAccept]
        );

        return $course;
    }

    private function enrollAsMember(Course $course, User $user): CourseMember
    {
        return CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);
    }

    // ---- Enrollment guard -----------------------------------------------

    public function test_new_user_cannot_enroll_in_finalized_course(): void
    {
        $student = User::factory()->create(['wallet' => 1000]);
        $course = $this->makeCourse([
            'finalization_status' => 'finalized',
            'tuition_fees' => 500,
        ]);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'COURSE_FINALIZED');

        // Critical: wallet must NOT be charged on a blocked enrollment.
        $this->assertEquals(1000, $student->fresh()->wallet);
        $this->assertDatabaseMissing('course_members', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_new_user_cannot_enroll_in_archived_course(): void
    {
        $student = User::factory()->create(['wallet' => 1000]);
        $course = $this->makeCourse(['finalization_status' => 'archived']);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'COURSE_ARCHIVED');
    }

    public function test_new_user_cannot_enroll_after_end_date(): void
    {
        $student = User::factory()->create(['wallet' => 1000]);
        $course = $this->makeCourse([
            'status' => 1,
            'end_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'COURSE_ENROLLMENT_CLOSED');
    }

    public function test_new_user_cannot_enroll_when_status_closed(): void
    {
        $student = User::factory()->create(['wallet' => 1000]);
        $course = $this->makeCourse(['status' => 4]);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'COURSE_ENROLLMENT_CLOSED');
    }

    public function test_new_user_can_enroll_in_active_course(): void
    {
        $student = User::factory()->create(['wallet' => 1000]);
        $course = $this->makeCourse(['status' => 1, 'tuition_fees' => 0]);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(200);
        $this->assertDatabaseHas('course_members', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_enroll_is_idempotent_for_existing_member(): void
    {
        $student = User::factory()->create(['wallet' => 1000]);
        $course = $this->makeCourse(['status' => 1, 'tuition_fees' => 500]);
        $this->enrollAsMember($course, $student);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        $response->assertStatus(200);
        $response->assertJsonPath('already_member', true);
        // No double-charge.
        $this->assertEquals(1000, $student->fresh()->wallet);
        $this->assertEquals(1, CourseMember::where([
            'course_id' => $course->id,
            'user_id' => $student->id,
        ])->count());
    }

    // ---- Existing-member read access ------------------------------------

    public function test_existing_member_can_read_after_finalize(): void
    {
        // The member-only "my course detail" endpoint doesn't require enrollment
        // mutation, so we just verify the storemember idempotency works.
        $student = User::factory()->create();
        $course = $this->makeCourse(['finalization_status' => 'finalized']);
        $this->enrollAsMember($course, $student);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/members");

        // Existing-member short-circuit returns 200 even though enrollment
        // would otherwise be blocked.
        $response->assertStatus(200);
        $response->assertJsonPath('already_member', true);
    }
}
