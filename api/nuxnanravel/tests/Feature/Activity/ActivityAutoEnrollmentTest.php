<?php

namespace Tests\Feature\Activity;

use App\Models\Academy;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\SchoolEvent;
use App\Models\User;
use App\Services\Activity\ActivityEnrollmentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityAutoEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(?array $audience = null): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = SchoolEvent::create([
            'academy_id' => $academy->id, 'created_by' => $owner->id, 'title' => 'Activity',
            'event_type' => 'activity', 'attendance_pattern' => 'recurring', 'start_datetime' => now(),
            'end_datetime' => now()->addDay(), 'status' => 'published', 'requires_registration' => false,
            'target_audience' => $audience,
        ]);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled', 'qr_token' => 'token', 'qr_token_expires_at' => now()->addHour()]);

        return [$owner, $academy, $event, $session];
    }

    private function member(Academy $academy, User $user): void
    {
        DB::table('academy_members')->insert(['academy_id' => $academy->id, 'user_id' => $user->id, 'status' => 2, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function checkIn(Academy $academy, SchoolEvent $event, ActivitySession $session, User $user)
    {
        return $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => 'token']);
    }

    public function test_audience_member_without_enrollment_can_check_in(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [$student->id]]);
        $this->member($academy, $student);
        $this->checkIn($academy, $event, $session, $student)->assertOk();
        $this->assertDatabaseCount('activity_enrollments', 1);
    }

    public function test_auto_created_enrollment_has_no_null_term_columns(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [$student->id]]);
        $this->member($academy, $student);
        $this->checkIn($academy, $event, $session, $student);
        $this->assertDatabaseMissing('activity_enrollments', ['semester' => null]);
        $this->assertDatabaseMissing('activity_enrollments', ['academic_year' => null]);
    }

    public function test_auto_created_enrollment_uses_the_current_academic_year(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [$student->id]]);
        $this->member($academy, $student);
        DB::table('academic_years')->insert(['academy_id' => $academy->id, 'name' => '2569', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->checkIn($academy, $event, $session, $student);
        $this->assertDatabaseHas('activity_enrollments', ['user_id' => $student->id, 'academic_year' => '2569']);
    }

    public function test_non_member_is_still_refused_on_null_audience_event(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(null);
        $this->checkIn($academy, $event, $session, $student)->assertStatus(422);
        $this->assertDatabaseCount('activity_enrollments', 0);
    }

    public function test_user_outside_target_audience_is_still_refused(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [User::factory()->create()->id]]);
        $this->member($academy, $student);
        $this->checkIn($academy, $event, $session, $student)->assertStatus(422);
        $this->assertDatabaseCount('activity_enrollments', 0);
    }

    public function test_teacher_scan_auto_enrolls_an_audience_member(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [$student->id]]);
        $this->member($academy, $student);
        DB::table('academy_members')->where('academy_id', $academy->id)->where('user_id', $student->id)->update(['member_code' => 123]);
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/scan", ['identifier' => '123'])->assertOk();
        $this->assertDatabaseHas('activity_enrollments', ['user_id' => $student->id]);
    }

    public function test_bulk_records_auto_enroll_members_and_report_skipped(): void
    {
        $member = User::factory()->create();
        $nonMember = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(null);
        $this->member($academy, $member);
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/records", ['records' => [['user_id' => $member->id, 'status' => 'present'], ['user_id' => $nonMember->id, 'status' => 'absent']]])->assertOk()->assertJsonPath('skipped_user_ids.0', $nonMember->id);
        $this->assertDatabaseHas('activity_attendances', ['user_id' => $member->id]);
    }

    // A dropped enrollment shares the unique-index columns, so firstOrCreate would hand it back.
    public function test_dropped_enrollment_is_not_silently_reinstated(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [$student->id]]);
        $this->member($academy, $student);
        $term = app(ActivityEnrollmentResolver::class)->currentTerm($event);
        ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'dropped'] + $term);
        $this->checkIn($academy, $event, $session, $student)->assertStatus(422);
        $this->assertDatabaseCount('activity_attendances', 0);
        $this->assertDatabaseMissing('activity_enrollments', ['user_id' => $student->id, 'status' => 'active']);
    }

    public function test_second_check_in_reuses_the_same_enrollment(): void
    {
        $student = User::factory()->create();
        [$owner, $academy, $event, $session] = $this->fixture(['user_ids' => [$student->id]]);
        $this->member($academy, $student);
        $this->checkIn($academy, $event, $session, $student)->assertOk();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/records", ['records' => [['user_id' => $student->id, 'status' => 'present']]])->assertOk();
        $this->assertDatabaseCount('activity_enrollments', 1);
    }
}
