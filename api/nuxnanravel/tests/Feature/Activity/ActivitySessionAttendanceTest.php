<?php

namespace Tests\Feature\Activity;

use App\Models\Academy;
use App\Models\ActivityAttendance;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitySessionAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function event(User $owner, Academy $academy, array $overrides = []): SchoolEvent
    {
        return SchoolEvent::create(array_merge([
            'academy_id' => $academy->id, 'created_by' => $owner->id, 'title' => 'Recurring activity',
            'event_type' => 'activity', 'attendance_pattern' => 'recurring', 'start_datetime' => now()->startOfDay(),
            'end_datetime' => now()->startOfDay()->addDay(), 'status' => 'published', 'requires_registration' => false,
        ], $overrides));
    }

    public function test_recurring_event_generates_expected_sessions(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events", [
            'title' => 'Recurring', 'event_type' => 'activity', 'attendance_pattern' => 'recurring',
            'start_datetime' => now()->startOfDay()->toDateTimeString(), 'end_datetime' => now()->addDays(2)->endOfDay()->toDateTimeString(),
            'status' => 'published', 'is_recurring' => true, 'recurrence_pattern' => ['frequency' => 'daily', 'until' => now()->addDays(2)->toDateString()],
        ])->assertCreated();
        $event = SchoolEvent::findOrFail($response->json('data.id'));
        $this->assertSame(3, ActivitySession::where('event_id', $event->id)->count());
    }

    public function test_qr_check_in_is_idempotent_and_invalid_token_is_refused(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'active']);
        $enrollment = ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'active']);
        $qr = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/refresh-qr")->assertOk();
        $token = $qr->json('qr_token');
        $this->assertSame("CHECKIN:ACTIVITY:{$academy->id}:{$event->id}:{$session->id}:{$token}", $qr->json('qr_content'));
        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => $token])->assertOk();
        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => $token])->assertStatus(422);
        $this->assertDatabaseCount('activity_attendances', 1);
        $this->assertNotNull($enrollment->fresh()->attendance_count);
        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/check-in", ['qr_token' => 'invalid'])->assertStatus(422);
    }

    public function test_teacher_scan_rejects_unknown_identifier(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create(['personal_code' => 'STUDENT-CODE']);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $enrollment = ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'active']);
        $url = "/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}";
        $this->actingAs($owner, 'api')->postJson("$url/scan", ['identifier' => 'missing'])->assertNotFound();
    }

    public function test_teacher_bulk_records_persist_remarks(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create(['personal_code' => 'STUDENT-CODE']);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'active']);

        $url = "/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}";
        $this->actingAs($owner, 'api')->postJson("$url/records", ['records' => [['user_id' => $student->id, 'status' => 'late', 'remarks' => 'traffic']]])->assertOk();
        $this->assertDatabaseHas('activity_attendances', ['session_id' => $session->id, 'user_id' => $student->id, 'status' => 'late', 'remarks' => 'traffic']);
    }

    // The activity endpoint reads user_id/remarks. student_id and remark belong to menu #18 and
    // used to be accepted here and dropped without a word — they must be refused out loud.
    public function test_bulk_records_rejects_keys_the_endpoint_does_not_read(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'active']);

        $url = "/api/academies/{$academy->id}/events/{$event->id}/sessions/{$session->id}/records";

        $this->actingAs($owner, 'api')
            ->postJson($url, ['records' => [['user_id' => $student->id, 'status' => 'late', 'remark' => 'traffic']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('records.0');

        $this->actingAs($owner, 'api')
            ->postJson($url, ['records' => [['student_id' => $student->id, 'status' => 'late', 'remarks' => 'traffic']]])
            ->assertStatus(422);

        $this->assertDatabaseCount('activity_attendances', 0);
    }

    public function test_student_attendance_summary_has_period_buckets(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $event = $this->event($owner, $academy);
        $session = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => now(), 'status' => 'scheduled']);
        $enrollment = ActivityEnrollment::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => 'active']);
        ActivityAttendance::create(['session_id' => $session->id, 'enrollment_id' => $enrollment->id, 'user_id' => $student->id, 'status' => 'late']);

        $this->actingAs($student, 'api')
            ->getJson("/api/academies/{$academy->id}/events/{$event->id}/enrollments/{$enrollment->id}/attendance-summary?period=daily")
            ->assertOk()
            ->assertJsonPath('period', 'daily');
    }
}
