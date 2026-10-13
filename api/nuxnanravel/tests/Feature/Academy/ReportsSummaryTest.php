<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #21 สถิติและรายงาน
 * - R-S2: GET /{academy}/reports/summary (gate reports.view) คืนการ์ด 4 ตัว
 * - R-S1: GET /{academy}/members/stats ต้องมีสิทธิ์ members.view (กัน cross-tenant leak)
 */
class ReportsSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN'); // bypass academy.permission + visibility (CheckAcademyPermission)

        return $actor;
    }

    public function test_summary_returns_four_cards_with_integer_values(): void
    {
        $academy = Academy::factory()->create();
        Course::factory()->count(3)->create(['academy_id' => $academy->id]);

        $actor = $this->superAdmin();

        $response = $this->actingAs($actor, 'api')
            ->getJson("/api/academies/{$academy->id}/reports/summary");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['total_students', 'total_staff', 'total_classrooms', 'total_courses'],
            ]);

        $data = $response->json('data');
        foreach (['total_students', 'total_staff', 'total_classrooms', 'total_courses'] as $key) {
            $this->assertIsInt($data[$key], "$key ต้องเป็นจำนวนเต็ม");
        }

        // courses เป็นแหล่งเดียวที่ seed ได้แน่นอนในเทสต์ — ต้องนับได้ 3
        $this->assertSame(3, $data['total_courses']);
        // academy ว่าง: การ์ดอื่นต้องเป็น 0 ไม่ใช่ error/null
        $this->assertSame(0, $data['total_students']);
        $this->assertSame(0, $data['total_staff']);
        $this->assertSame(0, $data['total_classrooms']);
    }

    public function test_summary_blocked_for_non_member(): void
    {
        $academy = Academy::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/reports/summary")
            ->assertStatus(403);
    }

    public function test_member_stats_requires_permission_for_non_member(): void
    {
        // R-S1: เดิม members/stats อยู่ในกลุ่ม auth:api เปล่า → ใครล็อกอินก็อ่านได้
        $academy = Academy::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/members/stats")
            ->assertStatus(403);
    }

    public function test_member_stats_accessible_to_privileged_user(): void
    {
        $academy = Academy::factory()->create();
        $actor = $this->superAdmin();

        $this->actingAs($actor, 'api')
            ->getJson("/api/academies/{$academy->id}/members/stats")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['stats' => ['total', 'approved'], 'role_distribution']);
    }
}
