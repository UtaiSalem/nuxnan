<?php

namespace Tests\Feature\Activity;

use App\Exports\ActivityAttendanceExport;
use App\Models\Academy;
use App\Models\ActivityAttendance;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\SchoolEvent;
use App\Models\User;
use App\Services\Activity\ActivityAttendanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ActivityAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    // The whole reason rows come from the audience and not from activity_enrollments:
    // someone who never turned up has neither an enrollment nor an attendance row.
    public function test_audience_member_who_never_attended_still_appears_as_a_row(): void
    {
        [$event, $student] = $this->fixture(2);
        $report = app(ActivityAttendanceReport::class)->build($event);

        $this->assertCount(1, $report['rows']);
        $this->assertSame(2, $report['rows'][0]['not_recorded']);
        $this->assertSame(0.0, $report['rows'][0]['attendance_rate']);
    }

    public function test_event_with_no_sessions_reports_zero_rate_without_dividing_by_zero(): void
    {
        [$event] = $this->fixture(0);
        $report = app(ActivityAttendanceReport::class)->build($event);

        $this->assertSame(0, $report['sessions_total']);
        $this->assertSame(0.0, $report['rows'][0]['attendance_rate']);
    }

    public function test_counts_are_split_by_status(): void
    {
        [$event, $student, , , $sessions] = $this->fixture(5);
        $this->attend($event, $student, $sessions[0], 'present');
        $this->attend($event, $student, $sessions[1], 'late');
        $this->attend($event, $student, $sessions[2], 'leave');
        $this->attend($event, $student, $sessions[3], 'activity_leave');
        $this->attend($event, $student, $sessions[4], 'absent');

        $row = app(ActivityAttendanceReport::class)->build($event)['rows'][0];

        $this->assertSame(1, $row['present']);
        $this->assertSame(1, $row['late']);
        $this->assertSame(1, $row['leave']);
        $this->assertSame(1, $row['activity_leave']);
        $this->assertSame(1, $row['absent']);
        $this->assertSame(0, $row['not_recorded']);
    }

    public function test_attendance_rate_counts_present_and_late(): void
    {
        [$event, $student, , , $sessions] = $this->fixture(4);
        $this->attend($event, $student, $sessions[0], 'present');
        $this->attend($event, $student, $sessions[1], 'late');
        $this->attend($event, $student, $sessions[2], 'absent');
        $this->attend($event, $student, $sessions[3], 'absent');

        $this->assertSame(50.0, app(ActivityAttendanceReport::class)->build($event)['rows'][0]['attendance_rate']);
    }

    // "nobody took the register" is not the same claim as "the teacher marked them missing".
    public function test_not_recorded_is_not_folded_into_absent(): void
    {
        [$event, $student, , , $sessions] = $this->fixture(3);
        $this->attend($event, $student, $sessions[0], 'absent');

        $row = app(ActivityAttendanceReport::class)->build($event)['rows'][0];

        $this->assertSame(1, $row['absent']);
        $this->assertSame(2, $row['not_recorded']);
    }

    public function test_date_range_limits_the_denominator(): void
    {
        [$event, $student] = $this->fixture(0);
        $inRange = ActivitySession::create(['event_id' => $event->id, 'start_datetime' => '2026-03-10 09:00:00', 'status' => 'scheduled']);
        ActivitySession::create(['event_id' => $event->id, 'start_datetime' => '2026-05-10 09:00:00', 'status' => 'scheduled']);
        $this->attend($event, $student, $inRange, 'present');

        $service = app(ActivityAttendanceReport::class);

        $all = $service->build($event);
        $this->assertSame(2, $all['sessions_total']);
        $this->assertSame(50.0, $all['rows'][0]['attendance_rate']);

        $march = $service->build($event, '2026-03-01', '2026-03-31');
        $this->assertSame(1, $march['sessions_total']);
        $this->assertSame(100.0, $march['rows'][0]['attendance_rate']);
    }

    public function test_manager_can_pull_the_json_report(): void
    {
        [$event, $student, $owner, $academy, $sessions] = $this->fixture(2);
        $this->attend($event, $student, $sessions[0], 'present');

        $this->actingAs($owner, 'api')
            ->getJson($this->url($academy, $event))
            ->assertOk()
            ->assertJsonPath('sessions_total', 2)
            ->assertJsonPath('rows.0.user_id', $student->id)
            ->assertJsonPath('rows.0.present', 1)
            ->assertJsonPath('rows.0.not_recorded', 1);
    }

    public function test_non_manager_cannot_pull_the_report(): void
    {
        [$event, , , $academy] = $this->fixture(1);

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson($this->url($academy, $event))
            ->assertForbidden();
    }

    public function test_report_of_another_academys_event_is_not_found(): void
    {
        [$event] = $this->fixture(1);
        $ownerB = User::factory()->create();
        $academyB = Academy::factory()->create(['user_id' => $ownerB->id]);

        $this->actingAs($ownerB, 'api')
            ->getJson($this->url($academyB, $event))
            ->assertNotFound();
    }

    public function test_xlsx_format_downloads_a_spreadsheet(): void
    {
        Excel::fake();
        [$event, , $owner, $academy] = $this->fixture(1);

        $this->actingAs($owner, 'api')->get($this->url($academy, $event).'?format=xlsx')->assertOk();

        Excel::assertDownloaded("activity-attendance-{$event->id}-".now()->format('Ymd').'.xlsx', function (ActivityAttendanceExport $export) {
            // The sheet must carry the session count, which lives on the report and not on a row.
            return $export->array()[0][9] === 1;
        });
    }

    // A whole-school audience is 3,000+ people and a term is dozens of sessions. The report must
    // stay at three queries — session ids, one grouped aggregate, the roster — and join in memory.
    public function test_query_count_does_not_scale_with_people_or_sessions(): void
    {
        [$event, $student, , $academy, $sessions] = $this->fixture(6);
        foreach ($sessions as $session) {
            $this->attend($event, $student, $session, 'present');
        }
        foreach (range(1, 20) as $_) {
            $extra = User::factory()->create();
            DB::table('academy_members')->insert([
                'academy_id' => $academy->id, 'user_id' => $extra->id, 'status' => 2,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $event->update(['target_audience' => ['all' => true]]);

        DB::enableQueryLog();
        $report = app(ActivityAttendanceReport::class)->build($event);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(21, $report['rows']);
        $this->assertSame(3, $queries);
    }

    private function url(Academy $academy, SchoolEvent $event): string
    {
        return "/api/academies/{$academy->id}/events/{$event->id}/attendance-report";
    }

    private function attend(SchoolEvent $event, User $student, ActivitySession $session, string $status): void
    {
        $enrollment = ActivityEnrollment::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $student->id, 'semester' => '1', 'academic_year' => '2569'],
            ['status' => 'active'],
        );

        ActivityAttendance::create([
            'session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'status' => $status,
        ]);
    }

    private function fixture(int $sessionCount): array
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        DB::table('academy_members')->insert([
            'academy_id' => $academy->id, 'user_id' => $student->id, 'status' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $event = SchoolEvent::create([
            'academy_id' => $academy->id, 'created_by' => $owner->id, 'title' => 'Activity',
            'event_type' => 'activity', 'attendance_pattern' => 'recurring', 'start_datetime' => now(),
            'end_datetime' => now()->addDay(), 'status' => 'published', 'requires_registration' => false,
            'target_audience' => ['user_ids' => [$student->id]],
        ]);
        $sessions = [];
        for ($index = 0; $index < $sessionCount; $index++) {
            $sessions[] = ActivitySession::create([
                'event_id' => $event->id, 'start_datetime' => now()->addDays($index),
                'status' => 'scheduled', 'qr_token' => "token-{$index}",
                'qr_token_expires_at' => now()->addHour(),
            ]);
        }

        return [$event, $student, $owner, $academy, $sessions];
    }
}
