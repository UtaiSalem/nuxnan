<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\ReportDefinition;
use App\Models\ReportSchedule;
use App\Models\Role;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: 6 auditLog->log() calls in ReportController used to pass a
 * class-string as arg2 (?Model) and an int id as arg3 (?array), which threw
 * a TypeError → 500 on every write endpoint. See worklog task_5fa50dd0.
 * These tests hit the three distinct call shapes and assert the endpoint
 * succeeds AND the audit row is written with the real entity type/id.
 */
class ReportAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN'); // bypass academy.permission

        return $actor;
    }

    private function makeDefinition(Academy $academy, User $creator): ReportDefinition
    {
        return ReportDefinition::create([
            'academy_id' => $academy->id,
            'created_by' => $creator->id,
            'code' => 'test-'.uniqid(),
            'name' => 'นิยามทดสอบ',
            'category' => ReportDefinition::CATEGORY_ACADEMIC,
            'report_type' => ReportDefinition::TYPE_TABLE,
            'data_source' => 'custom', // ไม่ตรง switch case → ข้ามการ query จริง
            'columns' => ['name'],
        ]);
    }

    public function test_create_definition_writes_audit_log_without_500(): void
    {
        $actor = $this->superAdmin();
        $academy = Academy::factory()->create();

        $response = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$academy->id}/reports/definitions",
            [
                'name' => 'รายงานผลการเรียน',
                'category' => ReportDefinition::CATEGORY_ACADEMIC,
                'report_type' => ReportDefinition::TYPE_TABLE,
                'data_source' => 'custom',
                'columns' => ['name', 'score'],
            ]
        );

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report_definition_created',
            'entity_type' => ReportDefinition::class,
            'entity_id' => $response->json('data.id'),
        ]);
    }

    public function test_generate_report_writes_audit_log_without_500(): void
    {
        $actor = $this->superAdmin();
        $academy = Academy::factory()->create();
        $definition = $this->makeDefinition($academy, $actor);

        $response = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$academy->id}/reports/generate",
            [
                'definition_id' => $definition->id,
                'name' => 'รายงานที่สร้าง',
            ]
        );

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report_generated',
            'entity_type' => SavedReport::class,
            'entity_id' => $response->json('data.id'),
        ]);
    }

    public function test_create_schedule_writes_audit_log_without_500(): void
    {
        $actor = $this->superAdmin();
        $academy = Academy::factory()->create();

        $report = SavedReport::create([
            'academy_id' => $academy->id,
            'definition_id' => null,
            'user_id' => $actor->id,
            'name' => 'รายงานสำหรับตั้งเวลา',
            'parameters' => [],
            'cached_data' => [],
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($actor, 'api')->postJson(
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

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report_schedule_created',
            'entity_type' => ReportSchedule::class,
            'entity_id' => $response->json('data.id'),
        ]);
    }
}
