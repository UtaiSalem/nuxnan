<?php

namespace Tests\Unit\Services;

use App\Enums\CourseLifecycleState;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use App\Services\CourseLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the pure read-only checks in CourseLifecycleService and
 * Course::lifecycleState() derivation. No HTTP layer.
 */
class CourseLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private CourseLifecycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CourseLifecycleService::class);
    }

    // ---- Lifecycle state derivation -------------------------------------

    public function test_lifecycle_state_active_when_status_1_and_no_finalization(): void
    {
        $course = Course::factory()->create(['status' => 1]);

        $this->assertSame(CourseLifecycleState::Active, $course->lifecycleState());
        $this->assertTrue($course->is_enrollment_open);
        $this->assertFalse($course->is_post_end);
    }

    public function test_lifecycle_state_draft_when_status_2(): void
    {
        $course = Course::factory()->create(['status' => 2]);
        $this->assertSame(CourseLifecycleState::Draft, $course->lifecycleState());
    }

    public function test_lifecycle_state_enrollment_closed_when_status_4(): void
    {
        $course = Course::factory()->create(['status' => 4]);
        $this->assertSame(CourseLifecycleState::EnrollmentClosed, $course->lifecycleState());
        $this->assertFalse($course->is_enrollment_open);
    }

    public function test_lifecycle_state_enrollment_closed_when_end_date_past(): void
    {
        $course = Course::factory()->create([
            'status' => 1,
            'end_date' => now()->subDay(),
        ]);
        $this->assertSame(CourseLifecycleState::EnrollmentClosed, $course->lifecycleState());
    }

    public function test_finalization_status_takes_precedence_over_status_and_end_date(): void
    {
        $course = Course::factory()->create([
            'status' => 4,
            'end_date' => now()->subDay(),
            'finalization_status' => 'finalized',
        ]);
        $this->assertSame(CourseLifecycleState::Finalized, $course->lifecycleState());
    }

    public function test_lifecycle_state_archived_overrides_everything(): void
    {
        $course = Course::factory()->create([
            'status' => 1,
            'finalization_status' => 'archived',
        ]);
        $this->assertSame(CourseLifecycleState::Archived, $course->lifecycleState());
    }

    // ---- canEnroll -------------------------------------------------------

    public function test_can_enroll_allows_active_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 1]);

        $result = $this->service->canEnroll($course, $user);
        $this->assertTrue($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_OK, $result->reasonCode);
    }

    public function test_can_enroll_blocks_finalized_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'finalized']);

        $result = $this->service->canEnroll($course, $user);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_FINALIZED, $result->reasonCode);
    }

    public function test_can_enroll_blocks_archived_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'archived']);

        $result = $this->service->canEnroll($course, $user);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_ARCHIVED, $result->reasonCode);
    }

    public function test_can_enroll_blocks_course_with_past_end_date(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'status' => 1,
            'end_date' => now()->subDay(),
        ]);

        $result = $this->service->canEnroll($course, $user);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_ENROLLMENT_CLOSED, $result->reasonCode);
    }

    public function test_can_enroll_blocks_when_status_is_closed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 4]);

        $result = $this->service->canEnroll($course, $user);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_ENROLLMENT_CLOSED, $result->reasonCode);
    }

    public function test_can_enroll_blocks_draft_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 2]);

        $result = $this->service->canEnroll($course, $user);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_DRAFT, $result->reasonCode);
    }

    public function test_can_enroll_requires_authenticated_user(): void
    {
        $course = Course::factory()->create(['status' => 1]);

        $result = $this->service->canEnroll($course, null);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_NOT_AUTHENTICATED, $result->reasonCode);
    }

    // ---- canEnterCourse --------------------------------------------------

    public function test_existing_member_can_enter_finalized_course(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'finalization_status' => 'finalized',
        ]);
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);

        $result = $this->service->canEnterCourse($course, $student);
        $this->assertTrue($result->allowed);
    }

    public function test_non_member_cannot_enter_archived_course(): void
    {
        $stranger = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'archived']);

        $result = $this->service->canEnterCourse($course, $stranger);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_ARCHIVED, $result->reasonCode);
    }

    public function test_course_owner_can_enter_archived_course(): void
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'instructor_id' => $owner->id,
            'finalization_status' => 'archived',
        ]);

        $result = $this->service->canEnterCourse($course, $owner);
        $this->assertTrue($result->allowed);
    }

    // ---- canSubmitWork ---------------------------------------------------

    public function test_can_submit_quiz_when_course_active(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 1]);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);

        $result = $this->service->canSubmitWork($course, $member, 'quiz');
        $this->assertTrue($result->allowed);
    }

    public function test_cannot_submit_quiz_after_finalize(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'finalized']);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);

        $result = $this->service->canSubmitWork($course, $member, 'quiz');
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_WORK_LOCKED, $result->reasonCode);
    }

    public function test_cannot_submit_assignment_after_grading_starts(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'grading']);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);

        $result = $this->service->canSubmitWork($course, $member, 'assignment');
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_WORK_LOCKED, $result->reasonCode);
    }

    public function test_remediation_without_grant_is_blocked(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'finalized']);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);

        $result = $this->service->canSubmitWork($course, $member, 'remediation');
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_NO_REMEDIATION_GRANT, $result->reasonCode);
    }

    public function test_remediation_blocked_in_archived_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['finalization_status' => 'archived']);
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
            'enrollment_date' => now(),
        ]);

        $result = $this->service->canSubmitWork($course, $member, 'remediation');
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_ARCHIVED, $result->reasonCode);
    }

    // ---- Manager / instructor -------------------------------------------

    public function test_manager_blocked_on_archived_course(): void
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'instructor_id' => $owner->id,
            'finalization_status' => 'archived',
        ]);

        $result = $this->service->canManageAsInstructor($course, $owner);
        $this->assertFalse($result->allowed);
        $this->assertSame(CourseLifecycleService::REASON_ARCHIVED, $result->reasonCode);
    }

    public function test_manager_allowed_on_finalized_course(): void
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'instructor_id' => $owner->id,
            'finalization_status' => 'finalized',
        ]);

        $result = $this->service->canManageAsInstructor($course, $owner);
        $this->assertTrue($result->allowed);
    }

    // ---- Appeal window ---------------------------------------------------

    public function test_appeal_window_is_closed_for_active_course(): void
    {
        $course = Course::factory()->create(['status' => 1]);
        $this->assertFalse($this->service->appealWindowOpen($course));
    }
}
