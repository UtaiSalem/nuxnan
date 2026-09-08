<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementRouteGuardTest extends TestCase
{
    use RefreshDatabase;

    private function academyWithMember(array $permissions = []): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    public function test_non_member_is_blocked_from_every_school_management_module(): void
    {
        $academy = Academy::factory()->create();
        $user = User::factory()->create();

        $routes = [
            'fee-structures',
            'tuition-fees/summary',
            'expenses',
            'budgets',
            'staff',
            'staff/summary',
            'payroll',
            'payroll/summary',
            'reports/definitions',
            'analytics/overview',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user, 'api')
                ->getJson("/api/academies/{$academy->id}/{$route}");

            $this->assertEquals(
                403,
                $response->status(),
                "Route {$route} failed: expected 403, got {$response->status()}"
            );
            $this->assertEquals(
                'Not a member of this academy',
                $response->json('message'),
                "Route {$route} failed: expected message 'Not a member of this academy'"
            );
        }
    }

    public function test_plain_member_cannot_read_finance_or_hr_modules(): void
    {
        [$academy, $user] = $this->academyWithMember([]);

        $routes = [
            'fee-structures',
            'payments',
            'expenses',
            'budgets',
            'staff',
            'payroll',
            'leave-requests',
            'reports/definitions',
            'analytics/overview',
            'dashboard/widgets',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user, 'api')
                ->getJson("/api/academies/{$academy->id}/{$route}");

            $this->assertEquals(
                403,
                $response->status(),
                "Route {$route} failed: expected 403, got {$response->status()}"
            );
            $this->assertEquals(
                'Insufficient permissions',
                $response->json('message'),
                "Route {$route} failed: expected message 'Insufficient permissions'"
            );
        }
    }

    public function test_member_with_finance_view_passes_the_gate(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view']);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/fee-structures");

        // ถ้าเส้นไหนคืน 500 เพราะบั๊กเดิมของคอนโทรลเลอร์ ห้ามแก้คอนโทรลเลอร์ ให้ assert ว่า status ไม่ใช่ 401/403 แทน (อ้างอิง G8)
        $this->assertNotContains($response->status(), [401, 403], "Route should pass the gate, but got {$response->status()}");
    }

    public function test_member_with_staff_view_passes_the_gate(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/staff");

        // ถ้าเส้นไหนคืน 500 เพราะบั๊กเดิมของคอนโทรลเลอร์ ห้ามแก้คอนโทรลเลอร์ ให้ assert ว่า status ไม่ใช่ 401/403 แทน (อ้างอิง G8)
        $this->assertNotContains($response->status(), [401, 403], "Route should pass the gate, but got {$response->status()}");
    }

    public function test_member_with_reports_view_passes_the_gate(): void
    {
        [$academy, $user] = $this->academyWithMember(['reports.view']);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/reports/definitions");

        // ถ้าเส้นไหนคืน 500 เพราะบั๊กเดิมของคอนโทรลเลอร์ ห้ามแก้คอนโทรลเลอร์ ให้ assert ว่า status ไม่ใช่ 401/403 แทน (อ้างอิง G8)
        $this->assertNotContains($response->status(), [401, 403], "Route should pass the gate, but got {$response->status()}");
    }

    public function test_plain_member_can_still_use_self_service_routes(): void
    {
        [$academy, $user] = $this->academyWithMember([]);

        $requests = [
            ['method' => 'post', 'route' => 'staff-attendance/check-in', 'body' => []],
            ['method' => 'post', 'route' => 'staff-attendance/check-out', 'body' => []],
            ['method' => 'get', 'route' => 'leave-requests/leave-types', 'body' => []],
            ['method' => 'post', 'route' => 'leave-requests', 'body' => []],
            ['method' => 'get', 'route' => 'analytics/dashboard-stats', 'body' => []],
            ['method' => 'get', 'route' => 'analytics/student-stats', 'body' => []],
            ['method' => 'get', 'route' => 'analytics/teacher-pending-assignments', 'body' => []],
            ['method' => 'get', 'route' => 'dashboard/layout', 'body' => []],
            ['method' => 'get', 'route' => 'meetings/slots/available', 'body' => []],
            ['method' => 'get', 'route' => 'announcements', 'body' => []],
        ];

        foreach ($requests as $req) {
            $method = $req['method'].'Json';
            $response = $this->actingAs($user, 'api')
                ->$method("/api/academies/{$academy->id}/{$req['route']}", $req['body']);

            $this->assertNotContains(
                $response->status(),
                [401, 403],
                "Self service route {$req['route']} failed with status {$response->status()}"
            );
        }
    }

    public function test_non_member_is_still_blocked_on_self_service_routes(): void
    {
        $academy = Academy::factory()->create();
        $user = User::factory()->create();

        $routes = [
            'staff-attendance/check-in' => 'post',
            'staff-attendance/check-out' => 'post',
            'leave-requests/leave-types' => 'get',
            'analytics/dashboard-stats' => 'get',
        ];

        foreach ($routes as $route => $method) {
            $methodJson = $method.'Json';
            $response = $this->actingAs($user, 'api')
                ->$methodJson("/api/academies/{$academy->id}/{$route}");

            $this->assertEquals(
                403,
                $response->status(),
                "Non-member should be blocked on {$route}, but got {$response->status()}"
            );
        }
    }

    public function test_archived_academy_blocks_school_management_modules(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view']);

        // `archived_at` ไม่ได้อยู่ใน $fillable — ต้อง set ตรงแบบเดียวกับ AcademyController::archive()
        $academy->archived_at = now();
        $academy->save();

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/fee-structures");

        $this->assertEquals(403, $response->status());
        $this->assertEquals('academy_archived', $response->json('code'));
    }
}
