<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\ReportDefinition;
use App\Models\Role;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * กัน regression ของ TypeError ใน ReportController::log() ที่เคยส่ง class-string + id
 * ผิดตำแหน่ง argument ทำให้ endpoint 500 (ต้องส่ง Model instance เป็น arg #2)
 */
class ReportAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN'); // bypass academy.permission (CheckAcademyPermission)

        return $actor;
    }

    private function makeDefinition(Academy $academy, User $creator): ReportDefinition
    {
        return ReportDefinition::create([
            'academy_id' => $academy->id,
            'code' => 'rpt_'.uniqid(), // code เป็น NOT NULL + unique ในตาราง
            'name' => 'รายงานทดสอบ',
            'category' => ReportDefinition::CATEGORY_ACADEMIC,
            'report_type' => ReportDefinition::TYPE_TABLE,
            'data_source' => 'custom',
            'columns' => ['name', 'score'],
            'created_by' => $creator->id,
        ]);
    }

    public function test_generate_report_does_not_500_and_writes_audit_log(): void
    {
        $actor = $this->superAdmin();
        $academy = Academy::factory()->create();
        $definition = $this->makeDefinition($academy, $actor);

        $response = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$academy->id}/reports/generate",
            [
                'definition_id' => $definition->id,
                'name' => 'สรุปผลการเรียน',
                'parameters' => [],
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $savedReportId = $response->json('data.id');
        $this->assertNotNull($savedReportId);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report_generated',
            'user_id' => $actor->id,
            'entity_type' => SavedReport::class,
            'entity_id' => $savedReportId,
        ]);
    }

    public function test_update_definition_does_not_500_and_writes_audit_log(): void
    {
        $actor = $this->superAdmin();
        $academy = Academy::factory()->create();
        $definition = $this->makeDefinition($academy, $actor);

        $response = $this->actingAs($actor, 'api')->patchJson(
            "/api/academies/{$academy->id}/reports/definitions/{$definition->id}",
            ['name' => 'รายงานที่แก้ชื่อแล้ว']
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report_definition_updated',
            'user_id' => $actor->id,
            'entity_type' => ReportDefinition::class,
            'entity_id' => $definition->id,
        ]);
    }
}
