<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #22 ประวัติกิจกรรม (backend)
 * - Q2: GET /{academy}/audit-logs (academyIndex) — ประวัติการแก้ไขเฉพาะโรงเรียนนั้น (audit_logs.academy_id)
 * - AL-S4: GET /{academy}/activity-log/export — ส่งออกไฟล์ (gate members.view/reports.view)
 */
class AcademyAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN');

        return $actor;
    }

    private function seedLog(Academy $academy): void
    {
        AuditLog::create([
            'academy_id' => $academy->id,
            'user_id' => $academy->user_id,
            'action' => 'updated',
            'entity_type' => Academy::class,
            'entity_id' => $academy->id,
            'module' => 'academy',
            'created_at' => now(),
        ]);
    }

    public function test_academy_index_scopes_to_the_academy(): void
    {
        $a = Academy::factory()->create();
        $b = Academy::factory()->create();
        $this->seedLog($a);
        $this->seedLog($a);
        $this->seedLog($b); // ของอีกโรงเรียน ต้องไม่ถูกนับ

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->getJson("/api/academies/{$a->id}/audit-logs");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_academy_index_blocked_for_non_member(): void
    {
        $a = Academy::factory()->create();
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/academies/{$a->id}/audit-logs")
            ->assertStatus(403);
    }

    public function test_activity_log_export_returns_file(): void
    {
        $a = Academy::factory()->create();

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->get("/api/academies/{$a->id}/activity-log/export");

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'spreadsheetml',
            strtolower($response->headers->get('content-type') ?? '')
        );
    }

    public function test_activity_log_export_blocked_for_non_member(): void
    {
        $a = Academy::factory()->create();
        $this->actingAs(User::factory()->create(), 'api')
            ->get("/api/academies/{$a->id}/activity-log/export")
            ->assertStatus(403);
    }
}
