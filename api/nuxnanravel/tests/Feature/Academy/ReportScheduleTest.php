<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\ReportSchedule;
use App\Models\Role;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Report schedule CRUD. Covers the updateSchedule bug: request field is
 * time_of_day but the column is scheduled_time — the old code passed the
 * validated array straight to update() and tried to write a non-existent
 * `time_of_day` column (SQL error). createSchedule mapped it; update did not.
 */
class ReportScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $u = User::factory()->create();
        $u->assignRole('SUPER_ADMIN');

        return $u;
    }

    private function savedReport(Academy $academy, User $actor): SavedReport
    {
        return SavedReport::create([
            'academy_id' => $academy->id,
            'definition_id' => null,
            'user_id' => $actor->id,
            'name' => 'รายงานสำหรับตั้งเวลา',
            'parameters' => [],
            'cached_data' => [],
            'generated_at' => now(),
        ]);
    }

    public function test_schedule_crud_and_time_of_day_maps_to_scheduled_time(): void
    {
        $actor = $this->actor();
        $academy = Academy::factory()->create();
        $report = $this->savedReport($academy, $actor);

        // create
        $create = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$academy->id}/reports/schedules",
            [
                'saved_report_id' => $report->id,
                'frequency' => ReportSchedule::FREQUENCY_WEEKLY,
                'day_of_week' => 1,
                'time_of_day' => '08:30',
                'export_format' => ReportSchedule::FORMAT_CSV,
                'recipients' => ['teacher@example.com'],
            ]
        );
        $create->assertStatus(201)->assertJsonPath('success', true);
        $scheduleId = $create->json('data.id');
        $this->assertStringStartsWith('08:30', (string) ReportSchedule::find($scheduleId)->scheduled_time);

        // update time_of_day → must land in scheduled_time (regression guard)
        $update = $this->actingAs($actor, 'api')->patchJson(
            "/api/academies/{$academy->id}/reports/schedules/{$scheduleId}",
            ['time_of_day' => '09:45', 'export_format' => ReportSchedule::FORMAT_PDF]
        );
        $update->assertStatus(200)->assertJsonPath('success', true);
        $fresh = ReportSchedule::find($scheduleId);
        $this->assertStringStartsWith('09:45', (string) $fresh->scheduled_time);
        $this->assertEquals(ReportSchedule::FORMAT_PDF, $fresh->export_format);

        // toggle status
        $active = $fresh->is_active;
        $toggle = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$academy->id}/reports/schedules/{$scheduleId}/toggle-status"
        );
        $toggle->assertStatus(200);
        $this->assertNotEquals($active, ReportSchedule::find($scheduleId)->is_active);

        // delete
        $delete = $this->actingAs($actor, 'api')->deleteJson(
            "/api/academies/{$academy->id}/reports/schedules/{$scheduleId}"
        );
        $delete->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('report_schedules', ['id' => $scheduleId]);
    }
}
